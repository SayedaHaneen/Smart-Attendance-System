<?php
// includes/ai_fraud_detector.php - AI Fraud & Proxy Detection Validator

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates an attendance check-in request against security parameters and logs fraud ratings.
 * Returns [fraud_score, flags_array, is_approved]
 */
function detectAttendanceFraud($student_id, $session_id, $device_identifier, $latitude = null, $longitude = null) {
    $db = getDB();
    $fraud_score = 0;
    $flags = [];
    
    // 1. Fetch Session Info
    $sess_stmt = $db->prepare("SELECT * FROM attendance_sessions WHERE session_id = ?");
    $sess_stmt->bind_param("i", $session_id);
    $sess_stmt->execute();
    $session = $sess_stmt->get_result()->fetch_assoc();
    
    if (!$session) {
        return [
            'fraud_score' => 100,
            'flags' => ['INVALID_SESSION'],
            'is_approved' => false,
            'reason' => 'Attendance session does not exist.'
        ];
    }
    
    // 2. Check for Duplicate Device Identifier (Proxy Check)
    // Has this device been used to mark attendance for ANOTHER student during THIS session?
    $dup_stmt = $db->prepare("SELECT student_id FROM attendance WHERE session_id = ? AND device_id IN (
                                  SELECT device_id FROM student_devices WHERE device_identifier = ?
                              ) AND student_id != ?");
    $dup_stmt->bind_param("isi", $session_id, $device_identifier, $student_id);
    $dup_stmt->execute();
    $dup_res = $dup_stmt->get_result();
    
    if ($dup_res->num_rows > 0) {
        $fraud_score += 55;
        $flags[] = 'DUPLICATE_DEVICE_SCAN';
    }
    
    // 3. Check for Impossible Speed / Rapid Scan Check
    // Did this student scan in under 5 seconds since the session started?
    $start_timestamp = strtotime($session['session_date'] . ' ' . $session['start_time']);
    $time_diff = time() - $start_timestamp;
    if ($time_diff < 5 && $time_diff > 0) {
        $fraud_score += 25;
        $flags[] = 'SUSPICIOUS_SPEED_SCAN';
    }
    
    // 4. GPS Geofencing Check (Optional room validation check)
    // If the room has latitude and longitude defined in the schedules/rooms system, check it
    if ($latitude !== null && $longitude !== null) {
        // Find if this session is associated with a schedule and room
        $room_stmt = $db->prepare("SELECT r.* FROM rooms r 
                                   JOIN schedules s ON s.room_id = r.room_id
                                   WHERE s.teacher_id = ? AND s.start_time <= ? AND s.end_time >= ? LIMIT 1");
        $now_time = date('H:i:s');
        $room_stmt->bind_param("iss", $session['teacher_id'], $now_time, $now_time);
        $room_stmt->execute();
        $room = $room_stmt->get_result()->fetch_assoc();
        
        if ($room) {
            // Distance calculation using Haversine formula
            $earth_radius = 6371000; // in meters
            $latFrom = deg2rad($latitude);
            $lonFrom = deg2rad($longitude);
            $latTo = deg2rad($room['latitude']);
            $lonTo = deg2rad($room['longitude']);
            
            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;
            
            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            $distance = $angle * $earth_radius;
            
            $allowed_radius = $room['radius_meters'] > 0 ? $room['radius_meters'] : 20;
            
            if ($distance > $allowed_radius) {
                $fraud_score += 45;
                $flags[] = 'GPS_OUT_OF_BOUNDS';
            }
        }
    }
    
    // Cap at 100
    $fraud_score = min($fraud_score, 100);
    $is_approved = ($fraud_score < 75); // Block check-in if fraud score is critical
    
    // Save to fraud audit table
    if ($fraud_score > 0) {
        $flag_str = implode(',', $flags);
        $audit_stmt = $db->prepare("INSERT INTO fraud_audits (attendance_id, fraud_score, flags, device_fingerprint) 
                                    VALUES (?, ?, ?, ?)");
        // Since attendance record might not be inserted yet, use 0 or link it later
        $temp_att_id = 0;
        $audit_stmt->bind_param("idss", $temp_att_id, $fraud_score, $flag_str, $device_identifier);
        $audit_stmt->execute();
    }
    
    return [
        'fraud_score' => $fraud_score,
        'flags' => $flags,
        'is_approved' => $is_approved,
        'reason' => empty($flags) ? 'All security checks passed.' : 'Potential proxy detected: ' . implode(', ', $flags)
    ];
}
?>

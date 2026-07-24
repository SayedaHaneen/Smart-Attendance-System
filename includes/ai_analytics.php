<?php
// includes/ai_analytics.php - AI Analytics & Recommendations Forecasting Engine

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Calculates attendance status details and builds predictions for a student in a specific course.
 */
function predictStudentAttendance($student_id, $subject_id) {
    $db = getDB();
    
    // 1. Fetch historical attendance data
    $att_query = "SELECT status, attendance_date, DAYOFWEEK(attendance_date) as day_num 
                  FROM attendance 
                  WHERE student_id = ? AND session_id IN (
                      SELECT session_id FROM attendance_sessions WHERE subject_id = ?
                  )";
    $stmt = $db->prepare($att_query);
    $stmt->bind_param("ii", $student_id, $subject_id);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $total_classes = count($history);
    if ($total_classes === 0) {
        return [
            'risk_score' => 0,
            'confidence' => 100,
            'reason' => 'No historical classes recorded for this course.',
            'recommendation' => 'Monitor the next 3 check-ins to build baseline predictions.'
        ];
    }
    
    $present = 0;
    $absent = 0;
    $late = 0;
    $friday_absences = 0;
    
    foreach ($history as $h) {
        if ($h['status'] === 'Present') $present++;
        elseif ($h['status'] === 'Absent') $absent++;
        elseif ($h['status'] === 'Late') $late++;
        
        // Track Friday absences (DAYOFWEEK: 1 = Sunday, 6 = Friday, 7 = Saturday)
        if ($h['status'] === 'Absent' && $h['day_num'] == 6) {
            $friday_absences++;
        }
    }
    
    $attendance_rate = (($present + ($late * 0.5)) / $total_classes) * 100;
    
    // 2. Predictive Modelling
    $risk_score = 0;
    $reasons = [];
    $confidence = 80; // Base confidence
    
    // Rule A: Attendance threshold risk
    if ($attendance_rate < 75) {
        $risk_score += 40 + (75 - $attendance_rate) * 2;
        $reasons[] = "Current attendance is " . round($attendance_rate, 1) . "%, which is below the mandatory 75% threshold.";
    } else {
        $risk_score += (100 - $attendance_rate) * 0.5;
    }
    
    // Rule B: High Late-checkins count
    if ($late > 3) {
        $risk_score += 15;
        $reasons[] = "Repeated tardiness patterns detected ($late late entries).";
    }
    
    // Rule C: Friday absentee patterns
    if ($friday_absences >= 2) {
        $risk_score += 10;
        $reasons[] = "High absenteeism recorded specifically on Fridays.";
    }
    
    // Caps
    $risk_score = min(max(round($risk_score, 1), 0), 100);
    
    // 3. Formulate Actionable Recommendation
    if ($risk_score >= 70) {
        $recommendation = "HOD intervention required. Schedule a student counseling session and notify parents of immediate attendance shortage risk.";
    } elseif ($risk_score >= 40) {
        $recommendation = "Issue an automated email reminder to the student. Suggest attending makeup sessions or extra lectures.";
    } else {
        $recommendation = "Maintain regular attendance. Student is currently in the safe compliance zone.";
    }
    
    if (empty($reasons)) {
        $reasons[] = "Steady attendance parameters maintained.";
    }
    
    // Save prediction details to database for audit checks
    $save_stmt = $db->prepare("INSERT INTO ai_predictions (student_id, subject_id, risk_score, confidence_score, prediction_type, reason, recommendation)
                               VALUES (?, ?, ?, ?, 'Shortage', ?, ?)
                               ON DUPLICATE KEY UPDATE risk_score = VALUES(risk_score), confidence_score = VALUES(confidence_score), reason = VALUES(reason), recommendation = VALUES(recommendation)");
    $reason_str = implode(" | ", $reasons);
    $save_stmt->bind_param("iiddss", $student_id, $subject_id, $risk_score, $confidence, $reason_str, $recommendation);
    $save_stmt->execute();
    
    return [
        'risk_score' => $risk_score,
        'confidence' => $confidence,
        'reason' => $reason_str,
        'recommendation' => $recommendation
    ];
}

/**
 * Predicts the probability of the student dropping out.
 */
function predictDropoutRisk($student_id) {
    $db = getDB();
    
    // Fetch average risk score across all predictions
    $res = $db->query("SELECT AVG(risk_score) as avg_risk, COUNT(*) as courses FROM ai_predictions WHERE student_id = " . intval($student_id));
    $data = $res->fetch_assoc();
    
    $avg_risk = $data['avg_risk'] ?? 0;
    $courses = $data['courses'] ?? 0;
    
    if ($courses === 0) {
        return ['dropout_risk' => 0, 'status' => 'Low Risk', 'message' => 'Insufficient course metrics to run prediction.'];
    }
    
    $dropout_risk = min(max(round($avg_risk, 1), 0), 100);
    $status = 'Low Risk';
    if ($dropout_risk >= 70) $status = 'High Risk - CRITICAL';
    elseif ($dropout_risk >= 40) $status = 'Medium Risk - Warned';
    
    return [
        'dropout_risk' => $dropout_risk,
        'status' => $status,
        'message' => "Calculated based on $courses course profiles. HOD alert status is currently " . ($dropout_risk >= 45 ? "ACTIVE" : "INACTIVE")
    ];
}
?>

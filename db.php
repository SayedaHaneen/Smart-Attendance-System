<?php
// db.php - Database Connection

require_once 'config.php';

function getConnection() {
    return getDB();
}

function executeQuery($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->get_result()->fetch_assoc();
}

function insertRecord($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->insert_id;
}

function updateRecord($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->affected_rows;
}

function deleteRecord($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->affected_rows;
}
?>
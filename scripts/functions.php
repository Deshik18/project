<?php
require_once 'scripts/db.php';
session_start();

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags($data));
}

function getsemgrades($rollno) {
    $sql = "SELECT * FROM academic_sem_grades_with_header WHERE rollno = ? ORDER BY semno";
    $params = [$rollno];
    $types = "s";
    return executeQuery($sql, $params, $types);
}

function getnamefromroll($rollno) {
    $sql = "SELECT name FROM roll_name_map WHERE rollno= ?";
    $params = [$rollno];
    $types = "s";
    return executeQuery($sql, $params, $types);
}
?>

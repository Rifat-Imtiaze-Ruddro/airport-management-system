<?php
function requireLogin() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }
}

function requireRole($allowed_roles) {
    requireLogin();
    
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], (array)$allowed_roles)) {
        header("Location: unauthorized.php");
        exit();
    }
}

function canAccessFlightManagement() {
    if (!isset($_SESSION['user_role'])) return false;
    $allowed_roles = ['administrator', 'airport_manager', 'airline_staff'];
    return in_array($_SESSION['user_role'], $allowed_roles);
}

function canAccessServiceRequests() {
    if (!isset($_SESSION['user_role'])) return false;
    $allowed_roles = ['administrator', 'airport_manager', 'airline_staff', 'service_staff'];
    return in_array($_SESSION['user_role'], $allowed_roles);
}

function canAccessCommunications() {
    // All roles can access communications
    return true;
}

function canAccessAdminFunctions() {
    if (!isset($_SESSION['user_role'])) return false;
    $allowed_roles = ['administrator', 'airport_manager'];
    return in_array($_SESSION['user_role'], $allowed_roles);
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}
?>
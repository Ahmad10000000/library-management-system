<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function requireLogin() {
  if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
  }
}

function isLoggedIn() {
  return !empty($_SESSION["user_id"]);
}

function currentUserName() {
  return $_SESSION["user_name"] ?? "User";
}


function currentUserRole() {
  return $_SESSION["user_role"] ?? "user";
}


function isAdmin() {
  return currentUserRole() === "admin";
}

function requireAdmin() {
  requireLogin();
  if (!isAdmin()) {
    http_response_code(403);
    die("Forbidden: Admin only");
  }
}

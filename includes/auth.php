<?php

require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}
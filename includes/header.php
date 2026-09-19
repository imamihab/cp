<?php

require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = $page_title ?? 'Dashboard';
$user = $_SESSION['user'] ?? null;
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= htmlspecialchars($page_title) ?>
        - <?= APP_NAME ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/style.css"
    >
</head>

<body>

<div class="app-wrapper">
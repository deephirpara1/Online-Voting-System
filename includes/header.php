<?php
/**
 * Shared Header Template
 * Includes <head> with all CSS/CDN dependencies.
 *
 * Usage: Set $pageTitle before including this file.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VoteSecure — Secure Online Voting System">
    <title><?= sanitize($pageTitle ?? 'VoteSecure') ?> | <?= APP_NAME ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= getBaseUrl() ?>/assets/images/favicon.ico">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- Custom Stylesheet -->
    <link href="<?= getBaseUrl() ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

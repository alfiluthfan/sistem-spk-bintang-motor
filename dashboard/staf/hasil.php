<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../src/Auth.php';

$basePath = '../../';

if (!Auth::isLoggedIn()) {
    header('Location: ' . $basePath . 'login.php');
    exit;
}

if (Auth::isManajemen()) {
    header('Location: ' . $basePath . 'dashboard/manajemen/index.php');
    exit;
}

$activeMenu = 'hasil';
$breadcrumbCurrent = 'Hasil Perangkingan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Perangkingan - Sistem Pendukung Keputusan</title>
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/app.css">
</head>
<body class="bg-gray-100">
<div class="flex min-h-screen">

    <?php require __DIR__ . '/../../partials/sidebar_staf.php'; ?>

    <div class="flex-1 flex flex-col">

        <?php require __DIR__ . '/../../partials/topbar.php'; ?>

        <main class="flex-1 p-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Hasil Perangkingan</h1>

            <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
                Halaman <span class="font-semibold text-gray-700">Hasil Perangkingan</span> sedang dalam pengembangan.
            </div>
        </main>

    </div>

</div>
</body>
</html>

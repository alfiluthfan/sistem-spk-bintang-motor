<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../src/Auth.php';

if (!Auth::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Staf - Sistem Pendukung Keputusan</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body class="min-h-screen bg-canvas p-6">
    <main class="max-w-3xl mx-auto bg-white border border-gray-200 rounded-lg p-8">
        <h1 class="text-xl font-bold text-gray-900">Dashboard Staf Penjualan / Admin IT</h1>
        <p class="mt-2 text-gray-600">
            Selamat datang, <?= htmlspecialchars((string) $_SESSION['nama'], ENT_QUOTES, 'UTF-8') ?>.
            Halaman ini adalah placeholder — kelola data kriteria, alternatif, dan nilai alternatif di sini.
        </p>
        <a href="../logout.php" class="inline-block mt-6 text-sm font-semibold text-gray-900 underline">
            Keluar
        </a>
    </main>
</body>
</html>

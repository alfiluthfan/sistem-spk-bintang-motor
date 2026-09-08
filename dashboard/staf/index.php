<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/DashboardStaffService.php';

Auth::requireRole('staf');

$user = Auth::user();

$dashboardService = new DashboardStaffService(
    Database::connect()
);

$dashboard = $dashboardService->getDashboardData();

$activePage = 'dashboard';

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatTanggal(?string $tanggal): string
{
    if (!$tanggal) {
        return '-';
    }

    return date(
        'd/m/Y',
        strtotime($tanggal)
    );
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Dashboard Staf | SPK Motor
    </title>

    <link
        rel="stylesheet"
        href="../../assets/css/app.css">

</head>


<body class="bg-zinc-100 text-zinc-900">

    <?php
    require __DIR__ .
        '/../../partials/staf-sidebar.php';
    ?>


    <!-- Main -->
    <div class="min-h-screen lg:ml-60">

        <!-- Topbar -->
        <header
            class="sticky top-0 z-30 flex h-16
                   items-center justify-between
                   border-b border-zinc-200
                   bg-white px-4
                   sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">

                <!-- Mobile sidebar button -->
                <button
                    type="button"
                    id="sidebarButton"
                    class="flex h-9 w-9 items-center
                           justify-center rounded-md
                           border border-zinc-300
                           lg:hidden"
                    aria-label="Buka menu">
                    ☰
                </button>


                <!-- Breadcrumb -->
                <div
                    class="hidden items-center gap-2
                           text-sm sm:flex">

                    <span class="text-zinc-500">
                        Dealer Bintang Motor Cinere
                    </span>

                    <span class="text-zinc-400">
                        /
                    </span>

                    <span class="font-semibold text-zinc-900">
                        Dashboard
                    </span>

                </div>

                <span
                    class="font-semibold sm:hidden">
                    Dashboard
                </span>

            </div>


            <!-- User -->
            <div class="flex items-center gap-3">

                <div
                    class="flex h-8 w-8 items-center
                           justify-center rounded-full
                           border border-zinc-300
                           text-sm text-zinc-500">
                    ♟
                </div>

                <div
                    class="hidden text-sm sm:block">
                    <span class="font-medium">
                        Staf
                    </span>

                    <span class="text-zinc-400">
                        —
                    </span>

                    <span>
                        <?= e($user['nama'] ?? 'Pengguna'); ?>
                    </span>
                </div>

            </div>

        </header>


        <!-- Content -->
        <main class="p-4 sm:p-6 lg:p-8">

            <!-- Page title -->
            <div class="mb-6">

                <h1
                    class="text-2xl font-bold
                           tracking-tight">
                    Dashboard Staf Penjualan
                </h1>

                <p
                    class="mt-1 text-sm text-zinc-500">
                    Ringkasan data operasional Sistem
                    Pendukung Keputusan Dealer Bintang
                    Motor Cinere.
                </p>

            </div>


            <!-- Summary cards -->
            <section
                class="grid grid-cols-1 gap-4
                       sm:grid-cols-2
                       xl:grid-cols-4">

                <!-- Alternatif -->
                <article
                    class="rounded-md border
                           border-zinc-300
                           bg-white p-5">

                    <p
                        class="text-xs font-medium
                               uppercase text-zinc-500">
                        Total Alternatif
                    </p>

                    <p
                        class="mt-2 text-2xl
                               font-bold">
                        <?= $dashboard['total_alternatif']; ?>
                        Motor
                    </p>

                </article>


                <!-- Kriteria -->
                <article
                    class="rounded-md border
                           border-zinc-300
                           bg-white p-5">

                    <p
                        class="text-xs font-medium
                               uppercase text-zinc-500">
                        Total Kriteria
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold">
                        <?= $dashboard['total_kriteria']; ?>
                        Kriteria
                    </p>

                </article>


                <!-- Periode -->
                <article
                    class="rounded-md border
                           border-zinc-300
                           bg-white p-5">

                    <p
                        class="text-xs font-medium
                               uppercase text-zinc-500">
                        Periode Aktif
                    </p>

                    <?php if (
                        $dashboard['periode_aktif'] !== null
                    ): ?>

                        <p
                            class="mt-2 truncate
                                   text-xl font-bold">
                            <?= e(
                                $dashboard['periode_aktif']['nama_periode']
                            ); ?>
                        </p>

                        <p
                            class="mt-1 text-xs text-zinc-500">
                            <?= formatTanggal(
                                $dashboard['periode_aktif']['tanggal_mulai']
                            ); ?>

                            -

                            <?= formatTanggal(
                                $dashboard['periode_aktif']['tanggal_selesai']
                            ); ?>
                        </p>

                    <?php else: ?>

                        <p
                            class="mt-2 text-xl
                                   font-bold text-zinc-500">
                            Tidak Ada
                        </p>

                        <p
                            class="mt-1 text-xs
                                   text-zinc-500">
                            Belum ada periode aktif
                        </p>

                    <?php endif; ?>

                </article>


                <!-- Nilai -->
                <article
                    class="rounded-md border
                           border-zinc-300
                           bg-white p-5">

                    <p
                        class="text-xs font-medium
                               uppercase text-zinc-500">
                        Nilai Terisi
                    </p>

                    <p class="mt-2 text-2xl font-bold">

                        <?=
                        $dashboard['kelengkapan_nilai']['terisi'];
                        ?>

                        /

                        <?=
                        $dashboard['kelengkapan_nilai']['total'];
                        ?>

                        Data

                    </p>

                    <p class="mt-1 text-xs text-zinc-500">

                        <?php if (
                            $dashboard['kelengkapan_nilai']['lengkap']
                        ): ?>

                            Seluruh nilai sudah lengkap

                        <?php else: ?>

                            Nilai masih perlu dilengkapi

                        <?php endif; ?>

                    </p>

                </article>

            </section>


            <!-- Main dashboard grid -->
            <section
                class="mt-6 grid grid-cols-1
                       gap-6 xl:grid-cols-3">

                <!-- Alternatif summary -->
                <article
                    class="rounded-md border
                           border-zinc-300
                           bg-white p-5
                           xl:col-span-2">

                    <div class="mb-4">

                        <h2
                            class="font-semibold text-zinc-900">
                            Ringkasan Data Alternatif
                        </h2>

                        <p
                            class="mt-1 text-sm
                                   text-zinc-500">
                            Daftar alternatif motor dan
                            kelengkapan nilai kriteria pada
                            periode aktif.
                        </p>

                    </div>


                    <div class="overflow-x-auto">

                        <table
                            class="w-full min-w-[600px]
                                   border-collapse text-sm">

                            <thead
                                class="bg-zinc-100
                                       text-left">

                                <tr>

                                    <th
                                        class="border-b
                                               border-zinc-300
                                               px-3 py-3
                                               font-semibold">
                                        No
                                    </th>

                                    <th
                                        class="border-b
                                               border-zinc-300
                                               px-3 py-3
                                               font-semibold">
                                        Kode
                                    </th>

                                    <th
                                        class="border-b
                                               border-zinc-300
                                               px-3 py-3
                                               font-semibold">
                                        Nama Produk
                                    </th>

                                    <th
                                        class="border-b
                                               border-zinc-300
                                               px-3 py-3
                                               font-semibold">
                                        Nilai Terisi
                                    </th>

                                    <th
                                        class="border-b
                                               border-zinc-300
                                               px-3 py-3
                                               font-semibold">
                                        Status Nilai
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (
                                    empty($dashboard['alternatif'])
                                ): ?>

                                    <tr>

                                        <td
                                            colspan="5"
                                            class="px-3 py-8
                                                   text-center
                                                   text-zinc-500">
                                            Belum ada data
                                            alternatif.
                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach (
                                        $dashboard['alternatif']
                                        as $index => $alternatif
                                    ): ?>

                                        <tr
                                            class="border-b
                                                   border-zinc-200
                                                   last:border-b-0">

                                            <td
                                                class="px-3 py-3">
                                                <?= $index + 1; ?>
                                            </td>

                                            <td
                                                class="px-3 py-3
                                                       text-zinc-600">
                                                <?= e(
                                                    $alternatif['kode_alternatif']
                                                ); ?>
                                            </td>

                                            <td
                                                class="px-3 py-3
                                                       font-medium">
                                                <?= e(
                                                    $alternatif['nama_alternatif']
                                                ); ?>
                                            </td>

                                            <td
                                                class="px-3 py-3
                                                       text-zinc-600">

                                                <?=
                                                $alternatif['jumlah_nilai'];
                                                ?>

                                                /

                                                <?=
                                                $alternatif['total_kriteria'];
                                                ?>

                                            </td>

                                            <td
                                                class="px-3 py-3">

                                                <?php if (
                                                    $alternatif['lengkap']
                                                ): ?>

                                                    <span
                                                        class="
                                                            inline-flex
                                                            rounded-md
                                                            border
                                                            border-zinc-400
                                                            bg-zinc-50
                                                            px-2 py-1
                                                            text-xs
                                                            font-medium
                                                        ">
                                                        Lengkap
                                                    </span>

                                                <?php else: ?>

                                                    <span
                                                        class="
                                                            inline-flex
                                                            rounded-md
                                                            border
                                                            border-zinc-400
                                                            bg-white
                                                            px-2 py-1
                                                            text-xs
                                                            text-zinc-600
                                                        ">
                                                        Belum Lengkap
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </article>


                <!-- Ranking terbaru -->
                <article
                    class="rounded-md border
                           border-zinc-300
                           bg-white p-5">

                    <div class="mb-4">

                        <h2 class="font-semibold">
                            Hasil Perangkingan Terbaru
                        </h2>

                        <?php if (
                            $dashboard['ranking_terbaru'] !== null
                        ): ?>

                            <p
                                class="mt-1 text-xs
                                       text-zinc-500">
                                <?= e(
                                    $dashboard['ranking_terbaru']['nama_periode']
                                ); ?>
                            </p>

                        <?php else: ?>

                            <p
                                class="mt-1 text-xs
                                       text-zinc-500">
                                Belum tersedia
                            </p>

                        <?php endif; ?>

                    </div>


                    <?php if (
                        $dashboard['ranking_terbaru'] === null
                    ): ?>

                        <div
                            class="flex min-h-48
                                   items-center
                                   justify-center
                                   rounded-md
                                   border
                                   border-dashed
                                   border-zinc-300
                                   px-5
                                   text-center">

                            <div>

                                <p
                                    class="text-sm
                                           font-medium">
                                    Belum ada hasil
                                    perangkingan
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           text-zinc-500">
                                    Hasil akan tampil setelah
                                    proses TOPSIS selesai.
                                </p>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="space-y-2">

                            <?php foreach (
                                $dashboard['ranking_terbaru']['detail']
                                as $ranking
                            ): ?>

                                <div
                                    class="flex items-center
                                           justify-between
                                           rounded-md border
                                           border-zinc-200
                                           px-3 py-3">

                                    <div
                                        class="flex items-center
                                               gap-3">

                                        <div
                                            class="flex h-8 w-8
                                                   items-center
                                                   justify-center
                                                   rounded-md
                                                   bg-zinc-100
                                                   text-sm
                                                   font-bold">
                                            <?= (int)
                                            $ranking['peringkat']; ?>
                                        </div>

                                        <div>

                                            <p
                                                class="text-sm
                                                       font-semibold">
                                                <?= e(
                                                    $ranking['nama_alternatif']
                                                ); ?>
                                            </p>

                                            <p
                                                class="text-xs
                                                       text-zinc-500">
                                                <?= e(
                                                    $ranking['kode_alternatif']
                                                ); ?>
                                            </p>

                                        </div>

                                    </div>


                                    <span
                                        class="text-sm
                                               font-medium">
                                        <?= number_format(
                                            (float)
                                            $ranking['nilai_preferensi'],
                                            4
                                        ); ?>
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <a
                            href="hasil.php"
                            class="mt-4 inline-flex
                                   text-sm font-medium
                                   text-zinc-700
                                   hover:text-zinc-950">
                            Lihat hasil lengkap →
                        </a>

                    <?php endif; ?>

                </article>

            </section>

        </main>

    </div>


    <!-- Mobile sidebar -->
    <script>
        const sidebar =
            document.getElementById('sidebar');

        const sidebarButton =
            document.getElementById('sidebarButton');

        const sidebarOverlay =
            document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        }

        sidebarButton?.addEventListener(
            'click',
            openSidebar
        );

        sidebarOverlay?.addEventListener(
            'click',
            closeSidebar
        );
    </script>

</body>

</html>
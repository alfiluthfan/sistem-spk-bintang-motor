<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/KriteriaService.php';

Auth::requireRole('manajemen');

$user = Auth::user();

$kriteriaService = new KriteriaService(
    Database::connect()
);

$daftarKriteria = $kriteriaService->getAll();

$activePage = 'kriteria';


function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
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
        Data Kriteria | SPK Motor
    </title>

    <link
        rel="stylesheet"
        href="../../assets/css/app.css">

</head>


<body class="bg-zinc-100 text-zinc-900">


    <?php
    require __DIR__
        . '/../../partials/manajemen-sidebar.php';
    ?>


    <div class="min-h-screen lg:ml-60">


        <!-- TOPBAR -->
        <header
            class="sticky top-0 z-30
               flex h-16 items-center
               justify-between
               border-b border-zinc-200
               bg-white
               px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">

                <button
                    type="button"
                    id="sidebarButton"
                    class="flex h-9 w-9
                       items-center justify-center
                       rounded-md border
                       border-zinc-300
                       lg:hidden">
                    ☰
                </button>

                <div
                    class="hidden items-center
                       gap-2 text-sm sm:flex">

                    <span class="text-zinc-500">
                        Dealer Bintang Motor Cinere
                    </span>

                    <span class="text-zinc-400">
                        /
                    </span>

                    <span class="font-semibold">
                        Data Kriteria
                    </span>

                </div>

            </div>


            <div class="flex items-center gap-3">

                <div
                    class="flex h-8 w-8
                       items-center justify-center
                       rounded-full
                       border border-zinc-300
                       text-sm text-zinc-500">
                    ♟
                </div>

                <div class="hidden text-sm sm:block">

                    <span class="font-medium">
                        Manajemen
                    </span>

                    <span class="text-zinc-400">
                        —
                    </span>

                    <?= e(
                        $user['nama']
                            ?? 'Pengguna'
                    ); ?>

                </div>

            </div>

        </header>


        <!-- CONTENT -->
        <main class="p-4 sm:p-6 lg:p-8">


            <div class="mb-6">

                <h1
                    class="text-2xl font-bold
                       tracking-tight">
                    Data Kriteria
                </h1>

                <p
                    class="mt-1 text-sm
                       text-zinc-500">
                    Daftar kriteria yang digunakan
                    dalam proses pembobotan AHP dan
                    perangkingan TOPSIS.
                </p>

            </div>


            <!-- INFORMATION -->
            <div
                class="mb-5 rounded-md
                   border border-zinc-300
                   bg-white px-4 py-3
                   text-sm text-zinc-600">
                Data kriteria dikelola oleh
                <strong>Staf Penjualan / Admin IT</strong>.
                Manajemen memiliki akses untuk melihat
                data sebagai dasar perbandingan AHP.
            </div>


            <!-- TABLE -->
            <section
                class="rounded-md border
                   border-zinc-300
                   bg-white p-5">

                <div class="overflow-x-auto">

                    <table
                        class="w-full min-w-[650px]
                           border-collapse text-sm">

                        <thead class="bg-zinc-100">

                            <tr>

                                <th
                                    class="w-16
                                       border-b
                                       border-zinc-300
                                       px-3 py-3
                                       text-left">
                                    No
                                </th>

                                <th
                                    class="w-28
                                       border-b
                                       border-zinc-300
                                       px-3 py-3
                                       text-left">
                                    Kode
                                </th>

                                <th
                                    class="border-b
                                       border-zinc-300
                                       px-3 py-3
                                       text-left">
                                    Nama Kriteria
                                </th>

                                <th
                                    class="w-40
                                       border-b
                                       border-zinc-300
                                       px-3 py-3
                                       text-left">
                                    Jenis
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (
                                empty($daftarKriteria)
                            ): ?>

                                <tr>

                                    <td
                                        colspan="4"
                                        class="px-3 py-10
                                       text-center
                                       text-zinc-500">
                                        Belum ada data kriteria.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach (
                                    $daftarKriteria
                                    as $index => $kriteria
                                ): ?>

                                    <tr
                                        class="border-b
                                       border-zinc-200
                                       last:border-b-0">

                                        <td class="px-3 py-3">
                                            <?= $index + 1; ?>
                                        </td>

                                        <td
                                            class="px-3 py-3
                                           font-semibold">
                                            <?= e(
                                                $kriteria['kode_kriteria']
                                            ); ?>
                                        </td>

                                        <td
                                            class="px-3 py-3
                                           font-medium">
                                            <?= e(
                                                $kriteria['nama_kriteria']
                                            ); ?>
                                        </td>

                                        <td class="px-3 py-3">

                                            <span
                                                class="inline-flex
                                               rounded-md
                                               border
                                               border-zinc-300
                                               bg-zinc-50
                                               px-2.5 py-1
                                               text-xs">
                                                <?= e(
                                                    $kriteria['jenis_kriteria']
                                                ); ?>
                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>


        </main>

    </div>


    <script>
        const sidebar =
            document.getElementById('sidebar');

        const sidebarButton =
            document.getElementById(
                'sidebarButton'
            );

        const sidebarOverlay =
            document.getElementById(
                'sidebarOverlay'
            );


        function openSidebar() {

            sidebar?.classList.remove(
                '-translate-x-full'
            );

            sidebarOverlay?.classList.remove(
                'hidden'
            );
        }


        function closeSidebar() {

            sidebar?.classList.add(
                '-translate-x-full'
            );

            sidebarOverlay?.classList.add(
                'hidden'
            );
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
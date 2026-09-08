<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';

require_once __DIR__
    . '/../../src/DashboardManagementService.php';

Auth::requireRole('manajemen');

$user = Auth::user();

$dashboardService =
    new DashboardManagementService(
        Database::connect()
    );

$dashboard =
    $dashboardService->getDashboardData();

$activePage = 'dashboard';


function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatTanggal(
    ?string $tanggal
): string {
    if (!$tanggal) {
        return '-';
    }

    return date(
        'd/m/Y',
        strtotime($tanggal)
    );
}


function formatDecimal(
    mixed $value,
    int $decimal = 4
): string {
    if (
        $value === null
        || $value === ''
    ) {
        return '-';
    }

    return number_format(
        (float) $value,
        $decimal,
        '.',
        ''
    );
}


$periode =
    $dashboard['periode_aktif'];

$hasilAhp =
    $dashboard['hasil_ahp'];

$produkUnggulan =
    $dashboard['produk_unggulan'];

$statusProses =
    $dashboard['status_proses'];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width,
                 initial-scale=1.0">

    <title>
        Dashboard Manajemen | SPK Motor
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


        <!-- =============================================
         TOPBAR
    ============================================== -->

        <header
            class="sticky top-0 z-30
               flex h-16
               items-center
               justify-between
               border-b
               border-zinc-200
               bg-white
               px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">

                <button
                    type="button"
                    id="sidebarButton"
                    class="flex h-9 w-9
                       items-center
                       justify-center
                       rounded-md
                       border
                       border-zinc-300
                       lg:hidden">
                    ☰
                </button>


                <div
                    class="hidden items-center
                       gap-2 text-sm
                       sm:flex">

                    <span class="text-zinc-500">
                        Dealer Bintang Motor Cinere
                    </span>

                    <span class="text-zinc-400">
                        /
                    </span>

                    <span class="font-semibold">
                        Dashboard
                    </span>

                </div>


                <span
                    class="font-semibold
                       sm:hidden">
                    Dashboard
                </span>

            </div>


            <!-- User -->
            <div class="flex items-center gap-3">

                <div
                    class="flex h-8 w-8
                       items-center
                       justify-center
                       rounded-full
                       border
                       border-zinc-300
                       text-sm
                       text-zinc-500">
                    ♟
                </div>

                <div
                    class="hidden
                       text-sm
                       sm:block">

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


        <!-- =============================================
         CONTENT
    ============================================== -->

        <main class="p-4 sm:p-6 lg:p-8">


            <!-- Title -->
            <div class="mb-6">

                <h1
                    class="text-2xl
                       font-bold
                       tracking-tight">
                    Dashboard Manajemen
                </h1>

                <p
                    class="mt-1
                       text-sm
                       text-zinc-500">
                    Ringkasan proses pengambilan keputusan
                    penentuan produk unggulan motor.
                </p>

            </div>


            <!-- =============================================
             PERIODE AKTIF
        ============================================== -->

            <section
                class="mb-5
                   rounded-md
                   border
                   border-zinc-300
                   bg-white
                   px-5 py-4">

                <div
                    class="flex flex-col gap-3
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

                    <div>

                        <p
                            class="text-xs
                               font-medium
                               uppercase
                               text-zinc-500">
                            Periode Penilaian Aktif
                        </p>


                        <?php if (
                            $periode !== null
                        ): ?>

                            <p
                                class="mt-1
                                   text-lg
                                   font-bold">
                                <?= e(
                                    $periode['nama_periode']
                                ); ?>
                            </p>

                            <p
                                class="mt-1
                                   text-xs
                                   text-zinc-500">
                                <?= formatTanggal(
                                    $periode['tanggal_mulai']
                                ); ?>

                                –

                                <?= formatTanggal(
                                    $periode['tanggal_selesai']
                                ); ?>
                            </p>

                        <?php else: ?>

                            <p
                                class="mt-1
                                   font-semibold
                                   text-zinc-500">
                                Belum ada periode aktif
                            </p>

                        <?php endif; ?>

                    </div>


                    <?php if (
                        $periode !== null
                    ): ?>

                        <span
                            class="inline-flex
                               w-fit
                               rounded-md
                               bg-zinc-900
                               px-3 py-1.5
                               text-xs
                               font-semibold
                               text-white">
                            Aktif
                        </span>

                    <?php endif; ?>

                </div>

            </section>


            <!-- =============================================
             SUMMARY CARDS
        ============================================== -->

            <section
                class="grid
                   grid-cols-1
                   gap-4
                   sm:grid-cols-2
                   xl:grid-cols-4">


                <!-- Kriteria -->
                <article
                    class="rounded-md
                       border
                       border-zinc-300
                       bg-white
                       p-5">

                    <p
                        class="text-xs
                           font-medium
                           uppercase
                           text-zinc-500">
                        Jumlah Kriteria
                    </p>

                    <p
                        class="mt-2
                           text-2xl
                           font-bold">
                        <?= $dashboard['total_kriteria']; ?>

                        Kriteria
                    </p>

                </article>


                <!-- Alternatif -->
                <article
                    class="rounded-md
                       border
                       border-zinc-300
                       bg-white
                       p-5">

                    <p
                        class="text-xs
                           font-medium
                           uppercase
                           text-zinc-500">
                        Jumlah Alternatif
                    </p>

                    <p
                        class="mt-2
                           text-2xl
                           font-bold">
                        <?= $dashboard['total_alternatif']; ?>

                        Motor
                    </p>

                </article>


                <!-- CR -->
                <article
                    class="rounded-md
                       border
                       border-zinc-300
                       bg-white
                       p-5">

                    <p
                        class="text-xs
                           font-medium
                           uppercase
                           text-zinc-500">
                        Konsistensi AHP
                    </p>


                    <?php if (
                        $hasilAhp !== null
                    ): ?>

                        <p
                            class="mt-2
                               text-2xl
                               font-bold">
                            <?= formatDecimal(
                                $hasilAhp['consistency_ratio'],
                                4
                            ); ?>
                        </p>

                        <p
                            class="mt-1 text-xs
                               <?= strtolower(
                                    trim(
                                        (string)
                                        $hasilAhp['status_konsistensi']
                                    )
                                ) === 'konsisten'
                                    ? 'text-zinc-700'
                                    : 'font-medium text-zinc-900';
                                ?>">
                            <?= e(
                                $hasilAhp['status_konsistensi']
                                    ?? 'Belum Diproses'
                            ); ?>
                        </p>

                    <?php else: ?>

                        <p
                            class="mt-2
                               text-xl
                               font-bold
                               text-zinc-500">
                            Belum Ada
                        </p>

                        <p
                            class="mt-1
                               text-xs
                               text-zinc-500">
                            AHP belum diproses
                        </p>

                    <?php endif; ?>

                </article>


                <!-- Produk terbaik -->
                <article
                    class="rounded-md
                       border
                       border-zinc-300
                       bg-white
                       p-5">

                    <p
                        class="text-xs
                           font-medium
                           uppercase
                           text-zinc-500">
                        Produk Unggulan
                    </p>


                    <?php if (
                        $produkUnggulan !== null
                    ): ?>

                        <p
                            class="mt-2
                               text-2xl
                               font-bold">
                            <?= e(
                                $produkUnggulan['nama_alternatif']
                            ); ?>
                        </p>

                        <p
                            class="mt-1
                               text-xs
                               text-zinc-500">
                            Preferensi:
                            <?= formatDecimal(
                                $produkUnggulan['nilai_preferensi'],
                                4
                            ); ?>
                        </p>

                    <?php else: ?>

                        <p
                            class="mt-2
                               text-xl
                               font-bold
                               text-zinc-500">
                            Belum Ada
                        </p>

                        <p
                            class="mt-1
                               text-xs
                               text-zinc-500">
                            TOPSIS belum diproses
                        </p>

                    <?php endif; ?>

                </article>

            </section>


            <!-- =============================================
             SECOND ROW
        ============================================== -->

            <section
                class="mt-6
                   grid grid-cols-1
                   gap-6
                   xl:grid-cols-2">


                <!-- =========================================
                 BOBOT KRITERIA
            ========================================== -->

                <article
                    class="rounded-md
                       border
                       border-zinc-300
                       bg-white
                       p-5">

                    <div>

                        <h2
                            class="font-semibold">
                            Bobot Kriteria (AHP)
                        </h2>

                        <p
                            class="mt-1
                               text-sm
                               text-zinc-500">
                            Nilai prioritas kriteria
                            berdasarkan hasil perhitungan
                            AHP terbaru.
                        </p>

                    </div>


                    <?php if (
                        empty($dashboard['bobot_kriteria'])
                    ): ?>

                        <div
                            class="mt-5
                               flex min-h-52
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
                                    Bobot belum tersedia
                                </p>

                                <p
                                    class="mt-1
                                       text-xs
                                       text-zinc-500">
                                    Lakukan perbandingan
                                    berpasangan AHP terlebih
                                    dahulu.
                                </p>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="mt-6 space-y-4">

                            <?php foreach (
                                $dashboard['bobot_kriteria'] as $bobot
                            ): ?>

                                <?php
                                $bobotValue =
                                    (float)
                                    $bobot['bobot'];

                                $percentage =
                                    min(
                                        100,
                                        max(
                                            0,
                                            $bobotValue * 100
                                        )
                                    );
                                ?>

                                <div
                                    class="grid
                                       grid-cols-[160px_1fr_65px]
                                       items-center
                                       gap-3">

                                    <div>

                                        <p
                                            class="text-sm
                                               text-zinc-600">
                                            <?= e(
                                                $bobot['nama_kriteria']
                                            ); ?>
                                        </p>

                                        <p
                                            class="text-[11px]
                                               text-zinc-400">
                                            <?= e(
                                                $bobot['kode_kriteria']
                                            ); ?>

                                            •

                                            <?= e(
                                                $bobot['jenis_kriteria']
                                            ); ?>
                                        </p>

                                    </div>


                                    <div
                                        class="h-3
                                           overflow-hidden
                                           rounded-sm
                                           bg-zinc-100">

                                        <div
                                            class="h-full
                                               rounded-sm
                                               bg-zinc-900"
                                            style="
                                            width:
                                            <?= round(
                                                $percentage,
                                                2
                                            ); ?>%;
                                        "></div>

                                    </div>


                                    <span
                                        class="text-right
                                           text-sm
                                           font-semibold">
                                        <?= formatDecimal(
                                            $bobotValue,
                                            4
                                        ); ?>
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </article>


                <!-- =========================================
                 STATUS PROSES
            ========================================== -->

                <article
                    class="rounded-md
                       border
                       border-zinc-300
                       bg-white
                       p-5">

                    <div>

                        <h2 class="font-semibold">
                            Status Proses Sistem
                        </h2>

                        <p
                            class="mt-1
                               text-sm
                               text-zinc-500">
                            Tahapan proses Sistem Pendukung
                            Keputusan pada periode aktif.
                        </p>

                    </div>


                    <div class="mt-5 space-y-1">


                        <?php
                        $processItems = [
                            [
                                'label' =>
                                'Data Kriteria',

                                'status' =>
                                $statusProses['kriteria'],
                            ],
                            [
                                'label' =>
                                'Data Alternatif',

                                'status' =>
                                $statusProses['alternatif'],
                            ],
                            [
                                'label' =>
                                'Input Nilai Alternatif',

                                'status' =>
                                $statusProses['nilai_alternatif'],
                            ],
                            [
                                'label' =>
                                'Perbandingan AHP',

                                'status' =>
                                $statusProses['perbandingan_ahp'],
                            ],
                            [
                                'label' =>
                                'Perhitungan Bobot AHP',

                                'status' =>
                                $statusProses['bobot_ahp'],
                            ],
                            [
                                'label' =>
                                'Konsistensi AHP',

                                'status' =>
                                $statusProses['konsistensi_ahp'],
                            ],
                            [
                                'label' =>
                                'Perhitungan TOPSIS',

                                'status' =>
                                $statusProses['topsis'],
                            ],
                            [
                                'label' =>
                                'Perangkingan',

                                'status' =>
                                $statusProses['perangkingan'],
                            ],
                        ];
                        ?>


                        <?php foreach (
                            $processItems as $index => $item
                        ): ?>

                            <div
                                class="
                                flex items-center
                                justify-between
                                px-3 py-3

                                <?= $index % 2 === 0
                                    ? 'bg-zinc-100'
                                    : 'bg-white';
                                ?>
                            ">

                                <div
                                    class="flex
                                       items-center
                                       gap-3">

                                    <span
                                        class="
                                        flex h-5 w-5
                                        items-center
                                        justify-center
                                        border
                                        text-[11px]
                                        font-bold

                                        <?= $item['status']
                                            ? 'border-zinc-900 bg-zinc-900 text-white'
                                            : 'border-zinc-300 bg-white text-zinc-400';
                                        ?>
                                    ">
                                        <?= $item['status']
                                            ? '✓'
                                            : '';
                                        ?>
                                    </span>

                                    <span
                                        class="
                                        text-sm

                                        <?= $item['status']
                                            ? 'font-medium text-zinc-900'
                                            : 'text-zinc-500';
                                        ?>
                                    ">
                                        <?= e(
                                            $item['label']
                                        ); ?>
                                    </span>

                                </div>


                                <span
                                    class="
                                    text-xs

                                    <?= $item['status']
                                        ? 'text-zinc-700'
                                        : 'text-zinc-400';
                                    ?>
                                ">
                                    <?= $item['status']
                                        ? 'Selesai'
                                        : 'Belum';
                                    ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </article>

            </section>


            <!-- =============================================
             DETAIL AHP / TOPSIS
        ============================================== -->

            <section
                class="mt-6
                   grid grid-cols-1
                   gap-6
                   lg:grid-cols-2">

                <!-- AHP -->
                <article
                    class="rounded-md
                       border
                       border-zinc-300
                       bg-white
                       p-5">

                    <h2 class="font-semibold">
                        Ringkasan AHP
                    </h2>


                    <?php if (
                        $hasilAhp === null
                    ): ?>

                        <p
                            class="mt-4
                               text-sm
                               text-zinc-500">
                            Belum terdapat hasil AHP pada
                            periode aktif.
                        </p>

                    <?php else: ?>

                        <dl
                            class="mt-4
                               divide-y
                               divide-zinc-200
                               text-sm">

                            <div
                                class="flex
                                   justify-between
                                   py-3">
                                <dt class="text-zinc-500">
                                    Lambda Max
                                </dt>

                                <dd class="font-medium">
                                    <?= formatDecimal(
                                        $hasilAhp['lambda_max'],
                                        6
                                    ); ?>
                                </dd>
                            </div>

                            <div
                                class="flex
                                   justify-between
                                   py-3">
                                <dt class="text-zinc-500">
                                    Consistency Index
                                </dt>

                                <dd class="font-medium">
                                    <?= formatDecimal(
                                        $hasilAhp['consistency_index'],
                                        6
                                    ); ?>
                                </dd>
                            </div>

                            <div
                                class="flex
                                   justify-between
                                   py-3">
                                <dt class="text-zinc-500">
                                    Consistency Ratio
                                </dt>

                                <dd class="font-medium">
                                    <?= formatDecimal(
                                        $hasilAhp['consistency_ratio'],
                                        6
                                    ); ?>
                                </dd>
                            </div>

                            <div
                                class="flex
                                   justify-between
                                   py-3">
                                <dt class="text-zinc-500">
                                    Status
                                </dt>

                                <dd class="font-semibold">
                                    <?= e(
                                        $hasilAhp['status_konsistensi']
                                    ); ?>
                                </dd>
                            </div>

                        </dl>

                    <?php endif; ?>

                </article>


                <!-- TOPSIS -->
                <article
                    class="rounded-md
                       border
                       border-zinc-300
                       bg-white
                       p-5">

                    <h2 class="font-semibold">
                        Hasil Keputusan Terbaru
                    </h2>


                    <?php if (
                        $produkUnggulan === null
                    ): ?>

                        <p
                            class="mt-4
                               text-sm
                               text-zinc-500">
                            Belum terdapat hasil perangkingan
                            TOPSIS untuk periode aktif.
                        </p>

                    <?php else: ?>

                        <div class="mt-5">

                            <p
                                class="text-xs
                                   font-medium
                                   uppercase
                                   text-zinc-500">
                                Peringkat 1
                            </p>

                            <p
                                class="mt-1
                                   text-2xl
                                   font-bold">
                                <?= e(
                                    $produkUnggulan['nama_alternatif']
                                ); ?>
                            </p>

                            <p
                                class="mt-1
                                   text-sm
                                   text-zinc-500">
                                <?= e(
                                    $produkUnggulan['kode_alternatif']
                                ); ?>
                            </p>


                            <div
                                class="mt-5
                                   border-t
                                   border-zinc-200
                                   pt-4">

                                <div
                                    class="flex
                                       justify-between
                                       text-sm">

                                    <span
                                        class="text-zinc-500">
                                        Nilai Preferensi
                                    </span>

                                    <span
                                        class="font-bold">
                                        <?= formatDecimal(
                                            $produkUnggulan['nilai_preferensi'],
                                            6
                                        ); ?>
                                    </span>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

                </article>

            </section>

        </main>

    </div>


    <!-- =============================================
     MOBILE SIDEBAR
============================================== -->

    <script>
        const sidebar =
            document.getElementById(
                'sidebar'
            );

        const sidebarButton =
            document.getElementById(
                'sidebarButton'
            );

        const sidebarOverlay =
            document.getElementById(
                'sidebarOverlay'
            );


        function openSidebar() {

            sidebar.classList.remove(
                '-translate-x-full'
            );

            sidebarOverlay.classList.remove(
                'hidden'
            );
        }


        function closeSidebar() {

            sidebar.classList.add(
                '-translate-x-full'
            );

            sidebarOverlay.classList.add(
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
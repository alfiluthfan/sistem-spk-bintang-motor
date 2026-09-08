<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/RankingResultService.php';

Auth::requireRole('staf');

$user = Auth::user();

$rankingService = new RankingResultService(
    Database::connect()
);

$data = $rankingService->getPageData();

$periode = $data['periode'];
$hasilTopsis = $data['hasil_topsis'];
$ranking = $data['ranking'];
$winner = $data['winner'];
$totalKriteria = $data['total_kriteria'];
$totalAlternatif = $data['total_alternatif'];

$basePath = '../../';

$activePage = 'hasil';

$breadcrumbCurrent = 'Hasil Perangkingan';


function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatPreference(
    mixed $value
): string {
    if (
        $value === null
        || $value === ''
    ) {
        return '-';
    }

    return number_format(
        (float) $value,
        6,
        '.',
        ''
    );
}


function priorityLabel(
    int $rank
): string {
    return match ($rank) {
        1 => 'Prioritas Tertinggi',
        2 => 'Prioritas Kedua',
        3 => 'Prioritas Ketiga',
        4 => 'Prioritas Keempat',
        default => 'Prioritas ke-' . $rank,
    };
}


function formatDateTime(
    ?string $value
): string {
    if (!$value) {
        return '-';
    }

    return date(
        'd/m/Y H:i',
        strtotime($value)
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
        Hasil Perangkingan | SPK Motor
    </title>

    <link
        rel="stylesheet"
        href="<?= $basePath; ?>assets/css/app.css">

</head>


<body class="bg-zinc-100 text-zinc-900">


    <?php
    require __DIR__
        . '/../../partials/staf-sidebar.php';
    ?>


    <div class="min-h-screen lg:ml-60">


        <!-- ==========================================
         TOPBAR
    =========================================== -->

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
                        Hasil Perangkingan
                    </span>

                </div>

            </div>


            <!-- User -->
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
                        Staf
                    </span>

                    <span class="text-zinc-400">
                        —
                    </span>

                    <?= htmlspecialchars(
                        (string) (
                            $user['nama']
                            ?? 'Pengguna'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </div>

            </div>

        </header>


        <!-- ==========================================
         CONTENT
    =========================================== -->

        <main class="p-4 sm:p-6 lg:p-8">

            <div class="mb-6">

                <h1
                    class="text-2xl font-bold
                       tracking-tight">
                    Hasil Perangkingan
                </h1>

                <p
                    class="mt-1 text-sm
                       text-zinc-500">
                    Hasil perangkingan produk motor
                    berdasarkan perhitungan AHP-TOPSIS.
                </p>

            </div>


            <?php if ($periode === null): ?>

                <!-- ==========================================
         BELUM ADA PERIODE
    =========================================== -->

                <section
                    class="rounded-md
               border border-zinc-300
               bg-white p-10
               text-center">

                    <h2 class="font-semibold">
                        Belum Ada Periode Penilaian Aktif
                    </h2>

                    <p
                        class="mt-2 text-sm
                   text-zinc-500">
                        Hasil perangkingan belum dapat
                        ditampilkan karena belum terdapat
                        periode penilaian aktif.
                    </p>

                </section>


            <?php elseif ($hasilTopsis === null): ?>

                <!-- ==========================================
         TOPSIS BELUM DIPROSES
    =========================================== -->

                <section
                    class="rounded-md
               border border-zinc-300
               bg-white p-10
               text-center">

                    <h2 class="font-semibold">
                        Hasil Perangkingan Belum Tersedia
                    </h2>

                    <p
                        class="mt-2 text-sm
                   text-zinc-500">
                        Perhitungan TOPSIS untuk periode

                        <strong class="text-zinc-800">
                            <?= e(
                                $periode['nama_periode']
                            ); ?>
                        </strong>

                        belum dilakukan oleh Manajemen.
                    </p>

                    <p
                        class="mt-2 text-xs
                   text-zinc-400">
                        Hasil akan tampil setelah proses
                        AHP dan TOPSIS selesai dilakukan.
                    </p>

                </section>


            <?php elseif (empty($ranking)): ?>

                <!-- ==========================================
         HEADER TOPSIS ADA, DETAIL TIDAK ADA
    =========================================== -->

                <section
                    class="rounded-md
               border border-zinc-300
               bg-white p-10
               text-center">

                    <h2 class="font-semibold">
                        Data Perangkingan Belum Tersedia
                    </h2>

                    <p
                        class="mt-2 text-sm
                   text-zinc-500">
                        Proses TOPSIS telah tercatat,
                        tetapi detail hasil perangkingannya
                        belum tersedia.
                    </p>

                </section>


            <?php else: ?>


                <!-- ==========================================
         INFO PERIODE
    =========================================== -->

                <section
                    class="mb-6
               rounded-md
               border border-zinc-300
               bg-white p-5">

                    <div
                        class="grid grid-cols-1
                   gap-5
                   sm:grid-cols-2
                   lg:grid-cols-4">

                        <div>

                            <p
                                class="text-xs
                           font-medium
                           uppercase
                           text-zinc-500">
                                Periode Penilaian
                            </p>

                            <p
                                class="mt-1
                           font-semibold">
                                <?= e(
                                    $periode['nama_periode']
                                ); ?>
                            </p>

                        </div>


                        <div>

                            <p
                                class="text-xs
                           font-medium
                           uppercase
                           text-zinc-500">
                                Jumlah Kriteria
                            </p>

                            <p
                                class="mt-1
                           font-semibold">
                                <?= (int) $totalKriteria; ?>
                                Kriteria
                            </p>

                        </div>


                        <div>

                            <p
                                class="text-xs
                           font-medium
                           uppercase
                           text-zinc-500">
                                Jumlah Alternatif
                            </p>

                            <p
                                class="mt-1
                           font-semibold">
                                <?= (int) $totalAlternatif; ?>
                                Produk
                            </p>

                        </div>


                        <div>

                            <p
                                class="text-xs
                           font-medium
                           uppercase
                           text-zinc-500">
                                Waktu Proses
                            </p>

                            <p
                                class="mt-1
                           font-semibold">
                                <?= formatDateTime(
                                    $hasilTopsis['tanggal_proses']
                                ); ?>
                            </p>

                        </div>

                    </div>

                </section>


                <!-- ==========================================
         PRODUK UNGGULAN
    =========================================== -->

                <?php if ($winner !== null): ?>

                    <section
                        class="mb-6
                   rounded-md
                   border border-zinc-300
                   bg-white p-6">

                        <div
                            class="flex flex-col
                       gap-5
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

                            <div
                                class="flex items-center
                           gap-5">

                                <div
                                    class="flex h-16 w-16
                               shrink-0
                               items-center
                               justify-center
                               rounded-full
                               border-2
                               border-zinc-400
                               bg-zinc-50
                               text-3xl">
                                    ★
                                </div>


                                <div>

                                    <p
                                        class="text-xs
                                   font-semibold
                                   uppercase
                                   text-zinc-500">
                                        Produk Unggulan /
                                        Rekomendasi Terbaik
                                    </p>

                                    <h2
                                        class="mt-1
                                   text-3xl
                                   font-bold">
                                        <?= e(
                                            $winner['nama_alternatif']
                                        ); ?>
                                    </h2>

                                    <p
                                        class="mt-1
                                   text-sm
                                   text-zinc-600">

                                        <?= e(
                                            $winner['kode_alternatif']
                                        ); ?>

                                        · Nilai Preferensi:

                                        <strong
                                            class="text-zinc-900">
                                            <?= formatPreference(
                                                $winner['nilai_preferensi']
                                            ); ?>
                                        </strong>

                                    </p>

                                </div>

                            </div>


                            <span
                                class="inline-flex
                           w-fit
                           rounded-md
                           bg-zinc-900
                           px-4 py-2
                           text-xs
                           font-bold
                           text-white">
                                TERBAIK
                            </span>

                        </div>

                    </section>

                <?php endif; ?>


                <!-- ==========================================
         TABLE RANKING
    =========================================== -->

                <section
                    class="rounded-md
               border border-zinc-300
               bg-white p-5">

                    <div class="mb-4">

                        <h2 class="font-semibold">
                            Tabel Perangkingan Alternatif
                        </h2>

                        <p
                            class="mt-1 text-sm
                       text-zinc-500">
                            Hasil akhir produk berdasarkan
                            nilai preferensi perhitungan TOPSIS.
                        </p>

                    </div>


                    <div class="overflow-x-auto">

                        <table
                            class="w-full
                       min-w-[800px]
                       border-collapse
                       text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th
                                        class="w-28
                                   border-b
                                   border-zinc-300
                                   px-3 py-3
                                   text-left">
                                        Peringkat
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
                                        Nama Produk
                                    </th>

                                    <th
                                        class="w-52
                                   border-b
                                   border-zinc-300
                                   px-3 py-3
                                   text-right">
                                        Nilai Preferensi
                                    </th>

                                    <th
                                        class="w-56
                                   border-b
                                   border-zinc-300
                                   px-3 py-3
                                   text-left">
                                        Keterangan
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $ranking as $result
                                ): ?>

                                    <?php
                                    $rank =
                                        (int) $result['peringkat'];
                                    ?>

                                    <tr
                                        class="
                            border-b
                            border-zinc-200
                            last:border-b-0

                            <?= $rank === 1
                                        ? 'bg-zinc-50'
                                        : '';
                            ?>
                        ">

                                        <!-- PERINGKAT -->
                                        <td class="px-3 py-3">

                                            <span
                                                class="
                                    inline-flex
                                    min-w-10
                                    items-center
                                    justify-center
                                    rounded-md
                                    px-2 py-1.5
                                    font-bold

                                    <?= $rank === 1
                                        ? 'bg-zinc-900 text-white'
                                        : 'bg-zinc-100 text-zinc-900';
                                    ?>
                                ">
                                                #<?= $rank; ?>
                                            </span>

                                        </td>


                                        <!-- KODE -->
                                        <td
                                            class="px-3 py-3
                                   text-zinc-600">
                                            <?= e(
                                                $result['kode_alternatif']
                                            ); ?>
                                        </td>


                                        <!-- NAMA -->
                                        <td
                                            class="px-3 py-3
                                   font-semibold">
                                            <?= e(
                                                $result['nama_alternatif']
                                            ); ?>
                                        </td>


                                        <!-- PREFERENSI -->
                                        <td
                                            class="px-3 py-3
                                   text-right
                                   font-bold">
                                            <?= formatPreference(
                                                $result['nilai_preferensi']
                                            ); ?>
                                        </td>


                                        <!-- PRIORITAS -->
                                        <td class="px-3 py-3">

                                            <span
                                                class="
                                    inline-flex
                                    rounded-md
                                    border
                                    px-2.5 py-1
                                    text-xs
                                    font-medium

                                    <?= $rank === 1
                                        ? 'border-zinc-900 bg-zinc-900 text-white'
                                        : 'border-zinc-300 bg-white text-zinc-700';
                                    ?>
                                ">

                                                <?= e(
                                                    priorityLabel(
                                                        $rank
                                                    )
                                                ); ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- ==========================================
         RINGKASAN
    =========================================== -->

                <?php if ($winner !== null): ?>

                    <section
                        class="mt-6
                   rounded-md
                   border border-zinc-300
                   bg-white p-5">

                        <h2 class="font-semibold">
                            Ringkasan Hasil
                        </h2>

                        <p
                            class="mt-2
                       text-sm
                       leading-6
                       text-zinc-600">
                            Berdasarkan hasil perhitungan
                            metode AHP-TOPSIS pada periode

                            <strong class="text-zinc-900">
                                <?= e(
                                    $periode['nama_periode']
                                ); ?>
                            </strong>,

                            alternatif

                            <strong class="text-zinc-900">
                                <?= e(
                                    $winner['kode_alternatif']
                                ); ?>

                                —

                                <?= e(
                                    $winner['nama_alternatif']
                                ); ?>
                            </strong>

                            memperoleh nilai preferensi
                            tertinggi sebesar

                            <strong class="text-zinc-900">
                                <?= formatPreference(
                                    $winner['nilai_preferensi']
                                ); ?>
                            </strong>

                            dan menempati peringkat pertama.
                            Produk tersebut merupakan rekomendasi
                            produk unggulan pada periode
                            penilaian tersebut.
                        </p>

                    </section>

                <?php endif; ?>


            <?php endif; ?>

        </main>

    </div>


    <!-- ==========================================
     MOBILE SIDEBAR
=========================================== -->

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
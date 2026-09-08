<?php

declare(strict_types=1);

require_once __DIR__
    . '/../../src/Auth.php';

require_once __DIR__
    . '/../../src/RankingResultService.php';


Auth::requireRole('manajemen');

$user = Auth::user();

$resultService =
    new RankingResultService(
        Database::connect()
    );

$data =
    $resultService->getPageData();

$activePage = 'hasil';

$periode =
    $data['periode'];

$hasilTopsis =
    $data['hasil_topsis'];

$ranking =
    $data['ranking'];

$winner =
    $data['winner'];


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function decimal(
    mixed $value,
    int $precision = 6
): string {
    if (
        $value === null
        || $value === ''
    ) {
        return '-';
    }

    return number_format(
        (float) $value,
        $precision,
        '.',
        ''
    );
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


function priorityLabel(
    int $rank
): string {
    return match ($rank) {
        1 => 'Prioritas Tertinggi',
        2 => 'Prioritas Kedua',
        3 => 'Prioritas Ketiga',
        4 => 'Prioritas Keempat',

        default =>
        'Prioritas ke-' . $rank,
    };
}

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
        Hasil Perangkingan | SPK Motor
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


        <!-- =====================================================
         TOPBAR
    ====================================================== -->

        <header
            class="sticky top-0 z-30
               flex h-16 items-center
               justify-between
               border-b border-zinc-200
               bg-white
               px-4 sm:px-6 lg:px-8
               print:hidden">

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


            <div class="flex items-center gap-3">

                <div
                    class="flex h-8 w-8
                       items-center justify-center
                       rounded-full border
                       border-zinc-300
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


        <!-- =====================================================
         CONTENT
    ====================================================== -->

        <main class="p-4 sm:p-6 lg:p-8">


            <!-- TITLE -->
            <div class="mb-6">

                <h1
                    class="text-2xl font-bold
                       tracking-tight">
                    Hasil Perangkingan
                    Produk Motor Terbaik
                </h1>

                <p
                    class="mt-1 text-sm
                       text-zinc-500">
                    Metode AHP-TOPSIS
                    — Dealer Bintang Motor Cinere
                </p>

            </div>


            <!-- =================================================
             BELUM ADA PERIODE
        ================================================== -->

            <?php if ($periode === null): ?>

                <section
                    class="rounded-md border
                       border-zinc-300
                       bg-white p-10
                       text-center">

                    <h2 class="font-semibold">
                        Belum ada periode aktif
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


                <!-- =================================================
                 TOPSIS BELUM ADA
            ================================================== -->

                <section
                    class="rounded-md border
                       border-zinc-300
                       bg-white p-10
                       text-center">

                    <h2 class="font-semibold">
                        Hasil perangkingan belum tersedia
                    </h2>

                    <p
                        class="mt-2 text-sm
                           text-zinc-500">
                        Perhitungan TOPSIS untuk periode

                        <strong>
                            <?= e(
                                $periode['nama_periode']
                            ); ?>
                        </strong>

                        belum dilakukan.
                    </p>

                    <a
                        href="topsis.php"
                        class="mt-5 inline-flex
                           rounded-md
                           bg-zinc-900
                           px-4 py-2.5
                           text-sm font-semibold
                           text-white
                           hover:bg-zinc-800">
                        Jalankan TOPSIS
                    </a>

                </section>


            <?php else: ?>


                <!-- =================================================
                 INFO PERIODE
            ================================================== -->

                <section
                    class="mb-6
                       rounded-md border
                       border-zinc-300
                       bg-white px-5 py-4">

                    <div
                        class="grid grid-cols-1
                           gap-5 sm:grid-cols-2
                           lg:grid-cols-4">

                        <div>

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Periode Penilaian
                            </p>

                            <p class="mt-1 font-semibold">
                                <?= e(
                                    $periode['nama_periode']
                                ); ?>
                            </p>

                        </div>


                        <div>

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Jumlah Kriteria
                            </p>

                            <p class="mt-1 font-semibold">
                                <?= (int)
                                $data['total_kriteria']; ?>

                                Kriteria
                            </p>

                        </div>


                        <div>

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Jumlah Alternatif
                            </p>

                            <p class="mt-1 font-semibold">
                                <?= (int)
                                $data['total_alternatif']; ?>

                                Produk
                            </p>

                        </div>


                        <div>

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Waktu Proses
                            </p>

                            <p class="mt-1 font-semibold">
                                <?= formatDateTime(
                                    $hasilTopsis['tanggal_proses']
                                ); ?>
                            </p>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                 WINNER
            ================================================== -->

                <?php if ($winner !== null): ?>

                    <section
                        class="mb-6
                           rounded-md border
                           border-zinc-300
                           bg-white p-6">

                        <div
                            class="flex flex-col gap-5
                               sm:flex-row
                               sm:items-center
                               sm:justify-between">

                            <div
                                class="flex
                                   items-center gap-5">

                                <!-- Star -->
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
                                        (Ranking #1)
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
                                            <?= decimal(
                                                $winner['nilai_preferensi'],
                                                6
                                            ); ?>
                                        </strong>
                                    </p>

                                </div>

                            </div>


                            <span
                                class="inline-flex
                                   w-fit rounded-md
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


                <!-- =================================================
                 RANKING TABLE
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <div class="mb-4">

                        <h2 class="font-semibold">
                            Tabel Perangkingan Alternatif
                        </h2>

                        <p
                            class="mt-1 text-sm
                               text-zinc-500">
                            Hasil akhir perangkingan produk
                            berdasarkan nilai preferensi
                            metode AHP-TOPSIS.
                        </p>

                    </div>


                    <div class="overflow-x-auto">

                        <table
                            class="w-full min-w-[750px]
                               border-collapse text-sm">

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
                                        Keterangan Prioritas
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $ranking as $result
                                ): ?>

                                    <?php
                                    $rank =
                                        (int)
                                        $result['peringkat'];
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

                                        <!-- Ranking -->
                                        <td
                                            class="px-3 py-3">

                                            <span
                                                class="
                                            inline-flex
                                            min-w-9
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


                                        <!-- Code -->
                                        <td
                                            class="px-3 py-3
                                           text-zinc-600">
                                            <?= e(
                                                $result['kode_alternatif']
                                            ); ?>
                                        </td>


                                        <!-- Name -->
                                        <td
                                            class="px-3 py-3
                                           font-semibold">
                                            <?= e(
                                                $result['nama_alternatif']
                                            ); ?>
                                        </td>


                                        <!-- Preference -->
                                        <td
                                            class="px-3 py-3
                                           text-right
                                           font-bold">
                                            <?= decimal(
                                                $result['nilai_preferensi'],
                                                6
                                            ); ?>
                                        </td>


                                        <!-- Priority -->
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


                <!-- =================================================
                 ANALISIS SINGKAT
            ================================================== -->

                <?php if ($winner !== null): ?>

                    <section
                        class="mb-6 rounded-md
                           border border-zinc-300
                           bg-white p-5">

                        <h2 class="font-semibold">
                            Analisis Singkat
                        </h2>

                        <p
                            class="mt-2
                               text-sm leading-6
                               text-zinc-600">
                            Berdasarkan hasil perhitungan
                            metode AHP-TOPSIS pada periode

                            <strong
                                class="text-zinc-900">
                                <?= e(
                                    $periode['nama_periode']
                                ); ?>
                            </strong>,

                            alternatif

                            <strong
                                class="text-zinc-900">
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

                            <strong
                                class="text-zinc-900">
                                <?= decimal(
                                    $winner['nilai_preferensi'],
                                    6
                                ); ?>
                            </strong>.

                            Dengan demikian, produk tersebut
                            menempati peringkat pertama dan
                            direkomendasikan sebagai produk
                            unggulan pada periode penilaian
                            tersebut.
                        </p>

                    </section>

                <?php endif; ?>


                <!-- =================================================
                 ACTION
            ================================================== -->

                <div
                    class="flex flex-col gap-3
                       sm:flex-row
                       print:hidden">

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex
                           justify-center
                           rounded-md
                           bg-zinc-900
                           px-5 py-2.5
                           text-sm font-semibold
                           text-white
                           transition
                           hover:bg-zinc-800">
                        Cetak Hasil
                    </button>


                    <a
                        href="topsis.php"
                        class="inline-flex
                           justify-center
                           rounded-md
                           border border-zinc-400
                           bg-white
                           px-5 py-2.5
                           text-sm font-semibold
                           transition
                           hover:bg-zinc-100">
                        Lihat Proses TOPSIS
                    </a>


                    <a
                        href="laporan.php"
                        class="inline-flex
                           justify-center
                           rounded-md
                           border border-zinc-400
                           bg-white
                           px-5 py-2.5
                           text-sm font-semibold
                           transition
                           hover:bg-zinc-100">
                        Laporan Lengkap →
                    </a>

                </div>

            <?php endif; ?>

        </main>

    </div>


    <!-- =========================================================
     MOBILE SIDEBAR
========================================================== -->

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
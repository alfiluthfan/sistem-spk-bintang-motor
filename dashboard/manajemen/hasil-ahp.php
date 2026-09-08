<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';

require_once __DIR__
    . '/../../src/AhpResultService.php';

Auth::requireRole('manajemen');

$user = Auth::user();

$resultService =
    new AhpResultService(
        Database::connect()
    );

$data =
    $resultService->getResultData();

$activePage = 'hasil-ahp';

$periode =
    $data['periode'];

$criteria =
    $data['criteria'];

$hasil =
    $data['hasil'];

$weights =
    $data['weights'];

$pairwiseMatrix =
    $data['pairwise_matrix'];

$normalizedMatrix =
    $data['normalized_matrix'];

$columnSums =
    $data['column_sums'];

$normalizedColumnSums =
    $data['normalized_column_sums'];

$isCalculated =
    $data['calculated'];

$isConsistent =
    $isCalculated
    && strtolower(
        trim(
            (string) (
                $hasil['status_konsistensi'] ?? ''
            )
        )
    ) === 'konsisten';


/*
|--------------------------------------------------------------------------
| Helper
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
    int $precision = 4
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


function percentage(
    mixed $value
): string {
    return number_format(
        (float) $value * 100,
        2,
        '.',
        ''
    ) . '%';
}


function matrixValue(
    float $value
): string {
    $known = [
        1 / 9 => '1/9',
        1 / 8 => '1/8',
        1 / 7 => '1/7',
        1 / 6 => '1/6',
        1 / 5 => '1/5',
        1 / 4 => '1/4',
        1 / 3 => '1/3',
        1 / 2 => '1/2',
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
        9 => '9',
    ];

    foreach ($known as $number => $label) {
        if (
            abs($value - (float) $number)
            < 0.0001
        ) {
            return $label;
        }
    }

    return number_format(
        $value,
        4,
        '.',
        ''
    );
}


function formatTanggalWaktu(
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


/*
|--------------------------------------------------------------------------
| Sort bobot untuk visualisasi
|--------------------------------------------------------------------------
*/

$sortedWeights = $weights;

usort(
    $sortedWeights,
    static fn(array $a, array $b): int =>
    (float) $b['bobot']
        <=>
        (float) $a['bobot']
);

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
        Hasil AHP | SPK Motor
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
               bg-white px-4
               sm:px-6 lg:px-8">

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
                        Hasil Perhitungan AHP
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
                    class="text-2xl
                       font-bold tracking-tight">
                    Hasil Perhitungan Bobot Kriteria
                    — Metode AHP
                </h1>

                <p
                    class="mt-1
                       text-sm text-zinc-500">
                    Hasil pembobotan dan pengujian
                    konsistensi berdasarkan matriks
                    perbandingan berpasangan.
                </p>

            </div>


            <!-- =================================================
             EMPTY PERIODE
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
                        Hasil AHP belum dapat ditampilkan
                        karena belum terdapat periode
                        penilaian aktif.
                    </p>

                </section>


            <?php elseif ($hasil === null): ?>


                <!-- =================================================
                 BELUM ADA AHP
            ================================================== -->

                <section
                    class="rounded-md border
                       border-zinc-300
                       bg-white p-10
                       text-center">

                    <h2 class="font-semibold">
                        Hasil AHP belum tersedia
                    </h2>

                    <p
                        class="mt-2 text-sm
                           text-zinc-500">
                        Lakukan perbandingan berpasangan
                        terlebih dahulu untuk periode
                        <?= e(
                            $periode['nama_periode']
                        ); ?>.
                    </p>

                    <a
                        href="ahp.php"
                        class="mt-5 inline-flex
                           rounded-md bg-zinc-900
                           px-4 py-2.5
                           text-sm font-semibold
                           text-white
                           hover:bg-zinc-800">
                        Ke Perbandingan AHP
                    </a>

                </section>


            <?php elseif (!$isCalculated): ?>


                <!-- =================================================
                 BELUM DIHITUNG
            ================================================== -->

                <section
                    class="rounded-md border
                       border-zinc-300
                       bg-white p-10
                       text-center">

                    <h2 class="font-semibold">
                        Perbandingan sudah tersimpan
                    </h2>

                    <p
                        class="mt-2 text-sm
                           text-zinc-500">
                        Nilai perbandingan sudah tersedia,
                        tetapi bobot AHP belum dihitung.
                    </p>

                    <a
                        href="ahp.php"
                        class="mt-5 inline-flex
                           rounded-md bg-zinc-900
                           px-4 py-2.5
                           text-sm font-semibold
                           text-white
                           hover:bg-zinc-800">
                        Hitung Bobot AHP
                    </a>

                </section>


            <?php else: ?>


                <!-- =================================================
                 INFORMATION
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <div
                        class="grid grid-cols-1
                           gap-5 sm:grid-cols-2
                           lg:grid-cols-4">

                        <div>

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Periode
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
                                <?= count($criteria); ?>
                                Kriteria
                            </p>

                        </div>


                        <div>

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Penilai
                            </p>

                            <p class="mt-1 font-semibold">
                                <?= e(
                                    $hasil['nama_penilai']
                                ); ?>
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
                                <?= formatTanggalWaktu(
                                    $hasil['tanggal_proses']
                                ); ?>
                            </p>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                 1. PAIRWISE MATRIX
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <div class="mb-4">

                        <h2 class="font-semibold">
                            1. Matriks Perbandingan Berpasangan
                        </h2>

                        <p
                            class="mt-1 text-sm
                               text-zinc-500">
                            Matriks lengkap dibentuk dari
                            nilai perbandingan utama,
                            nilai diagonal 1, dan nilai
                            resiprokal otomatis.
                        </p>

                    </div>


                    <div class="overflow-x-auto">

                        <table
                            class="w-full min-w-[800px]
                               border-collapse text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th
                                        class="border-b
                                           border-zinc-300
                                           px-3 py-3
                                           text-left">
                                        Kriteria
                                    </th>

                                    <?php foreach (
                                        $criteria
                                        as $criterion
                                    ): ?>

                                        <th
                                            class="border-b
                                               border-zinc-300
                                               px-3 py-3
                                               text-center">
                                            <?= e(
                                                $criterion['kode_kriteria']
                                            ); ?>

                                            <span
                                                class="block text-[11px]
                                                   font-normal
                                                   text-zinc-500">
                                                <?= e(
                                                    $criterion['nama_kriteria']
                                                ); ?>
                                            </span>
                                        </th>

                                    <?php endforeach; ?>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $criteria
                                    as $i => $criterion
                                ): ?>

                                    <tr
                                        class="border-b
                                           border-zinc-200">

                                        <th
                                            class="px-3 py-3
                                               text-left">
                                            <?= e(
                                                $criterion['kode_kriteria']
                                            ); ?>

                                            <span
                                                class="block text-xs
                                                   font-normal
                                                   text-zinc-500">
                                                <?= e(
                                                    $criterion['nama_kriteria']
                                                ); ?>
                                            </span>
                                        </th>


                                        <?php foreach (
                                            $criteria
                                            as $j => $column
                                        ): ?>

                                            <td
                                                class="
                                                px-3 py-3
                                                text-center

                                                <?= $i === $j
                                                    ? 'bg-zinc-100 font-semibold'
                                                    : '';
                                                ?>
                                            ">
                                                <?= e(
                                                    matrixValue(
                                                        (float)
                                                        $pairwiseMatrix[$i][$j]
                                                    )
                                                ); ?>
                                            </td>

                                        <?php endforeach; ?>

                                    </tr>

                                <?php endforeach; ?>


                                <!-- Jumlah -->
                                <tr class="bg-zinc-100">

                                    <th
                                        class="px-3 py-3
                                           text-left">
                                        Jumlah
                                    </th>

                                    <?php foreach (
                                        $columnSums
                                        as $sum
                                    ): ?>

                                        <td
                                            class="px-3 py-3
                                               text-center
                                               font-semibold">
                                            <?= decimal(
                                                $sum,
                                                4
                                            ); ?>
                                        </td>

                                    <?php endforeach; ?>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                 2. NORMALIZED MATRIX
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <div class="mb-4">

                        <h2 class="font-semibold">
                            2. Matriks Normalisasi
                        </h2>

                        <p
                            class="mt-1 text-sm
                               text-zinc-500">
                            Setiap nilai matriks dibagi
                            dengan jumlah nilai pada kolom
                            yang bersangkutan.
                        </p>

                    </div>


                    <div class="overflow-x-auto">

                        <table
                            class="w-full min-w-[800px]
                               border-collapse text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th
                                        class="border-b
                                           border-zinc-300
                                           px-3 py-3
                                           text-left">
                                        Kriteria
                                    </th>

                                    <?php foreach (
                                        $criteria
                                        as $criterion
                                    ): ?>

                                        <th
                                            class="border-b
                                               border-zinc-300
                                               px-3 py-3
                                               text-center">
                                            <?= e(
                                                $criterion['nama_kriteria']
                                            ); ?>
                                        </th>

                                    <?php endforeach; ?>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $criteria
                                    as $i => $criterion
                                ): ?>

                                    <tr
                                        class="border-b
                                           border-zinc-200">

                                        <th
                                            class="px-3 py-3
                                               text-left">
                                            <?= e(
                                                $criterion['nama_kriteria']
                                            ); ?>
                                        </th>


                                        <?php foreach (
                                            $criteria
                                            as $j => $column
                                        ): ?>

                                            <td
                                                class="px-3 py-3
                                                   text-center
                                                   text-zinc-600">
                                                <?= decimal(
                                                    $normalizedMatrix[$i][$j],
                                                    4
                                                ); ?>
                                            </td>

                                        <?php endforeach; ?>

                                    </tr>

                                <?php endforeach; ?>


                                <tr class="bg-zinc-100">

                                    <th class="px-3 py-3">
                                        Jumlah
                                    </th>

                                    <?php foreach (
                                        $normalizedColumnSums
                                        as $sum
                                    ): ?>

                                        <td
                                            class="px-3 py-3
                                               text-center
                                               font-semibold">
                                            <?= decimal(
                                                $sum,
                                                4
                                            ); ?>
                                        </td>

                                    <?php endforeach; ?>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                 WEIGHT + CONSISTENCY
            ================================================== -->

                <section
                    class="grid grid-cols-1
                       gap-6 xl:grid-cols-2">


                    <!-- BOBOT -->
                    <article
                        class="rounded-md
                           border border-zinc-300
                           bg-white p-5">

                        <h2 class="font-semibold">
                            3. Bobot Prioritas
                            (Eigenvector)
                        </h2>

                        <p
                            class="mt-1 text-sm
                               text-zinc-500">
                            Bobot setiap kriteria
                            berdasarkan rata-rata baris
                            matriks normalisasi.
                        </p>


                        <div
                            class="mt-5
                               overflow-x-auto">

                            <table
                                class="w-full
                                   border-collapse
                                   text-sm">

                                <thead class="bg-zinc-100">

                                    <tr>

                                        <th
                                            class="px-3 py-3
                                               text-left">
                                            Kode
                                        </th>

                                        <th
                                            class="px-3 py-3
                                               text-left">
                                            Nama Kriteria
                                        </th>

                                        <th
                                            class="px-3 py-3
                                               text-right">
                                            Bobot
                                        </th>

                                        <th
                                            class="px-3 py-3
                                               text-right">
                                            Persentase
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $sortedWeights
                                        as $weight
                                    ): ?>

                                        <tr
                                            class="border-b
                                               border-zinc-200
                                               last:border-0">

                                            <td
                                                class="px-3 py-3
                                                   font-semibold">
                                                <?= e(
                                                    $weight['kode_kriteria']
                                                ); ?>
                                            </td>

                                            <td class="px-3 py-3">
                                                <?= e(
                                                    $weight['nama_kriteria']
                                                ); ?>
                                            </td>

                                            <td
                                                class="px-3 py-3
                                                   text-right
                                                   font-semibold">
                                                <?= decimal(
                                                    $weight['bobot'],
                                                    6
                                                ); ?>
                                            </td>

                                            <td
                                                class="px-3 py-3
                                                   text-right
                                                   text-zinc-600">
                                                <?= percentage(
                                                    $weight['bobot']
                                                ); ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </article>


                    <!-- CONSISTENCY -->
                    <article
                        class="rounded-md
                           border border-zinc-300
                           bg-white p-5">

                        <h2 class="font-semibold">
                            4. Uji Konsistensi
                        </h2>

                        <p
                            class="mt-1 text-sm
                               text-zinc-500">
                            Pengujian konsistensi terhadap
                            penilaian perbandingan
                            berpasangan.
                        </p>


                        <dl
                            class="mt-5 divide-y
                               divide-zinc-200
                               rounded-md
                               border border-zinc-300
                               px-4">

                            <div
                                class="flex
                                   justify-between
                                   py-3 text-sm">
                                <dt class="text-zinc-500">
                                    λ max
                                </dt>

                                <dd class="font-semibold">
                                    <?= decimal(
                                        $hasil['lambda_max'],
                                        6
                                    ); ?>
                                </dd>
                            </div>


                            <div
                                class="flex
                                   justify-between
                                   py-3 text-sm">
                                <dt class="text-zinc-500">
                                    CI
                                    (Consistency Index)
                                </dt>

                                <dd class="font-semibold">
                                    <?= decimal(
                                        $hasil['consistency_index'],
                                        6
                                    ); ?>
                                </dd>
                            </div>


                            <div
                                class="flex
                                   justify-between
                                   py-3 text-sm">
                                <dt class="text-zinc-500">
                                    RI
                                    (Random Index)
                                </dt>

                                <dd class="font-semibold">
                                    <?= decimal(
                                        $data['random_index'],
                                        2
                                    ); ?>
                                </dd>
                            </div>


                            <div
                                class="flex
                                   justify-between
                                   py-3 text-sm">
                                <dt class="font-semibold">
                                    CR
                                    (Consistency Ratio)
                                </dt>

                                <dd class="font-bold">
                                    <?= decimal(
                                        $hasil['consistency_ratio'],
                                        6
                                    ); ?>
                                </dd>
                            </div>

                        </dl>


                        <!-- STATUS -->
                        <?php if ($isConsistent): ?>

                            <div
                                class="mt-4
                                   rounded-md
                                   border
                                   border-emerald-300
                                   bg-emerald-50
                                   px-4 py-3
                                   text-sm
                                   font-semibold
                                   text-emerald-800">
                                ✓ KONSISTEN
                                (CR ≤ 0.10)

                                <p
                                    class="mt-1
                                       text-xs
                                       font-normal">
                                    Bobot kriteria dapat
                                    digunakan dalam proses
                                    TOPSIS.
                                </p>
                            </div>

                        <?php else: ?>

                            <div
                                class="mt-4
                                   rounded-md
                                   border border-red-300
                                   bg-red-50
                                   px-4 py-3
                                   text-sm
                                   font-semibold
                                   text-red-800">
                                ! TIDAK KONSISTEN
                                (CR &gt; 0.10)

                                <p
                                    class="mt-1
                                       text-xs
                                       font-normal">
                                    Perbandingan berpasangan
                                    perlu diperbaiki sebelum
                                    bobot digunakan pada TOPSIS.
                                </p>
                            </div>


                            <a
                                href="ahp.php"
                                class="mt-4 inline-flex
                                   rounded-md
                                   bg-zinc-900
                                   px-4 py-2.5
                                   text-sm
                                   font-semibold
                                   text-white
                                   hover:bg-zinc-800">
                                Perbaiki Perbandingan
                            </a>

                        <?php endif; ?>

                    </article>

                </section>


                <!-- =================================================
                 VISUALISASI
            ================================================== -->

                <section
                    class="mt-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <h2 class="font-semibold">
                        Visualisasi Bobot Kriteria
                    </h2>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Perbandingan proporsi bobot
                        masing-masing kriteria.
                    </p>


                    <div class="mt-6 space-y-4">

                        <?php foreach (
                            $sortedWeights
                            as $weight
                        ): ?>

                            <?php
                            $weightValue =
                                (float)
                                $weight['bobot'];

                            $barWidth =
                                min(
                                    100,
                                    max(
                                        0,
                                        $weightValue * 100
                                    )
                                );
                            ?>

                            <div
                                class="grid
                                   grid-cols-1
                                   gap-2
                                   sm:grid-cols-[180px_1fr_80px]
                                   sm:items-center">

                                <div>

                                    <p
                                        class="text-sm
                                           font-medium">
                                        <?= e(
                                            $weight['nama_kriteria']
                                        ); ?>
                                    </p>

                                    <p
                                        class="text-[11px]
                                           text-zinc-500">
                                        <?= e(
                                            $weight['kode_kriteria']
                                        ); ?>

                                        •

                                        <?= e(
                                            $weight['jenis_kriteria']
                                        ); ?>
                                    </p>

                                </div>


                                <div
                                    class="h-4
                                       overflow-hidden
                                       rounded-sm
                                       bg-zinc-100">

                                    <div
                                        class="h-full
                                           rounded-sm
                                           bg-zinc-700"
                                        style="width: <?= round(
                                                            $barWidth,
                                                            2
                                                        ); ?>%"></div>

                                </div>


                                <span
                                    class="text-sm
                                       font-semibold">
                                    <?= percentage(
                                        $weightValue
                                    ); ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </section>


                <!-- =================================================
                 FOOTER ACTION
            ================================================== -->

                <section
                    class="mt-6 flex
                       flex-col gap-3
                       sm:flex-row
                       sm:justify-end">

                    <a
                        href="ahp.php"
                        class="inline-flex
                           justify-center
                           rounded-md
                           border border-zinc-400
                           bg-white
                           px-5 py-2.5
                           text-sm font-semibold
                           hover:bg-zinc-100">
                        Lihat Perbandingan
                    </a>


                    <?php if ($isConsistent): ?>

                        <a
                            href="topsis.php"
                            class="inline-flex
                               justify-center
                               rounded-md
                               bg-zinc-900
                               px-5 py-2.5
                               text-sm
                               font-semibold
                               text-white
                               hover:bg-zinc-800">
                            Lanjut ke TOPSIS →
                        </a>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

        </main>

    </div>


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
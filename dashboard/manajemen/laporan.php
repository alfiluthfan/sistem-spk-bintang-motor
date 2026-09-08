<?php

declare(strict_types=1);

require_once __DIR__
    . '/../../src/Auth.php';

require_once __DIR__
    . '/../../src/ReportService.php';


Auth::requireRole('manajemen');

$user = Auth::user();

$reportService =
    new ReportService(
        Database::connect()
    );

$activePage = 'laporan';


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


function percentage(
    mixed $value
): string {
    if (
        $value === null
        || $value === ''
    ) {
        return '-';
    }

    return number_format(
        (float) $value * 100,
        2,
        '.',
        ''
    ) . '%';
}


function formatDate(
    ?string $value
): string {
    if (!$value) {
        return '-';
    }

    return date(
        'd/m/Y',
        strtotime($value)
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
        default => 'Prioritas ke-' . $rank,
    };
}


/*
|--------------------------------------------------------------------------
| Periode
|--------------------------------------------------------------------------
*/

$periods =
    $reportService->getPeriods();

$defaultPeriod =
    $reportService->getDefaultPeriod();

$idPeriode = null;

$requestedPeriod =
    $_GET['periode'] ?? null;

if ($requestedPeriod !== null) {
    $validated = filter_var(
        $requestedPeriod,
        FILTER_VALIDATE_INT
    );

    if (
        $validated !== false
        && $validated > 0
    ) {
        $idPeriode =
            (int) $validated;
    }
}

if (
    $idPeriode === null
    && $defaultPeriod !== null
) {
    $idPeriode =
        (int) $defaultPeriod['id_periode'];
}


/*
|--------------------------------------------------------------------------
| Jenis laporan
|--------------------------------------------------------------------------
*/

$reportType =
    (string) (
        $_GET['jenis'] ?? 'lengkap'
    );

$allowedTypes = [
    'lengkap',
    'ahp',
    'topsis',
    'rekomendasi',
];

if (
    !in_array(
        $reportType,
        $allowedTypes,
        true
    )
) {
    $reportType = 'lengkap';
}


/*
|--------------------------------------------------------------------------
| Data
|--------------------------------------------------------------------------
*/

$report = [
    'periode' => null,
    'hasil_ahp' => null,
    'weights' => [],
    'hasil_topsis' => null,
    'ranking' => [],
    'winner' => null,
];

if ($idPeriode !== null) {
    $report =
        $reportService->getReportData(
            $idPeriode
        );
}

$periode =
    $report['periode'];

$hasilAhp =
    $report['hasil_ahp'];

$weights =
    $report['weights'];

$hasilTopsis =
    $report['hasil_topsis'];

$ranking =
    $report['ranking'];

$winner =
    $report['winner'];


/*
|--------------------------------------------------------------------------
| Penandatangan
|--------------------------------------------------------------------------
*/

$signerName =
    trim(
        (string) (
            $_GET['penandatangan']
            ?? $user['nama']
            ?? ''
        )
    );

$signerPosition =
    trim(
        (string) (
            $_GET['jabatan']
            ?? 'Kepala Cabang'
        )
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
        Laporan Hasil SPK | SPK Motor
    </title>

    <link
        rel="stylesheet"
        href="../../assets/css/app.css">


    <style>
        @page {
            size: A4;
            margin: 16mm;
        }

        @media print {

            body {
                background: #ffffff !important;
            }

            #sidebar,
            #sidebarOverlay,
            #topbar,
            .no-print {
                display: none !important;
            }

            #mainShell {
                margin-left: 0 !important;
            }

            #printArea {
                border: none !important;
                padding: 0 !important;
                background: #ffffff !important;
            }

            .print-break-avoid {
                break-inside: avoid;
            }

            table {
                break-inside: auto;
            }

            tr {
                break-inside: avoid;
            }
        }
    </style>

</head>


<body class="bg-zinc-100 text-zinc-900">


    <?php
    require __DIR__
        . '/../../partials/manajemen-sidebar.php';
    ?>


    <div
        id="mainShell"
        class="min-h-screen lg:ml-60">


        <!-- =====================================================
         TOPBAR
    ====================================================== -->

        <header
            id="topbar"
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
                        Laporan
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


        <main class="p-4 sm:p-6 lg:p-8">


            <!-- =================================================
             TITLE
        ================================================== -->

            <div class="mb-6 no-print">

                <h1
                    class="text-2xl
                       font-bold tracking-tight">
                    Laporan Hasil Analisis
                </h1>

                <p
                    class="mt-1 text-sm
                       text-zinc-500">
                    Pilih periode dan jenis laporan,
                    kemudian cetak hasil analisis
                    Sistem Pendukung Keputusan.
                </p>

            </div>


            <!-- =================================================
             CONFIGURATION + PREVIEW
        ================================================== -->

            <div
                class="grid grid-cols-1
                   gap-6 xl:grid-cols-[430px_1fr]">


                <!-- =============================================
                 CONFIGURATION
            ============================================== -->

                <aside class="space-y-5 no-print">


                    <!-- Jenis laporan -->
                    <section
                        class="rounded-md border
                           border-zinc-300
                           bg-white p-5">

                        <h2 class="font-semibold">
                            Pilih Jenis Laporan
                        </h2>


                        <form
                            id="reportForm"
                            action="laporan.php"
                            method="GET"
                            class="mt-4">

                            <?php if (
                                $idPeriode !== null
                            ): ?>

                                <input
                                    type="hidden"
                                    name="periode"
                                    value="<?= $idPeriode; ?>">

                            <?php endif; ?>


                            <div class="space-y-3">

                                <label
                                    class="flex cursor-pointer
                                       items-center gap-3
                                       text-sm">

                                    <input
                                        type="radio"
                                        name="jenis"
                                        value="lengkap"
                                        <?= $reportType
                                            === 'lengkap'
                                            ? 'checked'
                                            : '';
                                        ?>
                                        onchange="
                                        this.form.submit()
                                    ">

                                    Laporan Lengkap
                                    (AHP + TOPSIS)

                                </label>


                                <label
                                    class="flex cursor-pointer
                                       items-center gap-3
                                       text-sm">

                                    <input
                                        type="radio"
                                        name="jenis"
                                        value="ahp"
                                        <?= $reportType
                                            === 'ahp'
                                            ? 'checked'
                                            : '';
                                        ?>
                                        onchange="
                                        this.form.submit()
                                    ">

                                    Laporan Bobot Kriteria
                                    (AHP)

                                </label>


                                <label
                                    class="flex cursor-pointer
                                       items-center gap-3
                                       text-sm">

                                    <input
                                        type="radio"
                                        name="jenis"
                                        value="topsis"
                                        <?= $reportType
                                            === 'topsis'
                                            ? 'checked'
                                            : '';
                                        ?>
                                        onchange="
                                        this.form.submit()
                                    ">

                                    Laporan Perangkingan
                                    (TOPSIS)

                                </label>


                                <label
                                    class="flex cursor-pointer
                                       items-center gap-3
                                       text-sm">

                                    <input
                                        type="radio"
                                        name="jenis"
                                        value="rekomendasi"
                                        <?= $reportType
                                            === 'rekomendasi'
                                            ? 'checked'
                                            : '';
                                        ?>
                                        onchange="
                                        this.form.submit()
                                    ">

                                    Laporan Rekomendasi
                                    Produk Unggulan

                                </label>

                            </div>

                        </form>

                    </section>


                    <!-- Pengaturan -->
                    <section
                        class="rounded-md border
                           border-zinc-300
                           bg-white p-5">

                        <h2 class="font-semibold">
                            Pengaturan Dokumen Laporan
                        </h2>


                        <form
                            action="laporan.php"
                            method="GET"
                            class="mt-5 space-y-4">

                            <input
                                type="hidden"
                                name="jenis"
                                value="<?= e(
                                            $reportType
                                        ); ?>">


                            <!-- Periode -->
                            <div>

                                <label
                                    for="periode"
                                    class="mb-1.5 block
                                       text-sm font-medium">
                                    Periode Laporan
                                </label>

                                <select
                                    id="periode"
                                    name="periode"
                                    onchange="
                                    this.form.submit()
                                "
                                    class="w-full rounded-md
                                       border border-zinc-400
                                       bg-white px-3 py-2.5
                                       text-sm outline-none
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                                    <?php foreach (
                                        $periods as $period
                                    ): ?>

                                        <option
                                            value="<?= (int)
                                                    $period['id_periode']; ?>"
                                            <?= $idPeriode
                                                ===
                                                (int) $period['id_periode']
                                                ? 'selected'
                                                : '';
                                            ?>>
                                            <?= e(
                                                $period['nama_periode']
                                            ); ?>

                                            —
                                            <?= e(
                                                $period['status']
                                            ); ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- Tanggal -->
                            <div>

                                <label
                                    class="mb-1.5 block
                                       text-sm font-medium">
                                    Tanggal Cetak
                                </label>

                                <input
                                    type="text"
                                    value="<?= date(
                                                'd/m/Y'
                                            ); ?>"
                                    readonly
                                    class="w-full
                                       rounded-md
                                       border
                                       border-zinc-300
                                       bg-zinc-100
                                       px-3 py-2.5
                                       text-sm">

                            </div>


                            <!-- Penandatangan -->
                            <div>

                                <label
                                    for="penandatangan"
                                    class="mb-1.5 block
                                       text-sm font-medium">
                                    Ditandatangani Oleh
                                </label>

                                <input
                                    type="text"
                                    id="penandatangan"
                                    name="penandatangan"
                                    value="<?= e(
                                                $signerName
                                            ); ?>"
                                    maxlength="100"
                                    class="w-full
                                       rounded-md
                                       border
                                       border-zinc-400
                                       bg-white px-3
                                       py-2.5 text-sm
                                       outline-none
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                            </div>


                            <!-- Jabatan -->
                            <div>

                                <label
                                    for="jabatan"
                                    class="mb-1.5 block
                                       text-sm font-medium">
                                    Jabatan Penandatangan
                                </label>

                                <input
                                    type="text"
                                    id="jabatan"
                                    name="jabatan"
                                    value="<?= e(
                                                $signerPosition
                                            ); ?>"
                                    maxlength="100"
                                    class="w-full
                                       rounded-md
                                       border
                                       border-zinc-400
                                       bg-white px-3
                                       py-2.5 text-sm
                                       outline-none
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                            </div>


                            <button
                                type="submit"
                                class="w-full
                                   rounded-md
                                   border border-zinc-400
                                   bg-white
                                   px-4 py-2.5
                                   text-sm font-semibold
                                   hover:bg-zinc-100">
                                Terapkan Pengaturan
                            </button>

                        </form>

                    </section>

                </aside>


                <!-- =============================================
                 PRINT DOCUMENT
            ============================================== -->

                <section
                    id="printArea"
                    class="rounded-md
                       border border-zinc-300
                       bg-white p-6
                       sm:p-8">


                    <?php if (
                        $periode === null
                    ): ?>

                        <div
                            class="py-16 text-center">

                            <h2 class="font-semibold">
                                Data laporan belum tersedia
                            </h2>

                            <p
                                class="mt-2 text-sm
                                   text-zinc-500">
                                Belum terdapat periode
                                penilaian yang dapat
                                ditampilkan.
                            </p>

                        </div>


                    <?php else: ?>


                        <!-- =====================================
                         REPORT HEADER
                    ====================================== -->

                        <header
                            class="border-b-2
                               border-zinc-900
                               pb-5 text-center">

                            <h2
                                class="text-xl font-bold
                                   uppercase">
                                Laporan Hasil Analisis SPK
                            </h2>

                            <p
                                class="mt-1 font-medium">
                                Penentuan Produk Unggulan
                                Motor Terbaik
                            </p>

                            <p
                                class="mt-1 text-sm
                                   text-zinc-500">
                                Dealer Bintang Motor Cinere
                            </p>


                            <p
                                class="mt-3 text-sm">
                                Periode:
                                <strong>
                                    <?= e(
                                        $periode['nama_periode']
                                    ); ?>
                                </strong>
                            </p>

                        </header>


                        <!-- =====================================
                         PERIODE
                    ====================================== -->

                        <section
                            class="mt-6
                               print-break-avoid">

                            <h3
                                class="font-semibold">
                                Informasi Periode Penilaian
                            </h3>


                            <table
                                class="mt-3 w-full
                                   border-collapse
                                   text-sm">

                                <tbody>

                                    <tr
                                        class="border-b
                                           border-zinc-200">
                                        <td
                                            class="w-48
                                               py-2
                                               text-zinc-500">
                                            Nama Periode
                                        </td>

                                        <td
                                            class="py-2
                                               font-medium">
                                            <?= e(
                                                $periode['nama_periode']
                                            ); ?>
                                        </td>
                                    </tr>


                                    <tr
                                        class="border-b
                                           border-zinc-200">
                                        <td
                                            class="py-2
                                               text-zinc-500">
                                            Tanggal Mulai
                                        </td>

                                        <td class="py-2">
                                            <?= formatDate(
                                                $periode['tanggal_mulai']
                                            ); ?>
                                        </td>
                                    </tr>


                                    <tr
                                        class="border-b
                                           border-zinc-200">
                                        <td
                                            class="py-2
                                               text-zinc-500">
                                            Tanggal Selesai
                                        </td>

                                        <td class="py-2">
                                            <?= formatDate(
                                                $periode['tanggal_selesai']
                                            ); ?>
                                        </td>
                                    </tr>


                                    <tr>
                                        <td
                                            class="py-2
                                               text-zinc-500">
                                            Status
                                        </td>

                                        <td
                                            class="py-2
                                               font-medium">
                                            <?= e(
                                                $periode['status']
                                            ); ?>
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </section>


                        <!-- =====================================
                         AHP
                    ====================================== -->

                        <?php if (
                            in_array(
                                $reportType,
                                ['lengkap', 'ahp'],
                                true
                            )
                        ): ?>

                            <section class="mt-8">

                                <h3
                                    class="font-semibold">
                                    Hasil Pembobotan Kriteria
                                    — AHP
                                </h3>


                                <?php if (
                                    $hasilAhp === null
                                ): ?>

                                    <p
                                        class="mt-3
                                           text-sm
                                           text-zinc-500">
                                        Hasil perhitungan AHP
                                        belum tersedia pada
                                        periode ini.
                                    </p>

                                <?php else: ?>


                                    <!-- Bobot -->
                                    <div
                                        class="mt-4
                                           overflow-x-auto">

                                        <table
                                            class="w-full
                                               border-collapse
                                               text-sm">

                                            <thead
                                                class="bg-zinc-100">

                                                <tr>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-left">
                                                        Kode
                                                    </th>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-left">
                                                        Kriteria
                                                    </th>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-left">
                                                        Jenis
                                                    </th>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-right">
                                                        Bobot
                                                    </th>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-right">
                                                        Persentase
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody>

                                                <?php foreach (
                                                    $weights as $weight
                                                ): ?>

                                                    <tr>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           font-semibold">
                                                            <?= e(
                                                                $weight['kode_kriteria']
                                                            ); ?>
                                                        </td>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2">
                                                            <?= e(
                                                                $weight['nama_kriteria']
                                                            ); ?>
                                                        </td>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2">
                                                            <?= e(
                                                                $weight['jenis_kriteria']
                                                            ); ?>
                                                        </td>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-right">
                                                            <?= decimal(
                                                                $weight['bobot'],
                                                                6
                                                            ); ?>
                                                        </td>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-right">
                                                            <?= percentage(
                                                                $weight['bobot']
                                                            ); ?>
                                                        </td>

                                                    </tr>

                                                <?php endforeach; ?>

                                            </tbody>

                                        </table>

                                    </div>


                                    <!-- Konsistensi -->
                                    <div
                                        class="mt-5
                                           print-break-avoid">

                                        <h4
                                            class="text-sm
                                               font-semibold">
                                            Uji Konsistensi
                                        </h4>

                                        <dl
                                            class="mt-2
                                               divide-y
                                               divide-zinc-200
                                               text-sm">

                                            <div
                                                class="flex
                                                   justify-between
                                                   py-2">
                                                <dt>
                                                    λ max
                                                </dt>

                                                <dd
                                                    class="font-medium">
                                                    <?= decimal(
                                                        $hasilAhp['lambda_max'],
                                                        6
                                                    ); ?>
                                                </dd>
                                            </div>


                                            <div
                                                class="flex
                                                   justify-between
                                                   py-2">
                                                <dt>
                                                    Consistency
                                                    Index (CI)
                                                </dt>

                                                <dd
                                                    class="font-medium">
                                                    <?= decimal(
                                                        $hasilAhp['consistency_index'],
                                                        6
                                                    ); ?>
                                                </dd>
                                            </div>


                                            <div
                                                class="flex
                                                   justify-between
                                                   py-2">
                                                <dt>
                                                    Consistency
                                                    Ratio (CR)
                                                </dt>

                                                <dd
                                                    class="font-medium">
                                                    <?= decimal(
                                                        $hasilAhp['consistency_ratio'],
                                                        6
                                                    ); ?>
                                                </dd>
                                            </div>


                                            <div
                                                class="flex
                                                   justify-between
                                                   py-2">
                                                <dt>
                                                    Status
                                                </dt>

                                                <dd
                                                    class="font-semibold">
                                                    <?= e(
                                                        $hasilAhp['status_konsistensi']
                                                    ); ?>
                                                </dd>
                                            </div>

                                        </dl>

                                    </div>

                                <?php endif; ?>

                            </section>

                        <?php endif; ?>


                        <!-- =====================================
                         TOPSIS / RANKING
                    ====================================== -->

                        <?php if (
                            in_array(
                                $reportType,
                                ['lengkap', 'topsis'],
                                true
                            )
                        ): ?>

                            <section class="mt-8">

                                <h3 class="font-semibold">
                                    Hasil Perangkingan TOPSIS
                                </h3>


                                <?php if (
                                    empty($ranking)
                                ): ?>

                                    <p
                                        class="mt-3
                                           text-sm
                                           text-zinc-500">
                                        Hasil TOPSIS belum
                                        tersedia pada periode
                                        ini.
                                    </p>

                                <?php else: ?>

                                    <div
                                        class="mt-4
                                           overflow-x-auto">

                                        <table
                                            class="w-full
                                               border-collapse
                                               text-sm">

                                            <thead
                                                class="bg-zinc-100">

                                                <tr>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-center">
                                                        Peringkat
                                                    </th>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-left">
                                                        Kode
                                                    </th>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-left">
                                                        Produk
                                                    </th>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-right">
                                                        Preferensi
                                                    </th>

                                                    <th
                                                        class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-left">
                                                        Prioritas
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody>

                                                <?php foreach (
                                                    $ranking
                                                    as $result
                                                ): ?>

                                                    <?php
                                                    $rank =
                                                        (int)
                                                        $result['peringkat'];
                                                    ?>

                                                    <tr>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-center
                                                           font-semibold">
                                                            <?= $rank; ?>
                                                        </td>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2">
                                                            <?= e(
                                                                $result['kode_alternatif']
                                                            ); ?>
                                                        </td>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           font-medium">
                                                            <?= e(
                                                                $result['nama_alternatif']
                                                            ); ?>
                                                        </td>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2
                                                           text-right">
                                                            <?= decimal(
                                                                $result['nilai_preferensi'],
                                                                6
                                                            ); ?>
                                                        </td>

                                                        <td
                                                            class="border
                                                           border-zinc-300
                                                           px-3 py-2">
                                                            <?= e(
                                                                priorityLabel(
                                                                    $rank
                                                                )
                                                            ); ?>
                                                        </td>

                                                    </tr>

                                                <?php endforeach; ?>

                                            </tbody>

                                        </table>

                                    </div>

                                <?php endif; ?>

                            </section>

                        <?php endif; ?>


                        <!-- =====================================
                         REKOMENDASI
                    ====================================== -->

                        <?php if (
                            in_array(
                                $reportType,
                                [
                                    'lengkap',
                                    'rekomendasi'
                                ],
                                true
                            )
                        ): ?>

                            <section
                                class="mt-8
                                   print-break-avoid">

                                <h3 class="font-semibold">
                                    Rekomendasi Produk Unggulan
                                </h3>


                                <?php if (
                                    $winner === null
                                ): ?>

                                    <p
                                        class="mt-3 text-sm
                                           text-zinc-500">
                                        Rekomendasi belum
                                        tersedia karena TOPSIS
                                        belum diproses.
                                    </p>

                                <?php else: ?>

                                    <div
                                        class="mt-4
                                           rounded-md
                                           border
                                           border-zinc-300
                                           bg-zinc-50
                                           p-5">

                                        <p
                                            class="text-xs
                                               font-medium
                                               uppercase
                                               text-zinc-500">
                                            Produk Unggulan
                                        </p>

                                        <p
                                            class="mt-1
                                               text-xl
                                               font-bold">
                                            <?= e(
                                                $winner['kode_alternatif']
                                            ); ?>

                                            —

                                            <?= e(
                                                $winner['nama_alternatif']
                                            ); ?>
                                        </p>

                                        <p
                                            class="mt-2
                                               text-sm
                                               leading-6">
                                            Berdasarkan hasil
                                            perhitungan metode
                                            AHP-TOPSIS,
                                            alternatif tersebut
                                            memperoleh nilai
                                            preferensi tertinggi
                                            sebesar

                                            <strong>
                                                <?= decimal(
                                                    $winner['nilai_preferensi'],
                                                    6
                                                ); ?>
                                            </strong>

                                            dan menempati
                                            peringkat pertama.
                                            Dengan demikian,
                                            produk tersebut
                                            direkomendasikan
                                            sebagai produk
                                            unggulan pada periode

                                            <strong>
                                                <?= e(
                                                    $periode['nama_periode']
                                                ); ?>
                                            </strong>.
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </section>

                        <?php endif; ?>


                        <!-- =====================================
                         PROCESS INFORMATION
                    ====================================== -->

                        <?php if (
                            $reportType === 'lengkap'
                        ): ?>

                            <section
                                class="mt-8
                                   print-break-avoid">

                                <h3 class="font-semibold">
                                    Informasi Proses
                                </h3>

                                <div
                                    class="mt-3
                                       grid grid-cols-1
                                       gap-3 sm:grid-cols-2
                                       text-sm">

                                    <div
                                        class="rounded-md
                                           border
                                           border-zinc-300
                                           p-3">
                                        <p
                                            class="text-xs
                                               text-zinc-500">
                                            Proses AHP
                                        </p>

                                        <p
                                            class="mt-1
                                               font-medium">
                                            <?= $hasilAhp
                                                !== null
                                                ? formatDateTime(
                                                    $hasilAhp['tanggal_proses']
                                                )
                                                : '-';
                                            ?>
                                        </p>
                                    </div>


                                    <div
                                        class="rounded-md
                                           border
                                           border-zinc-300
                                           p-3">
                                        <p
                                            class="text-xs
                                               text-zinc-500">
                                            Proses TOPSIS
                                        </p>

                                        <p
                                            class="mt-1
                                               font-medium">
                                            <?= $hasilTopsis
                                                !== null
                                                ? formatDateTime(
                                                    $hasilTopsis['tanggal_proses']
                                                )
                                                : '-';
                                            ?>
                                        </p>
                                    </div>

                                </div>

                            </section>

                        <?php endif; ?>


                        <!-- =====================================
                         SIGNATURE
                    ====================================== -->

                        <section
                            class="mt-12
                               ml-auto
                               w-full
                               max-w-[260px]
                               text-center
                               text-sm
                               print-break-avoid">

                            <p>
                                Cinere,
                                <?= date('d/m/Y'); ?>
                            </p>

                            <p class="mt-1">
                                Mengetahui,
                            </p>

                            <p
                                id="previewPosition"
                                class="font-medium">
                                <?= e(
                                    $signerPosition
                                ); ?>
                            </p>


                            <div class="h-20"></div>


                            <p
                                id="previewSigner"
                                class="border-t
                                   border-zinc-900
                                   pt-2
                                   font-semibold">
                                <?= e(
                                    $signerName
                                ); ?>
                            </p>

                        </section>


                        <!-- =====================================
                         FOOTER
                    ====================================== -->

                        <footer
                            class="mt-10
                               border-t
                               border-zinc-200
                               pt-3
                               text-center
                               text-[11px]
                               text-zinc-500">
                            Dicetak pada
                            <?= date(
                                'd/m/Y H:i'
                            ); ?>
                            —
                            Sistem Pendukung Keputusan
                            Dealer Bintang Motor Cinere
                        </footer>

                    <?php endif; ?>

                </section>

            </div>


            <!-- =================================================
             ACTION BUTTONS
        ================================================== -->

            <?php if (
                $periode !== null
            ): ?>

                <div
                    class="mt-6
                       flex justify-end
                       gap-3 no-print">

                    <a
                        href="hasil.php"
                        class="rounded-md
                           border border-zinc-400
                           bg-white
                           px-5 py-2.5
                           text-sm font-semibold
                           hover:bg-zinc-100">
                        Kembali
                    </a>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="rounded-md
                           bg-zinc-900
                           px-5 py-2.5
                           text-sm font-semibold
                           text-white
                           hover:bg-zinc-800">
                        Cetak / Simpan PDF
                    </button>

                </div>

            <?php endif; ?>

        </main>

    </div>


    <script>
        /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */

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


        /*
        |--------------------------------------------------------------------------
        | Live preview signature
        |--------------------------------------------------------------------------
        */

        const signerInput =
            document.getElementById(
                'penandatangan'
            );

        const positionInput =
            document.getElementById(
                'jabatan'
            );

        const signerPreview =
            document.getElementById(
                'previewSigner'
            );

        const positionPreview =
            document.getElementById(
                'previewPosition'
            );


        signerInput?.addEventListener(
            'input',
            function() {

                if (signerPreview) {
                    signerPreview.textContent =
                        this.value || '-';
                }

            }
        );


        positionInput?.addEventListener(
            'input',
            function() {

                if (positionPreview) {
                    positionPreview.textContent =
                        this.value || '-';
                }

            }
        );
    </script>


</body>

</html>
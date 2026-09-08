<?php

declare(strict_types=1);

require_once __DIR__
    . '/../../src/Auth.php';

require_once __DIR__
    . '/../../src/TopsisService.php';

Auth::requireRole('manajemen');

$user = Auth::user();

$topsisService =
    new TopsisService(
        Database::connect()
    );

$activePage = 'topsis';

$error = null;
$success = null;


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


function rawNumber(
    mixed $value
): string {
    if (
        $value === null
        || $value === ''
    ) {
        return '-';
    }

    $number = (float) $value;

    if (
        abs($number - round($number))
        < 0.0000001
    ) {
        return number_format(
            $number,
            0,
            ',',
            '.'
        );
    }

    return number_format(
        $number,
        2,
        ',',
        '.'
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


/*
|--------------------------------------------------------------------------
| Flash
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['success'])) {
    $success =
        (string) $_SESSION['success'];

    unset($_SESSION['success']);
}


/*
|--------------------------------------------------------------------------
| Process TOPSIS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    $csrf =
        (string) (
            $_POST['_token'] ?? ''
        );

    if (!Auth::verifyCsrf($csrf)) {
        $error =
            'Permintaan tidak valid. Silakan coba kembali.';
    } else {
        try {
            $idHasil =
                $topsisService
                ->processAndSave();

            $_SESSION['success'] =
                'Perhitungan TOPSIS berhasil diselesaikan dan hasil perangkingan telah disimpan.';

            header(
                'Location: topsis.php?hasil='
                    . $idHasil
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                $exception->getMessage()
            );

            $error =
                $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Terjadi kesalahan saat menjalankan perhitungan TOPSIS.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page Data
|--------------------------------------------------------------------------
*/

$data =
    $topsisService->getPageData();

$periode =
    $data['periode'];

$criteria =
    $data['criteria'];

$alternatives =
    $data['alternatives'];

$hasilAhp =
    $data['hasil_ahp'];

$weights =
    $data['weights'];

$calculation =
    $data['calculation'];

$latestResult =
    $data['latest_result'];

$ready =
    $data['ready'];

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
        Perhitungan TOPSIS | SPK Motor
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
               flex h-16
               items-center justify-between
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
                        Perhitungan TOPSIS
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
                    Proses Perhitungan TOPSIS
                </h1>

                <p
                    class="mt-1 text-sm
                       text-zinc-500">
                    Evaluasi alternatif motor menggunakan
                    bobot kriteria hasil AHP yang konsisten.
                </p>

            </div>


            <!-- SUCCESS -->
            <?php if ($success !== null): ?>

                <div
                    class="mb-5 rounded-md
                       border border-zinc-300
                       bg-white px-4 py-3
                       text-sm">
                    <?= e($success); ?>
                </div>

            <?php endif; ?>


            <!-- ERROR -->
            <?php if ($error !== null): ?>

                <div
                    class="mb-5 flex gap-3
                       rounded-md border
                       border-zinc-400
                       bg-zinc-50 px-4 py-3
                       text-sm text-zinc-700">
                    <strong>!</strong>

                    <span>
                        <?= e($error); ?>
                    </span>
                </div>

            <?php endif; ?>


            <!-- =================================================
             STATUS / BOBOT
        ================================================== -->

            <section
                class="mb-6 rounded-md
                   border border-zinc-300
                   bg-white p-5">

                <div
                    class="grid grid-cols-1
                       gap-5 lg:grid-cols-3">

                    <!-- Periode -->
                    <div>

                        <p
                            class="text-xs font-medium
                               uppercase
                               text-zinc-500">
                            Periode Aktif
                        </p>

                        <p
                            class="mt-2 font-semibold">
                            <?= $periode !== null
                                ? e(
                                    $periode['nama_periode']
                                )
                                : 'Belum Ada';
                            ?>
                        </p>

                    </div>


                    <!-- AHP -->
                    <div>

                        <p
                            class="text-xs font-medium
                               uppercase
                               text-zinc-500">
                            Status AHP
                        </p>

                        <?php if (
                            $hasilAhp !== null
                        ): ?>

                            <p
                                class="mt-2
                                   font-semibold">
                                <?= e(
                                    $hasilAhp['status_konsistensi']
                                        ?? 'Belum Dihitung'
                                ); ?>
                            </p>

                            <p
                                class="mt-1 text-xs
                                   text-zinc-500">
                                CR =
                                <?= decimal(
                                    $hasilAhp['consistency_ratio'],
                                    4
                                ); ?>
                            </p>

                        <?php else: ?>

                            <p
                                class="mt-2
                                   text-zinc-500">
                                Belum Ada
                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- Nilai -->
                    <div>

                        <p
                            class="text-xs font-medium
                               uppercase
                               text-zinc-500">
                            Nilai Alternatif
                        </p>

                        <p
                            class="mt-2 font-semibold">
                            <?= $data['nilai_terisi']; ?>

                            /

                            <?= $data['nilai_total']; ?>

                            Data
                        </p>

                    </div>

                </div>


                <!-- Bobot -->
                <?php if (!empty($weights)): ?>

                    <div
                        class="mt-5 border-t
                           border-zinc-200 pt-5">

                        <p class="text-sm font-semibold">
                            Bobot Kriteria dari AHP (W)
                        </p>

                        <div
                            class="mt-3 flex
                               flex-wrap gap-2">

                            <?php foreach (
                                $weights as $weight
                            ): ?>

                                <span
                                    class="rounded-md
                                       border
                                       border-zinc-300
                                       bg-zinc-50
                                       px-3 py-2
                                       text-xs">
                                    <strong>
                                        <?= e(
                                            $weight['kode_kriteria']
                                        ); ?>
                                    </strong>

                                    —

                                    <?= decimal(
                                        $weight['bobot'],
                                        4
                                    ); ?>

                                    <span
                                        class="text-zinc-500">
                                        (
                                        <?= e(
                                            $weight['jenis_kriteria']
                                        ); ?>
                                        )
                                    </span>
                                </span>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- Action -->
                <div
                    class="mt-5 flex
                       flex-col gap-3
                       border-t
                       border-zinc-200 pt-5
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

                    <div>

                        <?php if ($ready): ?>

                            <p
                                class="text-sm
                                   font-medium">
                                Data siap diproses.
                            </p>

                        <?php else: ?>

                            <p
                                class="text-sm
                                   font-medium">
                                TOPSIS belum dapat dijalankan.
                            </p>

                            <p
                                class="mt-1 text-xs
                                   text-zinc-500">
                                <?= e(
                                    $data['message']
                                        ?? ''
                                ); ?>
                            </p>

                        <?php endif; ?>

                    </div>


                    <?php if ($ready): ?>

                        <form
                            action="topsis.php"
                            method="POST">

                            <input
                                type="hidden"
                                name="_token"
                                value="<?= e(
                                            Auth::csrfToken()
                                        ); ?>">

                            <button
                                type="submit"
                                class="rounded-md
                                   bg-zinc-900
                                   px-5 py-2.5
                                   text-sm
                                   font-semibold
                                   text-white
                                   transition
                                   hover:bg-zinc-800">
                                Jalankan Perhitungan TOPSIS
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </section>


            <?php if (
                !$ready
                && $hasilAhp !== null
                && (
                    $hasilAhp['consistency_ratio'] !== null
                    && (float) $hasilAhp['consistency_ratio'] > 0.10
                )
            ): ?>

                <div class="mb-6">

                    <a
                        href="ahp.php"
                        class="inline-flex
                           rounded-md
                           border border-zinc-400
                           bg-white
                           px-4 py-2.5
                           text-sm font-semibold
                           hover:bg-zinc-100">
                        Perbaiki Perbandingan AHP
                    </a>

                </div>

            <?php endif; ?>


            <?php if (
                $latestResult !== null
                && $calculation !== null
            ): ?>


                <!-- =================================================
                 STALE WARNING
            ================================================== -->

                <?php if (
                    !$data['result_is_current']
                ): ?>

                    <div
                        class="mb-6 rounded-md
                           border border-amber-300
                           bg-amber-50
                           px-4 py-3
                           text-sm
                           text-amber-800">
                        Data nilai atau bobot AHP telah berubah
                        sejak hasil TOPSIS terakhir dibuat.
                        Jalankan kembali perhitungan untuk
                        memperbarui hasil perangkingan.
                    </div>

                <?php endif; ?>


                <!-- =================================================
                 HASIL TERAKHIR
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white px-5 py-4">

                    <div
                        class="flex flex-col gap-2
                           sm:flex-row
                           sm:items-center
                           sm:justify-between">

                        <div>

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Hasil TOPSIS Terakhir
                            </p>

                            <p class="mt-1 font-semibold">
                                Proses #
                                <?= (int)
                                $latestResult['id_hasil_topsis']; ?>
                            </p>

                        </div>

                        <p
                            class="text-sm
                               text-zinc-500">
                            <?= formatDateTime(
                                $latestResult['tanggal_proses']
                            ); ?>
                        </p>

                    </div>

                </section>


                <?php
                $decision =
                    $calculation['decision_matrix'];

                $normalized =
                    $calculation['normalized_matrix'];

                $weighted =
                    $calculation['weighted_matrix'];

                $idealPositive =
                    $calculation['ideal_positive'];

                $idealNegative =
                    $calculation['ideal_negative'];

                $distances =
                    $calculation['distances'];

                $ranking =
                    $calculation['ranking'];
                ?>


                <!-- =================================================
                 1. MATRIKS KEPUTUSAN
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <h2 class="font-semibold">
                        1. Matriks Keputusan
                    </h2>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Data aktual masing-masing alternatif
                        terhadap seluruh kriteria pada
                        periode aktif.
                    </p>


                    <div class="mt-5 overflow-x-auto">

                        <table
                            class="w-full min-w-[850px]
                               border-collapse text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th
                                        class="px-3 py-3
                                           text-left">
                                        Alternatif
                                    </th>

                                    <?php foreach (
                                        $criteria
                                        as $criterion
                                    ): ?>

                                        <th
                                            class="px-3 py-3
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
                                    $alternatives
                                    as $i => $alternative
                                ): ?>

                                    <tr
                                        class="border-b
                                           border-zinc-200">

                                        <th
                                            class="px-3 py-3
                                               text-left">
                                            <?= e(
                                                $alternative['kode_alternatif']
                                            ); ?>

                                            <span
                                                class="block text-xs
                                                   font-normal
                                                   text-zinc-500">
                                                <?= e(
                                                    $alternative['nama_alternatif']
                                                ); ?>
                                            </span>
                                        </th>


                                        <?php foreach (
                                            $criteria
                                            as $j => $criterion
                                        ): ?>

                                            <td
                                                class="px-3 py-3
                                                   text-center">
                                                <?= rawNumber(
                                                    $decision[$i][$j]
                                                ); ?>
                                            </td>

                                        <?php endforeach; ?>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                 2. NORMALISASI
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <h2 class="font-semibold">
                        2. Normalisasi Matriks Keputusan
                    </h2>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Normalisasi vektor dilakukan dengan
                        membagi setiap nilai terhadap akar
                        jumlah kuadrat pada kolom kriterianya.
                    </p>


                    <div class="mt-5 overflow-x-auto">

                        <table
                            class="w-full min-w-[850px]
                               border-collapse text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th class="px-3 py-3 text-left">
                                        Alternatif
                                    </th>

                                    <?php foreach (
                                        $criteria
                                        as $criterion
                                    ): ?>

                                        <th
                                            class="px-3 py-3
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
                                    $alternatives
                                    as $i => $alternative
                                ): ?>

                                    <tr
                                        class="border-b
                                           border-zinc-200">

                                        <th
                                            class="px-3 py-3
                                               text-left">
                                            <?= e(
                                                $alternative['kode_alternatif']
                                            ); ?>

                                            (<?= e(
                                                    $alternative['nama_alternatif']
                                                ); ?>)
                                        </th>

                                        <?php foreach (
                                            $criteria
                                            as $j => $criterion
                                        ): ?>

                                            <td
                                                class="px-3 py-3
                                                   text-center
                                                   text-zinc-600">
                                                <?= decimal(
                                                    $normalized[$i][$j],
                                                    6
                                                ); ?>
                                            </td>

                                        <?php endforeach; ?>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                 3. NORMALISASI TERBOBOT
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <h2 class="font-semibold">
                        3. Matriks Normalisasi Terbobot
                    </h2>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Setiap nilai hasil normalisasi
                        dikalikan dengan bobot prioritas
                        kriteria hasil AHP.
                    </p>


                    <div class="mt-5 overflow-x-auto">

                        <table
                            class="w-full min-w-[850px]
                               border-collapse text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th class="px-3 py-3 text-left">
                                        Alternatif
                                    </th>

                                    <?php foreach (
                                        $criteria
                                        as $criterion
                                    ): ?>

                                        <?php
                                        $criteriaWeight = 0;

                                        foreach (
                                            $weights
                                            as $weight
                                        ) {
                                            if (
                                                (int) $weight['id_kriteria']
                                                ===
                                                (int) $criterion['id_kriteria']
                                            ) {
                                                $criteriaWeight =
                                                    $weight['bobot'];

                                                break;
                                            }
                                        }
                                        ?>

                                        <th
                                            class="px-3 py-3
                                               text-center">
                                            <?= e(
                                                $criterion['kode_kriteria']
                                            ); ?>

                                            <span
                                                class="block text-[11px]
                                                   font-normal
                                                   text-zinc-500">
                                                W =
                                                <?= decimal(
                                                    $criteriaWeight,
                                                    4
                                                ); ?>
                                            </span>
                                        </th>

                                    <?php endforeach; ?>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $alternatives
                                    as $i => $alternative
                                ): ?>

                                    <tr
                                        class="border-b
                                           border-zinc-200">

                                        <th
                                            class="px-3 py-3
                                               text-left">
                                            <?= e(
                                                $alternative['kode_alternatif']
                                            ); ?>

                                            (<?= e(
                                                    $alternative['nama_alternatif']
                                                ); ?>)
                                        </th>

                                        <?php foreach (
                                            $criteria
                                            as $j => $criterion
                                        ): ?>

                                            <td
                                                class="px-3 py-3
                                                   text-center
                                                   text-zinc-600">
                                                <?= decimal(
                                                    $weighted[$i][$j],
                                                    6
                                                ); ?>
                                            </td>

                                        <?php endforeach; ?>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                 4. IDEAL
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <h2 class="font-semibold">
                        4. Solusi Ideal Positif dan Negatif
                    </h2>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Solusi ideal ditentukan berdasarkan
                        jenis kriteria Benefit atau Cost.
                    </p>


                    <div class="mt-5 overflow-x-auto">

                        <table
                            class="w-full min-w-[750px]
                               border-collapse text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th
                                        class="px-3 py-3
                                           text-left">
                                        Solusi Ideal
                                    </th>

                                    <?php foreach (
                                        $criteria
                                        as $criterion
                                    ): ?>

                                        <th
                                            class="px-3 py-3
                                               text-center">
                                            <?= e(
                                                $criterion['kode_kriteria']
                                            ); ?>

                                            <span
                                                class="block text-[11px]
                                                   font-normal
                                                   text-zinc-500">
                                                <?= e(
                                                    $criterion['jenis_kriteria']
                                                ); ?>
                                            </span>
                                        </th>

                                    <?php endforeach; ?>

                                </tr>

                            </thead>


                            <tbody>

                                <tr
                                    class="border-b
                                       border-zinc-200">

                                    <th class="px-3 py-3 text-left">
                                        A+ (Ideal Positif)
                                    </th>

                                    <?php foreach (
                                        $idealPositive
                                        as $value
                                    ): ?>

                                        <td
                                            class="px-3 py-3
                                               text-center">
                                            <?= decimal(
                                                $value,
                                                6
                                            ); ?>
                                        </td>

                                    <?php endforeach; ?>

                                </tr>


                                <tr>

                                    <th class="px-3 py-3 text-left">
                                        A− (Ideal Negatif)
                                    </th>

                                    <?php foreach (
                                        $idealNegative
                                        as $value
                                    ): ?>

                                        <td
                                            class="px-3 py-3
                                               text-center">
                                            <?= decimal(
                                                $value,
                                                6
                                            ); ?>
                                        </td>

                                    <?php endforeach; ?>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                 5. DISTANCE
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <h2 class="font-semibold">
                        5. Jarak Alternatif terhadap
                        Solusi Ideal
                    </h2>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Menghitung jarak Euclidean terhadap
                        solusi ideal positif (D+) dan
                        solusi ideal negatif (D−).
                    </p>


                    <div class="mt-5 overflow-x-auto">

                        <table
                            class="w-full
                               border-collapse text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th class="px-3 py-3 text-left">
                                        Alternatif
                                    </th>

                                    <th class="px-3 py-3 text-right">
                                        Jarak Positif (D+)
                                    </th>

                                    <th class="px-3 py-3 text-right">
                                        Jarak Negatif (D−)
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $distances as $distance
                                ): ?>

                                    <tr
                                        class="border-b
                                           border-zinc-200">

                                        <td class="px-3 py-3">

                                            <strong>
                                                <?= e(
                                                    $distance['kode_alternatif']
                                                ); ?>
                                            </strong>

                                            <span
                                                class="ml-1
                                                   text-zinc-500">
                                                <?= e(
                                                    $distance['nama_alternatif']
                                                ); ?>
                                            </span>

                                        </td>

                                        <td
                                            class="px-3 py-3
                                               text-right">
                                            <?= decimal(
                                                $distance['jarak_positif'],
                                                6
                                            ); ?>
                                        </td>

                                        <td
                                            class="px-3 py-3
                                               text-right">
                                            <?= decimal(
                                                $distance['jarak_negatif'],
                                                6
                                            ); ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                 6. PREFERENCE
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <h2 class="font-semibold">
                        6. Nilai Preferensi
                    </h2>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Nilai preferensi dihitung menggunakan
                        Vi = D− / (D+ + D−). Semakin besar
                        nilai Vi, semakin baik alternatif.
                    </p>


                    <div class="mt-5 overflow-x-auto">

                        <table
                            class="w-full
                               border-collapse text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th class="px-3 py-3 text-left">
                                        Alternatif
                                    </th>

                                    <th class="px-3 py-3 text-right">
                                        D+
                                    </th>

                                    <th class="px-3 py-3 text-right">
                                        D−
                                    </th>

                                    <th class="px-3 py-3 text-right">
                                        Nilai Preferensi (Vi)
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $distances as $distance
                                ): ?>

                                    <tr
                                        class="border-b
                                           border-zinc-200">

                                        <td class="px-3 py-3">

                                            <strong>
                                                <?= e(
                                                    $distance['kode_alternatif']
                                                ); ?>
                                            </strong>

                                            <span
                                                class="ml-1
                                                   text-zinc-500">
                                                <?= e(
                                                    $distance['nama_alternatif']
                                                ); ?>
                                            </span>

                                        </td>

                                        <td
                                            class="px-3 py-3
                                               text-right">
                                            <?= decimal(
                                                $distance['jarak_positif'],
                                                6
                                            ); ?>
                                        </td>

                                        <td
                                            class="px-3 py-3
                                               text-right">
                                            <?= decimal(
                                                $distance['jarak_negatif'],
                                                6
                                            ); ?>
                                        </td>

                                        <td
                                            class="px-3 py-3
                                               text-right
                                               font-bold">
                                            <?= decimal(
                                                $distance['nilai_preferensi'],
                                                6
                                            ); ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                 7. RANKING
            ================================================== -->

                <section
                    class="mb-6 rounded-md
                       border border-zinc-300
                       bg-white p-5">

                    <h2 class="font-semibold">
                        7. Perangkingan
                    </h2>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Alternatif diurutkan dari nilai
                        preferensi tertinggi ke terendah.
                    </p>


                    <div class="mt-5 overflow-x-auto">

                        <table
                            class="w-full
                               border-collapse text-sm">

                            <thead class="bg-zinc-100">

                                <tr>

                                    <th
                                        class="w-28 px-3 py-3
                                           text-center">
                                        Peringkat
                                    </th>

                                    <th class="px-3 py-3 text-left">
                                        Kode
                                    </th>

                                    <th class="px-3 py-3 text-left">
                                        Nama Produk
                                    </th>

                                    <th class="px-3 py-3 text-right">
                                        Nilai Preferensi
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $ranking as $result
                                ): ?>

                                    <tr
                                        class="
                                        border-b
                                        border-zinc-200

                                        <?= (int)
                                        $result['peringkat'] === 1
                                            ? 'bg-zinc-50'
                                            : '';
                                        ?>
                                    ">

                                        <td
                                            class="px-3 py-3
                                               text-center">

                                            <span
                                                class="
                                                inline-flex
                                                h-8 w-8
                                                items-center
                                                justify-center
                                                rounded-md
                                                font-bold

                                                <?= (int)
                                                $result['peringkat'] === 1
                                                    ? 'bg-zinc-900 text-white'
                                                    : 'bg-zinc-100';
                                                ?>
                                            ">
                                                <?= (int)
                                                $result['peringkat']; ?>
                                            </span>

                                        </td>

                                        <td
                                            class="px-3 py-3
                                               font-semibold">
                                            <?= e(
                                                $result['kode_alternatif']
                                            ); ?>
                                        </td>

                                        <td
                                            class="px-3 py-3">
                                            <?= e(
                                                $result['nama_alternatif']
                                            ); ?>
                                        </td>

                                        <td
                                            class="px-3 py-3
                                               text-right
                                               font-bold">
                                            <?= decimal(
                                                $result['nilai_preferensi'],
                                                6
                                            ); ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                    <?php if (!empty($ranking)): ?>

                        <?php
                        $winner = $ranking[0];
                        ?>

                        <div
                            class="mt-5 rounded-md
                               border border-zinc-300
                               bg-zinc-50
                               px-5 py-4">

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Produk Unggulan
                            </p>

                            <p
                                class="mt-1 text-xl
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
                                class="mt-1 text-sm
                                   text-zinc-500">
                                Nilai preferensi tertinggi:

                                <strong
                                    class="text-zinc-900">
                                    <?= decimal(
                                        $winner['nilai_preferensi'],
                                        6
                                    ); ?>
                                </strong>
                            </p>

                        </div>

                    <?php endif; ?>

                </section>


                <!-- ACTION -->
                <div
                    class="flex flex-col
                       justify-end gap-3
                       sm:flex-row">

                    <a
                        href="hasil.php"
                        class="inline-flex
                           justify-center
                           rounded-md
                           bg-zinc-900
                           px-5 py-2.5
                           text-sm font-semibold
                           text-white
                           hover:bg-zinc-800">
                        Lihat Hasil Perangkingan →
                    </a>

                </div>

            <?php endif; ?>


            <!-- =================================================
             BELUM ADA HASIL
        ================================================== -->

            <?php if (
                $ready
                && $latestResult === null
            ): ?>

                <section
                    class="rounded-md
                       border border-dashed
                       border-zinc-300
                       bg-white p-10
                       text-center">

                    <h2 class="font-semibold">
                        TOPSIS siap dihitung
                    </h2>

                    <p
                        class="mt-2 text-sm
                           text-zinc-500">
                        Bobot AHP telah konsisten dan nilai
                        alternatif sudah lengkap. Klik
                        "Jalankan Perhitungan TOPSIS"
                        untuk menghasilkan tujuh tahap
                        perhitungan dan perangkingan.
                    </p>

                </section>

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
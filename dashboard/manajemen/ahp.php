<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';

require_once __DIR__
    . '/../../src/AhpComparisonService.php';

require_once __DIR__
    . '/../../src/AHPService.php';

Auth::requireRole('manajemen');

$user = Auth::user();

$pdo = Database::connect();

$comparisonService =
    new AhpComparisonService($pdo);

$ahpService =
    new AHPService($pdo);

$activePage = 'ahp';

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


/*
|--------------------------------------------------------------------------
| Skala Saaty
|--------------------------------------------------------------------------
|
| Key adalah nilai yang dikirim ke server.
| Value adalah label yang tampil.
|
*/

$saatyScale = [
    '0.111111' => '1/9',
    '0.125000' => '1/8',
    '0.142857' => '1/7',
    '0.166667' => '1/6',
    '0.200000' => '1/5',
    '0.250000' => '1/4',
    '0.333333' => '1/3',
    '0.500000' => '1/2',
    '1.000000' => '1',
    '2.000000' => '2',
    '3.000000' => '3',
    '4.000000' => '4',
    '5.000000' => '5',
    '6.000000' => '6',
    '7.000000' => '7',
    '8.000000' => '8',
    '9.000000' => '9',
];


function findSaatyKey(
    mixed $value,
    array $scale
): ?string {
    if (
        $value === null
        || $value === ''
    ) {
        return null;
    }

    $number = (float) $value;

    foreach ($scale as $key => $label) {
        if (
            abs(
                $number - (float) $key
            ) < 0.00001
        ) {
            return $key;
        }
    }

    return null;
}


function reciprocalLabel(
    mixed $value,
    array $scale
): string {
    if (
        $value === null
        || $value === ''
        || (float) $value <= 0
    ) {
        return '-';
    }

    $reciprocal =
        1 / (float) $value;

    $key = findSaatyKey(
        $reciprocal,
        $scale
    );

    return $key !== null
        ? $scale[$key]
        : number_format(
            $reciprocal,
            4,
            '.',
            ''
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
| Data utama
|--------------------------------------------------------------------------
*/

$periode =
    $comparisonService->getActivePeriod();

$criteria =
    $comparisonService->getCriteria();

$currentPairs = [];

$latestResultId = null;

if ($periode !== null) {
    $latest =
        $comparisonService
        ->getLatestComparisons(
            (int) $periode['id_periode'],
            (int) $user['id_user']
        );

    $latestResultId =
        $latest['id_hasil_ahp'];

    $currentPairs =
        $latest['pairs'];
}


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    $csrfToken =
        (string) (
            $_POST['_token'] ?? ''
        );

    if (!Auth::verifyCsrf($csrfToken)) {
        $error =
            'Permintaan tidak valid. Silakan coba kembali.';
    } elseif ($periode === null) {
        $error =
            'Tidak ada periode penilaian yang aktif.';
    } elseif (count($criteria) < 2) {
        $error =
            'Minimal diperlukan dua kriteria.';
    } else {
        $action =
            (string) (
                $_POST['action'] ?? ''
            );

        $submitted =
            $_POST['comparison'] ?? [];

        $comparisons = [];

        /*
        |--------------------------------------------------------------------------
        | Hanya membaca segitiga atas
        |--------------------------------------------------------------------------
        */

        $count = count($criteria);

        for ($i = 0; $i < $count; $i++) {
            for (
                $j = $i + 1;
                $j < $count;
                $j++
            ) {
                $id1 =
                    (int) $criteria[$i]['id_kriteria'];

                $id2 =
                    (int) $criteria[$j]['id_kriteria'];

                $raw =
                    $submitted[$id1][$id2]
                    ?? '';

                $raw = trim(
                    (string) $raw
                );

                $pairKey =
                    $id1 . ':' . $id2;

                /*
                 * Supaya input tetap muncul
                 * ketika validasi gagal.
                 */
                $currentPairs[$pairKey] =
                    $raw;

                if ($raw === '') {
                    $error =
                        'Seluruh pasangan perbandingan wajib diisi.';

                    break 2;
                }

                $validKey =
                    findSaatyKey(
                        $raw,
                        $saatyScale
                    );

                if ($validKey === null) {
                    $error =
                        'Terdapat nilai perbandingan yang tidak valid.';

                    break 2;
                }

                $comparisons[] = [
                    'id_kriteria_1' =>
                    $id1,

                    'id_kriteria_2' =>
                    $id2,

                    'nilai' =>
                    (float) $validKey,
                ];
            }
        }


        if ($error === null) {
            try {
                $idHasil =
                    $comparisonService
                    ->saveComparisons(
                        (int) $periode['id_periode'],
                        (int) $user['id_user'],
                        $comparisons
                    );

                if ($action === 'calculate') {
                    $result =
                        $ahpService
                        ->calculateAndSave(
                            $idHasil,
                            $criteria,
                            $comparisons
                        );

                    $_SESSION['success'] =
                        'Perhitungan AHP berhasil. '
                        . 'CR = '
                        . number_format(
                            $result['consistency_ratio'],
                            4,
                            '.',
                            ''
                        )
                        . ' ('
                        . $result['status']
                        . ').';
                } else {
                    $_SESSION['success'] =
                        'Perbandingan AHP berhasil disimpan.';
                }

                header(
                    'Location: ahp.php'
                );

                exit;
            } catch (Throwable $exception) {
                error_log(
                    $exception->getMessage()
                );

                $error =
                    'Terjadi kesalahan saat memproses perbandingan AHP.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Jumlah pasangan
|--------------------------------------------------------------------------
*/

$totalPairs =
    count($criteria) > 1
    ? (int) (
        count($criteria)
        * (count($criteria) - 1)
        / 2
    )
    : 0;

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
        Perbandingan AHP | SPK Motor
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
               flex h-16
               items-center justify-between
               border-b border-zinc-200
               bg-white px-4
               sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">

                <button
                    type="button"
                    id="sidebarButton"
                    class="flex h-9 w-9
                       items-center
                       justify-center
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
                        Perbandingan Berpasangan AHP
                    </span>

                </div>

            </div>


            <div class="flex items-center gap-3">

                <div
                    class="flex h-8 w-8
                       items-center
                       justify-center
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


        <main class="p-4 sm:p-6 lg:p-8">


            <!-- TITLE -->
            <div class="mb-6">

                <h1
                    class="text-2xl
                       font-bold
                       tracking-tight">
                    Matriks Perbandingan
                    Berpasangan Kriteria (AHP)
                </h1>

                <p
                    class="mt-1
                       text-sm
                       text-zinc-500">
                    Bandingkan tingkat kepentingan
                    antar-kriteria menggunakan
                    skala Saaty.
                </p>

            </div>


            <!-- SUCCESS -->
            <?php if ($success !== null): ?>

                <div
                    class="mb-5
                       rounded-md
                       border border-zinc-300
                       bg-white
                       px-4 py-3
                       text-sm">
                    <?= e($success); ?>
                </div>

            <?php endif; ?>


            <!-- ERROR -->
            <?php if ($error !== null): ?>

                <div
                    class="mb-5
                       flex gap-3
                       rounded-md
                       border border-zinc-400
                       bg-zinc-50
                       px-4 py-3
                       text-sm
                       text-zinc-700">
                    <strong>!</strong>

                    <span>
                        <?= e($error); ?>
                    </span>
                </div>

            <?php endif; ?>


            <!-- PERIODE -->
            <section
                class="mb-5
                   rounded-md
                   border border-zinc-300
                   bg-white
                   p-5">

                <p
                    class="text-xs
                       font-medium
                       uppercase
                       text-zinc-500">
                    Periode Penilaian
                </p>

                <?php if ($periode !== null): ?>

                    <div
                        class="mt-2
                           flex flex-wrap
                           items-center gap-3">

                        <span
                            class="text-lg
                               font-bold">
                            <?= e(
                                $periode['nama_periode']
                            ); ?>
                        </span>

                        <span
                            class="rounded-md
                               bg-zinc-900
                               px-2.5 py-1
                               text-xs
                               font-semibold
                               text-white">
                            Aktif
                        </span>

                    </div>

                <?php else: ?>

                    <p
                        class="mt-2
                           text-sm
                           text-zinc-500">
                        Belum terdapat periode aktif.
                    </p>

                <?php endif; ?>

            </section>


            <!-- SKALA -->
            <section
                class="mb-6
                   rounded-md
                   border border-zinc-300
                   bg-white
                   p-4">

                <h2 class="text-sm font-semibold">
                    Skala Kepentingan Saaty
                </h2>

                <p
                    class="mt-2
                       text-xs
                       leading-6
                       text-zinc-500">
                    1 = Sama Penting,
                    3 = Sedikit Lebih Penting,
                    5 = Lebih Penting,
                    7 = Sangat Penting,
                    9 = Mutlak Lebih Penting.
                    Nilai 2, 4, 6, dan 8 dapat
                    digunakan sebagai nilai antara.
                    Pecahan menunjukkan bahwa kriteria
                    kolom lebih penting daripada
                    kriteria baris.
                </p>

            </section>


            <?php if (
                $periode === null
                || count($criteria) < 2
            ): ?>

                <section
                    class="rounded-md
                       border border-zinc-300
                       bg-white
                       p-10 text-center">

                    <p class="font-semibold">
                        Perbandingan belum dapat dilakukan
                    </p>

                    <p
                        class="mt-2
                           text-sm
                           text-zinc-500">
                        Pastikan periode aktif dan
                        minimal dua kriteria sudah tersedia.
                    </p>

                </section>


            <?php else: ?>


                <form
                    action="ahp.php"
                    method="POST">

                    <input
                        type="hidden"
                        name="_token"
                        value="<?= e(
                                    Auth::csrfToken()
                                ); ?>">


                    <section
                        class="rounded-md
                           border border-zinc-300
                           bg-white
                           p-5">

                        <div
                            class="mb-4
                               flex flex-col gap-2
                               sm:flex-row
                               sm:items-center
                               sm:justify-between">

                            <div>

                                <h2 class="font-semibold">
                                    Matriks Perbandingan
                                </h2>

                                <p
                                    class="mt-1
                                       text-xs
                                       text-zinc-500">
                                    <?= $totalPairs; ?>
                                    pasangan utama perlu
                                    dinilai.
                                </p>

                            </div>

                            <span
                                class="text-xs
                                   text-zinc-500">
                                Nilai diagonal dan resiprokal
                                dihitung otomatis.
                            </span>

                        </div>


                        <div class="overflow-x-auto">

                            <table
                                class="w-full
                                   min-w-[850px]
                                   border-collapse
                                   text-sm">

                                <thead class="bg-zinc-100">

                                    <tr>

                                        <th
                                            class="min-w-48
                                               border-b
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
                                                class="min-w-40
                                                   border-b
                                                   border-zinc-300
                                                   px-3 py-3
                                                   text-center">

                                                <?= e(
                                                    $criterion['kode_kriteria']
                                                ); ?>

                                                <span
                                                    class="block
                                                       text-[11px]
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
                                        $criteria as $i => $row
                                    ): ?>

                                        <tr
                                            class="border-b
                                           border-zinc-200
                                           last:border-b-0">

                                            <th
                                                class="px-3 py-3
                                               text-left">
                                                <?= e(
                                                    $row['kode_kriteria']
                                                ); ?>

                                                <span
                                                    class="block
                                                   text-xs
                                                   font-normal
                                                   text-zinc-500">
                                                    <?= e(
                                                        $row['nama_kriteria']
                                                    ); ?>
                                                </span>
                                            </th>


                                            <?php foreach (
                                                $criteria
                                                as $j => $column
                                            ): ?>

                                                <?php
                                                $rowId =
                                                    (int)
                                                    $row['id_kriteria'];

                                                $columnId =
                                                    (int)
                                                    $column['id_kriteria'];
                                                ?>


                                                <!-- DIAGONAL -->
                                                <?php if ($i === $j): ?>

                                                    <td
                                                        class="bg-zinc-100
                                                       px-3 py-3
                                                       text-center
                                                       font-bold">
                                                        1
                                                    </td>


                                                    <!-- UPPER TRIANGLE -->
                                                <?php elseif ($i < $j): ?>

                                                    <?php
                                                    $key =
                                                        $rowId
                                                        . ':'
                                                        . $columnId;

                                                    $current =
                                                        $currentPairs[$key]
                                                        ?? null;

                                                    $selectedKey =
                                                        findSaatyKey(
                                                            $current,
                                                            $saatyScale
                                                        );

                                                    $selectId =
                                                        'pair-'
                                                        . $rowId
                                                        . '-'
                                                        . $columnId;
                                                    ?>

                                                    <td class="px-3 py-3">

                                                        <select
                                                            id="<?= e($selectId); ?>"
                                                            name="comparison[<?= $rowId; ?>][<?= $columnId; ?>]"
                                                            data-row="<?= $rowId; ?>"
                                                            data-column="<?= $columnId; ?>"
                                                            class="ahp-input
                                                           w-full
                                                           rounded-md
                                                           border
                                                           border-zinc-400
                                                           bg-white
                                                           px-3 py-2
                                                           text-center
                                                           outline-none
                                                           focus:border-zinc-900
                                                           focus:ring-1
                                                           focus:ring-zinc-900"
                                                            required>

                                                            <option value="">
                                                                Pilih
                                                            </option>

                                                            <?php foreach (
                                                                $saatyScale
                                                                as $value => $label
                                                            ): ?>

                                                                <option
                                                                    value="<?= e($value); ?>"
                                                                    <?= $selectedKey === $value
                                                                        ? 'selected'
                                                                        : '';
                                                                    ?>>
                                                                    <?= e($label); ?>
                                                                </option>

                                                            <?php endforeach; ?>

                                                        </select>

                                                    </td>


                                                    <!-- LOWER TRIANGLE -->
                                                <?php else: ?>

                                                    <?php
                                                    $upperKey =
                                                        $columnId
                                                        . ':'
                                                        . $rowId;

                                                    $upperValue =
                                                        $currentPairs[$upperKey]
                                                        ?? null;
                                                    ?>

                                                    <td
                                                        class="bg-zinc-50
                                                       px-3 py-3
                                                       text-center
                                                       text-zinc-500">

                                                        <span
                                                            id="reciprocal-<?= $columnId; ?>-<?= $rowId; ?>">
                                                            <?= e(
                                                                reciprocalLabel(
                                                                    $upperValue,
                                                                    $saatyScale
                                                                )
                                                            ); ?>
                                                        </span>

                                                    </td>

                                                <?php endif; ?>

                                            <?php endforeach; ?>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>


                        <!-- INFORMATION -->
                        <div
                            class="mt-5
                               rounded-md
                               bg-zinc-50
                               px-4 py-3
                               text-xs
                               leading-5
                               text-zinc-500">
                            Contoh: apabila Volume Penjualan
                            dibanding Permintaan Pasar bernilai
                            <strong>3</strong>, maka sistem
                            otomatis membentuk nilai kebalikannya
                            menjadi <strong>1/3</strong>.
                        </div>


                        <!-- BUTTONS -->
                        <div
                            class="mt-5
                               flex flex-col
                               justify-end gap-3
                               border-t
                               border-zinc-200
                               pt-5
                               sm:flex-row">

                            <button
                                type="submit"
                                name="action"
                                value="save"
                                class="rounded-md
                                   border border-zinc-400
                                   bg-white
                                   px-5 py-2.5
                                   text-sm
                                   font-semibold
                                   transition
                                   hover:bg-zinc-100">
                                Simpan Perbandingan
                            </button>


                            <button
                                type="submit"
                                name="action"
                                value="calculate"
                                class="rounded-md
                                   bg-zinc-900
                                   px-5 py-2.5
                                   text-sm
                                   font-semibold
                                   text-white
                                   transition
                                   hover:bg-zinc-800">
                                Hitung Bobot AHP
                            </button>

                        </div>

                    </section>

                </form>

            <?php endif; ?>

        </main>

    </div>


    <script>
        /*
    |--------------------------------------------------------------------------
    | Sidebar mobile
    |--------------------------------------------------------------------------
    */

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


        /*
        |--------------------------------------------------------------------------
        | Reciprocal AHP
        |--------------------------------------------------------------------------
        */

        const reciprocalLabels = {
            '0.111111': '9',
            '0.125000': '8',
            '0.142857': '7',
            '0.166667': '6',
            '0.200000': '5',
            '0.250000': '4',
            '0.333333': '3',
            '0.500000': '2',

            '1.000000': '1',

            '2.000000': '1/2',
            '3.000000': '1/3',
            '4.000000': '1/4',
            '5.000000': '1/5',
            '6.000000': '1/6',
            '7.000000': '1/7',
            '8.000000': '1/8',
            '9.000000': '1/9'
        };


        document
            .querySelectorAll('.ahp-input')
            .forEach((select) => {

                select.addEventListener(
                    'change',
                    function() {

                        const row =
                            this.dataset.row;

                        const column =
                            this.dataset.column;

                        const target =
                            document.getElementById(
                                `reciprocal-${row}-${column}`
                            );

                        if (!target) {
                            return;
                        }

                        target.textContent =
                            reciprocalLabels[
                                this.value
                            ] ?? '-';
                    }
                );

            });
    </script>


</body>

</html>
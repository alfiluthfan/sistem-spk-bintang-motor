<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__
    . '/../../src/NilaiAlternatifService.php';

Auth::requireRole('staf');

$user = Auth::user();

$nilaiService = new NilaiAlternatifService(
    Database::connect()
);

$activePage = 'nilai';

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

function formatInputNilai(
    mixed $value
): string {
    if ($value === null || $value === '') {
        return '';
    }

    $value = (string) $value;

    if (str_contains($value, '.')) {
        $value = rtrim(
            rtrim($value, '0'),
            '.'
        );
    }

    return $value;
}


/*
|--------------------------------------------------------------------------
| Flash message
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['success'])) {
    $success = (string) $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = (string) $_SESSION['error'];
    unset($_SESSION['error']);
}


/*
|--------------------------------------------------------------------------
| Master Data
|--------------------------------------------------------------------------
*/

$periodeList =
    $nilaiService->getPeriodeList();

$activePeriode =
    $nilaiService->getActivePeriode();

$kriteria =
    $nilaiService->getKriteria();

$alternatif =
    $nilaiService->getAlternatif();


/*
|--------------------------------------------------------------------------
| Menentukan periode terpilih
|--------------------------------------------------------------------------
*/

$requestedPeriode =
    $_POST['id_periode']
    ?? $_GET['periode']
    ?? null;

$idPeriode = null;

if ($requestedPeriode !== null) {

    $validated = filter_var(
        $requestedPeriode,
        FILTER_VALIDATE_INT
    );

    if (
        $validated !== false
        && $validated > 0
    ) {
        $idPeriode = (int) $validated;
    }
}


/*
|--------------------------------------------------------------------------
| Default ke periode aktif
|--------------------------------------------------------------------------
*/

if ($idPeriode === null) {

    if ($activePeriode !== null) {

        $idPeriode =
            (int) $activePeriode['id_periode'];
    } elseif (!empty($periodeList)) {

        $idPeriode =
            (int) $periodeList[0]['id_periode'];
    }
}


/*
|--------------------------------------------------------------------------
| Ambil detail periode
|--------------------------------------------------------------------------
*/

$selectedPeriode = null;

if ($idPeriode !== null) {

    $selectedPeriode =
        $nilaiService->findPeriodeById(
            $idPeriode
        );

    if ($selectedPeriode === null) {
        $error =
            'Periode penilaian tidak ditemukan.';
    }
}


/*
|--------------------------------------------------------------------------
| Nilai yang sudah tersimpan
|--------------------------------------------------------------------------
*/

$matrixNilai = [];

if ($selectedPeriode !== null) {

    $matrixNilai =
        $nilaiService->getNilaiByPeriode(
            (int) $selectedPeriode['id_periode']
        );
}


/*
|--------------------------------------------------------------------------
| POST - Simpan Nilai
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'save'
) {

    $csrfToken = (string) (
        $_POST['_token'] ?? ''
    );

    if (!Auth::verifyCsrf($csrfToken)) {

        $error =
            'Permintaan tidak valid. Silakan coba kembali.';
    } elseif ($selectedPeriode === null) {

        $error =
            'Periode penilaian tidak valid.';
    } elseif (
        strtolower(
            trim(
                (string) $selectedPeriode['status']
            )
        ) !== 'aktif'
    ) {

        $error =
            'Nilai hanya dapat diubah pada periode yang berstatus Aktif.';
    } elseif (
        empty($alternatif)
        || empty($kriteria)
    ) {

        $error =
            'Data alternatif atau kriteria belum tersedia.';
    } else {

        $submittedMatrix =
            $_POST['nilai'] ?? [];

        $validatedMatrix = [];

        foreach (
            $alternatif as $itemAlternatif
        ) {

            $idAlternatif =
                (int) $itemAlternatif['id_alternatif'];

            foreach (
                $kriteria as $itemKriteria
            ) {

                $idKriteria =
                    (int) $itemKriteria['id_kriteria'];

                $rawValue =
                    $submittedMatrix[$idAlternatif][$idKriteria]
                    ?? '';

                $rawValue = trim(
                    (string) $rawValue
                );


                /*
                |--------------------------------------------------------------------------
                | Simpan input ke matrix agar
                | tetap muncul jika validasi gagal
                |--------------------------------------------------------------------------
                */

                $matrixNilai[$idAlternatif][$idKriteria] = $rawValue;


                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                */

                if ($rawValue === '') {

                    $error =
                        'Seluruh nilai alternatif wajib diisi.';

                    break 2;
                }

                if (!is_numeric($rawValue)) {

                    $error =
                        'Nilai alternatif harus berupa angka.';

                    break 2;
                }

                $numericValue =
                    (float) $rawValue;

                if ($numericValue < 0) {

                    $error =
                        'Nilai alternatif tidak boleh negatif.';

                    break 2;
                }

                $validatedMatrix[$idAlternatif][$idKriteria] =
                    number_format(
                        $numericValue,
                        2,
                        '.',
                        ''
                    );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        if ($error === null) {

            try {

                $nilaiService->saveMatrix(
                    (int) $selectedPeriode['id_periode'],
                    $validatedMatrix
                );

                $_SESSION['success'] =
                    'Nilai alternatif berhasil disimpan.';

                header(
                    'Location: nilai.php?periode='
                        . (int) $selectedPeriode['id_periode']
                );

                exit;
            } catch (Throwable $exception) {

                error_log(
                    $exception->getMessage()
                );

                $error =
                    'Terjadi kesalahan saat menyimpan nilai alternatif.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Status kelengkapan
|--------------------------------------------------------------------------
*/

$totalExpected =
    count($alternatif)
    * count($kriteria);

$totalFilled = 0;

if ($selectedPeriode !== null) {

    $totalFilled =
        $nilaiService->countNilaiTerisi(
            (int) $selectedPeriode['id_periode']
        );
}

$isComplete =
    $totalExpected > 0
    && $totalFilled >= $totalExpected;

$progressPercentage =
    $totalExpected > 0
    ? min(
        100,
        ($totalFilled / $totalExpected)
            * 100
    )
    : 0;

$isEditable =
    $selectedPeriode !== null
    && strtolower(
        trim(
            (string) $selectedPeriode['status']
        )
    ) === 'aktif';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Nilai Alternatif | SPK Motor
    </title>

    <link
        rel="stylesheet"
        href="../../assets/css/app.css">

</head>


<body class="bg-zinc-100 text-zinc-900">


    <?php
    require __DIR__
        . '/../../partials/staf-sidebar.php';
    ?>


    <div class="min-h-screen lg:ml-60">


        <!-- =====================================================
         TOPBAR
    ====================================================== -->

        <header
            class="sticky top-0 z-30 flex h-16
               items-center justify-between
               border-b border-zinc-200
               bg-white px-4
               sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">

                <button
                    type="button"
                    id="sidebarButton"
                    class="flex h-9 w-9 items-center
                       justify-center rounded-md
                       border border-zinc-300
                       lg:hidden">
                    ☰
                </button>

                <div
                    class="hidden items-center gap-2
                       text-sm sm:flex">

                    <span class="text-zinc-500">
                        Dealer Bintang Motor Cinere
                    </span>

                    <span class="text-zinc-400">
                        /
                    </span>

                    <span class="font-semibold">
                        Nilai Alternatif
                    </span>

                </div>

                <span
                    class="font-semibold sm:hidden">
                    Nilai Alternatif
                </span>

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
                        Staf
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


            <!-- PAGE TITLE -->
            <div class="mb-6">

                <h1
                    class="text-2xl font-bold
                       tracking-tight">
                    Input Nilai Alternatif
                    terhadap Kriteria
                </h1>

                <p
                    class="mt-1 text-sm text-zinc-500">
                    Masukkan data aktual setiap produk
                    terhadap masing-masing kriteria
                    berdasarkan periode penilaian.
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
                       rounded-md
                       border border-zinc-400
                       bg-zinc-50
                       px-4 py-3
                       text-sm text-zinc-700">

                    <span class="font-bold">
                        !
                    </span>

                    <span>
                        <?= e($error); ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- =================================================
             PERIODE
        ================================================== -->

            <section
                class="mb-6 rounded-md
                   border border-zinc-300
                   bg-white p-5">

                <div
                    class="flex flex-col gap-4
                       lg:flex-row
                       lg:items-end
                       lg:justify-between">

                    <div class="w-full lg:max-w-md">

                        <label
                            for="periode"
                            class="mb-1.5 block
                               text-sm font-semibold">
                            Periode Penilaian
                        </label>

                        <?php if (
                            empty($periodeList)
                        ): ?>

                            <div
                                class="rounded-md
                                   border border-zinc-300
                                   bg-zinc-50
                                   px-3 py-2.5
                                   text-sm text-zinc-500">
                                Belum ada periode penilaian.
                            </div>

                        <?php else: ?>

                            <form
                                action="nilai.php"
                                method="GET">

                                <select
                                    id="periode"
                                    name="periode"
                                    onchange="this.form.submit()"
                                    class="w-full rounded-md
                                       border border-zinc-400
                                       bg-white px-3
                                       py-2.5 text-sm
                                       outline-none
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                                    <?php foreach (
                                        $periodeList
                                        as $periode
                                    ): ?>

                                        <option
                                            value="<?= (int) $periode['id_periode']; ?>"
                                            <?=
                                            $selectedPeriode !== null
                                                && (int) $selectedPeriode['id_periode']
                                                === (int) $periode['id_periode']
                                                ? 'selected'
                                                : '';
                                            ?>>
                                            <?= e(
                                                $periode['nama_periode']
                                            ); ?>

                                            —
                                            <?= e(
                                                $periode['status']
                                            ); ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </form>

                        <?php endif; ?>

                    </div>


                    <?php if (
                        $selectedPeriode !== null
                    ): ?>

                        <div
                            class="flex flex-wrap
                               items-center gap-3">

                            <div>

                                <p
                                    class="text-xs
                                       text-zinc-500">
                                    Rentang Penilaian
                                </p>

                                <p
                                    class="mt-1 text-sm
                                       font-medium">
                                    <?= formatTanggal(
                                        $selectedPeriode['tanggal_mulai']
                                    ); ?>

                                    -

                                    <?= formatTanggal(
                                        $selectedPeriode['tanggal_selesai']
                                    ); ?>
                                </p>

                            </div>


                            <?php if ($isEditable): ?>

                                <span
                                    class="rounded-md
                                       bg-zinc-900
                                       px-3 py-1.5
                                       text-xs
                                       font-semibold
                                       text-white">
                                    Aktif
                                </span>

                            <?php else: ?>

                                <span
                                    class="rounded-md
                                       border
                                       border-zinc-300
                                       bg-zinc-50
                                       px-3 py-1.5
                                       text-xs
                                       font-medium
                                       text-zinc-600">
                                    Selesai
                                </span>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <!-- =================================================
             EMPTY MASTER DATA
        ================================================== -->

            <?php if (
                empty($alternatif)
                || empty($kriteria)
                || $selectedPeriode === null
            ): ?>

                <section
                    class="rounded-md
                       border border-zinc-300
                       bg-white p-10
                       text-center">

                    <h2 class="font-semibold">
                        Data belum dapat diinput
                    </h2>

                    <p
                        class="mt-2 text-sm
                           text-zinc-500">
                        Pastikan periode penilaian,
                        data alternatif, dan data
                        kriteria sudah tersedia.
                    </p>

                </section>


            <?php else: ?>


                <!-- =================================================
                 MATRIX FORM
            ================================================== -->

                <form
                    action="nilai.php?periode=<?= (int) $selectedPeriode['id_periode']; ?>"
                    method="POST">

                    <input
                        type="hidden"
                        name="_token"
                        value="<?= e(
                                    Auth::csrfToken()
                                ); ?>">

                    <input
                        type="hidden"
                        name="action"
                        value="save">

                    <input
                        type="hidden"
                        name="id_periode"
                        value="<?= (int) $selectedPeriode['id_periode']; ?>">


                    <section
                        class="rounded-md
                           border border-zinc-300
                           bg-white p-5">

                        <!-- Historical notice -->
                        <?php if (!$isEditable): ?>

                            <div
                                class="mb-5
                                   rounded-md
                                   border
                                   border-zinc-300
                                   bg-zinc-50
                                   px-4 py-3
                                   text-sm
                                   text-zinc-600">
                                Periode ini telah selesai.
                                Data ditampilkan sebagai
                                riwayat dan tidak dapat diubah.
                            </div>

                        <?php endif; ?>


                        <!-- TABLE -->
                        <div class="overflow-x-auto">

                            <table
                                class="w-full
                                   min-w-[950px]
                                   border-collapse
                                   text-sm">

                                <thead
                                    class="bg-zinc-100">

                                    <tr>

                                        <th
                                            class="min-w-48
                                               border-b
                                               border-zinc-300
                                               px-3 py-3
                                               text-left">
                                            Alternatif /
                                            Kriteria
                                        </th>


                                        <?php foreach (
                                            $kriteria
                                            as $itemKriteria
                                        ): ?>

                                            <th
                                                class="min-w-44
                                                   border-b
                                                   border-zinc-300
                                                   px-3 py-3
                                                   text-left">

                                                <p>
                                                    <?= e(
                                                        $itemKriteria['nama_kriteria']
                                                    ); ?>
                                                </p>

                                                <p
                                                    class="mt-0.5
                                                       text-[11px]
                                                       font-normal
                                                       text-zinc-500">
                                                    <?= e(
                                                        $itemKriteria['kode_kriteria']
                                                    ); ?>

                                                    •

                                                    <?= e(
                                                        $itemKriteria['jenis_kriteria']
                                                    ); ?>
                                                </p>

                                            </th>

                                        <?php endforeach; ?>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $alternatif
                                        as $itemAlternatif
                                    ): ?>

                                        <?php
                                        $idAlternatif =
                                            (int)
                                            $itemAlternatif['id_alternatif'];
                                        ?>

                                        <tr
                                            class="border-b
                                               border-zinc-200
                                               last:border-b-0">

                                            <!-- Alternative -->
                                            <td
                                                class="px-3 py-3">

                                                <p
                                                    class="font-semibold">
                                                    <?= e(
                                                        $itemAlternatif['kode_alternatif']
                                                    ); ?>
                                                </p>

                                                <p
                                                    class="mt-0.5
                                                       text-xs
                                                       text-zinc-500">
                                                    <?= e(
                                                        $itemAlternatif['nama_alternatif']
                                                    ); ?>
                                                </p>

                                            </td>


                                            <!-- Criteria inputs -->
                                            <?php foreach (
                                                $kriteria
                                                as $itemKriteria
                                            ): ?>

                                                <?php
                                                $idKriteria =
                                                    (int)
                                                    $itemKriteria['id_kriteria'];

                                                $currentValue =
                                                    $matrixNilai[$idAlternatif][$idKriteria]
                                                    ?? '';
                                                ?>

                                                <td
                                                    class="px-3 py-3">

                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        inputmode="decimal"
                                                        name="nilai[<?= $idAlternatif; ?>][<?= $idKriteria; ?>]"
                                                        value="<?= e(
                                                                    formatInputNilai(
                                                                        $currentValue
                                                                    )
                                                                ); ?>"
                                                        <?= $isEditable
                                                            ? 'required'
                                                            : 'disabled';
                                                        ?>
                                                        class="
                                                        w-full
                                                        rounded-md
                                                        border
                                                        border-zinc-400
                                                        px-3 py-2
                                                        text-sm
                                                        outline-none

                                                        <?= $isEditable
                                                            ? 'bg-white focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900'
                                                            : 'cursor-not-allowed bg-zinc-100 text-zinc-500'
                                                        ?>
                                                    ">

                                                </td>

                                            <?php endforeach; ?>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>


                        <!-- =====================================
                         STATUS
                    ====================================== -->

                        <div
                            class="mt-6
                               border-t
                               border-zinc-200
                               pt-5">

                            <div
                                class="flex flex-col
                                   gap-3
                                   sm:flex-row
                                   sm:items-center
                                   sm:justify-between">

                                <div>

                                    <p
                                        class="text-sm
                                           font-semibold">

                                        Status:

                                        <?= $totalFilled; ?>

                                        /

                                        <?= $totalExpected; ?>

                                        nilai terisi

                                        <?php if (
                                            $isComplete
                                        ): ?>

                                            (Lengkap)

                                        <?php else: ?>

                                            (Belum Lengkap)

                                        <?php endif; ?>

                                    </p>

                                    <p
                                        class="mt-1
                                           text-xs
                                           text-zinc-500">
                                        <?= count(
                                            $alternatif
                                        ); ?>
                                        alternatif ×
                                        <?= count(
                                            $kriteria
                                        ); ?>
                                        kriteria
                                    </p>

                                </div>


                                <!-- Progress -->
                                <div
                                    class="w-full
                                       sm:max-w-xs">

                                    <div
                                        class="h-2
                                           overflow-hidden
                                           rounded-full
                                           bg-zinc-200">

                                        <div
                                            class="h-full
                                               rounded-full
                                               bg-zinc-900
                                               transition-all"
                                            style="width: <?= round(
                                                                $progressPercentage,
                                                                2
                                                            ); ?>%">
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =====================================
                         BUTTON
                    ====================================== -->

                        <?php if ($isEditable): ?>

                            <div
                                class="mt-5
                                   flex
                                   justify-end
                                   gap-3
                                   border-t
                                   border-zinc-200
                                   pt-5">

                                <button
                                    type="reset"
                                    class="rounded-md
                                       border
                                       border-zinc-400
                                       px-5 py-2.5
                                       text-sm
                                       font-medium
                                       transition
                                       hover:bg-zinc-100">
                                    Reset
                                </button>

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
                                    Simpan Nilai
                                </button>

                            </div>

                        <?php endif; ?>

                    </section>

                </form>

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
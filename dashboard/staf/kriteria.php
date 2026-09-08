<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/KriteriaService.php';

Auth::requireRole('staf');

$user = Auth::user();

$kriteriaService = new KriteriaService(
    Database::connect()
);

$activePage = 'kriteria';

$error = null;
$success = null;

$formMode = 'create';

$formData = [
    'id_kriteria' => '',
    'kode_kriteria' => '',
    'nama_kriteria' => '',
    'jenis_kriteria' => 'Benefit',
];


/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}


/*
|--------------------------------------------------------------------------
| Edit Mode
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['edit'])
) {
    $id = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT
    );

    if ($id !== false) {
        $kriteria = $kriteriaService->findById($id);

        if ($kriteria !== null) {
            $formMode = 'edit';
            $formData = $kriteria;
        } else {
            $error = 'Data kriteria tidak ditemukan.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| POST Process
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = (string) ($_POST['_token'] ?? '');

    if (!Auth::verifyCsrf($csrfToken)) {
        $error = 'Permintaan tidak valid. Silakan coba kembali.';
    } else {

        $action = (string) ($_POST['action'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | Create / Update
        |--------------------------------------------------------------------------
        */

        if (
            $action === 'create'
            || $action === 'update'
        ) {
            $id = isset($_POST['id_kriteria'])
                ? (int) $_POST['id_kriteria']
                : null;

            $kode = strtoupper(
                trim((string) ($_POST['kode_kriteria'] ?? ''))
            );

            $nama = trim(
                (string) ($_POST['nama_kriteria'] ?? '')
            );

            $jenis = trim(
                (string) ($_POST['jenis_kriteria'] ?? '')
            );

            $formData = [
                'id_kriteria' => $id ?? '',
                'kode_kriteria' => $kode,
                'nama_kriteria' => $nama,
                'jenis_kriteria' => $jenis,
            ];

            $formMode =
                $action === 'update'
                ? 'edit'
                : 'create';


            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            if (
                $kode === ''
                || $nama === ''
                || $jenis === ''
            ) {
                $error = 'Semua data kriteria wajib diisi.';
            } elseif (
                strlen($kode) > 10
            ) {
                $error = 'Kode kriteria maksimal 10 karakter.';
            } elseif (
                strlen($nama) > 100
            ) {
                $error = 'Nama kriteria maksimal 100 karakter.';
            } elseif (
                !in_array(
                    $jenis,
                    ['Benefit', 'Cost'],
                    true
                )
            ) {
                $error = 'Jenis kriteria tidak valid.';
            } elseif (
                $kriteriaService->kodeExists(
                    $kode,
                    $action === 'update'
                        ? $id
                        : null
                )
            ) {
                $error = 'Kode kriteria sudah digunakan.';
            } else {

                try {

                    if ($action === 'create') {

                        $kriteriaService->create(
                            $kode,
                            $nama,
                            $jenis
                        );

                        $_SESSION['success'] =
                            'Kriteria berhasil ditambahkan.';
                    } else {

                        if ($id === null || $id <= 0) {
                            throw new RuntimeException(
                                'ID kriteria tidak valid.'
                            );
                        }

                        $kriteriaService->update(
                            $id,
                            $kode,
                            $nama,
                            $jenis
                        );

                        $_SESSION['success'] =
                            'Kriteria berhasil diperbarui.';
                    }

                    header(
                        'Location: kriteria.php'
                    );

                    exit;
                } catch (Throwable $exception) {

                    error_log(
                        $exception->getMessage()
                    );

                    $error =
                        'Terjadi kesalahan saat menyimpan data.';
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        if ($action === 'delete') {

            $id = filter_var(
                $_POST['id_kriteria'] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id === null) {

                $_SESSION['error'] =
                    'ID kriteria tidak valid.';
            } else {

                try {

                    $deleted =
                        $kriteriaService->delete($id);

                    if ($deleted) {
                        $_SESSION['success'] =
                            'Kriteria berhasil dihapus.';
                    } else {
                        $_SESSION['error'] =
                            'Kriteria tidak dapat dihapus karena sudah digunakan dalam proses penilaian.';
                    }
                } catch (Throwable $exception) {

                    error_log(
                        $exception->getMessage()
                    );

                    $_SESSION['error'] =
                        'Terjadi kesalahan saat menghapus data.';
                }
            }

            header(
                'Location: kriteria.php'
            );

            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Get Data
|--------------------------------------------------------------------------
*/

$daftarKriteria = $kriteriaService->getAll();


function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
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
        . '/../../partials/staf-sidebar.php';
    ?>


    <div class="min-h-screen lg:ml-60">


        <!-- =========================================================
         TOPBAR
    ========================================================== -->

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
                        Data Kriteria
                    </span>
                </div>

                <span
                    class="font-semibold sm:hidden">
                    Data Kriteria
                </span>

            </div>


            <div class="flex items-center gap-3">

                <div
                    class="flex h-8 w-8 items-center
                       justify-center rounded-full
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


        <!-- =========================================================
         CONTENT
    ========================================================== -->

        <main class="p-4 sm:p-6 lg:p-8">


            <!-- Title -->
            <div
                class="mb-6 flex flex-col gap-4
                   sm:flex-row sm:items-center
                   sm:justify-between">

                <div>

                    <h1
                        class="text-2xl font-bold
                           tracking-tight">
                        Kelola Data Kriteria
                    </h1>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Kelola kriteria yang digunakan
                        dalam proses penilaian produk.
                    </p>

                </div>


                <a
                    href="kriteria.php?form=create#form-kriteria"
                    class="inline-flex items-center
                       justify-center rounded-md
                       bg-zinc-900 px-4 py-2.5
                       text-sm font-semibold
                       text-white
                       transition hover:bg-zinc-800">
                    + Tambah Kriteria
                </a>

            </div>


            <!-- Success Alert -->
            <?php if ($success !== null): ?>

                <div
                    class="mb-5 rounded-md border
                       border-zinc-300 bg-white
                       px-4 py-3 text-sm">
                    <?= e($success); ?>
                </div>

            <?php endif; ?>


            <!-- Error Alert -->
            <?php if ($error !== null): ?>

                <div
                    class="mb-5 flex gap-3 rounded-md
                       border border-zinc-400
                       bg-zinc-50 px-4 py-3
                       text-sm text-zinc-700">

                    <span class="font-bold">
                        !
                    </span>

                    <span>
                        <?= e($error); ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- =====================================================
             TABLE
        ====================================================== -->

            <section
                class="rounded-md border
                   border-zinc-300
                   bg-white p-5">

                <div class="overflow-x-auto">

                    <table
                        class="w-full min-w-[650px]
                           border-collapse text-sm">

                        <thead
                            class="bg-zinc-100">

                            <tr>

                                <th
                                    class="w-16 border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    No
                                </th>

                                <th
                                    class="w-28 border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    Kode
                                </th>

                                <th
                                    class="border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    Nama Kriteria
                                </th>

                                <th
                                    class="w-40 border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    Jenis
                                </th>

                                <th
                                    class="w-44 border-b
                                       border-zinc-300
                                       px-3 py-3 text-right">
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (
                                empty($daftarKriteria)
                            ): ?>

                                <tr>

                                    <td
                                        colspan="5"
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
                                               px-2 py-1
                                               text-xs">
                                                <?= e(
                                                    $kriteria['jenis_kriteria']
                                                ); ?>
                                            </span>

                                        </td>


                                        <td class="px-3 py-3">

                                            <div
                                                class="flex
                                               justify-end
                                               gap-2">

                                                <!-- EDIT -->
                                                <a
                                                    href="kriteria.php?edit=<?= (int) $kriteria['id_kriteria']; ?>#form-kriteria"
                                                    class="rounded-md border border-zinc-300
           px-3 py-1.5 text-xs font-medium
           transition hover:bg-zinc-100">
                                                    Edit
                                                </a>


                                                <!-- DELETE -->
                                                <form
                                                    action="kriteria.php"
                                                    method="POST"
                                                    onsubmit="
                                                return confirm(
                                                    'Yakin ingin menghapus kriteria ini?'
                                                );
                                            ">

                                                    <input
                                                        type="hidden"
                                                        name="_token"
                                                        value="<?=
                                                                e(
                                                                    Auth::csrfToken()
                                                                );
                                                                ?>">

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="delete">

                                                    <input
                                                        type="hidden"
                                                        name="id_kriteria"
                                                        value="<?=
                                                                (int)
                                                                $kriteria['id_kriteria'];
                                                                ?>">

                                                    <button
                                                        type="submit"
                                                        class="rounded-md
                                                       border
                                                       border-zinc-300
                                                       px-3 py-1.5
                                                       text-xs
                                                       font-medium
                                                       transition
                                                       hover:bg-zinc-100">
                                                        Hapus
                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>


            <!-- =====================================================
             FORM
        ====================================================== -->

            <?php if (
                isset($_GET['form'])
                || $formMode === 'edit'
                || $error !== null
            ): ?>

                <section
                    id="form-kriteria"
                    class="mt-6 rounded-md border
                       border-dashed
                       border-zinc-400
                       bg-white p-6">

                    <div
                        class="border-b
                           border-zinc-200 pb-2">

                        <h2
                            class="font-semibold">
                            <?= $formMode === 'edit'
                                ? 'Edit Kriteria'
                                : 'Tambah Kriteria';
                            ?>
                        </h2>

                        <p
                            class="mt-1 text-xs
                               text-zinc-500">
                            Bobot kriteria tidak diinput
                            pada halaman ini karena diperoleh
                            melalui proses AHP oleh Manajemen.
                        </p>

                    </div>


                    <form
                        action="kriteria.php"
                        method="POST"
                        class="mt-5">

                        <input
                            type="hidden"
                            name="_token"
                            value="<?= e(
                                        Auth::csrfToken()
                                    ); ?>">

                        <input
                            type="hidden"
                            name="action"
                            value="<?=
                                    $formMode === 'edit'
                                        ? 'update'
                                        : 'create';
                                    ?>">


                        <?php if (
                            $formMode === 'edit'
                        ): ?>

                            <input
                                type="hidden"
                                name="id_kriteria"
                                value="<?=
                                        (int)
                                        $formData['id_kriteria'];
                                        ?>">

                        <?php endif; ?>


                        <div
                            class="grid grid-cols-1
                               gap-5 md:grid-cols-2">

                            <!-- Kode -->
                            <div>

                                <label
                                    for="kode_kriteria"
                                    class="mb-1.5 block
                                       text-sm
                                       font-medium">
                                    Kode Kriteria
                                </label>

                                <input
                                    type="text"
                                    name="kode_kriteria"
                                    id="kode_kriteria"
                                    maxlength="10"
                                    required
                                    placeholder="Contoh: C1"
                                    value="<?= e(
                                                (string)
                                                $formData['kode_kriteria']
                                            ); ?>"
                                    class="w-full rounded-md
                                       border
                                       border-zinc-400
                                       bg-white px-3
                                       py-2.5 text-sm
                                       outline-none
                                       transition
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                            </div>


                            <!-- Jenis -->
                            <div>

                                <label
                                    for="jenis_kriteria"
                                    class="mb-1.5 block
                                       text-sm
                                       font-medium">
                                    Jenis Kriteria
                                </label>

                                <select
                                    name="jenis_kriteria"
                                    id="jenis_kriteria"
                                    required
                                    class="w-full rounded-md
                                       border
                                       border-zinc-400
                                       bg-white px-3
                                       py-2.5 text-sm
                                       outline-none
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                                    <option
                                        value="Benefit"
                                        <?=
                                        $formData['jenis_kriteria'] === 'Benefit'
                                            ? 'selected'
                                            : '';
                                        ?>>
                                        Benefit
                                    </option>

                                    <option
                                        value="Cost"
                                        <?=
                                        $formData['jenis_kriteria'] === 'Cost'
                                            ? 'selected'
                                            : '';
                                        ?>>
                                        Cost
                                    </option>

                                </select>

                            </div>


                            <!-- Nama -->
                            <div class="md:col-span-2">

                                <label
                                    for="nama_kriteria"
                                    class="mb-1.5 block
                                       text-sm
                                       font-medium">
                                    Nama Kriteria
                                </label>

                                <input
                                    type="text"
                                    name="nama_kriteria"
                                    id="nama_kriteria"
                                    maxlength="100"
                                    required
                                    placeholder="Contoh: Volume Penjualan"
                                    value="<?= e(
                                                (string)
                                                $formData['nama_kriteria']
                                            ); ?>"
                                    class="w-full rounded-md
                                       border
                                       border-zinc-400
                                       bg-white px-3
                                       py-2.5 text-sm
                                       outline-none
                                       transition
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                            </div>

                        </div>


                        <!-- Buttons -->
                        <div
                            class="mt-7 flex
                               justify-end gap-3">

                            <a
                                href="kriteria.php"
                                class="rounded-md
                                   border
                                   border-zinc-400
                                   px-4 py-2
                                   text-sm
                                   font-medium
                                   transition
                                   hover:bg-zinc-100">
                                Batal
                            </a>

                            <button
                                type="submit"
                                class="rounded-md
                                   bg-zinc-900
                                   px-5 py-2
                                   text-sm
                                   font-semibold
                                   text-white
                                   transition
                                   hover:bg-zinc-800">
                                <?=
                                $formMode === 'edit'
                                    ? 'Simpan Perubahan'
                                    : 'Simpan';
                                ?>
                            </button>

                        </div>

                    </form>

                </section>

            <?php endif; ?>

        </main>

    </div>


    <!-- =========================================================
     MOBILE SIDEBAR
========================================================== -->

    <script>
        const sidebar =
            document.getElementById('sidebar');

        const sidebarButton =
            document.getElementById('sidebarButton');

        const sidebarOverlay =
            document.getElementById('sidebarOverlay');


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
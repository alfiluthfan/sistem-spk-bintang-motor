<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AlternatifService.php';

Auth::requireRole('staf');

$user = Auth::user();

$alternatifService = new AlternatifService(
    Database::connect()
);

$activePage = 'alternatif';

$error = null;
$success = null;

$formMode = null;

$formData = [
    'id_alternatif' => '',
    'kode_alternatif' => '',
    'nama_alternatif' => '',
];

$viewData = null;


/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Flash Message
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
| Form Create
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && ($_GET['form'] ?? null) === 'create'
) {
    $formMode = 'create';
}


/*
|--------------------------------------------------------------------------
| Detail / Lihat
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['view'])
) {
    $id = filter_var(
        $_GET['view'],
        FILTER_VALIDATE_INT
    );

    if ($id === false || $id === null) {
        $error = 'ID alternatif tidak valid.';
    } else {
        $viewData = $alternatifService->findById(
            (int) $id
        );

        if ($viewData === null) {
            $error = 'Data alternatif tidak ditemukan.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Edit
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

    if ($id === false || $id === null) {
        $error = 'ID alternatif tidak valid.';
    } else {
        $alternatif =
            $alternatifService->findById(
                (int) $id
            );

        if ($alternatif === null) {
            $error = 'Data alternatif tidak ditemukan.';
        } else {
            $formMode = 'edit';
            $formData = $alternatif;
        }
    }
}


/*
|--------------------------------------------------------------------------
| POST Request
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = (string) (
        $_POST['_token'] ?? ''
    );

    if (!Auth::verifyCsrf($csrfToken)) {

        $error =
            'Permintaan tidak valid. Silakan coba kembali.';
    } else {

        $action = (string) (
            $_POST['action'] ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | Create / Update
        |--------------------------------------------------------------------------
        */

        if (
            $action === 'create'
            || $action === 'update'
        ) {

            $rawId =
                $_POST['id_alternatif'] ?? null;

            $id = $rawId !== null
                ? filter_var(
                    $rawId,
                    FILTER_VALIDATE_INT
                )
                : null;

            $kode = strtoupper(
                trim(
                    (string) (
                        $_POST['kode_alternatif']
                        ?? ''
                    )
                )
            );

            $nama = trim(
                (string) (
                    $_POST['nama_alternatif']
                    ?? ''
                )
            );

            $formMode =
                $action === 'update'
                ? 'edit'
                : 'create';

            $formData = [
                'id_alternatif' =>
                $id !== false && $id !== null
                    ? (int) $id
                    : '',

                'kode_alternatif' => $kode,
                'nama_alternatif' => $nama,
            ];


            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            if ($kode === '' || $nama === '') {

                $error =
                    'Kode dan nama alternatif wajib diisi.';
            } elseif (strlen($kode) > 10) {

                $error =
                    'Kode alternatif maksimal 10 karakter.';
            } elseif (strlen($nama) > 100) {

                $error =
                    'Nama produk maksimal 100 karakter.';
            } elseif (
                !preg_match(
                    '/^[A-Z0-9_-]+$/',
                    $kode
                )
            ) {

                $error =
                    'Kode alternatif hanya boleh berisi huruf, angka, tanda hubung, atau underscore.';
            } elseif (
                $action === 'update'
                && (
                    $id === false
                    || $id === null
                    || (int) $id <= 0
                )
            ) {

                $error =
                    'ID alternatif tidak valid.';
            } elseif (
                $alternatifService->kodeExists(
                    $kode,
                    $action === 'update'
                        ? (int) $id
                        : null
                )
            ) {

                $error =
                    'Kode alternatif sudah digunakan.';
            } else {

                try {

                    if ($action === 'create') {

                        $alternatifService->create(
                            $kode,
                            $nama
                        );

                        $_SESSION['success'] =
                            'Alternatif berhasil ditambahkan.';
                    } else {

                        $alternatifService->update(
                            (int) $id,
                            $kode,
                            $nama
                        );

                        $_SESSION['success'] =
                            'Alternatif berhasil diperbarui.';
                    }

                    header(
                        'Location: alternatif.php'
                    );

                    exit;
                } catch (Throwable $exception) {

                    error_log(
                        $exception->getMessage()
                    );

                    $error =
                        'Terjadi kesalahan saat menyimpan data alternatif.';
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
                $_POST['id_alternatif'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                $id === false
                || $id === null
            ) {

                $_SESSION['error'] =
                    'ID alternatif tidak valid.';
            } else {

                try {

                    $deleted =
                        $alternatifService->delete(
                            (int) $id
                        );

                    if ($deleted) {

                        $_SESSION['success'] =
                            'Alternatif berhasil dihapus.';
                    } else {

                        $_SESSION['error'] =
                            'Alternatif tidak dapat dihapus karena sudah digunakan dalam data penilaian atau hasil TOPSIS.';
                    }
                } catch (Throwable $exception) {

                    error_log(
                        $exception->getMessage()
                    );

                    $_SESSION['error'] =
                        'Terjadi kesalahan saat menghapus alternatif.';
                }
            }

            header(
                'Location: alternatif.php'
            );

            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$perPage = 6;

$page = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT
);

if (
    $page === false
    || $page < 1
) {
    $page = 1;
}

$totalData =
    $alternatifService->countAll();

$totalPages = max(
    1,
    (int) ceil(
        $totalData / $perPage
    )
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset =
    ($page - 1) * $perPage;

$daftarAlternatif =
    $alternatifService->getPaginated(
        $perPage,
        $offset
    );

$startNumber =
    $totalData > 0
    ? $offset + 1
    : 0;

$endNumber = min(
    $offset + $perPage,
    $totalData
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Data Alternatif | SPK Motor
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
               bg-white px-4 sm:px-6 lg:px-8">

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
                        Data Alternatif
                    </span>
                </div>

                <span
                    class="font-semibold sm:hidden">
                    Data Alternatif
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


        <!-- =====================================================
         CONTENT
    ====================================================== -->

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
                        Kelola Data Alternatif
                        (Produk Motor)
                    </h1>

                    <p
                        class="mt-1 text-sm
                           text-zinc-500">
                        Kelola produk motor yang menjadi
                        alternatif dalam proses penilaian TOPSIS.
                    </p>

                </div>


                <a
                    href="alternatif.php?form=create#form-alternatif"
                    class="inline-flex items-center
                       justify-center rounded-md
                       bg-zinc-900 px-4 py-2.5
                       text-sm font-semibold
                       text-white
                       transition hover:bg-zinc-800">
                    + Tambah Alternatif
                </a>

            </div>


            <!-- Success -->
            <?php if ($success !== null): ?>

                <div
                    class="mb-5 rounded-md
                       border border-zinc-300
                       bg-white px-4 py-3
                       text-sm">
                    <?= e($success); ?>
                </div>

            <?php endif; ?>


            <!-- Error -->
            <?php if ($error !== null): ?>

                <div
                    class="mb-5 flex gap-3
                       rounded-md border
                       border-zinc-400
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


            <!-- =================================================
             TABLE
        ================================================== -->

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
                                    class="w-16 border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    No
                                </th>

                                <th
                                    class="w-32 border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    Kode
                                </th>

                                <th
                                    class="border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    Nama Produk
                                </th>

                                <th
                                    class="w-64 border-b
                                       border-zinc-300
                                       px-3 py-3 text-right">
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (
                                empty($daftarAlternatif)
                            ): ?>

                                <tr>

                                    <td
                                        colspan="4"
                                        class="px-3 py-10
                                       text-center
                                       text-zinc-500">
                                        Belum ada data alternatif.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach (
                                    $daftarAlternatif
                                    as $index => $alternatif
                                ): ?>

                                    <tr
                                        class="border-b
                                       border-zinc-200
                                       last:border-b-0">

                                        <td class="px-3 py-3">
                                            <?= $offset + $index + 1; ?>
                                        </td>

                                        <td
                                            class="px-3 py-3
                                           font-semibold">
                                            <?= e(
                                                $alternatif['kode_alternatif']
                                            ); ?>
                                        </td>

                                        <td
                                            class="px-3 py-3
                                           font-medium">
                                            <?= e(
                                                $alternatif['nama_alternatif']
                                            ); ?>
                                        </td>

                                        <td class="px-3 py-3">

                                            <div
                                                class="flex
                                               justify-end
                                               gap-2">

                                                <!-- Lihat -->
                                                <a
                                                    href="alternatif.php?view=<?= (int) $alternatif['id_alternatif']; ?>#detail-alternatif"
                                                    class="rounded-md
                                                   border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   text-xs
                                                   font-medium
                                                   transition
                                                   hover:bg-zinc-100">
                                                    Lihat
                                                </a>


                                                <!-- Edit -->
                                                <a
                                                    href="alternatif.php?edit=<?= (int) $alternatif['id_alternatif']; ?>#form-alternatif"
                                                    class="rounded-md
                                                   border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   text-xs
                                                   font-medium
                                                   transition
                                                   hover:bg-zinc-100">
                                                    Edit
                                                </a>


                                                <!-- Hapus -->
                                                <form
                                                    action="alternatif.php"
                                                    method="POST"
                                                    onsubmit="
                                                return confirm(
                                                    'Yakin ingin menghapus alternatif ini?'
                                                );
                                            ">

                                                    <input
                                                        type="hidden"
                                                        name="_token"
                                                        value="<?= e(
                                                                    Auth::csrfToken()
                                                                ); ?>">

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="delete">

                                                    <input
                                                        type="hidden"
                                                        name="id_alternatif"
                                                        value="<?= (int) $alternatif['id_alternatif']; ?>">

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


                <!-- Pagination -->
                <div
                    class="mt-5 flex flex-col
                       gap-4 border-t
                       border-zinc-200 pt-5
                       sm:flex-row
                       sm:items-center
                       sm:justify-between">

                    <p class="text-sm text-zinc-500">

                        Menampilkan
                        <?= $startNumber; ?>–<?= $endNumber; ?>
                        dari
                        <?= $totalData; ?>
                        data

                    </p>


                    <?php if ($totalPages > 1): ?>

                        <div class="flex flex-wrap gap-2">

                            <!-- Previous -->
                            <?php if ($page > 1): ?>

                                <a
                                    href="?page=<?= $page - 1; ?>"
                                    class="rounded-md
                                       border border-zinc-300
                                       bg-white px-3 py-2
                                       text-xs font-medium
                                       hover:bg-zinc-100">
                                    Sebelumnya
                                </a>

                            <?php else: ?>

                                <span
                                    class="cursor-not-allowed
                                       rounded-md border
                                       border-zinc-200
                                       px-3 py-2
                                       text-xs
                                       text-zinc-400">
                                    Sebelumnya
                                </span>

                            <?php endif; ?>


                            <!-- Page numbers -->
                            <?php for (
                                $i = 1;
                                $i <= $totalPages;
                                $i++
                            ): ?>

                                <a
                                    href="?page=<?= $i; ?>"
                                    class="
                                    rounded-md border
                                    px-3 py-2
                                    text-xs font-medium

                                    <?= $i === $page
                                        ? 'border-zinc-900 bg-zinc-900 text-white'
                                        : 'border-zinc-300 bg-white hover:bg-zinc-100'
                                    ?>
                                ">
                                    <?= $i; ?>
                                </a>

                            <?php endfor; ?>


                            <!-- Next -->
                            <?php if (
                                $page < $totalPages
                            ): ?>

                                <a
                                    href="?page=<?= $page + 1; ?>"
                                    class="rounded-md
                                       border border-zinc-300
                                       bg-white px-3 py-2
                                       text-xs font-medium
                                       hover:bg-zinc-100">
                                    Selanjutnya
                                </a>

                            <?php else: ?>

                                <span
                                    class="cursor-not-allowed
                                       rounded-md border
                                       border-zinc-200
                                       px-3 py-2
                                       text-xs
                                       text-zinc-400">
                                    Selanjutnya
                                </span>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <!-- =================================================
             DETAIL ALTERNATIF
        ================================================== -->

            <?php if ($viewData !== null): ?>

                <section
                    id="detail-alternatif"
                    class="mt-6 rounded-md
                       border border-dashed
                       border-zinc-400
                       bg-white p-6">

                    <div
                        class="border-b
                           border-zinc-200 pb-3">

                        <h2 class="font-semibold">
                            Detail Alternatif
                        </h2>

                        <p
                            class="mt-1 text-sm
                               text-zinc-500">
                            Informasi produk yang menjadi
                            alternatif penilaian.
                        </p>

                    </div>


                    <div
                        class="mt-5 grid grid-cols-1
                           gap-5 md:grid-cols-2">

                        <div>

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Kode Alternatif
                            </p>

                            <p class="mt-1 font-semibold">
                                <?= e(
                                    $viewData['kode_alternatif']
                                ); ?>
                            </p>

                        </div>


                        <div>

                            <p
                                class="text-xs font-medium
                                   uppercase
                                   text-zinc-500">
                                Nama Produk
                            </p>

                            <p class="mt-1 font-semibold">
                                <?= e(
                                    $viewData['nama_alternatif']
                                ); ?>
                            </p>

                        </div>

                    </div>


                    <div
                        class="mt-6 flex
                           justify-end gap-3">

                        <a
                            href="alternatif.php"
                            class="rounded-md
                               border border-zinc-300
                               px-4 py-2
                               text-sm font-medium
                               hover:bg-zinc-100">
                            Tutup
                        </a>

                        <a
                            href="alternatif.php?edit=<?= (int) $viewData['id_alternatif']; ?>#form-alternatif"
                            class="rounded-md
                               bg-zinc-900
                               px-4 py-2
                               text-sm font-semibold
                               text-white
                               hover:bg-zinc-800">
                            Edit Data
                        </a>

                    </div>

                </section>

            <?php endif; ?>


            <!-- =================================================
             CREATE / EDIT FORM
        ================================================== -->

            <?php if (
                $formMode !== null
            ): ?>

                <section
                    id="form-alternatif"
                    class="mt-6 rounded-md
                       border border-dashed
                       border-zinc-400
                       bg-white p-6">

                    <div
                        class="border-b
                           border-zinc-200 pb-3">

                        <h2 class="font-semibold">

                            <?= $formMode === 'edit'
                                ? 'Edit Alternatif'
                                : 'Tambah Alternatif';
                            ?>

                        </h2>

                        <p
                            class="mt-1 text-sm
                               text-zinc-500">
                            Masukkan kode dan nama produk
                            yang akan digunakan dalam proses
                            penilaian.
                        </p>

                    </div>


                    <form
                        action="alternatif.php"
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
                            value="<?= $formMode === 'edit'
                                        ? 'update'
                                        : 'create';
                                    ?>">


                        <?php if (
                            $formMode === 'edit'
                        ): ?>

                            <input
                                type="hidden"
                                name="id_alternatif"
                                value="<?= (int) $formData['id_alternatif']; ?>">

                        <?php endif; ?>


                        <div
                            class="grid grid-cols-1
                               gap-5 md:grid-cols-2">

                            <!-- Kode -->
                            <div>

                                <label
                                    for="kode_alternatif"
                                    class="mb-1.5 block
                                       text-sm
                                       font-medium">
                                    Kode Alternatif
                                </label>

                                <input
                                    type="text"
                                    id="kode_alternatif"
                                    name="kode_alternatif"
                                    required
                                    maxlength="10"
                                    placeholder="Contoh: A1"
                                    value="<?= e(
                                                (string)
                                                $formData['kode_alternatif']
                                            ); ?>"
                                    class="w-full rounded-md
                                       border border-zinc-400
                                       bg-white px-3
                                       py-2.5 text-sm
                                       uppercase
                                       outline-none
                                       transition
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                            </div>


                            <!-- Nama Produk -->
                            <div>

                                <label
                                    for="nama_alternatif"
                                    class="mb-1.5 block
                                       text-sm
                                       font-medium">
                                    Nama Produk
                                </label>

                                <input
                                    type="text"
                                    id="nama_alternatif"
                                    name="nama_alternatif"
                                    required
                                    maxlength="100"
                                    placeholder="Contoh: Honda Beat"
                                    value="<?= e(
                                                (string)
                                                $formData['nama_alternatif']
                                            ); ?>"
                                    class="w-full rounded-md
                                       border border-zinc-400
                                       bg-white px-3
                                       py-2.5 text-sm
                                       outline-none
                                       transition
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                            </div>

                        </div>


                        <div
                            class="mt-7 flex
                               justify-end gap-3">

                            <a
                                href="alternatif.php"
                                class="rounded-md
                                   border border-zinc-400
                                   px-4 py-2
                                   text-sm font-medium
                                   transition
                                   hover:bg-zinc-100">
                                Batal
                            </a>

                            <button
                                type="submit"
                                class="rounded-md
                                   bg-zinc-900
                                   px-5 py-2
                                   text-sm font-semibold
                                   text-white
                                   transition
                                   hover:bg-zinc-800">

                                <?= $formMode === 'edit'
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
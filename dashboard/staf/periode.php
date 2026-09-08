<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/PeriodeService.php';

Auth::requireRole('staf');

$user = Auth::user();

$periodeService = new PeriodeService(
    Database::connect()
);

$activePage = 'periode';

$error = null;
$success = null;
$formMode = null;

$formData = [
    'id_periode' => '',
    'nama_periode' => '',
    'tanggal_mulai' => '',
    'tanggal_selesai' => '',
    'status' => 'Aktif',
];


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

function validDate(string $date): bool
{
    $object = DateTime::createFromFormat(
        'Y-m-d',
        $date
    );

    return $object !== false
        && $object->format('Y-m-d') === $date;
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
| Create Form
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
        $error = 'ID periode tidak valid.';
    } else {
        $periode = $periodeService->findById(
            (int) $id
        );

        if ($periode === null) {
            $error = 'Data periode tidak ditemukan.';
        } else {
            $formMode = 'edit';
            $formData = $periode;
        }
    }
}


/*
|--------------------------------------------------------------------------
| POST
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
                $_POST['id_periode'] ?? null;

            $id = $rawId !== null
                ? filter_var(
                    $rawId,
                    FILTER_VALIDATE_INT
                )
                : null;

            $nama = trim(
                (string) (
                    $_POST['nama_periode']
                    ?? ''
                )
            );

            $tanggalMulai = trim(
                (string) (
                    $_POST['tanggal_mulai']
                    ?? ''
                )
            );

            $tanggalSelesai = trim(
                (string) (
                    $_POST['tanggal_selesai']
                    ?? ''
                )
            );

            $status = trim(
                (string) (
                    $_POST['status']
                    ?? ''
                )
            );

            $formMode =
                $action === 'update'
                ? 'edit'
                : 'create';

            $formData = [
                'id_periode' =>
                $id !== false
                    && $id !== null
                    ? (int) $id
                    : '',

                'nama_periode' => $nama,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'status' => $status,
            ];


            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            if (
                $nama === ''
                || $tanggalMulai === ''
                || $tanggalSelesai === ''
                || $status === ''
            ) {
                $error =
                    'Seluruh data periode wajib diisi.';
            } elseif (strlen($nama) > 100) {
                $error =
                    'Nama periode maksimal 100 karakter.';
            } elseif (
                !validDate($tanggalMulai)
                || !validDate($tanggalSelesai)
            ) {
                $error =
                    'Format tanggal tidak valid.';
            } elseif (
                $tanggalSelesai < $tanggalMulai
            ) {
                $error =
                    'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
            } elseif (
                !in_array(
                    $status,
                    ['Aktif', 'Selesai'],
                    true
                )
            ) {
                $error =
                    'Status periode tidak valid.';
            } elseif (
                $action === 'update'
                && (
                    $id === false
                    || $id === null
                    || (int) $id <= 0
                )
            ) {
                $error =
                    'ID periode tidak valid.';
            } else {

                try {

                    if ($action === 'create') {

                        $periodeService->create(
                            $nama,
                            $tanggalMulai,
                            $tanggalSelesai,
                            $status
                        );

                        $_SESSION['success'] =
                            'Periode penilaian berhasil ditambahkan.';
                    } else {

                        $periodeService->update(
                            (int) $id,
                            $nama,
                            $tanggalMulai,
                            $tanggalSelesai,
                            $status
                        );

                        $_SESSION['success'] =
                            'Periode penilaian berhasil diperbarui.';
                    }

                    header(
                        'Location: periode.php'
                    );

                    exit;
                } catch (Throwable $exception) {

                    error_log(
                        $exception->getMessage()
                    );

                    $error =
                        'Terjadi kesalahan saat menyimpan periode penilaian.';
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
                $_POST['id_periode'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                $id === false
                || $id === null
            ) {

                $_SESSION['error'] =
                    'ID periode tidak valid.';
            } else {

                try {

                    $deleted =
                        $periodeService->delete(
                            (int) $id
                        );

                    if ($deleted) {

                        $_SESSION['success'] =
                            'Periode penilaian berhasil dihapus.';
                    } else {

                        $_SESSION['error'] =
                            'Periode tidak dapat dihapus karena sudah digunakan pada data penilaian, AHP, atau TOPSIS.';
                    }
                } catch (Throwable $exception) {

                    error_log(
                        $exception->getMessage()
                    );

                    $_SESSION['error'] =
                        'Terjadi kesalahan saat menghapus periode.';
                }
            }

            header(
                'Location: periode.php'
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
    $periodeService->countAll();

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

$daftarPeriode =
    $periodeService->getPaginated(
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
        Data Periode Penilaian | SPK Motor
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


        <!-- TOPBAR -->
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
                        Data Periode Penilaian
                    </span>

                </div>

                <span class="font-semibold sm:hidden">
                    Periode Penilaian
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


        <!-- CONTENT -->
        <main class="p-4 sm:p-6 lg:p-8">


            <!-- Header page -->
            <div
                class="mb-6 flex flex-col gap-4
                   sm:flex-row
                   sm:items-center
                   sm:justify-between">

                <div>

                    <h1
                        class="text-2xl font-bold
                           tracking-tight">
                        Kelola Periode Penilaian
                    </h1>

                    <p
                        class="mt-1 text-sm text-zinc-500">
                        Kelola periode yang digunakan untuk
                        mengelompokkan data penilaian dan hasil
                        perhitungan SPK.
                    </p>

                </div>


                <a
                    href="periode.php?form=create#form-periode"
                    class="inline-flex items-center
                       justify-center rounded-md
                       bg-zinc-900 px-4 py-2.5
                       text-sm font-semibold
                       text-white transition
                       hover:bg-zinc-800">
                    + Tambah Periode
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


            <!-- TABLE -->
            <section
                class="rounded-md border
                   border-zinc-300
                   bg-white p-5">

                <div class="overflow-x-auto">

                    <table
                        class="w-full min-w-[800px]
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
                                    class="border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    Nama Periode
                                </th>

                                <th
                                    class="w-40 border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    Tanggal Mulai
                                </th>

                                <th
                                    class="w-40 border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    Tanggal Selesai
                                </th>

                                <th
                                    class="w-32 border-b
                                       border-zinc-300
                                       px-3 py-3 text-left">
                                    Status
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
                                empty($daftarPeriode)
                            ): ?>

                                <tr>

                                    <td
                                        colspan="6"
                                        class="px-3 py-10
                                       text-center
                                       text-zinc-500">
                                        Belum ada periode penilaian.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach (
                                    $daftarPeriode
                                    as $index => $periode
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
                                                $periode['nama_periode']
                                            ); ?>
                                        </td>

                                        <td class="px-3 py-3">
                                            <?= formatTanggal(
                                                $periode['tanggal_mulai']
                                            ); ?>
                                        </td>

                                        <td class="px-3 py-3">
                                            <?= formatTanggal(
                                                $periode['tanggal_selesai']
                                            ); ?>
                                        </td>

                                        <td class="px-3 py-3">

                                            <?php if (
                                                $periode['status']
                                                === 'Aktif'
                                            ): ?>

                                                <span
                                                    class="inline-flex
                                                   rounded-md
                                                   bg-zinc-900
                                                   px-2.5 py-1
                                                   text-xs
                                                   font-medium
                                                   text-white">
                                                    Aktif
                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="inline-flex
                                                   rounded-md
                                                   border
                                                   border-zinc-300
                                                   bg-zinc-50
                                                   px-2.5 py-1
                                                   text-xs
                                                   text-zinc-600">
                                                    <?= e(
                                                        $periode['status']
                                                    ); ?>
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td class="px-3 py-3">

                                            <div
                                                class="flex
                                               justify-end
                                               gap-2">

                                                <a
                                                    href="periode.php?edit=<?= (int) $periode['id_periode']; ?>#form-periode"
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


                                                <form
                                                    action="periode.php"
                                                    method="POST"
                                                    onsubmit="
                                                return confirm(
                                                    'Yakin ingin menghapus periode ini?'
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
                                                        name="id_periode"
                                                        value="<?= (int) $periode['id_periode']; ?>">

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
                    class="mt-5 flex flex-col gap-4
                       border-t border-zinc-200
                       pt-5 sm:flex-row
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

                            <?php if ($page > 1): ?>

                                <a
                                    href="?page=<?= $page - 1; ?>"
                                    class="rounded-md border
                                       border-zinc-300
                                       px-3 py-2
                                       text-xs font-medium
                                       hover:bg-zinc-100">
                                    Sebelumnya
                                </a>

                            <?php endif; ?>


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

                                    <?= $page === $i
                                        ? 'border-zinc-900 bg-zinc-900 text-white'
                                        : 'border-zinc-300 bg-white hover:bg-zinc-100'
                                    ?>
                                ">
                                    <?= $i; ?>
                                </a>

                            <?php endfor; ?>


                            <?php if (
                                $page < $totalPages
                            ): ?>

                                <a
                                    href="?page=<?= $page + 1; ?>"
                                    class="rounded-md border
                                       border-zinc-300
                                       px-3 py-2
                                       text-xs font-medium
                                       hover:bg-zinc-100">
                                    Selanjutnya
                                </a>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <!-- FORM -->
            <?php if ($formMode !== null): ?>

                <section
                    id="form-periode"
                    class="mt-6 rounded-md
                       border border-dashed
                       border-zinc-400
                       bg-white p-6">

                    <div
                        class="border-b
                           border-zinc-200 pb-3">

                        <h2 class="font-semibold">

                            <?= $formMode === 'edit'
                                ? 'Edit Periode Penilaian'
                                : 'Tambah Periode Penilaian';
                            ?>

                        </h2>

                        <p
                            class="mt-1 text-sm
                               text-zinc-500">
                            Tentukan rentang waktu dan status
                            periode penilaian.
                        </p>

                    </div>


                    <form
                        action="periode.php"
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
                                name="id_periode"
                                value="<?= (int) $formData['id_periode']; ?>">

                        <?php endif; ?>


                        <div
                            class="grid grid-cols-1
                               gap-5 md:grid-cols-2">

                            <!-- Nama -->
                            <div class="md:col-span-2">

                                <label
                                    for="nama_periode"
                                    class="mb-1.5 block
                                       text-sm font-medium">
                                    Nama Periode
                                </label>

                                <input
                                    type="text"
                                    id="nama_periode"
                                    name="nama_periode"
                                    maxlength="100"
                                    required
                                    placeholder="Contoh: Penilaian September 2026"
                                    value="<?= e(
                                                (string)
                                                $formData['nama_periode']
                                            ); ?>"
                                    class="w-full rounded-md
                                       border border-zinc-400
                                       bg-white px-3 py-2.5
                                       text-sm outline-none
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                            </div>


                            <!-- Tanggal Mulai -->
                            <div>

                                <label
                                    for="tanggal_mulai"
                                    class="mb-1.5 block
                                       text-sm font-medium">
                                    Tanggal Mulai
                                </label>

                                <input
                                    type="date"
                                    id="tanggal_mulai"
                                    name="tanggal_mulai"
                                    required
                                    value="<?= e(
                                                (string)
                                                $formData['tanggal_mulai']
                                            ); ?>"
                                    class="w-full rounded-md
                                       border border-zinc-400
                                       bg-white px-3 py-2.5
                                       text-sm outline-none
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                            </div>


                            <!-- Tanggal Selesai -->
                            <div>

                                <label
                                    for="tanggal_selesai"
                                    class="mb-1.5 block
                                       text-sm font-medium">
                                    Tanggal Selesai
                                </label>

                                <input
                                    type="date"
                                    id="tanggal_selesai"
                                    name="tanggal_selesai"
                                    required
                                    value="<?= e(
                                                (string)
                                                $formData['tanggal_selesai']
                                            ); ?>"
                                    class="w-full rounded-md
                                       border border-zinc-400
                                       bg-white px-3 py-2.5
                                       text-sm outline-none
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                            </div>


                            <!-- Status -->
                            <div>

                                <label
                                    for="status"
                                    class="mb-1.5 block
                                       text-sm font-medium">
                                    Status
                                </label>

                                <select
                                    id="status"
                                    name="status"
                                    required
                                    class="w-full rounded-md
                                       border border-zinc-400
                                       bg-white px-3 py-2.5
                                       text-sm outline-none
                                       focus:border-zinc-900
                                       focus:ring-1
                                       focus:ring-zinc-900">

                                    <option
                                        value="Aktif"
                                        <?= $formData['status']
                                            === 'Aktif'
                                            ? 'selected'
                                            : '';
                                        ?>>
                                        Aktif
                                    </option>

                                    <option
                                        value="Selesai"
                                        <?= $formData['status']
                                            === 'Selesai'
                                            ? 'selected'
                                            : '';
                                        ?>>
                                        Selesai
                                    </option>

                                </select>

                                <p
                                    class="mt-1.5 text-xs
                                       text-zinc-500">
                                    Jika periode ini diaktifkan,
                                    periode aktif sebelumnya akan
                                    otomatis menjadi selesai.
                                </p>

                            </div>

                        </div>


                        <div
                            class="mt-7 flex
                               justify-end gap-3">

                            <a
                                href="periode.php"
                                class="rounded-md border
                                   border-zinc-400
                                   px-4 py-2
                                   text-sm font-medium
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
<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';

Auth::redirectIfAuthenticated();

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = (string) ($_POST['_token'] ?? '');

    if (!Auth::verifyCsrf($csrfToken)) {
        $error = 'Permintaan tidak valid. Silakan coba kembali.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            if (Auth::attempt($username, $password)) {
                Auth::redirectToDashboard();
            }

            $error = 'Username atau password salah.';
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            $error = 'Terjadi kesalahan pada sistem. Silakan coba kembali.';
        }
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'role') {
    $error = 'Role pengguna tidak dikenali oleh sistem.';
}

function e(string $value): string
{
    return htmlspecialchars(
        $value,
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
        Login | Sistem Pendukung Keputusan
    </title>

    <link
        rel="stylesheet"
        href="./assets/css/app.css">
</head>

<body class="min-h-screen bg-[#f7f7f8] text-zinc-900">

    <main
        class="flex min-h-screen items-center justify-center px-4 py-10">

        <section
            class="w-full max-w-[440px] rounded-md border
                   border-zinc-400 bg-white px-8 py-8 shadow-sm">

            <!-- Header -->
            <header class="text-center">

                <h1
                    class="text-xl font-bold tracking-tight text-zinc-900">
                    Sistem Pendukung Keputusan
                </h1>

                <p
                    class="mt-1 text-[15px] text-zinc-600">
                    Penentuan Produk Unggulan Motor Terbaik
                </p>

                <p
                    class="mt-1 text-xs text-zinc-500">
                    Dealer Bintang Motor Cinere
                </p>

            </header>


            <div class="my-6 border-t border-zinc-200"></div>


            <!-- Form Login -->
            <form
                action=""
                method="POST"
                class="space-y-4">

                <input
                    type="hidden"
                    name="_token"
                    value="<?= e(Auth::csrfToken()); ?>">


                <!-- Username -->
                <div>

                    <label
                        for="username"
                        class="mb-1 block text-sm font-medium text-zinc-900">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= e($username); ?>"
                        placeholder="Masukkan username anda..."
                        autocomplete="username"
                        autofocus
                        required
                        class="block w-full rounded-md border
                               border-zinc-400 bg-white px-3 py-2.5
                               text-sm text-zinc-900
                               placeholder:text-zinc-400
                               outline-none transition
                               focus:border-zinc-900
                               focus:ring-1
                               focus:ring-zinc-900">

                </div>


                <!-- Password -->
                <div>

                    <label
                        for="password"
                        class="mb-1 block text-sm font-medium text-zinc-900">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password anda..."
                        autocomplete="current-password"
                        required
                        class="block w-full rounded-md border
                               border-zinc-400 bg-white px-3 py-2.5
                               text-sm text-zinc-900
                               placeholder:text-zinc-400
                               outline-none transition
                               focus:border-zinc-900
                               focus:ring-1
                               focus:ring-zinc-900">

                </div>


                <!-- Button Login -->
                <button
                    type="submit"
                    class="mt-2 w-full rounded-md bg-zinc-900
                           px-4 py-2.5 text-sm font-semibold
                           text-white transition
                           hover:bg-zinc-800
                           focus:outline-none
                           focus:ring-2
                           focus:ring-zinc-500
                           focus:ring-offset-2">
                    Masuk
                </button>


                <!-- Error Message -->
                <?php if ($error !== null): ?>

                    <div
                        role="alert"
                        class="flex items-center gap-3 rounded-md
                               border border-zinc-400
                               bg-zinc-50 px-3 py-2.5
                               text-sm text-zinc-600">

                        <span
                            class="flex h-4 w-4 flex-shrink-0
                                   items-center justify-center
                                   rounded-sm border border-zinc-400
                                   text-[10px] font-semibold">
                            !
                        </span>

                        <span>
                            <?= e($error); ?>
                        </span>

                    </div>

                <?php endif; ?>

            </form>


            <div class="my-6 border-t border-zinc-200"></div>


            <!-- Footer -->
            <footer class="text-center">

                <p class="text-xs text-zinc-500">
                    &copy;
                    <?= date('Y'); ?>
                    Dealer Bintang Motor Cinere
                </p>

            </footer>

        </section>

    </main>

</body>

</html>
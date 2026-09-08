<?php

declare(strict_types=1);

$activePage = $activePage ?? '';

$menuItems = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'href' => 'index.php',
    ],
    [
        'key' => 'periode',
        'label' => 'Data Periode Penilaian',
        'href' => 'periode.php',
    ],
    [
        'key' => 'kriteria',
        'label' => 'Data Kriteria',
        'href' => 'kriteria.php',
    ],
    [
        'key' => 'alternatif',
        'label' => 'Data Alternatif',
        'href' => 'alternatif.php',
    ],
    [
        'key' => 'nilai',
        'label' => 'Nilai Alternatif',
        'href' => 'nilai.php',
    ],
    [
        'key' => 'hasil',
        'label' => 'Hasil Perangkingan',
        'href' => 'hasil.php',
    ],
];
?>

<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-60
           -translate-x-full flex-col
           bg-zinc-900 text-zinc-300
           transition-transform duration-200
           lg:translate-x-0">

    <!-- Logo -->
    <div class="px-5 pt-5">

        <div class="flex items-center gap-3">

            <div
                class="flex h-6 w-6 items-center
                       justify-center border
                       border-zinc-400 text-xs
                       font-bold text-white">
                M
            </div>

            <span
                class="font-semibold tracking-wide text-white">
                SPK Motor
            </span>

        </div>

        <div class="mt-4 border-t border-zinc-600"></div>

    </div>


    <!-- Navigation -->
    <nav class="mt-6 flex-1 px-5">

        <ul class="space-y-1">

            <?php foreach ($menuItems as $menu): ?>

                <?php
                $isActive =
                    $activePage === $menu['key'];
                ?>

                <li>

                    <a
                        href="<?= htmlspecialchars(
                                    $menu['href'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                        class="
                            flex items-center gap-3
                            rounded-md px-4 py-2.5
                            text-sm transition

                            <?= $isActive
                                ? 'bg-zinc-700 font-semibold text-white'
                                : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'
                            ?>
                        ">

                        <span
                            class="
                                flex h-4 w-4 items-center
                                justify-center rounded-sm
                                border text-[8px]

                                <?= $isActive
                                    ? 'border-zinc-300 text-white'
                                    : 'border-zinc-500 text-zinc-400'
                                ?>
                            ">
                            •
                        </span>

                        <?= htmlspecialchars(
                            $menu['label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </a>

                </li>

            <?php endforeach; ?>

        </ul>

    </nav>


    <!-- Footer sidebar -->
    <div class="px-5 pb-6">

        <div class="border-t border-zinc-600 pt-4">

            <p class="text-xs text-zinc-500">
                Peran Pengguna:
            </p>

            <p
                class="mt-0.5 text-sm font-medium text-zinc-200">
                Staf Penjualan / Admin IT
            </p>

        </div>

        <a
            href="../../logout.php"
            class="mt-5 flex items-center gap-3
                   text-sm text-zinc-400
                   transition hover:text-white">
            <span
                class="flex h-4 w-4 items-center
                       justify-center rounded-sm
                       border border-zinc-500
                       text-[10px]">
                ←
            </span>

            Keluar
        </a>

    </div>

</aside>


<!-- Overlay mobile -->
<div
    id="sidebarOverlay"
    class="fixed inset-0 z-40 hidden
           bg-black/40 lg:hidden"></div>
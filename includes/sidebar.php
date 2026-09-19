<?php

require_once __DIR__ . '/../config/app.php';

$user = $_SESSION['user'] ?? null;

$current_path = $_SERVER['PHP_SELF'];

function menu_active(string $path): string
{
    global $current_path;

    return str_contains($current_path, $path)
        ? 'active'
        : '';
}
?>

<aside class="sidebar">

    <div class="sidebar-brand">

        <div class="brand-icon">
            SI
        </div>

        <div>
            <div class="brand-title">
                Snack Inventory
            </div>

            <small>
                Management System
            </small>
        </div>

    </div>


    <div class="sidebar-menu">

        <div class="menu-section">
            UTAMA
        </div>

        <a
            href="<?= BASE_URL ?>/dashboard/index.php"
            class="menu-item <?= menu_active('/dashboard/') ?>"
        >
            <span>⌂</span>
            Dashboard
        </a>


        <div class="menu-section">
            MASTER DATA
        </div>

        <a
            href="<?= BASE_URL ?>/categories/index.php"
            class="menu-item <?= menu_active('/categories/') ?>"
        >
            <span>▦</span>
            Kategori
        </a>

        <a
            href="<?= BASE_URL ?>/products/index.php"
            class="menu-item <?= menu_active('/products/') ?>"
        >
            <span>□</span>
            Barang
        </a>


        <div class="menu-section">
            TRANSAKSI
        </div>

        <a
            href="<?= BASE_URL ?>/stock/masuk.php"
            class="menu-item <?= menu_active('/stock/masuk.php') ?>"
        >
            <span>＋</span>
            Stok Masuk
        </a>

        <a
            href="<?= BASE_URL ?>/stock/keluar.php"
            class="menu-item <?= menu_active('/stock/keluar.php') ?>"
        >
            <span>−</span>
            Stok Keluar
        </a>

        <a
            href="<?= BASE_URL ?>/stock/history.php"
            class="menu-item <?= menu_active('/stock/history.php') ?>"
        >
            <span>↻</span>
            Riwayat Stok
        </a>


        <div class="menu-section">
            LAPORAN
        </div>

        <a
            href="<?= BASE_URL ?>/reports/stock.php"
            class="menu-item <?= menu_active('/reports/') ?>"
        >
            <span>▤</span>
            Laporan Stok
        </a>

    </div>


    <div class="sidebar-footer">

        <div class="user-box">

            <div class="user-avatar">
                <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
            </div>

            <div class="user-info">

                <strong>
                    <?= htmlspecialchars($user['name'] ?? 'Administrator') ?>
                </strong>

                <small>
                    <?= htmlspecialchars($user['role'] ?? 'admin') ?>
                </small>

            </div>

        </div>

        <a
            href="<?= BASE_URL ?>/auth/logout.php"
            class="logout-link"
        >
            Logout
        </a>

    </div>

</aside>
<?php

require_once __DIR__ . '/../config/app.php';

$user = $_SESSION['user'] ?? null;
$current_path = $_SERVER['PHP_SELF'] ?? '';

/**
 * Menentukan menu yang sedang aktif.
 */
function menu_active(string $path): string
{
    global $current_path;

    // Jangan aktifkan menu Stok Masuk & Keluar
    // ketika sedang berada di halaman Riwayat Stok.
    if (
        $path === '/stock/' &&
        str_contains($current_path, '/stock/history')
    ) {
        return '';
    }

    // Riwayat Stok hanya aktif pada halaman history.
    if (
        $path === '/stock/history' &&
        !str_contains($current_path, '/stock/history')
    ) {
        return '';
    }

    return str_contains($current_path, $path) ? 'active' : '';
}

?>

<aside class="sidebar" id="appSidebar">

    <!-- ==================================================
         SIDEBAR BRAND
    =================================================== -->

    <div class="sidebar-brand">

        <div class="brand-icon">
            SI
        </div>

        <div class="brand-copy">

            <div class="brand-title">
                Snack Inventory
            </div>

            <small>
                Sistem Manajemen Stok
            </small>

        </div>

        <button
            type="button"
            class="sidebar-toggle"
            id="sidebarToggle"
            aria-controls="appSidebar"
            aria-expanded="true"
            aria-label="Perkecil sidebar"
            title="Perkecil sidebar"
        >
            <span aria-hidden="true"></span>
        </button>

    </div>


    <!-- ==================================================
         SIDEBAR NAVIGATION
    =================================================== -->

    <nav
        class="sidebar-menu"
        aria-label="Navigasi utama"
    >

        <!-- DASHBOARD -->

        <a
            href="<?= BASE_URL ?>/dashboard/index.php"
            class="menu-item <?= menu_active('/dashboard/') ?>"
            title="Dashboard"
            aria-label="Dashboard"
        >
            <span
                class="menu-icon"
                aria-hidden="true"
            >&#8962;</span>

            <span class="menu-label">
                Dashboard
            </span>
        </a>


        <!-- ==================================================
             MASTER DATA
        =================================================== -->

        <div class="menu-section">
            MASTER DATA
        </div>


        <!-- BARANG -->

        <a
            href="<?= BASE_URL ?>/products/index.php"
            class="menu-item <?= menu_active('/products/') ?>"
            title="Barang"
            aria-label="Barang"
        >
            <span
                class="menu-icon"
                aria-hidden="true"
            >&#9632;</span>

            <span class="menu-label">
                Barang
            </span>
        </a>


        <!-- KATEGORI -->

        <a
            href="<?= BASE_URL ?>/categories/index.php"
            class="menu-item <?= menu_active('/categories/') ?>"
            title="Kategori"
            aria-label="Kategori"
        >
            <span
                class="menu-icon"
                aria-hidden="true"
            >&#9638;</span>

            <span class="menu-label">
                Kategori
            </span>
        </a>


        <!-- ==================================================
             MANAJEMEN STOK
        =================================================== -->

        <div class="menu-section">
            MANAJEMEN STOK
        </div>


        <!-- STOK MASUK & KELUAR -->

        <a
            href="<?= BASE_URL ?>/stock/index.php"
            class="menu-item <?= menu_active('/stock/') ?>"
            title="Stok Masuk & Keluar"
            aria-label="Stok Masuk dan Keluar"
        >
            <span
                class="menu-icon"
                aria-hidden="true"
            >&#8596;</span>

            <span class="menu-label">
                Stok Masuk &amp; Keluar
            </span>
        </a>


        <!-- RIWAYAT STOK -->

        <a
            href="<?= BASE_URL ?>/stock/history.php"
            class="menu-item <?= menu_active('/stock/history') ?>"
            title="Riwayat Stok"
            aria-label="Riwayat Stok"
        >
            <span
                class="menu-icon"
                aria-hidden="true"
            >&#8801;</span>

            <span class="menu-label">
                Riwayat Stok
            </span>
        </a>


        <!-- ==================================================
             LAPORAN
        =================================================== -->

        <div class="menu-section">
            LAPORAN
        </div>


        <!-- LAPORAN STOK -->

        <a
            href="<?= BASE_URL ?>/reports/stock.php"
            class="menu-item <?= menu_active('/reports/') ?>"
            title="Laporan Stok"
            aria-label="Laporan Stok"
        >
            <span
                class="menu-icon"
                aria-hidden="true"
            >&#9635;</span>

            <span class="menu-label">
                Laporan Stok
            </span>
        </a>

    </nav>


    <!-- ==================================================
         SIDEBAR FOOTER
    =================================================== -->

    <div class="sidebar-footer">

        <!-- USER PROFILE -->

        <div class="user-box">

            <div class="user-avatar">
                <?= strtoupper(
                    substr(
                        $user['name'] ?? 'A',
                        0,
                        1
                    )
                ) ?>
            </div>

            <div class="user-info">

                <strong>
                    <?= htmlspecialchars(
                        $user['name'] ?? 'Administrator'
                    ) ?>
                </strong>

                <small>
                    <?= htmlspecialchars(
                        ucfirst($user['role'] ?? 'admin')
                    ) ?>
                </small>

            </div>

        </div>


        <!-- ==================================================
             LOGOUT BUTTON
        =================================================== -->

        <button
            type="button"
            class="sidebar-logout-btn"
            id="logoutButton"
            data-bs-toggle="modal"
            data-bs-target="#logoutConfirmModal"
            title="Logout"
        >

            <span
                class="logout-icon"
                aria-hidden="true"
            >
                &#8599;
            </span>

            <span class="logout-label">
                Logout
            </span>

        </button>

    </div>

</aside>


<!-- ==================================================
     LOGOUT CONFIRMATION MODAL
================================================== -->

<div
    class="modal fade"
    id="logoutConfirmModal"
    tabindex="-1"
    aria-labelledby="logoutConfirmModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content logout-modal">


            <!-- ==================================================
                 MODAL HEADER
            =================================================== -->

            <div class="modal-header">

                <div class="logout-modal-heading">

                    <div class="logout-modal-icon">
                        <span aria-hidden="true">
                            &#8599;
                        </span>
                    </div>

                    <div>

                        <h5
                            class="modal-title"
                            id="logoutConfirmModalLabel"
                        >
                            Keluar dari sistem?
                        </h5>

                        <small>
                            Snack Inventory
                        </small>

                    </div>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>

            </div>


            <!-- ==================================================
                 MODAL BODY
            =================================================== -->

            <div class="modal-body">

                <p class="logout-question">
                    Anda akan keluar dari akun Administrator.
                </p>

                <p class="logout-description">
                    Pastikan semua pekerjaan yang sedang dilakukan
                    sudah tersimpan sebelum keluar dari sistem.
                </p>

            </div>


            <!-- ==================================================
                 MODAL FOOTER
            =================================================== -->

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn logout-cancel"
                    data-bs-dismiss="modal"
                >
                    Batal
                </button>

                <a
                    href="<?= BASE_URL ?>/auth/logout.php"
                    class="btn logout-confirm"
                >
                    <span aria-hidden="true">
                        &#8599;
                    </span>

                    Keluar
                </a>

            </div>

        </div>

    </div>

</div>
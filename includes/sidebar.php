<?php

require_once __DIR__ . '/../config/app.php';

$user = $_SESSION['user'] ?? null;

$current_path = $_SERVER['PHP_SELF'];


/*
|--------------------------------------------------------------------------
| MENU ACTIVE
|--------------------------------------------------------------------------
*/

function menu_active(string $path): string
{
    global $current_path;

    return str_contains(
        $current_path,
        $path
    )
        ? 'active'
        : '';
}

?>


<aside class="sidebar">


    <!-- =====================================================
         BRAND
    ====================================================== -->

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


    <!-- =====================================================
         MENU
    ====================================================== -->

    <div class="sidebar-menu">


        <!-- =================================================
             UTAMA
        ================================================== -->

        <div class="menu-section">

            UTAMA

        </div>


        <a
            href="<?= BASE_URL ?>/dashboard/index.php"
            class="menu-item <?= menu_active(
                '/dashboard/'
            ) ?>"
        >

            <span>

                ⌂

            </span>

            Dashboard

        </a>


        <!-- =================================================
             MASTER DATA
        ================================================== -->

        <div class="menu-section">

            MASTER DATA

        </div>


        <!-- KATEGORI -->

        <a
            href="<?= BASE_URL ?>/categories/index.php"
            class="menu-item <?= menu_active(
                '/categories/'
            ) ?>"
        >

            <span>

                ▦

            </span>

            Kategori

        </a>


        <!-- BARANG -->

        <a
            href="<?= BASE_URL ?>/products/index.php"
            class="menu-item <?= menu_active(
                '/products/'
            ) ?>"
        >

            <span>

                □

            </span>

            Barang

        </a>


        <!-- =================================================
             TRANSAKSI
        ================================================== -->

        <div class="menu-section">

            TRANSAKSI

        </div>


        <!-- MANAJEMEN STOK -->

        <a
            href="<?= BASE_URL ?>/stock/index.php"
            class="menu-item <?= menu_active(
                '/stock/'
            ) ?>"
        >

            <span>

                ⇄

            </span>

            Manajemen Stok

        </a>


        <!-- =================================================
             LAPORAN
        ================================================== -->

        <div class="menu-section">

            LAPORAN

        </div>


        <!-- LAPORAN STOK -->

        <a
            href="<?= BASE_URL ?>/reports/stock.php"
            class="menu-item <?= menu_active(
                '/reports/'
            ) ?>"
        >

            <span>

                ▤

            </span>

            Laporan Stok

        </a>


    </div>


    <!-- =====================================================
         SIDEBAR FOOTER
    ====================================================== -->

    <div class="sidebar-footer">


        <!-- USER -->

        <div class="user-box">


            <div class="user-avatar">

                <?= strtoupper(
                    substr(
                        $user['name']
                        ?? 'A',

                        0,

                        1
                    )
                ) ?>

            </div>


            <div class="user-info">


                <strong>

                    <?= htmlspecialchars(
                        $user['name']
                        ?? 'Administrator'
                    ) ?>

                </strong>


                <small>

                    <?= htmlspecialchars(
                        $user['role']
                        ?? 'admin'
                    ) ?>

                </small>


            </div>


        </div>


        <!-- LOGOUT -->

        <a
            href="<?= BASE_URL ?>/auth/logout.php"
            class="logout-link"
        >

            Logout

        </a>


    </div>


</aside>
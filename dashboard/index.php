<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$page_title = 'Dashboard';


/*
|--------------------------------------------------------------------------
| Statistik Dashboard
|--------------------------------------------------------------------------
*/

$total_products = (int) $pdo
    ->query("SELECT COUNT(*) FROM products")
    ->fetchColumn();


$normal_stock = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM products
        WHERE stock > minimum_stock
    ")
    ->fetchColumn();


$low_stock = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM products
        WHERE stock > 0
        AND stock <= minimum_stock
    ")
    ->fetchColumn();


$out_stock = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM products
        WHERE stock = 0
    ")
    ->fetchColumn();


/*
|--------------------------------------------------------------------------
| Transaksi Terbaru
|--------------------------------------------------------------------------
*/

$recent_movements = $pdo
    ->query("
        SELECT
            sm.id,
            sm.type,
            sm.quantity,
            sm.description,
            sm.created_at,
            p.code,
            p.name,
            p.unit,
            u.name AS user_name
        FROM stock_movements sm

        INNER JOIN products p
            ON p.id = sm.product_id

        LEFT JOIN users u
            ON u.id = sm.user_id

        ORDER BY sm.created_at DESC, sm.id DESC

        LIMIT 8
    ")
    ->fetchAll();


require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="content">

    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Dashboard
            </h1>

            <p class="page-subtitle">
                Ringkasan kondisi inventori Snack Mbak Tanti.
            </p>

        </div>

        <div>

            <span class="text-secondary small">

                <?= date('d M Y') ?>

            </span>

        </div>

    </div>


    <!-- STATISTICS -->

    <div class="row g-3 mb-4">


        <!-- TOTAL -->

        <div class="col-xl-3 col-md-6">

            <div class="dashboard-card">

                <div class="card-label">
                    Total Barang
                </div>

                <div class="card-value">
                    <?= $total_products ?>
                </div>

                <div class="card-description">
                    Seluruh barang terdaftar
                </div>

            </div>

        </div>


        <!-- NORMAL -->

        <div class="col-xl-3 col-md-6">

            <div class="dashboard-card">

                <div class="card-label">
                    Stok Normal
                </div>

                <div class="card-value text-success">
                    <?= $normal_stock ?>
                </div>

                <div class="card-description">
                    Stok di atas minimum
                </div>

            </div>

        </div>


        <!-- LOW -->

        <div class="col-xl-3 col-md-6">

            <div class="dashboard-card">

                <div class="card-label">
                    Stok Menipis
                </div>

                <div class="card-value text-warning">
                    <?= $low_stock ?>
                </div>

                <div class="card-description">
                    Perlu diperhatikan
                </div>

            </div>

        </div>


        <!-- EMPTY -->

        <div class="col-xl-3 col-md-6">

            <div class="dashboard-card">

                <div class="card-label">
                    Stok Habis
                </div>

                <div class="card-value text-danger">
                    <?= $out_stock ?>
                </div>

                <div class="card-description">
                    Barang tanpa stok
                </div>

            </div>

        </div>

    </div>


    <!-- RECENT TRANSACTIONS -->

    <div class="table-card">

        <div class="table-card-header">

            <h5>
                Transaksi Stok Terbaru
            </h5>

            <a
                href="<?= BASE_URL ?>/stock/history.php"
                class="btn btn-sm btn-outline-primary"
            >
                Lihat Semua
            </a>

        </div>


        <div class="table-card-body">

            <?php if ($recent_movements): ?>

                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Barang
                                </th>

                                <th>
                                    Jenis
                                </th>

                                <th>
                                    Jumlah
                                </th>

                                <th>
                                    Keterangan
                                </th>

                                <th>
                                    User
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($recent_movements as $movement): ?>

                            <tr>

                                <td>
                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime($movement['created_at'])
                                    ) ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars($movement['name']) ?>
                                    </strong>

                                    <br>

                                    <small class="text-secondary">
                                        <?= htmlspecialchars($movement['code']) ?>
                                    </small>

                                </td>


                                <td>

                                    <?php if ($movement['type'] === 'IN'): ?>

                                        <span class="badge text-bg-success">
                                            Masuk
                                        </span>

                                    <?php else: ?>

                                        <span class="badge text-bg-danger">
                                            Keluar
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= (int) $movement['quantity'] ?>

                                    <?= htmlspecialchars($movement['unit']) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $movement['description'] ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $movement['user_name'] ?? '-'
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    Belum ada transaksi stok.

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
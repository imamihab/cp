<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$page_title = 'Dashboard';


/*
|--------------------------------------------------------------------------
| STATISTIK DASHBOARD
|--------------------------------------------------------------------------
*/

$total_products = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM products
    ")
    ->fetchColumn();

$total_categories = (int) $pdo
    ->query("SELECT COUNT(*) FROM categories")
    ->fetchColumn();

$available_stock = (int) $pdo
    ->query("SELECT COALESCE(SUM(stock), 0) FROM products")
    ->fetchColumn();

$low_stock_products = $pdo->query("SELECT code, name, stock, minimum_stock, unit FROM products WHERE stock <= minimum_stock ORDER BY stock ASC, name ASC LIMIT 6")->fetchAll();


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
| TRANSAKSI STOK TERBARU
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

        ORDER BY
            sm.created_at DESC,
            sm.id DESC

        LIMIT 8
    ")
    ->fetchAll();


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/sidebar.php';

?>


<main class="content">


    <?php require_once dirname(__DIR__) . '/includes/topbar.php'; ?>

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    


    <!-- =====================================================
         STATISTIK
    ====================================================== -->

    <div class="dashboard-intro">
        <h1>Dashboard</h1>
        <p>Ringkasan kondisi inventori saat ini.</p>
    </div>

    <div class="row g-3 mb-4">


        <!-- TOTAL BARANG -->

        <div class="col-xl-3 col-md-6 col-6">

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


        <!-- STOK NORMAL -->

        <div class="col-xl-3 col-md-6 col-6">

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


        <!-- STOK MENIPIS -->

        <div class="col-xl-3 col-md-6 col-6">

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


        <!-- STOK HABIS -->

        <div class="col-xl-3 col-md-6 col-6">

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


    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="card-label">Kategori</div>
                <div class="card-value"><?= $total_categories ?></div>
                <div class="card-description">Total kategori barang</div>
                <hr>
                <div class="card-label">Total stok tersedia</div>
                <div class="fw-semibold"><?= number_format($available_stock) ?> unit tercatat</div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="table-card h-100">
                <div class="table-card-header"><h5>Stok Menipis</h5><a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/products/index.php">Lihat barang</a></div>
                <?php if ($low_stock_products): ?>
                <div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Barang</th><th class="text-end">Stok</th><th class="text-end">Min. stok</th></tr></thead><tbody>
                <?php foreach ($low_stock_products as $product): ?><tr><td><?= htmlspecialchars($product['code']) ?></td><td><?= htmlspecialchars($product['name']) ?></td><td class="text-end"><?= (int) $product['stock'] ?> <?= htmlspecialchars($product['unit']) ?></td><td class="text-end"><?= (int) $product['minimum_stock'] ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <?php else: ?><div class="empty-state">Tidak ada barang dengan stok menipis.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TRANSAKSI TERBARU -->

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


                        <?php foreach (
                            $recent_movements
                            as $movement
                        ): ?>


                            <tr>


                                <!-- TANGGAL -->

                                <td>

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $movement['created_at']
                                        )
                                    ) ?>

                                </td>


                                <!-- BARANG -->

                                <td>


                                    <strong>

                                        <?= htmlspecialchars(
                                            $movement['name']
                                        ) ?>

                                    </strong>


                                    <br>


                                    <small
                                        class="text-secondary"
                                    >

                                        <?= htmlspecialchars(
                                            $movement['code']
                                        ) ?>

                                    </small>


                                </td>


                                <!-- JENIS -->

                                <td>


                                    <?php if (
                                        $movement['type'] === 'IN'
                                    ): ?>


                                        <span
                                            class="badge text-bg-success"
                                        >

                                            Masuk

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="badge text-bg-danger"
                                        >

                                            Keluar

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- JUMLAH -->

                                <td>

                                    <?= (int)
                                        $movement['quantity']
                                    ?>

                                    <?= htmlspecialchars(
                                        $movement['unit']
                                    ) ?>

                                </td>


                                <!-- KETERANGAN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $movement['description']
                                        ?? '-'
                                    ) ?>

                                </td>


                                <!-- USER -->

                                <td>

                                    <?= htmlspecialchars(
                                        $movement['user_name']
                                        ?? '-'
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

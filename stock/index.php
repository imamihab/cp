<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$page_title = 'Manajemen Stok';

$errors = [];

$success = $_GET['success'] ?? '';

$old = [
    'type' => 'IN',
    'product_id' => '',
    'quantity' => '',
    'description' => '',
];


/*
|--------------------------------------------------------------------------
| PROSES TRANSAKSI STOK
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $type = $_POST['type'] ?? '';

    $product_id = (int) (
        $_POST['product_id'] ?? 0
    );

    $quantity = filter_var(
        $_POST['quantity'] ?? '',
        FILTER_VALIDATE_INT
    );

    $description = trim(
        $_POST['description'] ?? ''
    );


    $old = [
        'type' => $type,
        'product_id' => $product_id,
        'quantity' => $_POST['quantity'] ?? '',
        'description' => $description,
    ];


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if (
        $type !== 'IN' &&
        $type !== 'OUT'
    ) {

        $errors[] =
            'Jenis transaksi tidak valid.';

    }


    if ($product_id <= 0) {

        $errors[] =
            'Barang wajib dipilih.';

    }


    if (
        $quantity === false ||
        $quantity <= 0
    ) {

        $errors[] =
            'Jumlah transaksi harus lebih dari 0.';

    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN TRANSAKSI
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | AMBIL DATA BARANG
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    code,
                    name,
                    unit,
                    stock

                FROM products

                WHERE id = ?

                FOR UPDATE
            ");

            $stmt->execute([
                $product_id
            ]);

            $product = $stmt->fetch();


            if (!$product) {

                throw new Exception(
                    'Barang tidak ditemukan.'
                );

            }


            $current_stock =
                (int) $product['stock'];


            /*
            |--------------------------------------------------------------------------
            | STOK MASUK
            |--------------------------------------------------------------------------
            */

            if ($type === 'IN') {

                $new_stock =
                    $current_stock + $quantity;

            }


            /*
            |--------------------------------------------------------------------------
            | STOK KELUAR
            |--------------------------------------------------------------------------
            */

            else {

                if (
                    $quantity >
                    $current_stock
                ) {

                    throw new Exception(
                        'Stok tidak mencukupi. '
                        . 'Stok tersedia hanya '
                        . number_format(
                            $current_stock
                        )
                        . ' '
                        . $product['unit']
                        . '.'
                    );

                }


                $new_stock =
                    $current_stock - $quantity;

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE STOK BARANG
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE products

                SET
                    stock = ?,
                    updated_at = CURRENT_TIMESTAMP

                WHERE id = ?
            ");

            $stmt->execute([
                $new_stock,
                $product_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | SIMPAN RIWAYAT
            |--------------------------------------------------------------------------
            */

            $user_id =
                $_SESSION['user']['id'] ?? null;


            $stmt = $pdo->prepare("
                INSERT INTO stock_movements (
                    product_id,
                    type,
                    quantity,
                    description,
                    user_id
                )

                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $stmt->execute([
                $product_id,
                $type,
                $quantity,
                $description !== ''
                    ? $description
                    : null,
                $user_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            header(
                'Location: '
                . BASE_URL
                . '/stock/index.php?success='
                . (
                    $type === 'IN'
                        ? 'in'
                        : 'out'
                )
            );

            exit;


        } catch (Throwable $e) {

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();

            }


            $errors[] =
                $e->getMessage();

        }

    }

}


/*
|--------------------------------------------------------------------------
| DATA BARANG
|--------------------------------------------------------------------------
*/

$products = $pdo
    ->query("
        SELECT
            id,
            code,
            name,
            unit,
            stock,
            minimum_stock

        FROM products

        ORDER BY name ASC
    ")
    ->fetchAll();


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$total_products = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM products
    ")
    ->fetchColumn();


$total_stock = (int) $pdo
    ->query("
        SELECT COALESCE(
            SUM(stock),
            0
        )

        FROM products
    ")
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
| FILTER
|--------------------------------------------------------------------------
*/

$filter_type =
    $_GET['type'] ?? '';

$filter_product =
    (int) (
        $_GET['product_id'] ?? 0
    );

$filter_date_from =
    $_GET['date_from'] ?? '';

$filter_date_to =
    $_GET['date_to'] ?? '';


$where = [];

$params = [];


if (
    $filter_type === 'IN' ||
    $filter_type === 'OUT'
) {

    $where[] =
        'sm.type = ?';

    $params[] =
        $filter_type;

}


if ($filter_product > 0) {

    $where[] =
        'sm.product_id = ?';

    $params[] =
        $filter_product;

}


if ($filter_date_from !== '') {

    $where[] =
        'DATE(sm.created_at) >= ?';

    $params[] =
        $filter_date_from;

}


if ($filter_date_to !== '') {

    $where[] =
        'DATE(sm.created_at) <= ?';

    $params[] =
        $filter_date_to;

}


$where_sql = '';

if ($where) {

    $where_sql =
        'WHERE '
        . implode(
            ' AND ',
            $where
        );

}


/*
|--------------------------------------------------------------------------
| RIWAYAT TRANSAKSI
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        sm.id,
        sm.type,
        sm.quantity,
        sm.description,
        sm.created_at,

        p.code AS product_code,
        p.name AS product_name,
        p.unit,

        u.name AS user_name

    FROM stock_movements sm

    INNER JOIN products p
        ON p.id = sm.product_id

    LEFT JOIN users u
        ON u.id = sm.user_id

    $where_sql

    ORDER BY
        sm.created_at DESC,
        sm.id DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$movements = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| PERSENTASE STATUS
|--------------------------------------------------------------------------
*/

$status_total =
    max(
        $total_products,
        1
    );


$normal_percentage =
    round(
        ($normal_stock / $status_total)
        * 100
    );


$low_percentage =
    round(
        ($low_stock / $status_total)
        * 100
    );


$out_percentage =
    round(
        ($out_stock / $status_total)
        * 100
    );


/*
|--------------------------------------------------------------------------
| HEADER & SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/sidebar.php';

?>


<main class="content stock-page">


    <?php require_once dirname(__DIR__) . '/includes/topbar.php'; ?>

    <!-- =====================================================
         ALERT SUCCESS STOK MASUK
    ====================================================== -->

    <?php if ($success === 'in'): ?>

        <div
            class="alert alert-success alert-dismissible fade show stock-alert"
            role="alert"
        >

            <strong>

                Berhasil!

            </strong>

            Stok masuk berhasil dicatat dan stok barang
            telah diperbarui.


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ALERT SUCCESS STOK KELUAR
    ====================================================== -->

    <?php if ($success === 'out'): ?>

        <div
            class="alert alert-success alert-dismissible fade show stock-alert"
            role="alert"
        >

            <strong>

                Berhasil!

            </strong>

            Stok keluar berhasil dicatat dan stok barang
            telah diperbarui.


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ALERT ERROR
    ====================================================== -->

    <?php if ($errors): ?>

        <div
            class="alert alert-danger alert-dismissible fade show stock-alert"
            role="alert"
        >

            <strong>

                Transaksi gagal.

            </strong>


            <ul class="mb-0 mt-2">

                <?php foreach (
                    $errors
                    as $error
                ): ?>

                    <li>

                        <?= htmlspecialchars(
                            $error
                        ) ?>

                    </li>

                <?php endforeach; ?>

            </ul>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="stock-page-header">


        <div>
            <div>
                <h1>Manajemen Stok</h1>
                <p>Catat dan pantau seluruh pergerakan stok barang.</p>
            </div>
        </div>


        <div class="stock-action-buttons">


            <button
                type="button"
                class="btn-stock btn-stock-in"
                onclick="openStockModal('IN')"
            >

                <span class="btn-stock-icon">

                    +

                </span>

                Stok Masuk

            </button>


            <button
                type="button"
                class="btn-stock btn-stock-out"
                onclick="openStockModal('OUT')"
            >

                <span class="btn-stock-icon">

                    −

                </span>

                Stok Keluar

            </button>


        </div>


    </div>


    <!-- =====================================================
         SUMMARY CARDS
    ====================================================== -->

    <div class="stock-summary-grid">


        <div class="stock-summary-card">


            <div class="summary-icon summary-blue">

                ▤

            </div>


            <div class="summary-content">

                <span>

                    Total Barang

                </span>


                <strong>

                    <?= number_format(
                        $total_products
                    ) ?>

                </strong>


                <small>

                    Jenis barang terdaftar

                </small>

            </div>


        </div>


        <div class="stock-summary-card">


            <div class="summary-icon summary-green">

                ◉

            </div>


            <div class="summary-content">

                <span>

                    Total Stok

                </span>


                <strong>

                    <?= number_format(
                        $total_stock
                    ) ?>

                </strong>


                <small>

                    Total seluruh stok barang

                </small>

            </div>


        </div>


        <div class="stock-summary-card">


            <div class="summary-icon summary-yellow">

                !

            </div>


            <div class="summary-content">

                <span>

                    Stok Menipis

                </span>


                <strong>

                    <?= number_format(
                        $low_stock
                    ) ?>

                </strong>


                <small>

                    Barang perlu perhatian

                </small>

            </div>


        </div>


        <div class="stock-summary-card">


            <div class="summary-icon summary-red">

                ×

            </div>


            <div class="summary-content">

                <span>

                    Stok Habis

                </span>


                <strong>

                    <?= number_format(
                        $out_stock
                    ) ?>

                </strong>


                <small>

                    Barang sudah habis

                </small>

            </div>


        </div>


    </div>


    <!-- =====================================================
         STATUS PERSEDIAAN
    ====================================================== -->

    <section class="stock-section">


        <div class="stock-section-header">

            <h2>

                Status Persediaan

            </h2>


            <p>

                Ringkasan kondisi stok berdasarkan batas
                minimum stok.

            </p>

        </div>


        <div class="stock-status-grid">


            <!-- NORMAL -->

            <div class="stock-status-item">


                <div class="status-circle status-normal">

                    ✓

                </div>


                <div class="status-main">


                    <div class="status-title-row">


                        <div>

                            <strong>

                                Normal

                            </strong>


                            <small>

                                Stok di atas minimum

                            </small>

                        </div>


                        <strong class="status-number">

                            <?= number_format(
                                $normal_stock
                            ) ?>

                            <span>

                                barang

                            </span>

                        </strong>

                    </div>


                    <div class="status-progress">

                        <div
                            class="status-progress-bar status-progress-normal"
                            style="width: <?= $normal_percentage ?>%;"
                        ></div>

                    </div>


                </div>


            </div>


            <!-- MENIPIS -->

            <div class="stock-status-item">


                <div class="status-circle status-warning">

                    !

                </div>


                <div class="status-main">


                    <div class="status-title-row">


                        <div>

                            <strong>

                                Menipis

                            </strong>


                            <small>

                                Stok mencapai batas minimum

                            </small>

                        </div>


                        <strong class="status-number">

                            <?= number_format(
                                $low_stock
                            ) ?>

                            <span>

                                barang

                            </span>

                        </strong>

                    </div>


                    <div class="status-progress">

                        <div
                            class="status-progress-bar status-progress-warning"
                            style="width: <?= $low_percentage ?>%;"
                        ></div>

                    </div>


                </div>


            </div>


            <!-- HABIS -->

            <div class="stock-status-item">


                <div class="status-circle status-danger">

                    ×

                </div>


                <div class="status-main">


                    <div class="status-title-row">


                        <div>

                            <strong>

                                Habis

                            </strong>


                            <small>

                                Stok sama dengan 0

                            </small>

                        </div>


                        <strong class="status-number">

                            <?= number_format(
                                $out_stock
                            ) ?>

                            <span>

                                barang

                            </span>

                        </strong>

                    </div>


                    <div class="status-progress">

                        <div
                            class="status-progress-bar status-progress-danger"
                            style="width: <?= $out_percentage ?>%;"
                        ></div>

                    </div>


                </div>


            </div>


        </div>


    </section>


    <!-- =====================================================
         FILTER TRANSAKSI
    ====================================================== -->

    <section class="stock-section">


        <div class="stock-section-header">

            <h2>

                Filter Transaksi

            </h2>


            <p>

                Cari riwayat berdasarkan jenis transaksi,
                barang, dan periode tanggal.

            </p>

        </div>


        <form
            method="GET"
            action=""
            class="stock-filter"
        >


            <div class="filter-field">


                <label>

                    Jenis Transaksi

                </label>


                <select
                    name="type"
                    class="form-select"
                >

                    <option value="">

                        Semua Transaksi

                    </option>


                    <option
                        value="IN"

                        <?= $filter_type === 'IN'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Stok Masuk

                    </option>


                    <option
                        value="OUT"

                        <?= $filter_type === 'OUT'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Stok Keluar

                    </option>


                </select>


            </div>


            <div class="filter-field">


                <label>

                    Barang

                </label>


                <select
                    name="product_id"
                    class="form-select"
                >

                    <option value="">

                        Semua Barang

                    </option>


                    <?php foreach (
                        $products
                        as $product
                    ): ?>


                        <option
                            value="<?= (int) $product['id'] ?>"

                            <?= $filter_product ===
                                (int) $product['id']
                                    ? 'selected'
                                    : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $product['code']
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $product['name']
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


            </div>


            <div class="filter-field">


                <label>

                    Dari

                </label>


                <input
                    type="date"
                    name="date_from"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $filter_date_from
                    ) ?>"
                >


            </div>


            <div class="filter-field">


                <label>

                    Sampai

                </label>


                <input
                    type="date"
                    name="date_to"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $filter_date_to
                    ) ?>"
                >


            </div>


            <div class="filter-actions">


                <button
                    type="submit"
                    class="btn-filter"
                >

                    Filter

                </button>


                <a
                    href="<?= BASE_URL ?>/stock/index.php"
                    class="btn-reset"
                >

                    Reset

                </a>


            </div>


        </form>


    </section>


    <!-- =====================================================
         RIWAYAT TRANSAKSI
    ====================================================== -->

    <section class="stock-section stock-history-section">


        <div class="stock-section-header history-header">


            <div>

                <h2>

                    Riwayat Transaksi

                </h2>


                <p>

                    Daftar seluruh transaksi stok masuk
                    dan stok keluar.

                </p>

            </div>


            <div class="transaction-count">

                <?= number_format(
                    count($movements)
                ) ?>

                transaksi

            </div>


        </div>


        <?php if ($movements): ?>


            <div class="stock-table-wrapper">


                <table class="stock-table">


                    <thead>

                        <tr>

                            <th>

                                No

                            </th>

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
                        $movements
                        as $index => $movement
                    ): ?>


                        <tr>


                            <td>

                                <?= $index + 1 ?>

                            </td>


                            <td>

                                <div class="transaction-date">

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $movement[
                                                'created_at'
                                            ]
                                        )
                                    ) ?>


                                    <small>

                                        <?= date(
                                            'H:i',
                                            strtotime(
                                                $movement[
                                                    'created_at'
                                                ]
                                            )
                                        ) ?>

                                    </small>

                                </div>

                            </td>


                            <td>

                                <div class="product-cell">

                                    <strong>

                                        <?= htmlspecialchars(
                                            $movement[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </strong>


                                    <span>

                                        <?= htmlspecialchars(
                                            $movement[
                                                'product_code'
                                            ]
                                        ) ?>

                                    </span>

                                </div>

                            </td>


                            <td>


                                <?php if (
                                    $movement['type']
                                    === 'IN'
                                ): ?>


                                    <span
                                        class="transaction-badge badge-in"
                                    >

                                        Stok Masuk

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="transaction-badge badge-out"
                                    >

                                        Stok Keluar

                                    </span>


                                <?php endif; ?>


                            </td>


                            <td>


                                <strong
                                    class="<?= $movement['type'] === 'IN'
                                        ? 'quantity-in'
                                        : 'quantity-out'
                                    ?>"
                                >

                                    <?= $movement['type']
                                        === 'IN'
                                            ? '+'
                                            : '-'
                                    ?><?= number_format(
                                        (int) $movement[
                                            'quantity'
                                        ]
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $movement['unit']
                                    ) ?>

                                </strong>


                            </td>


                            <td>


                                <?php if (
                                    !empty(
                                        $movement[
                                            'description'
                                        ]
                                    )
                                ): ?>


                                    <?= htmlspecialchars(
                                        $movement[
                                            'description'
                                        ]
                                    ) ?>


                                <?php else: ?>


                                    <span class="text-muted">

                                        -

                                    </span>


                                <?php endif; ?>


                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $movement[
                                        'user_name'
                                    ]
                                    ?? '-'
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div class="stock-empty-state">


                <div class="empty-icon">

                    ▤

                </div>


                <h3>

                    Belum Ada Transaksi

                </h3>


                <p>

                    Belum ada transaksi stok yang
                    tercatat.

                </p>


                <div class="empty-actions">


                    <button
                        type="button"
                        class="btn-stock btn-stock-in"
                        onclick="openStockModal('IN')"
                    >

                        + Stok Masuk

                    </button>


                    <button
                        type="button"
                        class="btn-stock btn-stock-out"
                        onclick="openStockModal('OUT')"
                    >

                        − Stok Keluar

                    </button>


                </div>


            </div>


        <?php endif; ?>


    </section>


</main>


<!-- =========================================================
     MODAL TRANSAKSI STOK
========================================================== -->

<div
    class="modal fade"
    id="stockTransactionModal"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered"
    >


        <div class="modal-content stock-modal">


            <!-- MODAL HEADER -->

            <div class="stock-modal-header">


                <div>


                    <div
                        class="modal-type-icon"
                        id="modalTypeIcon"
                    >

                        +

                    </div>


                    <div>


                        <h5 id="stockModalTitle">

                            Tambah Stok Masuk

                        </h5>


                        <p id="stockModalSubtitle">

                            Tambahkan stok barang
                            ke inventori.

                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>


            </div>


            <!-- FORM -->

            <form
                method="POST"
                action=""
            >


                <div class="modal-body stock-modal-body">


                    <input
                        type="hidden"
                        name="type"
                        id="stock_type"
                        value="IN"
                    >


                    <!-- BARANG -->

                    <div class="modal-field">


                        <label
                            for="modal_product_id"
                        >

                            Barang

                            <span>

                                *

                            </span>

                        </label>


                        <select
                            name="product_id"
                            id="modal_product_id"
                            class="form-select"
                            required
                        >


                            <option value="">

                                -- Pilih Barang --

                            </option>


                            <?php foreach (
                                $products
                                as $product
                            ): ?>


                                <option
                                    value="<?= (int) $product['id'] ?>"
                                    data-stock="<?= (int) $product['stock'] ?>"
                                    data-unit="<?= htmlspecialchars(
                                        $product['unit'],
                                        ENT_QUOTES
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $product['code']
                                    ) ?>

                                    -

                                    <?= htmlspecialchars(
                                        $product['name']
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                    <!-- STOK SAAT INI -->

                    <div
                        id="currentStockInfo"
                        class="current-stock-box"
                    >

                        <span>

                            Stok saat ini

                        </span>


                        <strong
                            id="currentStockValue"
                        >

                            0

                        </strong>

                    </div>


                    <!-- JUMLAH -->

                    <div class="modal-field">


                        <label
                            for="modal_quantity"
                        >

                            Jumlah

                            <span>

                                *

                            </span>

                        </label>


                        <input
                            type="number"
                            name="quantity"
                            id="modal_quantity"
                            class="form-control"
                            min="1"
                            placeholder="Masukkan jumlah"
                            required
                        >


                    </div>


                    <!-- KETERANGAN -->

                    <div class="modal-field">


                        <label
                            for="modal_description"
                        >

                            Keterangan

                        </label>


                        <textarea
                            name="description"
                            id="modal_description"
                            class="form-control"
                            rows="3"
                            placeholder="Contoh: Pembelian barang"
                        ></textarea>


                    </div>


                </div>


                <!-- MODAL FOOTER -->

                <div class="stock-modal-footer">


                    <button
                        type="button"
                        class="btn-modal-cancel"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn-modal-submit btn-submit-in"
                        id="stockSubmitButton"
                    >

                        Simpan Stok Masuk

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<script>

/*
|--------------------------------------------------------------------------
| OPEN MODAL
|--------------------------------------------------------------------------
*/

function openStockModal(type) {

    const modalElement =
        document.getElementById(
            'stockTransactionModal'
        );


    const modal =
        bootstrap.Modal.getOrCreateInstance(
            modalElement
        );


    const typeInput =
        document.getElementById(
            'stock_type'
        );


    const title =
        document.getElementById(
            'stockModalTitle'
        );


    const subtitle =
        document.getElementById(
            'stockModalSubtitle'
        );


    const icon =
        document.getElementById(
            'modalTypeIcon'
        );


    const submit =
        document.getElementById(
            'stockSubmitButton'
        );


    const product =
        document.getElementById(
            'modal_product_id'
        );


    const quantity =
        document.getElementById(
            'modal_quantity'
        );


    const description =
        document.getElementById(
            'modal_description'
        );


    typeInput.value = type;


    product.value = '';

    quantity.value = '';

    description.value = '';


    updateCurrentStock();


    /*
    |--------------------------------------------------------------------------
    | STOK MASUK
    |--------------------------------------------------------------------------
    */

    if (type === 'IN') {

        title.innerText =
            'Tambah Stok Masuk';

        subtitle.innerText =
            'Tambahkan stok barang ke inventori.';

        icon.innerText =
            '+';

        icon.className =
            'modal-type-icon modal-icon-in';

        submit.innerText =
            'Simpan Stok Masuk';

        submit.className =
            'btn-modal-submit btn-submit-in';

    }


    /*
    |--------------------------------------------------------------------------
    | STOK KELUAR
    |--------------------------------------------------------------------------
    */

    else {

        title.innerText =
            'Tambah Stok Keluar';

        subtitle.innerText =
            'Kurangi stok barang dari inventori.';

        icon.innerText =
            '−';

        icon.className =
            'modal-type-icon modal-icon-out';

        submit.innerText =
            'Simpan Stok Keluar';

        submit.className =
            'btn-modal-submit btn-submit-out';

    }


    modal.show();

}


/*
|--------------------------------------------------------------------------
| UPDATE CURRENT STOCK
|--------------------------------------------------------------------------
*/

function updateCurrentStock() {

    const select =
        document.getElementById(
            'modal_product_id'
        );


    const info =
        document.getElementById(
            'currentStockInfo'
        );


    const value =
        document.getElementById(
            'currentStockValue'
        );


    if (!select.value) {

        info.classList.remove(
            'show'
        );

        return;

    }


    const option =
        select.options[
            select.selectedIndex
        ];


    const stock =
        Number(
            option.dataset.stock || 0
        );


    const unit =
        option.dataset.unit || '';


    value.innerText =
        stock.toLocaleString(
            'id-ID'
        )
        + ' '
        + unit;


    info.classList.add(
        'show'
    );

}


/*
|--------------------------------------------------------------------------
| PRODUCT CHANGE
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        'modal_product_id'
    )
    .addEventListener(
        'change',
        updateCurrentStock
    );


/*
|--------------------------------------------------------------------------
| VALIDASI STOK KELUAR DI FRONTEND
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        'modal_quantity'
    )
    .addEventListener(
        'input',
        function () {

            const type =
                document.getElementById(
                    'stock_type'
                ).value;


            const select =
                document.getElementById(
                    'modal_product_id'
                );


            const option =
                select.options[
                    select.selectedIndex
                ];


            if (
                type !== 'OUT' ||
                !select.value ||
                !option
            ) {

                this.setCustomValidity('');

                return;

            }


            const stock =
                Number(
                    option.dataset.stock || 0
                );


            const quantity =
                Number(
                    this.value || 0
                );


            if (
                quantity > stock
            ) {

                this.setCustomValidity(
                    'Jumlah stok keluar melebihi stok yang tersedia.'
                );

            } else {

                this.setCustomValidity('');

            }

        }
    );


/*
|--------------------------------------------------------------------------
| ERROR -> BUKA MODAL KEMBALI
|--------------------------------------------------------------------------
*/

<?php if ($errors): ?>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const type =
            <?= json_encode(
                $old['type']
            ) ?>;


        const product =
            <?= json_encode(
                (string) $old['product_id']
            ) ?>;


        const quantity =
            <?= json_encode(
                (string) $old['quantity']
            ) ?>;


        const description =
            <?= json_encode(
                $old['description']
            ) ?>;


        openStockModal(type);


        document
            .getElementById(
                'modal_product_id'
            )
            .value = product;


        document
            .getElementById(
                'modal_quantity'
            )
            .value = quantity;


        document
            .getElementById(
                'modal_description'
            )
            .value = description;


        updateCurrentStock();

    }
);

<?php endif; ?>


</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>

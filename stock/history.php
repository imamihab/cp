<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$page_title = 'Riwayat Stok';


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$type = $_GET['type'] ?? '';

$product_id = (int) (
    $_GET['product_id'] ?? 0
);

$date_from = $_GET['date_from'] ?? '';

$date_to = $_GET['date_to'] ?? '';


/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


/*
|--------------------------------------------------------------------------
| FILTER TYPE
|--------------------------------------------------------------------------
*/

if (
    $type === 'IN' ||
    $type === 'OUT'
) {

    $where[] = 'sm.type = ?';

    $params[] = $type;

}


/*
|--------------------------------------------------------------------------
| FILTER PRODUCT
|--------------------------------------------------------------------------
*/

if ($product_id > 0) {

    $where[] =
        'sm.product_id = ?';

    $params[] =
        $product_id;

}


/*
|--------------------------------------------------------------------------
| FILTER DATE FROM
|--------------------------------------------------------------------------
*/

if ($date_from !== '') {

    $where[] =
        'DATE(sm.created_at) >= ?';

    $params[] =
        $date_from;

}


/*
|--------------------------------------------------------------------------
| FILTER DATE TO
|--------------------------------------------------------------------------
*/

if ($date_to !== '') {

    $where[] =
        'DATE(sm.created_at) <= ?';

    $params[] =
        $date_to;

}


/*
|--------------------------------------------------------------------------
| WHERE
|--------------------------------------------------------------------------
*/

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
| AMBIL RIWAYAT
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
| PRODUCTS
|--------------------------------------------------------------------------
*/

$products = $pdo
    ->query("
        SELECT
            id,
            code,
            name

        FROM products

        ORDER BY name ASC
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

    <!-- PAGE HEADER -->

    <div class="page-header">


        <div>

            <h1 class="page-title">

                Riwayat Stok

            </h1>


            <p class="page-subtitle">

                Lihat seluruh transaksi stok masuk dan
                stok keluar.

            </p>

        </div>


    </div>


    <!-- FILTER -->

    <div class="table-card mb-4">


        <div class="table-card-header">


            <div>

                <h5>

                    Filter Riwayat

                </h5>

            </div>


        </div>


        <div class="table-card-body">


            <form
                method="GET"
                action=""
            >


                <div class="row">


                    <!-- TIPE -->

                    <div class="col-md-3 mb-3">


                        <label
                            class="form-label"
                        >

                            Jenis Transaksi

                        </label>


                        <select
                            name="type"
                            class="form-select"
                        >

                            <option value="">

                                Semua

                            </option>


                            <option
                                value="IN"

                                <?= $type === 'IN'
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                Stok Masuk

                            </option>


                            <option
                                value="OUT"

                                <?= $type === 'OUT'
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                Stok Keluar

                            </option>


                        </select>


                    </div>


                    <!-- BARANG -->

                    <div class="col-md-3 mb-3">


                        <label
                            class="form-label"
                        >

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

                                    <?= $product_id ===
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


                    <!-- DARI -->

                    <div class="col-md-2 mb-3">


                        <label
                            class="form-label"
                        >

                            Dari

                        </label>


                        <input
                            type="date"
                            name="date_from"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $date_from
                            ) ?>"
                        >


                    </div>


                    <!-- SAMPAI -->

                    <div class="col-md-2 mb-3">


                        <label
                            class="form-label"
                        >

                            Sampai

                        </label>


                        <input
                            type="date"
                            name="date_to"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $date_to
                            ) ?>"
                        >


                    </div>


                    <!-- BUTTON -->

                    <div
                        class="col-md-2 mb-3 d-flex align-items-end"
                    >


                        <div
                            class="d-flex gap-2 w-100"
                        >


                            <button
                                type="submit"
                                class="btn btn-primary flex-fill"
                            >

                                Filter

                            </button>


                            <a
                                href="<?= BASE_URL ?>/stock/history.php"
                                class="btn btn-light border"
                            >

                                Reset

                            </a>


                        </div>


                    </div>


                </div>


            </form>


        </div>


    </div>


    <!-- TABLE -->

    <div class="table-card">


        <div class="table-card-header">


            <div>

                <h5>

                    Riwayat Transaksi

                </h5>


                <small class="text-secondary">

                    Total

                    <?= count(
                        $movements
                    ) ?>

                    transaksi

                </small>

            </div>


        </div>


        <div class="table-card-body">


            <?php if ($movements): ?>


                <div class="table-responsive">


                    <table class="table">


                        <thead>

                            <tr>

                                <th width="60">
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

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $movement[
                                                'created_at'
                                            ]
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $movement[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </strong>


                                    <br>


                                    <small
                                        class="text-secondary"
                                    >

                                        <?= htmlspecialchars(
                                            $movement[
                                                'product_code'
                                            ]
                                        ) ?>

                                    </small>

                                </td>


                                <td>


                                    <?php if (
                                        $movement['type']
                                        === 'IN'
                                    ): ?>


                                        <span
                                            class="badge text-bg-success"
                                        >

                                            Stok Masuk

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="badge text-bg-danger"
                                        >

                                            Stok Keluar

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <td>


                                    <strong>

                                        <?= $movement['type']
                                            === 'IN'
                                                ? '+'
                                                : '-'
                                        ?><?= number_format(
                                            (int) $movement[
                                                'quantity'
                                            ]
                                        ) ?>

                                    </strong>


                                    <?= htmlspecialchars(
                                        $movement['unit']
                                    ) ?>


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


                                        <span
                                            class="text-secondary"
                                        >

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


                <div class="empty-state">

                    Tidak ada transaksi stok
                    yang ditemukan.

                </div>


            <?php endif; ?>


        </div>


    </div>


</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
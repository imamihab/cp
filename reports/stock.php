<?php

require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$page_title = 'Laporan Stok';

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$category_id = isset($_GET['category_id'])
    ? (int) $_GET['category_id']
    : 0;

$status = $_GET['status'] ?? 'all';

$keyword = trim($_GET['keyword'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDASI STATUS
|--------------------------------------------------------------------------
*/

$allowed_status = [
    'all',
    'normal',
    'menipis',
    'habis'
];

if (!in_array($status, $allowed_status, true)) {
    $status = 'all';
}

/*
|--------------------------------------------------------------------------
| DATA KATEGORI
|--------------------------------------------------------------------------
*/

$category_stmt = $pdo->query("
    SELECT
        id,
        name
    FROM categories
    ORDER BY name ASC
");

$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| QUERY PRODUK
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.code,
        p.name,
        p.unit,
        p.minimum_stock,
        p.stock,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON c.id = p.category_id
    WHERE 1 = 1
";

$params = [];


/*
|--------------------------------------------------------------------------
| FILTER KATEGORI
|--------------------------------------------------------------------------
*/

if ($category_id > 0) {

    $sql .= "
        AND p.category_id = :category_id
    ";

    $params['category_id'] = $category_id;
}


/*
|--------------------------------------------------------------------------
| FILTER KEYWORD
|--------------------------------------------------------------------------
*/

if ($keyword !== '') {

    $sql .= "
        AND (
            p.code LIKE :keyword_code
            OR p.name LIKE :keyword_name
        )
    ";

    $keyword_param = '%' . $keyword . '%';
    $params['keyword_code'] = $keyword_param;
    $params['keyword_name'] = $keyword_param;
}


/*
|--------------------------------------------------------------------------
| FILTER STATUS
|--------------------------------------------------------------------------
*/

if ($status === 'normal') {

    $sql .= "
        AND p.stock > p.minimum_stock
    ";
}

if ($status === 'menipis') {

    $sql .= "
        AND p.stock > 0
        AND p.stock <= p.minimum_stock
    ";
}

if ($status === 'habis') {

    $sql .= "
        AND p.stock = 0
    ";
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        p.name ASC
";


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| HITUNG SUMMARY
|--------------------------------------------------------------------------
*/

$total_products = count($products);

$total_stock = 0;
$total_normal = 0;
$total_menipis = 0;
$total_habis = 0;

foreach ($products as $product) {

    $stock = (int) $product['stock'];
    $minimum_stock = (int) $product['minimum_stock'];

    $total_stock += $stock;

    if ($stock === 0) {

        $total_habis++;

    } elseif ($stock <= $minimum_stock) {

        $total_menipis++;

    } else {

        $total_normal++;
    }
}


/*
|--------------------------------------------------------------------------
| HELPER STATUS
|--------------------------------------------------------------------------
*/

function stock_status(int $stock, int $minimum_stock): array
{
    if ($stock === 0) {

        return [
            'label' => 'Habis',
            'class' => 'report-status-danger'
        ];
    }

    if ($stock <= $minimum_stock) {

        return [
            'label' => 'Menipis',
            'class' => 'report-status-warning'
        ];
    }

    return [
        'label' => 'Normal',
        'class' => 'report-status-success'
    ];
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>


<main class="content report-stock-page">

    <?php require_once dirname(__DIR__) . '/includes/topbar.php'; ?>

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="report-page-header">

        <div>
            <div>
                <h1>Laporan Stok</h1>
                <p>Ringkasan kondisi dan ketersediaan barang.</p>
            </div>
        </div>


        <div class="report-header-actions">

            <button
                type="button"
                class="report-btn report-btn-print"
                onclick="window.print()"
            >
                Cetak Laporan

            </button>

        </div>

    </div>


    <!-- =====================================================
         SUMMARY
    ====================================================== -->

    <div class="report-summary-grid">


        <!-- TOTAL BARANG -->

        <div class="report-summary-card">

            <div class="report-summary-icon report-icon-blue">
                Inventori
            </div>

            <div>

                <span>
                    Total Barang
                </span>

                <strong>
                    <?= number_format($total_products) ?>
                </strong>

                <small>
                    Jenis barang
                </small>

            </div>

        </div>


        <!-- TOTAL STOK -->

        <div class="report-summary-card">

            <div class="report-summary-icon report-icon-green">
                Total
            </div>

            <div>

                <span>
                    Total Stok
                </span>

                <strong>
                    <?= number_format($total_stock) ?>
                </strong>

                <small>
                    Seluruh unit stok
                </small>

            </div>

        </div>


        <!-- MENIPIS -->

        <div class="report-summary-card">

            <div class="report-summary-icon report-icon-yellow">
                !
            </div>

            <div>

                <span>
                    Stok Menipis
                </span>

                <strong>
                    <?= number_format($total_menipis) ?>
                </strong>

                <small>
                    Perlu perhatian
                </small>

            </div>

        </div>


        <!-- HABIS -->

        <div class="report-summary-card">

            <div class="report-summary-icon report-icon-red">
                -
            </div>

            <div>

                <span>
                    Stok Habis
                </span>

                <strong>
                    <?= number_format($total_habis) ?>
                </strong>

                <small>
                    Tidak tersedia
                </small>

            </div>

        </div>

    </div>


    <!-- =====================================================
         FILTER
    ====================================================== -->

    <section class="report-section">

        <div class="report-section-header">

            <div>

                <h2>
                    Filter Laporan
                </h2>

                <p>
                    Gunakan filter untuk menampilkan data stok tertentu.
                </p>

            </div>

        </div>


        <form
            method="GET"
            action=""
            class="report-filter"
        >


            <!-- SEARCH -->

            <div class="report-filter-field">

                <label for="keyword">
                    Cari Barang
                </label>

                <input
                    type="text"
                    id="keyword"
                    name="keyword"
                    class="form-control"
                    placeholder="Kode atau nama barang"
                    value="<?= htmlspecialchars($keyword) ?>"
                >

            </div>


            <!-- CATEGORY -->

            <div class="report-filter-field">

                <label for="category_id">
                    Kategori
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    class="form-select"
                >

                    <option value="0">
                        Semua Kategori
                    </option>

                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= $category_id === (int) $category['id']
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars(
                                $category['name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- STATUS -->

            <div class="report-filter-field">

                <label for="status">
                    Status Stok
                </label>

                <select
                    id="status"
                    name="status"
                    class="form-select"
                >

                    <option
                        value="all"
                        <?= $status === 'all'
                            ? 'selected'
                            : '' ?>
                    >
                        Semua Status
                    </option>

                    <option
                        value="normal"
                        <?= $status === 'normal'
                            ? 'selected'
                            : '' ?>
                    >
                        Normal
                    </option>

                    <option
                        value="menipis"
                        <?= $status === 'menipis'
                            ? 'selected'
                            : '' ?>
                    >
                        Menipis
                    </option>

                    <option
                        value="habis"
                        <?= $status === 'habis'
                            ? 'selected'
                            : '' ?>
                    >
                        Habis
                    </option>

                </select>

            </div>


            <!-- BUTTON -->

            <div class="report-filter-actions">

                <button
                    type="submit"
                    class="report-btn report-btn-filter"
                >
                    Filter
                </button>


                <a
                    href="<?= BASE_URL ?>/reports/stock.php"
                    class="report-btn report-btn-reset"
                >
                    Reset
                </a>

            </div>

        </form>

    </section>


    <!-- =====================================================
         TABLE
    ====================================================== -->

    <section class="report-section report-table-section">

        <div class="report-section-header report-table-header">

            <div>

                <h2>
                    Data Stok Barang
                </h2>

                <p>
                    Menampilkan <?= number_format($total_products) ?>
                    jenis barang.
                </p>

            </div>

            <div class="report-count">

                <?= number_format($total_products) ?>
                barang

            </div>

        </div>


        <?php if (empty($products)): ?>

            <div class="report-empty">

                <div class="report-empty-icon">
                    Inventori
                </div>

                <h3>
                    Tidak ada data
                </h3>

                <p>
                    Data stok yang sesuai dengan filter tidak ditemukan.
                </p>

            </div>

        <?php else: ?>

            <div class="report-table-wrapper">

                <table class="report-table">

                    <thead>

                        <tr>

                            <th width="55">
                                No
                            </th>

                            <th>
                                Kode
                            </th>

                            <th>
                                Barang
                            </th>

                            <th>
                                Kategori
                            </th>

                            <th class="text-center">
                                Stok
                            </th>

                            <th class="text-center">
                                Minimum
                            </th>

                            <th class="text-center">
                                Satuan
                            </th>

                            <th class="text-center">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($products as $index => $product): ?>

                            <?php

                            $stock = (int) $product['stock'];

                            $minimum_stock =
                                (int) $product['minimum_stock'];

                            $status_data =
                                stock_status(
                                    $stock,
                                    $minimum_stock
                                );

                            ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>


                                <td>

                                    <span class="report-code">

                                        <?= htmlspecialchars(
                                            $product['code']
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="report-product">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $product['name']
                                            ) ?>

                                        </strong>

                                    </div>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $product['category_name']
                                        ?? '-'
                                    ) ?>

                                </td>


                                <td class="text-center">

                                    <strong
                                        class="<?= $stock === 0
                                            ? 'report-stock-empty'
                                            : '' ?>"
                                    >

                                        <?= number_format($stock) ?>

                                    </strong>

                                </td>


                                <td class="text-center">

                                    <?= number_format(
                                        $minimum_stock
                                    ) ?>

                                </td>


                                <td class="text-center">

                                    <?= htmlspecialchars(
                                        $product['unit']
                                    ) ?>

                                </td>


                                <td class="text-center">

                                    <span
                                        class="report-status <?= $status_data['class'] ?>"
                                    >

                                        <?= $status_data['label'] ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>


    <!-- =====================================================
         PRINT FOOTER
    ====================================================== -->

    <div class="report-print-footer">

        Dicetak pada:
        <?= date('d-m-Y H:i') ?>

    </div>


</main>





<?php require_once __DIR__ . '/../includes/footer.php'; ?>

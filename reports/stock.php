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
            p.code LIKE :keyword
            OR p.name LIKE :keyword
        )
    ";

    $params['keyword'] = '%' . $keyword . '%';
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

    <!-- =====================================================
         TOP HEADER
    ====================================================== -->

    <div class="top-header">

        <div class="top-header-title">

            <h5>
                Laporan Stok
            </h5>

            <span>
                Sistem Informasi Manajemen Inventori
            </span>

        </div>


        <div class="top-header-user">

            <div class="top-user-avatar">

                <?= strtoupper(
                    substr(
                        $_SESSION['user']['name'] ?? 'A',
                        0,
                        1
                    )
                ) ?>

            </div>

            <div class="top-user-info">

                <strong>
                    <?= htmlspecialchars(
                        $_SESSION['user']['name'] ?? 'Administrator'
                    ) ?>
                </strong>

                <small>
                    <?= htmlspecialchars(
                        $_SESSION['user']['role'] ?? 'admin'
                    ) ?>
                </small>

            </div>

        </div>

    </div>


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="report-page-header">

        <div>

            

        </div>


        <div class="report-header-actions">

            <button
                type="button"
                class="report-btn report-btn-print"
                onclick="window.print()"
            >
                <span>
                    🖨
                </span>

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
                ▤
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
                ◉
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
                ×
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
                    ▤
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


<style>

/* =========================================================
   REPORT STOCK
========================================================= */

.report-stock-page {
    padding-bottom: 40px;
}


/* PAGE HEADER */

.report-page-header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 22px;
}

.report-page-header h1 {
    margin: 0 0 5px;

    color: #101828;

    font-size: 28px;

    font-weight: 700;
}

.report-page-header p {
    margin: 0;

    color: #667085;

    font-size: 14px;
}

.report-header-actions {
    display: flex;

    align-items: center;

    gap: 8px;
}


/* BUTTON */

.report-btn {
    min-height: 41px;

    padding: 0 16px;

    border-radius: 8px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 7px;

    border: 0;

    font-family: inherit;

    font-size: 13px;

    font-weight: 600;

    text-decoration: none;

    cursor: pointer;

    box-sizing: border-box;
}

.report-btn-print {
    background: #344054;

    color: #fff;
}

.report-btn-print:hover {
    background: #1d2939;

    color: #fff;
}

.report-btn-filter {
    background: #2563eb;

    color: #fff;
}

.report-btn-filter:hover {
    background: #1d4ed8;
}

.report-btn-reset {
    border: 1px solid #d0d5dd;

    background: #fff;

    color: #344054;
}

.report-btn-reset:hover {
    background: #f9fafb;

    color: #101828;
}


/* SUMMARY */

.report-summary-grid {
    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 17px;

    margin-bottom: 22px;
}

.report-summary-card {
    min-height: 118px;

    padding: 19px;

    background: #fff;

    border: 1px solid #eaecf0;

    border-radius: 13px;

    display: flex;

    align-items: center;

    gap: 14px;

    box-shadow:
        0 3px 12px rgba(16, 24, 40, .04);
}

.report-summary-icon {
    width: 48px;
    height: 48px;

    min-width: 48px;

    border-radius: 11px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 20px;

    font-weight: 700;
}

.report-icon-blue {
    background: #eef4ff;

    color: #2563eb;
}

.report-icon-green {
    background: #ecfdf3;

    color: #039855;
}

.report-icon-yellow {
    background: #fffaeb;

    color: #f79009;
}

.report-icon-red {
    background: #fef3f2;

    color: #d92d20;
}

.report-summary-card span {
    display: block;

    margin-bottom: 3px;

    color: #667085;

    font-size: 12px;
}

.report-summary-card strong {
    display: block;

    color: #101828;

    font-size: 25px;

    line-height: 1.2;
}

.report-summary-card small {
    display: block;

    margin-top: 4px;

    color: #98a2b3;

    font-size: 10px;
}


/* SECTION */

.report-section {
    margin-bottom: 22px;

    overflow: hidden;

    background: #fff;

    border: 1px solid #eaecf0;

    border-radius: 13px;

    box-shadow:
        0 3px 12px rgba(16, 24, 40, .04);
}

.report-section-header {
    padding: 18px 21px;

    border-bottom: 1px solid #eaecf0;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}

.report-section-header h2 {
    margin: 0;

    color: #101828;

    font-size: 16px;

    font-weight: 700;
}

.report-section-header p {
    margin: 4px 0 0;

    color: #667085;

    font-size: 12px;
}


/* FILTER */

.report-filter {
    padding: 19px 21px;

    display: grid;

    grid-template-columns:
        minmax(180px, 1.4fr)
        minmax(160px, 1fr)
        minmax(160px, 1fr)
        auto;

    gap: 13px;

    align-items: end;
}

.report-filter-field label {
    display: block;

    margin-bottom: 6px;

    color: #344054;

    font-size: 12px;

    font-weight: 600;
}

.report-filter-field .form-control,
.report-filter-field .form-select {
    width: 100%;

    height: 42px;

    padding: 0 12px;

    border: 1px solid #d0d5dd;

    border-radius: 8px;

    color: #344054;

    font-family: inherit;

    font-size: 13px;

    box-shadow: none;

    box-sizing: border-box;
}

.report-filter-field .form-control:focus,
.report-filter-field .form-select:focus {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px rgba(37, 99, 235, .10);
}

.report-filter-actions {
    display: flex;

    gap: 7px;
}


/* TABLE */

.report-table-section {
    overflow: hidden;
}

.report-table-header {
    min-height: 72px;
}

.report-count {
    padding: 6px 11px;

    background: #f2f4f7;

    border-radius: 999px;

    color: #475467;

    font-size: 11px;

    font-weight: 600;

    white-space: nowrap;
}

.report-table-wrapper {
    width: 100%;

    overflow-x: auto;
}

.report-table {
    width: 100%;

    min-width: 850px;

    border-collapse: collapse;
}

.report-table thead th {
    padding: 12px 15px;

    background: #f8fafc;

    border-bottom: 1px solid #eaecf0;

    color: #475467;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    white-space: nowrap;
}

.report-table tbody td {
    padding: 13px 15px;

    border-bottom: 1px solid #f2f4f7;

    color: #475467;

    font-size: 12px;

    vertical-align: middle;
}

.report-table tbody tr:last-child td {
    border-bottom: 0;
}

.report-table tbody tr:hover {
    background: #f9fafb;
}

.report-code {
    color: #667085;

    font-size: 11px;

    font-family: monospace;
}

.report-product strong {
    color: #101828;

    font-size: 12px;
}

.report-stock-empty {
    color: #d92d20;
}


/* STATUS */

.report-status {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    min-width: 68px;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 10px;

    font-weight: 600;
}

.report-status-success {
    background: #ecfdf3;

    color: #027a48;
}

.report-status-warning {
    background: #fffaeb;

    color: #b54708;
}

.report-status-danger {
    background: #fef3f2;

    color: #b42318;
}


/* EMPTY */

.report-empty {
    padding: 60px 20px;

    text-align: center;
}

.report-empty-icon {
    width: 58px;
    height: 58px;

    margin: 0 auto 14px;

    background: #f2f4f7;

    border-radius: 14px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 24px;
}

.report-empty h3 {
    margin: 0;

    color: #101828;

    font-size: 16px;
}

.report-empty p {
    margin: 6px 0 0;

    color: #98a2b3;

    font-size: 12px;
}


/* PRINT FOOTER */

.report-print-footer {
    display: none;
}


/* RESPONSIVE */

@media (max-width: 1200px) {

    .report-summary-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .report-filter {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .report-filter-actions {
        grid-column: span 2;

        justify-content: flex-end;
    }

}


@media (max-width: 768px) {

    .report-page-header {
        align-items: flex-start;

        flex-direction: column;
    }

    .report-header-actions {
        width: 100%;
    }

    .report-btn-print {
        width: 100%;
    }

    .report-summary-grid {
        grid-template-columns: 1fr;
    }

    .report-filter {
        grid-template-columns: 1fr;
    }

    .report-filter-actions {
        grid-column: auto;

        width: 100%;
    }

    .report-filter-actions .report-btn {
        flex: 1;
    }

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    @page {
        size: A4 landscape;

        margin: 12mm;
    }

    body {
        background: #fff !important;
    }

    .sidebar,
    .top-header,
    .report-header-actions,
    .report-filter,
    .report-summary-grid,
    .report-count {
        display: none !important;
    }

    .content {
        width: 100% !important;

        margin: 0 !important;

        padding: 0 !important;
    }

    .report-stock-page {
        padding: 0 !important;
    }

    .report-page-header {
        display: block;

        margin-bottom: 20px;

        border-bottom: 2px solid #101828;

        padding-bottom: 12px;
    }

    .report-page-header h1 {
        font-size: 22px;
    }

    .report-page-header p {
        font-size: 11px;
    }

    .report-section {
        border: 0;

        box-shadow: none;

        margin-bottom: 0;
    }

    .report-section-header {
        padding: 0 0 10px;

        border-bottom: 1px solid #d0d5dd;
    }

    .report-table {
        min-width: 0;
    }

    .report-table thead th {
        background: #f2f4f7 !important;

        color: #101828 !important;

        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .report-table tbody td {
        padding: 8px 10px;

        font-size: 10px;
    }

    .report-status {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .report-print-footer {
        display: block;

        margin-top: 15px;

        padding-top: 8px;

        border-top: 1px solid #d0d5dd;

        color: #667085;

        font-size: 9px;

        text-align: right;
    }

}

</style>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
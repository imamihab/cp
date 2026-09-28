<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$page_title = 'Master Barang';

$errors = [];

$success = $_GET['success'] ?? '';

$old = [
    'code' => '',
    'name' => '',
    'category_id' => '',
    'unit' => '',
    'minimum_stock' => '0',
    'stock' => '0',
];


/*
|--------------------------------------------------------------------------
| HANDLE POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | TAMBAH BARANG
    |--------------------------------------------------------------------------
    */

    if ($action === 'create') {

        $old['code'] = trim($_POST['code'] ?? '');
        $old['name'] = trim($_POST['name'] ?? '');
        $old['category_id'] = (int) ($_POST['category_id'] ?? 0);
        $old['unit'] = trim($_POST['unit'] ?? '');
        $old['minimum_stock'] = $_POST['minimum_stock'] ?? '0';
        $old['stock'] = $_POST['stock'] ?? '0';


        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        if ($old['code'] === '') {

            $errors[] = 'Kode barang wajib diisi.';

        }

        if ($old['name'] === '') {

            $errors[] = 'Nama barang wajib diisi.';

        }

        if ($old['category_id'] <= 0) {

            $errors[] = 'Kategori barang wajib dipilih.';

        }

        if ($old['unit'] === '') {

            $errors[] = 'Satuan barang wajib diisi.';

        }


        $minimum_stock = filter_var(
            $old['minimum_stock'],
            FILTER_VALIDATE_INT
        );

        $stock = filter_var(
            $old['stock'],
            FILTER_VALIDATE_INT
        );


        if (
            $minimum_stock === false ||
            $minimum_stock < 0
        ) {

            $errors[] =
                'Stok minimum harus berupa angka 0 atau lebih.';

        }


        if (
            $stock === false ||
            $stock < 0
        ) {

            $errors[] =
                'Stok awal harus berupa angka 0 atau lebih.';

        }


        /*
        |--------------------------------------------------------------------------
        | CEK KODE DUPLIKAT
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $check = $pdo->prepare("
                SELECT id
                FROM products
                WHERE LOWER(code) = LOWER(?)
                LIMIT 1
            ");

            $check->execute([
                $old['code']
            ]);


            if ($check->fetch()) {

                $errors[] =
                    'Kode barang tersebut sudah digunakan.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | CEK KATEGORI
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $check = $pdo->prepare("
                SELECT id
                FROM categories
                WHERE id = ?
                LIMIT 1
            ");

            $check->execute([
                $old['category_id']
            ]);


            if (!$check->fetch()) {

                $errors[] =
                    'Kategori yang dipilih tidak ditemukan.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | SIMPAN BARANG
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $stmt = $pdo->prepare("
                INSERT INTO products (
                    category_id,
                    code,
                    name,
                    unit,
                    minimum_stock,
                    stock
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $stmt->execute([
                $old['category_id'],
                $old['code'],
                $old['name'],
                $old['unit'],
                $minimum_stock,
                $stock
            ]);


            header(
                'Location: '
                . BASE_URL
                . '/products/index.php?success=created'
            );

            exit;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | EDIT BARANG
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit') {

        $edit_id = (int) ($_POST['id'] ?? 0);

        $edit_code = trim($_POST['code'] ?? '');
        $edit_name = trim($_POST['name'] ?? '');
        $edit_category_id = (int) (
            $_POST['category_id'] ?? 0
        );

        $edit_unit = trim($_POST['unit'] ?? '');

        $edit_minimum_stock = $_POST[
            'minimum_stock'
        ] ?? '0';


        /*
        |--------------------------------------------------------------------------
        | VALIDASI ID
        |--------------------------------------------------------------------------
        */

        if ($edit_id <= 0) {

            $errors[] =
                'Data barang tidak valid.';

        }


        /*
        |--------------------------------------------------------------------------
        | VALIDASI FIELD
        |--------------------------------------------------------------------------
        */

        if ($edit_code === '') {

            $errors[] =
                'Kode barang wajib diisi.';

        }

        if ($edit_name === '') {

            $errors[] =
                'Nama barang wajib diisi.';

        }

        if ($edit_category_id <= 0) {

            $errors[] =
                'Kategori barang wajib dipilih.';

        }

        if ($edit_unit === '') {

            $errors[] =
                'Satuan barang wajib diisi.';

        }


        $edit_minimum_stock_value = filter_var(
            $edit_minimum_stock,
            FILTER_VALIDATE_INT
        );


        if (
            $edit_minimum_stock_value === false ||
            $edit_minimum_stock_value < 0
        ) {

            $errors[] =
                'Stok minimum harus berupa angka 0 atau lebih.';

        }


        /*
        |--------------------------------------------------------------------------
        | CEK BARANG
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $check = $pdo->prepare("
                SELECT id
                FROM products
                WHERE id = ?
                LIMIT 1
            ");

            $check->execute([
                $edit_id
            ]);


            if (!$check->fetch()) {

                $errors[] =
                    'Barang tidak ditemukan.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | CEK KODE DUPLIKAT
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $check = $pdo->prepare("
                SELECT id
                FROM products
                WHERE LOWER(code) = LOWER(?)
                AND id != ?
                LIMIT 1
            ");

            $check->execute([
                $edit_code,
                $edit_id
            ]);


            if ($check->fetch()) {

                $errors[] =
                    'Kode barang tersebut sudah digunakan.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | CEK KATEGORI
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $check = $pdo->prepare("
                SELECT id
                FROM categories
                WHERE id = ?
                LIMIT 1
            ");

            $check->execute([
                $edit_category_id
            ]);


            if (!$check->fetch()) {

                $errors[] =
                    'Kategori yang dipilih tidak ditemukan.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE BARANG
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $stmt = $pdo->prepare("
                UPDATE products

                SET
                    category_id = ?,
                    code = ?,
                    name = ?,
                    unit = ?,
                    minimum_stock = ?

                WHERE id = ?
            ");

            $stmt->execute([
                $edit_category_id,
                $edit_code,
                $edit_name,
                $edit_unit,
                $edit_minimum_stock_value,
                $edit_id
            ]);


            header(
                'Location: '
                . BASE_URL
                . '/products/index.php?success=updated'
            );

            exit;

        }

    }

}


/*
|--------------------------------------------------------------------------
| AMBIL DATA KATEGORI
|--------------------------------------------------------------------------
*/

$categories = $pdo
    ->query("
        SELECT
            id,
            name

        FROM categories

        ORDER BY name ASC
    ")
    ->fetchAll();


/*
|--------------------------------------------------------------------------
| AMBIL DATA BARANG
|--------------------------------------------------------------------------
*/

$products = $pdo
    ->query("
        SELECT
            p.id,
            p.category_id,
            p.code,
            p.name,
            p.unit,
            p.minimum_stock,
            p.stock,
            p.created_at,
            p.updated_at,

            c.name AS category_name

        FROM products p

        LEFT JOIN categories c
            ON c.id = p.category_id

        ORDER BY p.name ASC
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


    <!-- =====================================================
         TOP HEADER
    ====================================================== -->

    <div class="top-header">


        <div class="top-header-title">

            <h5>

                <?= htmlspecialchars(
                    $page_title
                ) ?>

            </h5>


            <span>

                Sistem Informasi Manajemen Inventori

            </span>

        </div>


        <div class="top-header-user">


            <div class="top-user-avatar">

                <?= strtoupper(
                    substr(
                        $_SESSION['user']['name']
                        ?? 'A',

                        0,

                        1
                    )
                ) ?>

            </div>


            <div class="top-user-info">

                <strong>

                    <?= htmlspecialchars(
                        $_SESSION['user']['name']
                        ?? 'Administrator'
                    ) ?>

                </strong>


                <small>

                    <?= htmlspecialchars(
                        ucfirst(
                            $_SESSION['user']['role']
                            ?? 'admin'
                        )
                    ) ?>

                </small>

            </div>


        </div>


    </div>


    <!-- =====================================================
         ALERT CREATED
    ====================================================== -->

    <?php if ($success === 'created'): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <strong>

                Berhasil!

            </strong>

            Barang berhasil ditambahkan.


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ALERT UPDATED
    ====================================================== -->

    <?php if ($success === 'updated'): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <strong>

                Berhasil!

            </strong>

            Data barang berhasil diperbarui.


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ALERT DELETED
    ====================================================== -->

    <?php if ($success === 'deleted'): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <strong>

                Berhasil!

            </strong>

            Barang berhasil dihapus.


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ALERT ERROR
    ====================================================== -->

    <?php if ($errors): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <strong>

                Gagal menyimpan data.

            </strong>


            <ul class="mb-0 mt-2">

                <?php foreach ($errors as $error): ?>

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
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header">


        <div>

           

        </div>


        <div>

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#createProductModal"
            >

                + Tambah Barang

            </button>

        </div>


    </div>


    <!-- =====================================================
         TABLE CARD
    ====================================================== -->

    <div class="table-card">


        <div class="table-card-header">


            <div>

                <h5>

                    Daftar Barang

                </h5>


                <small class="text-secondary">

                    Total

                    <?= count(
                        $products
                    ) ?>

                    barang

                </small>

            </div>


        </div>


        <div class="table-card-body">


            <?php if ($products): ?>


                <div class="table-responsive">


                    <table class="table">


                        <thead>

                            <tr>

                                <th width="60">
                                    No
                                </th>

                                <th>
                                    Kode
                                </th>

                                <th>
                                    Nama Barang
                                </th>

                                <th>
                                    Kategori
                                </th>

                                <th>
                                    Satuan
                                </th>

                                <th>
                                    Stok
                                </th>

                                <th>
                                    Min. Stok
                                </th>

                                <th>
                                    Status
                                </th>

                                <th width="160">
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $products
                            as $index => $product
                        ): ?>


                            <?php

                            $stock =
                                (int) $product['stock'];

                            $minimum_stock =
                                (int) $product[
                                    'minimum_stock'
                                ];


                            if ($stock <= 0) {

                                $status =
                                    'Habis';

                                $status_class =
                                    'text-bg-danger';

                            } elseif (
                                $stock <=
                                $minimum_stock
                            ) {

                                $status =
                                    'Menipis';

                                $status_class =
                                    'text-bg-warning';

                            } else {

                                $status =
                                    'Normal';

                                $status_class =
                                    'text-bg-success';

                            }

                            ?>


                            <tr>


                                <!-- NO -->

                                <td>

                                    <?= $index + 1 ?>

                                </td>


                                <!-- KODE -->

                                <td>

                                    <code>

                                        <?= htmlspecialchars(
                                            $product['code']
                                        ) ?>

                                    </code>

                                </td>


                                <!-- NAMA -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $product['name']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- KATEGORI -->

                                <td>

                                    <?= htmlspecialchars(
                                        $product[
                                            'category_name'
                                        ]
                                        ?? '-'
                                    ) ?>

                                </td>


                                <!-- SATUAN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $product['unit']
                                    ) ?>

                                </td>


                                <!-- STOK -->

                                <td>

                                    <strong>

                                        <?= number_format(
                                            $stock
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- MINIMUM -->

                                <td>

                                    <?= number_format(
                                        $minimum_stock
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="badge <?= $status_class ?>"
                                    >

                                        <?= $status ?>

                                    </span>

                                </td>


                                <!-- AKSI -->

                                <td>


                                    <div
                                        class="d-flex gap-1"
                                    >


                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editProductModal<?= (int) $product['id'] ?>"
                                        >

                                            Edit

                                        </button>


                                        <a
                                            href="<?= BASE_URL ?>/products/delete.php?id=<?= (int) $product['id'] ?>"
                                            class="btn btn-sm btn-outline-danger"

                                            onclick="return confirm(
                                                'Yakin ingin menghapus barang <?= htmlspecialchars(
                                                    $product['name'],
                                                    ENT_QUOTES
                                                ) ?>?'
                                            );"
                                        >

                                            Hapus

                                        </a>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <div class="empty-state">


                    <div class="mb-3">

                        Belum ada data barang.

                    </div>


                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#createProductModal"
                    >

                        Tambah Barang Pertama

                    </button>


                </div>


            <?php endif; ?>


        </div>


    </div>


</main>


<!-- =========================================================
     MODAL TAMBAH BARANG
========================================================== -->

<div
    class="modal fade"
    id="createProductModal"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered modal-lg"
    >


        <div class="modal-content">


            <div class="modal-header">


                <h5 class="modal-title">

                    Tambah Barang

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>


            </div>


            <form
                method="POST"
                action=""
            >


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="action"
                        value="create"
                    >


                    <div class="row">


                        <!-- KODE -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="create_code"
                            >

                                Kode Barang
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="create_code"
                                name="code"
                                maxlength="50"
                                placeholder="Contoh: BRG-001"
                                value="<?= htmlspecialchars(
                                    $old['code']
                                ) ?>"
                                required
                            >


                        </div>


                        <!-- NAMA -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="create_name"
                            >

                                Nama Barang
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="create_name"
                                name="name"
                                maxlength="150"
                                placeholder="Contoh: Keripik Pisang"
                                value="<?= htmlspecialchars(
                                    $old['name']
                                ) ?>"
                                required
                            >


                        </div>


                        <!-- KATEGORI -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="create_category"
                            >

                                Kategori
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <select
                                class="form-select"
                                id="create_category"
                                name="category_id"
                                required
                            >

                                <option value="">

                                    -- Pilih Kategori --

                                </option>


                                <?php foreach (
                                    $categories
                                    as $category
                                ): ?>


                                    <option
                                        value="<?= (int) $category['id'] ?>"

                                        <?= (
                                            (int) $old[
                                                'category_id'
                                            ] ===
                                            (int) $category['id']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $category['name']
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>


                        <!-- SATUAN -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="create_unit"
                            >

                                Satuan
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="create_unit"
                                name="unit"
                                maxlength="30"
                                placeholder="Contoh: pcs"
                                value="<?= htmlspecialchars(
                                    $old['unit']
                                ) ?>"
                                required
                            >


                        </div>


                        <!-- STOK MINIMUM -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="create_minimum_stock"
                            >

                                Stok Minimum

                            </label>


                            <input
                                type="number"
                                class="form-control"
                                id="create_minimum_stock"
                                name="minimum_stock"
                                min="0"
                                value="<?= htmlspecialchars(
                                    $old['minimum_stock']
                                ) ?>"
                            >


                            <small class="text-secondary">

                                Batas stok sebelum status menjadi
                                <strong>Menipis</strong>.

                            </small>


                        </div>


                        <!-- STOK AWAL -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="create_stock"
                            >

                                Stok Awal

                            </label>


                            <input
                                type="number"
                                class="form-control"
                                id="create_stock"
                                name="stock"
                                min="0"
                                value="<?= htmlspecialchars(
                                    $old['stock']
                                ) ?>"
                            >


                            <small class="text-secondary">

                                Stok awal saat barang pertama kali
                                dimasukkan.

                            </small>


                        </div>


                    </div>


                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-light border"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        Simpan Barang

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<!-- =========================================================
     MODAL EDIT BARANG
========================================================== -->

<?php foreach (
    $products
    as $product
): ?>


<div
    class="modal fade"
    id="editProductModal<?= (int) $product['id'] ?>"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered modal-lg"
    >


        <div class="modal-content">


            <div class="modal-header">


                <h5 class="modal-title">

                    Edit Barang

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>


            </div>


            <form
                method="POST"
                action=""
            >


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="action"
                        value="edit"
                    >


                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $product['id'] ?>"
                    >


                    <div class="row">


                        <!-- KODE -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="edit_code_<?= (int) $product['id'] ?>"
                            >

                                Kode Barang
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="edit_code_<?= (int) $product['id'] ?>"
                                name="code"
                                maxlength="50"
                                value="<?= htmlspecialchars(
                                    $product['code']
                                ) ?>"
                                required
                            >


                        </div>


                        <!-- NAMA -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="edit_name_<?= (int) $product['id'] ?>"
                            >

                                Nama Barang
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="edit_name_<?= (int) $product['id'] ?>"
                                name="name"
                                maxlength="150"
                                value="<?= htmlspecialchars(
                                    $product['name']
                                ) ?>"
                                required
                            >


                        </div>


                        <!-- KATEGORI -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="edit_category_<?= (int) $product['id'] ?>"
                            >

                                Kategori
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <select
                                class="form-select"
                                id="edit_category_<?= (int) $product['id'] ?>"
                                name="category_id"
                                required
                            >


                                <option value="">

                                    -- Pilih Kategori --

                                </option>


                                <?php foreach (
                                    $categories
                                    as $category
                                ): ?>


                                    <option
                                        value="<?= (int) $category['id'] ?>"

                                        <?= (
                                            (int) $product[
                                                'category_id'
                                            ] ===
                                            (int) $category['id']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $category['name']
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>


                        <!-- SATUAN -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="edit_unit_<?= (int) $product['id'] ?>"
                            >

                                Satuan
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                id="edit_unit_<?= (int) $product['id'] ?>"
                                name="unit"
                                maxlength="30"
                                value="<?= htmlspecialchars(
                                    $product['unit']
                                ) ?>"
                                required
                            >


                        </div>


                        <!-- STOK MINIMUM -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                                for="edit_minimum_stock_<?= (int) $product['id'] ?>"
                            >

                                Stok Minimum

                            </label>


                            <input
                                type="number"
                                class="form-control"
                                id="edit_minimum_stock_<?= (int) $product['id'] ?>"
                                name="minimum_stock"
                                min="0"
                                value="<?= (int) $product['minimum_stock'] ?>"
                            >


                        </div>


                        <!-- STOK SAAT INI -->

                        <div class="col-md-6 mb-3">


                            <label
                                class="form-label"
                            >

                                Stok Saat Ini

                            </label>


                            <input
                                type="text"
                                class="form-control"
                                value="<?= number_format(
                                    (int) $product['stock']
                                ) ?>"
                                readonly
                            >


                            <small class="text-secondary">

                                Stok diubah melalui menu
                                <strong>Stok Masuk</strong> dan
                                <strong>Stok Keluar</strong>.

                            </small>


                        </div>


                    </div>


                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-light border"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        Simpan Perubahan

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<?php endforeach; ?>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
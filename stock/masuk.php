<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$page_title = 'Stok Masuk';

$errors = [];

$success = $_GET['success'] ?? '';

$old = [
    'product_id' => '',
    'quantity' => '',
    'description' => '',
];


/*
|--------------------------------------------------------------------------
| HANDLE FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
        'product_id' => $product_id,
        'quantity' => $_POST['quantity'] ?? '',
        'description' => $description,
    ];


    /*
    |--------------------------------------------------------------------------
    | VALIDASI BARANG
    |--------------------------------------------------------------------------
    */

    if ($product_id <= 0) {

        $errors[] =
            'Barang wajib dipilih.';

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDASI JUMLAH
    |--------------------------------------------------------------------------
    */

    if (
        $quantity === false ||
        $quantity <= 0
    ) {

        $errors[] =
            'Jumlah stok masuk harus lebih dari 0.';

    }


    /*
    |--------------------------------------------------------------------------
    | CEK BARANG
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $stmt = $pdo->prepare("
            SELECT
                id,
                code,
                name,
                unit,
                stock

            FROM products

            WHERE id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $product_id
        ]);

        $product = $stmt->fetch();


        if (!$product) {

            $errors[] =
                'Barang tidak ditemukan.';

        }

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
            | UPDATE STOK
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE products

                SET
                    stock = stock + ?,
                    updated_at = CURRENT_TIMESTAMP

                WHERE id = ?
            ");

            $stmt->execute([
                $quantity,
                $product_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | CATAT RIWAYAT
            |--------------------------------------------------------------------------
            */

            $user_id = $_SESSION['user']['id'] ?? null;


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
                    'IN',
                    ?,
                    ?,
                    ?
                )
            ");


            $stmt->execute([
                $product_id,
                $quantity,
                $description !== ''
                    ? $description
                    : null,
                $user_id
            ]);


            $pdo->commit();


            header(
                'Location: '
                . BASE_URL
                . '/stock/masuk.php?success=created'
            );

            exit;


        } catch (
            Throwable $e
        ) {

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();

            }


            $errors[] =
                'Transaksi stok masuk gagal disimpan.';

        }

    }

}


/*
|--------------------------------------------------------------------------
| AMBIL DATA BARANG
|--------------------------------------------------------------------------
*/

$products = $pdo
    ->query("
        SELECT
            id,
            code,
            name,
            unit,
            stock

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

    <!-- =====================================================
         ALERT SUCCESS
    ====================================================== -->

    <?php if ($success === 'created'): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
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

                Gagal menyimpan transaksi.

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

            <h1 class="page-title">

                Stok Masuk

            </h1>


            <p class="page-subtitle">

                Catat barang yang masuk dan tambahkan
                jumlah stok secara otomatis.

            </p>

        </div>


    </div>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="table-card">


        <div class="table-card-header">


            <div>

                <h5>

                    Form Stok Masuk

                </h5>


                <small class="text-secondary">

                    Masukkan data barang yang masuk ke inventori.

                </small>

            </div>


        </div>


        <div class="table-card-body">


            <form
                method="POST"
                action=""
            >


                <div class="row">


                    <!-- =================================================
                         BARANG
                    ================================================== -->

                    <div class="col-md-6 mb-3">


                        <label
                            for="product_id"
                            class="form-label"
                        >

                            Barang

                            <span class="text-danger">

                                *

                            </span>

                        </label>


                        <select
                            id="product_id"
                            name="product_id"
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

                                    <?= (
                                        (int) $old[
                                            'product_id'
                                        ] ===
                                        (int) $product['id']
                                    )
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

                                    | Stok:

                                    <?= number_format(
                                        (int) $product['stock']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $product['unit']
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <?php if (!$products): ?>

                            <div
                                class="form-text text-danger"
                            >

                                Belum ada barang.
                                Silakan tambahkan barang terlebih
                                dahulu melalui Master Barang.

                            </div>

                        <?php endif; ?>


                    </div>


                    <!-- =================================================
                         JUMLAH
                    ================================================== -->

                    <div class="col-md-6 mb-3">


                        <label
                            for="quantity"
                            class="form-label"
                        >

                            Jumlah Stok Masuk

                            <span class="text-danger">

                                *

                            </span>

                        </label>


                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            class="form-control"
                            min="1"
                            placeholder="Contoh: 50"
                            value="<?= htmlspecialchars(
                                $old['quantity']
                            ) ?>"
                            required
                        >


                        <div class="form-text">

                            Masukkan jumlah barang yang masuk.

                        </div>


                    </div>


                    <!-- =================================================
                         KETERANGAN
                    ================================================== -->

                    <div class="col-12 mb-3">


                        <label
                            for="description"
                            class="form-label"
                        >

                            Keterangan

                        </label>


                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Contoh: Pembelian dari supplier"
                        ><?= htmlspecialchars(
                            $old['description']
                        ) ?></textarea>


                    </div>


                </div>


                <!-- =================================================
                     BUTTON
                ================================================== -->

                <div
                    class="d-flex justify-content-end gap-2"
                >


                    <a
                        href="<?= BASE_URL ?>/dashboard/index.php"
                        class="btn btn-light border"
                    >

                        Batal

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"

                        <?= !$products
                            ? 'disabled'
                            : ''
                        ?>
                    >

                        Simpan Stok Masuk

                    </button>


                </div>


            </form>


        </div>


    </div>


    <!-- =====================================================
         INFORMATION
    ====================================================== -->

    <div
        class="alert alert-info mt-4"
        role="alert"
    >

        <strong>

            Informasi:

        </strong>

        Setiap transaksi stok masuk akan otomatis
        menambahkan jumlah stok barang dan mencatatnya
        ke dalam riwayat stok.

    </div>


</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
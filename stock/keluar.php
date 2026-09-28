<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$page_title = 'Stok Keluar';

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
            'Jumlah stok keluar harus lebih dari 0.';

    }


    /*
    |--------------------------------------------------------------------------
    | PROSES TRANSAKSI
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | LOCK BARANG
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


            /*
            |--------------------------------------------------------------------------
            | CEK STOK
            |--------------------------------------------------------------------------
            */

            if (
                $quantity >
                (int) $product['stock']
            ) {

                throw new Exception(
                    'Stok tidak mencukupi. Stok tersedia hanya '
                    . number_format(
                        (int) $product['stock']
                    )
                    . ' '
                    . $product['unit']
                    . '.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE STOK
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE products

                SET
                    stock = stock - ?,
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
                    'OUT',
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
                . '/stock/keluar.php?success=created'
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }


            $errors[] =
                $e->getMessage();

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


    <!-- TOP HEADER -->

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


    <!-- ALERT SUCCESS -->

    <?php if ($success === 'created'): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
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


    <!-- ALERT ERROR -->

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
            ></button>

        </div>

    <?php endif; ?>


    <!-- PAGE HEADER -->

    <div class="page-header">


        <div>

            <h1 class="page-title">

                Stok Keluar

            </h1>


            <p class="page-subtitle">

                Catat barang yang keluar dari inventori.

            </p>

        </div>


    </div>


    <!-- FORM -->

    <div class="table-card">


        <div class="table-card-header">


            <div>

                <h5>

                    Form Stok Keluar

                </h5>


                <small class="text-secondary">

                    Stok barang akan otomatis berkurang
                    setelah transaksi disimpan.

                </small>

            </div>


        </div>


        <div class="table-card-body">


            <form
                method="POST"
                action=""
            >


                <div class="row">


                    <!-- BARANG -->

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


                    </div>


                    <!-- JUMLAH -->

                    <div class="col-md-6 mb-3">


                        <label
                            for="quantity"
                            class="form-label"
                        >

                            Jumlah Stok Keluar

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
                            placeholder="Contoh: 10"
                            value="<?= htmlspecialchars(
                                $old['quantity']
                            ) ?>"
                            required
                        >


                    </div>


                    <!-- KETERANGAN -->

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
                            placeholder="Contoh: Penjualan barang"
                        ><?= htmlspecialchars(
                            $old['description']
                        ) ?></textarea>


                    </div>


                </div>


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

                        Simpan Stok Keluar

                    </button>


                </div>


            </form>


        </div>


    </div>


    <!-- INFORMATION -->

    <div
        class="alert alert-info mt-4"
        role="alert"
    >

        <strong>
            Informasi:
        </strong>

        Sistem akan menolak transaksi apabila jumlah
        stok keluar lebih besar daripada stok yang tersedia.

    </div>


</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| VALIDASI ID
|--------------------------------------------------------------------------
*/

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {

    header(
        'Location: '
        . BASE_URL
        . '/products/index.php?success=error'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CARI BARANG
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        code,
        name
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $id
]);

$product = $stmt->fetch();


if (!$product) {

    header(
        'Location: '
        . BASE_URL
        . '/products/index.php?success=error'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK RIWAYAT STOK
|--------------------------------------------------------------------------
*/

$check = $pdo->prepare("
    SELECT COUNT(*)
    FROM stock_movements
    WHERE product_id = ?
");

$check->execute([
    $id
]);

$movement_count = (int) $check->fetchColumn();


/*
|--------------------------------------------------------------------------
| JIKA SUDAH MEMILIKI RIWAYAT
|--------------------------------------------------------------------------
*/

if ($movement_count > 0) {

    ?>

    <!DOCTYPE html>

    <html lang="id">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>
            Barang Tidak Dapat Dihapus
        </title>


        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

    </head>


    <body
        class="bg-light"
    >


        <div
            class="container"
            style="max-width: 650px; margin-top: 100px;"
        >


            <div
                class="card shadow-sm border-0"
            >


                <div class="card-body p-4">


                    <div
                        class="alert alert-warning mb-4"
                    >

                        <h5
                            class="alert-heading"
                        >

                            Barang Tidak Dapat Dihapus

                        </h5>


                        <p class="mb-0">

                            Barang

                            <strong>

                                <?= htmlspecialchars(
                                    $product['name']
                                ) ?>

                            </strong>

                            sudah memiliki

                            <strong>

                                <?= $movement_count ?>

                                transaksi stok.

                            </strong>

                        </p>

                    </div>


                    <p>

                        Data barang tidak dapat dihapus karena
                        sudah digunakan dalam riwayat stok.

                    </p>


                    <p>

                        Hal ini dilakukan agar riwayat transaksi
                        inventori tetap tersimpan dan tidak
                        kehilangan referensi barang.

                    </p>


                    <div
                        class="bg-light border rounded p-3 mb-4"
                    >

                        <div class="mb-2">

                            <strong>
                                Kode Barang:
                            </strong>

                            <?= htmlspecialchars(
                                $product['code']
                            ) ?>

                        </div>


                        <div>

                            <strong>
                                Nama Barang:
                            </strong>

                            <?= htmlspecialchars(
                                $product['name']
                            ) ?>

                        </div>

                    </div>


                    <a
                        href="<?= BASE_URL ?>/products/index.php"
                        class="btn btn-primary"
                    >

                        Kembali ke Master Barang

                    </a>


                </div>


            </div>


        </div>


    </body>

    </html>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| HAPUS BARANG
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    DELETE FROM products
    WHERE id = ?
");

$stmt->execute([
    $id
]);


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header(
    'Location: '
    . BASE_URL
    . '/products/index.php?success=deleted'
);

exit;
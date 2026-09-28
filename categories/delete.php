<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$id = (int) ($_GET['id'] ?? 0);


/*
|--------------------------------------------------------------------------
| VALIDASI ID
|--------------------------------------------------------------------------
*/

if ($id <= 0) {

    header(
        'Location: '
        . BASE_URL
        . '/categories/index.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| CEK KATEGORI
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name
    FROM categories
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $id
]);

$category = $stmt->fetch();


if (!$category) {

    header(
        'Location: '
        . BASE_URL
        . '/categories/index.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| CEK BARANG YANG MENGGUNAKAN KATEGORI
|--------------------------------------------------------------------------
*/

$check = $pdo->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE category_id = ?
");

$check->execute([
    $id
]);

$product_count = (int) $check->fetchColumn();


/*
|--------------------------------------------------------------------------
| TIDAK BOLEH HAPUS JIKA MASIH DIPAKAI
|--------------------------------------------------------------------------
*/

if ($product_count > 0) {

    ?>

    <!DOCTYPE html>

    <html lang="id">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Kategori Tidak Dapat Dihapus</title>


        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

    </head>


    <body class="bg-light">


        <div
            class="container"
            style="
                max-width: 600px;
                margin-top: 100px;
            "
        >


            <div class="card border-0 shadow-sm">


                <div class="card-body p-4 text-center">


                    <div class="mb-3">

                        <div
                            style="
                                width:60px;
                                height:60px;
                                margin:auto;
                                border-radius:50%;
                                background:#fff3cd;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                font-size:28px;
                                color:#856404;
                            "
                        >

                            !

                        </div>

                    </div>


                    <h4 class="mb-2">

                        Kategori Tidak Dapat Dihapus

                    </h4>


                    <p class="text-secondary">

                        Kategori

                        <strong>

                            <?= htmlspecialchars(
                                $category['name']
                            ) ?>

                        </strong>

                        masih digunakan oleh

                        <strong>

                            <?= $product_count ?>

                        </strong>

                        barang.

                    </p>


                    <p class="text-secondary small">

                        Silakan pindahkan barang tersebut
                        ke kategori lain terlebih dahulu.

                    </p>


                    <a
                        href="<?= BASE_URL ?>/categories/index.php"
                        class="btn btn-primary"
                    >

                        Kembali

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
| DELETE
|--------------------------------------------------------------------------
*/

$delete = $pdo->prepare("
    DELETE FROM categories
    WHERE id = ?
");

$delete->execute([
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
    . '/categories/index.php?success=deleted'
);

exit;
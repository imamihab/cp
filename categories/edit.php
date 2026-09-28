<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$page_title = 'Edit Kategori';

$id = (int) ($_GET['id'] ?? 0);

$errors = [];


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
| AMBIL DATA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        description
    FROM categories
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$category = $stmt->fetch();


if (!$category) {

    header(
        'Location: '
        . BASE_URL
        . '/categories/index.php'
    );

    exit;

}


$name = $category['name'];

$description = $category['description'];


/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim(
        $_POST['name'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $errors[] =
            'Nama kategori wajib diisi.';

    }


    /*
    |--------------------------------------------------------------------------
    | CEK DUPLIKAT
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $check = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE LOWER(name) = LOWER(?)
            AND id != ?
            LIMIT 1
        ");

        $check->execute([
            $name,
            $id
        ]);


        if ($check->fetch()) {

            $errors[] =
                'Kategori dengan nama tersebut sudah tersedia.';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        $update = $pdo->prepare("
            UPDATE categories
            SET
                name = ?,
                description = ?
            WHERE id = ?
        ");

        $update->execute([
            $name,
            $description !== ''
                ? $description
                : null,
            $id
        ]);


        header(
            'Location: '
            . BASE_URL
            . '/categories/index.php'
        );

        exit;

    }

}


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="content">


    <!-- TOP HEADER -->

    <div class="top-header">

        <div class="top-header-title">

            <h5>
                <?= htmlspecialchars($page_title) ?>
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


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Edit Kategori
            </h1>

            <p class="page-subtitle">
                Perbarui informasi kategori barang.
            </p>

        </div>

    </div>


    <!-- FORM -->

    <div class="table-card">

        <div class="table-card-header">

            <h5>
                Form Kategori
            </h5>

        </div>


        <div class="p-4">


            <?php if ($errors): ?>

                <div class="alert alert-danger">

                    <strong>
                        Periksa data berikut:
                    </strong>

                    <ul class="mb-0 mt-2">

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= htmlspecialchars($error) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
            >


                <!-- NAMA -->

                <div class="mb-3">

                    <label
                        for="name"
                        class="form-label"
                    >
                        Nama Kategori
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars($name) ?>"
                        maxlength="100"
                        required
                    >

                </div>


                <!-- DESKRIPSI -->

                <div class="mb-4">

                    <label
                        for="description"
                        class="form-label"
                    >
                        Deskripsi
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        rows="4"
                        placeholder="Deskripsi kategori (opsional)"
                    ><?= htmlspecialchars($description ?? '') ?></textarea>

                </div>


                <!-- BUTTON -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Simpan Perubahan
                    </button>


                    <a
                        href="<?= BASE_URL ?>/categories/index.php"
                        class="btn btn-light border"
                    >
                        Batal
                    </a>

                </div>


            </form>


        </div>

    </div>


</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
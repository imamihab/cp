<?php

require_once __DIR__ . '/../includes/auth.php';

require_login();

require_once __DIR__ . '/../config/database.php';

$page_title = 'Master Kategori';

$errors = [];

$success = $_GET['success'] ?? '';


/*
|--------------------------------------------------------------------------
| FORM DEFAULT
|--------------------------------------------------------------------------
*/

$old_name = '';
$old_description = '';

$edit_id = 0;
$edit_name = '';
$edit_description = '';


/*
|--------------------------------------------------------------------------
| HANDLE POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | TAMBAH KATEGORI
    |--------------------------------------------------------------------------
    */

    if ($action === 'create') {

        $old_name = trim(
            $_POST['name'] ?? ''
        );

        $old_description = trim(
            $_POST['description'] ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | VALIDASI NAMA
        |--------------------------------------------------------------------------
        */

        if ($old_name === '') {

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
                LIMIT 1
            ");

            $check->execute([
                $old_name
            ]);


            if ($check->fetch()) {

                $errors[] =
                    'Kategori dengan nama tersebut sudah tersedia.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | SIMPAN KATEGORI
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $stmt = $pdo->prepare("
                INSERT INTO categories (
                    name,
                    description
                )
                VALUES (
                    ?,
                    ?
                )
            ");

            $stmt->execute([
                $old_name,
                $old_description !== ''
                    ? $old_description
                    : null
            ]);


            header(
                'Location: '
                . BASE_URL
                . '/categories/index.php?success=created'
            );

            exit;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | EDIT KATEGORI
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit') {

        $edit_id = (int) (
            $_POST['id'] ?? 0
        );

        $edit_name = trim(
            $_POST['name'] ?? ''
        );

        $edit_description = trim(
            $_POST['description'] ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | VALIDASI ID
        |--------------------------------------------------------------------------
        */

        if ($edit_id <= 0) {

            $errors[] =
                'Data kategori tidak valid.';

        }


        /*
        |--------------------------------------------------------------------------
        | VALIDASI NAMA
        |--------------------------------------------------------------------------
        */

        if ($edit_name === '') {

            $errors[] =
                'Nama kategori wajib diisi.';

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
                $edit_id
            ]);


            if (!$check->fetch()) {

                $errors[] =
                    'Kategori tidak ditemukan.';

            }

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
                $edit_name,
                $edit_id
            ]);


            if ($check->fetch()) {

                $errors[] =
                    'Kategori dengan nama tersebut sudah tersedia.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE KATEGORI
        |--------------------------------------------------------------------------
        */

        if (!$errors) {

            $stmt = $pdo->prepare("
                UPDATE categories

                SET
                    name = ?,
                    description = ?

                WHERE id = ?
            ");

            $stmt->execute([
                $edit_name,

                $edit_description !== ''
                    ? $edit_description
                    : null,

                $edit_id
            ]);


            header(
                'Location: '
                . BASE_URL
                . '/categories/index.php?success=updated'
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
            c.id,
            c.name,
            c.description,
            c.created_at,

            COUNT(p.id) AS product_count

        FROM categories c

        LEFT JOIN products p
            ON p.category_id = c.id

        GROUP BY
            c.id,
            c.name,
            c.description,
            c.created_at

        ORDER BY
            c.name ASC
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
         NOTIFICATION - CREATED
    ====================================================== -->

    <?php if ($success === 'created'): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <strong>
                Berhasil!
            </strong>

            Kategori berhasil ditambahkan.


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         NOTIFICATION - UPDATED
    ====================================================== -->

    <?php if ($success === 'updated'): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <strong>
                Berhasil!
            </strong>

            Kategori berhasil diperbarui.


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         NOTIFICATION - DELETED
    ====================================================== -->

    <?php if ($success === 'deleted'): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <strong>
                Berhasil!
            </strong>

            Kategori berhasil dihapus.


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         NOTIFICATION - ERROR
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
            <h1 class="page-title">Kategori</h1>
            <p class="page-subtitle">Kelola kategori barang.</p>
        </div>


        <div>


            <!-- BUTTON TAMBAH -->

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#createCategoryModal"
            >

                + Tambah Kategori

            </button>


        </div>


    </div>


    <!-- =====================================================
         TABLE CARD
    ====================================================== -->

    <div class="table-card">


        <!-- TABLE HEADER -->

        <div class="table-card-header">


            <div>

                <h5>

                    Daftar Kategori

                </h5>


                <small class="text-secondary">

                    Total

                    <?= count(
                        $categories
                    ) ?>

                    kategori

                </small>

            </div>


        </div>


        <!-- TABLE BODY -->

        <div class="table-card-body">


            <?php if ($categories): ?>


                <div class="table-responsive">


                    <table class="table">


                        <thead>

                            <tr>


                                <th width="60">

                                    No

                                </th>


                                <th>

                                    Nama Kategori

                                </th>


                                <th>

                                    Deskripsi

                                </th>


                                <th width="130">

                                    Jumlah Barang

                                </th>


                                <th width="160">

                                    Aksi

                                </th>


                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $categories
                            as $index => $category
                        ): ?>


                            <tr>


                                <!-- NO -->

                                <td>

                                    <?= $index + 1 ?>

                                </td>


                                <!-- NAMA -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $category['name']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- DESKRIPSI -->

                                <td>


                                    <?php if (
                                        !empty(
                                            $category['description']
                                        )
                                    ): ?>


                                        <?= htmlspecialchars(
                                            $category['description']
                                        ) ?>


                                    <?php else: ?>


                                        <span
                                            class="text-secondary"
                                        >

                                            -

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- JUMLAH BARANG -->

                                <td>


                                    <span
                                        class="badge text-bg-light"
                                    >

                                        <?= (int)
                                            $category[
                                                'product_count'
                                            ] ?>

                                        barang

                                    </span>


                                </td>


                                <!-- ACTION -->

                                <td>


                                    <div
                                        class="d-flex gap-1"
                                    >


                                        <!-- EDIT -->

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCategoryModal<?= (int) $category['id'] ?>"
                                        >

                                            Edit

                                        </button>


                                        <!-- DELETE -->

                                        <a
                                            href="<?= BASE_URL ?>/categories/delete.php?id=<?= (int) $category['id'] ?>"
                                            class="btn btn-sm btn-outline-danger"

                                            onclick="return confirm(
                                                'Yakin ingin menghapus kategori <?= htmlspecialchars(
                                                    $category['name'],
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


                <!-- EMPTY STATE -->

                <div class="empty-state">


                    <div class="mb-3">

                        Belum ada kategori.

                    </div>


                    <button
                        type="button"
                        class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#createCategoryModal"
                    >

                        Tambah Kategori Pertama

                    </button>


                </div>


            <?php endif; ?>


        </div>


    </div>


</main>


<!-- =========================================================
     MODAL TAMBAH KATEGORI
========================================================== -->

<div
    class="modal fade"
    id="createCategoryModal"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered"
    >


        <div class="modal-content">


            <!-- HEADER -->

            <div class="modal-header">


                <h5 class="modal-title">

                    Tambah Kategori

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>


            </div>


            <!-- FORM -->

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


                    <!-- NAMA -->

                    <div class="mb-3">


                        <label
                            for="create_name"
                            class="form-label"
                        >

                            Nama Kategori

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="create_name"
                            name="name"
                            class="form-control"
                            placeholder="Contoh: Snack"
                            maxlength="100"
                            value="<?= htmlspecialchars(
                                $old_name
                            ) ?>"
                            required
                        >


                    </div>


                    <!-- DESKRIPSI -->

                    <div class="mb-2">


                        <label
                            for="create_description"
                            class="form-label"
                        >

                            Deskripsi

                        </label>


                        <textarea
                            id="create_description"
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Deskripsi kategori (opsional)"
                        ><?= htmlspecialchars(
                            $old_description
                        ) ?></textarea>


                    </div>


                </div>


                <!-- FOOTER -->

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

                        Simpan Kategori

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<!-- =========================================================
     MODAL EDIT KATEGORI
========================================================== -->

<?php foreach (
    $categories
    as $category
): ?>


<div
    class="modal fade"
    id="editCategoryModal<?= (int) $category['id'] ?>"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered"
    >


        <div class="modal-content">


            <!-- HEADER -->

            <div class="modal-header">


                <h5 class="modal-title">

                    Edit Kategori

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>


            </div>


            <!-- FORM -->

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
                        value="<?= (int) $category['id'] ?>"
                    >


                    <!-- NAMA -->

                    <div class="mb-3">


                        <label
                            for="edit_name_<?= (int) $category['id'] ?>"
                            class="form-label"
                        >

                            Nama Kategori

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            id="edit_name_<?= (int) $category['id'] ?>"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $category['name']
                            ) ?>"
                            maxlength="100"
                            required
                        >


                    </div>


                    <!-- DESKRIPSI -->

                    <div class="mb-2">


                        <label
                            for="edit_description_<?= (int) $category['id'] ?>"
                            class="form-label"
                        >

                            Deskripsi

                        </label>


                        <textarea
                            id="edit_description_<?= (int) $category['id'] ?>"
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Deskripsi kategori (opsional)"
                        ><?= htmlspecialchars(
                            $category['description']
                            ?? ''
                        ) ?></textarea>


                    </div>


                </div>


                <!-- FOOTER -->

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

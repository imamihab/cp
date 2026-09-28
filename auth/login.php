<?php

require_once __DIR__ . '/../config/app.php';

session_start();

if (!empty($_SESSION['user'])) {
    header(
        'Location: ' .
        BASE_URL .
        '/dashboard/index.php'
    );

    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$username = '';


/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim(
        $_POST['username'] ?? ''
    );

    $password = $_POST['password'] ?? '';


    if ($username === '') {

        $error = 'Username wajib diisi.';

    } elseif ($password === '') {

        $error = 'Password wajib diisi.';

    } else {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                username,
                password,
                role
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([
            $username
        ]);

        $user = $stmt->fetch();


        if (
            $user &&
            password_verify(
                $password,
                $user['password']
            )
        ) {

            unset(
                $user['password']
            );

            $_SESSION['user'] = $user;

            header(
                'Location: ' .
                BASE_URL .
                '/dashboard/index.php'
            );

            exit;
        }


        $error =
            'Username atau password salah.';
    }
}

?>


<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Snack Inventory Management System"
    >

    <title>
        Login - Snack Inventory
    </title>


    <style>

        /* =====================================================
           RESET
        ====================================================== */

        * {
            box-sizing: border-box;
        }


        html,
        body {
            width: 100%;
            min-height: 100%;
        }


        body {

            margin: 0;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Arial,
                sans-serif;

            background: #eef4ff;

            color: #101828;

            -webkit-font-smoothing: antialiased;
        }


        button,
        input {
            font-family: inherit;
        }


        /* =====================================================
           PAGE
        ====================================================== */

        .login-page {

            min-height: 100vh;

            padding: 30px;

            display: flex;

            align-items: center;

            justify-content: center;

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    #0f2f87 0%,
                    #1755d1 38%,
                    #2587ed 70%,
                    #38bdf8 100%
                );
        }


        /* =====================================================
           BACKGROUND EFFECTS
        ====================================================== */

        .bg-orb {

            position: absolute;

            border-radius: 50%;

            pointer-events: none;

            filter: blur(1px);
        }


        .bg-orb-one {

            width: 600px;
            height: 600px;

            top: -330px;
            right: -160px;

            background:
                rgba(255,255,255,.08);
        }


        .bg-orb-two {

            width: 500px;
            height: 500px;

            bottom: -330px;
            left: -230px;

            background:
                rgba(255,255,255,.08);
        }


        .bg-orb-three {

            width: 180px;
            height: 180px;

            left: 10%;
            top: 18%;

            background:
                rgba(255,255,255,.035);
        }


        .bg-orb-four {

            width: 100px;
            height: 100px;

            right: 17%;
            bottom: 15%;

            background:
                rgba(255,255,255,.045);
        }


        /* =====================================================
           MAIN CONTAINER
        ====================================================== */

        .login-wrapper {

            width: 100%;

            max-width: 980px;

            min-height: 590px;

            position: relative;

            z-index: 2;

            display: grid;

            grid-template-columns:
                1fr 1fr;

            overflow: hidden;

            border-radius: 26px;

            background:
                rgba(255,255,255,.96);

            box-shadow:
                0 35px 90px
                rgba(7,31,87,.30);

            border:
                1px solid
                rgba(255,255,255,.4);
        }


        /* =====================================================
           LEFT BRAND PANEL
        ====================================================== */

        .brand-panel {

            position: relative;

            padding: 55px 50px;

            display: flex;

            flex-direction: column;

            justify-content: space-between;

            overflow: hidden;

            color: #ffffff;

            background:
                linear-gradient(
                    150deg,
                    #123891 0%,
                    #1d58d7 50%,
                    #2698ef 100%
                );
        }


        .brand-panel::after {

            content: "";

            position: absolute;

            width: 380px;

            height: 380px;

            right: -190px;

            bottom: -190px;

            border-radius: 50%;

            background:
                rgba(255,255,255,.07);
        }


        .brand-panel::before {

            content: "";

            position: absolute;

            width: 220px;

            height: 220px;

            left: -110px;

            top: 30%;

            border-radius: 50%;

            border:
                1px solid
                rgba(255,255,255,.08);
        }


        .brand-content {

            position: relative;

            z-index: 2;
        }


        /* =====================================================
           LOGO
        ====================================================== */

        .brand-logo {

            width: 70px;

            height: 70px;

            margin-bottom: 25px;

            border-radius: 19px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(255,255,255,.98);

            color:
                #2563eb;

            font-size: 25px;

            font-weight: 800;

            letter-spacing: -1px;

            box-shadow:
                0 14px 30px
                rgba(0,0,0,.16);
        }


        .brand-panel h1 {

            margin: 0;

            font-size: 34px;

            line-height: 1.15;

            letter-spacing: -.8px;

            font-weight: 750;
        }


        .brand-description {

            max-width: 350px;

            margin: 15px 0 0;

            color:
                rgba(255,255,255,.78);

            font-size: 14px;

            line-height: 1.7;
        }


        /* =====================================================
           FEATURES
        ====================================================== */

        .brand-features {

            position: relative;

            z-index: 2;

            display: flex;

            flex-direction: column;

            gap: 12px;
        }


        .feature-item {

            display: flex;

            align-items: center;

            gap: 11px;

            color:
                rgba(255,255,255,.88);

            font-size: 12px;
        }


        .feature-icon {

            width: 28px;

            height: 28px;

            border-radius: 8px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(255,255,255,.12);

            font-size: 13px;
        }


        .brand-footer {

            position: relative;

            z-index: 2;

            margin-top: 25px;

            color:
                rgba(255,255,255,.55);

            font-size: 10px;
        }


        /* =====================================================
           RIGHT LOGIN PANEL
        ====================================================== */

        .login-panel {

            padding: 55px 55px;

            display: flex;

            align-items: center;

            background: #ffffff;
        }


        .login-content {

            width: 100%;

            max-width: 370px;

            margin: 0 auto;
        }


        /* =====================================================
           LOGIN HEADER
        ====================================================== */

        .login-header {

            margin-bottom: 30px;
        }


        .login-header .welcome {

            margin: 0 0 7px;

            color:
                #2563eb;

            font-size: 12px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 1px;
        }


        .login-header h2 {

            margin: 0;

            color:
                #101828;

            font-size: 27px;

            line-height: 1.2;

            letter-spacing: -.5px;

            font-weight: 750;
        }


        .login-header p {

            margin: 9px 0 0;

            color:
                #667085;

            font-size: 13px;

            line-height: 1.55;
        }


        /* =====================================================
           ALERT
        ====================================================== */

        .login-alert {

            margin-bottom: 20px;

            padding: 12px 14px;

            display: flex;

            align-items: flex-start;

            gap: 10px;

            border:
                1px solid #fecdca;

            border-radius: 10px;

            background:
                #fef3f2;

            color:
                #b42318;

            font-size: 12px;

            line-height: 1.5;
        }


        .alert-icon {

            width: 20px;

            height: 20px;

            flex-shrink: 0;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                #f04438;

            color: #ffffff;

            font-size: 11px;

            font-weight: 700;
        }


        /* =====================================================
           FORM
        ====================================================== */

        .form-group {

            margin-bottom: 19px;
        }


        .form-label-row {

            margin-bottom: 7px;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .form-label {

            color:
                #344054;

            font-size: 12px;

            font-weight: 650;
        }


        .form-control-wrapper {

            position: relative;
        }


        .input-icon {

            position: absolute;

            left: 13px;

            top: 50%;

            transform:
                translateY(-50%);

            width: 18px;

            height: 18px;

            color:
                #98a2b3;

            display: flex;

            align-items: center;

            justify-content: center;

            pointer-events: none;

            font-size: 14px;
        }


        .form-control {

            width: 100%;

            height: 47px;

            padding:
                0 13px 0 42px;

            border:
                1px solid #d0d5dd;

            border-radius: 10px;

            outline: none;

            background: #ffffff;

            color:
                #101828;

            font-size: 13px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .form-control.password {

            padding-right: 46px;
        }


        .form-control::placeholder {

            color:
                #98a2b3;
        }


        .form-control:hover {

            border-color:
                #98a2b3;
        }


        .form-control:focus {

            border-color:
                #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,.10);
        }


        /* =====================================================
           PASSWORD TOGGLE
        ====================================================== */

        .password-toggle {

            position: absolute;

            top: 50%;

            right: 7px;

            transform:
                translateY(-50%);

            width: 34px;

            height: 34px;

            border: 0;

            border-radius: 8px;

            background:
                transparent;

            color:
                #667085;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 14px;
        }


        .password-toggle:hover {

            background:
                #f2f4f7;

            color:
                #344054;
        }


        /* =====================================================
           SUBMIT
        ====================================================== */

        .login-button {

            width: 100%;

            height: 47px;

            margin-top: 3px;

            border: 0;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #1d4ed8,
                    #2563eb
                );

            color:
                #ffffff;

            font-size: 13px;

            font-weight: 650;

            cursor: pointer;

            box-shadow:
                0 8px 18px
                rgba(37,99,235,.20);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .login-button:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 11px 23px
                rgba(37,99,235,.27);
        }


        .login-button:active {

            transform:
                translateY(0);
        }


        /* =====================================================
           DEMO
        ====================================================== */

        .demo-box {

            margin-top: 18px;

            padding: 10px 13px;

            border:
                1px solid #eaecf0;

            border-radius: 9px;

            background:
                #f8fafc;

            text-align: center;

            color:
                #667085;

            font-size: 10px;
        }


        .demo-box strong {

            color:
                #344054;

            font-weight: 650;
        }


        /* =====================================================
           SECURITY
        ====================================================== */

        .security-info {

            margin-top: 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            color:
                #98a2b3;

            font-size: 10px;
        }


        .security-icon {

            color:
                #12b76a;

            font-size: 12px;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 850px) {

            .login-page {

                padding: 20px;
            }


            .login-wrapper {

                max-width: 460px;

                min-height: auto;

                grid-template-columns: 1fr;

                border-radius: 22px;
            }


            .brand-panel {

                padding: 35px;

                min-height: 300px;
            }


            .brand-panel h1 {

                font-size: 28px;
            }


            .brand-features {

                display: none;
            }


            .brand-footer {

                margin-top: 35px;
            }


            .login-panel {

                padding: 38px 35px;
            }

        }


        @media (max-width: 500px) {

            .login-page {

                padding: 12px;
            }


            .login-wrapper {

                border-radius: 18px;
            }


            .brand-panel {

                padding: 28px 25px;

                min-height: 250px;
            }


            .brand-logo {

                width: 58px;

                height: 58px;

                margin-bottom: 19px;

                border-radius: 16px;

                font-size: 21px;
            }


            .brand-panel h1 {

                font-size: 25px;
            }


            .brand-description {

                margin-top: 10px;

                font-size: 12px;
            }


            .login-panel {

                padding: 30px 23px;
            }


            .login-header {

                margin-bottom: 24px;
            }


            .login-header h2 {

                font-size: 23px;
            }

        }

    </style>

</head>


<body>


<div class="login-page">


    <!-- =====================================================
         BACKGROUND
    ====================================================== -->

    <div class="bg-orb bg-orb-one"></div>

    <div class="bg-orb bg-orb-two"></div>

    <div class="bg-orb bg-orb-three"></div>

    <div class="bg-orb bg-orb-four"></div>


    <!-- =====================================================
         LOGIN WRAPPER
    ====================================================== -->

    <div class="login-wrapper">


        <!-- =================================================
             BRAND PANEL
        ================================================== -->

        <section class="brand-panel">


            <div class="brand-content">


                


                <h1>

                    Snack Inventory

                </h1>


                <p class="brand-description">

                    Sistem informasi manajemen inventori
                    untuk membantu pencatatan dan
                    pengelolaan stok barang secara
                    lebih mudah dan terstruktur.

                </p>


            </div>


            <div class="brand-features">


                <div class="feature-item">

                    <span class="feature-icon">
                        ✓
                    </span>

                    <span>
                        Manajemen data barang
                    </span>

                </div>


                <div class="feature-item">

                    <span class="feature-icon">
                        ⇄
                    </span>

                    <span>
                        Pencatatan stok masuk dan keluar
                    </span>

                </div>


                <div class="feature-item">

                    <span class="feature-icon">
                        ▤
                    </span>

                    <span>
                        Laporan kondisi persediaan
                    </span>

                </div>


            </div>


            <div class="brand-footer">

                Snack Inventory Management System

            </div>


        </section>


        <!-- =================================================
             LOGIN PANEL
        ================================================== -->

        <section class="login-panel">


            <div class="login-content">


                <!-- HEADER -->

                <div class="login-header">

                    <div class="welcome">

                        Selamat Datang

                    </div>


                    <h2>

                        Masuk ke Sistem

                    </h2>


                    <p>

                        Gunakan akun Anda untuk mengakses
                        dashboard inventori.

                    </p>

                </div>


                <!-- ERROR -->

                <?php if ($error): ?>

                    <div
                        class="login-alert"
                        role="alert"
                    >

                        <span class="alert-icon">
                            !
                        </span>

                        <span>

                            <?= htmlspecialchars(
                                $error
                            ) ?>

                        </span>

                    </div>

                <?php endif; ?>


                <!-- FORM -->

                <form
                    method="POST"
                    action=""
                >


                    <!-- USERNAME -->

                    <div class="form-group">


                        <div class="form-label-row">

                            <label
                                for="username"
                                class="form-label"
                            >

                                Username

                            </label>

                        </div>


                        <div class="form-control-wrapper">


                            <span class="input-icon">
                                ◉
                            </span>


                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="Masukkan username"
                                value="<?= htmlspecialchars(
                                    $username
                                ) ?>"
                                autocomplete="username"
                                required
                                autofocus
                            >


                        </div>


                    </div>


                    <!-- PASSWORD -->

                    <div class="form-group">


                        <div class="form-label-row">

                            <label
                                for="password"
                                class="form-label"
                            >

                                Password

                            </label>

                        </div>


                        <div class="form-control-wrapper">


                            <span class="input-icon">
                                ●
                            </span>


                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >


                            <button
                                type="button"
                                id="passwordToggle"
                                class="password-toggle"
                                aria-label="Tampilkan password"
                                title="Tampilkan password"
                            >

                                ◉

                            </button>


                        </div>


                    </div>


                    <!-- BUTTON -->

                    <button
                        type="submit"
                        class="login-button"
                    >

                        Masuk ke Sistem

                    </button>


                </form>


                <!-- DEMO -->

                <div class="demo-box">

                    Demo:

                    <strong>
                        admin
                    </strong>

                    &nbsp;/&nbsp;

                    <strong>
                        admin123
                    </strong>

                </div>


                <!-- SECURITY -->

                <div class="security-info">

                    <span class="security-icon">
                        ✓
                    </span>

                    Sistem menggunakan autentikasi
                    password terenkripsi.

                </div>


            </div>


        </section>


    </div>


</div>


<script>

const passwordInput =
    document.getElementById(
        'password'
    );

const passwordToggle =
    document.getElementById(
        'passwordToggle'
    );


passwordToggle.addEventListener(
    'click',
    function () {

        const visible =
            passwordInput.type === 'text';


        if (visible) {

            passwordInput.type =
                'password';

            passwordToggle.innerHTML =
                '◉';

            passwordToggle.setAttribute(
                'aria-label',
                'Tampilkan password'
            );

            passwordToggle.setAttribute(
                'title',
                'Tampilkan password'
            );

        } else {

            passwordInput.type =
                'text';

            passwordToggle.innerHTML =
                '◌';

            passwordToggle.setAttribute(
                'aria-label',
                'Sembunyikan password'
            );

            passwordToggle.setAttribute(
                'title',
                'Sembunyikan password'
            );
        }

    }
);

</script>


</body>

</html>
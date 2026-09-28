<?php
require_once __DIR__ . '/../config/app.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user'])) { header('Location: ' . BASE_URL . '/dashboard/index.php'); exit; }
require_once __DIR__ . '/../config/database.php';
$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '') $error = 'Username wajib diisi.';
    elseif ($password === '') $error = 'Password wajib diisi.';
    else {
        $stmt = $pdo->prepare('SELECT id, name, username, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            header('Location: ' . BASE_URL . '/dashboard/index.php');
            exit;
        }
        $error = 'Username atau password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistem Informasi Manajemen Inventori Snack Inventory">
    <title>Masuk - Snack Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=6">
</head>
<body class="login-page">
<main class="login-card">
    <section class="login-brand-panel" aria-label="Tentang aplikasi" style="--login-photo:url('<?= BASE_URL ?>/assets/images/login-snacks.png')">
        <div class="login-brand-lockup">
            <span class="login-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"></path><path d="m4.5 7.5 7.5 4.3 7.5-4.3M12 12v8.5"></path></svg></span>
            <span><strong>Snack Inventory</strong><small>Sistem Management Inventory</small></span>
        </div>
        <div class="login-brand-copy">
            <h1>Kelola Stok<br>Lebih Mudah<br>dan Terorganisir.</h1>
            <p>Sistem informasi untuk membantu pengelolaan inventori jajanan pasar dan snack secara sederhana, praktis, dan akurat.</p>
        </div>
        <div class="login-features" aria-label="Fitur aplikasi">
            <div><span class="feature-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"></path><path d="m4.5 7.5 7.5 4.3 7.5-4.3M12 12v8.5"></path></svg></span><strong>Data Barang</strong><small>Tersusun rapi per kategori</small></div>
            <div><span class="feature-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="7.5" ry="3"></ellipse><path d="M4.5 5v6c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3V5M4.5 11v6c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3v-6"></path></svg></span><strong>Pantau Stok</strong><small>Masuk, keluar dan riwayat</small></div>
            <div><span class="feature-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3.5h8l4 4V20H6V3.5Z"></path><path d="M14 3.5v4h4M9 12h6M9 15h6"></path></svg></span><strong>Laporan</strong><small>Informasi stok lebih jelas</small></div>
        </div>
    </section>

    <section class="login-form-panel" aria-labelledby="loginTitle">
        <div class="login-form-card">
            <div class="login-form-brand" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"></path><path d="m4.5 7.5 7.5 4.3 7.5-4.3M12 12v8.5"></path></svg></div>
        <div class="login-form-heading">
            <h2 id="loginTitle">Snack Inventory</h2>
            <p>Sistem Informasi Manajemen Inventori<br></p>
        </div>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" action="">
            <div class="login-field"><label class="form-label" for="username">Username</label><div class="login-input-wrap"><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.2"></circle><path d="M5.5 19c.5-3.3 2.8-5 6.5-5s6 1.7 6.5 5"></path></svg></span><input class="form-control" type="text" id="username" name="username" placeholder="Masukkan username" value="<?= htmlspecialchars($username) ?>" autocomplete="username" required autofocus></div></div>
            <div class="login-field"><label class="form-label" for="password">Password</label><div class="login-input-wrap"><span aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5.5" y="10" width="13" height="10" rx="1.8"></rect><path d="M8.5 10V7.5a3.5 3.5 0 0 1 7 0V10"></path></svg></span><input class="form-control" type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required><button class="login-password-toggle" type="button" id="passwordToggle" aria-label="Tampilkan password" title="Tampilkan password"><svg viewBox="0 0 24 24"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button></div></div>
            <button class="btn btn-primary login-submit" type="submit">Masuk <span aria-hidden="true">&#8594;</span></button>
        </form>
        </div>
        <p class="login-access-note">&copy; <?= date('Y') ?> Snack Inventory. All rights reserved.</p>
    </section>
</main>
<script>
document.getElementById('passwordToggle').addEventListener('click', function () {
    const input = document.getElementById('password');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    this.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    this.setAttribute('title', show ? 'Sembunyikan password' : 'Tampilkan password');
});
</script>
</body>
</html>

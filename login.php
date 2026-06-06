<?php
session_start();
require_once 'db_connect.php';

// Redirect jika sudah login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set Session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['nama'];
                $_SESSION['admin_role'] = $user['role'];

                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    } else {
        $error = 'Harap isi semua kolom login!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator - Ponpes Al-Barokah</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="gradient-bg"></div>

    <div class="login-container">
        <!-- Logo / Back Link -->
        <div style="margin-bottom: 20px;">
            <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: var(--transition);">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>

        <div class="login-header">
            <div class="logo-icon" style="margin: 0 auto 15px; width: 50px; height: 50px; font-size: 1.8rem; border-radius: 12px;">B</div>
            <h2>Portal Admin</h2>
            <p>Pondok Pesantren Al-Barokah</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username"><i class="fa-solid fa-user-tag"></i> Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label for="password"><i class="fa-solid fa-key"></i> Kata Sandi</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan kata sandi" required>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 12px;">
                Masuk ke Dashboard <i class="fa-solid fa-right-to-bracket"></i>
            </button>
        </form>

        <div style="margin-top: 30px; text-align: center; font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid var(--border-glass); padding-top: 20px;">
            <p style="margin-bottom: 5px; color: var(--gold); font-weight: 500;">Daftar Akun Pengelola:</p>
            <p>1. admin.utama (Admin Utama/Bendahara)</p>
            <p>2. admin.mts (Admin MTs/Humas)</p>
            <p>3. admin.smk (Admin SMK/Kesantrian)</p>
        </div>
    </div>
</body>
</html>

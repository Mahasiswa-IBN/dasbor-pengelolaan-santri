<?php
session_start();
require_once 'db_connect.php';

// Validasi session login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$adminName = $_SESSION['admin_name'];
$adminRole = $_SESSION['admin_role'];
$adminUsername = $_SESSION['admin_username'];

$message = '';
$messageType = '';

// Proses Simpan Pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaPondok = trim($_POST['nama_pondok'] ?? '');
    $alamatPondok = trim($_POST['alamat_pondok'] ?? '');
    $noHpPondok = trim($_POST['no_hp_pondok'] ?? '');
    
    // Bersihkan format nomor telepon
    $noHpPondok = preg_replace('/[^0-9]/', '', $noHpPondok);

    try {
        $pdo->beginTransaction();

        $stmtUpdate = $pdo->prepare("INSERT INTO `settings` (`meta_key`, `meta_value`) VALUES (:meta_key, :meta_value) 
            ON DUPLICATE KEY UPDATE `meta_value` = :meta_value_update");
            
        $stmtUpdate->execute(['meta_key' => 'nama_pondok', 'meta_value' => $namaPondok, 'meta_value_update' => $namaPondok]);
        $stmtUpdate->execute(['meta_key' => 'alamat_pondok', 'meta_value' => $alamatPondok, 'meta_value_update' => $alamatPondok]);
        $stmtUpdate->execute(['meta_key' => 'no_hp_pondok', 'meta_value' => $noHpPondok, 'meta_value_update' => $noHpPondok]);

        // Upload Logo Baru
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $logoTmp = $_FILES['logo_file']['tmp_name'];
            $logoName = $_FILES['logo_file']['name'];
            $logoExt = strtolower(pathinfo($logoName, PATHINFO_EXTENSION));
            
            $allowedLogoExts = ['png', 'jpg', 'jpeg', 'svg'];
            if (in_array($logoExt, $allowedLogoExts)) {
                $newLogoName = 'logo_' . time() . '.' . $logoExt;
                $destLogo = 'uploads/settings/' . $newLogoName;
                
                if (!is_dir('uploads/settings')) {
                    mkdir('uploads/settings', 0777, true);
                }
                
                if (move_uploaded_file($logoTmp, $destLogo)) {
                    $stmtUpdate->execute(['meta_key' => 'logo_path', 'meta_value' => $newLogoName, 'meta_value_update' => $newLogoName]);
                }
            } else {
                throw new Exception("Format logo tidak valid! Gunakan PNG, JPG, atau JPEG.");
            }
        }

        // Upload Gambar Hero Baru
        if (isset($_FILES['gambar_pondok_file']) && $_FILES['gambar_pondok_file']['error'] === UPLOAD_ERR_OK) {
            $gbrTmp = $_FILES['gambar_pondok_file']['tmp_name'];
            $gbrName = $_FILES['gambar_pondok_file']['name'];
            $gbrExt = strtolower(pathinfo($gbrName, PATHINFO_EXTENSION));
            
            $allowedGbrExts = ['png', 'jpg', 'jpeg', 'webp'];
            if (in_array($gbrExt, $allowedGbrExts)) {
                $newGbrName = 'pondok_' . time() . '.' . $gbrExt;
                $destGbr = 'uploads/settings/' . $newGbrName;
                
                if (!is_dir('uploads/settings')) {
                    mkdir('uploads/settings', 0777, true);
                }
                
                if (move_uploaded_file($gbrTmp, $destGbr)) {
                    $stmtUpdate->execute(['meta_key' => 'gambar_pondok_path', 'meta_value' => $newGbrName, 'meta_value_update' => $newGbrName]);
                }
            } else {
                throw new Exception("Format gambar pondok tidak valid! Gunakan PNG, JPG, JPEG, atau WEBP.");
            }
        }

        $pdo->commit();
        $message = "Pengaturan website berhasil disimpan!";
        $messageType = "success";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Gagal menyimpan pengaturan: " . $e->getMessage();
        $messageType = "error";
    }
}

// Ambil settings terbaru
$settings = getSettings($pdo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Website - Pondok Pesantren Al-Barokah</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: var(--bg-dark);">
    <div class="gradient-bg"></div>

    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon" style="width: 38px; height: 38px; font-size: 1.3rem;">B</div>
                <div class="logo-text">
                    <h1 style="font-size: 1.1rem; line-height: 1.2;">AL-BAROKAH</h1>
                    <p style="font-size: 0.65rem;">Sistem Informasi</p>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="sidebar-user">
                <div class="sidebar-user-name" title="<?php echo $adminName; ?>"><?php echo $adminName; ?></div>
                <div class="sidebar-user-role"><?php echo $adminRole; ?></div>
            </div>

            <!-- Navigation Links -->
            <ul class="sidebar-menu">
                <li>
                    <a href="admin_dashboard.php" class="sidebar-link">
                        <i class="fa-solid fa-table-columns"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="admin_settings.php" class="sidebar-link active">
                        <i class="fa-solid fa-gear"></i> Pengaturan
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="sidebar-link sidebar-link-logout">
                        <i class="fa-solid fa-right-from-bracket"></i> Keluar
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Panel -->
        <main class="dashboard-main">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="dashboard-title">
                    <h2>Pengaturan Website</h2>
                    <p>Sesuaikan identitas, nomor telepon, logo, dan gambar utama pondok pesantren.</p>
                </div>
                <div>
                    <span style="font-size: 0.85rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); padding: 8px 16px; border-radius: 20px;">
                        <i class="fa-regular fa-calendar-check" style="color: var(--gold); margin-right: 6px;"></i> 
                        <?php echo date('d M Y'); ?>
                    </span>
                </div>
            </header>

            <!-- Settings Form Card -->
            <section class="panel" style="margin-top: 30px; padding: 30px;">
                <div class="panel-header" style="margin-bottom: 25px; border-bottom: 1px solid var(--border-glass); padding-bottom: 15px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; color: var(--text-white);">
                        <i class="fa-solid fa-sliders" style="color: var(--gold);"></i> Identitas & Informasi Pondok
                    </h3>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="<?php echo $messageType === 'success' ? 'alert-success' : 'alert-danger'; ?>" style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px; justify-content: center;">
                        <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i> 
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form action="admin_settings.php" method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label for="nama_pondok" style="margin-bottom: 8px; display: block; font-weight: 500; font-size: 0.9rem; color: var(--text-light);">
                                <i class="fa-solid fa-kaaba" style="color: var(--gold); margin-right: 4px;"></i> Nama Pondok Pesantren
                            </label>
                            <input type="text" id="nama_pondok" name="nama_pondok" class="form-control" value="<?php echo htmlspecialchars($settings['nama_pondok'] ?? ''); ?>" required style="width: 100%;">
                        </div>
                        <div class="form-group">
                            <label for="no_hp_pondok" style="margin-bottom: 8px; display: block; font-weight: 500; font-size: 0.9rem; color: var(--text-light);">
                                <i class="fa-solid fa-phone" style="color: var(--gold); margin-right: 4px;"></i> No. HP / WhatsApp Pondok
                            </label>
                            <input type="text" id="no_hp_pondok" name="no_hp_pondok" class="form-control" value="<?php echo htmlspecialchars($settings['no_hp_pondok'] ?? ''); ?>" required style="width: 100%;" placeholder="Contoh: 6281234567890">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="alamat_pondok" style="margin-bottom: 8px; display: block; font-weight: 500; font-size: 0.9rem; color: var(--text-light);">
                            <i class="fa-solid fa-map-location-dot" style="color: var(--gold); margin-right: 4px;"></i> Alamat Lengkap Pondok Pesantren
                        </label>
                        <textarea id="alamat_pondok" name="alamat_pondok" class="form-control" rows="3" required style="width: 100%; min-height: 100px; resize: vertical;"><?php echo htmlspecialchars($settings['alamat_pondok'] ?? ''); ?></textarea>
                    </div>

                    <!-- Uploads Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 35px;">
                        <!-- Logo Upload -->
                        <div class="form-group" style="display: flex; gap: 15px; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); padding: 15px; border-radius: var(--border-radius);">
                            <div style="width: 64px; height: 64px; border-radius: 8px; background: var(--bg-card); border: 1px solid var(--border-glass); overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 4px; flex-shrink: 0;">
                                <img src="uploads/settings/<?php echo htmlspecialchars($settings['logo_path'] ?? 'logo.png'); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                            <div style="flex-grow: 1;">
                                <label for="logo_file" style="margin-bottom: 5px; display: block; font-weight: 500; font-size: 0.85rem; color: var(--text-light);">
                                    <i class="fa-solid fa-image" style="color: var(--gold); margin-right: 4px;"></i> Unggah Logo Baru
                                </label>
                                <input type="file" id="logo_file" name="logo_file" class="form-control" accept="image/*" style="font-size: 0.8rem; padding: 5px;">
                            </div>
                        </div>

                        <!-- Gambar Hero Upload -->
                        <div class="form-group" style="display: flex; gap: 15px; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); padding: 15px; border-radius: var(--border-radius);">
                            <div style="width: 110px; height: 64px; border-radius: 8px; background: var(--bg-card); border: 1px solid var(--border-glass); overflow: hidden; flex-shrink: 0;">
                                <img src="uploads/settings/<?php echo htmlspecialchars($settings['gambar_pondok_path'] ?? 'pondok.png'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="flex-grow: 1;">
                                <label for="gambar_pondok_file" style="margin-bottom: 5px; display: block; font-weight: 500; font-size: 0.85rem; color: var(--text-light);">
                                    <i class="fa-solid fa-images" style="color: var(--gold); margin-right: 4px;"></i> Unggah Gambar Baru
                                </label>
                                <input type="file" id="gambar_pondok_file" name="gambar_pondok_file" class="form-control" accept="image/*" style="font-size: 0.8rem; padding: 5px;">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: var(--transition);">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan
                    </button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>

<?php
session_start();
require_once 'db_connect.php';

// Ambil settings awal untuk pengecekan file yang ada
$settings = getSettings($pdo);

// Handle delete file action before normal save handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_key'])) {
    $deleteKey = trim($_POST['delete_key']);
    // detect ajax
    $isAjaxDelete = (!empty($_POST['ajax']) && $_POST['ajax'] == '1') || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    try {
        if (!empty($settings[$deleteKey])) {
            $toDelete = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . $settings[$deleteKey];
            if (file_exists($toDelete) && is_file($toDelete)) {
                unlink($toDelete);
            }

            // Set setting value to empty
            $stmtDel = $pdo->prepare("INSERT INTO `settings` (`meta_key`, `meta_value`) VALUES (:meta_key, '') ON DUPLICATE KEY UPDATE `meta_value` = ''");
            $stmtDel->execute(['meta_key' => $deleteKey]);

            $message = 'Berkas berhasil dihapus.';
            $messageType = 'success';
        } else {
            $message = 'Berkas tidak ditemukan atau sudah dihapus.';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Gagal menghapus berkas: ' . $e->getMessage();
        $messageType = 'error';
    }

    // Refresh settings after deletion
    $settings = getSettings($pdo);

    if ($isAjaxDelete) {
        header('Content-Type: application/json');
        echo json_encode(['status' => $messageType === 'success' ? 'success' : 'error', 'message' => $message, 'deleted_key' => $deleteKey]);
        exit;
    }
}

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
    // detect ajax request (either X-Requested-With header or explicit field)
    $isAjax = (!empty($_POST['ajax']) && $_POST['ajax'] == '1') || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    $namaPondok = trim($_POST['nama_pondok'] ?? '');
    $alamatPondok = trim($_POST['alamat_pondok'] ?? '');
    $noHpPondok = trim($_POST['no_hp_pondok'] ?? '');
        $namaAdminContact = trim($_POST['nama_admin_contact'] ?? '');
        $rekeningAdmin = trim($_POST['rekening_admin'] ?? '');
    
    // Bersihkan format nomor telepon
    $noHpPondok = preg_replace('/[^0-9]/', '', $noHpPondok);

    try {
        $pdo->beginTransaction();

        $stmtUpdate = $pdo->prepare("INSERT INTO `settings` (`meta_key`, `meta_value`) VALUES (:meta_key, :meta_value) 
            ON DUPLICATE KEY UPDATE `meta_value` = :meta_value_update");
            
        $stmtUpdate->execute(['meta_key' => 'nama_pondok', 'meta_value' => $namaPondok, 'meta_value_update' => $namaPondok]);
        $stmtUpdate->execute(['meta_key' => 'alamat_pondok', 'meta_value' => $alamatPondok, 'meta_value_update' => $alamatPondok]);
        $stmtUpdate->execute(['meta_key' => 'no_hp_pondok', 'meta_value' => $noHpPondok, 'meta_value_update' => $noHpPondok]);
            $stmtUpdate->execute(['meta_key' => 'nama_admin_contact', 'meta_value' => $namaAdminContact, 'meta_value_update' => $namaAdminContact]);
            $stmtUpdate->execute(['meta_key' => 'rekening_admin', 'meta_value' => $rekeningAdmin, 'meta_value_update' => $rekeningAdmin]);
            $infoPendaftaran = trim($_POST['info_pendaftaran'] ?? '');
            $infoBeranda = trim($_POST['info_beranda'] ?? '');
            $stmtUpdate->execute(['meta_key' => 'info_pendaftaran', 'meta_value' => $infoPendaftaran, 'meta_value_update' => $infoPendaftaran]);
            $stmtUpdate->execute(['meta_key' => 'info_beranda', 'meta_value' => $infoBeranda, 'meta_value_update' => $infoBeranda]);

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

        // Upload Background Beranda Baru
        if (isset($_FILES['background_beranda_file']) && $_FILES['background_beranda_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['background_beranda_file']['tmp_name'];
            $name = $_FILES['background_beranda_file']['name'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','webp'];
            if (in_array($ext, $allowed)) {
                $newName = 'background_beranda_' . time() . '.' . $ext;
                $dest = 'uploads/settings/' . $newName;
                if (!is_dir('uploads/settings')) {
                    mkdir('uploads/settings', 0777, true);
                }
                if (move_uploaded_file($tmp, $dest)) {
                    $stmtUpdate->execute(['meta_key' => 'background_beranda_path', 'meta_value' => $newName, 'meta_value_update' => $newName]);
                }
            } else {
                throw new Exception('Format background beranda tidak valid. Gunakan PNG/JPG/JPEG/WEBP.');
            }
        }

        // Upload Gambar Instansi
        if (isset($_FILES['gambar_instansi_file']) && $_FILES['gambar_instansi_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['gambar_instansi_file']['tmp_name'];
            $name = $_FILES['gambar_instansi_file']['name'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','webp'];
            if (in_array($ext, $allowed)) {
                $newName = 'instansi_' . time() . '.' . $ext;
                $dest = 'uploads/settings/' . $newName;
                if (!is_dir('uploads/settings')) {
                    mkdir('uploads/settings', 0777, true);
                }
                if (move_uploaded_file($tmp, $dest)) {
                    $stmtUpdate->execute(['meta_key' => 'gambar_instansi_path', 'meta_value' => $newName, 'meta_value_update' => $newName]);
                }
            } else {
                throw new Exception('Format gambar instansi tidak valid. Gunakan PNG/JPG/JPEG/WEBP.');
            }
        }

        // Upload logos & images per instansi
        $instansiList = [
            'mts' => ['logo_field' => 'mts_logo_file', 'image_field' => 'mts_image_file'],
            'smk' => ['logo_field' => 'smk_logo_file', 'image_field' => 'smk_image_file'],
            'murni' => ['logo_field' => 'murni_logo_file', 'image_field' => 'murni_image_file']
        ];

        foreach ($instansiList as $key => $fields) {
            // logo
            if (isset($_FILES[$fields['logo_field']]) && $_FILES[$fields['logo_field']]['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES[$fields['logo_field']]['tmp_name'];
                $name = $_FILES[$fields['logo_field']]['name'];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['png','jpg','jpeg','svg','webp'];
                if (in_array($ext, $allowed)) {
                    $newName = $key . '_logo_' . time() . '.' . $ext;
                    $dest = 'uploads/settings/' . $newName;
                    if (!is_dir('uploads/settings')) mkdir('uploads/settings', 0777, true);
                    if (move_uploaded_file($tmp, $dest)) {
                        $stmtUpdate->execute(['meta_key' => $key . '_logo', 'meta_value' => $newName, 'meta_value_update' => $newName]);
                    }
                } else {
                    throw new Exception('Format logo ' . $key . ' tidak valid.');
                }
            }

            // image
            if (isset($_FILES[$fields['image_field']]) && $_FILES[$fields['image_field']]['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES[$fields['image_field']]['tmp_name'];
                $name = $_FILES[$fields['image_field']]['name'];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['png','jpg','jpeg','webp'];
                if (in_array($ext, $allowed)) {
                    $newName = $key . '_image_' . time() . '.' . $ext;
                    $dest = 'uploads/settings/' . $newName;
                    if (!is_dir('uploads/settings')) mkdir('uploads/settings', 0777, true);
                    if (move_uploaded_file($tmp, $dest)) {
                        $stmtUpdate->execute(['meta_key' => $key . '_image', 'meta_value' => $newName, 'meta_value_update' => $newName]);
                    }
                } else {
                    throw new Exception('Format gambar ' . $key . ' tidak valid.');
                }
            }
        }

        // Upload Foto Pelengkap (maks 2)
        for ($i = 1; $i <= 2; $i++) {
            $field = 'foto_pelengkap_' . $i . '_file';
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES[$field]['tmp_name'];
                $name = $_FILES[$field]['name'];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['png','jpg','jpeg','webp'];
                if (in_array($ext, $allowed)) {
                    $newName = 'pelengkap_' . $i . '_' . time() . '.' . $ext;
                    $dest = 'uploads/settings/' . $newName;
                    if (!is_dir('uploads/settings')) {
                        mkdir('uploads/settings', 0777, true);
                    }
                    if (move_uploaded_file($tmp, $dest)) {
                        $stmtUpdate->execute(['meta_key' => 'foto_pelengkap_' . $i, 'meta_value' => $newName, 'meta_value_update' => $newName]);
                    }
                } else {
                    throw new Exception('Format foto pelengkap tidak valid. Gunakan PNG/JPG/JPEG/WEBP.');
                }
            }
        }

        // Upload Gallery Images (up to 6)
        for ($g = 1; $g <= 6; $g++) {
            $field = 'gallery_' . $g . '_file';
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES[$field]['tmp_name'];
                $name = $_FILES[$field]['name'];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['png','jpg','jpeg','webp'];
                if (in_array($ext, $allowed)) {
                    $newName = 'gallery_' . $g . '_' . time() . '.' . $ext;
                    $dest = 'uploads/settings/' . $newName;
                    if (!is_dir('uploads/settings')) mkdir('uploads/settings', 0777, true);
                    if (move_uploaded_file($tmp, $dest)) {
                        $stmtUpdate->execute(['meta_key' => 'gallery_' . $g, 'meta_value' => $newName, 'meta_value_update' => $newName]);
                    }
                } else {
                    throw new Exception('Format gallery image tidak valid.');
                }
            }
        }

        $pdo->commit();
        $message = "Pengaturan website berhasil disimpan!";
        $messageType = "success";

        if ($isAjax) {
            // return updated settings so frontend can refresh previews without reload
            $updatedSettings = getSettings($pdo);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => $message, 'settings' => $updatedSettings]);
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = "Gagal menyimpan pengaturan: " . $e->getMessage();
        $messageType = "error";

        if (isset($isAjax) && $isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $message]);
            exit;
        }
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
                <div style="display:flex; gap:10px; align-items:center;">
                    <span style="font-size: 0.85rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); padding: 8px 16px; border-radius: 20px;">
                        <i class="fa-regular fa-calendar-check" style="color: var(--gold); margin-right: 6px;"></i> 
                        <?php echo date('d M Y'); ?>
                    </span>
                    <button type="button" id="saveSettingsTop" class="btn-primary" onclick="document.getElementById('settingsForm').submit();" style="padding:8px 14px; border-radius:8px; font-weight:600; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan
                    </button>
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
                    <div class="<?php echo $messageType === 'success' ? 'alert-success' : 'alert-danger'; ?>" style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px; justify-content: center;" id="serverMessage">
                        <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i> 
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div id="ajaxMessage"></div>

                <form id="settingsForm" action="admin_settings.php" method="POST" enctype="multipart/form-data">
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

                    <div style="margin-bottom: 20px;">
                        <label for="info_pendaftaran" style="display:block; margin-bottom:8px; color:var(--text-light); font-weight:500;"><i class="fa-solid fa-file-contract" style="color:var(--gold); margin-right:6px;"></i> Informasi Pendaftaran (tampil di halaman pendaftaran)</label>
                        <textarea id="info_pendaftaran" name="info_pendaftaran" class="form-control" rows="4" style="width:100%;"><?php echo htmlspecialchars($settings['info_pendaftaran'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label for="info_beranda" style="display:block; margin-bottom:8px; color:var(--text-light); font-weight:500;"><i class="fa-solid fa-house" style="color:var(--gold); margin-right:6px;"></i> Informasi Beranda (tampil di halaman utama)</label>
                        <textarea id="info_beranda" name="info_beranda" class="form-control" rows="4" style="width:100%;"><?php echo htmlspecialchars($settings['info_beranda'] ?? ''); ?></textarea>
                    </div>

                    <!-- Per-instansi Logos & Images -->
                    <div style="margin-bottom: 20px;">
                        <h4 style="color:var(--text-white); margin-bottom: 10px;">Gambar & Logo Per-Instansi</h4>
                        <div style="display:grid; grid-template-columns: repeat(3,1fr); gap:12px;">
                            <!-- MTs -->
                            <div style="background: rgba(255,255,255,0.02); padding:10px; border-radius:8px; border:1px solid var(--border-glass);">
                                <div style="font-weight:600; margin-bottom:8px;">MTs</div>
                                                <div style="height:60px; margin-bottom:8px; overflow:hidden; border-radius:6px; display:flex; align-items:center; justify-content:center; gap:8px;">
                                                    <?php if (!empty($settings['mts_logo'])): ?>
                                                        <img id="img_mts_logo" src="uploads/settings/<?php echo htmlspecialchars($settings['mts_logo']); ?>" style="height:60px; object-fit:contain;">
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus logo MTs?');">
                                                            <input type="hidden" name="delete_key" value="mts_logo">
                                                            <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="file" name="mts_logo_file" accept="image/*" style="margin-bottom:6px;">
                                <div style="height:90px; overflow:hidden; border-radius:6px; margin-top:6px; position:relative;">
                                    <?php if (!empty($settings['mts_image'])): ?>
                                        <img id="img_mts_image" src="uploads/settings/<?php echo htmlspecialchars($settings['mts_image']); ?>" style="width:100%; height:90px; object-fit:cover;">
                                        <form method="POST" style="position:absolute; right:8px; top:8px;">
                                            <input type="hidden" name="delete_key" value="mts_image">
                                            <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="mts_image_file" accept="image/*">
                            </div>

                            <!-- SMK -->
                            <div style="background: rgba(255,255,255,0.02); padding:10px; border-radius:8px; border:1px solid var(--border-glass);">
                                <div style="font-weight:600; margin-bottom:8px;">SMK</div>
                                <div style="height:60px; margin-bottom:8px; overflow:hidden; border-radius:6px; display:flex; align-items:center; justify-content:center; gap:8px;">
                                    <?php if (!empty($settings['smk_logo'])): ?>
                                        <img id="img_smk_logo" src="uploads/settings/<?php echo htmlspecialchars($settings['smk_logo']); ?>" style="height:60px; object-fit:contain;">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus logo SMK?');">
                                            <input type="hidden" name="delete_key" value="smk_logo">
                                            <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="smk_logo_file" accept="image/*" style="margin-bottom:6px;">
                                <div style="height:90px; overflow:hidden; border-radius:6px; margin-top:6px; position:relative;">
                                    <?php if (!empty($settings['smk_image'])): ?>
                                        <img id="img_smk_image" src="uploads/settings/<?php echo htmlspecialchars($settings['smk_image']); ?>" style="width:100%; height:90px; object-fit:cover;">
                                        <form method="POST" style="position:absolute; right:8px; top:8px;">
                                            <input type="hidden" name="delete_key" value="smk_image">
                                            <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="smk_image_file" accept="image/*">
                            </div>

                            <!-- Murni Pondok -->
                            <div style="background: rgba(255,255,255,0.02); padding:10px; border-radius:8px; border:1px solid var(--border-glass);">
                                <div style="font-weight:600; margin-bottom:8px;">Murni Pondok</div>
                                <div style="height:60px; margin-bottom:8px; overflow:hidden; border-radius:6px; display:flex; align-items:center; justify-content:center; gap:8px;">
                                    <?php if (!empty($settings['murni_logo'])): ?>
                                        <img id="img_murni_logo" src="uploads/settings/<?php echo htmlspecialchars($settings['murni_logo']); ?>" style="height:60px; object-fit:contain;">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus logo Murni Pondok?');">
                                            <input type="hidden" name="delete_key" value="murni_logo">
                                            <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="murni_logo_file" accept="image/*" style="margin-bottom:6px;">
                                <div style="height:90px; overflow:hidden; border-radius:6px; margin-top:6px; position:relative;">
                                    <?php if (!empty($settings['murni_image'])): ?>
                                        <img id="img_murni_image" src="uploads/settings/<?php echo htmlspecialchars($settings['murni_image']); ?>" style="width:100%; height:90px; object-fit:cover;">
                                        <form method="POST" style="position:absolute; right:8px; top:8px;">
                                            <input type="hidden" name="delete_key" value="murni_image">
                                            <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="murni_image_file" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <!-- Gallery uploader (6 slots) -->
                    <div style="margin-bottom:20px;">
                        <h4 style="color:var(--text-white); margin-bottom:10px;">Galeri Foto (Dokumentasi)</h4>
                        <div style="display:grid; grid-template-columns: repeat(3,1fr); gap:12px;">
                            <?php for ($gi = 1; $gi <= 6; $gi++): ?>
                                <div style="background: rgba(255,255,255,0.02); padding:10px; border-radius:8px; border:1px solid var(--border-glass); text-align:center; position:relative;">
                                    <?php if (!empty($settings['gallery_' . $gi])): ?>
                                        <div style="height:90px; overflow:hidden; margin-bottom:8px;"><img id="img_gallery_<?php echo $gi; ?>" src="uploads/settings/<?php echo htmlspecialchars($settings['gallery_' . $gi]); ?>" style="width:100%; height:90px; object-fit:cover;"></div>
                                        <form method="POST" style="position:absolute; right:8px; top:8px;">
                                            <input type="hidden" name="delete_key" value="gallery_<?php echo $gi; ?>">
                                            <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                    <input type="file" name="gallery_<?php echo $gi; ?>_file" accept="image/*">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label for="nama_admin_contact" style="margin-bottom: 8px; display: block; font-weight: 500; font-size: 0.9rem; color: var(--text-light);">
                                <i class="fa-solid fa-user-tie" style="color: var(--gold); margin-right: 4px;"></i> Nama Admin Kontak (tampil di publik)
                            </label>
                            <input type="text" id="nama_admin_contact" name="nama_admin_contact" class="form-control" value="<?php echo htmlspecialchars($settings['nama_admin_contact'] ?? ''); ?>" style="width: 100%;">
                        </div>
                        <div class="form-group">
                            <label for="rekening_admin" style="margin-bottom: 8px; display: block; font-weight: 500; font-size: 0.9rem; color: var(--text-light);">
                                <i class="fa-solid fa-credit-card" style="color: var(--gold); margin-right: 4px;"></i> Nomor Rekening Admin
                            </label>
                            <input type="text" id="rekening_admin" name="rekening_admin" class="form-control" value="<?php echo htmlspecialchars($settings['rekening_admin'] ?? ''); ?>" style="width: 100%;" placeholder="Contoh: Bank BRI - 1234567890 a.n. Pondok Pesantren">
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
                            <div style="width: 64px; height: 64px; border-radius: 8px; background: var(--bg-card); border: 1px solid var(--border-glass); overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 4px; flex-shrink: 0; position:relative;">
                            <img id="img_logo_path" src="uploads/settings/<?php echo htmlspecialchars($settings['logo_path'] ?? 'logo.png'); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php if (!empty($settings['logo_path'])): ?>
                                    <form method="POST" style="position:absolute; right:6px; bottom:6px;">
                                        <input type="hidden" name="delete_key" value="logo_path">
                                        <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                    </form>
                                <?php endif; ?>
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
                            <div style="width: 110px; height: 64px; border-radius: 8px; background: var(--bg-card); border: 1px solid var(--border-glass); overflow: hidden; flex-shrink: 0; position:relative;">
                                <img id="img_gambar_pondok_path" src="uploads/settings/<?php echo htmlspecialchars($settings['gambar_pondok_path'] ?? 'pondok.png'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php if (!empty($settings['gambar_pondok_path'])): ?>
                                    <form method="POST" style="position:absolute; right:6px; bottom:6px;">
                                        <input type="hidden" name="delete_key" value="gambar_pondok_path">
                                        <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div style="flex-grow: 1;">
                                <label for="gambar_pondok_file" style="margin-bottom: 5px; display: block; font-weight: 500; font-size: 0.85rem; color: var(--text-light);">
                                    <i class="fa-solid fa-images" style="color: var(--gold); margin-right: 4px;"></i> Unggah Gambar Baru
                                </label>
                                <input type="file" id="gambar_pondok_file" name="gambar_pondok_file" class="form-control" accept="image/*" style="font-size: 0.8rem; padding: 5px;">
                            </div>
                        </div>
                        
                        <!-- Background Beranda Upload -->
                        <div class="form-group" style="display: flex; gap: 15px; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); padding: 15px; border-radius: var(--border-radius);">
                            <div style="width: 110px; height: 64px; border-radius: 8px; background: var(--bg-card); border: 1px solid var(--border-glass); overflow: hidden; flex-shrink: 0; position:relative;">
                                <img id="img_background_beranda_path" src="uploads/settings/<?php echo htmlspecialchars($settings['background_beranda_path'] ?? ($settings['gambar_pondok_path'] ?? 'pondok.png')); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php if (!empty($settings['background_beranda_path'])): ?>
                                    <form method="POST" style="position:absolute; right:6px; bottom:6px;">
                                        <input type="hidden" name="delete_key" value="background_beranda_path">
                                        <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div style="flex-grow: 1;">
                                <label for="background_beranda_file" style="margin-bottom: 5px; display: block; font-weight: 500; font-size: 0.85rem; color: var(--text-light);">
                                    <i class="fa-solid fa-photo-film" style="color: var(--gold); margin-right: 4px;"></i> Unggah Background Beranda
                                </label>
                                <input type="file" id="background_beranda_file" name="background_beranda_file" class="form-control" accept="image/*" style="font-size: 0.8rem; padding: 5px;">
                            </div>
                        </div>
                    </div>

                    <!-- Tambahan Foto Pelengkap -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 35px;">
                        <div class="form-group" style="display: flex; gap: 15px; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); padding: 15px; border-radius: var(--border-radius);">
                                <div style="width: 64px; height: 64px; border-radius: 8px; background: var(--bg-card); border: 1px solid var(--border-glass); overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 4px; flex-shrink: 0; position:relative;">
                                <img id="img_gambar_instansi_path" src="uploads/settings/<?php echo htmlspecialchars($settings['gambar_instansi_path'] ?? 'pondok.png'); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php if (!empty($settings['gambar_instansi_path'])): ?>
                                    <form method="POST" style="position:absolute; right:6px; bottom:6px;">
                                        <input type="hidden" name="delete_key" value="gambar_instansi_path">
                                        <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div style="flex-grow: 1;">
                                <label for="gambar_instansi_file" style="margin-bottom: 5px; display: block; font-weight: 500; font-size: 0.85rem; color: var(--text-light);">
                                    <i class="fa-solid fa-building" style="color: var(--gold); margin-right: 4px;"></i> Unggah Foto Instansi
                                </label>
                                <input type="file" id="gambar_instansi_file" name="gambar_instansi_file" class="form-control" accept="image/*" style="font-size: 0.8rem; padding: 5px;">
                            </div>
                        </div>

                        <div class="form-group" style="display: flex; gap: 15px; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); padding: 15px; border-radius: var(--border-radius);">
                                <div style="width: 64px; height: 64px; border-radius: 8px; background: var(--bg-card); border: 1px solid var(--border-glass); overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 4px; flex-shrink: 0; position:relative;">
                                <img id="img_foto_pelengkap_1" src="uploads/settings/<?php echo htmlspecialchars($settings['foto_pelengkap_1'] ?? 'pondok.png'); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php if (!empty($settings['foto_pelengkap_1'])): ?>
                                    <form method="POST" style="position:absolute; right:6px; bottom:6px;">
                                        <input type="hidden" name="delete_key" value="foto_pelengkap_1">
                                        <button type="submit" class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div style="flex-grow: 1;">
                                <label for="foto_pelengkap_1_file" style="margin-bottom: 5px; display: block; font-weight: 500; font-size: 0.85rem; color: var(--text-light);">
                                    <i class="fa-solid fa-photo" style="color: var(--gold); margin-right: 4px;"></i> Foto Pelengkap 1
                                </label>
                                <input type="file" id="foto_pelengkap_1_file" name="foto_pelengkap_1_file" class="form-control" accept="image/*" style="font-size: 0.8rem; padding: 5px;">
                            </div>
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
    <script>
        (function(){
            const form = document.getElementById('settingsForm');
            const topBtn = document.getElementById('saveSettingsTop');
            const bottomBtn = form ? form.querySelector('button[type="submit"]') : null;
            const ajaxMessage = document.getElementById('ajaxMessage');

            function setButtonsDisabled(disabled){
                if(topBtn) topBtn.disabled = disabled;
                if(bottomBtn) bottomBtn.disabled = disabled;
            }

            function showMessage(status, text){
                const cls = status === 'success' ? 'alert-success' : 'alert-danger';
                ajaxMessage.innerHTML = `<div class="${cls}" style="margin-bottom:25px; display:flex; align-items:center; gap:10px; justify-content:center;"><i class="fa-solid ${status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i> ${text}</div>`;
            }

            if (form) {
                form.addEventListener('submit', function(e){
                    // If user clicked the standard submit button (no JS) we still intercept and use AJAX
                    e.preventDefault();
                    setButtonsDisabled(true);

                    const fd = new FormData(form);
                    fd.append('ajax','1');

                    fetch(form.action, { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            if (!data) throw new Error('Invalid server response');
                            if (data.status === 'success') {
                                showMessage('success', data.message || 'Berhasil disimpan');
                                // update preview images if provided
                                if (data.settings) {
                                    Object.keys(data.settings).forEach(function(k){
                                        const el = document.getElementById('img_' + k);
                                        if (el && data.settings[k]) {
                                            el.src = 'uploads/settings/' + data.settings[k];
                                        }
                                    });
                                }
                            } else {
                                showMessage('error', data.message || 'Gagal menyimpan');
                            }
                        })
                        .catch(err => {
                            showMessage('error', 'Terjadi kesalahan: ' + err.message);
                        })
                        .finally(()=> setButtonsDisabled(false));
                });
            }

                        // Attach AJAX handlers for the inline delete forms (input[name=delete_key])
                        document.querySelectorAll('form').forEach(function(f){
                            const delInput = f.querySelector('input[name="delete_key"]');
                            if (!delInput) return;

                            f.addEventListener('submit', function(ev){
                                ev.preventDefault();
                                if (!confirm('Yakin ingin menghapus berkas ini?')) return;
                                setButtonsDisabled(true);
                                const fd = new FormData(f);
                                fd.append('ajax','1');

                                fetch(f.action || 'admin_settings.php', { method: 'POST', body: fd })
                                    .then(r => r.json())
                                    .then(data => {
                                        if (!data) throw new Error('Invalid server response');
                                        if (data.status === 'success') {
                                            showMessage('success', data.message || 'Dihapus');
                                            // update preview image if exists
                                            const key = data.deleted_key || fd.get('delete_key');
                                            const img = document.getElementById('img_' + key);
                                            if (img) {
                                                // replace with a default placeholder if available
                                                img.src = 'uploads/settings/pondok.png';
                                            }
                                            // remove the delete form button to reflect deletion
                                            try { f.remove(); } catch (e) {}
                                        } else {
                                            showMessage('error', data.message || 'Gagal menghapus');
                                        }
                                    })
                                    .catch(err => showMessage('error', 'Terjadi kesalahan: ' + err.message))
                                    .finally(()=> setButtonsDisabled(false));
                            });
                        });
        })();
    </script>
</body>
</html>

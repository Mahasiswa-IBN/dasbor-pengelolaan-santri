<?php
require_once 'db_connect.php';

$settings = getSettings($pdo);
$namaPondok = $settings['nama_pondok'] ?? 'Pondok Pesantren Al-Barokah';
$noHpPondok = $settings['no_hp_pondok'] ?? '6281234567890';

$errorMsg = '';
$successData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Ambil & Sanitasi Data Text
        $nama_lengkap = htmlspecialchars(strip_tags(trim($_POST['nama_lengkap'] ?? '')));
        $nama_panggilan = htmlspecialchars(strip_tags(trim($_POST['nama_panggilan'] ?? '')));
        $jenis_kelamin = htmlspecialchars(strip_tags(trim($_POST['jenis_kelamin'] ?? '')));
        $tempat_lahir = htmlspecialchars(strip_tags(trim($_POST['tempat_lahir'] ?? '')));
        $tanggal_lahir = htmlspecialchars(strip_tags(trim($_POST['tanggal_lahir'] ?? '')));
        $alamat = htmlspecialchars(strip_tags(trim($_POST['alamat'] ?? '')));
        $no_hp = htmlspecialchars(strip_tags(trim($_POST['no_hp'] ?? '')));
        $instansi = htmlspecialchars(strip_tags(trim($_POST['instansi'] ?? '')));
        $sekolah_asal = htmlspecialchars(strip_tags(trim($_POST['sekolah_asal'] ?? '')));
        $nama_ortu = htmlspecialchars(strip_tags(trim($_POST['nama_ortu'] ?? '')));
        $no_hp_ortu = htmlspecialchars(strip_tags(trim($_POST['no_hp_ortu'] ?? '')));

        // Validasi input wajib
        if (empty($nama_lengkap) || empty($nama_panggilan) || empty($jenis_kelamin) || empty($tempat_lahir) || 
            empty($tanggal_lahir) || empty($alamat) || empty($no_hp) || empty($instansi) || 
            empty($sekolah_asal) || empty($nama_ortu) || empty($no_hp_ortu)) {
            throw new Exception("Semua formulir bertanda wajib harus diisi!");
        }

        // 2. Fungsi Helper Upload File
        function uploadDokumen($fileKey, $destFolder, $allowedExts) {
            if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Gagal mengunggah berkas: " . str_replace('file_', '', $fileKey));
            }
            
            $file = $_FILES[$fileKey];
            $filename = $file['name'];
            $tmpName = $file['tmp_name'];
            $size = $file['size'];
            
            // Cek ukuran file (Maks 2MB)
            if ($size > 2 * 1024 * 1024) {
                throw new Exception("Ukuran berkas " . $filename . " terlalu besar (Maks 2MB).");
            }
            
            // Cek ekstensi file
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) {
                throw new Exception("Format berkas " . $filename . " tidak diizinkan. Hanya berkas " . implode(', ', $allowedExts) . " yang diperbolehkan.");
            }
            
            // Buat nama unik baru
            $newFilename = time() . '_' . uniqid() . '.' . $ext;
            // Pastikan folder tujuan ada
            if (!is_dir($destFolder)) {
                if (!mkdir($destFolder, 0755, true)) {
                    throw new Exception("Gagal membuat folder tujuan untuk menyimpan berkas.");
                }
            }

            $destPath = rtrim($destFolder, '/\\') . DIRECTORY_SEPARATOR . $newFilename;

            if (!move_uploaded_file($tmpName, $destPath)) {
                throw new Exception("Gagal menyimpan berkas di server.");
            }
            
            return $newFilename;
        }

        // 3. Eksekusi Upload Berkas
        $allowedDocs = ['pdf', 'jpg', 'jpeg', 'png'];
        $allowedFoto = ['jpg', 'jpeg', 'png'];

        $file_foto = uploadDokumen('file_foto', 'uploads/foto/', $allowedFoto);
        $file_skl = uploadDokumen('file_skl', 'uploads/skl/', $allowedDocs);
        $file_kk = uploadDokumen('file_kk', 'uploads/kk/', $allowedDocs);
        $file_akte = uploadDokumen('file_akte', 'uploads/akte/', $allowedDocs);
        // Upload bukti pembayaran (baru)
        $file_bukti = uploadDokumen('file_bukti_bayar', 'uploads/payment/', $allowedDocs);

        // 4. Simpan ke Database
        $token = bin2hex(random_bytes(16));
        $sql = "INSERT INTO `santri` 
            (`nama_lengkap`, `nama_panggilan`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `no_hp`, `instansi`, `sekolah_asal`, `nama_ortu`, `no_hp_ortu`, `file_skl`, `file_kk`, `file_akte`, `file_foto`, `file_bukti`, `status`, `token`) 
            VALUES 
            (:nama_lengkap, :nama_panggilan, :jenis_kelamin, :tempat_lahir, :tanggal_lahir, :alamat, :no_hp, :instansi, :sekolah_asal, :nama_ortu, :no_hp_ortu, :file_skl, :file_kk, :file_akte, :file_foto, :file_bukti, 'Pending', :token)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nama_lengkap' => $nama_lengkap,
            'nama_panggilan' => $nama_panggilan,
            'jenis_kelamin' => $jenis_kelamin,
            'tempat_lahir' => $tempat_lahir,
            'tanggal_lahir' => $tanggal_lahir,
            'alamat' => $alamat,
            'no_hp' => $no_hp,
            'instansi' => $instansi,
            'sekolah_asal' => $sekolah_asal,
            'nama_ortu' => $nama_ortu,
            'no_hp_ortu' => $no_hp_ortu,
            'file_skl' => $file_skl,
            'file_kk' => $file_kk,
            'file_akte' => $file_akte,
            'file_foto' => $file_foto,
            'file_bukti' => $file_bukti,
            'token' => $token
        ]);

        header('Location: submit_registration.php?token=' . $token . '&success=1');
        exit;

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = htmlspecialchars(strip_tags(trim($_GET['token'] ?? '')));
    if (empty($token)) {
        header('Location: register.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `santri` WHERE `token` = :token");
        $stmt->execute(['token' => $token]);
        $santri = $stmt->fetch();

        if (!$santri) {
            throw new Exception("Data pendaftaran dengan token tersebut tidak ditemukan.");
        }

        $regNo = 'REG-' . date('Y', strtotime($santri['created_at'])) . '-' . str_pad($santri['id'], 4, '0', STR_PAD_LEFT);
        $successData = [
            'reg_no' => $regNo,
            'nama_lengkap' => $santri['nama_lengkap'],
            'instansi' => $santri['instansi'],
            'status' => $santri['status'],
            'token' => $santri['token'],
            'santri' => $santri
        ];
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
} else {
    header('Location: register.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pendaftaran - <?php echo htmlspecialchars($namaPondok); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS tambahan untuk merapikan print-area saat window.print() dijalankan */
        #print-area {
            display: none;
        }
        @media print {
            /* Sembunyikan seluruh isi body asli */
            body {
                background: #fff !important;
                color: #000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .gradient-bg, .form-container, .navbar, footer {
                display: none !important;
            }
            /* Tampilkan area cetak */
            #print-area {
                display: block !important;
                visibility: visible !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                color: #000 !important;
            }
            #print-area * {
                visibility: visible !important;
                color: #000 !important;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-bg no-print"></div>

    <div class="form-container no-print" style="max-width: 600px; margin-top: 100px; margin-bottom: 50px;">
        <?php if (!empty($errorMsg)): ?>
            <!-- Card Error -->
            <div class="success-card">
                <div class="success-icon" style="background: rgba(231, 76, 60, 0.1); color: var(--danger); box-shadow: 0 0 20px rgba(231, 76, 60, 0.2);">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h2 style="color: var(--text-white); margin-bottom: 15px; font-family: 'Outfit';">Pendaftaran Gagal</h2>
                <p style="color: var(--text-muted); margin-bottom: 30px;"><?php echo $errorMsg; ?></p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <a href="javascript:history.back()" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
                    <a href="index.php" class="btn-primary" style="background: rgba(255,255,255,0.05); color: var(--text-white); border: 1px solid var(--border-glass); box-shadow: none;">Beranda</a>
                </div>
            </div>
        <?php else: ?>
            <?php
            $santri = $successData['santri'];
            $status = $successData['status'];
            $statusBadgeClass = strtolower($status);
            
            $statusTitle = "Pendaftaran Berhasil!";
            $statusDesc = "Formulir pendaftaran calon santri telah tersimpan di sistem kami.";
            $statusIcon = "fa-solid fa-circle-check";
            $statusColor = "var(--success)";
            
            if (!isset($_GET['success'])) {
                // Halaman dibuka ulang lewat link unik
                $statusTitle = "Status Pendaftaran";
                if ($status === 'Pending') {
                    $statusDesc = "Berkas pendaftaran Anda sedang dalam antrean verifikasi oleh Panitia PPDB.";
                    $statusIcon = "fa-solid fa-hourglass-half";
                    $statusColor = "var(--gold)";
                } else if ($status === 'Verified') {
                    $statusDesc = "Selamat! Pendaftaran Anda telah disetujui dan diverifikasi oleh Panitia.";
                    $statusIcon = "fa-solid fa-circle-check";
                    $statusColor = "#2ecc71";
                } else if ($status === 'Rejected') {
                    $statusDesc = "Maaf, berkas pendaftaran Anda ditolak. Silakan hubungi Panitia untuk informasi lebih lanjut.";
                    $statusIcon = "fa-solid fa-circle-xmark";
                    $statusColor = "#e74c3c";
                }
            }
            ?>
            <!-- Card Success -->
            <div class="success-card">
                <div class="success-icon" style="color: <?php echo $statusColor; ?>; border-color: <?php echo $statusColor; ?>; box-shadow: 0 0 20px <?php echo $statusColor; ?>33;">
                    <i class="<?php echo $statusIcon; ?>"></i>
                </div>
                <h2 style="color: var(--text-white); margin-bottom: 10px; font-family: 'Outfit';"><?php echo $statusTitle; ?></h2>
                <p style="color: var(--text-muted); margin-bottom: 25px;"><?php echo $statusDesc; ?></p>
                
                <!-- Info Pendaftaran -->
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: 12px; padding: 20px; text-align: left; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 10px 0;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">No. Registrasi:</span>
                        <strong style="color: var(--gold); font-family: 'Outfit';"><?php echo $successData['reg_no']; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 10px 0;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Nama Lengkap:</span>
                        <strong style="color: var(--text-white);"><?php echo $successData['nama_lengkap']; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 10px 0;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Instansi Pilihan:</span>
                        <strong style="color: var(--text-white);"><span class="badge instansi <?php echo strtolower(str_replace(' ', '', $successData['instansi'])); ?>"><?php echo $successData['instansi']; ?></span></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 10px 0;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Status Verifikasi:</span>
                        <strong style="color: var(--text-white);"><span class="badge status-badge <?php echo $statusBadgeClass; ?>"><?php echo $status; ?></span></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Tautan Unik Pendaftaran:</span>
                        <div style="display: flex; gap: 5px; width: 100%; max-width: 320px;">
                            <?php 
                            $actualLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                            // Bersihkan parameter success jika ada agar tautan bersih
                            $cleanLink = preg_replace('/&success=1|success=1&?/', '', $actualLink);
                            $cleanLink = rtrim($cleanLink, '?');
                            ?>
                            <input type="text" value="<?php echo htmlspecialchars($cleanLink); ?>" readonly id="uniqueRegLink" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-glass); color: var(--text-white); padding: 6px 10px; border-radius: 6px; font-size: 0.8rem; flex-grow: 1;">
                            <button type="button" onclick="copyUniqueLink()" style="background: var(--gold); border: none; color: var(--bg-dark); padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;"><i class="fa-regular fa-copy"></i> Salin</button>
                        </div>
                    </div>
                </div>

                <!-- Panduan Download PDF -->
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: 12px; padding: 20px; text-align: left; margin-bottom: 25px;">
                    <h3 style="color: var(--text-white); font-family: 'Outfit'; font-size: 1rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-circle-question" style="color: var(--gold);"></i> Panduan Simpan Bukti Pendaftaran (PDF)
                    </h3>
                    <ol style="color: var(--text-muted); font-size: 0.85rem; padding-left: 20px; line-height: 1.6; margin: 0;">
                        <li>Klik tombol <strong style="color: var(--text-white);">Cetak Bukti Pendaftaran</strong> di bawah.</li>
                        <li>Pada opsi <strong style="color: var(--text-white);">Tujuan / Destination</strong> di halaman cetak browser, ubah printer menjadi <strong style="color: var(--gold);">Simpan sebagai PDF (Save as PDF)</strong>.</li>
                        <li>Atur ukuran kertas ke <strong style="color: var(--text-white);">A4</strong>, lalu klik <strong style="color: var(--text-white);">Simpan (Save)</strong> untuk mengunduh dokumen PDF.</li>
                    </ol>
                </div>

                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 30px; line-height: 1.5; text-align: left;">
                    <i class="fa-solid fa-circle-info" style="color: var(--gold); margin-right: 5px;"></i> 
                    Langkah selanjutnya: Harap simpan tautan unik di atas untuk memantau status verifikasi pendaftaran Anda. Hubungi Panitia PPDB Offline melalui WhatsApp untuk validasi berkas fisik dan keuangan lebih lanjut.
                </p>

                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <button onclick="window.print()" class="btn-primary" style="background: var(--gold); color: var(--bg-dark); font-weight: 600; cursor: pointer;"><i class="fa-solid fa-print"></i> Cetak Bukti Pendaftaran</button>
                    <?php $adminContactLabel = !empty($settings['nama_admin_contact']) ? $settings['nama_admin_contact'] : 'Admin'; ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $noHpPondok); ?>?text=Assalamu'alaikum,%20saya%20ingin%20mengonfirmasi%20pendaftaran%20santri%20baru%20di%20<?php echo urlencode($namaPondok); ?>%20dengan%20No.%20Registrasi%20<?php echo $successData['reg_no']; ?>" target="_blank" class="btn-primary"><i class="fa-brands fa-whatsapp"></i> Hubungi <?php echo htmlspecialchars($adminContactLabel); ?></a>
                    <a href="index.php" class="btn-secondary" style="width: 100%; text-align: center; margin-top: 5px;"><i class="fa-solid fa-house"></i> Halaman Utama</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($errorMsg) && isset($successData)): ?>
    <!-- AREA KHUSUS UNTUK DICETAK -->
    <div id="print-area">
        <!-- Kop Surat -->
        <div style="text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 20px;">
            <img src="<?php echo 'uploads/settings/' . ($settings['logo_path'] ?? 'logo.png'); ?>" alt="Logo" style="width: 75px; height: 75px; object-fit: contain;">
            <div style="text-align: center;">
                <h1 style="font-size: 1.6rem; margin: 0; text-transform: uppercase; font-family: sans-serif; font-weight: bold;"><?php echo htmlspecialchars($namaPondok); ?></h1>
                <p style="font-size: 0.85rem; margin: 5px 0 0 0; color: #222; font-style: italic;"><?php echo htmlspecialchars($settings['alamat_pondok'] ?? ''); ?></p>
                <p style="font-size: 0.85rem; margin: 2px 0 0 0; color: #222;">Telepon/WA: <?php echo htmlspecialchars($noHpPondok); ?></p>
            </div>
        </div>
        
        <!-- Judul Bukti Pendaftaran -->
        <div style="text-align: center; margin-bottom: 25px;">
            <h2 style="font-size: 1.25rem; margin: 0; text-decoration: underline; text-transform: uppercase; font-family: sans-serif; font-weight: bold;">KARTU BUKTI PENDAFTARAN PPDB ONLINE</h2>
            <p style="font-size: 0.9rem; margin: 5px 0 0 0;">Tahun Ajaran: <?php echo date('Y') . '/' . (date('Y') + 1); ?></p>
        </div>

        <!-- Layout Data Detail dan Foto Pas -->
        <div style="display: flex; gap: 30px; margin-bottom: 30px; align-items: flex-start;">
            <!-- Tabel Data Santri -->
            <table style="width: 75%; border-collapse: collapse; font-size: 0.95rem; font-family: sans-serif;">
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="width: 35%; padding: 8px 0; font-weight: bold; vertical-align: top;">No. Registrasi</td>
                    <td style="width: 3%; padding: 8px 0; vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top; color: #d35400 !important; font-weight: bold; font-size: 1.05rem;"><?php echo $successData['reg_no']; ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Tanggal Pendaftaran</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top;"><?php echo date('d F Y H:i', strtotime($santri['created_at'])); ?> WIB</td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Nama Lengkap</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top; font-weight: bold; text-transform: uppercase;"><?php echo htmlspecialchars($santri['nama_lengkap']); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Jenis Kelamin</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top;"><?php echo $santri['jenis_kelamin'] === 'L' ? 'Laki-laki (Santri)' : 'Perempuan (Santriwati)'; ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Tempat, Tanggal Lahir</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top;"><?php echo htmlspecialchars($santri['tempat_lahir']) . ', ' . date('d F Y', strtotime($santri['tanggal_lahir'])); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Alamat</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($santri['alamat'])); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">No. HP / WA</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top;"><?php echo htmlspecialchars($santri['no_hp']); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Instansi Pendidikan</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top; font-weight: bold;"><?php echo htmlspecialchars($santri['instansi']); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Sekolah Asal</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top;"><?php echo htmlspecialchars($santri['sekolah_asal']); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Nama Orang Tua / Wali</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top;"><?php echo htmlspecialchars($santri['nama_ortu']); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Status Akun</td>
                    <td style="vertical-align: top;">:</td>
                    <td style="padding: 8px 0; vertical-align: top; font-weight: bold;"><?php echo $santri['status']; ?></td>
                </tr>
            </table>

            <!-- Foto Pas Pendaftar -->
            <div style="width: 25%; text-align: center; border: 1px dashed #777; padding: 5px; border-radius: 4px;">
                <img src="<?php echo 'uploads/foto/' . htmlspecialchars($santri['file_foto']); ?>" alt="Foto Pendaftar" style="width: 100%; height: auto; max-height: 220px; object-fit: cover; border-radius: 2px;">
                <div style="font-size: 0.75rem; font-weight: bold; margin-top: 5px; color: #444;">FOTO CALON SANTRI</div>
            </div>
        </div>

        <!-- Kolom Tanda Tangan & QR Code -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 50px; font-family: sans-serif;">
            <!-- QR Code -->
            <div style="text-align: center; border: 1px solid #aaa; padding: 10px; border-radius: 8px; width: 140px; background-color: #fff;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=<?php echo urlencode($cleanLink); ?>" alt="QR Code Link" style="width: 110px; height: 110px;">
                <div style="font-size: 0.65rem; color: #444; margin-top: 5px; font-weight: bold; line-height: 1.2;">Scan QR Code untuk cek keabsahan data</div>
            </div>
            
            <!-- TTD Calon Santri -->
            <div style="text-align: center; width: 220px; font-size: 0.9rem;">
                <p style="margin: 0 0 60px 0;">Calon Santri,</p>
                <strong style="text-decoration: underline; text-transform: uppercase;"><?php echo htmlspecialchars($santri['nama_lengkap']); ?></strong>
            </div>

            <!-- TTD Panitia -->
            <div style="text-align: center; width: 220px; font-size: 0.9rem;">
                <p style="margin: 0 0 60px 0;">Panitia PPDB,</p>
                <div style="border-bottom: 1px solid #000; width: 180px; margin: 0 auto; height: 1px;"></div>
            </div>
        </div>
    </div>

    <!-- Script copy link -->
    <script>
    function copyUniqueLink() {
        const copyText = document.getElementById("uniqueRegLink");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        
        // Custom micro-alert
        alert("Tautan unik pendaftaran berhasil disalin ke clipboard!");
    }
    </script>
    <?php endif; ?>
</body>
</html>

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
            $destPath = $destFolder . $newFilename;
            
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

        // 4. Simpan ke Database
        $sql = "INSERT INTO `santri` 
                (`nama_lengkap`, `nama_panggilan`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `no_hp`, `instansi`, `sekolah_asal`, `nama_ortu`, `no_hp_ortu`, `file_skl`, `file_kk`, `file_akte`, `file_foto`, `status`) 
                VALUES 
                (:nama_lengkap, :nama_panggilan, :jenis_kelamin, :tempat_lahir, :tanggal_lahir, :alamat, :no_hp, :instansi, :sekolah_asal, :nama_ortu, :no_hp_ortu, :file_skl, :file_kk, :file_akte, :file_foto, 'Pending')";
        
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
            'file_foto' => $file_foto
        ]);

        $insertedId = $pdo->lastInsertId();
        $regNo = 'REG-' . date('Y') . '-' . str_pad($insertedId, 4, '0', STR_PAD_LEFT);

        $successData = [
            'reg_no' => $regNo,
            'nama_lengkap' => $nama_lengkap,
            'instansi' => $instansi
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
</head>
<body>
    <div class="gradient-bg"></div>

    <div class="form-container" style="max-width: 600px; margin-top: 100px;">
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
            <!-- Card Success -->
            <div class="success-card">
                <div class="success-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h2 style="color: var(--text-white); margin-bottom: 10px; font-family: 'Outfit';">Pendaftaran Berhasil!</h2>
                <p style="color: var(--text-muted); margin-bottom: 25px;">Formulir pendaftaran calon santri telah tersimpan di sistem kami.</p>
                
                <!-- Info Pendaftaran -->
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: 12px; padding: 20px; text-align: left; margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 10px 0;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">No. Registrasi:</span>
                        <strong style="color: var(--gold); font-family: 'Outfit';"><?php echo $successData['reg_no']; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 10px 0;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Nama Lengkap:</span>
                        <strong style="color: var(--text-white);"><?php echo $successData['nama_lengkap']; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Instansi Pilihan:</span>
                        <strong style="color: var(--text-white);"><span class="badge instansi <?php echo strtolower(str_replace(' ', '', $successData['instansi'])); ?>"><?php echo $successData['instansi']; ?></span></strong>
                    </div>
                </div>

                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 30px; line-height: 1.5;">
                    <i class="fa-solid fa-circle-info" style="color: var(--gold);"></i> 
                    Langkah selanjutnya: Silakan simpan nomor registrasi Anda. Hubungi Panitia PPDB Offline untuk proses validasi berkas administrasi dan keuangan lebih lanjut.
                </p>

                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="index.php" class="btn-secondary"><i class="fa-solid fa-house"></i> Halaman Utama</a>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $noHpPondok); ?>?text=Assalamu'alaikum,%20saya%20ingin%20mengonfirmasi%20pendaftaran%20santri%20baru%20di%20<?php echo urlencode($namaPondok); ?>%20dengan%20No.%20Registrasi%20<?php echo $successData['reg_no']; ?>" target="_blank" class="btn-primary"><i class="fa-brands fa-whatsapp"></i> Hubungi Admin (Person 3)</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

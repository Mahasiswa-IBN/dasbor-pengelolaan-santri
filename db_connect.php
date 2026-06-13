<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'webppdbrenal';

try {
    // 1. Koneksi awal tanpa database untuk memastikan DB ada
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buat database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 2. Koneksi ulang dengan menyertakan nama database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 3. Buat Tabel `users` (Admin) jika belum ada
    $sqlUsers = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nama` VARCHAR(100) NOT NULL,
        `role` ENUM('Admin Utama', 'Admin MTs', 'Admin SMK') NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlUsers);

    // 4. Buat Tabel `santri` jika belum ada
    $sqlSantri = "CREATE TABLE IF NOT EXISTS `santri` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nama_lengkap` VARCHAR(100) NOT NULL,
        `nama_panggilan` VARCHAR(50) NOT NULL,
        `jenis_kelamin` ENUM('L', 'P') NOT NULL,
        `tempat_lahir` VARCHAR(50) NOT NULL,
        `tanggal_lahir` DATE NOT NULL,
        `alamat` TEXT NOT NULL,
        `no_hp` VARCHAR(20) NOT NULL,
        `instansi` ENUM('MTs', 'SMK', 'Murni Pondok') NOT NULL,
        `sekolah_asal` VARCHAR(100) NOT NULL,
        `nama_ortu` VARCHAR(100) NOT NULL,
        `no_hp_ortu` VARCHAR(20) NOT NULL,
        `file_skl` VARCHAR(255) NOT NULL,
        `file_kk` VARCHAR(255) NOT NULL,
        `file_akte` VARCHAR(255) NOT NULL,
        `file_foto` VARCHAR(255) NOT NULL,
        `status` ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
        `token` VARCHAR(64) UNIQUE NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlSantri);

    // Tambahkan kolom token jika belum ada (migrasi otomatis)
    $checkToken = $pdo->query("SHOW COLUMNS FROM `santri` LIKE 'token'");
    if (!$checkToken->fetch()) {
        $pdo->exec("ALTER TABLE `santri` ADD COLUMN `token` VARCHAR(64) NULL UNIQUE AFTER `status`");
        
        // Isi token untuk data lama jika ada
        $stmt = $pdo->query("SELECT id FROM `santri` WHERE `token` IS NULL");
        $oldRecords = $stmt->fetchAll();
        if ($oldRecords) {
            $updateStmt = $pdo->prepare("UPDATE `santri` SET `token` = :token WHERE id = :id");
            foreach ($oldRecords as $row) {
                $uniqueToken = bin2hex(random_bytes(16));
                $updateStmt->execute(['token' => $uniqueToken, 'id' => $row['id']]);
            }
        }
    }

    // 5. Buat Tabel `settings` jika belum ada
    $sqlSettings = "CREATE TABLE IF NOT EXISTS `settings` (
        `meta_key` VARCHAR(50) PRIMARY KEY,
        `meta_value` TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlSettings);

    // 6. Seed default users jika tabel `users` masih kosong
    $stmt = $pdo->query("SELECT COUNT(*) FROM `users`");
    if ($stmt->fetchColumn() == 0) {
        $admins = [
            [
                'username' => 'admin.utama',
                'password' => password_hash('adminbarokah123', PASSWORD_DEFAULT),
                'nama' => 'H. Ahmad Fauzi (Admin Utama)',
                'role' => 'Admin Utama'
            ],
            [
                'username' => 'admin.mts',
                'password' => password_hash('adminmts123', PASSWORD_DEFAULT),
                'nama' => 'Ust. Syarifuddin (Admin MTs)',
                'role' => 'Admin MTs'
            ],
            [
                'username' => 'admin.smk',
                'password' => password_hash('adminsmk123', PASSWORD_DEFAULT),
                'nama' => 'Ust. Khoirul Anam (Admin SMK)',
                'role' => 'Admin SMK'
            ]
        ];

        $insertStmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `nama`, `role`) VALUES (:username, :password, :nama, :role)");
        foreach ($admins as $admin) {
            $insertStmt->execute($admin);
        }
    }

    // 7. Seed default settings jika tabel `settings` kosong
    $stmtSettingsCount = $pdo->query("SELECT COUNT(*) FROM `settings`");
    if ($stmtSettingsCount->fetchColumn() == 0) {
        $defaultSettings = [
            'nama_pondok' => 'Pondok Pesantren Al-Barokah',
            'alamat_pondok' => 'Jl. Pondok Pesantren No. 45, Kecamatan Sukamakmur, Jawa Barat',
            'no_hp_pondok' => '6281234567890',
            'logo_path' => 'logo.png',
            'gambar_pondok_path' => 'pondok.png'
        ];

        $insertSettingStmt = $pdo->prepare("INSERT INTO `settings` (`meta_key`, `meta_value`) VALUES (:meta_key, :meta_value)");
        foreach ($defaultSettings as $key => $val) {
            $insertSettingStmt->execute([
                'meta_key' => $key,
                'meta_value' => $val
            ]);
        }
    }

} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// Helper function untuk mengambil semua settings
function getSettings($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM `settings`");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['meta_key']] = $row['meta_value'];
        }
        return $settings;
    } catch (PDOException $e) {
        return [];
    }
}
?>

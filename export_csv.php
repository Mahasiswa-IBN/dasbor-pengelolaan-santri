<?php
session_start();
require_once 'db_connect.php';

// Validasi session login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$settings = getSettings($pdo);
$namaPondok = $settings['nama_pondok'] ?? 'Pondok Pesantren Al-Barokah';

// Set headers untuk download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_pendaftaran_santri_' . date('Ymd_His') . '.csv"');

// Output UTF-8 BOM agar terbaca dengan benar di MS Excel (mencegah karakter acak)
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header kolom CSV
fputcsv($output, [
    'No. Registrasi',
    'Tanggal Daftar',
    'Nama Lengkap',
    'Nama Panggilan',
    'Jenis Kelamin',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Alamat',
    'No. HP / WA',
    'Instansi',
    'Sekolah Asal',
    'Nama Orang Tua / Wali',
    'No. HP Orang Tua',
    'Status Verifikasi',
    'Tautan Bukti Pendaftaran'
]);

// Buat basis URL tautan pendaftaran unik
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . str_replace('export_csv.php', '', $_SERVER['SCRIPT_NAME']);

try {
    $stmt = $pdo->query("SELECT * FROM `santri` ORDER BY `created_at` DESC");
    while ($row = $stmt->fetch()) {
        $regNo = 'REG-' . date('Y', strtotime($row['created_at'])) . '-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
        $uniqueLink = $baseUrl . "submit_registration.php?token=" . $row['token'];
        $jk = $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan';
        
        fputcsv($output, [
            $regNo,
            date('d-m-Y H:i', strtotime($row['created_at'])),
            $row['nama_lengkap'],
            $row['nama_panggilan'],
            $jk,
            $row['tempat_lahir'],
            $row['tanggal_lahir'],
            $row['alamat'],
            $row['no_hp'],
            $row['instansi'],
            $row['sekolah_asal'],
            $row['nama_ortu'],
            $row['no_hp_ortu'],
            $row['status'],
            $uniqueLink
        ]);
    }
} catch (PDOException $e) {
    // Apabila terjadi kesalahan database
}

fclose($output);
exit;
?>

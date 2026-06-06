<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

// Validasi session login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$adminRole = $_SESSION['admin_role'];

// Validasi input
if ($id <= 0 || !in_array($status, ['Pending', 'Verified', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Data parameter tidak valid.']);
    exit;
}

try {
    // 1. Ambil data instansi santri terlebih dahulu untuk verifikasi hak akses
    $stmtCheck = $pdo->prepare("SELECT `instansi`, `nama_lengkap` FROM `santri` WHERE `id` = :id");
    $stmtCheck->execute(['id' => $id]);
    $santri = $stmtCheck->fetch();

    if (!$santri) {
        echo json_encode(['success' => false, 'message' => 'Data santri tidak ditemukan.']);
        exit;
    }

    $instansiSantri = $santri['instansi'];

    // 2. Terapkan Hak Akses berbasis Role (RBAC)
    if ($adminRole === 'Admin MTs' && $instansiSantri !== 'MTs') {
        echo json_encode([
            'success' => false, 
            'message' => "Akses Ditolak: Sebagai Admin MTs, Anda hanya dapat memproses santri MTs. Santri ini terdaftar di instansi {$instansiSantri}."
        ]);
        exit;
    }

    if ($adminRole === 'Admin SMK' && $instansiSantri !== 'SMK') {
        echo json_encode([
            'success' => false, 
            'message' => "Akses Ditolak: Sebagai Admin SMK, Anda hanya dapat memproses santri SMK. Santri ini terdaftar di instansi {$instansiSantri}."
        ]);
        exit;
    }

    // Admin Utama memiliki akses penuh untuk seluruh instansi (MTs, SMK, Murni Pondok)

    // 3. Lakukan Update Status
    $stmtUpdate = $pdo->prepare("UPDATE `santri` SET `status` = :status WHERE `id` = :id");
    $stmtUpdate->execute([
        'status' => $status,
        'id' => $id
    ]);

    echo json_encode([
        'success' => true, 
        'message' => "Status pendaftaran santri atas nama {$santri['nama_lengkap']} berhasil diperbarui menjadi {$status}."
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
}
?>

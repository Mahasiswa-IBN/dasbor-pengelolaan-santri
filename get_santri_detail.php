<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

// Validasi session login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID Santri tidak valid.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM `santri` WHERE `id` = :id");
    $stmt->execute(['id' => $id]);
    $santri = $stmt->fetch();

    if ($santri) {
        // Beri format data tanggal lahir agar mudah dibaca di frontend
        $santri['tanggal_lahir_formatted'] = date('d F Y', strtotime($santri['tanggal_lahir']));
        $santri['created_at_formatted'] = date('d-m-Y H:i', strtotime($santri['created_at']));
        
        echo json_encode(['success' => true, 'data' => $santri]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data santri tidak ditemukan.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
}
?>

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

try {
    // 1. Ambil data statistik dari DB
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM `santri`");
    $statTotal = $stmtTotal->fetchColumn();

    $stmtMts = $pdo->query("SELECT COUNT(*) FROM `santri` WHERE `instansi` = 'MTs'");
    $statMts = $stmtMts->fetchColumn();

    $stmtSmk = $pdo->query("SELECT COUNT(*) FROM `santri` WHERE `instansi` = 'SMK'");
    $statSmk = $stmtSmk->fetchColumn();

    $stmtMurni = $pdo->query("SELECT COUNT(*) FROM `santri` WHERE `instansi` = 'Murni Pondok'");
    $statMurni = $stmtMurni->fetchColumn();

    // Ambil nama pondok dari settings
    $settings = getSettings($pdo);
    $namaPondok = $settings['nama_pondok'] ?? 'Pondok Pesantren Al-Barokah';

    // 2. Ambil seluruh data santri untuk tabel (termasuk token, no_hp_ortu, nama_ortu, dan file_bukti)
    $stmtSantri = $pdo->query("SELECT `id`, `nama_lengkap`, `instansi`, `no_hp`, `status`, `created_at`, `token`, `no_hp_ortu`, `nama_ortu`, `file_bukti` FROM `santri` ORDER BY `created_at` DESC");
    $listSantri = $stmtSantri->fetchAll();

} catch (PDOException $e) {
    die("Gagal mengambil data dari database: " . $e->getMessage());
}

// Helper untuk format nomor telepon lokal ke format internasional WhatsApp (62)
function formatWhatsAppNumber($number) {
    $number = preg_replace('/[^0-9]/', '', $number);
    if (strpos($number, '0') === 0) {
        $number = '62' . substr($number, 1);
    }
    return $number;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pondok Pesantren Al-Barokah</title>
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
                    <a href="admin_dashboard.php" class="sidebar-link active">
                        <i class="fa-solid fa-table-columns"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="admin_settings.php" class="sidebar-link">
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
                    <h2>Dasbor Pengelolaan Santri</h2>
                    <p>Selamat datang kembali, kelola pendaftaran online dan pendataan santri dengan mudah.</p>
                </div>
                <div>
                    <span style="font-size: 0.85rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); padding: 8px 16px; border-radius: 20px;">
                        <i class="fa-regular fa-calendar-check" style="color: var(--gold); margin-right: 6px;"></i> 
                        <?php echo date('d M Y'); ?>
                    </span>
                </div>
            </header>

            <!-- Stats Overview Cards -->
            <section class="stats-cards">
                <!-- Total Registered -->
                <div class="stat-card">
                    <div class="stat-card-icon total"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-card-info">
                        <h3><?php echo $statTotal; ?></h3>
                        <p>Total Pendaftar</p>
                    </div>
                </div>

                <!-- MTs -->
                <div class="stat-card">
                    <div class="stat-card-icon mts"><i class="fa-solid fa-book-open"></i></div>
                    <div class="stat-card-info">
                        <h3><?php echo $statMts; ?></h3>
                        <p>Santri MTs</p>
                    </div>
                </div>

                <!-- SMK -->
                <div class="stat-card">
                    <div class="stat-card-icon smk"><i class="fa-solid fa-laptop-code"></i></div>
                    <div class="stat-card-info">
                        <h3><?php echo $statSmk; ?></h3>
                        <p>Santri SMK</p>
                    </div>
                </div>

                <!-- Murni Pondok -->
                <div class="stat-card">
                    <div class="stat-card-icon murni"><i class="fa-solid fa-kaaba"></i></div>
                    <div class="stat-card-info">
                        <h3><?php echo $statMurni; ?></h3>
                        <p>Murni Pondok</p>
                    </div>
                </div>
            </section>

            <!-- Main Data Table Panel -->
            <section class="panel">
                <div class="panel-header">
                    <!-- Dynamic Filter Tabs (AS REQUESTED) -->
                    <div class="filter-tabs" id="instansiFilters">
                        <button class="filter-tab active" data-filter="all">Seluruh Santri</button>
                        <button class="filter-tab" data-filter="MTs">MTs Al-Barokah</button>
                        <button class="filter-tab" data-filter="SMK">SMK Al-Barokah</button>
                        <button class="filter-tab" data-filter="Murni Pondok">Murni Pondok</button>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <!-- Unduh Laporan Button -->
                        <a href="export_csv.php" class="btn-primary" style="background: var(--gold); color: var(--bg-dark); font-weight: 600; padding: 9px 16px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; border: none; cursor: pointer; transition: var(--transition);"><i class="fa-solid fa-file-csv" style="font-size: 1.1rem;"></i> Unduh Laporan</a>
                        
                        <!-- Search Input -->
                        <div class="search-wrapper">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" id="searchInput" class="search-input" placeholder="Cari nama santri...">
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-container">
                    <table class="custom-table" id="santriTable">
                        <thead>
                            <tr>
                                <th style="width: 60px; text-align: center;">No</th>
                                <th>Nama Lengkap</th>
                                <th style="width: 150px;">Instansi</th>
                                <th style="width: 140px;">No. HP / WA</th>
                                <th style="width: 140px;">Tgl Daftar</th>
                                <th style="width: 120px; text-align: center;">Status</th>
                                <th style="width: 140px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listSantri) === 0): ?>
                                <tr id="noDataRow">
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px 0;">
                                        <i class="fa-regular fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        Belum ada data pendaftaran masuk.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1; 
                                foreach ($listSantri as $row): 
                                    $statusClass = strtolower($row['status']);
                                    $instansiClean = strtolower(str_replace(' ', '', $row['instansi']));
                                    
                                    // Generate URL Bukti Pendaftaran Unik
                                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . str_replace('admin_dashboard.php', '', $_SERVER['SCRIPT_NAME']);
                                    $uniqueLink = $baseUrl . "submit_registration.php?token=" . $row['token'];
                                    
                                    // Pesan untuk orang tua
                                    $msgOrtu = "Assalamu'alaikum Wr. Wb. Yth. Orang Tua/Wali dari " . $row['nama_lengkap'] . ",\n\nBerikut adalah tautan bukti pendaftaran online PPDB " . $namaPondok . " putra/putri Anda:\n" . $uniqueLink . "\n\nSilakan simpan tautan di atas untuk mencetak kartu pendaftaran dan memantau status verifikasi berkas secara berkala.\n\nTerima kasih.\n*Panitia PPDB " . $namaPondok . "*";
                                    $waUrlOrtu = "https://wa.me/" . formatWhatsAppNumber($row['no_hp_ortu']) . "?text=" . urlencode($msgOrtu);
                                    
                                    // Pesan untuk calon santri
                                    $msgSantri = "Assalamu'alaikum Wr. Wb. Yth. " . $row['nama_lengkap'] . ",\n\nBerikut adalah tautan bukti pendaftaran online PPDB " . $namaPondok . " Anda:\n" . $uniqueLink . "\n\nSilakan simpan tautan di atas untuk mencetak kartu pendaftaran dan memantau status verifikasi berkas secara berkala.\n\nTerima kasih.\n*Panitia PPDB " . $namaPondok . "*";
                                    $waUrlSantri = "https://wa.me/" . formatWhatsAppNumber($row['no_hp']) . "?text=" . urlencode($msgSantri);
                                ?>
                                    <tr class="santri-row" data-instansi="<?php echo $row['instansi']; ?>" data-nama="<?php echo strtolower($row['nama_lengkap']); ?>">
                                        <td style="text-align: center;" class="row-number"><?php echo $no++; ?></td>
                                        <td style="font-weight: 600; color: var(--text-white);"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                        <td>
                                            <span class="badge instansi <?php echo $instansiClean; ?>"><?php echo $row['instansi']; ?></span>
                                        </td>
                                        <td>
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $row['no_hp']); ?>" target="_blank" style="color: var(--text-light); text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="fa-brands fa-whatsapp" style="color: #2ecc71;"></i> <?php echo htmlspecialchars($row['no_hp']); ?>
                                            </a>
                                        </td>
                                        <td style="font-size: 0.85rem; color: var(--text-muted);"><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                        <td style="text-align: center;">
                                            <span class="badge status-badge <?php echo $statusClass; ?>" id="status-badge-<?php echo $row['id']; ?>"><?php echo $row['status']; ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="actions" style="justify-content: center;">
                                                <!-- View Detail Button -->
                                                <button type="button" class="btn-action view" data-id="<?php echo $row['id']; ?>" title="Lihat Detail Berkas">
                                                    <i class="fa-regular fa-eye"></i>
                                                </button>
                                                
                                                <!-- Cetak Bukti Button -->
                                                <a href="submit_registration.php?token=<?php echo $row['token']; ?>" target="_blank" class="btn-action view" title="Cetak Bukti Pendaftaran" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                                                    <i class="fa-solid fa-print"></i>
                                                </a>
                                                <!-- Cetak / Lihat Bukti Pembayaran (jika ada) -->
                                                <?php if (!empty($row['file_bukti'])): ?>
                                                    <a href="uploads/payment/<?php echo htmlspecialchars($row['file_bukti']); ?>" target="_blank" class="btn-action view" title="Lihat / Cetak Bukti Pembayaran" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">
                                                        <i class="fa-solid fa-receipt"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <!-- WA Orang Tua -->
                                                <a href="<?php echo $waUrlOrtu; ?>" target="_blank" class="btn-action approve" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;" title="Kirim WA ke Orang Tua">
                                                    <i class="fa-brands fa-whatsapp"></i><span style="font-size: 0.65rem; margin-left: 2px; font-weight: bold;">O</span>
                                                </a>

                                                <!-- WA Santri -->
                                                <a href="<?php echo $waUrlSantri; ?>" target="_blank" class="btn-action approve" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;" title="Kirim WA ke Santri">
                                                    <i class="fa-brands fa-whatsapp"></i><span style="font-size: 0.65rem; margin-left: 2px; font-weight: bold;">S</span>
                                                </a>

                                                <!-- Quick Verify Button -->
                                                <button type="button" class="btn-action approve" data-id="<?php echo $row['id']; ?>" title="Verifikasi Pendaftaran">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                
                                                <!-- Quick Reject Button -->
                                                <button type="button" class="btn-action reject" data-id="<?php echo $row['id']; ?>" title="Tolak Pendaftaran">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <!-- Dummy row for JS filter empty states -->
                            <tr id="emptyFilterRow" style="display: none;">
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px 0;">
                                    <i class="fa-regular fa-face-frown" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                    Tidak ada data santri yang cocok dengan filter atau pencarian Anda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Student Detail Modal -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Profil Calon Santri</h3>
                <button type="button" class="modal-close" id="modalCloseBtn">&times;</button>
            </div>
            <div class="modal-body" id="modalBodyContent">
                <!-- Skeleton Loader / JS will inject content here -->
                <div class="spinner"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="modalCloseFooter">Tutup</button>
                <button type="button" class="btn-primary" id="modalVerifyBtn" style="background: var(--success); box-shadow: none; color: var(--bg-dark);"><i class="fa-solid fa-check"></i> Verifikasi</button>
                <button type="button" class="btn-primary" id="modalRejectBtn" style="background: var(--danger); box-shadow: none; color: var(--text-white);"><i class="fa-solid fa-xmark"></i> Tolak</button>
            </div>
        </div>
    </div>

    <!-- JS Scripts -->
    <script src="assets/js/dashboard.js"></script>
</body>
</html>

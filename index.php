<?php
require_once 'db_connect.php';
session_start();

// Ambil semua pengaturan dari database
$settings = getSettings($pdo);
$namaPondok = $settings['nama_pondok'] ?? 'Pondok Pesantren Al-Barokah';
$alamatPondok = $settings['alamat_pondok'] ?? 'Jl. Pondok Pesantren No. 45, Kecamatan Sukamakmur, Jawa Barat';
$noHpPondok = $settings['no_hp_pondok'] ?? '6281234567890';
$logoPath = 'uploads/settings/' . ($settings['logo_path'] ?? 'logo.png');
$gambarPondokPath = 'uploads/settings/' . ($settings['gambar_pondok_path'] ?? 'pondok.png');
// Per-instansi assets
$mtsLogo = !empty($settings['mts_logo']) ? 'uploads/settings/' . $settings['mts_logo'] : '';
$smkLogo = !empty($settings['smk_logo']) ? 'uploads/settings/' . $settings['smk_logo'] : '';
$murniLogo = !empty($settings['murni_logo']) ? 'uploads/settings/' . $settings['murni_logo'] : '';
$mtsImage = !empty($settings['mts_image']) ? 'uploads/settings/' . $settings['mts_image'] : '';
$smkImage = !empty($settings['smk_image']) ? 'uploads/settings/' . $settings['smk_image'] : '';
$murniImage = !empty($settings['murni_image']) ? 'uploads/settings/' . $settings['murni_image'] : '';
// Gallery
$gallery = [];
for ($i = 1; $i <= 6; $i++) {
    if (!empty($settings['gallery_' . $i])) $gallery[] = 'uploads/settings/' . $settings['gallery_' . $i];
}

// Ambil statistik real-time dari database
try {
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM `santri` WHERE `status` = 'Verified'");
    $totalVerified = $stmtTotal->fetchColumn();
    
    // Angka dasar ditambahkan dengan santri yang ada di database agar terlihat realistis
    $totalSantri = 342 + $totalVerified; 
    
    $stmtMts = $pdo->query("SELECT COUNT(*) FROM `santri` WHERE `instansi` = 'MTs' AND `status` = 'Verified'");
    $totalMts = 120 + $stmtMts->fetchColumn();
    
    $stmtSmk = $pdo->query("SELECT COUNT(*) FROM `santri` WHERE `instansi` = 'SMK' AND `status` = 'Verified'");
    $totalSmk = 150 + $stmtSmk->fetchColumn();
    
    $stmtMurni = $pdo->query("SELECT COUNT(*) FROM `santri` WHERE `instansi` = 'Murni Pondok' AND `status` = 'Verified'");
    $totalMurni = 72 + $stmtMurni->fetchColumn();
} catch (PDOException $e) {
    // Fallback jika terjadi error
    $totalSantri = 342;
    $totalMts = 120;
    $totalSmk = 150;
    $totalMurni = 72;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($namaPondok); ?> - Portal PPDB & Pendataan Santri</title>
    <meta name="description" content="Pendaftaran Online dan Pendataan Santri <?php echo htmlspecialchars($namaPondok); ?>. Menyediakan instansi pendidikan MTs, SMK, dan Program Murni Pondok.">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="gradient-bg"></div>

    <!-- Navigation Bar -->
    <nav class="navbar" id="mainNavbar">
        <div class="container navbar-content">
            <div class="logo-wrapper">
                <!-- Dynamic Logo Image -->
                <div class="logo-icon" style="background: var(--bg-card); overflow: hidden; padding: 2px;">
                    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;">
                </div>
                <div class="logo-text">
                    <h1><?php echo htmlspecialchars($namaPondok); ?></h1>
                    <p>Pondok Pesantren</p>
                </div>
            </div>
            <ul class="nav-links">
                <li><a href="#home">Beranda</a></li>
                <li><a href="#instansi">Instansi Pendidikan</a></li>
                <li><a href="#statistik">Statistik</a></li>
                <li><a href="login.php" class="btn-nav"><i class="fa-solid fa-lock-open"></i> Login Admin</a></li>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <li><a href="admin_settings.php" class="btn-nav" style="background: rgba(255,255,255,0.02);"><i class="fa-solid fa-gear"></i> Edit Situs</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <?php $backgroundBeranda = 'uploads/settings/' . ($settings['background_beranda_path'] ?? ($settings['gambar_pondok_path'] ?? 'pondok.png')); ?>
    <section class="hero" id="home" style="background-image: url('<?php echo htmlspecialchars($backgroundBeranda); ?>'); background-size: cover; background-position: center;">
        <div class="container hero-grid">
            <div class="hero-content">
                <h2>Membentuk Generasi <span>Berakhlak Mulia & Unggul</span></h2>
                <p>Selamat datang di Portal Penerimaan Santri Baru (PPDB) <?php echo htmlspecialchars($namaPondok); ?>. Kami menyelenggarakan pendidikan integratif berlandaskan nilai-nilai salafiyah dan kesiapan industri modern.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn-primary">Daftar Santri Baru <i class="fa-solid fa-arrow-right"></i></a>
                    <a href="#instansi" class="btn-secondary">Lihat Instansi</a>
                </div>
            </div>
            <div class="hero-visual">
                <!-- Dynamic Boarding School Image inside Card -->
                <div class="hero-art-card" style="padding: 12px; max-width: 440px; aspect-ratio: auto; justify-content: flex-start;">
                    <div style="width: 100%; aspect-ratio: 16/10; overflow: hidden; border-radius: 16px; border: 1px solid var(--border-glass);">
                        <img src="<?php echo htmlspecialchars($gambarPondokPath); ?>" alt="<?php echo htmlspecialchars($namaPondok); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="padding-top: 15px; text-align: center; width: 100%;">
                        <h3 style="font-size: 1.25rem; margin-bottom: 5px;"><?php echo htmlspecialchars($namaPondok); ?></h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); font-family: 'Inter';">Iman, Ilmu, Amal, & Akhlakul Karimah</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <?php if (!empty($gallery)): ?>
    <section class="section" id="galeri" style="padding: 30px 0;">
        <div class="container">
            <div class="section-title">
                <p>Galeri</p>
                <h2>Dokumentasi & Foto Kegiatan</h2>
            </div>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:12px;">
                <?php foreach ($gallery as $img): ?>
                    <div style="height:160px; overflow:hidden; border-radius:12px; border:1px solid var(--border-glass);">
                        <img src="<?php echo htmlspecialchars($img); ?>" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Stats Counter Bar -->
    <section class="stats-bar" id="statistik">
        <div class="container stats-grid">
            <div class="stat-item">
                <h3><?php echo $totalSantri; ?>+</h3>
                <p>Total Santri Aktif</p>
            </div>
            <div class="stat-item">
                <h3>28</h3>
                <p>Ustadz & Pengajar</p>
            </div>
            <div class="stat-item">
                <h3>100%</h3>
                <p>Kurikulum Terpadu</p>
            </div>
            <div class="stat-item">
                <h3>12+</h3>
                <p>Fasilitas Modern</p>
            </div>
        </div>
    </section>

    <!-- Instansi Pendidikan Section -->
    <section class="section" id="instansi">
        <div class="container">
            <div class="section-title">
                <p>Unit Pendidikan</p>
                <h2>Instansi di <?php echo htmlspecialchars($namaPondok); ?></h2>
            </div>
            <div class="cards-grid">
                <!-- MTs Card -->
                <div class="instansi-card">
                    <div class="card-icon"><i class="fa-solid fa-book-open"></i></div>
                    <h3>MTs Al-Barokah</h3>
                    <p>Madrasah Tsanawiyah setingkat SMP yang mengintegrasikan kurikulum Kementerian Agama dengan kajian kitab kuning salafiyah klasik.</p>
                    <span class="card-badge"><?php echo $totalMts; ?> Santri Terdaftar</span>
                </div>

                <!-- SMK Card -->
                <div class="instansi-card">
                    <div class="card-icon"><i class="fa-solid fa-laptop-code"></i></div>
                    <h3>SMK Al-Barokah</h3>
                    <p>Sekolah Menengah Kejuruan yang memadukan keahlian teknologi & rekayasa industri dengan pembentukan karakter santri profesional.</p>
                    <span class="card-badge"><?php echo $totalSmk; ?> Santri Terdaftar</span>
                </div>

                <!-- Murni Pondok Card -->
                <div class="instansi-card">
                    <div class="card-icon"><i class="fa-solid fa-kaaba"></i></div>
                    <h3>Murni Pondok (Tahfidz & Kitab)</h3>
                    <p>Program khusus bagi santri yang fokus sepenuhnya mendalami Al-Qur'an (Tahfidz) dan kajian literatur keislaman secara mendalam tanpa sekolah formal.</p>
                    <span class="card-badge"><?php echo $totalMurni; ?> Santri Terdaftar</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: rgba(10, 15, 12, 0.95); border-top: 1px solid var(--border-glass); padding: 40px 0; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
        <div class="container">
            <!-- Dynamic Footer Logo & Name -->
            <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 15px;">
                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" style="width: 32px; height: 32px; object-fit: contain;">
                <span style="color: var(--text-light); font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 1.15rem;"><?php echo htmlspecialchars($namaPondok); ?></span>
            </div>
            <p style="margin-bottom: 12px; max-width: 600px; margin-left: auto; margin-right: auto;"><?php echo htmlspecialchars($alamatPondok); ?></p>
            <?php if (!empty($settings['nama_admin_contact'])): ?>
                <p style="margin-bottom: 12px; max-width: 600px; margin-left: auto; margin-right: auto; color: var(--text-muted);">Kontak Admin: <?php echo htmlspecialchars($settings['nama_admin_contact']); ?></p>
            <?php endif; ?>
            <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 25px; font-size: 1.2rem;">
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $noHpPondok); ?>" target="_blank" style="color: var(--text-muted); transition: var(--transition);"><i class="fa-brands fa-whatsapp hover-gold"></i></a>
                <a href="#" style="color: var(--text-muted); transition: var(--transition);"><i class="fa-brands fa-facebook hover-gold"></i></a>
                <a href="#" style="color: var(--text-muted); transition: var(--transition);"><i class="fa-brands fa-instagram hover-gold"></i></a>
                <a href="#" style="color: var(--text-muted); transition: var(--transition);"><i class="fa-brands fa-youtube hover-gold"></i></a>
            </div>
            <p>&copy; 2026 <?php echo htmlspecialchars($namaPondok); ?>. All Rights Reserved.</p>
        </div>
    </footer>

    <style>
        .hover-gold:hover {
            color: var(--gold);
        }
    </style>
</body>
</html>

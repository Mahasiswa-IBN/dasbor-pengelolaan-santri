<?php
$sessionStarted = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $sessionStarted = true;
}
require_once 'db_connect.php';
$settings = getSettings($pdo);
$namaPondok = $settings['nama_pondok'] ?? 'Pondok Pesantren Al-Barokah';
$logoPath = 'uploads/settings/' . ($settings['logo_path'] ?? 'logo.png');
// Nomor rekening/administrasi (bisa dikonfigurasi lewat tabel `settings` dengan key `rekening_admin`)
$rekeningAdmin = $settings['rekening_admin'] ?? "Bank BRI - 1234567890 a.n. Pondok Pesantren Al-Barokah";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Santri Baru - <?php echo htmlspecialchars($namaPondok); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="gradient-bg"></div>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container navbar-content">
            <div class="logo-wrapper">
                <a href="index.php" style="text-decoration: none; display: flex; align-items: center; gap: 12px;">
                    <!-- Dynamic Logo Image -->
                    <div class="logo-icon" style="background: var(--bg-card); overflow: hidden; padding: 2px; width: 38px; height: 38px;">
                        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;">
                    </div>
                    <div class="logo-text">
                        <h1><?php echo htmlspecialchars($namaPondok); ?></h1>
                        <p>Pondok Pesantren</p>
                    </div>
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="index.php#instansi">Instansi</a></li>
                <li><a href="index.php#statistik">Statistik</a></li>
                <li><a href="login.php" class="btn-nav"><i class="fa-solid fa-lock-open"></i> Login Admin</a></li>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <li><a href="admin_settings.php" class="btn-nav"><i class="fa-solid fa-gear"></i> Edit Situs</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Multi-step Form Container -->
    <div class="form-container">
        <h2 style="text-align: center; margin-bottom: 30px; font-family: 'Outfit', sans-serif;">Pendaftaran Santri Baru (PPDB Online)</h2>
        
        <!-- Steps Indicator -->
        <div class="steps-indicator">
            <div class="step-indicator-bar" id="stepBar"></div>
            
            <div class="step-node active" data-step="1">
                <div class="step-circle">1</div>
                <div class="step-label">Data Diri</div>
            </div>
            <div class="step-node" data-step="2">
                <div class="step-circle">2</div>
                <div class="step-label">Instansi</div>
            </div>
            <div class="step-node" data-step="3">
                <div class="step-circle">3</div>
                <div class="step-label">Orang Tua</div>
            </div>
            <div class="step-node" data-step="4">
                <div class="step-circle">4</div>
                <div class="step-label">Berkas</div>
            </div>
        </div>

        <!-- Form Element -->
        <form action="submit_registration.php" method="POST" enctype="multipart/form-data" id="ppdbForm">
            
            <!-- STEP 1: DATA DIRI -->
            <div class="form-step active" data-step="1">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="nama_lengkap"><i class="fa-solid fa-user"></i> Nama Lengkap (Sesuai Ijazah/Akte)</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Contoh: Muhammad Akhyar" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nama_panggilan"><i class="fa-solid fa-signature"></i> Nama Panggilan</label>
                        <input type="text" id="nama_panggilan" name="nama_panggilan" class="form-control" placeholder="Contoh: Akhyar" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="jenis_kelamin"><i class="fa-solid fa-venus-mars"></i> Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" class="form-control" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L">Laki-laki (Santri)</option>
                            <option value="P">Perempuan (Santriwati)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tempat_lahir"><i class="fa-solid fa-map-location-dot"></i> Tempat Lahir</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" placeholder="Contoh: Bogor" required>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_lahir"><i class="fa-solid fa-calendar-days"></i> Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="no_hp"><i class="fa-brands fa-whatsapp"></i> No. HP / WhatsApp Aktif</label>
                        <input type="tel" id="no_hp" name="no_hp" class="form-control" placeholder="Contoh: 081234567890" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="alamat"><i class="fa-solid fa-house-chimney"></i> Alamat Lengkap (RT/RW, Desa, Kec, Kab/Kota, Prov)</label>
                        <textarea id="alamat" name="alamat" rows="3" class="form-control" placeholder="Contoh: Jl. Mawar No. 12 RT 02 RW 03, Desa Sukaresmi, Kec. Sukamakmur, Kab. Bogor" required></textarea>
                    </div>
                </div>
            </div>

            <!-- STEP 2: INSTANSI PENDIDIKAN -->
            <div class="form-step" data-step="2">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="instansi"><i class="fa-solid fa-school"></i> Pilihan Instansi Pendidikan</label>
                        <select id="instansi" name="instansi" class="form-control" required>
                            <option value="">-- Pilih Instansi --</option>
                            <option value="MTs">MTs Al-Barokah (Setingkat SMP)</option>
                            <option value="SMK">SMK Al-Barokah (Setingkat SMA)</option>
                            <option value="Murni Pondok">Murni Pondok (Tahfidz & Kitab Tanpa Sekolah Formal)</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="sekolah_asal"><i class="fa-solid fa-graduation-cap"></i> Sekolah Asal (SD/MI/SMP/MTs Sebelumnya)</label>
                        <input type="text" id="sekolah_asal" name="sekolah_asal" class="form-control" placeholder="Contoh: SD Negeri 1 Sukaresmi" required>
                    </div>
                </div>
            </div>

            <!-- STEP 3: DATA ORANG TUA / WALI -->
            <div class="form-step" data-step="3">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="nama_ortu"><i class="fa-solid fa-user-group"></i> Nama Lengkap Orang Tua / Wali</label>
                        <input type="text" id="nama_ortu" name="nama_ortu" class="form-control" placeholder="Contoh: Ahmad Sulaiman" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="no_hp_ortu"><i class="fa-solid fa-phone"></i> No. HP / WhatsApp Orang Tua / Wali</label>
                        <input type="tel" id="no_hp_ortu" name="no_hp_ortu" class="form-control" placeholder="Contoh: 089876543210" required>
                    </div>
                </div>
            </div>

            <!-- STEP 4: UNGGAH DOKUMEN -->
            <div class="form-step" data-step="4">
                <div class="form-grid">
                    <!-- Pas Foto -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-image"></i> Pas Foto Santri (3x4)</label>
                        <div class="file-upload-wrapper" id="upload-foto">
                            <i class="fa-solid fa-cloud-arrow-up file-upload-icon"></i>
                            <div class="file-upload-text">Pilih file foto atau seret ke sini</div>
                            <div class="file-upload-info">JPG, PNG (Maks 2MB)</div>
                            <input type="file" name="file_foto" accept=".jpg, .jpeg, .png" required>
                        </div>
                    </div>

                    <!-- SKL -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-file-invoice"></i> Surat Keterangan Lulus (SKL) / Ijazah Terakhir</label>
                        <div class="file-upload-wrapper" id="upload-skl">
                            <i class="fa-solid fa-file-pdf file-upload-icon"></i>
                            <div class="file-upload-text">Pilih file SKL/Ijazah atau seret ke sini</div>
                            <div class="file-upload-info">PDF, JPG, PNG (Maks 2MB)</div>
                            <input type="file" name="file_skl" accept=".pdf, .jpg, .jpeg, .png" required>
                        </div>
                    </div>

                    <!-- Kartu Keluarga -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-users"></i> Kartu Keluarga (KK)</label>
                        <div class="file-upload-wrapper" id="upload-kk">
                            <i class="fa-solid fa-file-pdf file-upload-icon"></i>
                            <div class="file-upload-text">Pilih file KK atau seret ke sini</div>
                            <div class="file-upload-info">PDF, JPG, PNG (Maks 2MB)</div>
                            <input type="file" name="file_kk" accept=".pdf, .jpg, .jpeg, .png" required>
                        </div>
                    </div>

                    <!-- Akta Kelahiran -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-file-signature"></i> Akta Kelahiran</label>
                        <div class="file-upload-wrapper" id="upload-akte">
                            <i class="fa-solid fa-file-pdf file-upload-icon"></i>
                            <div class="file-upload-text">Pilih file Akta atau seret ke sini</div>
                            <div class="file-upload-info">PDF, JPG, PNG (Maks 2MB)</div>
                            <input type="file" name="file_akte" accept=".pdf, .jpg, .jpeg, .png" required>
                        </div>
                    </div>

                    <!-- PEMBAYARAN PENDAFTARAN -->
                    <div class="form-group full-width" style="margin-top: 10px;">
                        <label><i class="fa-solid fa-credit-card"></i> Pembayaran Pendaftaran</label>
                        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); padding: 12px; border-radius: 8px; margin-bottom: 8px; color: var(--text-muted);">
                            <strong style="color: var(--text-white);">Nomor Rekening Admin:</strong>
                            <div style="margin-top:6px; font-weight:600; color: var(--gold);"><?php echo htmlspecialchars($rekeningAdmin); ?></div>
                            <div style="margin-top:8px; font-size:0.9rem;">Silakan transfer biaya pendaftaran sesuai ketentuan, lalu unggah bukti pembayaran di bawah ini.</div>
                        </div>
                        <div class="file-upload-wrapper" id="upload-bukti">
                            <i class="fa-solid fa-file-invoice file-upload-icon"></i>
                            <div class="file-upload-text">Pilih file Bukti Pembayaran atau seret ke sini</div>
                            <div class="file-upload-info">PDF, JPG, PNG (Maks 2MB)</div>
                            <input type="file" name="file_bukti_bayar" accept=".pdf, .jpg, .jpeg, .png" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="form-buttons">
                <button type="button" class="btn-secondary" id="btnPrev" style="visibility: hidden;"><i class="fa-solid fa-arrow-left"></i> Sebelumnya</button>
                <button type="button" class="btn-primary" id="btnNext">Selanjutnya <i class="fa-solid fa-arrow-right"></i></button>
                <button type="submit" class="btn-primary" id="btnSubmit" style="display: none;">Kirim Pendaftaran <i class="fa-solid fa-paper-plane"></i></button>
            </div>

        </form>
    </div>

    <!-- JS Scripts -->
    <script src="assets/js/main.js"></script>
</body>
</html>

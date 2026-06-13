<?php
session_start();
require_once 'db_connect.php';

// Check admin session
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Ensure gallery table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `caption` TEXT,
    `display_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];
    header('Content-Type: application/json');

    try {
        if ($action === 'upload') {
            if (!isset($_FILES['gallery_file']) || $_FILES['gallery_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File tidak ditemukan atau terjadi kesalahan upload');
            }

            $file = $_FILES['gallery_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','webp'];
            if (!in_array($ext, $allowed)) throw new Exception('Format file tidak diperbolehkan');

            $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'gallery';
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $newName = 'gallery_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $dir . DIRECTORY_SEPARATOR . $newName;
            if (!move_uploaded_file($file['tmp_name'], $dest)) throw new Exception('Gagal menyimpan file');

            // determine next order
            $stmt = $pdo->query("SELECT COALESCE(MAX(display_order),0) + 1 AS next_order FROM gallery_images");
            $next = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'] ?? 1;

            $ins = $pdo->prepare("INSERT INTO gallery_images (filename, caption, display_order) VALUES (:fn, '', :ord)");
            $ins->execute(['fn' => $newName, 'ord' => $next]);

            echo json_encode(['status' => 'success', 'message' => 'Gambar berhasil diunggah']);
            exit;
        }

        if ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID tidak valid');

            $stmt = $pdo->prepare("SELECT filename FROM gallery_images WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('Item tidak ditemukan');

            $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR . $row['filename'];
            if (file_exists($filePath) && is_file($filePath)) unlink($filePath);

            $del = $pdo->prepare("DELETE FROM gallery_images WHERE id = :id");
            $del->execute(['id' => $id]);

            echo json_encode(['status' => 'success', 'message' => 'Gambar dihapus']);
            exit;
        }

        if ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $caption = trim($_POST['caption'] ?? '');
            if ($id <= 0) throw new Exception('ID tidak valid');
            $up = $pdo->prepare("UPDATE gallery_images SET caption = :c WHERE id = :id");
            $up->execute(['c' => $caption, 'id' => $id]);
            echo json_encode(['status' => 'success', 'message' => 'Caption diperbarui']);
            exit;
        }

        if ($action === 'reorder') {
            // expect order[] = [id1,id2,...]
            $order = $_POST['order'] ?? [];
            if (!is_array($order)) throw new Exception('Format urutan tidak valid');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE gallery_images SET display_order = :ord WHERE id = :id");
            $pos = 1;
            foreach ($order as $id) {
                $stmt->execute(['ord' => $pos, 'id' => intval($id)]);
                $pos++;
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Urutan diperbarui']);
            exit;
        }

        throw new Exception('Aksi tidak dikenali');
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Fetch list for display
$stmt = $pdo->query("SELECT * FROM gallery_images ORDER BY display_order ASC, id ASC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manajemen Galeri - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gallery-grid { display:grid; grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); gap:12px; }
        .gallery-card { background: rgba(255,255,255,0.02); padding:10px; border-radius:8px; border:1px solid var(--border-glass); }
        .gallery-card img{ width:100%; height:140px; object-fit:cover; border-radius:6px; }
        .small-btn{ padding:6px 10px; font-size:0.85rem; }
    </style>
</head>
<body>
<div style="padding:18px; max-width:1100px; margin:0 auto;">
    <h2>Manajemen Galeri</h2>
    <p>Upload, edit caption, urutkan, dan hapus foto galeri.</p>

    <div style="margin-bottom:16px; display:flex; gap:12px; align-items:center;">
        <form id="uploadForm" enctype="multipart/form-data" style="display:flex; gap:8px; align-items:center;">
            <input type="file" name="gallery_file" accept="image/*" required>
            <button class="btn-primary" type="submit">Unggah</button>
        </form>
        <button id="saveOrder" class="btn-secondary small-btn">Simpan Urutan</button>
        <a href="admin_settings.php" class="btn-secondary small-btn">Kembali ke Pengaturan</a>
    </div>

    <div id="msg"></div>

    <div class="gallery-grid" id="galleryGrid">
        <?php foreach ($items as $it): ?>
            <div class="gallery-card" data-id="<?php echo $it['id']; ?>">
                <img src="uploads/gallery/<?php echo htmlspecialchars($it['filename']); ?>" alt="">
                <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                    <button class="btn-secondary btn-up small-btn">▲</button>
                    <button class="btn-secondary btn-down small-btn">▼</button>
                    <button class="btn-danger btn-delete small-btn" style="margin-left:auto;">Hapus</button>
                </div>
                <div style="margin-top:8px;">
                    <textarea class="caption-input" rows="2" style="width:100%;"><?php echo htmlspecialchars($it['caption']); ?></textarea>
                    <div style="display:flex; gap:8px; margin-top:6px;"><button class="btn-primary btn-save-caption small-btn">Simpan Caption</button></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    const uploadForm = document.getElementById('uploadForm');
    const msg = document.getElementById('msg');
    const grid = document.getElementById('galleryGrid');

    function showMessage(t, text){ msg.innerHTML = `<div class="${t==='ok'? 'alert-success':'alert-danger'}" style="margin-bottom:12px;">${text}</div>`; }

    uploadForm.addEventListener('submit', function(e){
        e.preventDefault();
        const fd = new FormData(uploadForm);
        fd.append('action','upload');
        fetch('admin_gallery.php', { method:'POST', body: fd })
            .then(r => r.json())
            .then(d => { if(d.status==='success'){ showMessage('ok', d.message); setTimeout(()=> location.reload(),600); } else showMessage('err', d.message); })
            .catch(err => showMessage('err', err.message));
    });

    // delegate delete, save caption, up/down
    grid.addEventListener('click', function(e){
        const card = e.target.closest('.gallery-card');
        if(!card) return;
        const id = card.getAttribute('data-id');

        if(e.target.classList.contains('btn-delete')){
            if(!confirm('Hapus gambar ini?')) return;
            const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
            fetch('admin_gallery.php', { method:'POST', body: fd })
                .then(r=>r.json()).then(d=>{ if(d.status==='success'){ showMessage('ok', d.message); card.remove(); } else showMessage('err', d.message); });
        }

        if(e.target.classList.contains('btn-save-caption')){
            const caption = card.querySelector('.caption-input').value;
            const fd = new FormData(); fd.append('action','update'); fd.append('id', id); fd.append('caption', caption);
            fetch('admin_gallery.php', { method:'POST', body: fd }).then(r=>r.json()).then(d=>{ if(d.status==='success') showMessage('ok', d.message); else showMessage('err', d.message); });
        }

        if(e.target.classList.contains('btn-up') || e.target.classList.contains('btn-down')){
            // simple DOM reorder
            if(e.target.classList.contains('btn-up')){
                const prev = card.previousElementSibling;
                if(prev) grid.insertBefore(card, prev);
            } else {
                const next = card.nextElementSibling;
                if(next) grid.insertBefore(next, card);
            }
        }
    });

    document.getElementById('saveOrder').addEventListener('click', function(){
        const ids = Array.from(grid.querySelectorAll('.gallery-card')).map(c => c.getAttribute('data-id'));
        const fd = new FormData(); fd.append('action','reorder'); ids.forEach(i=> fd.append('order[]', i));
        fetch('admin_gallery.php', { method:'POST', body: fd }).then(r=>r.json()).then(d=>{ if(d.status==='success') showMessage('ok', d.message); else showMessage('err', d.message); });
    });
</script>
</body>
</html>

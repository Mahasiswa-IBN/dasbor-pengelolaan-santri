document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------
    // 1. FILTERING DATA & PENCARIAN
    // ----------------------------------------------------
    const filterTabs = document.querySelectorAll('.filter-tab');
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('.santri-row');
    const emptyRow = document.getElementById('emptyFilterRow');
    const noDataRow = document.getElementById('noDataRow');

    let activeFilter = 'all';
    let searchQuery = '';

    const filterTable = () => {
        if (noDataRow) return; // Tidak ada data sama sekali di database

        let visibleCount = 0;
        tableRows.forEach(row => {
            const instansi = row.getAttribute('data-instansi');
            const nama = row.getAttribute('data-nama');

            const matchFilter = (activeFilter === 'all' || instansi === activeFilter);
            const matchSearch = nama.includes(searchQuery);

            if (matchFilter && matchSearch) {
                row.style.display = '';
                visibleCount++;
                // Update sequential number of row
                row.querySelector('.row-number').innerText = visibleCount;
            } else {
                row.style.display = 'none';
            }
        });

        // Tampilkan pesan kosong jika tidak ada yang terlihat
        if (visibleCount === 0) {
            emptyRow.style.display = '';
        } else {
            emptyRow.style.display = 'none';
        }
    };

    // Event handler klik tab filter
    filterTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            filterTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeFilter = tab.getAttribute('data-filter');
            filterTable();
        });
    });

    // Event handler input pencarian
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            filterTable();
        });
    }


    // ----------------------------------------------------
    // 2. PROSES UPDATE STATUS (VERIFIKASI & TOLAK)
    // ----------------------------------------------------
    const updateSantriStatus = (id, newStatus) => {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', newStatus);

        fetch('update_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update badge status di baris tabel
                const badge = document.getElementById(`status-badge-${id}`);
                if (badge) {
                    badge.innerText = newStatus;
                    badge.className = `badge status-badge ${newStatus.toLowerCase()}`;
                }
                alert(data.message);
                
                // Jika modal sedang aktif, tutup modal
                closeModal();
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Gagal memperbarui status. Terjadi kesalahan koneksi.');
        });
    };

    // Delegasi Event untuk tombol Aksi di tabel
    document.addEventListener('click', (e) => {
        // Tombol Approve
        const approveBtn = e.target.closest('.btn-action.approve');
        if (approveBtn) {
            const id = approveBtn.getAttribute('data-id');
            if (confirm('Apakah Anda yakin ingin memverifikasi pendaftaran santri ini?')) {
                updateSantriStatus(id, 'Verified');
            }
            return;
        }

        // Tombol Reject
        const rejectBtn = e.target.closest('.btn-action.reject');
        if (rejectBtn) {
            const id = rejectBtn.getAttribute('data-id');
            if (confirm('Apakah Anda yakin ingin menolak pendaftaran santri ini?')) {
                updateSantriStatus(id, 'Rejected');
            }
            return;
        }
    });


    // ----------------------------------------------------
    // 3. MODAL DETAIL SANTRI & VERIFIKASI BERKAS
    // ----------------------------------------------------
    const modal = document.getElementById('detailModal');
    const modalBody = document.getElementById('modalBodyContent');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalCloseFooter = document.getElementById('modalCloseFooter');
    const modalVerifyBtn = document.getElementById('modalVerifyBtn');
    const modalRejectBtn = document.getElementById('modalRejectBtn');

    let activeModalSantriId = null;

    const openModal = (id) => {
        activeModalSantriId = id;
        modal.classList.add('active');
        modalBody.innerHTML = '<div class="spinner"></div>';
        
        // Sembunyikan tombol verifikasi/tolak modal sampai data termuat
        modalVerifyBtn.style.display = 'none';
        modalRejectBtn.style.display = 'none';

        fetch(`get_santri_detail.php?id=${id}`)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                const s = res.data;
                
                // Tampilkan tombol verifikasi jika statusnya pending
                modalVerifyBtn.style.display = 'inline-flex';
                modalRejectBtn.style.display = 'inline-flex';
                
                // Template HTML detail santri
                modalBody.innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-foto-wrapper">
                            <img src="uploads/foto/${s.file_foto}" class="detail-foto" alt="Foto ${s.nama_lengkap}">
                            <span class="badge status-badge ${s.status.toLowerCase()}" style="margin-top: 15px; width: 100%; text-align: center;">${s.status}</span>
                        </div>
                        <div>
                            <table class="detail-info-table">
                                <tr>
                                    <td>Nama Lengkap</td>
                                    <td>: <strong>${s.nama_lengkap}</strong></td>
                                </tr>
                                <tr>
                                    <td>Nama Panggilan</td>
                                    <td>: ${s.nama_panggilan}</td>
                                </tr>
                                <tr>
                                    <td>Jenis Kelamin</td>
                                    <td>: ${s.jenis_kelamin === 'L' ? 'Laki-laki (Santri)' : 'Perempuan (Santriwati)'}</td>
                                </tr>
                                <tr>
                                    <td>TTL</td>
                                    <td>: ${s.tempat_lahir}, ${s.tanggal_lahir_formatted}</td>
                                </tr>
                                <tr>
                                    <td>No. HP / WhatsApp</td>
                                    <td>: ${s.no_hp}</td>
                                </tr>
                                <tr>
                                    <td>Alamat Lengkap</td>
                                    <td>: ${s.alamat}</td>
                                </tr>
                                <tr>
                                    <td>Pilihan Instansi</td>
                                    <td>: <span class="badge instansi ${s.instansi.toLowerCase().replace(' ', '')}">${s.instansi}</span></td>
                                </tr>
                                <tr>
                                    <td>Sekolah Asal</td>
                                    <td>: ${s.sekolah_asal}</td>
                                </tr>
                                <tr>
                                    <td>Nama Orang Tua/Wali</td>
                                    <td>: ${s.nama_ortu}</td>
                                </tr>
                                <tr>
                                    <td>No. HP Orang Tua</td>
                                    <td>: ${s.no_hp_ortu}</td>
                                </tr>
                                <tr>
                                    <td>Tanggal Daftar</td>
                                    <td>: ${s.created_at_formatted} WIB</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h4 class="detail-docs-title">Dokumen Pendukung (Klik untuk melihat berkas)</h4>
                    <div class="docs-grid">
                        <a href="uploads/skl/${s.file_skl}" target="_blank" class="doc-link-card">
                            <div class="doc-link-icon" style="color: #e74c3c;"><i class="fa-regular fa-file-pdf"></i></div>
                            <span class="doc-link-name">SKL / Ijazah</span>
                        </a>
                        <a href="uploads/kk/${s.file_kk}" target="_blank" class="doc-link-card">
                            <div class="doc-link-icon" style="color: #3498db;"><i class="fa-regular fa-file-pdf"></i></div>
                            <span class="doc-link-name">Kartu Keluarga</span>
                        </a>
                        <a href="uploads/akte/${s.file_akte}" target="_blank" class="doc-link-card">
                            <div class="doc-link-icon" style="color: #2ecc71;"><i class="fa-regular fa-file-pdf"></i></div>
                            <span class="doc-link-name">Akta Kelahiran</span>
                        </a>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `<p style="color: var(--danger); text-align: center; padding: 20px 0;">${res.message}</p>`;
            }
        })
        .catch(err => {
            console.error(err);
            modalBody.innerHTML = '<p style="color: var(--danger); text-align: center; padding: 20px 0;">Gagal memuat detail santri. Terjadi kesalahan koneksi.</p>';
        });
    };

    const closeModal = () => {
        modal.classList.remove('active');
        activeModalSantriId = null;
    };

    // Event listener tombol View di baris tabel
    document.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.btn-action.view');
        if (viewBtn) {
            const id = viewBtn.getAttribute('data-id');
            openModal(id);
        }
    });

    // Close modal triggers
    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (modalCloseFooter) modalCloseFooter.addEventListener('click', closeModal);
    window.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Modal action buttons
    modalVerifyBtn.addEventListener('click', () => {
        if (activeModalSantriId && confirm('Verifikasi berkas calon santri ini?')) {
            updateSantriStatus(activeModalSantriId, 'Verified');
        }
    });

    modalRejectBtn.addEventListener('click', () => {
        if (activeModalSantriId && confirm('Tolak pendaftaran calon santri ini?')) {
            updateSantriStatus(activeModalSantriId, 'Rejected');
        }
    });
});

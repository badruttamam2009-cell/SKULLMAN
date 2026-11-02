<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];
$isAdmin = ($username === 'admin');
$idsekolah = null;

// Ambil identitas sekolah untuk pengguna guru
if (!$isAdmin) {
    $querySekolah = "SELECT idsekolah FROM guru WHERE username = ?";
    $stmtSekolah = mysqli_prepare($conn, $querySekolah);

    mysqli_stmt_bind_param($stmtSekolah, 's', $username);
    mysqli_stmt_execute($stmtSekolah);

    $resultSekolah = mysqli_stmt_get_result($stmtSekolah);
    $rowSekolah = mysqli_fetch_assoc($resultSekolah);

    if (!$rowSekolah) {
        die("Data sekolah untuk akun ini tidak ditemukan.");
    }

    $idsekolah = $rowSekolah['idsekolah'];
}

// Ambil data siswa
if ($isAdmin) {
    $sql = "SELECT * FROM siswa
            WHERE status = 1 AND void = 0
            ORDER BY nis DESC";

    $stmt = mysqli_prepare($conn, $sql);
} else {
    $sql = "SELECT * FROM siswa
            WHERE idsekolah = ?
            AND status = 1
            AND void = 0
            ORDER BY nis DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $idsekolah);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Gagal mengambil data siswa: " . mysqli_error($conn));
}

$siswaList = [];

while ($row = mysqli_fetch_assoc($result)) {
    $siswaList[] = $row;
}

$totalSiswa = count($siswaList);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Siswa | SkullMan</title>

    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="style.css">

    <link rel="icon" type="image/png" href="skullman.png">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <!-- Overlay Sidebar -->
    <div id="sidebarOverlay" class="sidebar-overlay"
        onclick="closeSidebar()"></div>

    <!-- Sidebar -->
    <aside id="mySidebar" class="sidebar">

        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>

            <div>
                <h2>SkullMan</h2>
                <span>School Management</span>
            </div>
        </div>

        <div class="sidebar-menu">
            <p class="menu-label">MENU UTAMA</p>

            <a href="list_siswa.php" class="menu-link active">
                <i class="fa-solid fa-user-graduate"></i>
                <span>Data Siswa</span>
            </a>

            <a href="list_absensi.php" class="menu-link">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Riwayat Absensi</span>
            </a>

            <a href="list_guru.php" class="menu-link">
                <i class="fa-solid fa-users"></i>
                <span>Data Guru</span>
            </a>

            <a href="list_kelas.php" class="menu-link">
                <i class="fa-solid fa-chalkboard"></i>
                <span>Data Kelas</span>
            </a>

            <a href="list_kelastahun.php" class="menu-link">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Kelas Tahun</span>
            </a>

            <?php if ($isAdmin): ?>
                <a href="list_sekolah.php" class="menu-link">
                    <i class="fa-solid fa-school"></i>
                    <span>Data Sekolah</span>
                </a>
            <?php endif; ?>

            <p class="menu-label account-label">AKUN</p>

            <a href="logout.php" class="menu-link logout-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Log Out</span>
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-avatar">
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="user-info">
                <strong><?= htmlspecialchars($_SESSION['nama'] ?? $username) ?></strong>
                <span><?= htmlspecialchars($_SESSION['jabatan'] ?? 'Pengguna') ?></span>
            </div>
        </div>
    </aside>


    <!-- Main Content -->
    <main class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <button class="menu-toggle" onclick="openSidebar()"
                aria-label="Buka menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="topbar-title">
                <h1>Data Siswa</h1>
                <p>Kelola informasi dan data siswa sekolah</p>
            </div>

            <div class="topbar-user">
                <div class="topbar-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>
                    <strong><?= htmlspecialchars($_SESSION['nama'] ?? $username) ?></strong>
                    <span><?= $isAdmin ? 'Administrator' : 'Guru' ?></span>
                </div>
            </div>
        </header>


        <!-- Page Content -->
        <section class="page-container">

            <!-- Welcome -->
            <div class="welcome-section">
                <div>
                    <span class="welcome-label">MANAJEMEN SEKOLAH</span>
                    <h2>Data Siswa</h2>
                    <p>Kelola dan pantau informasi siswa dengan mudah.</p>
                </div>

                <a href="tambah_siswa.php" class="primary-button">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Siswa
                </a>
            </div>


            <!-- Statistics -->
            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-icon blue-icon">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>

                    <div>
                        <span>Total Siswa</span>
                        <h3><?= $totalSiswa ?></h3>
                        <small>Data siswa terdaftar</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>
                        <span>Status Data</span>
                        <h3>Aktif</h3>
                        <small>Menampilkan siswa aktif</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple-icon">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>

                    <div>
                        <span>Lingkup Data</span>
                        <h3><?= $isAdmin ? 'Semua Sekolah' : 'Sekolah Saya' ?></h3>
                        <small><?= $isAdmin ? 'Akses administrator' : 'Akses akun guru' ?></small>
                    </div>
                </div>

            </div>


            <!-- Student Table -->
            <div class="content-card">

                <div class="table-heading">
                    <div>
                        <h3>Daftar Siswa</h3>
                        <p>Daftar siswa yang terdaftar dalam sistem.</p>
                    </div>

                    <div class="total-badge">
                        <i class="fa-solid fa-users"></i>
                        <?= $totalSiswa ?> Siswa
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="student-table">

                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($totalSiswa > 0): ?>

                                <?php foreach ($siswaList as $index => $row): ?>
                                    <?php
                                    $nis = $row['NIS'];
                                    $modalId = 'detailModal' . $index;
                                    ?>

                                    <tr>
                                        <td class="number-cell">
                                            <?= $index + 1 ?>
                                        </td>

                                        <td>
                                            <span class="nis-text">
                                                <?= htmlspecialchars($nis) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="student-name">
                                                <div class="student-avatar">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>

                                                <strong>
                                                    <?= htmlspecialchars($row['NAMA']) ?>
                                                </strong>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="action-buttons">

                                                <button type="button"
                                                    class="action-button detail-button"
                                                    onclick="openDetail('<?= $modalId ?>')">
                                                    <i class="fa-solid fa-eye"></i>
                                                    <span>Detail</span>
                                                </button>

                                                <a href="edit_siswa.php?nis=<?= urlencode($nis) ?>"
                                                    class="action-button edit-button">
                                                    <i class="fa-solid fa-pen"></i>
                                                    <span>Edit</span>
                                                </a>

                                                <button type="button"
                                                    class="action-button delete-button"
                                                    onclick="openDeleteModal('<?= htmlspecialchars($nis, ENT_QUOTES) ?>')">
                                                    <i class="fa-solid fa-trash"></i>
                                                    <span>Hapus</span>
                                                </button>

                                            </div>
                                        </td>
                                    </tr>


                                    <!-- Detail Modal -->
                                    <div id="<?= $modalId ?>" class="custom-modal">
                                        <div class="modal-content">

                                            <div class="modal-header">
                                                <div>
                                                    <h3>Detail Data Siswa</h3>
                                                    <p>Informasi lengkap siswa</p>
                                                </div>

                                                <button type="button"
                                                    class="modal-close"
                                                    onclick="closeDetail('<?= $modalId ?>')">
                                                    &times;
                                                </button>
                                            </div>

                                            <div class="modal-body">

                                                <?php
                                                $detailFields = [
                                                    'NIS' => 'NIS',
                                                    'NAMA' => 'Nama',
                                                    'TEMPATLAHIR' => 'Tempat Lahir',
                                                    'TANGGALLAHIR' => 'Tanggal Lahir',
                                                    'JENISKELAMIN' => 'Jenis Kelamin',
                                                    'ALAMAT' => 'Alamat',
                                                    'NAMAAYAH' => 'Nama Ayah',
                                                    'NAMAIBU' => 'Nama Ibu',
                                                    'KONTAK1' => 'Kontak 1',
                                                    'KONTAK2' => 'Kontak 2',
                                                    'EMAIL' => 'Email'
                                                ];
                                                ?>

                                                <?php foreach ($detailFields as $field => $label): ?>
                                                    <div class="detail-row">
                                                        <span><?= $label ?></span>
                                                        <strong>
                                                            <?= htmlspecialchars($row[$field] ?? '-') ?>
                                                        </strong>
                                                    </div>
                                                <?php endforeach; ?>

                                                <div class="detail-row">
                                                    <span>Status</span>
                                                    <strong class="status-active">
                                                        <?= $row['STATUS'] == 1 ? 'Aktif' : 'Tidak Aktif' ?>
                                                    </strong>
                                                </div>

                                            </div>

                                            <div class="modal-footer">
                                                <button type="button"
                                                    class="secondary-button"
                                                    onclick="closeDetail('<?= $modalId ?>')">
                                                    Tutup
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <i class="fa-solid fa-folder-open"></i>
                                        <h3>Belum Ada Data Siswa</h3>
                                        <p>Data siswa belum tersedia.</p>
                                    </td>
                                </tr>

                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>

            </div>

            <footer class="page-footer">
                &copy; <?= date('Y') ?> SkullMan School Management System
            </footer>

        </section>
    </main>


    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="custom-modal">
        <div class="modal-content delete-confirmation">

            <div class="delete-icon">
                <i class="fa-solid fa-trash"></i>
            </div>

            <h3>Hapus Data Siswa?</h3>
            <p>Apakah kamu yakin ingin menghapus data siswa ini?</p>

            <div class="modal-footer centered-footer">
                <button type="button" class="secondary-button"
                    onclick="closeDeleteModal()">
                    Batal
                </button>

                <a id="confirmButton" href="#" class="danger-button">
                    Ya, Hapus
                </a>
            </div>

        </div>
    </div>


    <script>
        function openSidebar() {
            document.getElementById('mySidebar').classList.add('show');
            document.getElementById('sidebarOverlay').classList.add('show');
        }

        function closeSidebar() {
            document.getElementById('mySidebar').classList.remove('show');
            document.getElementById('sidebarOverlay').classList.remove('show');
        }

        function openDetail(id) {
            document.getElementById(id).classList.add('show');
        }

        function closeDetail(id) {
            document.getElementById(id).classList.remove('show');
        }

        function openDeleteModal(nis) {
            document.getElementById('deleteModal').classList.add('show');

            document.getElementById('confirmButton').href =
                'hapus_siswa.php?nis=' + encodeURIComponent(nis);
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('custom-modal')) {
                event.target.classList.remove('show');
            }
        });
    </script>

</body>

</html>
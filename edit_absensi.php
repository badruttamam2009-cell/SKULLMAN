<?php
include 'koneksi.php'; // Koneksi ke database
date_default_timezone_set('Asia/Jakarta');
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['idabsensi'])) {
    $idabsensi = $_GET['idabsensi'];
} else {
    die("Error: ID Absensi is not provided.");
}

// Ambil data absensi, tahun ajaran, dan kelas langsung dari query
$query_absensi_detail = "
    SELECT a.IDABSENSI, a.USERMODIFIED, kt.TAHUNAJARAN, k.NAMAKELAS, d.NIS, d.STATUS, s.NAMA
    FROM absensi a
    JOIN detilabsensi d ON a.IDABSENSI = d.IDABSENSI
    JOIN siswa s ON d.NIS = s.NIS
    JOIN kelastahun kt ON a.IDKELASTAHUN = kt.IDKELASTAHUN
    JOIN kelas k ON kt.IDKELAS = k.IDKELAS
    WHERE a.IDABSENSI = ?
";
$stmt_absensi_detail = mysqli_prepare($conn, $query_absensi_detail);
mysqli_stmt_bind_param($stmt_absensi_detail, 's', $idabsensi);
mysqli_stmt_execute($stmt_absensi_detail);
$result_absensi_detail = mysqli_stmt_get_result($stmt_absensi_detail);

if (!$result_absensi_detail || mysqli_num_rows($result_absensi_detail) == 0) {
    die("Error: Data absensi tidak ditemukan.");
}

$absensi_data = [];
while ($row = mysqli_fetch_assoc($result_absensi_detail)) {
    $absensi_data[] = $row;
}

// Ambil detail kelas dan tahun ajaran dari data pertama
$kelas_tahun = isset($absensi_data[0]) ? $absensi_data[0] : null;

// Ambil usermodified dari baris pertama data absensi
$usermodified = isset($kelas_tahun['USERMODIFIED']) ? $kelas_tahun['USERMODIFIED'] : 'Tidak Diketahui';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idabsensi = $_POST['idabsensi'];
    $kehadiran = $_POST['kehadiran'];
    $usermodified = $_SESSION['username']; // Ambil user yang melakukan update dari sesi login

    // Mulai transaksi untuk memastikan semua update berjalan dengan baik
    mysqli_begin_transaction($conn);

    try {
        // Update usermodified di tabel absensi
        $update_absensi_query = "UPDATE absensi SET USERMODIFIED = ? WHERE IDABSENSI = ?";
        $stmt_update_absensi = mysqli_prepare($conn, $update_absensi_query);
        mysqli_stmt_bind_param($stmt_update_absensi, 'ss', $usermodified, $idabsensi);
        mysqli_stmt_execute($stmt_update_absensi);

        // Update status kehadiran untuk setiap siswa di detilabsensi
        $update_detil_query = "UPDATE detilabsensi SET STATUS = ?, USERMODIFIED = ? WHERE IDABSENSI = ? AND NIS = ?";
        $stmt_update_detil = mysqli_prepare($conn, $update_detil_query);

        foreach ($kehadiran as $nis => $status) {
            mysqli_stmt_bind_param($stmt_update_detil, 'ssss', $status, $usermodified, $idabsensi, $nis);
            mysqli_stmt_execute($stmt_update_detil);
        }

        // Jika semua update berhasil, commit transaksi
        mysqli_commit($conn);

        header('Location: list_absensi.php');
        exit;
    } catch (Exception $e) {
        // Jika terjadi kesalahan, rollback transaksi
        mysqli_rollback($conn);
        echo "<script>alert('Gagal memperbarui data absensi. Silakan coba lagi.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>absensi</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="icon" type="image/png" href="skullman.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .bottom-right {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
        }

        .bottom-right i {
            margin: 0;
        }

        .w3-sidebar {
            z-index: 1100;
            position: fixed;
            left: -250px;
            width: 250px;
            height: 100%;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 0;
        }

        .w3-sidebar.show {
            left: 0;
        }

        .w3-sidebar.hide {
            left: -250px;
        }

        .w3-sidebar a {
            padding: 10px;
            text-decoration: none;
            font-size: 18px;
            color: black;
            display: block;
        }

        .w3-sidebar a:hover {
            background-color: #ddd;
        }

        .w3-sidebar .close-button {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 15px;
            background-color: #f44336;
            color: white;
            font-size: 20px;
            text-align: center;
            border: none;
            cursor: pointer;
        }

        /* Styling for sidebar overlay */
        .w3-sidebar-overlay {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .w3-sidebar-overlay.show {
            display: block;
        }

        @media (max-width: 600px) {
            .w3-table-all {
                table-layout: fixed;
                width: 100%;
            }

            .w3-table-all th,
            .w3-table-all td {
                word-wrap: break-word;
            }

            .w3-table-all th:nth-of-type(1),
            .w3-table-all td:nth-of-type(1) {
                width: 40%;
            }

            .w3-table-all th:nth-of-type(2),
            .w3-table-all td:nth-of-type(2) {
                width: 30%;
            }

            .w3-table-all th:nth-of-type(3),
            .w3-table-all td:nth-of-type(3) {
                width: 30%;
            }
        }

        .action-icons a {
            margin: 5px;
        }
     /* Styling for form elements */
.w3-container form select, 
.w3-container form #kelasSubmit {
    padding: 8px 12px;
    margin: 5px 0;
    font-size: 16px;
    border: 1px solid #ccc; /* Light gray border */
    border-radius: 4px;
    transition: all 0.3s ease;
}

/* Hover effect */
.w3-container form select:hover, 
.w3-container form #kelasSubmit:hover {
    border-color: #8B4513; /* Dark brown border */
    box-shadow: 0 0 5px rgba(139, 69, 19, 0.7); /* Brown glow */
}

/* Submit button styling */
.w3-container form #kelasSubmit {
    background-color: #8B4513; /* Dark brown background */
    color: #fff; /* White text */
    cursor: pointer;
}

/* Select dropdown styling */
.w3-container form select {
    background-color: #fff; /* White background for dropdown */
    color: #333; /* Dark text for readability */
}


.small-select {
    width: 50%; /* Lebar elemen menjadi 50% */
    padding: 10px; /* Padding untuk kenyamanan */
    font-size: 14px; /* Ukuran font */
}

/* Tambahan aturan untuk semua layar */
.full-width-table th, .full-width-table td {
    text-align: center; /* Rata tengah teks */
    vertical-align: middle; /* Rata tengah secara vertikal */
}

/* Media query untuk layar dengan lebar maksimal 600px */
@media (max-width: 600px) {
    .full-width-table {
        width: 100%; /* Tabel memenuhi lebar layar */
        font-size: 12px; /* Ukuran font lebih kecil untuk tampilan mobile */
        margin: 0; /* Hilangkan margin default */
    }

    .full-width-table th, .full-width-table td {
        word-wrap: break-word; /* Memastikan teks tidak melampaui lebar kolom */
        text-align: left; /* Penyesuaian untuk tampilan lebih baik */
    }

    /* Hilangkan padding pada container */
    .w3-container {
        padding: 0 !important; /* Hapus padding pada layar kecil */
    }

    /* Tambahkan margin pada tabel agar tetap rapi tanpa padding */
    .w3-responsive {
        margin: 5px; /* Tambahkan margin 5px */
    }

    .small-select {
        width: 100%; /* Lebar penuh pada layar kecil */
    }
#kembali-btn, #simpan-btn {
    width: 100%; 
    height: 50px;
    font-size: 16px; 
    padding: 15px; 
    text-align: center; 
    display: block; 
    line-height: 50px; 
}
}
    </style>
</head>
<body>

    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="w3-sidebar-overlay" onclick="w3_close()"></div>
  <!-- Sidebar -->
  <div class="w3-sidebar w3-bar-block w3-border-right w3-light-grey" id="mySidebar">
    <button onclick="w3_close()" class="w3-bar-item w3-button w3-red w3-center close-button">
        <b>Close</b><i class="fa fa-close" style="font-size:20px"></i>
    </button>
    <a href="list_siswa.php" class="w3-bar-item w3-button w3-border">
        <i class="fa fa-user-graduate" style="font-size:20px;"></i> List Siswa
    </a>
    <a href="list_absensi.php" class="w3-bar-item w3-button w3-border">
    <i class="fa fa-clipboard-list" style="font-size:20px;"></i> List Absensi
    </a>
    <a href="list_guru.php" class="w3-bar-item w3-button w3-border">
        <i class="fa fa-users" style="font-size:20px;"></i> List Guru
    </a>
    <a href="list_kelas.php" class="w3-bar-item w3-button w3-border">
        <i class="fa fa-chalkboard-teacher" style="font-size:20px;"></i> List Kelas
    </a>
    <a href="list_kelastahun.php" class="w3-bar-item w3-button w3-border">
        <i class="fas fa-calendar-check" style="font-size:20px;"></i> List Kelas Tahun
    </a>
    <?php if ($_SESSION['username'] == 'admin') { ?>
        <a href="list_sekolah.php" class="w3-bar-item w3-button w3-border">
            <i class="fa fa-school" style="font-size:20px;"></i> List Sekolah
        </a>
    <?php } ?>
    <a href="logout.php" class="w3-bar-item w3-button w3-red w3-center">
        <b>Log Out </b><i class="fas fa-sign-out-alt" style="font-size:20px"></i>
    </a>
</div>


   <!-- Header -->
   <div class="w3-red sticky-header" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
        <div style="flex-grow: 1; display: flex; justify-content: center;">
            <h3 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Edit Absensi</b></h3>
        </div>
    </div>



    <div class="w3-container w3-card w3-round w3-light-grey" style="margin-top: 20px;">
    <h3 class="w3-center w3-text-red"><b>Data Siswa</b></h3>
    <div class="w3-section w3-animate__animated w3-animate__fadeIn">
        <label class="w3-text-red"><b>Kelas dan tahun ajaran:</b></label>
        <input type="text" value="<?php echo $kelas_tahun ? $kelas_tahun['NAMAKELAS'] . ' - ' . $kelas_tahun['TAHUNAJARAN'] : 'Data tidak ditemukan'; ?>" readonly class="w3-input w3-border w3-round" />
    </div>

    <!-- Form Start -->
    <form method="POST" action="">
        <!-- Hidden input for absensi ID -->
        <input type="hidden" name="idabsensi" value="<?php echo $idabsensi; ?>">

        <!-- Attendance table -->
        <table class="w3-table w3-bordered w3-striped w3-hoverable w3-white w3-card-4 w3-card full-width-table">
            <thead>
                <tr class="w3-red w3-text-white">
                    <th style="text-align:center">NIS</th>
                    <th style="text-align:center">Nama Siswa</th>
                    <th style="text-align:center">Status Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($absensi_data as $siswa): 
                ?>
                    <tr>
                        <td style="text-align:center"><?php echo $siswa['NIS']; ?></td>
                        <td style="text-align:center"><?php echo $siswa['NAMA']; ?></td>
                        <td>
                            <!-- Attendance dropdown for each student -->
                            <select name="kehadiran[<?php echo $siswa['NIS']; ?>]" class="w3-select w3-border w3-round w3-light-grey small-select">
                                <option value="Hadir" <?php echo $siswa['STATUS'] == 'Hadir' ? 'selected' : ''; ?>>Hadir</option>
                                <option value="Tidak Hadir" <?php echo $siswa['STATUS'] == 'Tidak Hadir' ? 'selected' : ''; ?>>Tidak Hadir</option>
                                <option value="Sakit" <?php echo $siswa['STATUS'] == 'Sakit' ? 'selected' : ''; ?>>Sakit</option>
                                <option value="Izin" <?php echo $siswa['STATUS'] == 'Izin' ? 'selected' : ''; ?>>Izin</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>

        <!-- User Modified -->
        <label>User Modified</label>
        <input type="text" id="usermodified" name="usermodified" class="w3-input w3-border w3-light-grey"
            value="<?php echo htmlspecialchars($usermodified); ?>" readonly><br>

        <!-- Tombol Kembali dan Simpan -->
        <div class="w3-half">
            <a href="list_absensi.php" id="kembali-btn" class="w3-button w3-container w3-orange w3-padding-16" style="width: 100%;">Kembali</a>
        </div>
        <div class="w3-half">
            <input type="submit" value="Simpan" id="simpan-btn" class="w3-button w3-container w3-green w3-padding-16 w3-margin-bottom" style="width: 100%;">
        </div>
    </form>
    <!-- Form End -->
</div>



    <script>
        function w3_open() {
            document.getElementById("mySidebar").classList.add('show');
            document.getElementById("sidebarOverlay").classList.add('show');
        }

        function w3_close() {
            document.getElementById("mySidebar").classList.remove('show');
            document.getElementById("sidebarOverlay").classList.remove('show');
        }
    </script>
</body>
</html>

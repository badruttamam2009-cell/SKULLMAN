<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');
session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

$username = mysqli_real_escape_string($conn, $_SESSION['username']); // Get logged-in username

// Check if the logged-in user is 'admin'
if ($username == 'admin') {
    // Admin can view data for all schools
    $query_sekolah = "SELECT idsekolah FROM sekolah";
} else {
    // If not admin, retrieve idsekolah based on the username from the guru table
    $query_sekolah = "SELECT s.idsekolah 
                      FROM sekolah s 
                      JOIN guru g ON s.idsekolah = g.idsekolah 
                      WHERE g.username = ?";
}

$stmt_sekolah = mysqli_prepare($conn, $query_sekolah);

if ($username != 'admin') {
    // Bind username only if not admin
    mysqli_stmt_bind_param($stmt_sekolah, 's', $username);
}

mysqli_stmt_execute($stmt_sekolah);
$result_sekolah = mysqli_stmt_get_result($stmt_sekolah);

if ($result_sekolah && mysqli_num_rows($result_sekolah) > 0) {
    $row_sekolah = mysqli_fetch_assoc($result_sekolah);
    $idsekolah = $row_sekolah['idsekolah'];
    $_SESSION['idsekolah'] = $idsekolah; // Store idsekolah in session
} else {
    echo "Error: School ID not found for the user.";
    exit;
}

$idkelas = $_GET['id'];

$sql = "SELECT * FROM kelas WHERE IDKELAS = '$idkelas'";
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);

// Get the current USERMODIFIED value from the database
$userModified = $data['USERMODIFIED'];

// Modal variables
$showErrorModal = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namakelas = $_POST['NAMAKELAS'];
    $keterangan = $_POST['KETERANGAN'];
    $datemodified = date('Y-m-d H:i:s');
    $usermodified = $username;

    // Check if the new class name already exists (excluding the current class being edited)
    $sql_check_duplicate = "SELECT * FROM kelas WHERE NAMAKELAS = ? AND IDKELAS != ?";
    $stmt_check = mysqli_prepare($conn, $sql_check_duplicate);
    mysqli_stmt_bind_param($stmt_check, 'si', $namakelas, $idkelas);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        // Duplicate found
        $showErrorModal = true;
        $errorMessage = "Nama kelas '$namakelas' sudah ada. Silakan gunakan nama lain.";
    } else {
        // Proceed with the update
        $sql_update = "UPDATE kelas 
                       SET NAMAKELAS = '$namakelas', KETERANGAN = '$keterangan', DATEMODIFIED = '$datemodified', USERMODIFIED = '$usermodified' 
                       WHERE IDKELAS = '$idkelas'";

        if (mysqli_query($conn, $sql_update)) {
            header('Location: list_kelas.php');
            exit;
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kelas</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="icon" type="image/png" href="skullman.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .w3-sidebar {
            z-index: 1100;
            position: fixed;
            left: -250px;
            width: 250px;
            height: 100%;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 0;
            background-color: #f4f4f4;
            border-right: 1px solid #ccc;
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
    <div class="w3-red" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
        <div style="flex-grow: 1; display: flex; justify-content: center;">
            <h3 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Edit Data Kelas</b></h3>
        </div>
    </div>

    <form method="POST" class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">
        <label>Nama Kelas</label>
        <input class="w3-input w3-border w3-round" type="text" name="NAMAKELAS"
            value="<?php echo htmlspecialchars($data['NAMAKELAS'], ENT_QUOTES, 'UTF-8'); ?>" required><br>

        <label>Keterangan</label>
        <textarea class="w3-input w3-border w3-round" name="KETERANGAN" rows="4"
            required><?php echo htmlspecialchars($data['KETERANGAN'], ENT_QUOTES, 'UTF-8'); ?></textarea><br>

        <label>User Modified</label>
        <input class="w3-input w3-border w3-round" type="text" name="USERMODIFIED"
            value="<?php echo htmlspecialchars($data['USERMODIFIED'], ENT_QUOTES, 'UTF-8'); ?>" readonly><br>

        <div class="w3-half">
            <a href="list_kelas.php" class="w3-button w3-container w3-orange w3-padding-16"
                style="width: 100%;">Kembali</a>
        </div>
        <div class="w3-half">
            <input type="hidden" name="action" value="edit">
            <input type="submit" value="Simpan" class="w3-button w3-container w3-green w3-padding-16"
                style="width: 100%;">
        </div>
    </form>

    <?php if ($showErrorModal): ?>
        <div class="w3-modal" style="display:block;">
            <div class="w3-modal-content w3-animate-top w3-card-4">
                <header class="w3-container w3-red">
                    <span class="w3-button w3-hover-white w3-xlarge w3-display-topright"
                        onclick="document.querySelector('.w3-modal').style.display='none'">&times;</span>
                    <h2>Informasi</h2>
                </header>
                <div class="w3-container">
                    <p><?php echo htmlspecialchars($errorMessage); ?></p>
                </div>
            </div>
        </div>
        <script>
            // Auto-hide the modal after 3 seconds
            setTimeout(function () {
                const modal = document.querySelector('.w3-modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            }, 3000); // 3000 ms = 3 seconds
        </script>
    <?php endif; ?>

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
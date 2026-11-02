<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['idkelastahun'])) {
    $idkelastahun_to_delete = $_GET['idkelastahun'];

    mysqli_begin_transaction($conn);

    try {
        // Hapus data terkait di kelastahunsiswa
        $stmt_siswa = mysqli_prepare($conn, "DELETE FROM kelastahunsiswa WHERE IDKELASTAHUN = ?");
        mysqli_stmt_bind_param($stmt_siswa, 's', $idkelastahun_to_delete);
        if (!mysqli_stmt_execute($stmt_siswa)) {
            throw new Exception('Gagal menghapus data siswa terkait.');
        }
        mysqli_stmt_close($stmt_siswa);

        // Hapus data utama di kelastahun
        $stmt_kelastahun = mysqli_prepare($conn, "DELETE FROM kelastahun WHERE IDKELASTAHUN = ?");
        mysqli_stmt_bind_param($stmt_kelastahun, 's', $idkelastahun_to_delete);
        if (!mysqli_stmt_execute($stmt_kelastahun)) {
            throw new Exception('Gagal menghapus data kelas tahun.');
        }
        mysqli_stmt_close($stmt_kelastahun);

        // Commit transaksi
        mysqli_commit($conn);

        // Redirect ke halaman utama
        header('Location: list_kelastahun.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        mysqli_rollback($conn);
        echo "Error: " . $e->getMessage();
    }
}
// Periksa apakah username adalah "admin"
$is_admin = ($username === 'admin');

// Query untuk mendapatkan data kelastahun beserta daftar NIS
$query_kelastahun = "
SELECT 
    kt.IDKELASTAHUN,
    kt.TAHUNAJARAN,
    k.NAMAKELAS, 
    g.NAMA, -- Nama
    kt.STATUS,
    GROUP_CONCAT(DISTINCT s.NIS ORDER BY s.NIS SEPARATOR ', ') AS daftar_nis,
    COUNT(DISTINCT s.NIS) AS total_siswa
FROM 
    kelastahun kt
LEFT JOIN kelas k ON kt.IDKELAS = k.IDKELAS
LEFT JOIN guru g ON kt.NIP = g.NIP
LEFT JOIN kelastahunsiswa kts ON kt.IDKELASTAHUN = kts.IDKELASTAHUN
LEFT JOIN siswa s ON kts.NIS = s.NIS
WHERE 
    ";

// Jika admin, tampilkan semua data, jika bukan admin, batasi berdasarkan idsekolah
if ($is_admin) {
    $query_kelastahun .= "1"; // Menampilkan semua data
} else {
    $query_kelastahun .= "kt.IDSEKOLAH = (SELECT idsekolah FROM guru WHERE username = ?)";
}

$query_kelastahun .= "
GROUP BY 
    kt.IDKELASTAHUN
ORDER BY 
    kt.TAHUNAJARAN DESC, k.IDKELAS;
";

$stmt_kelastahun = mysqli_prepare($conn, $query_kelastahun);

// Jika bukan admin, bind parameter untuk username
if (!$is_admin) {
    mysqli_stmt_bind_param($stmt_kelastahun, 's', $username);
}

mysqli_stmt_execute($stmt_kelastahun);
$result_kelastahun = mysqli_stmt_get_result($stmt_kelastahun);

// Fetch all kelastahun data into an array
$kelastahun_data = [];
while ($row = mysqli_fetch_assoc($result_kelastahun)) {
    $kelastahun_data[] = $row;
}

mysqli_stmt_close($stmt_kelastahun);

// Data siswa untuk setiap ID kelastahun dengan NIS dan Nama
$siswa_data = [];
$query_siswa = "
SELECT kts.IDKELASTAHUN, s.NIS, s.NAMA
FROM kelastahunsiswa kts
LEFT JOIN siswa s ON kts.NIS = s.NIS
WHERE kts.IDKELASTAHUN = ?
";

$stmt_siswa = mysqli_prepare($conn, $query_siswa);

// Loop through the kelastahun data to get the student data for each IDKELASTAHUN
foreach ($kelastahun_data as $kelastahun) {
    mysqli_stmt_bind_param($stmt_siswa, 's', $kelastahun['IDKELASTAHUN']);
    mysqli_stmt_execute($stmt_siswa);
    $result_siswa = mysqli_stmt_get_result($stmt_siswa);

    $siswa_data[$kelastahun['IDKELASTAHUN']] = [];
    while ($row_siswa = mysqli_fetch_assoc($result_siswa)) {
        $siswa_data[$kelastahun['IDKELASTAHUN']][] = [
            'nis' => $row_siswa['NIS'],
            'nama' => $row_siswa['NAMA'],
        ];
    }
}
mysqli_stmt_close($stmt_siswa);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelastahun Data</title>
    <link rel="stylesheet" href="w3.css">
    <link rel="icon" type="image/png" href="skullman.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
         /* Styling for specific elements */
         .action-icons {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
        }

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
        @media only screen and (max-width: 600px) {
    .accessButton {
        font-size: 12px; /* Ukuran font yang lebih kecil */
        padding: 4px 8px; /* Padding yang lebih kecil */
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
    <a href="#" class="w3-bar-item w3-button w3-border">
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

      <!-- Page Content -->
      <div id="mainContent" style="margin-left: 0; transition: margin-left 0.5s;">
        <div class="w3-red sticky-header" style="display: flex; align-items: center; padding: 10px;">
            <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
            <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                <h1 style="margin: 0; line-height: 1.5rem; text-align: center; font-size: 30px; margin-top:5px; margin-bottom: 10px;">
                    <b>List kelas tahun</b>
                </h1>
                <div style="display: flex; font-size: 14px;">
                    <div style="flex: 1; text-align: left;">
                        <span style="font-weight: bold;">TAHUN AJARAN</span><br>
                        <span style="font-size: 15px; font-weight: bold;">KELAS</span> <br>
                    </div>
                    <div style="flex: 1; text-align: left;">
                        <span style="font-size: 15px; font-weight: bold;">WALI KELAS</span> <br>
                        <span style="font-size: 15px; font-weight: bold;">STATUS</span> <br>
                    </div>
                    <div style="flex: 1; text-align: left;">
                        <br> <span style="font-size: 15px; font-weight: bold;">DATA SISWA</span>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <span style="font-size: 16px; font-weight: bold;">Action</span>
                    </div>
                </div>
            </div>
        </div>
        <br>

   <!-- Table to display kelastahun data -->
<div class="w3-responsive">
    <table class="w3-table-all w3-centered" border="1" style="border-collapse: collapse; width: 100%;">
        <tbody>
            <?php foreach ($kelastahun_data as $row) { ?>
                <tr>
                    <td style="flex: 1; text-align: left; width: 20%">
                        <span style="font-size: 14px; "><?php echo htmlspecialchars($row['TAHUNAJARAN']); ?></span> <br>
                        <span style="font-size: 14px; "><?php echo htmlspecialchars($row['NAMAKELAS']); ?></span> <br>
                    </td>
                    <td style="flex: 1; text-align: center; max-width: 300px; word-wrap: break-word; word-break: break-word; width: 25%">
                        <span style="font-size: 14px; "><?php echo htmlspecialchars($row['NAMA']); ?></span> <br>
                        <span style="font-size: 14px; "><?php echo $row['STATUS'] == 1 ? 'Aktif' : 'Tidak Aktif'; ?></span> <br>
                    </td>
                    <td style="flex: 1; text-align: center;">
                        <span style="">
                        <button class="accessButton w3-button w3-brown w3-round" 
                        onclick="openModal(<?php echo htmlspecialchars(json_encode($siswa_data[$row['IDKELASTAHUN']])); ?>, '<?php echo htmlspecialchars($row['TAHUNAJARAN']); ?>')">
                        Lihat Data Siswa
                        </button>
                        </span>
                    </td>
                    <td style="width: 31%">
                        <a href="edit_kelastahun.php?idkelastahun=<?php echo urlencode($row['IDKELASTAHUN']); ?>" 
                           class="material-icons w3-yellow w3-btn w3-button w3-round" 
                           style="font-size: 15px;">&#xe22b;</a>
                           <a href="#" onclick="deleteKelastahun('<?php echo htmlspecialchars($row['IDKELASTAHUN']); ?>', '<?php echo htmlspecialchars($row['TAHUNAJARAN']); ?>')" 
   class="fa fa-trash w3-btn w3-button w3-round w3-red" 
   style="font-size: 15px;"></a>

                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal Structure -->
<div id="accessModal" class="w3-modal">
    <div class="w3-modal-content w3-animate-top w3-card-4" style="width: 50%;" onclick="event.stopPropagation()">
        <header class="w3-container w3-brown w3-center">
            <h2><b>Data Siswa</b></h2>
        </header>
        <div class="w3-container">
            <table class="w3-table-all w3-hoverable">
                <thead>
                    <tr>
                        <th class="w3-center">NIS</th>
                        <th class="w3-center">Nama Siswa</th>
                    </tr>
                </thead>
                <tbody id="accessDataList"></tbody>
            </table>
            <div class="w3-right">
                <button class="w3-button w3-red" onclick="closeModal1()">Tutup</button>
            </div>
        </div>
    </div>
</div>



<div id="deleteModal" class="w3-modal" style="align-items:center; padding-top: 15%;">
    <div class="w3-modal-content w3-animate-top w3-card-4">
        <header class="w3-container w3-red">
            <span onclick="document.getElementById('deleteModal').style.display='none'" 
                  class="w3-button w3-display-topright">&times;</span>
            <h2>Konfirmasi</h2>
        </header>
        <div class="w3-container">
            <p id="modalMessage">Apakah Anda yakin ingin menghapus data ini?</p>
            <div class="w3-right">
                <button class="w3-button w3-grey" 
                        onclick="document.getElementById('deleteModal').style.display='none'">Batal</button>
                <button id="confirmButton" class="w3-button w3-red">Hapus</button>
            </div>
        </div>
    </div>
</div>



         <!-- CSS untuk modal -->
         <style>
            .w3-modal {
                display: none;
                /* Sembunyikan modal secara default */
                position: fixed;
                z-index: 9999;
                /* Pastikan modal berada di atas semua elemen lain */
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.4);
                /* Latar belakang gelap semi-transparan */
            }
        </style>

<a href="tambah_kelastahun.php" class="w3-btn w3-round-xlarge w3-brown bottom-right">
            <i class="fa fa-plus" style="font-size:30px"></i>
        </a>
    <!-- JavaScript to handle modal behavior -->
    <script>
                // Function to update button text based on screen width
function updateButtonText() {
    const buttons = document.querySelectorAll(".accessButton"); // Select all buttons with the class accessButton
    buttons.forEach(button => {
        if (window.innerWidth <= 600) {
            button.innerHTML = "Lihat<br>Data Siswa"; // Update text for small screens
        } else {
            button.innerHTML = "Lihat Data Siswa"; // Original text for larger screens
        }
    });
}

// Initial call to set the button text
updateButtonText();
// Update text on window resize
window.addEventListener("resize", updateButtonText);

// Function to open modal and populate the table with NIS data
function openModal(nisList, tahunajaran) {
    var tableBody = document.getElementById('accessDataList');
    tableBody.innerHTML = ''; // Clear previous data

    // Loop through each NIS and add it to the table
    nisList.forEach(function(student) {
        var row = document.createElement('tr');
        var cell1 = document.createElement('td');
        var cell2 = document.createElement('td');

        // Add 'w3-center' class to cells for centering content
        cell1.classList.add('w3-center');
        cell2.classList.add('w3-center');

        cell1.textContent = student.nis; // NIS
        cell2.textContent = student.nama; // Nama Siswa

        row.appendChild(cell1);
        row.appendChild(cell2);
        tableBody.appendChild(row);
    });

    // Display the modal
    document.getElementById('accessModal').style.display = 'block';
}

// Close modal when the "Tutup" button is clicked
function closeModal1() {
    document.getElementById('accessModal').style.display = 'none';
}


function deleteKelastahun(idkelastahun, tahunajaran) {
    // Open modal and update message
    var modal = document.getElementById('deleteModal');
    modal.style.display = 'block'; // Show modal

    var modalMessage = document.getElementById('modalMessage');
    if (modalMessage) {
        modalMessage.textContent = "Apakah Anda yakin ingin menghapus data Kelas Tahun Ajaran'" + tahunajaran + "'?";
    }

    var confirmButton = document.getElementById('confirmButton');
    if (confirmButton) {
        confirmButton.onclick = function () {
            // Redirect to PHP deletion script
            window.location.href = "list_kelastahun.php?action=delete&idkelastahun=" + encodeURIComponent(idkelastahun);
        };
    }
}

function closeModal(event) {
    var modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
}


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

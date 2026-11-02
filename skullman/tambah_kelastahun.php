<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

$username = mysqli_real_escape_string($conn, $_SESSION['username']); // Dapatkan username yang login
$showModal = false;
$error_message = ""; // Variabel untuk pesan error
$id_kelastahun = ""; // Variabel untuk menyimpan ID kelastahun yang baru ditambahkan

// Jika username adalah 'admin', maka tampilkan semua data sekolah, kelas, guru, dan siswa
if ($username == 'admin') {
    $query_sekolah = "SELECT idsekolah FROM sekolah";
} else {
    // Jika bukan admin, tampilkan idsekolah berdasarkan username dari tabel guru
    $query_sekolah = "SELECT s.idsekolah 
                      FROM sekolah s 
                      JOIN guru g ON s.idsekolah = g.idsekolah 
                      WHERE g.username = ?";
}

$stmt_sekolah = mysqli_prepare($conn, $query_sekolah);

if ($username != 'admin') {
    // Bind username hanya jika bukan admin
    mysqli_stmt_bind_param($stmt_sekolah, 's', $username);
}

mysqli_stmt_execute($stmt_sekolah);
$result_sekolah = mysqli_stmt_get_result($stmt_sekolah);

if ($result_sekolah && mysqli_num_rows($result_sekolah) > 0) {
    $row_sekolah = mysqli_fetch_assoc($result_sekolah);
    $idsekolah = $row_sekolah['idsekolah']; // Ambil nilai idsekolah
} else {
    echo "Error: School ID not found for the user.";
    exit;
}

// Handle form submission untuk menambah kelastahun dan kelastahunsiswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $now = date('Y-m-d H:i:s');
    
    // Ambil data POST untuk kelastahun
    $tahunajaran = mysqli_real_escape_string($conn, $_POST['tahunajaran']);
    $idkelas = mysqli_real_escape_string($conn, $_POST['idkelas']);
    $nip = mysqli_real_escape_string($conn, $_POST['nip']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $nis_list = isset($_POST['nis']) ? explode(',', $_POST['nis']) : []; // Ambil NIS sebagai array

    // Cek jika tahunajaran sudah ada untuk idsekolah dan idkelas yang sama
    $check_tahun_query = "SELECT COUNT(*) AS count 
                          FROM kelastahun 
                          WHERE tahunajaran = ? AND idkelas = ? AND idsekolah = ?";
    $stmt_check_tahun = mysqli_prepare($conn, $check_tahun_query);
    mysqli_stmt_bind_param($stmt_check_tahun, 'sss', $tahunajaran, $idkelas, $idsekolah);
    mysqli_stmt_execute($stmt_check_tahun);
    $result_check_tahun = mysqli_stmt_get_result($stmt_check_tahun);
    
    if ($result_check_tahun) {
        $row = mysqli_fetch_assoc($result_check_tahun);
        if ($row['count'] > 0) {
            $error_message = "Tahun ajaran untuk kelas ini sudah ada"; // Set pesan error
        } else {
            // Insert kelastahun baru
            $sql_kelastahun = "INSERT INTO kelastahun (TAHUNAJARAN, IDKELAS, USERRECORD, DATERECORD, DATEMODIFIED, USERMODIFIED, STATUS, NIP, IDSEKOLAH)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_kelastahun = mysqli_prepare($conn, $sql_kelastahun);
            mysqli_stmt_bind_param($stmt_kelastahun, 'sssssssss', $tahunajaran, $idkelas, $username, $now, $now, $username, $status, $nip, $idsekolah);

            if (mysqli_stmt_execute($stmt_kelastahun)) {
                $id_kelastahun = mysqli_insert_id($conn); // Ambil ID kelastahun yang baru ditambahkan

                // Insert ke kelastahunsiswa jika ada NIS yang dipilih
                if (!empty($nis_list)) {
                    $sql_kelastahunsiswa = "INSERT INTO kelastahunsiswa (IDKELASTAHUN, NIS, DATERECORD, DATEMODIFIED, USERRECORD, USERMODIFIED, IDSEKOLAH)
                                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt_kelastahunsiswa = mysqli_prepare($conn, $sql_kelastahunsiswa);

                    foreach ($nis_list as $nis) {
                        $nis_clean = mysqli_real_escape_string($conn, $nis);

                        // Validasi NIS untuk sekolah yang sesuai
                        $check_nis_query = "SELECT COUNT(*) AS count 
                                            FROM siswa 
                                            WHERE nis = ? AND idsekolah = ?";
                        $stmt_check_nis = mysqli_prepare($conn, $check_nis_query);
                        mysqli_stmt_bind_param($stmt_check_nis, 'ss', $nis_clean, $idsekolah);
                        mysqli_stmt_execute($stmt_check_nis);
                        $result_check_nis = mysqli_stmt_get_result($stmt_check_nis);

                        if ($result_check_nis) {
                            $row = mysqli_fetch_assoc($result_check_nis);
                            if ($row['count'] > 0) { // Jika NIS valid, masukkan NIS ke tabel kelastahunsiswa
                                mysqli_stmt_bind_param($stmt_kelastahunsiswa, 'sssssss', $id_kelastahun, $nis_clean, $now, $now, $username, $username, $idsekolah);
                                if (!mysqli_stmt_execute($stmt_kelastahunsiswa)) {
                                    echo "Error inserting NIS: " . mysqli_error($conn);
                                }
                            } else {
                                echo "Invalid NIS for this school: $nis_clean<br>";
                            }
                        }
                    }
                }
                $showModal = true;
            } else {
                echo "Error inserting kelastahun: " . mysqli_error($conn);
            }
        }
    }
}
// Query untuk mengambil kelas, guru, dan siswa sesuai dengan session idsekolah
if ($username == 'admin') {
    // Jika admin, tampilkan semua kelas, guru, dan siswa tanpa filter idsekolah
    $query_kelas = "SELECT idkelas, namakelas FROM kelas";
    $query_guru = "SELECT nip, nama FROM guru WHERE nama != 'admin' AND status = 'Bekerja' AND void = 0";
    $query_siswa = "SELECT nis, nama FROM siswa WHERE void = 0";
} else {
    // Jika bukan admin, ambil data berdasarkan idsekolah
    $query_kelas = "SELECT idkelas, namakelas FROM kelas WHERE idsekolah = ?";
    $query_guru = "SELECT nip, nama FROM guru WHERE idsekolah = ? AND status = 'Bekerja' AND void = 0";
    $query_siswa = "SELECT nis, nama FROM siswa WHERE idsekolah = ? AND void = 0";
}

// Query untuk kelas
$stmt_kelas = mysqli_prepare($conn, $query_kelas);
if ($username != 'admin') {
    mysqli_stmt_bind_param($stmt_kelas, 's', $idsekolah);
}
mysqli_stmt_execute($stmt_kelas);
$result_kelas = mysqli_stmt_get_result($stmt_kelas);

// Query untuk guru
$stmt_guru = mysqli_prepare($conn, $query_guru);
if ($username != 'admin') {
    mysqli_stmt_bind_param($stmt_guru, 's', $idsekolah);
}
mysqli_stmt_execute($stmt_guru);
$result_guru = mysqli_stmt_get_result($stmt_guru);

// Query untuk siswa
$stmt_siswa = mysqli_prepare($conn, $query_siswa);
if ($username != 'admin') {
    mysqli_stmt_bind_param($stmt_siswa, 's', $idsekolah);
}
mysqli_stmt_execute($stmt_siswa);
$result_siswa = mysqli_stmt_get_result($stmt_siswa);

// Cek apakah semua query berhasil
if (!$result_kelas) {
    echo "Error fetching classes from database: " . mysqli_error($conn);
    exit;
}
if (!$result_guru) {
    echo "Error fetching teachers from database: " . mysqli_error($conn);
    exit;
}
if (!$result_siswa) {
    echo "Error fetching students from database: " . mysqli_error($conn);
    exit;
}

// Tutup koneksi setelah mengambil data
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="skullman.png">
    <title>kelas tahun</title>
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
        <h3 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Tambah kelas tahun</b></h3>
    </div>
</div>

<form method="POST" action="" class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">    
    <!-- Tabel Tahun Ajaran -->
    <label for="tahunajaran">Tahun Ajaran</label>
    <select name="tahunajaran" id="tahunajaran" class="w3-select w3-light-grey" required>
        <option value="" disabled selected>Pilih Tahun Ajaran</option>
        <?php
        // Hitung tahun ajaran: sebelumnya, sekarang, dan mendatang
        $currentYear = date('Y');
        $previousYear = $currentYear - 1;
        $nextYear = $currentYear + 1;

        // Format tahun ajaran
        $tahunAjaranOptions = [
            "$previousYear/" . ($previousYear + 1),
            "$currentYear/" . ($currentYear + 1),
            "$nextYear/" . ($nextYear + 1)
        ];

        // Generate options untuk select
        foreach ($tahunAjaranOptions as $tahunAjaran) {
            echo "<option value='$tahunAjaran'>$tahunAjaran</option>";
        }
        ?>
    </select>
    
    <label for="idkelas">Kelas</label>
    <select name="idkelas" id="idkelas" class="w3-select w3-light-grey" required>
        <option value="" disabled selected>Pilih Kelas</option>
        <?php while ($row = mysqli_fetch_assoc($result_kelas)) { ?>
            <option value="<?php echo $row['idkelas']; ?>"><?php echo $row['namakelas']; ?></option>
        <?php } ?>
    </select>

    <label for="nip">Wali Kelas</label>
    <select name="nip" id="nip" class="w3-select w3-light-grey" required>
        <option value="" disabled selected>Pilih Wali Kelas</option>
        <?php while ($row = mysqli_fetch_assoc($result_guru)) { ?>
            <option value="<?php echo $row['nip']; ?>"><?php echo $row['nama']; ?></option>
        <?php } ?>
    </select>

    <label for="status">Status</label>
    <select name="status" id="status" class="w3-select w3-light-grey" required>
        <option value="" disabled selected>Pilih Status</option>
        <option value="1">Aktif</option>
        <option value="0">Tidak Aktif</option>
    </select>

    <label for="nis">Siswa</label> <br>
    <input type="hidden" name="nis" id="hiddenNis" value="" required>
    <button type="button" class="w3-btn w3-red w3-round-large" id="openModal">Pilih</button> <br> <br>
    <div class="w3-half">
        <a href="list_kelastahun.php" class="w3-button w3-container w3-orange w3-padding-16" style="width: 100%;">Kembali</a>
    </div>
    <div class="w3-half">
        <input type="hidden" name="action" value="tambah">
        <input type="submit" value="Simpan" class="w3-button w3-container w3-green w3-padding-16" style="width: 100%;">
    </div>
</form>

<!-- Modal for selecting students -->
<div id="siswaModal" class="w3-modal">
    <div class="w3-modal-content w3-animate-top w3-card-4">
        <header class="w3-container w3-red">
            <span class="w3-btn w3-display-topright w3-large w3-text-white" id="closeModal">&times;</span>
            <h2 class="w3-center"><b>Pilih Siswa</b></h2>
        </header>
        <div class="w3-container">
            <form id="siswaForm">
                <?php while ($row = mysqli_fetch_assoc($result_siswa)) { ?>
                    <label class="w3-large">
                        <input class="w3-check" type="checkbox" name="nis[]" value="<?php echo $row['nis']; ?>"> <?php echo $row['nama']; ?>
                    </label><br>
                <?php } ?>
            </form>
        </div>
        <footer class="w3-container w3-padding-large">
            <button type="button" class="w3-btn w3-round-large w3-red w3-right" id="submitSiswa">Pilih</button>
            <button type="button" class="w3-btn w3-round-large w3-brown w3-right w3-margin-right" id="closeModalBtn">Tutup</button>
        </footer>
    </div>
</div>


<?php if ($showModal): ?>
    <div id="successModal" class="w3-modal" style="display:block;">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-green">
                <span class="w3-button w3-hover-red w3-xlarge w3-display-topright"
                    onclick="document.getElementById('successModal').style.display='none'">&times;</span>
                <h2>Informasi</h2>
            </header>
            <div class="w3-container">
                <p>Data kelas tahun dan siswa berhasil ditambahkan.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal for Error Message -->
<?php if (!empty($error_message)): ?>
    <div id="errorModal" class="w3-modal" style="display:block;" onclick="this.style.display='none'">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-red">
                <span onclick="document.getElementById('errorModal').style.display='none'" 
                      class="w3-button w3-display-topright">&times;</span>
                <h2>Informasi</h2>
            </header>
            <div class="w3-container">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Pesan Kesalahan -->
<div id="errorModal" class="w3-modal">
    <div class="w3-modal-content w3-animate-top w3-card-4">
        <header class="w3-container w3-red">
            <span id="closeErrorModal" class="w3-btn w3-display-topright w3-large w3-text-white">&times;</span>
            <h2>Informasi</h2>
        </header>
        <div class="w3-container">
            <p>Data siswa tidak boleh kosong</p>
        </div>
    </div>
</div>


<script>
   // Function to close the modal
function closeModal() {
    document.getElementById('successModal').style.display = 'none';
}

// Function to redirect to another page
function redirectToList() {
    setTimeout(function () {
        window.location.href = 'list_kelastahun.php';
    }, 1500); // Redirect after 1.5 seconds
}

// Show modal and handle redirection when the document is loaded
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('successModal');

    <?php if ($showModal): ?>
        modal.style.display = 'block'; // Show the modal
        redirectToList(); // Redirect after delay
    <?php endif; ?>
});

// Open the modal when the "Pilih Siswa" button is clicked
document.getElementById('openModal').onclick = function() {
    document.getElementById('siswaModal').style.display = 'block';
}

// Close the modal when the close button is clicked
document.getElementById('closeModal').onclick = function() {
    document.getElementById('siswaModal').style.display = 'none';
}

// Close the modal when the "Tutup" button is clicked
document.getElementById('closeModalBtn').onclick = function() {
    document.getElementById('siswaModal').style.display = 'none';
}

// Submit selected students when the "Pilih" button in the modal is clicked
document.getElementById('submitSiswa').onclick = function () {
    var selectedStudents = [];
    var checkboxes = document.querySelectorAll('input[name="nis[]"]:checked');

    // Ensure at least one student is selected
    if (checkboxes.length === 0) {
        // Show the error modal if no student is selected
        document.getElementById("errorModal").style.display = "block";
        return; // Prevent modal from closing or form from being submitted
    }

    checkboxes.forEach(function (checkbox) {
        selectedStudents.push(checkbox.value);
    });

    // Update a hidden input field with the selected NIS values (comma-separated)
    var hiddenInput = document.getElementById('hiddenNis');
    hiddenInput.value = selectedStudents.join(',');

    // Close the modal
    document.getElementById('siswaModal').style.display = 'none';
};

// Handle form submit to check if students are selected
document.querySelector('form').onsubmit = function(event) {
    // Get the hidden NIS input value
    var nisValue = document.getElementById('hiddenNis').value;

    // Check if no student has been selected
    if (nisValue === "") {
        // Prevent form submission
        event.preventDefault();

        // Show the error modal
        document.getElementById("errorModal").style.display = "block";
    }
};

// Close the error modal when the user clicks the 'X' button
document.getElementById("closeErrorModal").onclick = function() {
    document.getElementById("errorModal").style.display = "none";
};

// Close the error modal if the user clicks outside of it
window.onclick = function(event) {
    if (event.target == document.getElementById('errorModal')) {
        document.getElementById('errorModal').style.display = 'none';
    }
};

document.addEventListener("DOMContentLoaded", function () {
    var inputs = document.querySelectorAll('input[required], select[required]'); // Menangkap input dan select yang required
    
    inputs.forEach(input => {
        input.addEventListener('invalid', function (event) {
            event.preventDefault();
            // Pesan validasi custom
            let message = "Mohon diisi, tidak boleh kosong";

            // Khusus untuk select
            if (input.tagName.toLowerCase() === 'select') {
                message = "Silakan pilih salah satu opsi";
            }

            input.setCustomValidity(message);

            // Tampilkan pesan jika nilai belum valid
            if (input.validity.valueMissing) {
                input.reportValidity();
            }
        });

        input.addEventListener('input', function () {
            input.setCustomValidity(""); // Reset pesan custom ketika ada perubahan input
        });
    })
});

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

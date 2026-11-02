<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['idkelastahun'])) {
    $idkelastahun = $_GET['idkelastahun'];
} else {
    die("Error: ID Kelas Tahun is not provided.");
}

$username = mysqli_real_escape_string($conn, $_SESSION['username']); // Get logged-in username

// Check if the logged-in user is 'admin'
if ($username == 'admin') {
    // Admin can access all schools
    $query_sekolah = "SELECT idsekolah FROM sekolah";
    $idsekolah = null; // Admin does not need to filter by school
} else {
    // Retrieve idsekolah based on the username from the guru table
    $query_sekolah = "SELECT s.idsekolah 
                      FROM sekolah s 
                      JOIN guru g ON s.idsekolah = g.idsekolah 
                      WHERE g.username = ?";
    $stmt_sekolah = mysqli_prepare($conn, $query_sekolah);
    mysqli_stmt_bind_param($stmt_sekolah, 's', $username);
    mysqli_stmt_execute($stmt_sekolah);
    $result_sekolah = mysqli_stmt_get_result($stmt_sekolah);

    if ($result_sekolah && mysqli_num_rows($result_sekolah) > 0) {
        $row_sekolah = mysqli_fetch_assoc($result_sekolah);
        $idsekolah = $row_sekolah['idsekolah'];
        $_SESSION['idsekolah'] = $idsekolah; // Store idsekolah in session
    } else {
        die("Error: School ID not found for the user.");
    }
}

// Query to fetch kelastahun data
if ($username == 'admin') {
    $sql_kelastahun = "SELECT IDKELASTAHUN, tahunajaran, idkelas, nip, status, userrecord, usermodified, daterecord, datemodified 
                       FROM kelastahun 
                       WHERE IDKELASTAHUN = ?";
    $stmt_kelastahun = mysqli_prepare($conn, $sql_kelastahun);
    mysqli_stmt_bind_param($stmt_kelastahun, 's', $idkelastahun);
} else {
    $sql_kelastahun = "SELECT IDKELASTAHUN, tahunajaran, idkelas, nip, status, userrecord, usermodified, daterecord, datemodified 
                       FROM kelastahun 
                       WHERE IDKELASTAHUN = ? AND IDSEKOLAH = ?";
    $stmt_kelastahun = mysqli_prepare($conn, $sql_kelastahun);
    mysqli_stmt_bind_param($stmt_kelastahun, 'ss', $idkelastahun, $idsekolah);
}

mysqli_stmt_execute($stmt_kelastahun);
$result_kelastahun = mysqli_stmt_get_result($stmt_kelastahun);

if ($result_kelastahun && mysqli_num_rows($result_kelastahun) > 0) {
    $row_kelastahun = mysqli_fetch_assoc($result_kelastahun);
    $tahunajaran = $row_kelastahun['tahunajaran'];
    $idkelas = $row_kelastahun['idkelas'];
    $nip = $row_kelastahun['nip'];
    $status = $row_kelastahun['status'];
    $datemodified = date('Y-m-d H:i:s');
} else {
    // Do not display an error message for admin
    if ($username !== 'admin') {
        die("kelastahun data with ID $idkelastahun not found or does not belong to your school.");
    }
}

// Fetch siswa already registered in the kelastahun
$query_siswa_terdaftar = "SELECT nis FROM kelastahunsiswa WHERE IDKELASTAHUN = ?";
$stmt_siswa_terdaftar = mysqli_prepare($conn, $query_siswa_terdaftar);
mysqli_stmt_bind_param($stmt_siswa_terdaftar, 's', $idkelastahun);
mysqli_stmt_execute($stmt_siswa_terdaftar);
$result_siswa_terdaftar = mysqli_stmt_get_result($stmt_siswa_terdaftar);

$nis_terdaftar = [];
if ($result_siswa_terdaftar) {
    $nis_terdaftar = array_column(mysqli_fetch_all($result_siswa_terdaftar, MYSQLI_ASSOC), 'nis');
}

// Query kelas data (if admin, fetch all; if not admin, filter by idsekolah)
if ($username == 'admin') {
    $query_kelas = "SELECT idkelas, namakelas FROM kelas";
} else {
    $query_kelas = "SELECT idkelas, namakelas FROM kelas WHERE idsekolah = ?";
}

$stmt_kelas = mysqli_prepare($conn, $query_kelas);
if ($username != 'admin') {
    mysqli_stmt_bind_param($stmt_kelas, 's', $idsekolah);
}
mysqli_stmt_execute($stmt_kelas);
$result_kelas = mysqli_stmt_get_result($stmt_kelas);

if (!$result_kelas) {
    echo "Error fetching data from kelas table: " . mysqli_error($conn);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $idkelastahun = $_POST['idkelastahun'] ?? null;
    $username = $_SESSION['username'];
    $now = date('Y-m-d H:i:s');
    $tahunajaran = mysqli_real_escape_string($conn, $_POST['tahunajaran']);
    $idkelas = mysqli_real_escape_string($conn, $_POST['idkelas']);
    $nip = mysqli_real_escape_string($conn, $_POST['nip']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $nis_list = isset($_POST['nis']) ? (is_array($_POST['nis']) ? $_POST['nis'] : [$_POST['nis']]) : [];
    $error_message = ""; // Initialize error message

    // Check if the combination of idkelas and tahunajaran already exists (excluding current idkelastahun)
    $sql_check_combination = "
        SELECT COUNT(*) AS count
        FROM kelastahun
        WHERE IDKELAS = '$idkelas' AND TAHUNAJARAN = '$tahunajaran' AND IDSEKOLAH = '$idsekolah' AND IDKELASTAHUN != '$idkelastahun'";
    $result_check_combination = mysqli_query($conn, $sql_check_combination);

    if ($result_check_combination) {
        $row_check = mysqli_fetch_assoc($result_check_combination);
        if ($row_check['count'] > 0) {
            $error_message = "Tahun ajaran untuk kelas ini sudah ada";
        }
    } else {
        $error_message = "Error checking tahun ajaran dan kelas: " . mysqli_error($conn);
    }

    // Display modal if error occurs
    if (!empty($error_message)) {
        echo "<script>document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('errorModal').style.display = 'block';
            document.getElementById('errorMessage').innerHTML = '$error_message';
        });</script>";
    } else {
        if (empty($nis_list)) {
            $error_message = "Data siswa tidak ada yang dipilih.";
            echo "<script>document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('errorModal').style.display = 'block';
                document.getElementById('errorMessage').innerHTML = '$error_message';
            });</script>";
            exit;
        } else {
       // 1. Update kelastahun
$sql_kelastahun = "
UPDATE kelastahun
SET TAHUNAJARAN = '$tahunajaran', IDKELAS = '$idkelas', USERMODIFIED = '$username', DATEMODIFIED = '$now', STATUS = '$status', NIP = '$nip'
WHERE IDKELASTAHUN = '$idkelastahun' AND IDSEKOLAH = '$idsekolah'";

if (mysqli_query($conn, $sql_kelastahun)) {
    // 2. Delete students who are no longer selected
    if (!empty($nis_list)) {
        // Escaping and preparing NIS list for the query
        $nis_list_placeholders = implode("','", array_map(function($nis) use ($conn) { 
            return mysqli_real_escape_string($conn, $nis); 
        }, $nis_list));

        // Delete students whose NIS is not in the selected list
        $sql_delete_siswa = "
            DELETE FROM kelastahunsiswa
            WHERE IDKELASTAHUN = '$idkelastahun' 
              AND IDSEKOLAH = '$idsekolah' 
              AND NIS NOT IN ('$nis_list_placeholders')";
        
        if (!mysqli_query($conn, $sql_delete_siswa)) {
            $error_message = "Error deleting students: " . mysqli_error($conn);
            echo "<script>document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('errorModal').style.display = 'block';
                document.getElementById('errorMessage').innerHTML = '".addslashes($error_message)."';
            });</script>";
            exit;
        }
    }

    // 3. Insert new students
    $values = [];
    foreach ($nis_list as $nis) {
        $nis_clean = mysqli_real_escape_string($conn, $nis);
        $values[] = "('$idkelastahun', '$nis_clean', '$now', '$now', '$username', '$username', '$idsekolah')";
    }

    if (!empty($values)) {
        $sql_insert_siswa = "
            INSERT IGNORE INTO kelastahunsiswa (IDKELASTAHUN, NIS, DATERECORD, DATEMODIFIED, USERRECORD, USERMODIFIED, IDSEKOLAH)
            VALUES " . implode(", ", $values);

        if (!mysqli_query($conn, $sql_insert_siswa)) {
            $error_message = "Error inserting students: " . mysqli_error($conn);
            echo "<script>document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('errorModal').style.display = 'block';
                document.getElementById('errorMessage').innerHTML = '".addslashes($error_message)."';
            });</script>";
            exit;
        }
    }

    // Redirect after successful operation
    header('Location: list_kelastahun.php');
    exit;
} else {
    $error_message = "Error updating kelastahun: " . mysqli_error($conn);
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('errorModal').style.display = 'block';
        document.getElementById('errorMessage').innerHTML = '".addslashes($error_message)."';
    });</script>";
}
 }
}
}

// Fetch guru data (if admin, fetch all but exclude admin; if not admin, filter by idsekolah and status = 'Bekerja')
if ($username == 'admin') {
    // Exclude the admin from the result, select where void = 0 and status = 'Bekerja'
    $query_guru = "SELECT nip, nama FROM guru WHERE nama != 'admin' AND status = 'Bekerja' AND void = 0";
} else {
    // If not admin, filter by idsekolah, status = 'Bekerja', and void = 0
    $query_guru = "SELECT nip, nama FROM guru WHERE idsekolah = ? AND status = 'Bekerja' AND void = 0";
}

$stmt_guru = mysqli_prepare($conn, $query_guru);
if ($username != 'admin') {
    mysqli_stmt_bind_param($stmt_guru, 's', $idsekolah);
}
mysqli_stmt_execute($stmt_guru);
$result_guru = mysqli_stmt_get_result($stmt_guru);

if (!$result_guru) {
    echo "Error fetching data from guru table: " . mysqli_error($conn);
    exit;
}

// Fetch siswa data (if admin, fetch all; if not admin, filter by idsekolah)
if ($username == 'admin') {
    // Fetch all siswa where void = 0
    $query_siswa = "SELECT nis, nama FROM siswa WHERE void = 0";
} else {
    // Fetch siswa where idsekolah matches and void = 0
    $query_siswa = "SELECT nis, nama FROM siswa WHERE idsekolah = ? AND void = 0";
}

$stmt_siswa = mysqli_prepare($conn, $query_siswa);
if ($username != 'admin') {
    mysqli_stmt_bind_param($stmt_siswa, 's', $idsekolah);
}
mysqli_stmt_execute($stmt_siswa);
$result_siswa = mysqli_stmt_get_result($stmt_siswa);

if (!$result_siswa) {
    echo "Error fetching data from siswa table: " . mysqli_error($conn);
    exit;
}

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
    <title>Edit Kelas Tahun</title>
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
            <h3 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Edit Kelas tahun</b></h3>
        </div>
    </div>

<form method="POST" action="" class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">    
<label for="tahunajaran">Tahun Ajaran</label>
<select name="tahunajaran" id="tahunajaran" class="w3-select w3-light-grey" onchange="checkChanges()" required>
    <option value="" disabled>Pilih Tahun Ajaran</option>
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

    // Ambil nilai tahun ajaran yang ada di database jika ada
    $selectedTahunAjaran = isset($row_kelastahun['tahunajaran']) ? $row_kelastahun['tahunajaran'] : '';

    // Generate options untuk select
    foreach ($tahunAjaranOptions as $tahunAjaran) {
        // Menambahkan atribut 'selected' jika nilai tahun ajaran sesuai dengan nilai yang ada di database
        $selected = ($selectedTahunAjaran == $tahunAjaran) ? 'selected' : '';
        echo "<option value='$tahunAjaran' $selected>$tahunAjaran</option>";
    }
    ?>
</select>


    <label for="idkelas">Kelas</label>
    <select name="idkelas" id="idkelas" class="w3-select w3-light-grey" onchange="checkChanges()" required>
        <option value="" disabled>Pilih Kelas</option>
        <?php while ($row = mysqli_fetch_assoc($result_kelas)) { ?>
            <option value="<?php echo $row['idkelas']; ?>" 
                    <?php echo (isset($row_kelastahun['idkelas']) && $row['idkelas'] == $row_kelastahun['idkelas']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($row['namakelas']); ?>
            </option>
        <?php } ?>
    </select>

    <label for="nip">Wali Kelas</label>
    <select name="nip" id="nip" class="w3-select w3-light-grey" onchange="checkChanges()" required>
        <option value="" disabled>Pilih Wali Kelas</option>
        <?php while ($row = mysqli_fetch_assoc($result_guru)) { ?>
            <option value="<?php echo $row['nip']; ?>" 
                    <?php echo (isset($row_kelastahun['nip']) && $row['nip'] == $row_kelastahun['nip']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($row['nama']); ?>
            </option>
        <?php } ?>
    </select>

    <label for="status">Status</label>
<select name="status" id="status" class="w3-select w3-light-grey" onchange="checkChanges()" required>
    <option value="" disabled>Pilih Status</option>
    <option value="1" <?php echo (isset($status) && $status == '1') ? 'selected' : ''; ?>>Aktif</option>
    <option value="0" <?php echo (isset($status) && $status == '0') ? 'selected' : ''; ?>>Tidak Aktif</option>
</select>


    <!-- Label and Button to Open Modal -->
<label for="nis">Siswa</label> <br> 
<button type="button" id="openModal" class="w3-btn w3-red w3-round-large">Pilih</button> <br>

<!-- Hidden Field to Store Selected NIS -->
<input type="hidden" id="hiddenNis" name="nis" value="">

<!-- Modal -->
<div id="siswaModal" class="w3-modal">
    <div class="w3-modal-content w3-animate-top w3-card-4">
        <header class="w3-container w3-red">
            <span id="closeModalHeader" class="w3-btn w3-display-topright w3-large w3-text-white">&times;</span>
            <h2 class="w3-center"><b>Pilih Siswa</b></h2>
        </header>
        <div class="w3-container">
            <?php while ($row = mysqli_fetch_assoc($result_siswa)) { ?>
                <label class="w3-large">
                    <input class="w3-check" type="checkbox" name="nis[]" value="<?php echo $row['nis']; ?>" 
                        <?php echo in_array($row['nis'], $nis_terdaftar) ? 'checked' : ''; ?> 
                        onchange="checkChanges()">
                    <?php echo $row['nama']; ?>
                </label><br>
            <?php } ?>
        </div>
        <footer class="w3-container w3-padding-large">
            <button type="button" class="w3-btn w3-round-large w3-red w3-right" id="submitSiswa">Pilih</button>
            <button type="button" class="w3-btn w3-round-large w3-brown w3-right w3-margin-right" id="closeModalFooter">Tutup</button>
        </footer>
    </div>
</div>

    <label>User Modified</label>
    <input type="text" id="usermodified" name="usermodified" class="w3-input w3-border w3-light-grey"
        value="<?php echo htmlspecialchars($row_kelastahun['usermodified']); ?>" readonly><br>
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="idkelastahun" value="<?php echo $idkelastahun; ?>">

    <div class="w3-half">
        <a href="list_kelastahun.php" class="w3-button w3-container w3-orange w3-padding-16" style="width: 100%;">Kembali</a>
    </div>
    <div class="w3-half">
        <input type="submit" value="Simpan" class="w3-button w3-container w3-green w3-padding-16" style="width: 100%;" disabled>
    </div>
</form>

<!-- Error Modal -->
<div id="errorModal" class="w3-modal" style="display:none;" onclick="closeModal(event)">
    <div class="w3-modal-content w3-animate-top w3-card-4" onclick="event.stopPropagation()">
        <header class="w3-container w3-red">
            <span onclick="document.getElementById('errorModal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
            <h2>Informasi</h2>
        </header>
        <div class="w3-container">
            <p><?php echo htmlspecialchars($error_message ?? ""); ?></p>
        </div>
    </div>
</div>

<script>
  // Open the modal when the "Pilih" button is clicked
document.getElementById('openModal').addEventListener('click', function () {
    document.getElementById('siswaModal').style.display = 'block';
});

// Close the modal when the "x" button in the header is clicked
document.getElementById('closeModalHeader').addEventListener('click', function () {
    document.getElementById('siswaModal').style.display = 'none';
});

// Close the modal when the "Tutup" button in the footer is clicked
document.getElementById('closeModalFooter').addEventListener('click', function () {
    document.getElementById('siswaModal').style.display = 'none';
});

// When the "Pilih" button is clicked, save the selected nis values into the hidden input
document.getElementById('submitSiswa').addEventListener('click', function () {
    let selectedNis = [];
    
    // Collect all checked checkboxes and store their values in the selectedNis array
    document.querySelectorAll('#siswaModal input[name="nis[]"]:checked').forEach(function (checkbox) {
        selectedNis.push(checkbox.value);
    });
    
    // Join selected NIS values with commas and set it as the value of the hidden input field
    document.getElementById('hiddenNis').value = selectedNis.join(',');
    
    // Close the modal after selection
    document.getElementById('siswaModal').style.display = 'none';
});

// Function to handle any other changes (if needed)
function checkChanges() {
    // You can implement any additional functionality if needed when checkboxes change
}

 // Function to close modal if clicked outside of the modal content
 function closeModal(event) {
        if (event.target == document.getElementById('errorModal')) {
            document.getElementById('errorModal').style.display = 'none';
        }
    }

    // If there's an error message, show the modal
    <?php if (!empty($error_message)): ?>
        document.getElementById('errorModal').style.display = 'block';
    <?php endif; ?>

function w3_open() {
                document.getElementById("mySidebar").classList.add('show');
            document.getElementById("sidebarOverlay").classList.add('show');
        }

            function w3_close() {
                document.getElementById("mySidebar").classList.remove('show');
            document.getElementById("sidebarOverlay").classList.remove('show');
        }

// Function to enable/disable submit button based on input changes and NIS selection
function checkChanges() {
    // Ambil nilai asli dari form
    var originalValues = {
        tahunajaran: "<?php echo isset($row_kelastahun['tahunajaran']) ? htmlspecialchars($row_kelastahun['tahunajaran']) : ''; ?>",
        idkelas: "<?php echo isset($row_kelastahun['idkelas']) ? htmlspecialchars($row_kelastahun['idkelas']) : ''; ?>",
        nip: "<?php echo isset($row_kelastahun['nip']) ? htmlspecialchars($row_kelastahun['nip']) : ''; ?>",
        status: "<?php echo isset($status) ? htmlspecialchars($status) : ''; ?>",
        nis: "<?php echo implode(',', $nis_terdaftar); ?>"
    };

    // Ambil nilai saat ini dari form
    var currentValues = {
        tahunajaran: document.getElementById('tahunajaran').value,
        idkelas: document.getElementById('idkelas').value,
        nip: document.getElementById('nip').value,
        status: document.getElementById('status').value,
        nis: Array.from(document.querySelectorAll('input[name="nis[]"]:checked'))
            .map(function (checkbox) {
                return checkbox.value;
            })
            .join(',')
    };

    var updateButton = document.querySelector('input[type="submit"]');

    // Cek apakah ada perubahan pada form
    var anyChanges = (
        currentValues.tahunajaran !== originalValues.tahunajaran ||
        currentValues.idkelas !== originalValues.idkelas ||
        currentValues.nip !== originalValues.nip ||
        currentValues.status !== originalValues.status ||
        currentValues.nis !== originalValues.nis
    );

    // Disable submit button if no changes and no NIS selected
    if (!anyChanges || currentValues.nis === '') {
        updateButton.disabled = true; // Disable submit button if no changes
    } else {
        updateButton.disabled = false; // Enable submit button if there are changes
    }
}

// Memanggil fungsi checkChanges() saat halaman pertama kali dimuat untuk memeriksa status awal
window.onload = function() {
    checkChanges();
};
</script>
</body>
</html>


<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "skullman";

// Membuat koneksi ke database
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Function untuk mengecek akses halaman
function PerbolehkanAkses($Paramlistakses, $ParamAkses)
{
    return strpos($Paramlistakses, $ParamAkses) !== false;
}
?>

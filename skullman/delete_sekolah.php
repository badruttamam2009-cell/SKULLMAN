<?php
include('koneksi.php');

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['id'])) {
    $idsekolah = $_GET['id'];

    // Ambil nilai STATUS saat ini
    $sqlSelect = "SELECT STATUS FROM sekolah WHERE IDSEKOLAH='$idsekolah'";
    $result = $conn->query($sqlSelect);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentStatus = $row['STATUS'];

        // Jika STATUS saat ini adalah "Aktif", ubah menjadi "Tidak Aktif"
        $newStatus = ($currentStatus === "Aktif") ? "Tidak Aktif" : $currentStatus;

        // Update VOID dan STATUS
        $sqlUpdate = "UPDATE sekolah SET VOID = 1, STATUS = '$newStatus' WHERE IDSEKOLAH='$idsekolah'";

        if ($conn->query($sqlUpdate) === TRUE) {
            // Redirect ke list setelah berhasil
            header("Location: list_sekolah.php");
        } else {
            echo "Terjadi kesalahan saat menghapus data: " . $conn->error;
        }
    } else {
        echo "Data sekolah tidak ditemukan.";
    }
}

$conn->close();
?>
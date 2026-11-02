<?php
include 'koneksi.php';

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['NIP'])) {
    $NIP = $_GET['NIP'];

    // Menggunakan prepared statement untuk mengupdate kolom VOID
    $stmt = $conn->prepare("UPDATE guru SET VOID = 1 WHERE NIP = ?");
    $stmt->bind_param("s", $NIP);

    if ($stmt->execute()) {
        // Redirect kembali ke halaman list_guru.php setelah berhasil menghapus
        header("Location: list_guru.php");
        exit;
    } else {
        echo "Terjadi kesalahan saat menghapus data: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
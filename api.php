<?php
header("Content-Type: application/json");
// Koneksi ke database kamu [cite: 38]
$conn = new mysqli("localhost", "root", "", "db_penjualan_toko");

// 1. FITUR SIMPAN & UPDATE (POST) [cite: 500, 266]
if (isset($_POST['nama'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];

    if ($id == "") {
        // Logika Input Baru [cite: 499]
        $conn->query("INSERT INTO pelanggan (nama_pelanggan, email) VALUES ('$nama', '$email')");
    } else {
        // Logika Update Data [cite: 265]
        $conn->query("UPDATE pelanggan SET nama_pelanggan='$nama', email='$email' WHERE id_pelanggan=$id");
    }
    echo json_encode(["status" => "success"]);
    exit;
}

// 2. FITUR DELETE (GET) [cite: 380]
if (isset($_GET['hapus_id'])) {
    $id = $_GET['hapus_id'];
    $conn->query("DELETE FROM pelanggan WHERE id_pelanggan = $id");
    echo json_encode(["status" => "success"]);
    exit;
}

// 3. AMBIL SEMUA DATA (READ) [cite: 62, 123]
$pelanggan = $conn->query("SELECT * FROM pelanggan ORDER BY id_pelanggan DESC")->fetch_all(MYSQLI_ASSOC);
$produk = $conn->query("SELECT * FROM produk")->fetch_all(MYSQLI_ASSOC);

echo json_encode(["pelanggan" => $pelanggan, "produk" => $produk]);
?>
<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Ditambah Authorization di sini

// Koneksi ke database 
$conn = new mysqli("localhost", "root", "", "db_penjualan_toko");

// --- PASANG GEMBOK DI SINI ---
require_once "cek_token.php";
validasiToken($conn);
// -----------------------------

if ($conn->connect_error) {
    echo json_encode(["status" => false, "message" => "Koneksi database gagal"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // 1. Hitung Total Pendapatan Toko
        $q_pendapatan = "SELECT SUM(subtotal) as total_pendapatan FROM detail_transaksi";
        $res_pendapatan = $conn->query($q_pendapatan)->fetch_assoc();
        $total_pendapatan = $res_pendapatan['total_pendapatan'] ?? 0;

        // 2. Hitung Total Transaksi yang Terjadi
        $q_transaksi = "SELECT COUNT(id_transaksi) as total_transaksi FROM transaksi";
        $res_transaksi = $conn->query($q_transaksi)->fetch_assoc();
        $total_transaksi = $res_transaksi['total_transaksi'] ?? 0;

        // 3. Cari Produk yang Paling Laris (Paling Banyak Dibeli)
        $q_terlaris = "SELECT pr.nama_produk, SUM(d.jumlah) as total_terjual 
                       FROM detail_transaksi d
                       JOIN produk pr ON d.id_produk = pr.id_produk
                       GROUP BY d.id_produk
                       ORDER BY total_terjual DESC
                       LIMIT 1";
        $res_terlaris = $conn->query($q_terlaris)->fetch_assoc();
        $produk_terlaris = $res_terlaris ? $res_terlaris['nama_produk'] : "Belum ada transaksi";
        $jumlah_terjual = $res_terlaris ? $res_terlaris['total_terjual'] : 0;

        // 4. Hitung Jumlah Pelanggan Terdaftar
        $q_pelanggan = "SELECT COUNT(id_pelanggan) as total_pelanggan FROM pelanggan";
        $res_pelanggan = $conn->query($q_pelanggan)->fetch_assoc();
        $total_pelanggan = $res_pelanggan['total_pelanggan'] ?? 0;

        // Kirimkan data statistik dalam bentuk JSON beneran
        echo json_encode([
            "status" => true,
            "data" => [
                "total_pendapatan" => intval($total_pendapatan),
                "total_transaksi" => intval($total_transaksi),
                "total_pelanggan" => intval($total_pelanggan),
                "produk_terlaris" => [
                    "nama_produk" => $produk_terlaris,
                    "total_terjual" => intval($jumlah_terjual)
                ]
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Gagal memuat statistik: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => false, "message" => "Metode HTTP tidak didukung. Gunakan GET."]);
}
?>
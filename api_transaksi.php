<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Koneksi ke database kamu
$conn = new mysqli("localhost", "root", "", "db_penjualan_toko");

if ($conn->connect_error) {
    echo json_encode(["status" => false, "message" => "Koneksi database gagal"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // 1. READ TRANSAKSI (Melihat Riwayat Transaksi Lengkap dengan Detailnya)
        $query = "SELECT t.id_transaksi, t.tanggal_transaksi, p.nama_pelanggan, 
                         d.id_detail, pr.nama_produk, d.jumlah, d.subtotal
                  FROM transaksi t
                  JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
                  JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
                  JOIN produk pr ON d.id_produk = pr.id_produk
                  ORDER BY t.id_transaksi DESC";
                  
        $result = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        echo json_encode(["status" => true, "data" => $result]);
        break;

    case 'POST':
        // 2. CREATE TRANSAKSI (Proses Pembelian Baru)
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id_pelanggan = $input['id_pelanggan'];
        $tanggal = date('Y-m-d H:i:s');
        $items = $input['items']; // List produk yang dibeli (berupa array)

        // Mulai database transaction agar kalau salah satu error, semua dibatalkan (aman)
        $conn->begin_transaction();

        try {
            // Masukkan data ke tabel induk: transaksi
            $query_transaksi = "INSERT INTO transaksi (id_pelanggan, tanggal_transaksi) VALUES ($id_pelanggan, '$tanggal')";
            $conn->query($query_transaksi);
            
            // Ambil ID transaksi yang barusan tercipta
            $id_transaksi_baru = $conn->insert_id;

            // Looping untuk memasukkan semua produk yang dibeli ke tabel detail_transaksi
            foreach ($items as $item) {
                $id_produk = $item['id_produk'];
                $jumlah = $item['jumlah'];
                
                // Ambil harga produk dari tabel produk untuk menghitung subtotal
                $res_produk = $conn->query("SELECT harga, stok FROM produk WHERE id_produk = $id_produk")->fetch_assoc();
                $harga_satuan = $res_produk['harga'];
                $stok_sekarang = $res_produk['stok'];
                
                // Cek apakah stok cukup
                if ($stok_sekarang < $jumlah) {
                    throw new Exception("Stok produk ID $id_produk tidak mencukupi!");
                }
                
                $subtotal = $harga_satuan * $jumlah;

                // Insert ke tabel detail_transaksi
                $query_detail = "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, subtotal) 
                                 VALUES ($id_transaksi_baru, $id_produk, $jumlah, $subtotal)";
                $conn->query($query_detail);

                // Potong stok produk di tabel produk
                $query_update_stok = "UPDATE produk SET stok = stok - $jumlah WHERE id_produk = $id_produk";
                $conn->query($query_update_stok);
            }

            // Jika semua query sukses, simpan permanen ke database
            $conn->commit();
            echo json_encode(["status" => true, "message" => "Transaksi sukses dibuat dengan ID #" . $id_transaksi_baru]);

        } catch (Exception $e) {
            // Jika ada yang gagal/stok kurang, batalkan semua proses insert
            $conn->rollback();
            echo json_encode(["status" => false, "message" => "Transaksi gagal: " . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(["status" => false, "message" => "Metode HTTP tidak didukung"]);
        break;
}
?>
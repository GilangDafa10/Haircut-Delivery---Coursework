<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
requireRole('Admin');

if (isset($_POST['add_products'])) {
    // Ambil dan filter data
    $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['Kategori']);
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // ==== GENERATE KODE PRODUK ====
    $result = mysqli_query($koneksi, "SELECT id_produk FROM produk ORDER BY id_produk DESC LIMIT 1");
    $data = mysqli_fetch_assoc($result);

    if ($data) {
        $lastNumber = (int)substr($data['id_produk'], 1); // Misal: "P005" → 5
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }

    $id_produk = 'P' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

    // ==== PROSES UPLOAD GAMBAR ====
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $gambar = $_FILES['gambar']['name'];
        $tmp = $_FILES['gambar']['tmp_name'];
        $folder = "../data_gambar/";
        $ext = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            // Rename gambar agar unik
            $nama_gambar = uniqid('img_', true) . '.' . $ext;
            $path_gambar = $folder . $nama_gambar;

            if (move_uploaded_file($tmp, $path_gambar)) {
                // ==== SIMPAN KE DATABASE ====
                $query = "INSERT INTO produk (id_produk, nama_produk, kategori, harga, stok, gambar, deskripsi)
                          VALUES ('$id_produk', '$nama_produk', '$kategori', $harga, $stok, '$nama_gambar', '$deskripsi')";

                if (mysqli_query($koneksi, $query)) {
                    echo "<script>alert('Produk berhasil ditambahkan!'); window.location='../data_produk.php';</script>";
                } else {
                    echo "Gagal menyimpan ke database: " . mysqli_error($koneksi);
                }
            } else {
                echo "Gagal mengunggah gambar.";
            }
        } else {
            echo "Format gambar tidak valid. Gunakan JPG, JPEG, PNG, GIF, atau WEBP.";
        }
    } else {
        echo "Gambar belum diunggah atau terjadi kesalahan.";
    }
} else {
    echo "Form tidak dikirim.";
}

// ===== EDIT PRODUK =====
if (isset($_POST['edit_product'])) {
    $id_produk = mysqli_real_escape_string($koneksi, $_POST['id_produk']);
    $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['Kategori']);
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // Ambil data produk lama (untuk cek gambar lama)
    $result = mysqli_query($koneksi, "SELECT gambar FROM produk WHERE id_produk = '$id_produk'");
    $data_lama = mysqli_fetch_assoc($result);
    $gambar_lama = $data_lama['gambar'];

    // Proses upload gambar baru (jika ada)
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $gambar = $_FILES['gambar']['name'];
        $tmp = $_FILES['gambar']['tmp_name'];
        $folder = "../data_gambar/";
        $ext = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            // Rename gambar agar unik
            $nama_gambar_baru = uniqid('img_', true) . '.' . $ext;
            $path_gambar_baru = $folder . $nama_gambar_baru;

            if (move_uploaded_file($tmp, $path_gambar_baru)) {
                // Hapus gambar lama jika ada
                if ($gambar_lama && file_exists($folder . $gambar_lama)) {
                    unlink($folder . $gambar_lama);
                }
                $query = "UPDATE layanan 
                          SET nama_layanan='$nama_layanan', kategori='$kategori', harga=$harga, stok=$stok, gambar='$nama_gambar_baru', deskripsi='$deskripsi' 
                          WHERE id_produk='$id_produk'";
            } else {
                echo "Gagal mengunggah gambar baru.";
                exit;
            }
        } else {
            echo "Format gambar tidak valid.";
            exit;
        }
    } else {
        // Tidak ada gambar baru → tetap gunakan gambar lama
        $query = "UPDATE produk 
                  SET nama_produk='$nama_produk', kategori='$kategori', harga=$harga, stok=$stok, deskripsi='$deskripsi' 
                  WHERE id_produk='$id_produk'";
    }

    // Eksekusi query update
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Produk berhasil diperbarui!'); window.location='../data_produk.php';</script>";
    } else {
        echo "Gagal memperbarui produk: " . mysqli_error($koneksi);
    }
}

// ===== HAPUS PRODUK =====
if (isset($_GET['hapus_produk'])) {
    $id_produk = mysqli_real_escape_string($koneksi, $_GET['hapus_produk']);

    // Ambil data produk untuk cek gambar
    $result = mysqli_query($koneksi, "SELECT gambar FROM produk WHERE id_produk = '$id_produk'");
    $data = mysqli_fetch_assoc($result);
    $gambar = $data['gambar'];
    $folder = "../data_gambar/";

    // Hapus gambar dari folder (jika ada)
    if ($gambar && file_exists($folder . $gambar)) {
        unlink($folder . $gambar);
    }

    // Hapus data dari database
    $hapus = mysqli_query($koneksi, "DELETE FROM produk WHERE id_produk = '$id_produk'");

    if ($hapus) {
        echo "<script>alert('Produk berhasil dihapus.'); window.location='../data_produk.php';</script>";
    } else {
        echo "Gagal menghapus produk: " . mysqli_error($koneksi);
    }
}
?>

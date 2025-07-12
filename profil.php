<?php
$page_title = 'Profil Pengguna';
require 'config/database.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Logika untuk menangani unggahan file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_profil'])) {
    
    $foto = $_FILES['foto_profil'];

    // 1. Cek Error Unggahan Dasar
    if ($foto['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Terjadi kesalahan saat mengunggah file. Silakan coba lagi.";
    } else {
        // 2. Validasi Ukuran File (misal: maks 2MB)
        $max_size = 2 * 1024 * 1024; // 2 MB
        if ($foto['size'] > $max_size) {
            $error_message = "Ukuran file terlalu besar. Maksimal 2MB.";
        } else {
            // 3. Validasi Tipe File (MIME Type) - Ini bagian sanitasi terpenting!
            $allowed_types = ['image/jpeg', 'image/png'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $foto['tmp_name']);
            finfo_close($file_info);

            if (!in_array($mime_type, $allowed_types)) {
                $error_message = "Tipe file tidak valid. Hanya gambar JPEG dan PNG yang diizinkan.";
            } else {
                // Jika semua validasi lolos, proses ke database
                try {
                    // Baca konten file sebagai data biner
                    $image_content = file_get_contents($foto['tmp_name']);

                    // Gunakan prepared statement untuk menyimpan BLOB dengan aman
                    $sql = "UPDATE users SET foto_profil = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    
                    // PDO::PARAM_LOB digunakan untuk data biner (Large Object)
                    $stmt->bindParam(1, $image_content, PDO::PARAM_LOB);
                    $stmt->bindParam(2, $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Foto profil berhasil diperbarui!";
                    } else {
                        throw new Exception("Gagal menyimpan data ke database.");
                    }

                } catch (Exception $e) {
                    $error_message = "Terjadi kesalahan pada server: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<h1 class="h3 mb-4 text-gray-800">Profil Pengguna</h1>

<?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Foto Profil Saat Ini</h6>
            </div>
            <div class="card-body text-center">
                <img class="img-fluid rounded-circle" 
                     src="get_profile_pic.php?id=<?php echo $user_id; ?>&t=<?php echo time(); ?>" 
                     alt="Foto Profil" 
                     style="width: 150px; height: 150px; object-fit: cover;">
                <p class="mt-3 mb-0"><strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong></p>
                <p><small><?php echo htmlspecialchars(ucfirst($_SESSION['role'])); ?></small></p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Ganti Foto Profil</h6>
            </div>
            <div class="card-body">
                <form action="profil.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="foto_profil">Pilih File Gambar (JPG/PNG, maks 2MB)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="foto_profil" name="foto_profil" accept="image/jpeg, image/png" required>
                            <label class="custom-file-label" for="foto_profil">Pilih file...</label>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">Unggah Foto Baru</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = document.getElementById("foto_profil").files[0].name;
    var nextSibling = e.target.nextElementSibling;
    nextSibling.innerText = fileName;
});
</script>

<?php include 'includes/footer.php'; ?>
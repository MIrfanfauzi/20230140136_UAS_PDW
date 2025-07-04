<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Daftar Praktikum';
$activePage = 'katalog';

$header_path = __DIR__ . '/templates/header_mahasiswa.php';
$footer_path = __DIR__ . '/templates/footer_mahasiswa.php';

require_once __DIR__ . '/../config.php';

$message = '';

// Handle pendaftaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['daftar'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
        $message = "Anda harus login sebagai mahasiswa untuk mendaftar praktikum.";
    } else {
        $mahasiswa_id = $_SESSION['user_id'];
        $praktikum_id = $_POST['praktikum_id'];

        $sql_check = "SELECT id FROM pendaftaran_praktikum WHERE mahasiswa_id = ? AND praktikum_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $mahasiswa_id, $praktikum_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "Anda sudah terdaftar pada praktikum ini.";
        } else {
            $sql_insert = "INSERT INTO pendaftaran_praktikum (mahasiswa_id, praktikum_id) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $mahasiswa_id, $praktikum_id);
            $stmt_insert->execute();
            $message = $stmt_insert->affected_rows > 0 ? "Berhasil mendaftar praktikum!" : "Gagal mendaftar. Coba lagi.";
        }
    }
}

// Ambil semua mata praktikum
$sql = "SELECT id, nama_praktikum, deskripsi, created_at FROM mata_praktikum ORDER BY created_at DESC";
$result = $conn->query($sql);

// Ambil data praktikum yang sudah diikuti oleh mahasiswa
$praktikum_diikuti = [];
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa') {
    $mahasiswa_id = $_SESSION['user_id'];
    $sql_diikuti = "SELECT praktikum_id FROM pendaftaran_praktikum WHERE mahasiswa_id = ?";
    $stmt_diikuti = $conn->prepare($sql_diikuti);
    $stmt_diikuti->bind_param("i", $mahasiswa_id);
    $stmt_diikuti->execute();
    $result_diikuti = $stmt_diikuti->get_result();

    while ($row = $result_diikuti->fetch_assoc()) {
        $praktikum_diikuti[] = $row['praktikum_id'];
    }
}

if (file_exists($header_path)) {
    include_once $header_path;
} else {
    die("<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #fff0f0; border: 1px solid #ffbaba; color: #d8000c;'>
        <strong>Error:</strong> File <code>header_mahasiswa.php</code> tidak ditemukan.
    </div>");
}
?>

<!-- Judul Halaman -->
<div class="mb-8">
    <h2 class="text-3xl font-bold text-yellow-900 mb-2">📝 Daftar Mata Praktikum</h2>
    <p class="text-yellow-700">Silakan pilih mata praktikum yang ingin kamu ikuti.</p>
</div>

<!-- Notifikasi -->
<?php if (!empty($message)): ?>
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-6">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Daftar Praktikum -->
<?php if ($result->num_rows > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="bg-yellow-50 p-6 rounded-xl shadow hover:shadow-lg transition flex flex-col justify-between h-full">
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800 mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></h3>
                    <p class="text-yellow-700 mb-3"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                    <p class="text-sm text-yellow-500 mb-4">Dibuat pada: <?php echo date('d M Y', strtotime($row['created_at'])); ?></p>
                </div>
                <div>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa'): ?>
                        <?php if (in_array($row['id'], $praktikum_diikuti)): ?>
                            <button class="w-full bg-green-500 text-white py-2 px-4 rounded cursor-not-allowed" disabled>
                                Sudah Terdaftar
                            </button>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="praktikum_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="daftar" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded font-bold">
                                    Daftar Praktikum
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center">
                            <p class="text-yellow-600 mb-2">Login untuk mendaftar</p>
                            <a href="../login.php" class="text-yellow-700 hover:underline">Masuk Sekarang</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="text-center text-yellow-600 bg-yellow-50 p-6 rounded-xl shadow">
        <p>Belum ada mata praktikum yang tersedia.</p>
    </div>
<?php endif; ?>

<?php
$conn->close();

if (file_exists($footer_path)) {
    include_once $footer_path;
} else {
    die("<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #fff0f0; border: 1px solid #ffbaba; color: #d8000c;'>
        <strong>Error:</strong> File <code>footer_mahasiswa.php</code> tidak ditemukan.
    </div>");
}
?>

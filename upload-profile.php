<?php
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($email)) {
        echo json_encode(["status" => "error", "message" => "Email wajib diisi!"]);
        exit();
    }

    // 👇 TAMBAHAN: HANDLE HAPUS FOTO PROFIL
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $stmtGet = $conn->prepare("SELECT profile_image FROM users WHERE email = ?");
        $stmtGet->bind_param("s", $email);
        $stmtGet->execute();
        $res = $stmtGet->get_result();
        $row = $res->fetch_assoc();
        $stmtGet->close();

        if ($row && !empty($row['profile_image']) && file_exists($row['profile_image'])) {
            unlink($row['profile_image']);
        }

        $stmtDel = $conn->prepare("UPDATE users SET profile_image = NULL, updated_at = NOW() WHERE email = ?");
        $stmtDel->bind_param("s", $email);

        if ($stmtDel->execute()) {
            echo json_encode(["status" => "success", "message" => "Foto profil berhasil dihapus."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menghapus foto di database."]);
        }
        $stmtDel->close();
        $conn->close();
        exit();
    }
    // 👆 SAMPAI SINI TAMBAHAN

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFileName = "profile_" . time() . "_" . rand(1000, 9999) . "." . strtolower($fileExtension);
        $targetFilePath = $targetDir . $newFileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            // Update path gambar di database
            $stmt = $conn->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE email = ?");
            $stmt->bind_param("ss", $targetFilePath, $email);

            if ($stmt->execute()) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Foto profil berhasil diperbarui!",
                    "profile_image" => $targetFilePath
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Gagal memperbarui database."]);
            }
            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal mengunggah file ke server."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Tidak ada file gambar yang dikirim."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Metode request tidak valid."]);
}

$conn->close();
?>
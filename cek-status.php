<?php
require_once "db.php";

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (!empty($email)) {
    $stmt = $conn->prepare("SELECT id, username, email, profile_image FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "Sistem server & akun terverifikasi aktif!",
            "user" => $user
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Email tidak terdaftar di database."
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Server API Ambativasi Berjalan Normal & Database Terhubung!",
        "timestamp" => date("Y-m-d H:i:s")
    ]);
}

$conn->close();
?>
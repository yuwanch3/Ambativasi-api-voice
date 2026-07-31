<?php
require_once "db.php";

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 20;

// Ambil semua data user + XP, urutkan dari yang paling besar
$query = "SELECT u.id, u.username, u.email, u.profile_image,
                 COALESCE(x.total_xp, 0) AS total_xp,
                 COALESCE(x.attempts, 0) AS attempts
          FROM users u
          LEFT JOIN user_xp x ON x.user_id = u.id
          ORDER BY total_xp DESC, u.username ASC
          LIMIT ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$leaderboard = [];
$myEntry = null;
$myRank = null;

$rank = 0;
while ($row = $result->fetch_assoc()) {
    $rank++;
    $xp = intval($row["total_xp"]);
    $level = floor($xp / 500) + 1;

    $entry = [
        "rank" => $rank,
        "username" => $row["username"],
        "email" => $row["email"],
        "profile_image" => $row["profile_image"],
        "total_xp" => $xp,
        "level" => $level
    ];
    $leaderboard[] = $entry;

    if ($email !== '' && $row["email"] === $email) {
        $myEntry = $entry;
        $myRank = $rank;
    }
}
$stmt->close();

// Kalau user tidak masuk top list, cari rank sebenarnya dari XP-nya
if ($email !== '' && $myRank === null) {
    $stmt = $conn->prepare("SELECT u.id, u.username, u.email, u.profile_image,
                                   COALESCE(x.total_xp, 0) AS total_xp,
                                   COALESCE(x.attempts, 0) AS attempts
                            FROM users u
                            LEFT JOIN user_xp x ON x.user_id = u.id
                            WHERE u.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($myRow = $stmt->get_result()->fetch_assoc()) {
        $myXp = intval($myRow["total_xp"]);
        $myEntry = [
            "rank" => null,
            "username" => $myRow["username"],
            "email" => $myRow["email"],
            "profile_image" => $myRow["profile_image"],
            "total_xp" => $myXp,
            "level" => floor($myXp / 500) + 1
        ];
        $stmt->close();

        $countStmt = $conn->prepare("SELECT COUNT(*) AS higher FROM users u
                                     LEFT JOIN user_xp x ON x.user_id = u.id
                                     WHERE COALESCE(x.total_xp, 0) > ?");
        $countStmt->bind_param("i", $myXp);
        $countStmt->execute();
        $myRank = intval($countStmt->get_result()->fetch_assoc()["higher"]) + 1;
        $countStmt->close();
    } else {
        $stmt->close();
    }
}

echo json_encode([
    "status" => "success",
    "leaderboard" => $leaderboard,
    "me" => $myEntry ? array_merge($myEntry, ["rank" => $myRank]) : null
]);

$conn->close();

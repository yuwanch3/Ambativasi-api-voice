<?php
require_once "db.php";
require_once "env.php";

const GEMINI_API_KEY = getenv('GEMINI_API_KEY');

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["messages"]) || !is_array($input["messages"])) {
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Parameter 'messages' wajib dikirim."
    ]);
    exit();
}

$messages = $input["messages"];
$context = isset($input["context"]) ? $input["context"] : null;

$subject = isset($context["subject"]) ? $context["subject"] : "Halaman utama";
$topic = isset($context["topic"]) ? $context["topic"] : "";

$systemPrompt = "Kamu adalah asisten belajar pribadi untuk aplikasi Ambativasi.

Kamu membantu siswa memahami materi pelajaran dengan ramah dan sabar.
Gunakan bahasa Indonesia kecuali siswa bertanya dalam bahasa Inggris.
Jawab dengan singkat, jelas, dan berikan contoh jika relevan.
Jangan menjawab di luar topik pendidikan dan pembelajaran.

Siswa saat ini sedang membuka: {$subject}" . ($topic ? " - Topik: {$topic}" : "");

$geminiMessages = [];
foreach ($messages as $msg) {
    $role = ($msg["role"] === "assistant") ? "model" : "user";
    $geminiMessages[] = [
        "role" => $role,
        "parts" => [["text" => $msg["content"]]]
    ];
}

$payload = [
    "contents" => $geminiMessages,
    "systemInstruction" => [
        "parts" => [["text" => $systemPrompt]]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 1024
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent?key=" . GEMINI_API_KEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $err = json_decode($response, true);
    $errMsg = isset($err["error"]["message"]) ? $err["error"]["message"] : "Gagal terhubung ke Gemini API.";
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $errMsg
    ]);
    exit();
}

$data = json_decode($response, true);
$reply = $data["candidates"][0]["content"]["parts"][0]["text"];

echo json_encode([
    "success" => true,
    "status" => "success",
    "reply" => $reply
]);
?>
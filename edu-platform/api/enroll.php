<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'طريقة الطلب غير مسموحة']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$course = trim($data['course'] ?? '');
$student = trim($data['student'] ?? '');

if ($course === '' || $student === '') {
    echo json_encode(['status' => 'error', 'message' => 'بيانات التسجيل ناقصة']);
    exit;
}

$file = __DIR__ . '/../data/enrollments.json';
$enrollments = [];
if (file_exists($file)) {
    $enrollments = json_decode(file_get_contents($file), true) ?: [];
}

$enrollments[] = [
    'course' => htmlspecialchars($course, ENT_QUOTES, 'UTF-8'),
    'student' => htmlspecialchars($student, ENT_QUOTES, 'UTF-8'),
    'time' => date('Y-m-d H:i:s')
];

file_put_contents($file, json_encode($enrollments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode(['status' => 'success', 'message' => "تم تسجيلك في دورة: {$course}"]);

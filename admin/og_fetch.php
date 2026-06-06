<?php

require_once __DIR__ . '/image_system.php';

header('Content-Type: application/json');

if (!isset($_GET['img']) || !isset($_GET['title'])) {
    echo json_encode(['success' => false]);
    exit;
}

$url = $_GET['img'];
$title = $_GET['title'];

$file = nx_save_image_original($url, $title);

if ($file) {
    echo json_encode(['success' => true, 'file' => $file]);
} else {
    echo json_encode(['success' => false]);
}

<?php
/* Image upload used by the article editor. Returns JSON. */
require '_boot.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in()) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'ابتدا وارد شوید.'], JSON_UNESCAPED_UNICODE); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
    http_response_code(400); echo json_encode(['ok' => false, 'error' => 'درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE); exit;
}
echo json_encode(save_upload($_FILES['image'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

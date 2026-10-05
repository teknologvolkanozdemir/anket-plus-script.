<?php
require_once __DIR__ . '/../config.php';
session_start();

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Geçersiz istek (CSRF).'); }
}
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
}
function question_types(): array {
    return [
        'text' => 'Kısa metin', 'textarea' => 'Uzun metin', 'number' => 'Sayı', 'email' => 'E-posta',
        'date' => 'Tarih', 'radio' => 'Tek seçim (radyo)', 'select' => 'Açılır liste',
        'checkbox' => 'Çoklu seçim (onay kutusu)', 'rating' => 'Puan (1-5)',
    ];
}
function survey_url(string $token): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $dir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    if (basename(dirname($_SERVER['SCRIPT_NAME'])) !== 'admin') $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir . '/survey.php?t=' . $token;
}
function header_html(string $title, bool $nav = true): void { ?>
<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?></title><link rel="stylesheet" href="<?= $nav ? '../' : '' ?>assets/style.css"></head><body>
<?php if ($nav): ?><nav><a href="index.php"><b>Anket Yönetimi</b></a><a href="create.php">+ Yeni Anket</a>
<form method="post" action="logout.php" style="margin-left:auto"><?= csrf_field() ?><button class="link">Çıkış</button></form></nav><?php endif; ?>
<main><?php }
function footer_html(): void { echo '</main></body></html>'; }

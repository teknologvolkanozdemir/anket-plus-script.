<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $a = $st->fetch();
    if ($a && password_verify($_POST['password'] ?? '', $a['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $a['id'];
        header('Location: index.php'); exit;
    }
    $err = 'Kullanıcı adı veya şifre hatalı.';
}
header_html('Yönetici Girişi', false); ?>
<h2>Yönetici Girişi</h2><?php if ($err) echo '<p class="err">' . e($err) . '</p>'; ?>
<form method="post"><?= csrf_field() ?>
<label>Kullanıcı adı<input type="text" name="username" required></label>
<label>Şifre<input type="password" name="password" required></label>
<button>Giriş</button></form>
<?php footer_html();

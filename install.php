<?php
// Kurulum: tabloları oluşturur ve yönetici hesabı ekler. Kurulumdan sonra bu dosyayı silin.
require_once __DIR__ . '/includes/bootstrap.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = trim($_POST['username'] ?? ''); $p = $_POST['password'] ?? '';
    if ($u === '' || strlen($p) < 8) { $msg = 'Kullanıcı adı gerekli, şifre en az 8 karakter olmalı.'; }
    else {
        foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/schema.sql')))) as $sql) db()->exec($sql);
        if ((int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) { $msg = 'Kurulum zaten yapılmış. install.php dosyasını silin.'; }
        else {
            db()->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
            $msg = 'Kurulum tamamlandı. Lütfen install.php dosyasını silin ve admin/login.php adresinden giriş yapın.';
        }
    }
}
header_html('Kurulum', false); ?>
<h2>Kurulum</h2><?php if ($msg) echo '<p>' . e($msg) . '</p>'; ?>
<form method="post"><?= csrf_field() ?>
<label>Yönetici kullanıcı adı<input type="text" name="username" required></label>
<label>Şifre (min 8)<input type="password" name="password" required minlength="8"></label>
<button>Kur</button></form>
<?php footer_html();

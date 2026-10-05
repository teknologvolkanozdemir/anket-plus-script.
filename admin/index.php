<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') db()->prepare('DELETE FROM surveys WHERE id = ?')->execute([$id]);
    if (($_POST['action'] ?? '') === 'toggle') db()->prepare('UPDATE surveys SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    header('Location: index.php?page=' . max(1, (int)($_POST['page'] ?? 1))); exit;
}
$total = (int)db()->query('SELECT COUNT(*) FROM surveys')->fetchColumn();
$pages = max(1, (int)ceil($total / PER_PAGE));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$st = db()->prepare('SELECT s.*, (SELECT COUNT(*) FROM responses r WHERE r.survey_id = s.id) AS rc FROM surveys s ORDER BY s.id DESC LIMIT ' . PER_PAGE . ' OFFSET ' . (($page - 1) * PER_PAGE));
$st->execute();
$rows = $st->fetchAll();
header_html('Anketler'); ?>
<h2>Anketler (<?= $total ?>)</h2>
<?php if (!$rows) echo '<p>Henüz anket yok.</p>'; else { ?>
<table><tr><th>Başlık</th><th>Durum</th><th>Yanıt</th><th>İşlemler</th></tr>
<?php foreach ($rows as $r): $url = survey_url($r['token']); ?>
<tr><td><?= e($r['title']) ?></td><td><?= $r['is_active'] ? 'Açık' : 'Kapalı' ?></td><td><?= (int)$r['rc'] ?></td><td>
<button type="button" class="copy" data-url="<?= e($url) ?>">Bağlantıyı Kopyala</button>
<a class="btn gray" href="results.php?id=<?= (int)$r['id'] ?>">Sonuçlar</a>
<a class="btn gray" href="export.php?id=<?= (int)$r['id'] ?>">CSV</a>
<form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="page" value="<?= $page ?>">
<button name="action" value="toggle" class="gray"><?= $r['is_active'] ? 'Kapat' : 'Aç' ?></button>
<button name="action" value="delete" class="red" onclick="return confirm('Anket ve tüm yanıtları silinsin mi?')">Sil</button></form>
</td></tr><?php endforeach; ?></table><?php } ?>
<p class="pager"><?php for ($i = 1; $i <= $pages; $i++) echo '<a class="' . ($i === $page ? 'cur' : '') . '" href="?page=' . $i . '">' . $i . '</a>'; ?></p>
<script src="../assets/app.js"></script>
<?php footer_html();

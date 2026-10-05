<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM surveys WHERE id = ?'); $st->execute([$id]);
$s = $st->fetch();
if (!$s) { http_response_code(404); exit('Anket bulunamadı.'); }
$q = db()->prepare('SELECT * FROM questions WHERE survey_id = ? ORDER BY position, id'); $q->execute([$id]);
$questions = $q->fetchAll();
$total = (int)db()->query('SELECT COUNT(*) FROM responses WHERE survey_id = ' . $id)->fetchColumn();
$pages = max(1, (int)ceil($total / PER_PAGE));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$r = db()->prepare('SELECT * FROM responses WHERE survey_id = ? ORDER BY id DESC LIMIT ' . PER_PAGE . ' OFFSET ' . (($page - 1) * PER_PAGE));
$r->execute([$id]);
$resp = $r->fetchAll();
$ans = [];
if ($resp) {
    $ids = implode(',', array_map('intval', array_column($resp, 'id')));
    foreach (db()->query("SELECT response_id, question_id, value FROM answers WHERE response_id IN ($ids)") as $a) $ans[$a['response_id']][$a['question_id']] = $a['value'];
}
header_html('Sonuçlar'); ?>
<h2><?= e($s['title']) ?> – Sonuçlar (<?= $total ?>)</h2>
<p><a class="btn" href="export.php?id=<?= $id ?>">CSV indir</a> <a class="btn gray" href="index.php">Geri</a></p>
<div style="overflow:auto"><table><tr><th>#</th><th>Tarih</th><?php foreach ($questions as $qq) echo '<th>' . e($qq['label']) . '</th>'; ?></tr>
<?php foreach ($resp as $x): ?><tr><td><?= (int)$x['id'] ?></td><td><?= e($x['created_at']) ?></td>
<?php foreach ($questions as $qq) echo '<td>' . e($ans[$x['id']][$qq['id']] ?? '') . '</td>'; ?></tr><?php endforeach; ?></table></div>
<p class="pager"><?php for ($i = 1; $i <= $pages; $i++) echo '<a class="' . ($i === $page ? 'cur' : '') . '" href="?id=' . $id . '&page=' . $i . '">' . $i . '</a>'; ?></p>
<?php footer_html();

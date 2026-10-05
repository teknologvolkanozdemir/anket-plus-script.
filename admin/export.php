<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM surveys WHERE id = ?'); $st->execute([$id]);
$s = $st->fetch();
if (!$s) { http_response_code(404); exit('Anket bulunamadı.'); }
$q = db()->prepare('SELECT * FROM questions WHERE survey_id = ? ORDER BY position, id'); $q->execute([$id]);
$questions = $q->fetchAll();
// CSV enjeksiyonuna karşı formül karakterleriyle başlayan hücreleri etkisizleştir
function csv_safe($v): string { $v = (string)$v; return ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v; }
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="anket-' . $id . '-rapor.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, array_merge(['Yanıt No', 'Tarih'], array_map(fn($x) => csv_safe($x['label']), $questions)), ';');
$map = [];
foreach (db()->query('SELECT a.response_id, a.question_id, a.value FROM answers a JOIN responses r ON r.id = a.response_id WHERE r.survey_id = ' . $id) as $a) $map[$a['response_id']][$a['question_id']] = $a['value'];
foreach (db()->query('SELECT id, created_at FROM responses WHERE survey_id = ' . $id . ' ORDER BY id') as $r) {
    $row = [$r['id'], $r['created_at']];
    foreach ($questions as $x) $row[] = csv_safe($map[$r['id']][$x['id']] ?? '');
    fputcsv($out, $row, ';');
}
fclose($out);

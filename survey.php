<?php
// Anket yalnızca bağlantıya (token) sahip kişilere görünür.
require_once __DIR__ . '/includes/bootstrap.php';
$token = $_GET['t'] ?? '';
$st = db()->prepare('SELECT * FROM surveys WHERE token = ? AND is_active = 1');
$st->execute([preg_match('/^[a-f0-9]{32}$/', $token) ? $token : '']);
$s = $st->fetch();
if (!$s) { http_response_code(404); header_html('Bulunamadı', false); echo '<p>Anket bulunamadı veya kapalı.</p>'; footer_html(); exit; }
$qst = db()->prepare('SELECT * FROM questions WHERE survey_id = ? ORDER BY position, id');
$qst->execute([$s['id']]);
$questions = $qst->fetchAll();
$errors = []; $done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $vals = [];
    foreach ($questions as $q) {
        $raw = $_POST['a'][$q['id']] ?? null;
        $opts = $q['options'] ? json_decode($q['options'], true) : [];
        if ($q['type'] === 'checkbox') {
            $v = array_values(array_intersect((array)$raw, $opts)); $val = implode(', ', $v); $empty = !$v;
        } else {
            $val = is_array($raw) ? '' : trim((string)$raw); $empty = ($val === '');
            if (!$empty) {
                if (in_array($q['type'], ['radio', 'select'], true) && !in_array($val, $opts, true)) $val = '';
                elseif ($q['type'] === 'number' && !is_numeric($val)) $val = '';
                elseif ($q['type'] === 'email' && !filter_var($val, FILTER_VALIDATE_EMAIL)) $val = '';
                elseif ($q['type'] === 'date' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) $val = '';
                elseif ($q['type'] === 'rating' && !in_array($val, ['1','2','3','4','5'], true)) $val = '';
                elseif (in_array($q['type'], ['text'], true)) $val = mb_substr($val, 0, 1000);
                elseif ($q['type'] === 'textarea') $val = mb_substr($val, 0, 5000);
                if ($val === '') { $errors[$q['id']] = 'Geçersiz değer.'; }
            }
        }
        if ($empty && $q['is_required']) $errors[$q['id']] = 'Bu soru zorunludur.';
        $vals[$q['id']] = $val;
    }
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO responses (survey_id, created_at) VALUES (?, NOW())')->execute([$s['id']]);
        $rid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO answers (response_id, question_id, value) VALUES (?,?,?)');
        foreach ($vals as $qid => $v) $ins->execute([$rid, $qid, $v === '' ? null : $v]);
        $pdo->commit(); $done = true;
    }
}
header_html($s['title'], false);
if ($done) { echo '<h2>Teşekkürler!</h2><p>Yanıtınız kaydedildi.</p>'; footer_html(); exit; }
?>
<h2><?= e($s['title']) ?></h2><p><?= nl2br(e($s['description'])) ?></p>
<?php if ($errors) echo '<p class="err">Lütfen hataları düzeltin.</p>'; ?>
<form method="post"><?= csrf_field() ?>
<?php foreach ($questions as $q):
    $id = (int)$q['id']; $name = "a[$id]"; $opts = $q['options'] ? json_decode($q['options'], true) : [];
    $cur = $_POST['a'][$id] ?? ''; $req = $q['is_required'] ? ' required' : ''; ?>
<div class="q"><b><?= e($q['label']) ?><?= $q['is_required'] ? ' *' : '' ?></b>
<?php if (isset($errors[$id])) echo '<div class="err">' . e($errors[$id]) . '</div>';
switch ($q['type']) {
  case 'textarea': echo '<textarea name="' . $name . '" rows="4"' . $req . '>' . e(is_array($cur) ? '' : $cur) . '</textarea>'; break;
  case 'select':
    echo '<select name="' . $name . '"' . $req . '><option value="">Seçiniz</option>';
    foreach ($opts as $o) echo '<option' . ($cur === $o ? ' selected' : '') . '>' . e($o) . '</option>';
    echo '</select>'; break;
  case 'radio':
    foreach ($opts as $o) echo '<label class="opt"><input type="radio" name="' . $name . '" value="' . e($o) . '"' . ($cur === $o ? ' checked' : '') . $req . '> ' . e($o) . '</label>'; break;
  case 'checkbox':
    foreach ($opts as $o) echo '<label class="opt"><input type="checkbox" name="' . $name . '[]" value="' . e($o) . '"' . (in_array($o, (array)$cur, true) ? ' checked' : '') . '> ' . e($o) . '</label>'; break;
  case 'rating':
    for ($i = 1; $i <= 5; $i++) echo '<label class="opt" style="display:inline-block;margin-right:12px"><input type="radio" name="' . $name . '" value="' . $i . '"' . ((string)$cur === (string)$i ? ' checked' : '') . $req . '> ' . $i . '</label>'; break;
  default:
    $t = in_array($q['type'], ['number', 'email', 'date'], true) ? $q['type'] : 'text';
    echo '<input type="' . $t . '"' . ($t === 'number' ? ' step="any"' : '') . ' name="' . $name . '" value="' . e(is_array($cur) ? '' : $cur) . '"' . $req . '>';
} ?></div>
<?php endforeach; ?>
<button>Gönder</button></form>
<script>
// Zorunlu çoklu seçimlerde en az bir kutu işaretlenmeli
document.querySelector('form').addEventListener('submit', function (e) {
  <?php $creq = array_values(array_map(fn($q) => (int)$q['id'], array_filter($questions, fn($q) => $q['type'] === 'checkbox' && $q['is_required']))); ?>
  var ids = <?= json_encode($creq) ?>;
  for (var i = 0; i < ids.length; i++) {
    if (!document.querySelector('input[name="a[' + ids[i] + '][]"]:checked')) { e.preventDefault(); alert('Zorunlu çoklu seçim sorusunu cevaplayın.'); return; }
  }
});
</script>
<?php footer_html();

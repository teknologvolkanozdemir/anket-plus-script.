<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$err = ''; $newUrl = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $types = question_types();
    $qs = [];
    foreach ((array)($_POST['q_label'] ?? []) as $i => $label) {
        $label = trim($label); if ($label === '') continue;
        $type = $_POST['q_type'][$i] ?? 'text';
        if (!isset($types[$type])) $type = 'text';
        $opts = null;
        if (in_array($type, ['radio', 'select', 'checkbox'], true)) {
            $list = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)($_POST['q_options'][$i] ?? ''))), 'strlen'));
            if (!$list) { $err = 'Seçenekli sorulara en az bir seçenek girin: ' . $label; break; }
            $opts = json_encode($list, JSON_UNESCAPED_UNICODE);
        }
        $qs[] = [$type, $label, $opts, isset($_POST['q_required'][$i]) ? 1 : 0];
    }
    if ($title === '') $err = 'Anket başlığı gerekli.';
    elseif (!$err && !$qs) $err = 'En az bir soru ekleyin.';
    if (!$err) {
        $pdo = db(); $pdo->beginTransaction();
        $token = bin2hex(random_bytes(16));
        $pdo->prepare('INSERT INTO surveys (token, title, description, is_active, created_at) VALUES (?,?,?,1,NOW())')
            ->execute([$token, $title, trim($_POST['description'] ?? '')]);
        $sid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO questions (survey_id, position, type, label, options, is_required) VALUES (?,?,?,?,?,?)');
        foreach ($qs as $pos => $q) $ins->execute([$sid, $pos, $q[0], $q[1], $q[2], $q[3]]);
        $pdo->commit();
        $newUrl = survey_url($token);
    }
}
header_html('Yeni Anket');
if ($newUrl): ?>
<h2>Anket oluşturuldu</h2>
<input type="text" id="url" readonly value="<?= e($newUrl) ?>">
<p><button type="button" class="copy" data-url="<?= e($newUrl) ?>">Bağlantıyı Panoya Kopyala</button> <a class="btn gray" href="index.php">Anketlere dön</a></p>
<script src="../assets/app.js"></script>
<?php else: ?>
<h2>Yeni Anket</h2><?php if ($err) echo '<p class="err">' . e($err) . '</p>'; ?>
<form method="post" id="f"><?= csrf_field() ?>
<label>Başlık<input type="text" name="title" required maxlength="255" value="<?= e($_POST['title'] ?? '') ?>"></label>
<label>Açıklama<textarea name="description" rows="3"><?= e($_POST['description'] ?? '') ?></textarea></label>
<div id="qs"></div>
<p><button type="button" id="add">+ Soru Ekle</button> <button>Anketi Oluştur</button></p></form>
<template id="tpl"><div class="q">
<label>Soru<input type="text" data-n="q_label" maxlength="500"></label>
<label>Tür<select data-n="q_type"><?php foreach (question_types() as $k => $v) echo '<option value="' . e($k) . '">' . e($v) . '</option>'; ?></select></label>
<label class="optbox" style="display:none">Seçenekler (her satıra bir tane)<textarea data-n="q_options" rows="3"></textarea></label>
<label class="opt"><input type="checkbox" data-n="q_required" value="1"> Bu soruyu cevaplamak zorunlu</label>
<button type="button" class="red rm">Soruyu Sil</button></div></template>
<script>
var qs = document.getElementById('qs'), tpl = document.getElementById('tpl'), idx = 0;
function addQ() {
  var n = tpl.content.cloneNode(true), i = idx++, box = n.querySelector('.q');
  n.querySelectorAll('[data-n]').forEach(function (el) { el.name = el.dataset.n + '[' + i + ']'; });
  var sel = n.querySelector('select'), ob = n.querySelector('.optbox');
  sel.addEventListener('change', function () { ob.style.display = ['radio','select','checkbox'].indexOf(sel.value) >= 0 ? '' : 'none'; });
  n.querySelector('.rm').addEventListener('click', function () { box.remove(); });
  qs.appendChild(n);
}
document.getElementById('add').addEventListener('click', addQ);
addQ();
</script>
<?php endif; footer_html();

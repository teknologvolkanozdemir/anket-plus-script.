function copyText(text, done) {
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(done);
  } else {
    var t = document.createElement('textarea');
    t.value = text; document.body.appendChild(t); t.select();
    document.execCommand('copy'); document.body.removeChild(t); done();
  }
}
document.querySelectorAll('.copy').forEach(function (b) {
  b.addEventListener('click', function () {
    copyText(b.dataset.url, function () {
      var o = b.textContent; b.textContent = 'Kopyalandı!';
      setTimeout(function () { b.textContent = o; }, 1500);
    });
  });
});

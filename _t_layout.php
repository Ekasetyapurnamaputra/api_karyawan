<?php
$root = __DIR__;
$target = $_GET['p'] ?? 'admin/index.php';
unset($_GET['p']);
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once $root . '/config/helpers.php';
$_SESSION['user'] = ['id'=>1,'nik'=>'0001','nama'=>'Admin Test','jabatan'=>'Admin','email'=>'admin@test.local','role'=>'admin'];
$file = $root . '/' . $target;
chdir(dirname($file));
ob_start();
require $file;
$html = ob_get_clean();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body>
<?= $html ?>
<pre id="__diag"></pre>
<script>
window.addEventListener('load', function(){
  setTimeout(function(){
    var d = document.getElementById('__diag');
    var de = document.documentElement;
    var out = [];
    out.push('viewport=' + de.clientWidth + ' scrollWidth=' + de.scrollWidth + ' hOverflow=' + (de.scrollWidth > de.clientWidth + 1));
    var wide = [];
    document.querySelectorAll('body *').forEach(function(el){
      var r = el.getBoundingClientRect();
      if (r.width > 0 && r.right > de.clientWidth + 2) {
        var st = getComputedStyle(el);
        if (st.overflowX === 'visible' || st.overflowX === 'clip') {
          wide.push(el.tagName + '.' + (el.className||'').toString().split(' ').slice(0,2).join('.') + ' right=' + Math.round(r.right));
        }
      }
    });
    out.push('overflowingEls=' + wide.length + ' :: ' + wide.slice(0,8).join(' | '));
    var t = document.querySelector('.adm-table');
    if (t) { var tr = t.getBoundingClientRect(); out.push('table width=' + Math.round(tr.width) + ' wrapOverflowX=' + getComputedStyle(t.parentElement).overflowX); }
    var cards = document.querySelectorAll('.adm-table tbody tr');
    if (cards.length) {
      var first = cards[0];
      var td = first.querySelector('td');
      var disp = getComputedStyle(first).display;
      out.push('rowDisplay=' + disp + ' rowPos=' + getComputedStyle(first).position + ' tdDisplay=' + (td?getComputedStyle(td).display:'-'));
      if (disp === 'block') {
        var lbl = first.querySelector('td[data-label]');
        out.push('cardLabelAttr=' + (lbl ? lbl.getAttribute('data-label') : 'NONE') + ' labelVisible=' + (lbl ? (lbl.getAttribute('data-label')||'').length>0 : false));
      }
    }
    var scroll = document.querySelector('.adm-trend-scroll');
    if (scroll) { out.push('trendScroll clientW=' + scroll.clientWidth + ' scrollW=' + scroll.scrollWidth + ' scrollable=' + (scroll.scrollWidth > scroll.clientWidth) + ' overflowX=' + getComputedStyle(scroll).overflowX); }
    d.textContent = out.join('\n');
  }, 300);
});
</script></body></html>

<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0}iframe{border:0;display:block}</style></head><body>
<iframe id="f" src="/karyawan-wa/_t_render.php?p=<?= $_GET['p'] ?? 'admin/index.php' ?>"></iframe>
<pre id="__diag"></pre>
<script>
var W = <?= (int)($_GET['w'] ?? 320) ?>;
var f = document.getElementById('f');
f.style.width = W + 'px'; f.style.height = '1400px';
f.addEventListener('load', function(){
  setTimeout(function(){
    var doc = f.contentDocument, win = f.contentWindow;
    var de = doc.documentElement;
    var out = [];
    out.push('frameW=' + W + ' docClientW=' + de.clientWidth + ' scrollW=' + de.scrollWidth + ' hOverflow=' + (de.scrollWidth > de.clientWidth + 1));
    var wide = [];
    doc.querySelectorAll('body *').forEach(function(el){
      var r = el.getBoundingClientRect();
      if (r.width > 0 && r.right > de.clientWidth + 2) {
        var st = win.getComputedStyle(el);
        if (st.overflowX === 'visible' || st.overflowX === 'clip') {
          wide.push(el.tagName + '.' + String(el.className||'').split(' ').slice(0,2).join('.') + '@' + Math.round(r.right));
        }
      }
    });
    out.push('overflowing=' + wide.length + ' :: ' + wide.slice(0,6).join(' | '));
    var rows = doc.querySelectorAll('.adm-table tbody tr');
    if (rows.length) {
      var r0 = rows[0], td = r0.querySelector('td');
      out.push('rowDisplay=' + win.getComputedStyle(r0).display + ' tdDisplay=' + win.getComputedStyle(td).display);
      var lblTd = r0.querySelector('td:not(.adm-cell-index)');
      var before = win.getComputedStyle(lblTd, '::before');
      out.push('labelContent="' + before.content + '" labelW=' + lblTd.getBoundingClientRect().width.toFixed(0));
      var idxTd = r0.querySelector('td.adm-cell-index');
      out.push('indexHidden=' + (idxTd ? win.getComputedStyle(idxTd).display === 'none' : 'n/a'));
    }
    var sc = doc.querySelector('.adm-trend-scroll');
    if (sc) out.push('trend clientW=' + sc.clientWidth + ' scrollW=' + sc.scrollWidth + ' scrollable=' + (sc.scrollWidth > sc.clientWidth + 1) + ' overflowX=' + win.getComputedStyle(sc).overflowX);
    var tg = doc.querySelector('.adm-trend');
    if (tg) out.push('trendCols=' + tg.children.length + ' trendW=' + tg.getBoundingClientRect().width.toFixed(0) + ' trendScrollW=' + tg.scrollWidth);
    var sg = doc.querySelector('.stat-grid');
    if (sg) out.push('statGridCols=' + win.getComputedStyle(sg).gridTemplateColumns);
    var fl = doc.querySelector('.adm-filter');
    if (fl) out.push('filterDisplay=' + win.getComputedStyle(fl).display + ' filterW=' + fl.getBoundingClientRect().width.toFixed(0));
    document.getElementById('__diag').textContent = out.join('\n');
  }, 500);
});
</script></body></html>

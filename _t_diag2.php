<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0}iframe{border:0;display:block}</style></head><body>
<iframe id="f" src="/karyawan-wa/_t_render.php?p=admin/laporan.php"></iframe>
<pre id="__diag"></pre>
<script>
var W = <?= (int)($_GET['w'] ?? 320) ?>;
var f = document.getElementById('f');
f.style.width = W + 'px'; f.style.height = '1600px';
f.addEventListener('load', function(){
  setTimeout(function(){
    var doc = f.contentDocument, win = f.contentWindow;
    var de = doc.documentElement;
    var out = [];
    out.push('innerViewport=' + win.innerWidth + ' docClientW=' + de.clientWidth + ' scrollW=' + de.scrollWidth);
    var chain = ['.adm-split', '.adm-subpanel', '.adm-trend-scroll', '.adm-trend', '.adm-table-wrap', '.adm-table', '.adm-breakdown'];
    chain.forEach(function(sel){
      var el = doc.querySelector(sel);
      if (!el) { out.push(sel + ' MISSING'); return; }
      var r = el.getBoundingClientRect(); var st = win.getComputedStyle(el);
      out.push(sel + ' left=' + Math.round(r.left) + ' w=' + Math.round(r.width) + ' right=' + Math.round(r.right) +
        ' | display=' + st.display + ' overflowX=' + st.overflowX + ' minW=' + st.minWidth + ' maxW=' + st.maxWidth +
        ' gtc=' + st.gridTemplateColumns);
    });
    var split = doc.querySelector('.adm-split');
    if (split) { Array.prototype.forEach.call(split.children, function(c,i){
      var r=c.getBoundingClientRect(); var st=win.getComputedStyle(c);
      out.push('  child'+i+' '+String(c.className)+' w='+Math.round(r.width)+' minW='+st.minWidth+' display='+st.display);
    }); }
    var main = doc.querySelector('main') || doc.querySelector('.container') || doc.body;
    var stm = win.getComputedStyle(main);
    out.push('main=' + String(main.className||main.tagName) + ' overflowX=' + stm.overflowX + ' width=' + Math.round(main.getBoundingClientRect().width));
    doc.querySelectorAll('main, .container, .container-inner, body').forEach(function(el){
      var st = win.getComputedStyle(el);
      out.push('  box ' + String(el.className||el.tagName) + ' overflowX=' + st.overflowX + ' w=' + Math.round(el.getBoundingClientRect().width) + ' scrollW=' + el.scrollWidth);
    });
    document.getElementById('__diag').textContent = out.join('\n');
  }, 600);
});
</script></body></html>

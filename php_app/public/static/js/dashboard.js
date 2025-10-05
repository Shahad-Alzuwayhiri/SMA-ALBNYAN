// Small toggle for mobile sidebar
(function(){
  var btn = document.getElementById('hamburgerBtn')
  var overlay = document.querySelector('.sidebar-overlay')
  var sidebar = document.getElementById('mainSidebar')

  function openSidebar(){
    document.body.classList.add('sidebar-open')
    if(btn) btn.setAttribute('aria-expanded','true')
    if(overlay) overlay.setAttribute('aria-hidden','false')
    if(sidebar) sidebar.setAttribute('aria-hidden','false')
    // focus first focusable link inside sidebar for keyboard users
    try{ var first = sidebar && sidebar.querySelector('a,button,input'); if(first) first.focus() }catch(e){}
  }

  function closeSidebar(){
    document.body.classList.remove('sidebar-open')
    if(btn) btn.setAttribute('aria-expanded','false')
    if(overlay) overlay.setAttribute('aria-hidden','true')
    if(sidebar) sidebar.setAttribute('aria-hidden','true')
  }

  function toggle(){ if(document.body.classList.contains('sidebar-open')) closeSidebar(); else openSidebar() }

  if(btn){
    // ensure visibility is controlled by CSS; progressive enhancement
    btn.addEventListener('click', function(e){ e.preventDefault(); toggle() })
  }

  if(overlay){ overlay.addEventListener('click', function(){ closeSidebar() }) }

  // close on Escape key
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape' || e.key === 'Esc'){ if(document.body.classList.contains('sidebar-open')) closeSidebar() } })
})();

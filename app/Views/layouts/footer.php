    <footer class="lms-footer">Powered by ITEC LTD &copy; <?= date('Y') ?></footer>
  </main>
</div>
<script src="<?= $baseUrl ?>/assets/js/jquery.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/popper.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/moment.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/bootstrap.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/simplebar.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/daterangepicker.js"></script>
<script src="<?= $baseUrl ?>/assets/js/tinycolor-min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/config.js"></script>
<script src="<?= $baseUrl ?>/assets/js/apps.js"></script>
<script src="<?= $baseUrl ?>/assets/js/jquery.dataTables.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/dataTables.bootstrap4.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/select2.min.js"></script>
<div class="modal fade" id="statusConfirmModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Change status</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p class="mb-0">Confirm changing this record to <strong class="status-target">ON (1)</strong>?</p></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="confirmStatusButton">Confirm change</button></div></div></div></div>
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Delete record</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p>Deletion is permanent. Enter a reason to continue.</p><label for="deleteReason">Reason</label><textarea class="form-control" id="deleteReason" rows="3" required></textarea><div class="invalid-feedback">A reason is required.</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete record</button></div></div></div></div>
<script>$(function(){ $('.select2').select2({theme:'bootstrap4',width:'100%'}); $('.logistics-data-table').each(function(){ var table=$(this).DataTable({autoWidth:true,lengthMenu:[[10,25,50,-1],[10,25,50,'All']]}); $(table.table().node()).wrap('<div class="table-responsive logistics-table-scroll"></div>'); $('.module-search').on('keyup',function(){ table.search(this.value).draw(); }); $('.module-status-filter').on('change',function(){ table.search(this.value).draw(); }); }); var pendingStatusForm=null,pendingStatusValue=null,pendingDeleteForm=null; $('.status-switch').on('change',function(event){ event.preventDefault(); var input=$(this); pendingStatusValue=input.is(':checked') ? 1 : 0; input.prop('checked',!input.is(':checked')); pendingStatusForm=input.closest('form'); $('.status-target').text(pendingStatusValue ? 'ON (1)' : 'OFF (0)'); $('#statusConfirmModal').modal('show'); }); $('#confirmStatusButton').on('click',function(){ if(!pendingStatusForm){return;} pendingStatusForm.find('input[name=status]').val(pendingStatusValue); pendingStatusForm[0].submit(); }); $('#statusConfirmModal').on('hidden.bs.modal',function(){ pendingStatusForm=null; pendingStatusValue=null; }); $('.delete-form').on('submit',function(event){ event.preventDefault(); pendingDeleteForm=this; $('#deleteReason').val('').removeClass('is-invalid'); $('#deleteConfirmModal').modal('show'); }); $('#confirmDeleteButton').on('click',function(){ var reason=$('#deleteReason').val().trim(); if(!reason){ $('#deleteReason').addClass('is-invalid'); return; } $(pendingDeleteForm).find('input[name=reason]').val(reason); pendingDeleteForm.submit(); }); });</script>
<script>
try { if (localStorage.getItem('mode') === 'dark') { document.body.classList.add('dark'); } } catch (e) {}
(function () {
  var box = document.querySelector('.lms-search');
  if (!box) { return; }
  var input = box.querySelector('input'), list = box.querySelector('.lms-search-results');
  var pages = JSON.parse(box.getAttribute('data-pages') || '[]'), shown = [], active = -1;
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }
  function paint() {
    list.innerHTML = shown.length ? shown.map(function (p, i) {
      return '<li><a class="lms-search-item' + (i === active ? ' active' : '') + '" href="' + esc(p.url) + '"><strong>' + esc(p.label) + '</strong><small>' + esc(p.group) + '</small></a></li>';
    }).join('') : '<li class="lms-search-empty">No matching pages</li>';
    list.hidden = false;
  }
  function search() {
    var q = input.value.trim().toLowerCase();
    shown = pages.filter(function (p) { return !q || (p.label + ' ' + p.group + ' ' + p.keywords).toLowerCase().indexOf(q) !== -1; });
    active = q && shown.length ? 0 : -1;
    paint();
  }
  input.addEventListener('focus', search);
  input.addEventListener('input', search);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (!shown.length) { return; }
      active = (active + (e.key === 'ArrowDown' ? 1 : -1) + shown.length) % shown.length;
      paint();
      var el = list.querySelector('.active'); if (el) { el.scrollIntoView({block: 'nearest'}); }
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (shown[active >= 0 ? active : 0]) { window.location.href = shown[active >= 0 ? active : 0].url; }
    } else if (e.key === 'Escape') {
      list.hidden = true; input.blur();
    }
  });
  document.addEventListener('click', function (e) { if (!box.contains(e.target)) { list.hidden = true; } });
  document.addEventListener('keydown', function (e) {
    var tag = (e.target.tagName || '').toLowerCase();
    if (e.key === '/' && tag !== 'input' && tag !== 'textarea' && tag !== 'select' && !e.target.isContentEditable) { e.preventDefault(); input.focus(); }
  });
})();
</script>
</body>
</html>

  </main>
</div>

<!-- ═══ SCRIPTS ═══ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
  // ── Sidebar collapse (desktop) ──
  const collapseBtn = document.getElementById('sidebar-collapse-btn');
  const html = document.documentElement;
  if (collapseBtn) {
    collapseBtn.addEventListener('click', function () {
      const collapsed = html.classList.toggle('sidebar-collapsed');
      localStorage.setItem('admin_sidebar_collapsed', collapsed ? '1' : '0');
    });
  }

  // ── Sidebar toggle (mobile) ──
  document.getElementById('sidebar-toggle')?.addEventListener('click', function () {
    document.getElementById('admin-sidebar').classList.toggle('open');
    document.body.classList.toggle('sidebar-backdrop');
  });

  // Click backdrop / link to close mobile sidebar
  document.addEventListener('click', function (e) {
    if (window.innerWidth >= 992) return;
    if (e.target.closest('.sidebar-link')) {
      document.getElementById('admin-sidebar').classList.remove('open');
      document.body.classList.remove('sidebar-backdrop');
    }
  });

  // ── DataTables ──
  document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && jQuery.fn.DataTable) {
      jQuery('table.datatable').DataTable({
        language: {
          search: 'Cari:',
          lengthMenu: 'Tampilkan _MENU_ data',
          info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
          paginate: { previous: '←', next: '→' },
          emptyTable: 'Belum ada data',
          zeroRecords: 'Tidak ada hasil ditemukan',
        },
        pageLength: 10,
        order: [],
      });
    }
  });
</script>

<?php if (!empty($extra_js)): ?>
  <script src="<?= BASE_URL ?>assets/js/<?= $extra_js ?>"></script>
<?php endif; ?>

</body>
</html>

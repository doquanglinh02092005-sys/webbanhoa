    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelector('[data-admin-menu]')?.addEventListener('click', () => document.body.classList.toggle('admin-menu-open'));
    document.querySelectorAll('[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    }));
  </script>
</body>
</html>

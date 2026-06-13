    </main>
  </div>
  <script>
    document.querySelector('[data-admin-menu]')?.addEventListener('click', () => document.body.classList.toggle('admin-menu-open'));
    document.querySelectorAll('[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    }));
  </script>
</body>
</html>

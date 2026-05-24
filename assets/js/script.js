(function () {
  const hasSwal = () => typeof window.Swal !== 'undefined';

  const baseSwalOptions = {
    customClass: {
      popup: 'kanto-swal',
      confirmButton: 'kanto-confirm',
      cancelButton: 'kanto-cancel'
    },
    buttonsStyling: false
  };

  function swalFire(options) {
    if (!hasSwal()) {
      return Promise.resolve({ isConfirmed: true });
    }

    return window.Swal.fire({ ...baseSwalOptions, ...options });
  }

  function toast(icon, title) {
    if (!hasSwal()) {
      return;
    }

    window.Swal.fire({
      ...baseSwalOptions,
      toast: true,
      position: 'top-end',
      timer: 2200,
      timerProgressBar: true,
      showConfirmButton: false,
      icon,
      title
    });
  }

  window.KantoSwal = swalFire;
  window.KantoToast = toast;

  function appBaseUrl() {
    const configuredBase = String(window.KANTO_BASE_URL || '').trim();

    if (configuredBase) {
      return new URL(configuredBase, window.location.origin).href;
    }

    const script = document.querySelector('script[src*="assets/js/script.js"]');

    if (script?.src) {
      return script.src.replace(/assets\/js\/script\.js.*$/, '');
    }

    return `${window.location.origin}/`;
  }

  function normalizeRedirect(redirect) {
    const value = String(redirect || '').trim();

    if (!value) {
      return '';
    }

    if (value.startsWith('//')) {
      return new URL(value.replace(/^\/+/, ''), appBaseUrl()).href;
    }

    if (value.startsWith('/')) {
      const base = new URL(appBaseUrl());

      if (base.pathname !== '/' && !value.startsWith(base.pathname)) {
        return new URL(value.replace(/^\/+/, ''), base.href).href;
      }
    }

    return new URL(value, window.location.href).href;
  }

  function followRedirect(redirect) {
    const url = normalizeRedirect(redirect);

    if (url) {
      window.location.href = url;
    }
  }

  function showFlash() {
    const flash = window.KANTO_SWAL_FLASH;

    if (!flash) {
      return;
    }

    const redirect = flash.redirect || null;
    const isToast = Boolean(flash.toast);
    const options = {
      icon: flash.icon || 'info',
      title: flash.title || 'Notice',
      text: flash.text || flash.message || '',
      timer: flash.timer || (redirect ? 1500 : undefined),
      showConfirmButton: flash.showConfirmButton !== false
    };

    if (isToast) {
      toast(options.icon, options.title);
      if (redirect) {
        window.setTimeout(() => {
          followRedirect(redirect);
        }, flash.timer || 1300);
      }
      return;
    }

    swalFire(options).then(() => {
      if (redirect) {
        followRedirect(redirect);
      }
    });
  }

  function initLandingNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-site-nav]');

    if (!toggle || !nav) {
      return;
    }

    toggle.addEventListener('click', () => {
      nav.classList.toggle('is-open');
    });

    nav.querySelectorAll('a, button').forEach((item) => {
      item.addEventListener('click', () => {
        nav.classList.remove('is-open');
      });
    });
  }

  function openLogin(role) {
    const modal = document.querySelector('[data-login-modal]');
    const roleInput = document.getElementById('login_role');

    if (!modal) {
      return;
    }

    if (roleInput && role) {
      roleInput.value = role;
    }

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    document.getElementById('login_email')?.focus();
  }

  function closeLogin() {
    const modal = document.querySelector('[data-login-modal]');

    if (!modal) {
      return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  }

  function initLoginModal() {
    document.querySelectorAll('[data-open-login]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        openLogin(button.dataset.role || '');
      });
    });

    document.querySelectorAll('[data-close-login]').forEach((button) => {
      button.addEventListener('click', closeLogin);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeLogin();
      }
    });
  }

  function validateLogin(email, password, role) {
    if (!email) {
      return 'Please enter your username or email.';
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      return 'Please enter a valid email address.';
    }

    if (!password) {
      return 'Please enter your password.';
    }

    if (!role) {
      return 'Please select your role.';
    }

    return '';
  }

  function initLoginForm() {
    const form = document.getElementById('systemLoginForm');

    if (!form) {
      return;
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const formData = new FormData(form);
      formData.set('ajax', '1');
      const email = String(formData.get('email') || '').trim();
      const password = String(formData.get('password') || '');
      const role = String(formData.get('role') || '');
      const validationMessage = validateLogin(email, password, role);

      if (validationMessage) {
        await swalFire({
          icon: 'warning',
          title: 'Check your login details',
          text: validationMessage
        });
        return;
      }

      try {
        const response = await fetch(form.action || window.KANTO_LOGIN_ENDPOINT, {
          method: 'POST',
          body: formData,
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const result = await response.json();

        await swalFire({
          icon: result.icon || (result.status === 'success' ? 'success' : 'error'),
          title: result.title || 'Login',
          text: result.message || result.text || ''
        });

        if (result.status === 'success' && result.redirect) {
          followRedirect(result.redirect);
        }
      } catch (error) {
        await swalFire({
          icon: 'error',
          title: 'Unexpected server error',
          text: 'Something went wrong. Please try again.'
        });
      }
    });
  }

  function initProfileDropdowns() {
    const dropdowns = Array.from(document.querySelectorAll('[data-profile-dropdown]'));

    if (!dropdowns.length) {
      return;
    }

    const closeAll = (except = null) => {
      dropdowns.forEach((dropdown) => {
        if (dropdown === except) {
          return;
        }

        dropdown.classList.remove('is-open');
        dropdown.querySelector('[data-profile-toggle]')?.setAttribute('aria-expanded', 'false');
      });
    };

    dropdowns.forEach((dropdown) => {
      const toggle = dropdown.querySelector('[data-profile-toggle]');

      if (!toggle) {
        return;
      }

      toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = !dropdown.classList.contains('is-open');
        closeAll(dropdown);
        dropdown.classList.toggle('is-open', willOpen);
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
    });

    document.addEventListener('click', (event) => {
      if (!event.target.closest('[data-profile-dropdown]')) {
        closeAll();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeAll();
      }
    });
  }

  function initMobileSidebars() {
    const toggles = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));

    if (!toggles.length) {
      return;
    }

    const overlay = document.createElement('button');
    overlay.type = 'button';
    overlay.className = 'sidebar-overlay';
    overlay.setAttribute('aria-label', 'Close navigation');
    document.body.appendChild(overlay);

    const isMobile = () => window.matchMedia('(max-width: 920px)').matches;

    const setOpen = (sidebar, toggle, open) => {
      sidebar.classList.toggle('is-mobile-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('sidebar-open', open);
    };

    const closeAll = () => {
      toggles.forEach((toggle) => {
        const sidebar = toggle.closest('.app-sidebar') || document.querySelector('.app-sidebar');
        if (sidebar) {
          setOpen(sidebar, toggle, false);
        }
      });
    };

    toggles.forEach((toggle) => {
      const sidebar = toggle.closest('.app-sidebar') || document.querySelector('.app-sidebar');

      if (!sidebar) {
        return;
      }

      toggle.addEventListener('click', () => {
        setOpen(sidebar, toggle, !sidebar.classList.contains('is-mobile-open'));
      });

      sidebar.querySelectorAll('[data-sidebar-nav] a').forEach((link) => {
        link.addEventListener('click', () => {
          if (isMobile()) {
            setOpen(sidebar, toggle, false);
          }
        });
      });
    });

    overlay.addEventListener('click', closeAll);

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') {
        return;
      }

      closeAll();
    });

    window.addEventListener('resize', () => {
      if (isMobile()) {
        return;
      }

      closeAll();
    });
  }

  function initPasswordToggles() {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
      if (input.closest('.password-toggle-field')) {
        return;
      }

      const wrapper = document.createElement('span');
      wrapper.className = 'password-toggle-field';
      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'password-toggle-btn';
      button.setAttribute('aria-label', 'Show password');
      button.setAttribute('title', 'Show password');
      button.innerHTML = '<i class="fa-regular fa-eye" aria-hidden="true"></i>';
      wrapper.appendChild(button);

      button.addEventListener('click', () => {
        const showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        button.setAttribute('title', showPassword ? 'Hide password' : 'Show password');
        button.innerHTML = showPassword
          ? '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>'
          : '<i class="fa-regular fa-eye" aria-hidden="true"></i>';
        input.focus();
      });
    });
  }

  function initCategorySelects() {
    document.querySelectorAll('[data-category-select]').forEach((select) => {
      const group = select.closest('.form-group') || select.parentElement;
      const newCategoryField = group?.querySelector('[data-new-category-field]');
      const newCategoryInput = newCategoryField?.querySelector('input');

      if (!newCategoryField || !newCategoryInput) {
        return;
      }

      const sync = () => {
        const addingNew = select.value === '__new__';
        newCategoryField.classList.toggle('hidden', !addingNew);
        newCategoryInput.required = addingNew;

        if (!addingNew) {
          newCategoryInput.value = '';
        }
      };

      select.addEventListener('change', sync);
      sync();
    });
  }

  function initInventoryBrowsers() {
    const browsers = Array.from(document.querySelectorAll('[data-inventory-browser]'));

    if (!browsers.length) {
      return;
    }

    const searchInput = document.querySelector('.app-header-search input[name="search"], .app-header-search input[type="search"]');
    const normalize = (value) => String(value || '').trim().toLowerCase();

    browsers.forEach((browser) => {
      const controls = browser.querySelector('[data-inventory-controls]');
      const sections = Array.from(browser.querySelectorAll('[data-category-section]'));
      const emptyState = browser.querySelector('[data-inventory-empty]');
      let activeType = 'all';
      let activeValue = '';

      const activeButton = controls?.querySelector('[data-inventory-filter].is-active, [data-inventory-filter].rf-chip-active');
      if (activeButton) {
        activeType = activeButton.dataset.filterType || 'all';
        activeValue = activeButton.dataset.filterValue || '';
      }

      const rowMatchesFilter = (row, section) => {
        if (activeType === 'all') {
          return true;
        }

        if (activeType === 'category') {
          return String(section.dataset.category || '') === activeValue;
        }

        if (activeType === 'status') {
          return String(row.dataset.status || '') === activeValue;
        }

        return true;
      };

      const applyFilters = () => {
        const query = normalize(searchInput?.value || '');
        let visibleRows = 0;

        sections.forEach((section) => {
          let visibleRowsInSection = 0;
          const rows = Array.from(section.querySelectorAll('[data-product-row]'));

          rows.forEach((row) => {
            const matchesSearch = query === '' || normalize(row.dataset.search || '').includes(query);
            const isVisible = matchesSearch && rowMatchesFilter(row, section);

            row.classList.toggle('hidden', !isVisible);

            if (isVisible) {
              visibleRowsInSection++;
              visibleRows++;
            }
          });

          section.classList.toggle('hidden', visibleRowsInSection === 0);
        });

        emptyState?.classList.toggle('hidden', visibleRows > 0);
      };

      browser.addEventListener('click', (event) => {
        const button = event.target.closest('[data-inventory-filter]');

        if (!button || !browser.contains(button)) {
          return;
        }

        activeType = button.dataset.filterType || 'all';
        activeValue = button.dataset.filterValue || '';

        browser.querySelectorAll('[data-inventory-filter]').forEach((item) => {
          const isActive = item === button;
          item.classList.toggle('is-active', isActive);
          item.classList.toggle('rf-chip-active', isActive);
        });

        applyFilters();
      });

      searchInput?.addEventListener('input', applyFilters);
      applyFilters();
    });
  }

  function initConfirmations() {
    document.addEventListener('click', (event) => {
      const logoutLink = event.target.closest('[data-logout]');

      if (logoutLink) {
        event.preventDefault();
        swalFire({
          icon: 'question',
          title: 'Are you sure you want to logout?',
          text: 'Your current session will be closed.',
          showCancelButton: true,
          confirmButtonText: 'Yes, logout',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = logoutLink.href;
          }
        });
      }
    });

    document.addEventListener('submit', (event) => {
      const form = event.target;

      if (event.defaultPrevented || !(form instanceof HTMLFormElement) || form.dataset.swalConfirmed === '1') {
        return;
      }

      const trigger = event.submitter;
      const confirmTitle = form.dataset.swalConfirm || trigger?.dataset.swalConfirm;

      if (!confirmTitle) {
        return;
      }

      event.preventDefault();
      swalFire({
        icon: trigger?.dataset.swalIcon || form.dataset.swalIcon || 'warning',
        title: confirmTitle,
        text: trigger?.dataset.swalText || form.dataset.swalText || 'This action cannot be undone.',
        showCancelButton: true,
        confirmButtonText: trigger?.dataset.swalConfirmText || form.dataset.swalConfirmText || 'Yes, continue',
        cancelButtonText: trigger?.dataset.swalCancelText || form.dataset.swalCancelText || 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          form.dataset.swalConfirmed = '1';
          if (trigger && trigger.name) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = trigger.name;
            hidden.value = trigger.value;
            form.appendChild(hidden);
          }
          form.submit();
        }
      });
    });
  }

  function initFormValidation() {
    document.querySelectorAll('form').forEach((form) => {
      form.setAttribute('novalidate', 'novalidate');
    });

    document.addEventListener('submit', (event) => {
      const form = event.target;

      if (!(form instanceof HTMLFormElement) || form.id === 'systemLoginForm') {
        return;
      }

      const fields = Array.from(form.querySelectorAll('input, select, textarea'));

      for (const field of fields) {
        if (field.disabled || field.type === 'hidden') {
          continue;
        }

        const label = form.querySelector(`label[for="${field.id}"]`)?.textContent?.trim() || field.name || 'This field';
        const value = String(field.value || '').trim();

        if (field.hasAttribute('required') && value === '') {
          event.preventDefault();
          swalFire({
            icon: 'warning',
            title: 'Required field',
            text: `${label} is required.`
          }).then(() => field.focus());
          return;
        }

        if (field.type === 'email' && value !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          event.preventDefault();
          swalFire({
            icon: 'warning',
            title: 'Invalid email format',
            text: 'Please enter a valid email address.'
          }).then(() => field.focus());
          return;
        }

        if (field.type === 'number' && value !== '') {
          const numberValue = Number(value);
          const min = field.getAttribute('min');

          if (!Number.isFinite(numberValue) || (min !== null && numberValue < Number(min))) {
            event.preventDefault();
            swalFire({
              icon: 'warning',
              title: 'Invalid number input',
              text: `${label} has an invalid value.`
            }).then(() => field.focus());
            return;
          }
        }
      }

      const password = form.querySelector('input[name="password"], input[name="new_password"]');
      const confirmPassword = form.querySelector('input[name="confirm_password"]');

      if (password && confirmPassword && String(password.value) !== String(confirmPassword.value)) {
        event.preventDefault();
        swalFire({
          icon: 'warning',
          title: 'Passwords do not match',
          text: 'Please re-enter matching passwords.'
        }).then(() => confirmPassword.focus());
      }
    }, true);
  }

  document.addEventListener('DOMContentLoaded', () => {
    showFlash();
    initLandingNav();
    initLoginModal();
    initLoginForm();
    initPasswordToggles();
    initCategorySelects();
    initInventoryBrowsers();
    initProfileDropdowns();
    initMobileSidebars();
    initFormValidation();
    initConfirmations();
  });
})();

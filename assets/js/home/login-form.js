document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-login-register-form]').forEach((form) => {
    const usernameInput = form.querySelector('[data-register-username]');
    const emailInput = form.querySelector('[data-register-email]');
    const passwordInput = form.querySelector('#password');
    const passwordConfirmInput = form.querySelector('[data-register-password-confirm]');
    const registerButton = form.querySelector('[data-register-submit]');
    const loginButton = form.querySelector('[data-login-submit]');
    const registerFields = form.querySelectorAll('[data-register-fields]');
    const formContent = form.querySelector('.form_login');
    const leaveDuration = 320;
    const enterDuration = 420;

    if (!usernameInput || !emailInput || !passwordInput || !passwordConfirmInput || !registerButton || !loginButton || !formContent) {
      return;
    }

    const isEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());

    const setRegisterFieldsEnabled = (enabled) => {
      registerFields.forEach((field) => {
        field.hidden = !enabled;
      });

      emailInput.disabled = !enabled;
      passwordConfirmInput.disabled = !enabled;
      emailInput.required = enabled;
      passwordConfirmInput.required = enabled;
    };

    const prepareEmailField = () => {
      if (isEmail(usernameInput.value)) {
        emailInput.value = usernameInput.value.trim();
        emailInput.readOnly = true;
        emailInput.classList.add('home-register-email-locked');
      } else {
        if (emailInput.readOnly) {
          emailInput.value = '';
        }

        emailInput.readOnly = false;
        emailInput.classList.remove('home-register-email-locked');
      }
    };

    const focusFirstEmptyRegisterField = () => {
      const orderedFields = [usernameInput, emailInput, passwordInput, passwordConfirmInput];
      const firstEmptyField = orderedFields.find((field) => {
        return !field.disabled && !field.readOnly && field.value.trim() === '';
      });

      if (!firstEmptyField) {
        return null;
      }

      firstEmptyField.focus();
      return firstEmptyField;
    };

    const openRegisterMode = (shouldFocus = true) => {
      form.dataset.registerMode = 'true';
      setRegisterFieldsEnabled(true);
      prepareEmailField();

      if (shouldFocus) {
        focusFirstEmptyRegisterField();
      }
    };

    const closeRegisterMode = () => {
      form.dataset.registerMode = 'false';
      setRegisterFieldsEnabled(false);
      passwordConfirmInput.setCustomValidity('');
    };

    const validateLoginFields = () => {
      usernameInput.setCustomValidity('');
      passwordInput.setCustomValidity('');

      if (usernameInput.value.trim() === '') {
        usernameInput.setCustomValidity('Merci de renseigner un login.');
        usernameInput.reportValidity();
        usernameInput.focus();
        return false;
      }

      if (passwordInput.value.trim() === '') {
        passwordInput.setCustomValidity('Merci de renseigner un mot de passe.');
        passwordInput.reportValidity();
        passwordInput.focus();
        return false;
      }

      return true;
    };

    const switchModeWithCarousel = (targetMode, afterSwitch = () => {}) => {
      const shouldOpenRegister = targetMode === 'register';
      const leaveClass = shouldOpenRegister ? 'home-form-leave-left' : 'home-form-leave-right';
      const enterClass = shouldOpenRegister ? 'home-form-enter-right' : 'home-form-enter-left';
      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      if (reducedMotion) {
        if (shouldOpenRegister) {
          openRegisterMode(false);
        } else {
          closeRegisterMode();
        }

        afterSwitch();
        return;
      }

      form.style.height = `${form.offsetHeight}px`;
      form.offsetHeight;
      form.classList.add('home-form-is-animating');
      formContent.classList.add(leaveClass);

      window.setTimeout(() => {
        formContent.classList.remove(leaveClass);

        if (shouldOpenRegister) {
          openRegisterMode(false);
        } else {
          closeRegisterMode();
        }

        form.style.height = `${formContent.scrollHeight}px`;
        formContent.classList.add(enterClass);

        window.setTimeout(() => {
          formContent.classList.remove(enterClass);
          form.classList.remove('home-form-is-animating');
          form.style.height = '';
          afterSwitch();
        }, enterDuration);
      }, leaveDuration);
    };

    const validatePasswordConfirmation = () => {
      if (passwordConfirmInput.disabled) {
        passwordConfirmInput.setCustomValidity('');
        return true;
      }

      if (passwordInput.value !== passwordConfirmInput.value) {
        passwordConfirmInput.setCustomValidity('Les mots de passe ne correspondent pas.');
        return false;
      }

      passwordConfirmInput.setCustomValidity('');
      return true;
    };

    closeRegisterMode();

    usernameInput.addEventListener('input', () => {
      usernameInput.setCustomValidity('');

      if (form.dataset.registerMode === 'true') {
        prepareEmailField();
      }
    });

    passwordInput.addEventListener('input', validatePasswordConfirmation);
    passwordConfirmInput.addEventListener('input', validatePasswordConfirmation);

    registerButton.addEventListener('click', (event) => {
      if (form.dataset.registerMode !== 'true') {
        event.preventDefault();
        switchModeWithCarousel('register', focusFirstEmptyRegisterField);
        return;
      }

      prepareEmailField();

      const firstEmptyField = focusFirstEmptyRegisterField();

      if (firstEmptyField) {
        firstEmptyField.reportValidity();
        event.preventDefault();
        return;
      }

      if (!validatePasswordConfirmation()) {
        passwordConfirmInput.reportValidity();
        event.preventDefault();
      }
    });

    loginButton.addEventListener('click', (event) => {
      if (form.dataset.registerMode === 'true') {
        event.preventDefault();
        switchModeWithCarousel('login', () => {
          if (validateLoginFields()) {
            form.requestSubmit(loginButton);
          }
        });
        return;
      }

      closeRegisterMode();

      if (!validateLoginFields()) {
        event.preventDefault();
        return;
      }
    });

    passwordInput.addEventListener('input', () => {
      passwordInput.setCustomValidity('');
    });

    form.querySelectorAll('[data-password-toggle]').forEach((toggleButton) => {
      if (toggleButton.dataset.passwordToggleReady === 'true') {
        return;
      }

      const field = toggleButton.closest('.home-password-field')?.querySelector('[data-password-input]');

      if (!field) {
        return;
      }

      toggleButton.dataset.passwordToggleReady = 'true';

      const syncPasswordIcons = () => {
        const isVisible = field.type === 'text';

        toggleButton.classList.toggle('home-password-toggle-visible', isVisible);
        toggleButton.setAttribute('aria-label', isVisible ? 'Cacher le mot de passe' : 'Afficher le mot de passe');
      };

      syncPasswordIcons();

      toggleButton.addEventListener('click', () => {
        const shouldShow = field.type === 'password';

        field.type = shouldShow ? 'text' : 'password';
        syncPasswordIcons();
      });
    });
  });
});

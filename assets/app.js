// MODULE
import './modules/modules.js';
import { initSiteSync } from './js/service/siteSync.js';

// CSS
import './styles/app.css';

const initToaster = () => {
	const toaster = document.getElementById('toaster');

	if (!toaster) {
		return;
	}

	const message = (toaster.dataset.toaster || '').trim();

	if (!message) {
		return;
	}

	toaster.textContent = message;
	toaster.classList.add('toaster-visible');

	const hideToaster = () => {
		toaster.classList.remove('toaster-visible');
		toaster.classList.add('toaster-hidden');
	};

	window.setTimeout(hideToaster, 3500);
	toaster.addEventListener('click', hideToaster);
	toaster.addEventListener('transitionend', () => {
		if (toaster.classList.contains('toaster-hidden')) {
			toaster.textContent = '';
			toaster.classList.remove('toaster-hidden');
		}
	});
};

const initPasswordToggles = () => {
	document.querySelectorAll('[data-password-toggle]').forEach((toggleButton) => {
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
};

const initNetworkToaster = () => {
	const toaster = document.getElementById('toaster');

	if (!toaster || !('onLine' in navigator)) {
		return;
	}

	let offlineToasterVisible = false;

	const showOfflineToaster = () => {
		if (offlineToasterVisible) {
			return;
		}

		offlineToasterVisible = true;
		toaster.textContent = 'Connexion internet perdue';
		toaster.classList.remove('toaster-hidden', 'toaster-refresh');
		toaster.classList.add('toaster-visible', 'toaster-offline');
	};

	const hideOfflineToaster = () => {
		if (!offlineToasterVisible) {
			return;
		}

		offlineToasterVisible = false;
		toaster.classList.remove('toaster-visible', 'toaster-offline');
		toaster.classList.add('toaster-hidden');
	};

	window.addEventListener('offline', showOfflineToaster);
	window.addEventListener('online', hideOfflineToaster);

	if (!navigator.onLine) {
		showOfflineToaster();
	}
};

const initErrorPageRedirect = () => {
	const errorPage = document.querySelector('[data-error-page]');

	if (!errorPage) {
		return;
	}

	const redirectUrl = (errorPage.dataset.errorRedirectUrl || '').trim();
	const countdown = errorPage.querySelector('[data-error-countdown]');
	let remainingSeconds = Number.parseInt(errorPage.dataset.errorRedirectDelay || '', 10);

	if (!redirectUrl || !Number.isFinite(remainingSeconds) || remainingSeconds < 1) {
		return;
	}

	const updateCountdown = () => {
		if (countdown) {
			countdown.textContent = `${remainingSeconds} seconde${remainingSeconds > 1 ? 's' : ''}`;
		}
	};

	updateCountdown();
	const timer = window.setInterval(() => {
		remainingSeconds -= 1;
		updateCountdown();

		if (remainingSeconds <= 0) {
			window.clearInterval(timer);
			window.location.assign(redirectUrl);
		}
	}, 1000);
};

document.addEventListener('DOMContentLoaded', () => {
	initToaster();
	initSiteSync();
	initPasswordToggles();
	initNetworkToaster();
	initErrorPageRedirect();
});

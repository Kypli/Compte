import '../../styles/user/user.css';

document.addEventListener('DOMContentLoaded', () => {
	const form = document.querySelector('[data-preference-autosave]');
	const status = document.getElementById('preferenceSaveStatus');
	const links = [...document.querySelectorAll('[data-preference-section]')];
	const panels = [...document.querySelectorAll('[data-preference-panel]')];

	const activateSection = (section) => {
		const target = panels.some(panel => panel.dataset.preferencePanel === section)
			? section
			: 'tableau-de-bord';

		links.forEach(link => {
			const active = link.dataset.preferenceSection === target;
			link.classList.toggle('is-active', active);
			link.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		panels.forEach(panel => {
			panel.hidden = panel.dataset.preferencePanel !== target;
		});
	};

	links.forEach(link => {
		link.addEventListener('click', (event) => {
			event.preventDefault();
			const section = link.dataset.preferenceSection;
			activateSection(section);
			window.history.replaceState(window.history.state, '', `#${section}`);
		});
	});
	activateSection(window.location.hash.replace('#', ''));

	if (!form || !status) {
		return;
	}

	let saveTimer;
	let saving = false;
	let pending = false;
	let mascotTimer;
	let statusIdleTimer;

	const setStatus = (state, label) => {
		status.className = `preference-save-status is-${state}`;
		const mascot = status.querySelector('.preference-save-mascot');
		const statusLabel = status.querySelector('.preference-save-label') || mascot?.nextElementSibling;
		if (statusLabel) {
			statusLabel.textContent = label;
		}
		status.setAttribute('aria-busy', state === 'saving' ? 'true' : 'false');
		window.clearTimeout(statusIdleTimer);

		if (mascot) {
			mascot.textContent = '';
			mascot.classList.remove('is-saving', 'is-error', 'is-celebrating');
			mascot.classList.add(`is-${state}`);
			window.clearTimeout(mascotTimer);

			if (state === 'saved') {
				mascot.classList.add('is-celebrating');
				mascotTimer = window.setTimeout(() => {
					mascot.classList.remove('is-celebrating');
				}, 2000);
			}
		}

		if (state === 'saved') {
			statusIdleTimer = window.setTimeout(() => {
				status.classList.add('is-idle');
			}, 6000);
		}
	};

	const save = async () => {
		if (saving || !pending) {
			return;
		}

		pending = false;
		saving = true;
		setStatus('saving', 'Enregistrement...');

		try {
			const response = await fetch(form.action, {
				method: form.method || 'POST',
				body: new FormData(form),
				credentials: 'same-origin',
				headers: {'X-Requested-With': 'XMLHttpRequest'}
			});
			if (!response.ok) {
				throw new Error(`HTTP ${response.status}`);
			}
			setStatus('saved', 'Enregistré');
		} catch (error) {
			setStatus('error', 'Erreur');
			console.error('Erreur lors de l\'enregistrement des préférences', error);
		} finally {
			saving = false;
			if (pending) {
				save();
			}
		}
	};

	form.addEventListener('change', () => {
		pending = true;
		setStatus('saving', 'Enregistrement...');
		window.clearTimeout(saveTimer);
		saveTimer = window.setTimeout(save, 140);
	});
});

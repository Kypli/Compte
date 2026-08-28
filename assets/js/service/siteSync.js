const CHANNEL_NAME = 'compte-site-sync';
const STORAGE_KEY = 'compte-site-sync-message';
const TAB_ID = `${Date.now()}-${Math.random().toString(36).slice(2)}`;

let channel = null;
let refreshToastVisible = false;
const receivedMessages = new Set();

function getToaster(){
	return document.getElementById('toaster');
}

function hideToaster(toaster){
	toaster.classList.remove('toaster-visible');
	toaster.classList.add('toaster-hidden');
	refreshToastVisible = false;
}

function showRefreshToaster(){
	const toaster = getToaster();

	if (!toaster || refreshToastVisible) {
		return;
	}

	refreshToastVisible = true;
	toaster.classList.remove('toaster-hidden');
	toaster.classList.add('toaster-visible', 'toaster-refresh');
	toaster.innerHTML = 'Les données ne sont plus à jour. <a href="#" class="toaster-refresh-link">Actualiser</a>';

	const refreshLink = toaster.querySelector('.toaster-refresh-link');
	if (refreshLink) {
		refreshLink.addEventListener('click', function(event){
			event.preventDefault();
			window.location.reload();
		});
	}
}

function receiveMessage(message){
	if (!message || message.source === TAB_ID || receivedMessages.has(message.id)) {
		return;
	}

	receivedMessages.add(message.id);
	if (receivedMessages.size > 80) {
		receivedMessages.clear();
	}

	window.dispatchEvent(new CustomEvent('compte-site-sync-message', {
		detail: message.detail || {}
	}));
	showRefreshToaster();
}

export function notifySiteUpdate(detail = {}){
	const message = {
		id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
		source: TAB_ID,
		detail: detail
	};

	if (channel) {
		channel.postMessage(message);
	}

	try {
		localStorage.setItem(STORAGE_KEY, JSON.stringify(message));
		localStorage.removeItem(STORAGE_KEY);
	} catch (error) {
	}
}

export function initSiteSync(){
	if ('BroadcastChannel' in window) {
		channel = new BroadcastChannel(CHANNEL_NAME);
		channel.addEventListener('message', function(event){
			receiveMessage(event.data);
		});
	}

	window.addEventListener('storage', function(event){
		if (event.key !== STORAGE_KEY || !event.newValue) {
			return;
		}

		try {
			receiveMessage(JSON.parse(event.newValue));
		} catch (error) {
		}
	});

	const toaster = getToaster();
	if (toaster) {
		toaster.addEventListener('click', function(event){
			if (event.target.closest('.toaster-refresh-link')) {
				return;
			}

			hideToaster(toaster);
		});
	}
}

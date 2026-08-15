// JS
import './modalOperation.js';
import './modalCategory.js';
import { number_format } from '../service/service.js';

// CSS
import '../../styles/compte/compte.css';

let selectedMonth = null
let categoryMove = null
let subCategoryMove = null

////////////
// EXPORT FONCTIONS
////////////

// update tables
export function updateTables(){
	clearCategoryMove()
	clearSubCategoryMove()

	return $.ajax({
		type: "POST",
		url: Routing.generate('compte_tables', {
			id: $('#datas').data('compteid'),
			year: $('#datas').data('year'),
			months: $('#datas').data('monthdisplay')
		}),
		timeout: 15000,
		beforeSend: function(){
			spinner(true)
		},
		success: function(response){
			$('#tables').empty().append(response.render)
			restoreSelectedMonths()
			centerCompactTables()
			$('#last-actions-div').empty().append(response.render_last_actions)
			if (response.render_anomalies !== undefined) {
				$('#anomalies-div').empty().append(response.render_anomalies)
			}
			if (response.render_anomalies_modal !== undefined) {
				const nextContent = $(response.render_anomalies_modal).find('.modal-content')
				$('#modalAnomalies .modal-content').replaceWith(nextContent)
			}
			editSolde(response.solde)
			editSolde(response.soldeFinMensuel, 'FinMois')
			spinner(false)
		},
		error: function(error){
			spinner(false)
			console.log('Erreur ajax: ' + error)
		}
	})
}

function applySelectedMonth(){
	resetSelectedMonthWidths()

	document.querySelectorAll('.compteTable').forEach(table => {
		table.querySelectorAll('.month-selector').forEach(selector => {
			selector.setAttribute('aria-pressed', selector.dataset.month === selectedMonth ? 'true' : 'false')
		})
		table.querySelectorAll('[data-month]').forEach(cell => {
			cell.classList.toggle('month-column-selected', cell.dataset.month === selectedMonth)
		})
	})

	expandDisplayedMonth()
}

function resetSelectedMonthWidths(){
	document.querySelectorAll('.compteTable').forEach(table => {
		table.querySelectorAll('.month-width-expanded').forEach(cell => {
			cell.classList.remove('month-width-expanded')
			cell.style.removeProperty('width')
			cell.style.removeProperty('min-width')
			cell.style.removeProperty('max-width')
		})
	})
}

function expandDisplayedMonth(){
	const selectedMonthHeader = selectedMonth
		? document.querySelector(`.month-selector[data-month="${selectedMonth}"]`)
		: null
	const currentMonthHeader = document.querySelector('.month-selector.current')
	const expandedMonth = selectedMonthHeader
		? selectedMonth
		: $('#datas').data('monthdisplay') === 'current'
			? currentMonthHeader?.dataset.month
			: null

	if (!expandedMonth) {
		return
	}

	document.querySelectorAll('.compteTable').forEach(table => {
		const monthHeader = table.querySelector(`tr:first-child .month-visible[data-month="${expandedMonth}"]`)
		if (!monthHeader) {
			return
		}

		const monthWidth = monthHeader.getBoundingClientRect().width
		const expandedWidth = monthWidth * 1.2

		monthHeader.classList.add('month-width-expanded')
		monthHeader.style.setProperty('width', `${expandedWidth}px`, 'important')
		monthHeader.style.setProperty('min-width', `${expandedWidth}px`, 'important')
		monthHeader.style.setProperty('max-width', `${expandedWidth}px`, 'important')
	})
}

function restoreSelectedMonths(){
	applySelectedMonth()
}

function toggleSelectedMonth(month){
	selectedMonth = selectedMonth === month ? null : month
	applySelectedMonth()
	centerCompactTables()
}

function centerCompactTables(){
	document.querySelectorAll('.compte-table-viewport').forEach(viewport => {
		const table = viewport.querySelector('.month-display-compact')
		if (!table) {
			return
		}

		const visibleMonths = table.querySelectorAll('tr:first-child .month-visible')
		if (!visibleMonths.length) {
			return
		}

		table.style.marginLeft = '0px'
		table.style.marginRight = '0px'

		const firstMonth = visibleMonths[0]
		const lastMonth = visibleMonths[visibleMonths.length - 1]
		const monthsCenter = (firstMonth.offsetLeft + lastMonth.offsetLeft + lastMonth.offsetWidth) / 2
		const viewportCenter = viewport.clientWidth / 2
		const tableWidth = table.offsetWidth

		const leftSpace = Math.max(0, viewportCenter - monthsCenter)
		const rightSpace = Math.max(0, viewportCenter - (tableWidth - monthsCenter))
		table.style.marginLeft = `${leftSpace}px`
		table.style.marginRight = `${rightSpace}px`
		viewport.scrollLeft = leftSpace + monthsCenter - viewportCenter
	})
}

function startCategoryMove(handle){
	const table = handle.closest('.compteTable')
	if (!table) {
		return
	}

	clearCategoryMove()
	categoryMove = {
		categoryId: handle.dataset.categoryId,
		handle,
		table
	}
	table.classList.add('category-sort-active')
	handle.setAttribute('aria-grabbed', 'true')
	table.querySelectorAll(`[data-category-row="${handle.dataset.categoryId}"]`).forEach(row => {
		row.classList.add('category-sort-source')
	})
	table.querySelectorAll('[data-category-drop-before], [data-category-drop-end]').forEach(target => {
		target.setAttribute('tabindex', '0')
	})
}

function startSubCategoryMove(handle){
	const table = handle.closest('.compteTable')
	if (!table) {
		return
	}

	clearCategoryMove()
	clearSubCategoryMove()
	subCategoryMove = {
		subCategoryId: handle.dataset.subcategoryId,
		categoryId: handle.dataset.subcategoryCategoryId,
		handle,
		table
	}
	table.classList.add('subcategory-sort-active')
	handle.setAttribute('aria-grabbed', 'true')
	table.querySelectorAll(`[data-subcategory-drop-before="${handle.dataset.subcategoryId}"]`).forEach(cell => {
		cell.closest('tr')?.classList.add('subcategory-sort-source')
	})
	table.querySelectorAll('[data-subcategory-drop-before], [data-subcategory-drop-end]').forEach(target => {
		if (
			target.dataset.subcategoryDropBefore !== handle.dataset.subcategoryId
			&& (
				target.dataset.subcategoryCategoryId === handle.dataset.subcategoryCategoryId
				|| target.dataset.subcategoryDropEnd === handle.dataset.subcategoryCategoryId
			)
		) {
			target.setAttribute('tabindex', '0')
			target.classList.add('is-subcategory-drop-available')
		}
	})
}

function clearCategoryMove(){
	if (!categoryMove) {
		return
	}

	categoryMove.handle?.setAttribute('aria-grabbed', 'false')
	categoryMove.table?.classList.remove('category-sort-active')
	categoryMove.table?.querySelectorAll('.category-sort-source, .is-category-drop-target').forEach(element => {
		element.classList.remove('category-sort-source', 'is-category-drop-target')
	})
	categoryMove.table?.querySelectorAll('[data-category-drop-before]').forEach(target => {
		target.removeAttribute('tabindex')
	})
	categoryMove.table?.querySelectorAll('[data-category-drop-end]').forEach(target => {
		target.setAttribute('tabindex', '-1')
	})
	categoryMove = null
}

function clearSubCategoryMove(){
	if (!subCategoryMove) {
		return
	}

	subCategoryMove.handle?.setAttribute('aria-grabbed', 'false')
	subCategoryMove.table?.classList.remove('subcategory-sort-active')
	subCategoryMove.table?.querySelectorAll('.subcategory-sort-source, .is-subcategory-drop-target, .is-subcategory-drop-available').forEach(element => {
		element.classList.remove('subcategory-sort-source', 'is-subcategory-drop-target', 'is-subcategory-drop-available')
	})
	subCategoryMove.table?.querySelectorAll('[data-subcategory-drop-before], [data-subcategory-drop-end]').forEach(target => {
		target.removeAttribute('tabindex')
	})
	subCategoryMove = null
}

function moveCategory(target){
	if (!categoryMove || target.closest('.compteTable') !== categoryMove.table) {
		return
	}

	const table = categoryMove.table
	const categoryId = categoryMove.categoryId
	const beforeId = target.dataset.categoryDropBefore ?? ''
	if (beforeId === categoryId) {
		clearCategoryMove()
		return
	}

	clearCategoryMove()
	spinner(true)
	$.ajax({
		type: 'POST',
		url: table.dataset.categoryReorderUrl,
		data: {
			_token: table.dataset.categoryReorderToken,
			category: categoryId,
			before: beforeId
		},
		timeout: 15000
	})
		.done(function(){
			updateTables()
		})
		.fail(function(error){
			spinner(false)
			console.log('Erreur lors du déplacement de la catégorie: ' + error)
		})
}

function moveSubCategory(target){
	if (!subCategoryMove || target.closest('.compteTable') !== subCategoryMove.table) {
		return
	}
	if (
		target.dataset.subcategoryDropBefore === subCategoryMove.subCategoryId
		|| (
			target.dataset.subcategoryCategoryId !== subCategoryMove.categoryId
			&& target.dataset.subcategoryDropEnd !== subCategoryMove.categoryId
		)
	) {
		clearSubCategoryMove()
		return
	}

	const table = subCategoryMove.table
	const subCategoryId = subCategoryMove.subCategoryId
	const beforeId = target.dataset.subcategoryDropBefore ?? ''
	if (beforeId === subCategoryId) {
		clearSubCategoryMove()
		return
	}

	clearSubCategoryMove()
	spinner(true)
	$.ajax({
		type: 'POST',
		url: table.dataset.subcategoryReorderUrl,
		data: {
			_token: table.dataset.subcategoryReorderToken,
			subcategory: subCategoryId,
			category: target.dataset.subcategoryCategoryId ?? target.dataset.subcategoryDropEnd,
			before: beforeId
		},
		timeout: 15000
	})
		.done(function(){
			updateTables()
		})
		.fail(function(error){
			spinner(false)
			console.log('Erreur lors du deplacement de la sous-categorie: ' + error)
		})
}


////////////
// FONCTIONS
////////////

// Color soldeActuel
function editSolde(solde, text = 'Actuel'){

	$('#solde'+text+'Nb').text(number_format(solde, 2, ',', ' '))

	let 
		decouvert = $('#datas').data('decouvert'),
		hideAlert = true
	;

	if (solde == 0){
		$('#solde'+text)
			.addClass('total_month_full_neutre')
			.removeClass('total_month_full_pos')
			.removeClass('total_month_full_neg')
			.removeClass('total_month_full_dec')

	} else if(solde > 0){
		$('#solde'+text)
			.addClass('total_month_full_pos')
			.removeClass('total_month_full_neutre')
			.removeClass('total_month_full_neg')
			.removeClass('total_month_full_dec')

	} else if (solde < decouvert){
		hideAlert = false
		$('#solde'+text)
			.addClass('total_month_full_neg')
			.removeClass('total_month_full_pos')
			.removeClass('total_month_full_neutre')
			.removeClass('total_month_full_dec')

	} else {
		$('#solde'+text)
			.addClass('total_month_full_dec')
			.removeClass('total_month_full_pos')
			.removeClass('total_month_full_neutre')
			.removeClass('total_month_full_neg')
	}

	$('#solde'+text+'Alert').toggleClass('hide', hideAlert)
}

// Spinner
function spinner(etat){
	etat
		? $('#show_spinner').removeClass('hide').show()
		: $('.spinner').addClass('hide').hide()
}

$(document).ready(function(){
	selectedMonth = document.querySelector('.month-selector.current')?.dataset.month ?? null
	applySelectedMonth()
	centerCompactTables()
	const accountPreferencesButton = document.getElementById('accountPreferencesButton')
	const accountPreferencesPanel = document.getElementById('accountPreferencesPanel')
	const accountPreferencesForm = document.querySelector('[data-account-preference-autosave]')
	const accountPreferencesStatus = document.querySelector('[data-account-preference-status]')
	const datasElement = document.getElementById('datas')
	const tutorialButton = document.getElementById('tutorialButton')
	const tutorialModal = document.getElementById('modalAccountTutorial')
	const startAccountTourButton = document.getElementById('startAccountTourButton')
	let accountPreferencesSaveTimer
	let accountPreferencesRequest
	let accountTourIndex = 0
	let accountTourSteps = []
	let accountTourTarget = null
	let accountTourOverlay = null
	let accountTourPopover = null
	let accountTourRenderToken = 0

	const setAccountPreferenceStatus = function(label, state = 'saved'){
		if (!accountPreferencesStatus) {
			return
		}

		accountPreferencesStatus.textContent = label
		accountPreferencesStatus.classList.remove('is-saving', 'is-saved', 'is-error')
		accountPreferencesStatus.classList.add(`is-${state}`)
	}

	const closeAccountPreferences = function(){
		if (!accountPreferencesButton || !accountPreferencesPanel) {
			return
		}

		accountPreferencesPanel.hidden = true
		accountPreferencesButton.setAttribute('aria-expanded', 'false')
	}

	const applyAccountPreferences = function(preferences){
		if (!preferences) {
			return
		}

		document.body.classList.remove('account-background-green', 'account-background-light', 'account-background-grey')
		document.body.classList.add(`account-background-${preferences.accountBackground || 'green'}`)

		const datas = document.getElementById('datas')
		if (!datas) {
			return
		}

		Array.from(datas.classList).forEach(className => {
			if (className.startsWith('table-palette-')) {
				datas.classList.remove(className)
			}
		})
		datas.classList.add(`table-palette-${preferences.tablePalette || 'classic'}`)
		datas.classList.toggle('hide-editable-border', preferences.showEditableBorder === false)

		if (typeof preferences.compteGenreShow === 'boolean') {
			updateTables()
		}
	}

	if (accountPreferencesButton && accountPreferencesPanel) {
		accountPreferencesButton.setAttribute('title', 'Ouvrir les préférences des comptes')
		accountPreferencesButton.setAttribute('aria-controls', 'accountPreferencesPanel')
		accountPreferencesButton.setAttribute('aria-expanded', 'false')
		accountPreferencesButton.addEventListener('click', function(event){
			event.preventDefault()
			const opens = accountPreferencesPanel.hidden
			accountPreferencesPanel.hidden = !opens
			accountPreferencesButton.setAttribute('aria-expanded', opens ? 'true' : 'false')
		})
		accountPreferencesPanel.addEventListener('mouseleave', function(){
			closeAccountPreferences()
		})
		document.addEventListener('click', function(event){
			if (
				accountPreferencesPanel.hidden
				|| accountPreferencesPanel.contains(event.target)
				|| accountPreferencesButton.contains(event.target)
			) {
				return
			}

			closeAccountPreferences()
		})
		document.addEventListener('keydown', function(event){
			if ('Escape' !== event.key || accountPreferencesPanel.hidden) {
				return
			}

			closeAccountPreferences()
			accountPreferencesButton.focus()
		})
	}

	if (accountPreferencesForm) {
		const saveAccountPreferences = function(){
			window.clearTimeout(accountPreferencesSaveTimer)
			setAccountPreferenceStatus('Enregistrement...', 'saving')

			if (accountPreferencesRequest) {
				accountPreferencesRequest.abort()
			}

			accountPreferencesRequest = new AbortController()
			fetch(accountPreferencesForm.action, {
				method: accountPreferencesForm.method || 'POST',
				body: new FormData(accountPreferencesForm),
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				},
				signal: accountPreferencesRequest.signal
			})
				.then(response => {
					if (!response.ok) {
						throw new Error('Preference save failed')
					}

					return response.json()
				})
				.then(payload => {
					applyAccountPreferences(payload.preferences)
					setAccountPreferenceStatus('Enregistré', 'saved')
				})
				.catch(error => {
					if ('AbortError' === error.name) {
						return
					}

					setAccountPreferenceStatus('Erreur', 'error')
				})
		}

		accountPreferencesForm.addEventListener('change', function(){
			window.clearTimeout(accountPreferencesSaveTimer)
			accountPreferencesSaveTimer = window.setTimeout(saveAccountPreferences, 160)
		})
	}
	const markAccountTutorialSeen = function(){
		if (!datasElement || datasElement.dataset.accountTutorialSeen === '1') {
			return
		}

		const formData = new FormData()
		formData.append('_token', datasElement.dataset.accountTutorialSeenToken || '')

		fetch(datasElement.dataset.accountTutorialSeenUrl, {
			method: 'POST',
			body: formData,
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(response => {
				if (!response.ok) {
					throw new Error('Tutorial seen save failed')
				}

				datasElement.dataset.accountTutorialSeen = '1'
			})
			.catch(() => {})
	}

	const openAccountTutorial = function(markSeen = false){
		if (!tutorialModal) {
			return
		}

		if (window.bootstrap?.Modal) {
			window.bootstrap.Modal.getOrCreateInstance(tutorialModal).show()
		} else if (window.$ && typeof $(tutorialModal).modal === 'function') {
			$(tutorialModal).modal('show')
		} else {
			tutorialModal.classList.add('show')
			tutorialModal.style.display = 'block'
			tutorialModal.removeAttribute('aria-hidden')
		}

		if (markSeen) {
			markAccountTutorialSeen()
		}
	}

	const closeAccountTutorial = function(){
		if (!tutorialModal) {
			return
		}

		if (window.bootstrap?.Modal) {
			window.bootstrap.Modal.getOrCreateInstance(tutorialModal).hide()
		} else if (window.$ && typeof $(tutorialModal).modal === 'function') {
			$(tutorialModal).modal('hide')
		} else {
			tutorialModal.classList.remove('show')
			tutorialModal.style.display = 'none'
			tutorialModal.setAttribute('aria-hidden', 'true')
		}
	}

	const accountTourEditableCellSelector = '.compteTable .edit td[data-scid][data-month]:not(.counterEdit):not(.td_category_libelle):not(.td_subcategory_libelle)'

	const accountTourDefinitions = [
		{
			selector: '.account-header-actions',
			title: 'Aide et réglages',
			text: "Les préférences règlent l'apparence, la légende explique les codes visuels, et le bouton Tutoriel relance cette aide quand tu veux."
		},
		{
			selector: '.balance-summary',
			title: 'Soldes du compte',
			text: "Cette zone donne le solde actuel, le solde prévu en fin de mois et le découvert autorisé. Les alertes apparaissent quand le découvert est dépassé."
		},
		{
			selector: '#anomalies-div',
			title: 'Anomalies à corriger',
			text: "Le compteur d'anomalies signale les opérations anticipées en retard. Le bouton ouvre les actions de correction disponibles."
		},
		{
			selector: '#last-actions-div',
			title: 'Dernières actions',
			text: "Cette zone garde l'historique récent et propose l'annulation ou la restauration des dernières modifications disponibles."
		},
		{
			selector: '.account-view-controls',
			title: 'Période et affichage',
			text: "Change l'année affichée, puis choisis la largeur de lecture: année complète, période compacte ou mois courant."
		},
		{
			selector: '.compteTable',
			title: 'Tableaux revenus et dépenses',
			text: "Les tableaux regroupent le budget par catégorie, sous-catégorie et mois. Les couleurs séparent le passé, le mois courant et les mois à venir."
		},
		{
			selector: '.month-selector.current, .month-selector',
			title: 'Focus sur un mois',
			text: "Clique sur un mois pour le mettre en évidence dans toutes les zones du tableau et comparer plus vite les lignes."
		},
		{
			selector: accountTourEditableCellSelector,
			title: 'Cellules modifiables',
			text: "Les cellules des zones modifiables ouvrent la saisie des opérations. Les montants anticipés permettent de prévoir avant validation réelle."
		},
		{
			selector: '#modalOperation .modal-content',
			title: "Fenêtre d'opérations",
			text: "Après le clic sur une cellule, cette fenêtre liste les opérations du mois pour la sous-catégorie choisie. Le tutoriel l'ouvre ici sans enregistrer de changement.",
			before: () => openOperationModalForTour(),
			modal: true
		},
		{
			selector: '#modalOperation .inputNumber, #modalOperation .inputAnticipe',
			title: 'Montants réel et anticipé',
			text: "Le montant réel correspond à une opération confirmée. Le montant anticipé sert à préparer une dépense ou un revenu prévu avant sa validation.",
			before: () => openOperationModalForTour(),
			modal: true
		},
		{
			selector: '#modalOperation .inputDay',
			title: "Date de l'opération",
			text: "Le jour rattache l'opération au bon moment du mois. Il permet aussi de repérer les opérations anticipées devenues en retard.",
			before: () => openOperationModalForTour(),
			modal: true
		},
		{
			selector: '#modalOperation .inputComment',
			title: 'Commentaire',
			text: "Le commentaire sert de repère lisible: bénéficiaire, facture, précision personnelle ou toute information utile pour retrouver l'opération.",
			before: () => openOperationModalForTour(),
			modal: true
		},
		{
			selector: '#modalOperation .td_actions',
			title: 'Actions de ligne',
			text: "Les actions de ligne permettent de revenir en affichage simple, annuler une modification, supprimer une opération ou réactiver une ligne supprimée.",
			before: () => openOperationModalForTour(),
			modal: true
		},
		{
			selector: '#modalOperation .modal-footer',
			title: 'Enregistrer ou fermer',
			text: "Le bouton d'enregistrement apparaît seulement quand une modification est détectée. Fermer sans enregistrer permet de sortir de la fenêtre sans toucher aux données.",
			before: () => openOperationModalForTour(),
			modal: true
		},
		{
			selector: '.category-drag-handle, .subcategory-drag-handle',
			title: 'Réordonner le budget',
			text: "Les poignées de déplacement servent à réorganiser les catégories et sous-catégories. Après un déplacement, l'action peut être annulée."
		}
	]
	const ensureAccountTourElements = function(){
		if (!accountTourOverlay) {
			accountTourOverlay = document.createElement('div')
			accountTourOverlay.className = 'account-tour-overlay'
			accountTourOverlay.setAttribute('aria-hidden', 'true')
			document.body.appendChild(accountTourOverlay)
		}
		if (!accountTourPopover) {
			accountTourPopover = document.createElement('section')
			accountTourPopover.className = 'account-tour-popover'
			accountTourPopover.setAttribute('role', 'dialog')
			accountTourPopover.setAttribute('aria-live', 'polite')
			document.body.appendChild(accountTourPopover)
		}
	}

	const getVisibleTourElement = function(selector){
		return Array.from(document.querySelectorAll(selector)).find(element => {
			const rect = element.getBoundingClientRect()

			return rect.width > 0 && rect.height > 0
		})
	}

	const waitForTourElement = function(selector, timeout = 2400){
		const startedAt = Date.now()

		return new Promise(resolve => {
			const findElement = function(){
				const element = getVisibleTourElement(selector)
				if (element || Date.now() - startedAt >= timeout) {
					resolve(element || null)
					return
				}

				window.setTimeout(findElement, 80)
			}

			findElement()
		})
	}

	const closeOperationModalForTour = function(){
		const operationModal = document.getElementById('modalOperation')
		if (!operationModal) {
			return
		}

		operationModal.classList.remove('account-tour-modal-active')
		if (!operationModal.classList.contains('show') && operationModal.style.display !== 'block') {
			return
		}

		if (window.$ && typeof $(operationModal).modal === 'function') {
			$(operationModal).modal('hide')
		} else if (window.bootstrap?.Modal) {
			window.bootstrap.Modal.getOrCreateInstance(operationModal).hide()
		} else {
			operationModal.classList.remove('show')
			operationModal.style.display = 'none'
			operationModal.setAttribute('aria-hidden', 'true')
		}

		window.setTimeout(function(){
			operationModal.classList.remove('account-tour-modal-active')
			if (!operationModal.classList.contains('show') && operationModal.style.display !== 'block') {
				return
			}

			operationModal.classList.remove('show')
			operationModal.style.display = 'none'
			operationModal.setAttribute('aria-hidden', 'true')
			operationModal.removeAttribute('aria-modal')
			if (!document.querySelector('.modal.show')) {
				document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove())
				document.body.classList.remove('modal-open')
				document.body.style.removeProperty('padding-right')
			}
		}, 180)
	}

	const openOperationModalForTour = async function(){
		let operationModal = document.getElementById('modalOperation')
		if (!operationModal) {
			return
		}

		const hasOperationContent = getVisibleTourElement('#modalOperation .tr_ope, #modalOperation .tr_add')
		if (!hasOperationContent) {
			const editableCell = getVisibleTourElement(accountTourEditableCellSelector)
			if (!editableCell) {
				return
			}

			editableCell.click()
			await waitForTourElement('#modalOperation .tr_ope, #modalOperation .tr_add', 3200)
		}

		operationModal = document.getElementById('modalOperation')
		operationModal?.classList.add('account-tour-modal-active')

		if (!getVisibleTourElement('#modalOperation .inputDay')) {
			const fullEditButton = document.querySelector('#modalOperation #butFullToggleFormMod')
			if (fullEditButton && !fullEditButton.disabled && fullEditButton.value !== '1') {
				fullEditButton.click()
			} else {
				const editableOperationCell = getVisibleTourElement('#modalOperation .tr_ope .td_number, #modalOperation .tr_ope .td_anticipe, #modalOperation .tr_ope .td_date, #modalOperation .tr_ope .td_comment')
				editableOperationCell?.click()
			}

			await waitForTourElement('#modalOperation .inputDay', 1200)
		}
	}

	const buildAccountTourSteps = function(){
		return accountTourDefinitions
			.map(step => ({
				...step,
				target: getVisibleTourElement(step.selector)
			}))
			.filter(step => step.target || step.before)
	}

	const clearAccountTourTarget = function(){
		if (!accountTourTarget) {
			return
		}

		accountTourTarget.classList.remove('account-tour-highlight')
		accountTourTarget = null
	}

	const stopAccountTour = function(){
		accountTourRenderToken++
		clearAccountTourTarget()
		closeOperationModalForTour()
		document.body.classList.remove('account-tour-active')
		accountTourOverlay?.remove()
		accountTourPopover?.remove()
		accountTourOverlay = null
		accountTourPopover = null
		accountTourSteps = []
		window.removeEventListener('resize', positionAccountTourPopover)
		window.removeEventListener('scroll', positionAccountTourPopover, true)
		document.removeEventListener('keydown', stopAccountTourOnEscape)
	}

	function stopAccountTourOnEscape(event){
		if ('Escape' === event.key) {
			stopAccountTour()
		}
	}

	function positionAccountTourPopover(){
		if (!accountTourTarget || !accountTourPopover) {
			return
		}

		const rect = accountTourTarget.getBoundingClientRect()
		const gap = 14
		const popoverWidth = Math.min(360, window.innerWidth - 24)
		const measuredHeight = accountTourPopover.offsetHeight || 220
		let top = rect.bottom + gap
		let left = rect.left + (rect.width / 2) - (popoverWidth / 2)

		if (top + measuredHeight > window.innerHeight - 12) {
			top = rect.top - measuredHeight - gap
		}
		if (top < 12) {
			top = 12
		}

		left = Math.max(12, Math.min(left, window.innerWidth - popoverWidth - 12))
		accountTourPopover.style.width = `${popoverWidth}px`
		accountTourPopover.style.top = `${top}px`
		accountTourPopover.style.left = `${left}px`
	}

	const renderAccountTourStep = async function(direction = 1){
		const renderToken = ++accountTourRenderToken
		const step = accountTourSteps[accountTourIndex]
		if (!step) {
			stopAccountTour()
			return
		}

		clearAccountTourTarget()
		if (!step.modal) {
			closeOperationModalForTour()
		}
		if (step.before) {
			await step.before()
		}
		if (renderToken !== accountTourRenderToken) {
			return
		}

		step.target = getVisibleTourElement(step.selector)
		if (!step.target) {
			accountTourSteps.splice(accountTourIndex, 1)
			if (direction < 0) {
				accountTourIndex = Math.max(0, accountTourIndex - 1)
			}
			if (accountTourIndex >= accountTourSteps.length) {
				stopAccountTour()
				return
			}

			renderAccountTourStep(direction)
			return
		}

		accountTourTarget = step.target
		accountTourTarget.classList.add('account-tour-highlight')
		accountTourTarget.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' })

		const isLastStep = accountTourIndex === accountTourSteps.length - 1
		accountTourPopover.innerHTML = `
			<div class="account-tour-progress">Étape ${accountTourIndex + 1} / ${accountTourSteps.length}</div>
			<h5>${step.title}</h5>
			<p>${step.text}</p>
			<div class="account-tour-actions">
				<button type="button" class="account-tour-stop">Arrêter</button>
				<div class="account-tour-navigation">
					<button type="button" class="account-tour-previous"${accountTourIndex === 0 ? ' disabled' : ''}>Précédent</button>
					<button type="button" class="account-tour-next">${isLastStep ? 'Terminer' : 'Suivant'}</button>
				</div>
			</div>
		`
		accountTourPopover.querySelector('.account-tour-stop').addEventListener('click', stopAccountTour)
		accountTourPopover.querySelector('.account-tour-previous').addEventListener('click', function(){
			if (accountTourIndex === 0) {
				return
			}

			accountTourIndex--
			renderAccountTourStep(-1)
		})
		accountTourPopover.querySelector('.account-tour-next').addEventListener('click', function(){
			if (isLastStep) {
				stopAccountTour()
				return
			}

			accountTourIndex++
			renderAccountTourStep(1)
		})

		window.setTimeout(positionAccountTourPopover, 220)
	}

	const startAccountTour = function(){
		accountTourSteps = buildAccountTourSteps()
		if (!accountTourSteps.length) {
			return
		}

		closeAccountTutorial()
		ensureAccountTourElements()
		document.body.classList.add('account-tour-active')
		accountTourIndex = 0
		renderAccountTourStep(1)
		window.addEventListener('resize', positionAccountTourPopover)
		window.addEventListener('scroll', positionAccountTourPopover, true)
		document.addEventListener('keydown', stopAccountTourOnEscape)
	}

	if (tutorialButton) {
		tutorialButton.addEventListener('click', function(){
			openAccountTutorial(false)
		})
	}

	if (startAccountTourButton) {
		startAccountTourButton.addEventListener('click', function(){
			window.setTimeout(startAccountTour, 180)
		})
	}

	if (datasElement?.dataset.accountTutorialSeen === '0') {
		window.setTimeout(function(){
			openAccountTutorial(true)
		}, 250)
	}
	const budgetSwitcher = document.querySelector('.budget-switcher')
	if (budgetSwitcher) {
		const button = budgetSwitcher.querySelector('#budgetSwitcherButton')
		const menu = budgetSwitcher.querySelector('#budgetSwitcherMenu')
		const closeBudgetSwitcher = function(){
			menu.hidden = true
			button.setAttribute('aria-expanded', 'false')
		}

		button.addEventListener('click', function(){
			const opens = menu.hidden
			menu.hidden = !opens
			button.setAttribute('aria-expanded', opens ? 'true' : 'false')
		})
		document.addEventListener('click', function(event){
			if (!budgetSwitcher.contains(event.target)) {
				closeBudgetSwitcher()
			}
		})
		budgetSwitcher.addEventListener('keydown', function(event){
			if ('Escape' === event.key) {
				closeBudgetSwitcher()
				button.focus()
			}
		})
	}
	const yearPicker = document.getElementById('yearPicker')
	const yearNavigationForm = document.getElementById('yearNavigationForm')
	const yearPickerMenu = document.getElementById('yearPickerOptions')
	if (yearPicker && yearNavigationForm && yearPickerMenu) {
		const initialYear = yearPicker.value
		const yearPickerOptions = Array.from(yearPickerMenu.querySelectorAll('.year-picker-option'))
		const openYearPicker = function(){
			yearPickerMenu.hidden = false
			yearPicker.setAttribute('aria-expanded', 'true')
		}
		const closeYearPicker = function(){
			yearPickerMenu.hidden = true
			yearPicker.setAttribute('aria-expanded', 'false')
		}

		yearPicker.addEventListener('click', function(){
			openYearPicker()
		})

		yearPicker.addEventListener('focus', openYearPicker)

		yearPicker.addEventListener('input', function(){
			yearPicker.setCustomValidity('')
			openYearPicker()
		})

		yearPicker.addEventListener('keydown', function(event){
			if ('Enter' === event.key) {
				event.preventDefault()
				yearNavigationForm.requestSubmit()
			} else if ('Escape' === event.key) {
				closeYearPicker()
				yearPicker.select()
			} else if ('ArrowDown' === event.key && yearPickerOptions.length) {
				event.preventDefault()
				openYearPicker()
				yearPickerOptions[0].focus()
			}
		})

		yearPicker.addEventListener('blur', function(event){
			if (event.relatedTarget && yearPickerMenu.contains(event.relatedTarget)) {
				return
			}

			if (yearPicker.value !== initialYear && yearPicker.checkValidity()) {
				yearNavigationForm.requestSubmit()
			}
		})

		yearPickerMenu.addEventListener('click', function(event){
			const option = event.target.closest('.year-picker-option')
			if (!option) {
				return
			}

			yearPicker.value = option.dataset.year
			yearNavigationForm.requestSubmit()
		})

		yearPickerMenu.addEventListener('keydown', function(event){
			const option = event.target.closest('.year-picker-option')
			if (!option || !['ArrowDown', 'ArrowUp', 'Escape'].includes(event.key)) {
				return
			}

			event.preventDefault()
			if ('Escape' === event.key) {
				closeYearPicker()
				yearPicker.focus()
				return
			}

			const currentIndex = yearPickerOptions.indexOf(option)
			const offset = 'ArrowDown' === event.key ? 1 : -1
			const nextIndex = (currentIndex + offset + yearPickerOptions.length) % yearPickerOptions.length
			yearPickerOptions[nextIndex].focus()
		})

		document.addEventListener('click', function(event){
			if (!event.target.closest('.year-picker-field')) {
				closeYearPicker()
			}
		})

		yearNavigationForm.addEventListener('submit', function(event){
			if (!/^[0-9]{4}$/.test(yearPicker.value)) {
				event.preventDefault()
				yearPicker.setCustomValidity('Saisissez une année sur 4 chiffres.')
				yearPicker.reportValidity()
			}
		})
	}
	let resizeFrame
	window.addEventListener('resize', function(){
		window.cancelAnimationFrame(resizeFrame)
		resizeFrame = window.requestAnimationFrame(function(){
			applySelectedMonth()
			centerCompactTables()
		})
	})

	////////////
	// ON EVENTS
	////////////
	$('#monthDisplay').on('change', function(){
		const select = this
		const datas = $('#datas')
		const previousMonthDisplay = datas.data('monthdisplay')
		const monthDisplay = select.value
		const url = new URL(window.location.href)

		datas
			.data('monthdisplay', monthDisplay)
			.attr('data-monthdisplay', monthDisplay)
		url.searchParams.set('months', monthDisplay)
		window.history.replaceState(window.history.state, '', url)
		select.disabled = true

		updateTables()
			.fail(function(){
				datas
					.data('monthdisplay', previousMonthDisplay)
					.attr('data-monthdisplay', previousMonthDisplay)
				select.value = previousMonthDisplay
				url.searchParams.set('months', previousMonthDisplay)
				window.history.replaceState(window.history.state, '', url)
			})
			.always(function(){
				select.disabled = false
			})
	})

	$('body').on('click', '.undo-last-action', function(){
		const button = $(this)

		button.prop('disabled', true)
		spinner(true)
		$.ajax({
			type: 'POST',
			url: button.data('url'),
			data: {
				_token: button.data('token')
			},
			timeout: 15000,
			success: function(){
				updateTables()
			},
			error: function(error){
				button.prop('disabled', false)
				spinner(false)
				console.log("Erreur lors de l'annulation: " + error)
			}
		})
	})

	$('body').on('click', '.last-action-trigger', function(event){
		event.preventDefault()
		const container = this.closest('.last-actions')
		const locked = !container.classList.contains('is-locked')

		document.querySelectorAll('.last-actions.is-locked').forEach(lastActions => {
			lastActions.classList.remove('is-locked')
			lastActions.querySelector('.last-action-trigger')?.setAttribute('aria-expanded', 'false')
		})
		container.classList.toggle('is-locked', locked)
		this.setAttribute('aria-expanded', locked ? 'true' : 'false')
	})

	$(document).on('click', function(event){
		if ($(event.target).closest('.last-actions').length) {
			return
		}

		document.querySelectorAll('.last-actions.is-locked').forEach(lastActions => {
			lastActions.classList.remove('is-locked')
			lastActions.querySelector('.last-action-trigger')?.setAttribute('aria-expanded', 'false')
		})
	})

	$('body').on('click', '.category-drag-handle', function(event){
		event.preventDefault()
		event.stopImmediatePropagation()

		if (categoryMove?.handle === this) {
			clearCategoryMove()
			return
		}

		startCategoryMove(this)
	})

	$('body').on('click', '.subcategory-drag-handle', function(event){
		event.preventDefault()
		event.stopImmediatePropagation()

		if (subCategoryMove?.handle === this) {
			clearSubCategoryMove()
			return
		}

		startSubCategoryMove(this)
	})

	$('body').on('dragstart', '.category-drag-handle', function(event){
		startCategoryMove(this)
		const dataTransfer = event.originalEvent.dataTransfer
		dataTransfer.effectAllowed = 'move'
		dataTransfer.setData('text/plain', this.dataset.categoryId)
	})

	$('body').on('dragstart', '.subcategory-drag-handle', function(event){
		startSubCategoryMove(this)
		const dataTransfer = event.originalEvent.dataTransfer
		dataTransfer.effectAllowed = 'move'
		dataTransfer.setData('text/plain', this.dataset.subcategoryId)
	})

	$('body').on('dragover dragenter', '[data-category-drop-before], [data-category-drop-end]', function(event){
		if (!categoryMove || this.closest('.compteTable') !== categoryMove.table) {
			return
		}

		event.preventDefault()
		categoryMove.table.querySelectorAll('.is-category-drop-target').forEach(target => {
			target.classList.remove('is-category-drop-target')
		})
		this.classList.add('is-category-drop-target')
		event.originalEvent.dataTransfer.dropEffect = 'move'
	})

	$('body').on('dragover dragenter', '[data-subcategory-drop-before], [data-subcategory-drop-end]', function(event){
		if (!subCategoryMove || this.closest('.compteTable') !== subCategoryMove.table) {
			return
		}
		if (
			this.dataset.subcategoryDropBefore === subCategoryMove.subCategoryId
			|| (
				this.dataset.subcategoryCategoryId !== subCategoryMove.categoryId
				&& this.dataset.subcategoryDropEnd !== subCategoryMove.categoryId
			)
		) {
			return
		}

		event.preventDefault()
		subCategoryMove.table.querySelectorAll('.is-subcategory-drop-target').forEach(target => {
			target.classList.remove('is-subcategory-drop-target')
		})
		this.classList.add('is-subcategory-drop-target')
		event.originalEvent.dataTransfer.dropEffect = 'move'
	})

	$('body').on('dragleave', '[data-category-drop-before], [data-category-drop-end]', function(event){
		if (!this.contains(event.relatedTarget)) {
			this.classList.remove('is-category-drop-target')
		}
	})

	$('body').on('dragleave', '[data-subcategory-drop-before], [data-subcategory-drop-end]', function(event){
		if (!this.contains(event.relatedTarget)) {
			this.classList.remove('is-subcategory-drop-target')
		}
	})

	$('body').on('drop', '[data-category-drop-before], [data-category-drop-end]', function(event){
		if (!categoryMove) {
			return
		}

		event.preventDefault()
		event.stopPropagation()
		moveCategory(this)
	})

	$('body').on('drop', '[data-subcategory-drop-before], [data-subcategory-drop-end]', function(event){
		if (!subCategoryMove) {
			return
		}

		event.preventDefault()
		event.stopPropagation()
		moveSubCategory(this)
	})

	$('body').on('keydown', '[data-category-drop-before], [data-category-drop-end]', function(event){
		if (!categoryMove || !['Enter', ' '].includes(event.key)) {
			return
		}

		event.preventDefault()
		moveCategory(this)
	})

	$('body').on('keydown', '[data-subcategory-drop-before], [data-subcategory-drop-end]', function(event){
		if (!subCategoryMove || !['Enter', ' '].includes(event.key)) {
			return
		}

		event.preventDefault()
		moveSubCategory(this)
	})

	$('body').on('dragend', '.category-drag-handle', clearCategoryMove)
	$('body').on('dragend', '.subcategory-drag-handle', clearSubCategoryMove)
	$(document).on('keydown', function(event){
		if ('Escape' === event.key && categoryMove) {
			clearCategoryMove()
		}
		if ('Escape' === event.key && subCategoryMove) {
			clearSubCategoryMove()
		}
	})
	document.addEventListener('click', function(event){
		if (!categoryMove || event.target.closest('.category-drag-handle')) {
			return
		}

		const target = event.target.closest('[data-category-drop-before], [data-category-drop-end]')
		if (!target || target.closest('.compteTable') !== categoryMove.table) {
			return
		}

		event.preventDefault()
		event.stopImmediatePropagation()
		moveCategory(target)
	}, true)
	document.addEventListener('click', function(event){
		if (!subCategoryMove || event.target.closest('.subcategory-drag-handle')) {
			return
		}

		const target = event.target.closest('[data-subcategory-drop-before], [data-subcategory-drop-end]')
		if (!target || target.closest('.compteTable') !== subCategoryMove.table) {
			return
		}

		event.preventDefault()
		event.stopImmediatePropagation()
		moveSubCategory(target)
	}, true)

	$('body').on('click', '.month-selector', function(){
		toggleSelectedMonth(this.dataset.month)
	})

	$('body').on('click', '.resolve-anomaly-button', function(){
		const button = $(this)
		button.prop('disabled', true)
		spinner(true)

		$.ajax({
			type: 'POST',
			url: button.data('url'),
			data: {
				_token: button.data('token'),
				resolution: button.data('resolution')
			},
			timeout: 15000,
			success: function(){
				updateTables()
			},
			error: function(error){
				button.prop('disabled', false)
				spinner(false)
				console.log("Erreur lors de la correction de l'anomalie: " + error)
			}
		})
	})

	$('body').on('keydown', '.month-selector', function(event){
		if (!['Enter', ' '].includes(event.key)) {
			return
		}

		event.preventDefault()
		toggleSelectedMonth(this.dataset.month)
	})

	// Td anticipe jauni
	$("body .anticipe").hover(
		function(){ $(this).prev().addClass('jauni') },
		function(){	$(this).prev().removeClass('jauni')	}
	)

	// Td anticipe jauni après reload tbody
	$("body").on("mouseover", ".anticipe", function(e){

		$(this).prev().addClass('jauni')
		$(this).hover(
			null,
			function(){	$(this).prev().removeClass('jauni')	}
		)
	})
})

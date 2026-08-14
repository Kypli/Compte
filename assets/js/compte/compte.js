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
	let accountPreferencesSaveTimer
	let accountPreferencesRequest

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

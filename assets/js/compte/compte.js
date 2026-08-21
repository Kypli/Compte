// JS
import './modalOperation.js';
import './modalCategory.js';
import { money_display, money_symbol } from '../service/service.js';

// CSS
import '../../styles/compte/compte.css';

let selectedMonth = null
let selectedYear = null
let categoryMove = null
let subCategoryMove = null

////////////
// EXPORT FONCTIONS
////////////

// update tables
export function updateTables(){
	clearCategoryMove()
	clearSubCategoryMove()

	const routeParams = {
		id: $('#datas').data('compteid'),
		year: $('#datas').data('year'),
		months: $('#datas').data('monthdisplay'),
		months_before: getMonthRangeValue('monthRangeBefore', $('#datas').data('monthsbefore') || 1),
		months_after: getMonthRangeValue('monthRangeAfter', $('#datas').data('monthsafter') || 3),
		detail_months: getDetailMonthsParam(),
		detail_months_set: 1
	}
	const datasElement = document.getElementById('datas')
	if (datasElement) {
		routeParams.money_display_format = datasElement.dataset.moneydisplayformat || 'comma'
		routeParams.money_currency = datasElement.dataset.moneycurrency || 'EUR'
		routeParams.money_trim_zeros = datasElement.dataset.moneytrimzeros === '1' ? '1' : '0'
		routeParams.money_show_zero_decimals = datasElement.dataset.moneyshowzerodecimals === '0' ? '0' : '1'
	}
	const selectedMonthValue = datasElement?.dataset.selectedmonth
	const selectedYearValue = datasElement?.dataset.selectedyear
	if (selectedMonthValue) {
		routeParams.selected_month = selectedMonthValue
	}
	if (selectedMonthValue && selectedYearValue) {
		routeParams.selected_year = selectedYearValue
	}

	return $.ajax({
		type: "POST",
		url: Routing.generate('compte_tables', routeParams),
		timeout: 15000,
		beforeSend: function(){
			spinner(true)
		},
		success: function(response){
			$('#tables').empty().append(response.render)
			syncMoneySymbols()
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

function getCurrentMoneyDisplayFormat(){
	const datas = document.getElementById('datas')

	return datas?.dataset.moneydisplayformat || 'comma'
}

function shouldTrimCurrentMoneyZeros(){
	return document.getElementById('datas')?.dataset.moneytrimzeros === '1'
}

function shouldShowCurrentMoneyZeroDecimals(){
	return document.getElementById('datas')?.dataset.moneyshowzerodecimals !== '0'
}

function getCurrentMoneyCurrency(){
	const datas = document.getElementById('datas')

	return datas?.dataset.moneycurrency || 'EUR'
}

function syncMoneyAmounts(){
	document.querySelectorAll('[data-money-amount]').forEach(element => {
		element.textContent = money_display(
			element.dataset.moneyAmount || 0,
			getCurrentMoneyDisplayFormat(),
			getCurrentMoneyCurrency(),
			shouldTrimCurrentMoneyZeros(),
			shouldShowCurrentMoneyZeroDecimals()
		)
	})
}

function syncMoneySymbols(){
	const symbol = money_symbol(getCurrentMoneyCurrency())

	document.querySelectorAll('[data-money-symbol]').forEach(element => {
		element.textContent = symbol
	})
}

function getDetailMonthCheckboxes(){
	return Array.from(document.querySelectorAll('.month-detail-checkbox'))
}

function getDetailMonthMasterCheckbox(){
	return document.getElementById('monthDetailMasterCheckbox')
}

function getSelectedDetailMonths(){
	return getDetailMonthCheckboxes()
		.filter(checkbox => checkbox.checked)
		.map(checkbox => checkbox.value)
}

function getDetailMonthsParam(){
	const datasElement = document.getElementById('datas')
	const checkboxes = getDetailMonthCheckboxes()
	const selectedDetailMonths = getSelectedDetailMonths()

	if (checkboxes.length) {
		return selectedDetailMonths.join(',')
	}

	return datasElement?.dataset.detailmonths || ''
}

function syncDetailMonthSummary(){
	const summary = document.getElementById('monthDetailSummary')
	if (!summary) {
		return
	}

	const checkboxes = getDetailMonthCheckboxes()
	const count = getSelectedDetailMonths().length
	summary.textContent = `${count}/12`

	const masterCheckbox = getDetailMonthMasterCheckbox()
	if (masterCheckbox) {
		masterCheckbox.checked = checkboxes.length > 0 && count === checkboxes.length
		masterCheckbox.indeterminate = count > 0 && count < checkboxes.length
	}
}

function syncSelectedMonthControls(){
	const datasElement = document.getElementById('datas')
	const selectedMonthInput = document.getElementById('selectedMonthInput')
	const selectedYearInput = document.getElementById('selectedYearInput')
	const url = new URL(window.location.href)

	if (datasElement) {
		datasElement.dataset.selectedmonth = selectedMonth || ''
		datasElement.dataset.selectedyear = selectedMonth ? (selectedYear || datasElement.dataset.year || '') : ''
		$(datasElement).data('selectedmonth', selectedMonth || '')
		$(datasElement).data('selectedyear', selectedMonth ? (selectedYear || datasElement.dataset.year || '') : '')
	}
	if (selectedMonthInput) {
		selectedMonthInput.value = selectedMonth || ''
	}
	if (selectedYearInput) {
		selectedYearInput.value = selectedMonth ? (selectedYear || datasElement?.dataset.year || '') : ''
	}
	if (selectedMonth) {
		url.searchParams.set('selected_month', selectedMonth)
		url.searchParams.set('selected_year', selectedYear || datasElement?.dataset.year || '')
	} else {
		url.searchParams.delete('selected_month')
		url.searchParams.delete('selected_year')
	}
	window.history.replaceState(window.history.state, '', url)
}

function isSelectedMonthDisplayMode(mode){
	return typeof mode === 'string' && mode.endsWith('_selected')
}

function isCustomMonthDisplayMode(mode){
	return typeof mode === 'string' && mode.startsWith('custom_')
}

function getCustomMonthDisplayMode(){
	return document.getElementById('monthCustomBasis')?.value || 'custom_current'
}

function getMonthRangeValue(inputId, fallback){
	const input = document.getElementById(inputId)
	const parsedValue = Number.parseInt(input?.value ?? fallback, 10)
	const value = Number.isFinite(parsedValue) ? parsedValue : fallback

	return Math.min(Math.max(value, 0), 11)
}

function getMonthRangeParams(){
	const monthsBefore = getMonthRangeValue('monthRangeBefore', document.getElementById('datas')?.dataset.monthsbefore || 1)
	const monthsAfter = getMonthRangeValue('monthRangeAfter', document.getElementById('datas')?.dataset.monthsafter || 3)

	return {
		months_before: monthsBefore,
		months_after: Math.min(monthsAfter, 11 - monthsBefore)
	}
}

function syncMonthRangeInputs(range = getMonthRangeParams()){
	const beforeInput = document.getElementById('monthRangeBefore')
	const afterInput = document.getElementById('monthRangeAfter')

	if (beforeInput) {
		beforeInput.value = range.months_before
	}
	if (afterInput) {
		afterInput.value = range.months_after
		afterInput.max = String(11 - range.months_before)
	}
}

function monthDisplaySummaryLabel(monthDisplay, range = getMonthRangeParams()){
	if (monthDisplay === 'year') {
		return 'Année entière'
	}

	const basis = isSelectedMonthDisplayMode(monthDisplay) || monthDisplay === 'custom_selected'
		? 'mois sélectionné'
		: 'mois en cours'

	if (isCustomMonthDisplayMode(monthDisplay)) {
		return `Perso : ${range.months_before} av. / ${range.months_after} ap. - ${basis}`
	}
	if (typeof monthDisplay === 'string' && monthDisplay.startsWith('three_')) {
		return `1 av. / 3 ap. - ${basis}`
	}
	if (typeof monthDisplay === 'string' && monthDisplay.startsWith('one_')) {
		return `1 av. / 1 ap. - ${basis}`
	}
	if (typeof monthDisplay === 'string' && monthDisplay.startsWith('current_')) {
		return `1 seul mois - ${basis}`
	}

	return 'Affichage'
}

function syncMonthDisplaySummary(monthDisplay = document.getElementById('datas')?.dataset.monthdisplay || ''){
	const button = document.getElementById('accountDisplayButton')
	if (!button) {
		return
	}

	button.setAttribute('title', `Options d'affichage - ${monthDisplaySummaryLabel(monthDisplay)}`)
}

function syncMonthDisplaySelect(monthDisplay = document.getElementById('datas')?.dataset.monthdisplay || ''){
	const input = document.getElementById('monthDisplay')
	const currentSelect = document.getElementById('monthDisplayCurrent')
	const selectedSelect = document.getElementById('monthDisplaySelected')
	const yearButton = document.querySelector('[data-month-display-value="year"]')
	const isPresetMode = !isCustomMonthDisplayMode(monthDisplay)

	if (input) {
		input.value = monthDisplay
	}
	if (currentSelect) {
		currentSelect.value = isPresetMode && monthDisplay !== 'year' && !isSelectedMonthDisplayMode(monthDisplay) ? monthDisplay : ''
	}
	if (selectedSelect) {
		selectedSelect.value = isPresetMode && isSelectedMonthDisplayMode(monthDisplay) ? monthDisplay : ''
	}
	if (yearButton) {
		const isYear = monthDisplay === 'year'
		yearButton.classList.toggle('is-active', isYear)
		yearButton.setAttribute('aria-pressed', isYear ? 'true' : 'false')
	}
	syncMonthDisplaySummary(monthDisplay)
}

function setMonthDisplayControlsDisabled(disabled){
	$('#monthDisplay, [data-month-display-value], [data-month-display-select]').prop('disabled', disabled)
}

function syncMonthRangeFields(){
	const monthDisplay = document.getElementById('datas')?.dataset.monthdisplay || ''
	const customBasis = document.getElementById('monthCustomBasis')

	if (customBasis && isCustomMonthDisplayMode(monthDisplay)) {
		customBasis.value = monthDisplay
	}
	syncMonthDisplaySelect(monthDisplay)
	syncMonthRangeInputs()
}

function syncMonthRangeUrl(){
	const datasElement = document.getElementById('datas')
	const range = getMonthRangeParams()
	const url = new URL(window.location.href)

	if (datasElement) {
		datasElement.dataset.monthsbefore = String(range.months_before)
		datasElement.dataset.monthsafter = String(range.months_after)
		$(datasElement).data('monthsbefore', range.months_before)
		$(datasElement).data('monthsafter', range.months_after)
	}
	url.searchParams.set('months_before', String(range.months_before))
	url.searchParams.set('months_after', String(range.months_after))
	window.history.replaceState(window.history.state, '', url)
}

function syncDetailMonthsUrl(){
	const detailMonths = getSelectedDetailMonths().join(',')
	const datasElement = document.getElementById('datas')
	const url = new URL(window.location.href)

	if (datasElement) {
		datasElement.dataset.detailmonths = detailMonths
		$(datasElement).data('detailmonths', detailMonths)
	}
	url.searchParams.set('detail_months', detailMonths)
	url.searchParams.set('detail_months_set', '1')
	window.history.replaceState(window.history.state, '', url)
}

function setAccountDisplayTab(tab){
	document.querySelectorAll('[data-account-display-tab]').forEach(button => {
		const isActive = button.dataset.accountDisplayTab === tab
		button.classList.toggle('is-active', isActive)
		button.setAttribute('aria-selected', isActive ? 'true' : 'false')
	})
	document.querySelectorAll('[data-account-display-panel]').forEach(panel => {
		panel.hidden = panel.dataset.accountDisplayPanel !== tab
	})
}

function setDetailMonthControlsDisabled(disabled){
	getDetailMonthCheckboxes().forEach(checkbox => checkbox.disabled = disabled)
	const masterCheckbox = getDetailMonthMasterCheckbox()
	if (masterCheckbox) {
		masterCheckbox.disabled = disabled
	}
}

function updateDetailMonths(previousDetailMonths){
	syncDetailMonthSummary()
	syncDetailMonthsUrl()
	setDetailMonthControlsDisabled(true)

	updateTables()
		.fail(function(){
			const previousValues = previousDetailMonths ? previousDetailMonths.split(',') : []
			getDetailMonthCheckboxes().forEach(checkbox => checkbox.checked = previousValues.includes(checkbox.value))
			syncDetailMonthSummary()
			syncDetailMonthsUrl()
		})
		.always(function(){
			setDetailMonthControlsDisabled(false)
		})
}
function applySelectedMonth(){
	resetSelectedMonthWidths()

	document.querySelectorAll('.compteTable').forEach(table => {
		table.querySelectorAll('.month-selector').forEach(selector => {
			const selectorYear = selector.dataset.year || document.getElementById('datas')?.dataset.year || ''
			const isSelected = selector.dataset.month === selectedMonth && selectorYear === String(selectedYear || '')
			selector.setAttribute('aria-pressed', isSelected ? 'true' : 'false')
		})
		table.querySelectorAll('[data-month]').forEach(cell => {
			const cellYear = cell.dataset.year || document.getElementById('datas')?.dataset.year || ''
			cell.classList.toggle('month-column-selected', cell.dataset.month === selectedMonth && cellYear === String(selectedYear || ''))
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
	const datasElement = document.getElementById('datas')
	const selectedMonthHeader = selectedMonth
		? document.querySelector(`.month-selector[data-month="${selectedMonth}"][data-year="${selectedYear || datasElement?.dataset.year || ''}"]`)
		: null
	const currentMonthHeader = document.querySelector('.month-selector.current')
	const expandedMonth = selectedMonthHeader
		? selectedMonth
		: String($('#datas').data('monthdisplay')).startsWith('current')
			? currentMonthHeader?.dataset.month
			: null
	const expandedYear = selectedMonthHeader
		? selectedMonthHeader.dataset.year
		: currentMonthHeader?.dataset.year

	if (!expandedMonth) {
		return
	}

	document.querySelectorAll('.compteTable').forEach(table => {
		const monthHeader = table.querySelector(`tr:first-child .month-visible[data-month="${expandedMonth}"][data-year="${expandedYear}"]`)
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

function toggleSelectedMonth(month, year){
	const previousSelectedMonth = selectedMonth
	const previousSelectedYear = selectedYear
	const nextYear = String(year || document.getElementById('datas')?.dataset.year || '')
	const sameSelection = selectedMonth === month && String(selectedYear || '') === nextYear
	selectedMonth = sameSelection ? null : month
	selectedYear = sameSelection ? null : nextYear
	syncSelectedMonthControls()

	if (isSelectedMonthDisplayMode($('#datas').data('monthdisplay'))) {
		updateTables()
			.fail(function(){
				selectedMonth = previousSelectedMonth
				selectedYear = previousSelectedYear
				syncSelectedMonthControls()
				applySelectedMonth()
				centerCompactTables()
			})
		return
	}

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

	const datas = document.getElementById('datas')
	$('#solde'+text+'Nb').text(money_display(
		solde,
		datas?.dataset.moneydisplayformat || 'comma',
		datas?.dataset.moneycurrency || 'EUR',
		datas?.dataset.moneytrimzeros === '1',
		datas?.dataset.moneyshowzerodecimals !== '0'
	))

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
	const initialDatasElement = document.getElementById('datas')
	selectedMonth = initialDatasElement?.dataset.selectedmonth || document.querySelector('.month-selector.current')?.dataset.month || null
	selectedYear = selectedMonth
		? (initialDatasElement?.dataset.selectedyear || document.querySelector(`.month-selector[data-month="${selectedMonth}"]`)?.dataset.year || initialDatasElement?.dataset.year || null)
		: null
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
	let accountTourHighlightedElements = []
	let accountTourOverlay = null
	let accountTourPopover = null
	let accountTourRenderToken = 0
	let accountTourOriginalOperationContent = null
	let accountTourOriginalOperationClassName = null
	let accountTourOperationDemoActive = false
	let accountTourOriginalCategoryContent = null
	let accountTourOriginalCategoryClassName = null
	let accountTourCategoryDemoActive = false
	let accountTourOriginalLastActionsContent = null
	let accountTourLastActionsDemoActive = false
	let accountTourModalToken = 0

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
		let shouldUpdateTables = false
		const previousMoneyDisplayFormat = datas.dataset.moneydisplayformat || 'comma'
		const previousMoneyCurrency = datas.dataset.moneycurrency || 'EUR'
		const previousMoneyTrimZeros = datas.dataset.moneytrimzeros === '1'
		const previousMoneyShowZeroDecimals = datas.dataset.moneyshowzerodecimals !== '0'

		Array.from(datas.classList).forEach(className => {
			if (className.startsWith('table-palette-')) {
				datas.classList.remove(className)
			}
		})
		datas.classList.add(`table-palette-${preferences.tablePalette || 'classic'}`)
		datas.classList.toggle('hide-editable-border', preferences.showEditableBorder === false)
		if (preferences.moneyDisplayFormat) {
			datas.dataset.moneydisplayformat = preferences.moneyDisplayFormat
			$(datas).data('moneydisplayformat', preferences.moneyDisplayFormat)
			shouldUpdateTables = preferences.moneyDisplayFormat !== previousMoneyDisplayFormat
		}
		if (preferences.moneyCurrency) {
			datas.dataset.moneycurrency = preferences.moneyCurrency
			$(datas).data('moneycurrency', preferences.moneyCurrency)
			shouldUpdateTables = shouldUpdateTables || preferences.moneyCurrency !== previousMoneyCurrency
		}
		if (typeof preferences.moneyTrimZeros === 'boolean') {
			datas.dataset.moneytrimzeros = preferences.moneyTrimZeros ? '1' : '0'
			$(datas).data('moneytrimzeros', preferences.moneyTrimZeros ? 1 : 0)
			shouldUpdateTables = shouldUpdateTables || preferences.moneyTrimZeros !== previousMoneyTrimZeros
		}
		if (typeof preferences.moneyShowZeroDecimals === 'boolean') {
			datas.dataset.moneyshowzerodecimals = preferences.moneyShowZeroDecimals ? '1' : '0'
			$(datas).data('moneyshowzerodecimals', preferences.moneyShowZeroDecimals ? 1 : 0)
			shouldUpdateTables = shouldUpdateTables || preferences.moneyShowZeroDecimals !== previousMoneyShowZeroDecimals
		}

		if (typeof preferences.compteGenreShow === 'boolean') {
			shouldUpdateTables = true
		}
		syncMoneySymbols()
		syncMoneyAmounts()
		if (shouldUpdateTables) {
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
		const moneyTrimZerosInput = accountPreferencesForm.querySelector('[name$="[moneyTrimZeros]"]')
		const moneyZeroRow = accountPreferencesForm.querySelector('.money-format-zero-row')
		const moneyCurrencyInputs = accountPreferencesForm.querySelectorAll('[name$="[moneyCurrency]"]')
		const moneyZeroInputs = accountPreferencesForm.querySelectorAll('[name$="[moneyShowZeroDecimals]"]')
		const moneyZeroInput = Array.from(moneyZeroInputs).find(input => input.value === '0')
		const moneyZeroDecimalInput = Array.from(moneyZeroInputs).find(input => input.value === '1')
		const moneyZeroDecimalChoice = moneyZeroDecimalInput?.closest('.money-zero-choice')
		const moneyZeroDecimalDefaultTitle = moneyZeroDecimalChoice?.getAttribute('title') || '0.00'
		const yenZeroDecimalTitle = "Le yen japonais n'utilise pas de centimes : l'affichage 0.00 n'est pas disponible."
		const moneyFormatExamples = accountPreferencesForm.querySelectorAll('[data-money-format-example]')
		const moneySymbolByCurrency = {
			EUR: '\u20ac',
			USD: '$',
			GBP: '\u00a3',
			CHF: 'CHF',
			JPY: '\u00a5',
			CAD: 'CA$'
		}
		const getSelectedMoneyCurrency = function(){
			const selectedCurrencyInput = Array.from(moneyCurrencyInputs).find(input => input.checked)

			return selectedCurrencyInput?.value || 'EUR'
		}
		const syncMoneyZeroCurrencyState = function(){
			if (!moneyZeroInput || !moneyZeroDecimalInput || !moneyZeroDecimalChoice) {
				return
			}

			const shouldForceZero = getSelectedMoneyCurrency() === 'JPY' && !moneyTrimZerosInput?.checked
			if (shouldForceZero) {
				moneyZeroInput.checked = true
				moneyZeroDecimalInput.checked = false
			}

			moneyZeroDecimalInput.disabled = shouldForceZero
			moneyZeroDecimalChoice.classList.toggle('is-disabled', shouldForceZero)
			moneyZeroDecimalChoice.setAttribute('title', shouldForceZero ? yenZeroDecimalTitle : moneyZeroDecimalDefaultTitle)
		}
		const syncMoneyFormatExamples = function(){
			const symbol = money_symbol(getSelectedMoneyCurrency())

			moneyFormatExamples.forEach(example => {
				if (example.dataset.moneyFormat !== 'euro_cents') {
					return
				}

				const label = `1 234${symbol}56`
				example.textContent = label
				example.closest('.money-format-choice')?.setAttribute('title', label)
			})
		}
		const syncMoneyZeroPreferenceVisibility = function(){
			if (!moneyTrimZerosInput || !moneyZeroRow) {
				return
			}

			moneyZeroRow.hidden = moneyTrimZerosInput.checked
		}
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
			syncMoneyZeroCurrencyState()
			syncMoneyZeroPreferenceVisibility()
			syncMoneyFormatExamples()
			window.clearTimeout(accountPreferencesSaveTimer)
			accountPreferencesSaveTimer = window.setTimeout(saveAccountPreferences, 160)
		})
		syncMoneyZeroCurrencyState()
		syncMoneyZeroPreferenceVisibility()
		syncMoneyFormatExamples()
	}
	syncMoneySymbols()
	syncMoneyAmounts()
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
	const accountTourCategoryCellSelector = '.td_category_libelle, .td_subcategory_libelle'

	const accountTourDefinitions = [
		{
			selector: '.account-header-action-buttons',
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
			title: 'Bouton des anomalies',
			text: "Ce bouton signale les opérations anticipées en retard. Il donne accès à la fenêtre de correction."
		},
		{
			selector: '#modalAnomalies .modal-content',
			title: 'Fenêtre des anomalies',
			html: "<p>La fenêtre détaille chaque opération prévue dont la date est dépassée.</p><ul><li>Valider l'opération la passe en montant réel.</li><li>Supprimer l'opération retire une prévision qui n'est plus utile.</li></ul>",
			before: () => openAnomaliesModalForTour(),
			modal: 'anomalies'
		},
		{
			selector: '#last-actions-div',
			title: 'Dernières actions',
			text: "Cette zone garde l'historique récent et propose l'annulation ou la restauration des dernières modifications disponibles."
		},
		{
			selector: '#lastActionsPopover',
			title: 'Historique des actions',
			html: "<p>Ce panneau liste les dernières modifications du compte.</p><ul><li>Chaque ligne précise le type d'action, la zone concernée, la date et le montant.</li><li>Le bouton de retour permet d'annuler ou de restaurer une action récente.</li></ul>",
			before: () => openLastActionsPopoverForTour(),
			modal: 'lastActions'
		},
		{
			selector: '.account-view-controls',
			title: 'Période et affichage',
			text: "Change l'année affichée, puis choisis la largeur de lecture: année complète, période compacte ou mois courant."
		},
		{
			selector: '.bck_pos .compteTable',
			title: 'Tableau des recettes',
			text: "Ce tableau regroupe les recettes par catégorie, sous-catégorie et mois. Les totaux permettent de suivre ce qui rentre sur la période."
		},
		{
			selector: '.bck_neg .compteTable',
			title: 'Tableau des dépenses',
			text: "Ce tableau regroupe les dépenses. Les montants négatifs et anticipés aident à préparer les sorties avant qu'elles soient réellement passées."
		},
		{
			selector: '.gains-table',
			title: 'Tableau des gains',
			text: "Ce tableau compare recettes et dépenses, puis affiche le gain mensuel et le cumul de solde mois après mois."
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
			selector: accountTourCategoryCellSelector,
			title: 'Catégories et sous-catégories',
			text: "Les cellules de libellé ouvrent la gestion des catégories. Elles servent à structurer le budget avant de saisir les opérations mensuelles."
		},
		{
			selector: '#modalCategory .modal-content',
			title: 'Gestion des catégories',
			html: "<p>Cette fenêtre permet de modifier la catégorie sélectionnée et ses sous-catégories.</p><ul><li>Renommer une catégorie ou une sous-catégorie.</li><li>Ajouter une nouvelle sous-catégorie.</li><li>Préparer un déplacement ou une suppression avant enregistrement.</li></ul>",
			before: () => openCategoryModalForTour(),
			modal: 'category'
		},
		{
			selector: '#modalOperation .modal-content',
			title: "Fenêtre d'opérations",
			html: "<p>Cette démonstration montre plusieurs cas sans enregistrer de changement.</p><ul><li>Une opération réelle déjà confirmée.</li><li>Une opération anticipée prévue plus tard.</li><li>Une ligne ajoutée, une ligne modifiée et une ligne marquée pour suppression.</li></ul>",
			before: () => openOperationModalForTour(),
			modal: 'operation'
		},
		{
			selector: '#modalOperation .inputNumber, #modalOperation .inputAnticipe',
			title: 'Montants réel et anticipé',
			text: "Le montant réel correspond à une opération confirmée. Le montant anticipé sert à préparer une dépense ou un revenu prévu avant sa validation.",
			before: () => openOperationModalForTour(),
			modal: 'operation'
		},
		{
			selector: '#modalOperation .inputDay',
			title: "Date de l'opération",
			text: "Le jour rattache l'opération au bon moment du mois. Il permet aussi de repérer les opérations anticipées devenues en retard.",
			before: () => openOperationModalForTour(),
			modal: 'operation'
		},
		{
			selector: '#modalOperation .inputComment',
			title: 'Commentaire',
			text: "Le commentaire sert de repère lisible: bénéficiaire, facture, précision personnelle ou toute information utile pour retrouver l'opération.",
			before: () => openOperationModalForTour(),
			modal: 'operation'
		},
		{
			selector: '#modalOperation .trButStopFormMod, #modalOperation .trButCancelEdit, #modalOperation .trButDelAdd, #modalOperation .trButRevive',
			highlightSelector: '#modalOperation .trButStopFormMod, #modalOperation .trButCancelEdit, #modalOperation .trButDelAdd, #modalOperation .trButRevive',
			title: 'Actions de ligne',
			html: "<p>Les croix colorées indiquent l'action disponible sur la ligne.</p><ul><li><strong>Vert</strong> : retire une ligne ajoutée.</li><li><strong>Noir</strong> : ferme le mode édition.</li><li><strong>Bleu</strong> : annule les modifications.</li><li><strong>Rouge</strong> : annule une suppression.</li></ul>",
			before: () => openOperationModalForTour(),
			modal: 'operation'
		},
		{
			selector: '#modalOperation #modalOperationSaveClose, #modalOperation #modalOperationClose',
			highlightSelector: '#modalOperation #modalOperationSaveClose, #modalOperation #modalOperationClose',
			title: 'Enregistrer ou fermer',
			text: "Le bouton d'enregistrement apparaît seulement quand une modification est détectée. Fermer sans enregistrer permet de sortir de la fenêtre sans toucher aux données.",
			before: () => openOperationModalForTour(),
			modal: 'operation'
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

	const getVisibleTourElements = function(selector){
		return Array.from(document.querySelectorAll(selector)).filter(element => {
			const rect = element.getBoundingClientRect()

			return rect.width > 0 && rect.height > 0
		})
	}

	const getVisibleTourElement = function(selector){
		return getVisibleTourElements(selector)[0] || null
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

	const restoreOperationModalAfterTour = function(){
		const operationContent = document.querySelector('#modalOperation .modal-content')
		if (operationContent && accountTourOriginalOperationContent !== null) {
			operationContent.innerHTML = accountTourOriginalOperationContent
		}
		accountTourOriginalOperationContent = null
		accountTourOperationDemoActive = false
	}

	const restoreCategoryModalAfterTour = function(){
		const categoryContent = document.querySelector('#modalCategory .modal-content')
		if (categoryContent && accountTourOriginalCategoryContent !== null) {
			categoryContent.innerHTML = accountTourOriginalCategoryContent
		}
		accountTourOriginalCategoryContent = null
		accountTourCategoryDemoActive = false
	}

	const closeOperationModalForTour = function(){
		hideModalForTour(document.getElementById('modalOperation'), restoreOperationModalAfterTour)
	}

	const closeCategoryModalForTour = function(){
		hideModalForTour(document.getElementById('modalCategory'), restoreCategoryModalAfterTour)
	}

	const closeAnomaliesModalForTour = function(){
		hideModalForTour(document.getElementById('modalAnomalies'))
	}

	const restoreLastActionsAfterTour = function(){
		const lastActionsContainer = document.getElementById('last-actions-div')
		lastActionsContainer?.classList.remove('account-tour-last-actions-host')
		if (lastActionsContainer && accountTourOriginalLastActionsContent !== null) {
			lastActionsContainer.innerHTML = accountTourOriginalLastActionsContent
		}
		accountTourOriginalLastActionsContent = null
		accountTourLastActionsDemoActive = false
	}

	const closeLastActionsPopoverForTour = function(){
		document.querySelectorAll('.last-actions.is-locked, .last-actions.account-tour-last-actions-active').forEach(lastActions => {
			lastActions.classList.remove('is-locked', 'account-tour-last-actions-active')
			lastActions.querySelector('.last-action-trigger')?.setAttribute('aria-expanded', 'false')
		})
		restoreLastActionsAfterTour()
	}

	const closeTourModals = function(keepModal = null){
		if (keepModal !== 'operation') {
			closeOperationModalForTour()
		}
		if (keepModal !== 'category') {
			closeCategoryModalForTour()
		}
		if (keepModal !== 'anomalies') {
			closeAnomaliesModalForTour()
		}
		if (keepModal !== 'lastActions') {
			closeLastActionsPopoverForTour()
		}
	}

	const openLastActionsPopoverForTour = async function(){
		const lastActionsContainer = document.getElementById('last-actions-div')
		if (!lastActionsContainer) {
			return
		}
		if (!accountTourLastActionsDemoActive) {
			accountTourOriginalLastActionsContent = lastActionsContainer.innerHTML
			lastActionsContainer.innerHTML = getAccountTourLastActionsDemoHtml()
			accountTourLastActionsDemoActive = true
		}

		lastActionsContainer.classList.add('account-tour-last-actions-host')
		const lastActions = lastActionsContainer.querySelector('.last-actions')
		const trigger = lastActions?.querySelector('.last-action-trigger')
		if (!lastActions || !trigger) {
			return
		}

		trigger.setAttribute('aria-expanded', 'false')
		trigger.dispatchEvent(new MouseEvent('click', {
			bubbles: true,
			cancelable: true,
			view: window
		}))
		if (!lastActions.classList.contains('is-locked')) {
			lastActions.classList.add('is-locked')
			trigger.setAttribute('aria-expanded', 'true')
		}
		lastActions.classList.add('account-tour-last-actions-active')
		await waitForTourElement('#lastActionsPopover', 1200)
	}

	const openAnomaliesModalForTour = async function(){
		await showModalForTour(document.getElementById('modalAnomalies'), '#modalAnomalies .modal-content')
	}

	const openCategoryModalForTour = async function(){
		const categoryModal = document.getElementById('modalCategory')
		const categoryContent = categoryModal?.querySelector('.modal-content')
		if (!categoryModal || !categoryContent) {
			return
		}

		if (!accountTourCategoryDemoActive) {
			accountTourOriginalCategoryContent = categoryContent.innerHTML
			categoryContent.innerHTML = getAccountTourCategoryDemoHtml()
			accountTourCategoryDemoActive = true
		}

		await showModalForTour(categoryModal, '#modalCategory #cat_tab input')
	}

	const openOperationModalForTour = async function(){
		const operationModal = document.getElementById('modalOperation')
		const operationContent = operationModal?.querySelector('.modal-content')
		if (!operationModal || !operationContent) {
			return
		}

		if (!accountTourOperationDemoActive) {
			accountTourOriginalOperationContent = operationContent.innerHTML
			operationContent.innerHTML = getAccountTourOperationDemoHtml()
			accountTourOperationDemoActive = true
		}

		await showModalForTour(operationModal, '#modalOperation .inputDay')
	}

	const showModalForTour = async function(modal, selectorToWait){
		if (!modal) {
			return
		}

		modal.classList.add('account-tour-modal-active')
		modal.dataset.accountTourToken = String(++accountTourModalToken)
		if (window.$ && typeof $(modal).modal === 'function') {
			$(modal).modal('show')
		} else if (window.bootstrap?.Modal) {
			window.bootstrap.Modal.getOrCreateInstance(modal).show()
		} else {
			modal.classList.add('show')
			modal.style.display = 'block'
			modal.removeAttribute('aria-hidden')
			modal.setAttribute('aria-modal', 'true')
			document.body.classList.add('modal-open')
		}

		await waitForTourElement(selectorToWait, 1200)
	}

	const hideModalForTour = function(modal, afterHide){
		if (!modal) {
			return
		}

		const hideToken = modal.dataset.accountTourToken || ''
		modal.classList.remove('account-tour-modal-active')
		if (modal.classList.contains('show') || modal.style.display === 'block') {
			if (window.$ && typeof $(modal).modal === 'function') {
				$(modal).modal('hide')
			} else if (window.bootstrap?.Modal) {
				window.bootstrap.Modal.getOrCreateInstance(modal).hide()
			} else {
				modal.classList.remove('show')
				modal.style.display = 'none'
				modal.setAttribute('aria-hidden', 'true')
				modal.removeAttribute('aria-modal')
			}
		}

		window.setTimeout(function(){
			if (modal.dataset.accountTourToken !== hideToken && modal.classList.contains('account-tour-modal-active')) {
				return
			}

			modal.classList.remove('account-tour-modal-active')
			modal.classList.remove('show')
			modal.style.display = 'none'
			modal.setAttribute('aria-hidden', 'true')
			modal.removeAttribute('aria-modal')
			afterHide?.()
			if (!document.querySelector('.modal.show')) {
				document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove())
				document.body.classList.remove('modal-open')
				document.body.style.removeProperty('padding-right')
			}
		}, 180)
	}

	const getAccountTourLastActionsDemoHtml = function(){
		return `
			<div class="last-actions">
				<div class="last-action-preview">
					<span class="control-label">Dernières actions</span>
					<div class="last-action-current">
						<button type="button" class="last-action-trigger" aria-controls="lastActionsPopover" aria-expanded="true">
							<span class="action-kind action-kind-edit">Modification</span>
							<span class="last-action-summary action-zone-pos">Recettes / Salaire</span>
						</button>
					</div>
				</div>
				<div id="lastActionsPopover" class="last-actions-popover" role="tooltip">
					<div class="last-actions-popover-title">
						<i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
						<span>15 dernières actions</span>
					</div>
					<ol class="last-actions-list">
						<li class="last-actions-item">
							<span class="action-kind action-kind-edit">Modification</span>
							<span class="last-actions-description"><strong class="action-zone-pos">Recettes / Salaire</strong><small>05/08/2026 · 1 850,00 €</small></span>
							<time datetime="2026-08-18T10:30:00">18/08 10:30</time>
							<button type="button" class="undo-last-action undo-action" title="Annuler cette action"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i></button>
						</li>
						<li class="last-actions-item is-cancelled">
							<span class="action-kind action-kind-del">Suppression</span>
							<span class="last-actions-description"><strong class="action-zone-neg">Dépenses / Courses</strong><small>12/08/2026 · -42,80 €</small></span>
							<time datetime="2026-08-18T09:45:00">18/08 09:45</time>
							<button type="button" class="undo-last-action undo-revert-action" title="Restaurer cette action"><i class="fa-solid fa-rotate-left action-restore-icon" aria-hidden="true"></i></button>
						</li>
						<li class="last-actions-item">
							<span class="action-kind action-kind-move">Déplacement</span>
							<span class="last-actions-description"><strong class="action-zone-neg">Dépenses fixes</strong><small>Position 4 → 2</small></span>
							<time datetime="2026-08-17T18:12:00">17/08 18:12</time>
							<button type="button" class="undo-last-action undo-action" title="Annuler cette action"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i></button>
						</li>
					</ol>
				</div>
			</div>
		`
	}
	const getAccountTourCategoryDemoHtml = function(){
		return `
			<div class="modal-header bck_pos">
				<h4 class="modal-title">
					<span class="souligne">Gestionnaire de catégorie</span><br />
					« <span id="cat_name">Travail</span> »
				</h4>
				<button id="add_cat" class="m-1 p-2">Ajouter une catégorie</button>
				<button type="button" id="modalCatClose" class="btn btn-dark minWitdh195" data-dismiss="modal">Fermer sans enregistrer</button>
			</div>
			<div id="body_cat" class="modal-body">
				<div id="cat_save_error" class="modal-category-error" role="alert" style="display: none;"></div>
				<table id="cat_tab" class="m-1 p-1" data-sign="1">
					<tbody>
						<tr class="tr_category_before italique"><td class="pb-2"><div data-id="2" data-sign="1" class="hide before_actif other_actif" title="Afficher cette catégorie" style="display: block;"> Assurance </div></td></tr>
						<tr id="tr_category_1" class="tr_category">
							<td><input type="text" id="cat_1" value="Travail" name="category" placeholder="Libellé catégorie" data-pos="1" class="input_edit" /></td>
							<td class="row minWitdh20 m-2"><div class="col-3 p-1"><i id="td_chevron_cat_up" class="pointeur fas fa-chevron-circle-up opacity_low" aria-hidden="true"></i></div><div class="col-2 p-1"><i id="td_chevron_cat_down" class="fas fa-chevron-circle-down pointeur opacity_low" aria-hidden="true"></i></div></td>
						</tr>
						<tr class="tr_category_after italique"><td><div data-id="53" class="hide after_actif" style="display: block;"><span data-id="53" class="other_actif" title="Afficher cette catégorie">Gites</span></div><div id="cat_after_x" class="hide limite">....</div></td></tr>
						<tr id="tr_subcategories_1" class="tr_subcategories"><td></td><td><input type="text" class="m-1" id="sc_1" value="salaire" name="sc_1" placeholder="Libellé sous-catégorie" data-pos="1" /></td><td class="td_chevron_up minWitdh40 center"><i class="fa-solid fa-chevron-up opacity_low" style="display: none;"></i></td><td class="td_chevron_down minWitdh40 center pointeur"><i class="fa-solid fa-chevron-down opacity_low"></i></td><td class="minWitdh40 center ml-1"><span class="pointeur_help" title="Vous ne pouvez pas supprimer cette sous-catégorie car des opérations liées persistent"><button class="btn btn-muted not_delete_sc pointeur_help opacity_low" disabled><i class="fa-solid fa-xmark"></i></button></span></td></tr>
						<tr id="tr_subcategories_2" class="tr_subcategories"><td></td><td><input type="text" class="m-1" id="sc_2" value="prime" name="sc_2" placeholder="Libellé sous-catégorie" data-pos="2" /></td><td class="td_chevron_up minWitdh40 center pointeur"><i class="fa-solid fa-chevron-up opacity_low"></i></td><td class="td_chevron_down minWitdh40 center pointeur"><i class="fa-solid fa-chevron-down opacity_low"></i></td><td class="minWitdh40 center ml-1"><span class="pointeur_help" title="Vous ne pouvez pas supprimer cette sous-catégorie car des opérations liées persistent"><button class="btn btn-muted not_delete_sc pointeur_help opacity_low" disabled><i class="fa-solid fa-xmark"></i></button></span></td></tr>
						<tr id="tr_subcategories_3" class="tr_subcategories"><td></td><td><input type="text" class="m-1" id="sc_3" value="13eme mois" name="sc_3" placeholder="Libellé sous-catégorie" data-pos="3" /></td><td class="td_chevron_up minWitdh40 center pointeur"><i class="opacity_low fa-solid fa-chevron-up"></i></td><td class="td_chevron_down minWitdh40 center"><i class="opacity_low fa-solid fa-chevron-down" style="display: none;"></i></td><td class="minWitdh40 center ml-1"><span class="pointeur_help" title="Vous ne pouvez pas supprimer cette sous-catégorie car des opérations liées persistent"><button class="opacity_low btn btn-muted not_delete_sc pointeur_help" disabled><i class="fa-solid fa-xmark"></i></button></span></td></tr>
						<tr class="tr_add"><td></td><td><button id="modalCategorieAdd" class="mt-2">Ajouter</button></td></tr>
					</tbody>
				</table>
				<div id="modal_cat_spinner" class="spinner spinner-border text-primary mt-4 ml-2" role="status" style="display: none;"><span class="sr-only">Chargement...</span></div>
			</div>
			<div class="hide delete_zone m-2" style="display: none;"></div>
			<div class="hide modal-footer" style="display: block;">
				<div class="row">
					<div class="col-3"></div>
					<div class="col-6 p-0"><button type="button" id="cancel_cat" class="hide btn btn-danger m-1" style="display: inline-block;">Annuler les changements</button></div>
					<div class="col-3 p-0"><button type="button" id="modalCatSaveClose" class="btn btn-success m-1" data-dismiss="modal">Enregistrer et Fermer</button></div>
				</div>
			</div>
		`
	}
	const getAccountTourOperationDemoHtml = function(){
		return `
			<div class="modal-header row bck_pos">
				<h4 class="col-12 modal-title souligne center mt-0 pt-0">Gestion des opérations</h4>
				<div class="col-8 taille25"><span id="date_mois">Février</span> <span id="date_annee">2026</span><br /><span id="category" class="total_month_full_pos">Travail</span> -> <span id="subcategory" class="total_month_full_pos">Prime</span><br /></div>
				<div class="col-4 alignRight"><br /><button type="button" id="modalOperationClose" class="btn btn-dark minWitdh195" data-dismiss="modal" title="Fermer la fenêtre sans enregistrer">Fermer sans enregistrer</button></div>
			</div>
			<div class="modal-body pt-1">
				<table id="operation_tab" class="m-1 p-1 mt-2">
					<thead><tr class="mb-2"><th colspan="6" class="th_actions"><div class="row"><div class="col-2"><button id="butOpeAdd" class="btn btn-outline-success col-12 m-1 mr-4" value="0" title="Ajouter une ligne"><i class="fas fa-plus" aria-hidden="true"></i></button></div><div class="col-2"><button value="1" type="button" class="btn btn-outline-secondary col-12 m-1" id="butFullToggleFormMod" title="Afficher les formulaires des lignes"><i class="fas fa-times" title="Retirer les formulaires des lignes" aria-hidden="true"></i><span class="sr-only">Retirer les formulaires des lignes</span></button></div><div class="col-2"><button value="0" type="button" class="btn btn-outline-primary col-12 m-1 hide" id="butFullCancelEdit" title="Annuler les modifications des lignes" style="display: inline-block;"><i class="fas fa-times" aria-hidden="true"></i></button></div><div class="col-2"><button value="0" type="button" class="btn btn-outline-success col-12 m-1 hide" id="butFullDelAdd" title="Annuler les ajouts de lignes" style="display: inline-block;"><i class="fas fa-times" aria-hidden="true"></i></button></div><div class="col-2"><button value="0" type="button" class="btn btn-outline-danger col-12 m-1 hide" id="butFullRevive" title="Annuler les suppression de lignes" style="display: inline-block;"><i class="fas fa-times" aria-hidden="true"></i></button></div></div></th></tr><tr><th class="p-1">Montant</th><th class="th_switch p-1"></th><th class="p-1">A venir</th><th class="th_date p-1">Date</th><th class="th_comment p-1">Commentaire</th><th class="th_actions p-1"></th></tr></thead>
					<tbody data-year="0" data-month="0" data-daysinmonth="0" data-scid="0" data-type="">
						<tr id="ope_id_411" class="tr_ope"><td class="td_number">79,34</td><td class="td_switch"><span class="switch hide"><i class="fa-solid fa-repeat"></i></span></td><td class="td_anticipe"></td><td class="td_date p-1"><span class="day">26</span>/02/2026</td><td class="td_comment p-1">Comment 410</td><td class="td_actions row"><div class="hide btn-group col-4 ecart-left-5" role="group"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog" aria-hidden="true"></i><span class="caret"></span></button><ul class="dropdown-menu options p-1"><li class="invalid">Dupliquer</li><li class="invalid">Changer le mois/année</li><li class="invalid">Attribuer</li><li class="invalid noForm">Retire mode édition</li><li class="invalid">...</li><li><hr /></li><li class="delete">Supprimer</li></ul></div><div class="offset-2 col-4"><button type="button" class="btn btn-outline-secondary mt-2 mb-2 pt-0 pb-0 hide trButStopFormMod" style="display: none;"><i class="fas fa-times"></i></button><button type="button" class="btn btn-outline-primary mt-2 mb-2 pt-0 pb-0 hide trButCancelEdit" style="display: none;"><i class="fas fa-times"></i></button><button type="button" class="btn btn-outline-succes mt-2 mb-2 pt-0 pb-0 hide trButDelAdd"><i class="fas fa-times"></i></button><button type="button" class="btn btn-outline-danger mt-2 mb-2 pt-0 pb-0 hide trButRevive"><i class="fas fa-times"></i></button></div></td></tr>
						<tr id="ope_id_433" class="tr_ope"><td class="td_number"><input class="inputNumber" type="number" step="0.01" value="10.4" min="0" /></td><td class="td_switch"><span class="switch hide" style="display: inline;"><i class="fa-solid fa-repeat"></i></span></td><td class="td_anticipe"></td><td class="td_date p-1"><span class="day"><select class="inputDay"><option value="26" selected>26</option></select></span>/02/2026</td><td class="td_comment p-1"><input class="inputComment" type="text" value="Comment 432" /></td><td class="td_actions row"><div class="hide btn-group col-4 ecart-left-5" role="group" style="display: block;"><button type="button" class="btn btn-default dropdown-toggle"><i class="fas fa-cog"></i><span class="caret"></span></button></div><div class="offset-2 col-4"><button type="button" class="btn btn-outline-secondary mt-2 mb-2 pt-0 pb-0 hide trButStopFormMod" style="display: inline-block;"><i class="fas fa-times"></i></button><button type="button" class="btn btn-outline-primary mt-2 mb-2 pt-0 pb-0 hide trButCancelEdit" style="display: none;"><i class="fas fa-times"></i></button></div></td></tr>
						<tr id="ope_id_35" class="tr_ope tr_del"><td class="td_number">95,02</td><td class="td_switch"><span class="switch hide" style="display: none;"><i class="fa-solid fa-repeat"></i></span></td><td class="td_anticipe"></td><td class="td_date p-1"><span class="day">21</span>/02/2026</td><td class="td_comment p-1">Comment 34</td><td class="td_actions row"><div class="hide btn-group col-4 ecart-left-5" role="group" style="display: none;"></div><div class="offset-2 col-4"><button type="button" class="btn btn-outline-danger mt-2 mb-2 pt-0 pb-0 hide trButRevive" style="display: inline-block;"><i class="fas fa-times"></i></button></div></td></tr>
						<tr id="ope_id_476" class="tr_ope tr_edit"><td class="td_number"></td><td class="td_switch"><span class="switch hide" style="display: inline;"><i class="fa-solid fa-repeat"></i></span></td><td class="td_anticipe"><input class="inputAnticipe input_edit" type="number" step="0.01" value="66.96" min="0" /></td><td class="td_date p-1"><span class="day"><select class="inputDay"><option value="17" selected>17</option></select></span>/02/2026</td><td class="td_comment p-1"><input class="inputComment" type="text" value="Comment 475" /></td><td class="td_actions row"><div class="hide btn-group col-4 ecart-left-5" role="group" style="display: block;"><button type="button" class="btn btn-default dropdown-toggle"><i class="fas fa-cog"></i><span class="caret"></span></button></div><div class="offset-2 col-4"><button type="button" class="btn btn-outline-primary mt-2 mb-2 pt-0 pb-0 hide trButCancelEdit" style="display: inline-block;"><i class="fas fa-times"></i></button></div></td></tr>
						<tr class="tr_add"><td class="td_number"><input class="inputNumber alerteOpe" type="number" step="0.01" value="" placeholder="0" min="0" /></td><td class="td_switch"><span class="switch hide"><i class="fa-solid fa-repeat"></i></span></td><td class="td_anticipe"><input class="inputAnticipe alerteOpe" type="number" step="0.01" value="" placeholder="0" min="0" /></td><td class="td_date p-1"><select class="inputDay"><option value="1" selected>1</option></select>/<span>02</span>/<span>2026</span></td><td class="td_comment p-1"><input class="inputComment" type="text" /></td><td class="td_actions row"><div class="btn-group col-4" role="group"><button type="button" class="btn btn-default dropdown-toggle"><i class="fas fa-cog"></i><span class="caret"></span></button></div><div class="offset-2 col-4"><button type="button" class="btn btn-outline-success mt-2 mb-2 pt-0 pb-0 trButDelAdd"><i class="fas fa-times"></i></button></div></td></tr>
						<tr id="solde_tr_collabo"><td></td><td></td><td></td><td colspan="3"></td></tr><tr id="tr_solde" class="hide" style="display: table-row;"><td id="soldeReel" class="totalReel total_month_detail_1">153,92</td><td></td><td id="soldeAnticipe" class="totalAnticipe">66,96</td><td colspan="3"></td></tr>
					</tbody>
				</table>
				<div id="modal_ope_spinner" class="spinner spinner-border text-primary mt-4 ml-2" role="status" style="display: none;"><span class="sr-only">Chargement...</span></div><div id="solde" class="mt-3 col-3 total_month_full_pos">220,88</div>
			</div>
			<div class="hide modal-footer" style="display: block;"><div class="row"><div class="col-3"></div><div class="offset-6 col-3 p-0"><button type="button" id="modalOperationSaveClose" class="btn btn-success m-1" data-dismiss="modal">Enregistrer et Fermer</button></div></div></div>
		`
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
		accountTourHighlightedElements.forEach(element => element.classList.remove('account-tour-highlight'))
		accountTourHighlightedElements = []
		accountTourTarget = null
	}

	const stopAccountTour = function(){
		accountTourRenderToken++
		clearAccountTourTarget()
		closeTourModals()
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
		const activeTourModal = accountTourTarget.closest('.modal.account-tour-modal-active')
		const popoverWidth = activeTourModal ? Math.min(340, window.innerWidth - 24) : Math.min(380, window.innerWidth - 24)
		const measuredHeight = accountTourPopover.offsetHeight || 220
		const clamp = function(value, min, max){
			return Math.max(min, Math.min(value, max))
		}
		let top = rect.bottom + gap
		let left = rect.left + (rect.width / 2) - (popoverWidth / 2)

		accountTourPopover.classList.toggle('is-modal-step', Boolean(activeTourModal))
		if (activeTourModal) {
			const modalRect = (activeTourModal.querySelector('.modal-dialog') || activeTourModal).getBoundingClientRect()
			const sideTop = clamp(modalRect.top + 12, 12, window.innerHeight - measuredHeight - 12)
			const rightSpace = window.innerWidth - modalRect.right
			const leftSpace = modalRect.left

			if (rightSpace >= popoverWidth + gap) {
				left = modalRect.right + gap
				top = sideTop
			} else if (leftSpace >= popoverWidth + gap) {
				left = modalRect.left - popoverWidth - gap
				top = sideTop
			} else {
				left = modalRect.left + (modalRect.width / 2) - (popoverWidth / 2)
				if (modalRect.bottom + measuredHeight + gap <= window.innerHeight - 12) {
					top = modalRect.bottom + gap
				} else if (modalRect.top - measuredHeight - gap >= 12) {
					top = modalRect.top - measuredHeight - gap
				} else {
					top = window.innerHeight - measuredHeight - 12
				}
			}
		} else if (top + measuredHeight > window.innerHeight - 12) {
			top = rect.top - measuredHeight - gap
		}

		top = clamp(top, 12, window.innerHeight - measuredHeight - 12)
		left = clamp(left, 12, window.innerWidth - popoverWidth - 12)
		accountTourPopover.style.width = `${popoverWidth}px`
		accountTourPopover.style.top = `${top}px`
		accountTourPopover.style.left = `${left}px`
	}

	const escapeAccountTourText = function(value){
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
	}
	const renderAccountTourStep = async function(direction = 1){
		const renderToken = ++accountTourRenderToken
		const step = accountTourSteps[accountTourIndex]
		if (!step) {
			stopAccountTour()
			return
		}

		clearAccountTourTarget()
		closeTourModals(step.modal || null)
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
		accountTourHighlightedElements = step.highlightSelector ? getVisibleTourElements(step.highlightSelector) : [accountTourTarget]
		if (!accountTourHighlightedElements.includes(accountTourTarget)) {
			accountTourHighlightedElements.unshift(accountTourTarget)
		}
		accountTourHighlightedElements.forEach(element => element.classList.add('account-tour-highlight'))
		accountTourTarget.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' })

		const isLastStep = accountTourIndex === accountTourSteps.length - 1
		const stepBody = step.html || `<p>${step.text}</p>`
		const stepOptions = accountTourSteps
			.map((tourStep, index) => `<option value="${index}"${index === accountTourIndex ? ' selected' : ''}>${index + 1}. ${escapeAccountTourText(tourStep.title)}</option>`)
			.join('')
		accountTourPopover.innerHTML = `
			<div class="account-tour-progress-row">
				<div class="account-tour-progress">Étape ${accountTourIndex + 1} / ${accountTourSteps.length}</div>
				<label class="account-tour-jump">
					<span>Aller à</span>
					<select class="account-tour-jump-select">${stepOptions}</select>
				</label>
			</div>
			<h5>${step.title}</h5>
			<div class="account-tour-body">${stepBody}</div>
			<div class="account-tour-actions">
				<button type="button" class="account-tour-stop">Arrêter</button>
				<div class="account-tour-navigation">
					<button type="button" class="account-tour-previous"${accountTourIndex === 0 ? ' disabled' : ''}>Précédent</button>
					<button type="button" class="account-tour-next">${isLastStep ? 'Terminer' : 'Suivant'}</button>
				</div>
			</div>
		`
		accountTourPopover.querySelector('.account-tour-stop').addEventListener('click', stopAccountTour)
		accountTourPopover.querySelector('.account-tour-jump-select').addEventListener('change', function(){
			const nextIndex = Number(this.value)
			if (!Number.isInteger(nextIndex) || nextIndex === accountTourIndex || nextIndex < 0 || nextIndex >= accountTourSteps.length) {
				return
			}

			const direction = nextIndex > accountTourIndex ? 1 : -1
			accountTourIndex = nextIndex
			renderAccountTourStep(direction)
		})
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
	const accountSettingsModal = document.getElementById('modalAccountSettings')
	if (accountSettingsModal) {
		const formContainer = accountSettingsModal.querySelector('#accountSettingsFormContainer')
		const status = accountSettingsModal.querySelector('[data-account-settings-status]')
		const saveButton = accountSettingsModal.querySelector('[data-account-settings-save]')
		const saveIcon = saveButton.querySelector('[data-account-settings-save-icon]')
		const saveSpinner = saveButton.querySelector('[data-account-settings-save-spinner]')
		const deleteAccountNames = Array.from(accountSettingsModal.querySelectorAll('[data-account-delete-name]'))
		const setStatus = function(label, state = ''){
			status.textContent = label
			status.classList.remove('is-saving', 'is-success', 'is-error')
			if (state) {
				status.classList.add(`is-${state}`)
			}
		}
		const setSaveLoading = function(isLoading){
			saveButton.disabled = isLoading
			saveIcon.hidden = isLoading
			saveSpinner.hidden = !isLoading
		}
		const openAccountSettings = function(){
			if (window.bootstrap?.Modal) {
				window.bootstrap.Modal.getOrCreateInstance(accountSettingsModal).show()
			} else {
				$(accountSettingsModal).modal('show')
			}
		}
		const closeAccountSettings = function(){
			const dismissButton = accountSettingsModal.querySelector('.modal-footer [data-dismiss="modal"]')
			if (dismissButton) {
				dismissButton.click()
				return
			}

			$(accountSettingsModal).modal('hide')
		}

		const deleteForm = accountSettingsModal.querySelector('[data-account-delete-form]')
		const deleteConfirmation = accountSettingsModal.querySelector('[data-account-delete-confirm]')
		if (deleteForm && deleteConfirmation) {
			const deleteButton = deleteForm.querySelector('[data-account-delete-open]')
			const confirmDeleteButton = deleteConfirmation.querySelector('[data-account-delete-confirm-submit]')
			const cancelDeleteButtons = deleteConfirmation.querySelectorAll('[data-account-delete-cancel]')
			let deleteConfirmed = false

			const openDeleteConfirmation = function(){
				deleteConfirmation.hidden = false
				deleteConfirmation.setAttribute('aria-hidden', 'false')
				deleteConfirmation.querySelector('.account-delete-confirm-actions [data-account-delete-cancel]')?.focus()
			}
			const closeDeleteConfirmation = function(){
				deleteConfirmation.hidden = true
				deleteConfirmation.setAttribute('aria-hidden', 'true')
				deleteButton?.focus()
			}

			deleteForm.addEventListener('submit', function(event){
				if (deleteConfirmed) {
					return
				}

				event.preventDefault()
				openDeleteConfirmation()
			})
			confirmDeleteButton?.addEventListener('click', function(){
				deleteConfirmed = true
				deleteForm.requestSubmit()
			})
			cancelDeleteButtons.forEach(button => button.addEventListener('click', closeDeleteConfirmation))
			deleteConfirmation.addEventListener('click', function(event){
				if (event.target === deleteConfirmation) {
					closeDeleteConfirmation()
				}
			})
			deleteConfirmation.addEventListener('keydown', function(event){
				if ('Escape' === event.key) {
					event.stopPropagation()
					closeDeleteConfirmation()
				}
			})
		}

		accountSettingsModal.addEventListener('submit', function(event){
			const form = event.target.closest('#accountSettingsForm')
			if (!form) {
				return
			}

			event.preventDefault()
			setSaveLoading(true)
			setStatus('Enregistrement...', 'saving')

			fetch(form.action, {
				method: form.method || 'POST',
				body: new FormData(form),
				headers: {'X-Requested-With': 'XMLHttpRequest'}
			})
				.then(async response => ({response, payload: await response.json()}))
				.then(({response, payload}) => {
					if (!response.ok || !payload.saved) {
						if (payload.form) {
							formContainer.innerHTML = payload.form
						}
						throw new Error(payload.error || 'Validation impossible')
					}

					const datas = document.getElementById('datas')
					const accountTitle = document.querySelector('.account-title h1')
					const overdraft = document.getElementById('overdraftAuthorization')
					if (accountTitle) {
						accountTitle.textContent = payload.account.libelle
					}
					deleteAccountNames.forEach(element => {
						element.textContent = payload.account.libelle
					})
					if (datas) {
						const overdraftLimit = Number(payload.account.decouvert || 0) * -1
						datas.dataset.decouvert = String(overdraftLimit)
						$(datas).data('decouvert', overdraftLimit)
					}
					if (overdraft) {
						overdraft.dataset.moneyAmount = payload.account.decouvert || 0
					}
					syncMoneyAmounts()
					setStatus('Mise à jour du tableau...', 'saving')
					return Promise.resolve(updateTables()).then(() => {
						setStatus('Enregistré', 'success')
						closeAccountSettings()
					})
				})
				.catch(error => {
					if (error.message !== 'Validation impossible') {
						console.log('Erreur lors de la modification du compte: ' + error)
					}
					setStatus('Veuillez corriger le formulaire.', 'error')
				})
				.finally(() => {
					setSaveLoading(false)
				})
		})

		if (accountSettingsModal.dataset.openOnLoad === '1') {
			window.setTimeout(openAccountSettings, 120)
		}
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

	syncDetailMonthSummary()
	syncMonthRangeFields()

	const monthDetailToggle = document.getElementById('monthDetailToggle')
	const monthDetailMenu = document.getElementById('monthDetailMenu')
	if (monthDetailToggle && monthDetailMenu) {
		const closeMonthDetailMenu = function(){
			monthDetailMenu.hidden = true
			monthDetailToggle.setAttribute('aria-expanded', 'false')
		}

		monthDetailToggle.addEventListener('click', function(event){
			event.preventDefault()
			const opens = monthDetailMenu.hidden
			monthDetailMenu.hidden = !opens
			monthDetailToggle.setAttribute('aria-expanded', opens ? 'true' : 'false')
		})
		document.addEventListener('click', function(event){
			if (monthDetailMenu.hidden || monthDetailMenu.contains(event.target) || monthDetailToggle.contains(event.target)) {
				return
			}

			closeMonthDetailMenu()
		})
		document.addEventListener('keydown', function(event){
			if ('Escape' !== event.key || monthDetailMenu.hidden) {
				return
			}

			closeMonthDetailMenu()
			monthDetailToggle.focus()
		})
	}

	////////////
	// ON EVENTS
	////////////
	$('body').on('click', '[data-account-display-tab]', function(){
		setAccountDisplayTab(this.dataset.accountDisplayTab)
	})

	$('body').on('click', '[data-month-display-value]', function(){
		const monthDisplayInput = document.getElementById('monthDisplay')
		if (!monthDisplayInput || monthDisplayInput.value === this.dataset.monthDisplayValue) {
			return
		}
		monthDisplayInput.value = this.dataset.monthDisplayValue
		$(monthDisplayInput).trigger('change')
	})

	$('body').on('change', '[data-month-display-select]', function(){
		const monthDisplayInput = document.getElementById('monthDisplay')
		if (!monthDisplayInput || !this.value || monthDisplayInput.value === this.value) {
			syncMonthDisplaySelect(monthDisplayInput?.value || document.getElementById('datas')?.dataset.monthdisplay || '')
			return
		}
		monthDisplayInput.value = this.value
		$(monthDisplayInput).trigger('change')
	})

	$('body').on('change', '#monthDisplay', function(){
		const monthDisplayInput = this
		const datas = $('#datas')
		const previousMonthDisplay = datas.data('monthdisplay')
		const monthDisplay = monthDisplayInput.value
		if (!monthDisplay) {
			return
		}
		const monthRange = getMonthRangeParams()
		const detailMonths = getSelectedDetailMonths().join(',')
		const url = new URL(window.location.href)

		datas
			.data('monthdisplay', monthDisplay)
			.attr('data-monthdisplay', monthDisplay)
			.data('monthsbefore', monthRange.months_before)
			.attr('data-monthsbefore', monthRange.months_before)
			.data('monthsafter', monthRange.months_after)
			.attr('data-monthsafter', monthRange.months_after)
			.data('selectedmonth', selectedMonth || '')
			.attr('data-selectedmonth', selectedMonth || '')
			.data('selectedyear', selectedMonth ? (selectedYear || '') : '')
			.attr('data-selectedyear', selectedMonth ? (selectedYear || '') : '')
			.data('detailmonths', detailMonths)
			.attr('data-detailmonths', detailMonths)
		url.searchParams.set('months', monthDisplay)
		url.searchParams.set('months_before', monthRange.months_before)
		url.searchParams.set('months_after', monthRange.months_after)
		url.searchParams.set('detail_months', detailMonths)
		url.searchParams.set('detail_months_set', '1')
		if (selectedMonth) {
			url.searchParams.set('selected_month', selectedMonth)
			url.searchParams.set('selected_year', selectedYear || '')
		} else {
			url.searchParams.delete('selected_month')
			url.searchParams.delete('selected_year')
		}
		window.history.replaceState(window.history.state, '', url)
		syncMonthRangeFields()
		setMonthDisplayControlsDisabled(true)

		updateTables()
			.fail(function(){
				datas
					.data('monthdisplay', previousMonthDisplay)
					.attr('data-monthdisplay', previousMonthDisplay)
				syncMonthDisplaySelect(previousMonthDisplay)
				url.searchParams.set('months', previousMonthDisplay)
				window.history.replaceState(window.history.state, '', url)
				syncMonthRangeFields()
			})
			.always(function(){
				setMonthDisplayControlsDisabled(false)
			})
	})

	$('body').on('click', '#monthCustomApply', function(){
		const datas = $('#datas')
		const previousMonthDisplay = datas.data('monthdisplay')
		const previousBefore = document.getElementById('datas')?.dataset.monthsbefore || '1'
		const previousAfter = document.getElementById('datas')?.dataset.monthsafter || '3'
		const monthDisplay = getCustomMonthDisplayMode()
		const range = getMonthRangeParams()
		const detailMonths = getSelectedDetailMonths().join(',')
		const url = new URL(window.location.href)

		syncMonthRangeInputs(range)
		datas
			.data('monthdisplay', monthDisplay)
			.attr('data-monthdisplay', monthDisplay)
			.data('monthsbefore', range.months_before)
			.attr('data-monthsbefore', range.months_before)
			.data('monthsafter', range.months_after)
			.attr('data-monthsafter', range.months_after)
			.data('detailmonths', detailMonths)
			.attr('data-detailmonths', detailMonths)
		syncMonthDisplaySelect(monthDisplay)
		url.searchParams.set('months', monthDisplay)
		url.searchParams.set('months_before', range.months_before)
		url.searchParams.set('months_after', range.months_after)
		url.searchParams.set('detail_months', detailMonths)
		url.searchParams.set('detail_months_set', '1')
		window.history.replaceState(window.history.state, '', url)
		$('#monthCustomBasis, #monthRangeBefore, #monthRangeAfter, #monthCustomApply').prop('disabled', true)

		updateTables()
			.fail(function(){
				const beforeInput = document.getElementById('monthRangeBefore')
				const afterInput = document.getElementById('monthRangeAfter')
				if (beforeInput) {
					beforeInput.value = previousBefore
				}
				if (afterInput) {
					afterInput.value = previousAfter
				}
				datas
					.data('monthdisplay', previousMonthDisplay)
					.attr('data-monthdisplay', previousMonthDisplay)
				syncMonthDisplaySelect(previousMonthDisplay)
				syncMonthRangeUrl()
				url.searchParams.set('months', previousMonthDisplay)
				window.history.replaceState(window.history.state, '', url)
			})
			.always(function(){
				$('#monthCustomBasis, #monthRangeBefore, #monthRangeAfter, #monthCustomApply').prop('disabled', false)
			})
	})

	$('body').on('change', '.month-detail-checkbox', function(){
		const previousDetailMonths = document.getElementById('datas')?.dataset.detailmonths || ''

		updateDetailMonths(previousDetailMonths)
	})

	$('body').on('change', '#monthDetailMasterCheckbox', function(){
		const previousDetailMonths = document.getElementById('datas')?.dataset.detailmonths || ''
		const checked = this.checked

		getDetailMonthCheckboxes().forEach(checkbox => {
			checkbox.checked = checked
		})
		updateDetailMonths(previousDetailMonths)
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
		if ($(event.target).closest('.last-actions').length || document.body.classList.contains('account-tour-active')) {
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
		toggleSelectedMonth(this.dataset.month, this.dataset.year)
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
		toggleSelectedMonth(this.dataset.month, this.dataset.year)
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

// JS IMPORT
import { ucFirst } from '../service/service.js';
import { rememberLastSessionOperationCell, updateTables } from './compte.js';
import { money_display } from '../service/service.js';
import { number_toInput } from '../service/service.js';
import { notifySiteUpdate } from '../service/siteSync.js';

// CSS
import '../../styles/compte/modalOperation.css';

$(document).ready(function(){
	const getMoneyDisplayFormat = function(){
		return document.getElementById('datas')?.dataset.moneydisplayformat || 'comma'
	}
	const getMoneyCurrency = function(){
		return document.getElementById('datas')?.dataset.moneycurrency || 'EUR'
	}
	const shouldTrimMoneyZeros = function(){
		return document.getElementById('datas')?.dataset.moneytrimzeros === '1'
	}
	const shouldShowZeroDecimals = function(){
		return document.getElementById('datas')?.dataset.moneyshowzerodecimals !== '0'
	}

	////////////
	// ON LOAD
	////////////

	var
		_add = '',
		_tboby = '',
		_input_datas = {},
		_pending_duplicates = [],
		_duplicate_source_row = null,
		_duplicate_parent_modal = null,
		_duplicate_parent_backdrop = null
	;
	const isOperationReadOnly = function(){
		return $('#modalOperation').attr('data-read-only') === '1'
	}

	////////////
	// ON EVENTS
	////////////

	/** Chargement **/

	// Get Operations
	$("body").not('.counterEdit, .td_category_libelle, .td_subcategory_libelle').on("click", ".edit td:not(.counterEdit, .td_category_libelle, .td_subcategory_libelle)", function(e){

		let
			sc_id = $(this).data('scid'),
			sign = $(this).parent().parent().parent().data('sign'),
			month = $(this).data('month'),
			months = $('#datas').data('months'),
			year = $(this).data('year') || $('#datas').data('year'),
			anticipe = $(this).data('anticipe') == 1 ? 1 : 0,
			accountId = $(this).closest('tr').data('accountid')
		;

		$('#modalOperation .modal-header')
			.removeClass(sign ? 'bck_neg' : 'bck_pos')
			.addClass(sign ? 'bck_pos' : 'bck_neg')

		$('#modalOperation .modal-content')
			.removeClass(sign ? 'border_neg' : 'border_pos')
			.addClass(sign ? 'border_pos' : 'border_neg')

		getOperationsDatas(sc_id, sign, months, month, year, anticipe, accountId)
	})


	/** Change **/

	// Switch
	$("body").on("click", ".switch", function(e){
		toggleInputNumberAnticipe($(this).parent('td').parent('tr'))
		calculSolde()
		checkFormEditDel()
		controlOperation()
	})

	// FocusOut delete
	$("body").on("focusout", ".inputNumber, .inputAnticipe", function(e){
		focusOutDelete($(this))
	})

	// Input number + Anticipe -> Calcul/Check/Control + monnaie Style inputToDiv
	$("body").on("input", ".inputNumber, .inputAnticipe", function(e){
		calculSolde()
		checkFormEditDel()
		controlOperation()
		stopDelete($(this).parent().parent())
	})

	// Input Date + Comment -> Calcul/Check/Control
	$("body").on("input change", ".inputDay, .inputMonth, .inputYear, .inputComment", function(e){
		if ($(this).is('.inputMonth, .inputYear')) {
			syncOperationDateDays($(this).closest('tr'))
		}
		checkFormEditDel()
		controlOperation()
	})

	// add/delete -> Calcul/Check/Control
	$("body").on("click", ".deleteAdd, #butOpeAdd, .delete", function(e){
		calculSolde()
		controlOperation()
	})

	// Toggle divToInput
	$("body").on("click", ".td_number, .td_anticipe, .td_switch, .td_date, .td_comment", function(e){
		if (isOperationReadOnly()) {
			return
		}

		if (!$(this).parent().hasClass('tr_del')){
			toggleFormMod($(this).parent())
			controlOperation()
			checkFormEditDel()
		}
	})

	// Toggle inputToDiv (cog)
	$("body").on("click", ".noForm", function(e){
		if (!$(this).hasClass('invalid')){
			toggleFormMod($(this).parent().parent().parent().parent(), false)
		}
		controlOperation()
		checkFormEditDel()
	})

	// Toggle inputToDiv (short button)
	$("body").on("click", ".trButStopFormMod", function(e){
		toggleFormMod($(this).parent().parent().parent(), false)
		controlOperation()
		checkFormEditDel()
	})

	// formModFull (Big button)
	$("body").on("click", "#butFullToggleFormMod", function(e){
		$(this).val() == 0
			? formModFull()
			: formModFull(false)
	})


	/** Crud **/

	// Add 1 input
	$("body").on("click", "#butOpeAdd", function(e){
		addOpe()
	})

	// Delete add row (cog)
	$("body").on("click", ".deleteAdd", function(e){
		deleteAddOpe($(this).parent().parent().parent().parent('.tr_add'))
	})

	// Delete add ope (short button)
	$("body").on("click", ".trButDelAdd", function(e){
		deleteAddOpe($(this).parent().parent().parent('.tr_add'))
	})

	// Delete full add ope (Big button)
	$("body").on("click", "#butFullDelAdd", function(e){
		$('.tr_add').each(function(){
			clearPendingDuplications($(this))
			$(this).remove()
		})
		calculSolde()
		checkFormEditDel()
	})

	// Reset 1 ope edit
	$("body").on("click", ".trButCancelEdit", function(e){
		const row = $('#ope_id_' + $(this).data('opeid'))
		clearPendingDuplications(row)
		resetEdit(row.attr('id'))
		calculSolde()
	})

	// Reset all ope edit
	$("body").on("click", "#butFullCancelEdit", function(e){
		resetAllEdit()
		calculSolde()
	})

	// Delete ope
	$("body").on("click", ".delete", function(e){
		deleteOpe($(this).data('opeid'))
	})

	// Duplicate operation on remaining months
	$("body").on("click", ".duplicateOperation", function(e){
		e.preventDefault()
		e.stopPropagation()
		$(this).closest('.btn-group')
			.removeClass('show')
			.find('.dropdown-toggle')
			.attr('aria-expanded', 'false')
			.siblings('.dropdown-menu')
			.removeClass('show')
		openDuplicateModal($(this).closest('tr'))
	})

	$("body").on("change", ".operationDuplicateMonth", syncDuplicateApplyButton)

	$("body").on("change", "#operationDuplicateAll", function(){
		$('#modalOperationDuplicate .operationDuplicateMonth').prop('checked', $(this).prop('checked'))
		syncDuplicateApplyButton()
	})

	$("body").on("click", "#operationDuplicateApply", function(e){
		e.preventDefault()
		applyDuplicateSelection()
	})

	$("body").on("click", ".operationDuplicateDismiss", function(e){
		e.preventDefault()
		e.stopPropagation()
		closeDuplicateModal()
	})

	$('#modalOperationDuplicate')
		.on('shown.bs.modal', function(){
			$('.modal-backdrop').last().addClass('operation-duplicate-backdrop')
		})
		.on('hidden.bs.modal', function(){
			if (_duplicate_parent_modal) {
				const parentModal = _duplicate_parent_modal
				const parentBackdrop = _duplicate_parent_backdrop
				_duplicate_parent_modal = null
				_duplicate_parent_backdrop = null

				if (parentBackdrop && parentBackdrop.length > 0) {
					parentBackdrop.appendTo('body')
				}
				parentModal
					.css('display', 'block')
					.removeAttr('aria-hidden')
					.attr('aria-modal', 'true')
					.addClass('show')
				$('body').addClass('modal-open')
				controlOperation()
				checkFormEditDel()
				return
			}

			if ($('#modalOperation').hasClass('show')) {
				$('body').addClass('modal-open')
			}
		})

	// Assign operation to a person associated with the account
	$("body").on("click", ".assignOperation", function(e){
		e.preventDefault()
		e.stopPropagation()
		toggleAssignChooser($(this))
	})

	$("body").on("click", ".changeOperationDate", function(e){
		e.preventDefault()
		e.stopPropagation()
		const option = $(this)
		option.closest('.btn-group')
			.removeClass('show')
			.find('.dropdown-toggle')
			.attr('aria-expanded', 'false')
			.siblings('.dropdown-menu')
			.removeClass('show')
		const row = option.closest('tr')
		row.find('.inputMonth').length > 0
			? disableOperationDateMove(row)
			: enableOperationDateMove(row)
	})

	$("body").on("click", ".operation-assign-choice", function(e){
		e.stopPropagation()
	})

	$("body").on("click", ".operationAssignApply", function(e){
		e.preventDefault()
		e.stopPropagation()
		const choice = $(this).closest('.operation-assign-choice')
		const select = choice.find('.operationAssignSelect')
		assignOperation($(this).closest('tr'), select.val(), select.find('option:selected').text())
		choice.remove()
	})

	// Revive ope
	$("body").on("click", ".trButRevive", function(e){
		reviveOpe($(this).data('opeid'))
	})

	// Revive all ope
	$("body").on("click", "#butFullRevive", function(e){
		reviveAllOpe()
	})


	/** Save **/

	// Save
	$("body").on("click", "#modalOperationSaveClose", function(e){
		if (!controlOperation()){
			e.preventDefault()
			e.stopPropagation()
			$("#operation_tab tbody .alerteOpe:first").focus()
			return false
		}
		sauvegarde()
	})


	////////////
	// FONCTIONS
	////////////

	/** Chargement **/

	function getOperationsDatas(sc_id, sign, months, month, year, anticipe, accountId){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_operation', { sc: sc_id, year: year, month: month, sign: sign }),
			timeout: 15000,
			beforeSend: function(){
				saveMod(false)
				meta1(year, months[month])
				spinner(true)
				$('#butFullCancelEdit, #butFullDelAdd, #butFullRevive').hide()
			},
			success: function(response){

				_add = response.addRender
				_tboby = response.tBodyRender
				$('#modalOperation').attr('data-read-only', response.canEdit ? '0' : '1')

				meta2(response.category_libelle, response.subcategory_libelle, sign)
				showTbody(_tboby, year, month, response.days_in_month, sc_id, sign, anticipe, accountId, response.members)

				if (!response.canEdit) {
					$('#butFullToggleFormMod').prop('disabled', true)
				} else if (response.operations.length == 0) {
					$('#butFullToggleFormMod').prop('disabled', true)
					addOpe()
				} else {
					$('#butFullToggleFormMod').prop('disabled', false)
				}

				spinner(false)
				getInputDatas()
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
				spinner(false)
			}
		})
	}

	// Toggle spinner
	function spinner(etat){
		etat
			? $('#modal_ope_spinner').show()
			: $('#modal_ope_spinner').hide()
	}

	// Clean Text header + body
	function meta1(year, month){
		_pending_duplicates = []
		_duplicate_source_row = null

		// HEAD DATE
		$('#date_annee').text(year)
		$('#date_mois').text(ucFirst(month))

		// HEAD LIBELLE + COLOR
		$('#category').text('.............')
		$('#subcategory').text('.............')
		$('#category, #subcategory, #solde').removeClass("total_month_full_pos").removeClass("total_month_full_neg")

		// BODY
		$('#operation_tab tbody tr').not('#tr_solde, #solde_tr_collabo').remove()
		$('#tr_solde').hide()
		$('#solde').text('0').hide()
		formModFull(false)
	}

	// Text header + show body
	function meta2(cat_libelle, subcat_libelle, sign){

		// HEAD LIBELLE
		$('#category').text(ucFirst(cat_libelle))
		$('#subcategory').text(ucFirst(subcat_libelle))
		$('#solde, #tr_solde').show()
		sign = sign == 1 ? 'pos' : 'neg'
		$('#category, #subcategory, #solde').addClass('total_month_full_' + sign)
	}

	// Show Tbody
	function showTbody(render, year, month, daysInMonth, sc_id, sign, anticipe, accountId, members = []){

		// Update tbody
		$('#operation_tab tbody')
			.data('year', year)
			.data('month', month)
			.data('daysinmonth', daysInMonth)
			.data('scid', sc_id)
			.data('sign', sign)
			.data('anticipe', anticipe)
			.data('accountid', accountId)
			.data('members', members)
		;

		// Clean Tbody
		$('#operation_tab tbody tr')
			.not('#tr_solde, #solde_tr_collabo')
			.remove()
		;

		$('#operation_tab tbody').append(render)
		$('#solde_tr_collabo').insertAfter('#operation_tab tbody tr:last')
		$('#tr_solde').insertAfter('#operation_tab tbody tr:last')
		calculSolde()
	}

	// Récupère les datas des input pour control édition
	function getInputDatas(){

		// Reset
		_input_datas = {}

		// Number + Anticipe
		$('#operation_tab .tr_ope').each(function(index, div){
			let id = div.id
			const dateParts = operationDateParts($(div))
			_input_datas[id+'_number'] = number_toInput($('#' + id + ' .td_number').text().trim())
			_input_datas[id+'_anticipe'] = number_toInput($('#' + id + ' .td_anticipe').text().trim())
			_input_datas[id+'_date'] = String(dateParts.day).padStart(2, '0')
			_input_datas[id+'_month'] = String(dateParts.month).padStart(2, '0')
			_input_datas[id+'_year'] = String(dateParts.year)
			_input_datas[id+'_comment'] = $('#' + id + ' .td_comment').text().trim()
			_input_datas[id+'_assignee'] = rowAssignee($(div)) || ''
		})
	}


	/** Change **/

	// Toggle input Number <-> Anticipe
	function toggleInputNumberAnticipe(tr){

		let
			input = tr.find('input'),
			val = input.val(),
			clas = input.attr('class')
		;

		// Add anticipe
		if (clas.indexOf("inputNumber") == 0){
			tr.find('.td_anticipe').append("<input class='inputAnticipe' type='number' step='0.01' value='"+val+"' min='0' />")
			tr.find('.td_number').empty()

		// Add number
		} else if(clas.indexOf("inputAnticipe") == 0){
			tr.find('.td_number').append("<input class='inputNumber' type='number' step='0.01' value='"+val+"' min='0' />")
			tr.find('.td_anticipe').empty()
		}
	}

	// Mise à jour du solde
	function calculSolde(){

		let
			sign = $('#operation_tab tbody').data('sign'),
			counterSign = sign == 'pos' ? 'neg' : 'pos',
			solde = 0,
			solde_fait = 0,
			solde_anticipe = 0
		;

		// Sous-total
		$(".td_number").not('#operation_tab .tr_del .td_number').each(function(index, value){
			if ($(this).is(":visible")){

				let val = $(this).find('.inputNumber').val()

				solde_fait = val != undefined && val != ''
					? solde_fait + parseFloat(val)
					: $(this).text().trim() != ''
						? solde_fait + parseFloat(number_toInput($(this).text().trim()))
						: solde_fait
			}
		})

		$(".td_anticipe").not('#operation_tab .tr_del .td_anticipe').each(function(index, value){
			if ($(this).is(":visible")){

				let val = $(this).find('.inputAnticipe').val()

				solde_anticipe = val != undefined && val != ''
					? solde_anticipe + parseFloat(val)
					: $(this).text().trim() != ''
						? solde_anticipe + parseFloat(number_toInput($(this).text().trim()))
						: solde_anticipe
			}
		})

		// Math
		solde_fait = Math.round((solde_fait)*100)/100
		solde_anticipe = Math.round((solde_anticipe)*100)/100
		solde = Math.round((parseFloat(solde_fait) + parseFloat(solde_anticipe))*100)/100

		$('#soldeReel').text(money_display(solde_fait, getMoneyDisplayFormat(), getMoneyCurrency(), shouldTrimMoneyZeros(), shouldShowZeroDecimals()))
		$('#soldeAnticipe').text(money_display(solde_anticipe, getMoneyDisplayFormat(), getMoneyCurrency(), shouldTrimMoneyZeros(), shouldShowZeroDecimals()))
		$('#solde').text(money_display(solde, getMoneyDisplayFormat(), getMoneyCurrency(), shouldTrimMoneyZeros(), shouldShowZeroDecimals()))

		// Color Reel
		$('#soldeReel').addClass('total_month_detail_'+ sign).removeClass('total_month_detail_' + counterSign)
	}

	function escapeHtml(value){
		return String(value ?? '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;')
	}

	function rowAssignee(row){
		return row.attr('data-assignee-id') == undefined || row.attr('data-assignee-id') === ''
			? null
			: String(row.attr('data-assignee-id'))
	}

	function rowSourceKey(row){
		return row.attr('id') || 'add_' + row.index()
	}

	function cellTextWithoutWarning(cell){
		const clone = cell.clone()
		clone.find('.operation-field-warning').remove()

		return clone.text().trim()
	}

	function monthDays(year, month){
		return new Date(Number(year), Number(month), 0).getDate()
	}

	function operationPayloadFromRow(row){
		const number = row.find('.inputNumber').val() == undefined
			? number_toInput(cellTextWithoutWarning(row.find('.td_number')))
			: number_toInput(row.find('.inputNumber').val())
		const anticipe = row.find('.inputAnticipe').val() == undefined
			? number_toInput(cellTextWithoutWarning(row.find('.td_anticipe')))
			: number_toInput(row.find('.inputAnticipe').val())
		const dateParts = operationDateParts(row)

		return {
			id: row.attr('id')?.split('_')[2] || null,
			number: number,
			anticipe: anticipe,
			day: dateParts.day,
			month: dateParts.month,
			year: dateParts.year,
			comment: row.find('.inputComment').val() == undefined
				? row.find('.td_comment').text()
				: row.find('.inputComment').val(),
			delete: row.hasClass('tr_del') ? 1 : 0,
			assignee: rowAssignee(row)
		}
	}

	function monthLabel(month){
		const months = $('#datas').data('months') || {}

		return ucFirst(months[month] || `Mois ${month}`)
	}

	function openDuplicateModal(row){
		const payload = operationPayloadFromRow(row)
		const startMonth = Number(payload.month) + 1

		if (!hasOperationAmount(payload.number) && !hasOperationAmount(payload.anticipe)) {
			alert('Saisis un montant avant de dupliquer cette opération.')
			return
		}

		_duplicate_source_row = row
		const modal = $('#modalOperationDuplicate')
		const monthsContainer = modal.find('[data-duplicate-months]')
		const emptyMessage = modal.find('[data-duplicate-empty]')
		const selectAll = modal.find('[data-duplicate-select-all]')
		const sourceKey = rowSourceKey(row)
		const selectedMonths = _pending_duplicates
			.filter(duplicate => duplicate.sourceKey === sourceKey)
			.map(duplicate => Number(duplicate.month))

		monthsContainer.empty()
		modal.find('[data-duplicate-summary]').text(
			`Sélectionne les mois où copier cette opération en ${payload.year}.`
		)

		for (let month = startMonth; month <= 12; month++) {
			const checked = selectedMonths.length === 0 || selectedMonths.includes(month)
				? ' checked'
				: ''
			monthsContainer.append(`
				<label class="operation-duplicate-month">
					<input type="checkbox" class="operationDuplicateMonth" value="${month}"${checked}>
					<span>${escapeHtml(monthLabel(month))}</span>
				</label>
			`)
		}

		emptyMessage.prop('hidden', startMonth <= 12)
		selectAll.prop('hidden', startMonth > 12)
		syncDuplicateApplyButton()

		const parentModal = $('#modalOperation')
		if (parentModal.hasClass('show')) {
			_duplicate_parent_modal = parentModal
			_duplicate_parent_backdrop = $('.modal-backdrop').last().detach()
			$(document).off('focusin.bs.modal')
			parentModal
				.removeClass('show')
				.css('display', 'none')
				.attr('aria-hidden', 'true')
				.removeAttr('aria-modal')
			$('body').removeClass('modal-open')
			modal.modal('show')
			return
		}

		_duplicate_parent_modal = null
		_duplicate_parent_backdrop = null
		modal.modal('show')
	}

	function closeDuplicateModal(){
		$('#modalOperationDuplicate').modal('hide')
		_duplicate_source_row = null
	}

	function syncDuplicateApplyButton(){
		const checkboxes = $('#modalOperationDuplicate .operationDuplicateMonth')
		const selectedCount = checkboxes.filter(':checked').length
		const selectAll = $('#operationDuplicateAll')

		selectAll
			.prop('checked', checkboxes.length > 0 && selectedCount === checkboxes.length)
			.prop('indeterminate', selectedCount > 0 && selectedCount < checkboxes.length)
		$('#operationDuplicateApply').prop('disabled', selectedCount === 0)
	}

	function syncDuplicateRowWarning(row, months){
		const actionMenu = row.find('.operation-action-menu').first()

		actionMenu.find('.operation-duplicate-warning').remove()
		if (months.length === 0) {
			return
		}

		const message = `Duplication sur ${months.length} mois`
		const labels = months.map(month => monthLabel(month))
		const monthsList = labels.length > 1
			? `${labels.slice(0, -1).join(', ')} et ${labels[labels.length - 1]}`
			: labels[0]
		const title = `Mois concernés : ${monthsList}`

		actionMenu.append(
			`<span class="operation-duplicate-warning" title="${escapeHtml(title)}">${message}</span>`
		)
	}

	function clearPendingDuplications(row){
		const sourceKey = rowSourceKey(row)

		_pending_duplicates = _pending_duplicates.filter(duplicate => duplicate.sourceKey !== sourceKey)
		syncDuplicateRowWarning(row, [])
		row.find('.duplicateOperation')
			.removeClass('operation-option-selected')
			.attr('title', "Dupliquer cette opération sur les mois restants de l'année en cours")
	}

	function applyDuplicateSelection(){
		if (_duplicate_source_row === null) {
			return
		}

		const row = _duplicate_source_row
		const payload = operationPayloadFromRow(row)
		const sourceKey = rowSourceKey(row)
		const months = $('#modalOperationDuplicate .operationDuplicateMonth:checked')
			.map(function(){ return Number($(this).val()) })
			.get()

		_pending_duplicates = _pending_duplicates.filter(duplicate => duplicate.sourceKey !== sourceKey)
		months.forEach(function(month){
			_pending_duplicates.push({
				...payload,
				id: null,
				month: month,
				day: Math.min(Number(payload.day), monthDays(payload.year, month)),
				delete: 0,
				sourceKey: sourceKey
			})
		})

		row.toggleClass('tr_edit', months.length > 0)
		row.find('.duplicateOperation')
			.toggleClass('operation-option-selected', months.length > 0)
			.attr(
				'title',
				months.length > 0
					? `${months.length} duplication(s) en attente`
					: "Dupliquer cette opération sur les mois restants de l'année en cours"
			)
		syncDuplicateRowWarning(row, months)
		closeDuplicateModal()
		saveMod(true)
		checkFormEditDel(true)
	}

	function availableMembers(){
		const members = $('#operation_tab tbody').data('members')

		return Array.isArray(members) ? members : []
	}

	function toggleAssignChooser(item){
		const existing = item.siblings('.operation-assign-choice')
		if (existing.length > 0) {
			existing.remove()
			return
		}

		const members = availableMembers()
		if (members.length === 0) {
			item.remove()
			return
		}

		let options = '<option value="">Non attribuée</option>'
		const row = item.closest('tr')
		const selected = rowAssignee(row) || ''
		members.forEach(function(member){
			const id = String(member.id)
			options += `<option value="${escapeHtml(id)}"${id === selected ? ' selected' : ''}>${escapeHtml(member.name)}</option>`
		})

		item.after(`
			<li class="operation-assign-choice">
				<select class="operationAssignSelect" aria-label="Personne attribuée">${options}</select>
				<button type="button" class="operationAssignApply" title="Valider l'attribution">
					<i class="fa-solid fa-check" aria-hidden="true"></i>
				</button>
			</li>
		`)
	}

	function syncAssignOption(row, assignee, label = ''){
		row.find('.assignOperation')
			.toggleClass('operation-option-selected', assignee !== null)
			.attr(
				'title',
				assignee === null ? 'Attribuer cette opération à une personne' : `Attribuée à ${label}`
			)
	}

	function memberLabel(memberId){
		const member = availableMembers().find(item => String(item.id) === String(memberId))

		return member?.name || ''
	}

	function assignOperation(row, assignee, label){
		const sourceKey = rowSourceKey(row)
		const normalizedAssignee = assignee === '' ? null : String(assignee)

		row.attr('data-assignee-id', normalizedAssignee || '')
		_pending_duplicates = _pending_duplicates.map(duplicate => duplicate.sourceKey === sourceKey
			? { ...duplicate, assignee: normalizedAssignee }
			: duplicate
		)
		syncAssignOption(row, normalizedAssignee, label)

		saveMod(true)
		checkFormEditDel(true)
		controlOperation()
	}

	function todayParts(){
		const today = new Date()

		return {
			year: today.getFullYear(),
			month: today.getMonth() + 1,
			day: today.getDate()
		}
	}

	function operationDateParts(row){
		const day = row.find('.inputDay').val() == undefined
			? row.find('.td_date .day').text().trim()
			: row.find('.inputDay').val()
		const month = row.find('.inputMonth').val() == undefined
			? row.find('.td_date .date-month').text().trim() || $('#operation_tab tbody').data('month')
			: row.find('.inputMonth').val()
		const year = row.find('.inputYear').val() == undefined
			? row.find('.td_date .date-year').text().trim() || $('#operation_tab tbody').data('year')
			: row.find('.inputYear').val()

		return {
			year: Number(year),
			month: Number(month),
			day: Number(day)
		}
	}

	function operationDateIsValid(dateParts){
		return Number.isInteger(dateParts.year)
			&& dateParts.year >= 1900
			&& dateParts.year <= 2100
			&& Number.isInteger(dateParts.month)
			&& dateParts.month >= 1
			&& dateParts.month <= 12
			&& Number.isInteger(dateParts.day)
			&& dateParts.day >= 1
			&& dateParts.day <= monthDays(dateParts.year, dateParts.month)
	}

	function syncOperationDateValidity(row){
		const yearInput = row.find('.inputYear')
		if (yearInput.length === 0) {
			return true
		}

		const isValid = operationDateIsValid(operationDateParts(row))
		yearInput
			.toggleClass('alerteOpe', !isValid)
			.attr('title', isValid ? "Année de l'opération" : 'Saisis une année comprise entre 1900 et 2100')

		return isValid
	}

	function syncOperationDateDays(row){
		const dayInput = row.find('.inputDay')
		const dateParts = operationDateParts(row)
		if (
			dayInput.length === 0
			|| !Number.isInteger(dateParts.year)
			|| dateParts.year < 1900
			|| dateParts.year > 2100
			|| !Number.isInteger(dateParts.month)
			|| dateParts.month < 1
			|| dateParts.month > 12
		) {
			return
		}

		const days = monthDays(dateParts.year, dateParts.month)
		const selectedDay = Math.min(Math.max(dateParts.day || 1, 1), days)
		let options = ''
		for (let day = 1; day <= days; day++) {
			options += `<option value="${day}"${day === selectedDay ? ' selected' : ''}>${day}</option>`
		}
		dayInput.html(options)
	}

	function enableOperationDateMove(row){
		if (row.find('.inputDay').length === 0) {
			toggleFormMod(row)
		}
		if (row.find('.inputMonth').length > 0) {
			return
		}

		const dateParts = operationDateParts(row)
		let monthOptions = ''
		for (let month = 1; month <= 12; month++) {
			monthOptions += `
				<option value="${month}"${month === dateParts.month ? ' selected' : ''}>
					${String(month).padStart(2, '0')}
				</option>
			`
		}

		row.find('.date-month').replaceWith(
			`<select class="inputMonth" aria-label="Mois de l'opération">${monthOptions}</select>`
		)
		row.find('.date-year').replaceWith(
			`<input class="inputYear" type="number" min="1900" max="2100" step="1" value="${dateParts.year}" aria-label="Année de l'opération">`
		)
		row.find('.td_date').addClass('operation-date-editing')
		row.find('.changeOperationDate')
			.addClass('operation-option-selected')
			.attr('title', "Le mois et l'année sont modifiables dans la colonne Date")
		syncOperationDateDays(row)
		checkFormEditDel()
		controlOperation()
	}

	function disableOperationDateMove(row){
		const dateParts = operationDateParts(row)
		if (!operationDateIsValid(dateParts)) {
			controlOperation()
			return
		}

		row.find('.inputMonth').replaceWith(
			`<span class="date-month">${String(dateParts.month).padStart(2, '0')}</span>`
		)
		row.find('.inputYear').replaceWith(
			`<span class="date-year">${dateParts.year}</span>`
		)
		row.find('.td_date').removeClass('operation-date-editing')
		row.find('.changeOperationDate')
			.removeClass('operation-option-selected')
			.attr('title', "Modifier le mois et l'année de cette opération")
		checkFormEditDel()
		controlOperation()
	}

	function isBeforeToday(dateParts){
		const today = todayParts()

		if (dateParts.year !== today.year) {
			return dateParts.year < today.year
		}
		if (dateParts.month !== today.month) {
			return dateParts.month < today.month
		}

		return dateParts.day < today.day
	}

	function hasOperationAmount(value){
		const normalized = number_toInput(value)

		return normalized !== '' && Number(normalized) !== 0
	}

	function isAfterCurrentMonth(dateParts){
		const today = todayParts()

		if (dateParts.year !== today.year) {
			return dateParts.year > today.year
		}

		return dateParts.month > today.month
	}

	function setOperationFieldWarning(input, message = ''){
		input.removeClass('alerteOpeWarning')
		input.siblings('.operation-field-warning').remove()

		if (message === '') {
			return
		}

		input
			.addClass('alerteOpeWarning')
			.after(`<span class="operation-field-warning">${message}</span>`)
	}

	function syncOperationTimingWarnings(row){
		const inputNumber = row.find('.inputNumber')
		const inputAnticipe = row.find('.inputAnticipe')
		const dateParts = operationDateParts(row)

		setOperationFieldWarning(inputAnticipe)
		setOperationFieldWarning(inputNumber)
		row.find('.inputDay option:disabled').prop('disabled', false)
		row.find('.inputDay, .td_date')
			.removeClass('alerteOpe alerteOpeWarning')
			.removeAttr('title')

		if (!operationDateIsValid(dateParts)) {
			return
		}

		if (hasOperationAmount(inputAnticipe.val()) && isBeforeToday(dateParts)) {
			setOperationFieldWarning(inputAnticipe, "Date passée")
		}
		if (hasOperationAmount(inputNumber.val()) && isAfterCurrentMonth(dateParts)) {
			setOperationFieldWarning(inputNumber, "Date non atteinte")
		}
	}

	function hasBlockingOperationAlert(){
		return $('#operation_tab tbody .alerteOpe, #operation_tab tbody .alerte-doublon').length > 0
	}

	function syncOperationSaveButton(control = true){
		$('#modalOperationSaveClose').prop('disabled', !control || hasBlockingOperationAlert())
	}

	// Check Si formMod + Edit + +Del + SaveMod (Alerte visuelle)
	function checkFormEditDel(isSaveMod = false){

		// Hide butFullCancelEdit
		$('#butFullCancelEdit').hide().prop('disabled', true)

		// Hide butFullDelAdd ?
		$('#operation_tab').find('tbody .tr_add').length == 0
			? $('#butFullDelAdd').hide()
			: null

		// Hide butFullRevive ?
		$('#operation_tab').find('tbody .tr_del').length == 0
			? $('#butFullRevive').hide()
			: null

		// Check Number + Anticipe
		$('#operation_tab .tr_ope').each(function(index, tr){

			let 
				id = tr.id,

				input_number = $('#' + id + ' .inputNumber'),
				input_anticipe = $('#' + id + ' .inputAnticipe'),
				input_date = $('#' + id + ' .inputDay'),
				input_month = $('#' + id + ' .inputMonth'),
				input_year = $('#' + id + ' .inputYear'),
				input_comment = $('#' + id + ' .inputComment'),

				td_number = $('#' + id + ' .td_number'),
				td_anticipe = $('#' + id + ' .td_anticipe'),
				td_date = $('#' + id + ' .td_date'),
				td_date_day = $('#' + id + ' .td_date .day'),
				td_comment = $('#' + id + ' .td_comment'),

				tr_edit = false,
				tr_formMod = input_date.length == 1
					? true
					: false,
				date_parts = operationDateParts($(this)),

				number = input_number.length == 1
					? input_number.val()
					: number_toInput($('#' + id + ' .td_number').text().trim()),
				anticipe = input_anticipe.length == 1
					? input_anticipe.val()
					: number_toInput($('#' + id + ' .td_anticipe').text().trim()),
				date = String(date_parts.day).padStart(2, '0'),
				month = String(date_parts.month).padStart(2, '0'),
				year = String(date_parts.year),
				comment = tr_formMod
					? input_comment.val()
					: $('#' + id + ' .td_comment').text().trim()
			;

			// Correct Number
			if (_input_datas[id + '_number'] != number && number != '0' && number != ''){
				tr_edit = true

				_input_datas[id + '_number'] == ''
					? input_number.addClass('input_edit') && td_number.addClass('input_edit_val')
					: input_number.removeClass('input_edit') && td_number.removeClass('input_edit_val')

				number != _input_datas[id + '_anticipe']
					? input_number.addClass('input_edit_val') && td_number.addClass('input_edit_val')
					: input_number.removeClass('input_edit_val') && td_number.removeClass('input_edit_val')
			} else {
				input_number.removeClass('input_edit')
				input_number.removeClass('input_edit_val')
				td_number.removeClass('input_edit_val')
			}

			// Correct Anticipe
			if (_input_datas[id + '_anticipe'] != anticipe && anticipe != '0' && anticipe != ''){
				tr_edit = true

				_input_datas[id + '_anticipe'] == ''
					? input_anticipe.addClass('input_edit') && td_anticipe.addClass('input_edit_val_bis')
					: input_anticipe.removeClass('input_edit') && td_anticipe.removeClass('input_edit_val_bis')

				anticipe != _input_datas[id + '_number']
					? input_anticipe.addClass('input_edit_val_bis') && td_anticipe.addClass('input_edit_val_bis')
					: input_anticipe.removeClass('input_edit_val_bis') && td_anticipe.removeClass('input_edit_val_bis')
			} else {
				input_anticipe.removeClass('input_edit')
				input_anticipe.removeClass('input_edit_val_bis')
				td_anticipe.removeClass('input_edit_val_bis')
			}

			// Date
			if (
				_input_datas[id + '_date'] != date
				|| _input_datas[id + '_month'] != month
				|| _input_datas[id + '_year'] != year
			){
				tr_edit = true
				input_date.add(input_month).add(input_year).addClass('input_edit_val')
				td_date_day
					.add(td_date.find('.date-month'))
					.add(td_date.find('.date-year'))
					.addClass('input_edit_val')
			} else {
				input_date.add(input_month).add(input_year).removeClass('input_edit_val')
				td_date_day
					.add(td_date.find('.date-month'))
					.add(td_date.find('.date-year'))
					.removeClass('input_edit_val')
			}

			// Comment
			if (_input_datas[id + '_comment'] != comment){
				tr_edit = true
				input_comment.addClass('input_edit_val')
				td_comment.addClass('input_edit_val')
			} else {
				input_comment.removeClass('input_edit_val')
				td_comment.removeClass('input_edit_val')
			}

			if ((_input_datas[id + '_assignee'] || '') !== (rowAssignee($(this)) || '')){
				tr_edit = true
			}

			if (_pending_duplicates.some(duplicate => duplicate.sourceKey === rowSourceKey($(this)))){
				tr_edit = true
			}

			// trButStopFormMod ?
			tr_formMod && !tr_edit
				? $('#' + id + ' .trButStopFormMod').show()
				: $('#' + id + ' .trButStopFormMod').hide()

			// trButCancelEdit ?
			tr_edit && tr_formMod
				? $('#' + id + ' .trButCancelEdit').show()
				: $('#' + id + ' .trButCancelEdit').hide()

			// tr_edit + butFullCancelEdit ?
			tr_edit
				? isSaveMod = true && $(this).addClass('tr_edit') && $(this).removeClass('tr_del') && $('#butFullCancelEdit').show().prop('disabled', false)
				: $(this).removeClass('tr_edit')

			// Delete ?
			if ( (number == '' || number == '0') && (anticipe == '' || anticipe == '0') ){
				$(this).removeClass('tr_edit').addClass('tr_del')
				$('#' + id + ' .trButCancelEdit').hide()
				input_number.removeClass('input_edit')
				input_number.removeClass('input_edit_val')
				td_number.removeClass('input_edit_val')
			}
		})

		// isSaveMod true si addMod/tr_del
		if ($('body #operation_tab .tr_add, body #operation_tab .tr_del').length > 0){
			isSaveMod = true
		}

		// Check EditMod
		saveMod(isSaveMod)
	}

	// check si retire tr_del on input
	function stopDelete(tr){
			let 
				id = tr.attr('id'),

				input_number = $('#' + id + ' .inputNumber'),
				input_anticipe = $('#' + id + ' .inputAnticipe'),

				number = input_number.length == 1
					? input_number.val()
					: $('#' + id + ' .td_number').text().trim(),
				anticipe = input_anticipe.length == 1
					? input_anticipe.val()
					: $('#' + id + ' .td_anticipe').text().trim()
			;

			number == '' &&
			anticipe == ''
				? null
				: tr.removeClass('tr_del')
	}


	/** Form mod **/

	// All FormMode
	function formModFull(etat = true){
		$(".tr_ope, .tr_add").not('.tr_del').each(function(index, value){
			toggleFormMod($(this), etat)
		})
		controlOperation()
		checkFormEditDel()
	}

	// Toggle form <-> noForm
	function toggleFormMod(tr, divToInput = true){

		// Stop si ajout non défini
		if (tr.hasClass('tr_add') && tr.find('.inputNumber').length > 0 && tr.find('.inputAnticipe').length > 0){ return false }

		// Div -> Input
		if (divToInput){

			// Stop si déja visible
			if (tr.find('.inputComment').length > 0){ return false }

			let 
				td_number = tr.find('.td_number'),
				td_anticipe = tr.find('.td_anticipe'),
				td_date = tr.find('.td_date'),
				td_comment = tr.find('.td_comment'),

				number = number_toInput(td_number.text().trim()),
				anticipe = number_toInput(td_anticipe.text().trim()),
				date = operationDateParts(tr),
				daysInMonth = monthDays(date.year, date.month),
				comment = td_comment.text().trim(),

				input_number = "<input class='inputNumber' type='number' step='0.01' value='" + number + "' min='0' />",
				input_anticipe = "<input class='inputAnticipe' type='number' step='0.01' value='" + anticipe + "' min='0' />",
				input_date = "<span class='day'><select class='inputDay'/>",
				input_comment = "<input class='inputComment' type='text' value='" + comment + "' />"
			;

			// Switch + Actions
			tr.find('.switch, .btn-group').prop('disabled', false).show()

			// Noform
			tr.find('.btn-group .noForm').removeClass('invalid')

			// Number
			if (number != ''){ td_number.empty().append(input_number) }

			// Anticipe
			if (anticipe != ''){ td_anticipe.empty().append(input_anticipe) }

			// Date
			for (var i = 1; i <= daysInMonth; i++){

				let loopFirst = i == date.day
					? 'selected'
					: ''

				input_date = input_date + "<option value='" + i + "' " + loopFirst + ">" + i + "</option>"
			}
			input_date = input_date
				+ "</select></span>/<span class='date-month'>"
				+ String(date.month).padStart(2, '0')
				+ "</span>/<span class='date-year'>"
				+ date.year
				+ "</span>"
			td_date.empty().append(input_date)

			// Comment
			td_comment.empty().append(input_comment)

			// Button Edit
			tr.hasClass('tr_edit')
				? tr.find('.trButCancelEdit').show() && tr.find('.trButStopFormMod').hide()
				: tr.find('.trButStopFormMod').show()

		// Input -> Div
		} else {

			// Stop si déja visible
			if (tr.find('.inputComment').length == 0){ return false }

			let
				inputNumber = tr.find('.inputNumber'),
				inputAnticipe = tr.find('.inputAnticipe'),
				inputNumberVal = inputNumber.val() == null ? '' : money_display(inputNumber.val(), getMoneyDisplayFormat(), getMoneyCurrency(), shouldTrimMoneyZeros(), shouldShowZeroDecimals()),
				inputAnticipeVal = inputAnticipe.val() == null ? '' : money_display(inputAnticipe.val(), getMoneyDisplayFormat(), getMoneyCurrency(), shouldTrimMoneyZeros(), shouldShowZeroDecimals()),
				inputComment = tr.find('.inputComment'),
				date = operationDateParts(tr)
			;

			if (!operationDateIsValid(date)) {
				controlOperation()
				return false
			}

			// Switch + Action
			tr.find('.switch, .btn-group').prop('disabled', true).hide()

			inputNumber.after(inputNumberVal).remove()
			inputAnticipe.after(inputAnticipeVal).remove()
			tr.find('.td_date')
				.removeClass('operation-date-editing')
				.html(
					"<span class='day'>" + String(date.day).padStart(2, '0') + "</span>"
					+ "/<span class='date-month'>" + String(date.month).padStart(2, '0') + "</span>"
					+ "/<span class='date-year'>" + date.year + "</span>"
				)
			tr.find('.changeOperationDate')
				.removeClass('operation-option-selected')
				.attr('title', "Modifier le mois et l'année de cette opération")
			inputComment.after(inputComment.val()).remove()
		}
	}


	/** Crud **/

	// Add 1 operation
	function addOpe(){
		$('#butFullDelAdd').show()
		$('#operation_tab tbody').append(_add)
		$('#solde_tr_collabo').insertAfter('#operation_tab tbody tr:last')
		$('#tr_solde').insertAfter('#operation_tab tbody tr:last')
		saveMod(true)
		controlOperation()
	}

	// Delete 1 add
	function deleteAddOpe(tr){
		clearPendingDuplications(tr)
		tr.remove()
		calculSolde()
		checkFormEditDel()
	}

	// Reset 1 operation
	function resetEdit(id){
	
		let 
			date = _input_datas[id + '_date'],
			month = _input_datas[id + '_month'],
			year = _input_datas[id + '_year'],
			number = _input_datas[id + '_number'],
			anticipe = _input_datas[id + '_anticipe'],
			input_number = "<input class='inputNumber' type='number' step='0.01' value='" + number + "' min='0' />",
			input_anticipe = "<input class='inputAnticipe' type='number' step='0.01' value='" + anticipe + "' min='0' />"
		;

		resetForm(id, number, anticipe, date, month, year, input_number, input_anticipe)
		controlOperation()
		checkFormEditDel()
	}

	// Reset 1 operation
	function resetForm(id, number, anticipe, date, month, year, input_number, input_anticipe){
		const row = $('#' + id)

		// Number
		if (number != ''){
			$('#' + id + ' .inputNumber').length == 1
				? $('#' + id + ' .inputNumber').val(number)
				: $('#' + id + ' .td_number').append(input_number) && $('#' + id + ' .td_anticipe').empty()

		// Anticipe
		} else {
			$('#' + id + ' .inputAnticipe').length == 1
				? $('#' + id + ' .inputAnticipe').val(anticipe)
				: $('#' + id + ' .td_anticipe').append(input_anticipe) && $('#' + id + ' .td_number').empty()
		}

		// Date
		if (row.find('.inputMonth').length > 0) {
			row.find('.inputYear').val(year)
			row.find('.inputMonth').val(Number(month))
			syncOperationDateDays(row)
		}
		if (row.find('.inputDay').length > 0) {
			row.find('.inputDay').val(Number(date))
		} else {
			row.find('.td_date .day').text(date)
			row.find('.td_date .date-month').text(month)
			row.find('.td_date .date-year').text(year)
		}

		// Comment
		row.find('.inputComment').val(_input_datas[id + '_comment'])

		// Attribution
		const assignee = _input_datas[id + '_assignee'] || null
		row.attr('data-assignee-id', assignee || '')
		syncAssignOption(row, assignee, memberLabel(assignee))
	}

	// Reset all Edit operations
	function resetAllEdit(){

		$('#butFullCancelEdit').hide().prop('disabled', true)
		$('#operation_tab .tr_ope, #operation_tab .tr_add').each(function(){
			clearPendingDuplications($(this))
		})

		$('#operation_tab .tr_edit').each(function(index, div){

			let 
				id = div.id,
				date = _input_datas[id + '_date'],
				month = _input_datas[id + '_month'],
				year = _input_datas[id + '_year'],
				number = _input_datas[id + '_number'],
				anticipe = _input_datas[id + '_anticipe'],
				input_number = "<input class='inputNumber' type='number' step='0.01' value='" + number + "' min='0' />",
				input_anticipe = "<input class='inputAnticipe' type='number' step='0.01' value='" + anticipe + "' min='0' />",
				tr_formMod = $('#' + id + ' .td_date').find('.inputDay').length == 1 ? true : false
			;

			// FormMod
			if (tr_formMod){
				resetForm(id, number, anticipe, date, month, year, input_number, input_anticipe)

			// No FormMod
			} else {

				// Number + Anticipe
				$('#' + id + ' .td_number, #' + id + ' .td_anticipe').empty()
				number != ''
					? $('#' + id + ' .td_number').append(money_display(number, getMoneyDisplayFormat(), getMoneyCurrency(), shouldTrimMoneyZeros(), shouldShowZeroDecimals()))
					: $('#' + id + ' .td_anticipe').append(money_display(anticipe, getMoneyDisplayFormat(), getMoneyCurrency(), shouldTrimMoneyZeros(), shouldShowZeroDecimals()))

				// Date
				$('#' + id + ' .td_date .day').text(date)
				$('#' + id + ' .td_date .date-month').text(month)
				$('#' + id + ' .td_date .date-year').text(year)

				// Comment
				$('#' + id + ' .td_comment').text(_input_datas[id + '_comment'])
			}
		})

		controlOperation()
		checkFormEditDel()
	}

	// Delete 1 ope
	function deleteOpe(id){

		// Reset Ope
		resetEdit('ope_id_' + id)
		toggleFormMod($('#ope_id_' + id), false)
		$('#ope_id_' + id).addClass('tr_del')

		// Buttons
		$('#ope_id_' + id + ' .trButStopFormMod, #ope_id_' + id + ' .trButCancelEdit').hide()
		$('#ope_id_' + id + ' .trButRevive').show()
		$('#butFullRevive').show()

		calculSolde()
		checkFormEditDel(true)
		controlOperation()
	}

	// Delete 1 ope on focusOut
	function focusOutDelete(input){

		if (input.val() == '0' || input.val() == 0 || input.val() == null){
			deleteOpe(input.parent().parent().get(0).id.split('_')[2])
			controlOperation()
		}
	}

	// Revive 1 ope
	function reviveOpe(id){

		let tr = $('#ope_id_' + id)

		tr.removeClass('tr_del')
		toggleFormMod(tr, false)
		$('#ope_id_' + id + ' .trButRevive').hide()

		calculSolde()
		checkFormEditDel()
	}

	// Revive all ope
	function reviveAllOpe(){

		$('#operation_tab .tr_del').each(function(index, tr){
			$(this).removeClass('tr_del')
			$('#' + tr.id + ' .trButRevive').hide()
			toggleFormMod($(this), false)
		})
		calculSolde()
		checkFormEditDel()
	}


	/** Sauvegarde **/

	// Mode save
	function saveMod(etat){

		// ON
		if (etat){
			$('#modalOperation > .modal-dialog > .modal-content > .modal-footer').show()
			$('#modalOperationSaveClose').show()
			syncOperationSaveButton()
			$('#modalOperationClose').prop('title', 'Fermer la fenêtre sans enregistrer').text('Fermer sans enregistrer')

		// OFF
		} else {
			$('.tr_add').remove()
			_pending_duplicates = []
			$('#modalOperationSaveClose').prop('disabled', false).hide()
			$('#saveAdd, .deleteAdd').prop('disabled', true).hide()
			$('#close').prop('disabled', false).prop('title', 'Fermer la fenêtre')
			$('#modalOperation > .modal-dialog > .modal-content > .modal-footer').hide()
			$('#modalOperationClose').prop('title', 'Fermer la fenêtre').text('Fermer')
		}
	}

	function controlOperation(){

		let
			control = true,
			checkChange = false
		;

		$("#operation_tab tbody tr").not('#solde_tr_collabo, #tr_solde').each(function(index, value){

			let
				switch_icon = $(this).find('.switch'),
				input_number = $(this).find('.inputNumber'),
				input_anticipe = $(this).find('.inputAnticipe'),
				input_number_val = input_number.val(),
				input_anticipe_val = input_anticipe.val()
			;

			// 2 Input existants
			if (input_number_val != undefined && input_anticipe_val != undefined){

				let input_number_valid = input_number_val == '' || input_number_val == '0' || input_number_val == 0
					? false
					: true

				let input_anticipe_valid = input_anticipe_val == '' || input_anticipe_val == '0' || input_anticipe_val == 0
					? false
					: true

				// 2 vides
				if (!input_number_valid && !input_anticipe_valid){
					input_number.addClass('alerteOpe').removeClass('alerte-doublon')
					input_anticipe.addClass('alerteOpe').removeClass('alerte-doublon')
					control = false

				// 2 remplis (ne doit plus apparaitre)
				} else if(input_number_valid && input_anticipe_valid){
					input_number.addClass('alerte-doublon').removeClass('alerteOpe')
					input_anticipe.addClass('alerte-doublon').removeClass('alerteOpe')
					control = false

				// 1 rempli et 1 vide
				} else {
					switch_icon.show()
					$(this).find('.noForm').removeClass('invalid')
					input_number_valid
						? input_anticipe.remove() && input_number.removeClass('alerteOpe').removeClass('alerte-doublon')
						: input_number.remove() && input_anticipe.removeClass('alerteOpe').removeClass('alerte-doublon')
				}

			// Only Number valid
			} else if(input_number_val != undefined && input_anticipe_val == undefined){

				let input_number_valid = input_number_val == '' || input_number_val == '0' || input_number_val == 0
					? false
					: true

				if (input_number_valid){
					input_number.removeClass('alerteOpe').removeClass('alerte-doublon')
					switch_icon.show()
				} else {
					input_number.addClass('alerteOpe').removeClass('alerte-doublon')
					switch_icon.hide()
					$("#operation_tab tbody .alerteOpe:first").focus()
					control = false
				}
				checkChange = true

			// Only Anticipe valid
			} else if(input_number_val == undefined && input_anticipe_val != undefined){

				let input_anticipe_valid = input_anticipe_val == '' || input_anticipe_val == '0' || input_anticipe_val == 0
					? false
					: true

				if (input_anticipe_valid){
					input_anticipe.removeClass('alerteOpe').removeClass('alerte-doublon')
					switch_icon.show()
				} else {
					input_anticipe.addClass('alerteOpe').removeClass('alerte-doublon')
					switch_icon.hide()
					$("#operation_tab tbody .alerteOpe:first").focus()
					control = false
				}
				checkChange = true
			}

			if (!syncOperationDateValidity($(this))) {
				control = false
			}
			syncOperationTimingWarnings($(this))
		})

		// Button Statut
		checkChange
			? $('#butFullToggleFormMod').val(1).html("<i class='fas fa-times' title='Retirer les formulaires des lignes'></i>")
			: $('#butFullToggleFormMod').val(0).html("<i class='fas fa-edit'></i>")

		syncOperationSaveButton(control)

		return control
	}

	function sauvegarde(){

		let
			datas = [],
			sc_id = $('#operation_tab tbody').data('scid'),
			month = $('#operation_tab tbody').data('month'),
			year = $('#operation_tab tbody').data('year'),
			sign = $('#operation_tab tbody').data('sign'),
			modifiedAnticipe = $('#operation_tab tbody').data('anticipe') == 1 ? 1 : 0
		;

		$("#operation_tab tbody tr").not('#solde_tr_collabo, #tr_solde').each(function(index, value){
			const row = $(this)

			let operationPayload = operationPayloadFromRow(row)

			if (
				row.hasClass('tr_add')
				|| row.hasClass('tr_del')
				|| row.hasClass('tr_edit')
				|| row.find('.inputNumber, .inputAnticipe, .inputDay, .inputComment').length > 0
				|| operationPayload.assignee
			) {
				const rowTargetsAnticipation = row.find('.inputAnticipe').length > 0
					|| (row.find('.td_anticipe').text().trim() !== '' && row.find('.td_number').text().trim() === '')

				modifiedAnticipe = rowTargetsAnticipation ? 1 : 0
			}

			if (operationPayload.number != null || operationPayload.anticipe != null){
				datas.push(operationPayload)
			}
		})

		_pending_duplicates.forEach(function(duplicate){
			datas.push(duplicate)
		})

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_operation_save', { sc: sc_id, year: year, month: month, sign: sign }),
			data: { datas: datas },
			dataType: 'JSON',
			timeout: 15000,
			beforeSend: function(){
				$('#modalOperationSaveClose').prop('disabled', true)
			},
			success: function(response){
				if (response.save == true){
					const lastOperationCell = {
						scId: sc_id,
						month: month,
						year: year,
						anticipe: modifiedAnticipe
					}

					rememberLastSessionOperationCell(lastOperationCell)
					notifySiteUpdate({ type: 'compte-operation-saved', lastOperationCell: lastOperationCell })
					closeOperationModalAfterSave()
					updateTables()
				} else {
					alert(response.error || 'La sauvegarde a été refusée.')
					controlOperation()
				}
			},
			error: function(error){
				alert(error.responseJSON?.error || 'Erreur pendant la sauvegarde.')
				console.log('Erreur ajax: ' + error)
				controlOperation()
			}
		})
	}

	function closeOperationModalAfterSave(){
		const modal = $('#modalOperation')

		modal.modal('hide')
		if (modal.hasClass('show')) {
			modal
				.removeClass('show')
				.css('display', 'none')
				.attr('aria-hidden', 'true')
				.removeAttr('aria-modal')
			$('.modal-backdrop').remove()
			$('body').removeClass('modal-open').css('padding-right', '')
		}
	}
})

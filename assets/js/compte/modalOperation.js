// JS IMPORT
import { ucFirst } from '../service/service.js';
import { updateTable } from './compte.js';
import { number_format } from '../service/service.js';
import { number_toInput } from '../service/service.js';

// CSS
import '../../styles/compte/modalOperation.css';

$(document).ready(function(){

	////////////
	// ON LOAD
	////////////

	var
		_add = '',
		_tboby = '',
		_input_datas = {}
	;

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
			year = $('#datas').data('year')
		;

		$('.modal-header')
			.removeClass(sign ? 'bck_neg' : 'bck_pos')
			.addClass(sign ? 'bck_pos' : 'bck_neg')

		$('.modal-content')
			.removeClass(sign ? 'border_neg' : 'border_pos')
			.addClass(sign ? 'border_pos' : 'border_neg')

		getOperationsDatas(sc_id, sign, months, month, year)
	})


	/** Change **/

	// Switch
	$("body").on("click", ".switch", function(e){
		toggleInputNumberAnticipe($(this).parent('td').parent('tr'))
		calculSolde()
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
	$("body").on("input", ".inputDay, .inputComment", function(e){
		checkFormEditDel()
	})

	// add/delete -> Calcul/Check/Control
	$("body").on("click", ".deleteAdd, #butOpeAdd, .delete", function(e){
		calculSolde()
		controlOperation()
	})

	// Toggle divToInput
	$("body").on("click", ".td_number, .td_anticipe, .td_switch, .td_date, .td_comment", function(e){

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
		$('.tr_add').remove()
		calculSolde()
		checkFormEditDel()
	})

	// Reset 1 ope edit
	$("body").on("click", ".trButCancelEdit", function(e){
		resetEdit('ope_id_' + $(this).data('opeid'))
	})

	// Reset all ope edit
	$("body").on("click", "#butFullCancelEdit", function(e){
		resetAllEdit()
	})

	// Delete ope
	$("body").on("click", ".delete", function(e){
		deleteOpe($(this).data('opeid'))
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
		sauvegarde()
	})


	////////////
	// FONCTIONS
	////////////

	/** Chargement **/

	function getOperationsDatas(sc_id, sign, months, month, year){

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

				meta2(response.category_libelle, response.subcategory_libelle, sign)
				showTbody(_tboby, year, month, response.days_in_month, sc_id, sign)

				response.operations.length == 0
					? $('#butFullToggleFormMod').prop('disabled', true) && addOpe()
					: $('#butFullToggleFormMod').prop('disabled', false)

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
		if (etat){
			$('.spinner').show()

		} else {
			$('.spinner').hide()
		}
	}

	// Clean Text header + body
	function meta1(year, month){

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
	function showTbody(render, year, month, daysInMonth, sc_id, sign){

		// Update tbody
		$('#operation_tab tbody')
			.data('year', year)
			.data('month', month)
			.data('daysinmonth', daysInMonth)
			.data('scid', sc_id)
			.data('sign', sign)
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
			_input_datas[id+'_number'] = number_toInput($('#' + id + ' .td_number').text().trim())
			_input_datas[id+'_anticipe'] = number_toInput($('#' + id + ' .td_anticipe').text().trim())
			_input_datas[id+'_date'] = $('#' + id + ' .td_date').text().substring(0, 2).trim()
			_input_datas[id+'_comment'] = $('#' + id + ' .td_comment').text().trim()
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

		$('#soldeReel').text(number_format(solde_fait, 2, ',', ' '))
		$('#soldeAnticipe').text(number_format(solde_anticipe, 2, ',', ' '))
		$('#solde').text(number_format(solde, 2, ',', ' '))

		// Color Reel
		$('#soldeReel').addClass('total_month_detail_'+ sign).removeClass('total_month_detail_' + counterSign)
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

				number = input_number.length == 1
					? input_number.val()
					: number_toInput($('#' + id + ' .td_number').text().trim()),
				anticipe = input_anticipe.length == 1
					? input_anticipe.val()
					: number_toInput($('#' + id + ' .td_anticipe').text().trim()),
				date = tr_formMod
					? input_date.val() < 10
						? '0' + input_date.val()
						: input_date.val()
					: $('#' + id + ' .td_date').text().substring(0, 2).trim(),
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
			if (_input_datas[id + '_date'] != date){
				tr_edit = true
				input_date.addClass('input_edit_val')
				td_date_day.addClass('input_edit_val')
			} else {
				input_date.removeClass('input_edit_val')
				td_date_day.removeClass('input_edit_val')
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
				daysInMonth = $('#operation_tab tbody').data('daysinmonth'),

				td_number = tr.find('.td_number'),
				td_anticipe = tr.find('.td_anticipe'),
				td_date = tr.find('.td_date'),
				td_comment = tr.find('.td_comment'),

				number = number_toInput(td_number.text().trim()),
				anticipe = number_toInput(td_anticipe.text().trim()),
				date = td_date.text().split('/'),
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

				let loopFirst = i == date[0]
					? 'selected'
					: ''

				input_date = input_date + "<option value='" + i + "' " + loopFirst + ">" + i + "</option>"
			}
			input_date = input_date + "</select></span>/"+ date[1] + "/" + date[2]
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
				inputNumberVal = inputNumber.val() == null ? '' : number_format(inputNumber.val(), 2, ',', ' '),
				inputAnticipeVal = inputAnticipe.val() == null ? '' : number_format(inputAnticipe.val(), 2, ',', ' '),
				inputDay = tr.find('.inputDay'),
				inputComment = tr.find('.inputComment'),
				day = inputDay.val()
			;

			// Switch + Action
			tr.find('.switch, .btn-group').prop('disabled', true).hide()

			inputNumber.after(inputNumberVal).remove()
			inputAnticipe.after(inputAnticipeVal).remove()
			inputDay.after(day < 10 ? '0'+ day : day).remove()
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
	}

	// Delete 1 add
	function deleteAddOpe(tr){
		tr.remove()
		calculSolde()
		checkFormEditDel()
	}

	// Reset 1 operation
	function resetEdit(id){
	
		let 
			date = _input_datas[id + '_date'],
			number = _input_datas[id + '_number'],
			anticipe = _input_datas[id + '_anticipe'],
			input_number = "<input class='inputNumber' type='number' step='0.01' value='" + number + "' min='0' />",
			input_anticipe = "<input class='inputAnticipe' type='number' step='0.01' value='" + anticipe + "' min='0' />"
		;

		resetForm(id, number, anticipe, date, input_number, input_anticipe)
		checkFormEditDel()
	}

	// Reset 1 operation
	function resetForm(id, number, anticipe, date, input_number, input_anticipe){

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
		date = date < 10 ? date.substring(1) : date
		$('#' + id + ' .inputDay option[value="'+ date +'"]').prop('selected', true)

		// Comment
		$('#' + id + ' .inputComment').val(_input_datas[id + '_comment'])
	}

	// Reset all Edit operations
	function resetAllEdit(){

		$('#butFullCancelEdit').hide().prop('disabled', true)

		$('#operation_tab .tr_edit').each(function(index, div){

			let 
				id = div.id,
				date = _input_datas[id + '_date'],
				number = _input_datas[id + '_number'],
				anticipe = _input_datas[id + '_anticipe'],
				input_number = "<input class='inputNumber' type='number' step='0.01' value='" + number + "' min='0' />",
				input_anticipe = "<input class='inputAnticipe' type='number' step='0.01' value='" + anticipe + "' min='0' />",
				tr_formMod = $('#' + id + ' .td_date').find('.inputDay').length == 1 ? true : false
			;

			// FormMod
			if (tr_formMod){
				resetForm(id, number, anticipe, date, input_number, input_anticipe)

			// No FormMod
			} else {

				// Number + Anticipe
				$('#' + id + ' .td_number, #' + id + ' .td_anticipe').empty()
				number != ''
					? $('#' + id + ' .td_number').append(number_format(number, 2, ',', ' '))
					: $('#' + id + ' .td_anticipe').append(number_format(anticipe, 2, ',', ' '))

				// Date
				$('#' + id + ' .td_date .day').text(date)

				// Comment
				$('#' + id + ' .td_comment').text(_input_datas[id + '_comment'])
			}
		})

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
			$('.modal-footer').show()
			$('#modalOperationSaveClose').prop('disabled', false).show()
			$('#modalOperationClose').prop('title', 'Fermer la fenêtre sans enregistrer').text('Fermer sans enregistrer')

		// OFF
		} else {
			$('.tr_add').remove()
			$('#modalOperationSaveClose').prop('disabled', true).hide()
			$('#saveAdd, .deleteAdd').prop('disabled', true).hide()
			$('#close').prop('disabled', false).prop('title', 'Fermer la fenêtre')
			$('.modal-footer').hide()
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
		})

		// Button Statut
		checkChange
			? $('#butFullToggleFormMod').val(1).html("<i class='fas fa-times' title='Retirer les formulaires des lignes'></i>")
			: $('#butFullToggleFormMod').val(0).html("<i class='fas fa-edit'></i>")

		return control
	}

	function sauvegarde(){

		let
			datas = [],
			sc_id = $('#operation_tab tbody').data('scid'),
			month = $('#operation_tab tbody').data('month'),
			year = $('#operation_tab tbody').data('year'),
			sign = $('#operation_tab tbody').data('sign')
		;

		$("#operation_tab tbody tr").not('#solde_tr_collabo, #tr_solde').each(function(index, value){

			let
				id_array = value.id.split('_'),
				id = id_array[2] ? id_array[2] : null,
				number = $(this).find('.inputNumber').val() == undefined
					? number_toInput($(this).find('.td_number').text().trim())
					: number_toInput($(this).find('.inputNumber').val()),
				anticipe = $(this).find('.inputAnticipe').val() == undefined
					? number_toInput($(this).find('.td_anticipe').text().trim())
					: number_toInput($(this).find('.inputAnticipe').val()),
				day = $(this).find('.inputDay').val() == undefined
					? $(this).find('.td_date').text().substring(0, 2)
					: $(this).find('.inputDay').val(),
				comment = $(this).find('.inputComment').val() == undefined
					? $(this).find('.td_comment').text()
					: $(this).find('.inputComment').val()
			;

			if (number != null || anticipe != null){
				datas.push({
					id: id,
					number: number,
					anticipe: anticipe,
					day: day,
					month: month,
					year: year,
					comment: comment,
				})
			}
		})

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_operation_save', { sc: sc_id, year: year, month: month, sign: sign }),
			data: { datas: datas },
			dataType: 'JSON',
			timeout: 15000,
			beforeSend: function(){
			},
			success: function(response){
				updateTable()
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
			}
		})
	}
})
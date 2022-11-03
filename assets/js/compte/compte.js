// JS
import { ucFirst } from '../service/service.js';

// CSS
import '../../styles/compte/compte.css';
import '../../styles/compte/modalOperations.css';

$(document).ready(function(){

	////////////
	// ON LOAD
	////////////

	var
		modalOpeAdd = '',
		save_operations = []
	;

	////////////
	// ON EVENTS
	////////////

	// Table edit
	$(".anticipe").hover(
		function(){ $(this).prev().addClass('jauni')},
		function(){	$(this).prev().removeClass('jauni')	}
	);

	// Open Modal Operations
	$("body").not('.counterEdit').on("click", ".edit td:not(.counterEdit)", function(e){

		let
			sc_id = $(this).data('scid'),
			type = $(this).data('type'),
			month = $(this).data('month'),
			months = $('#datas').data('months'),
			year = $('#datas').data('year')
		;

		modalGetOperations(sc_id, type, months, month, year)
	})

	// Modal Ope editMod
	$("body").on("click", "#modalOpeEdit", function(e){
		$(this).val() == 0
			? editMod(true, false)
			: editMod(false, false)
	})

	// Modal ope addMod + Add 1 input
	$("body").on("click", "#modalOpeAdd", function(e){
		addMod(true)
		$('#modalOpeListeTbody').append(modalOpeAdd)
		$('#modalOpeSolde_tr_collabo').insertAfter('#modalOpeListeTbody tr:last')
		$('#modalOpeSolde_tr').insertAfter('#modalOpeListeTbody tr:last')
	})

	// Modal ope input max 2 decimal
	$(document).on('keydown', 'input[pattern]', function(e){
		let input = $(this);
		let oldVal = input.val();
		let regex = new RegExp(input.attr('pattern'), 'g');

		setTimeout(function(){
			let newVal = input.val();
			if(!regex.test(newVal)){
				input.val(oldVal); 
			}
		}, 1);
	})

	// Switch
	$("body").on("click", ".switch", function(e){
		toSwitch($(this).parent('td').parent('tr'))
	})

	// Save edit
	$("body").on("click", "#modalOpeSaveEdit", function(e){
		editMod(false, true)
	})

	// Save add
	$("body").on("click", "#modalOpeSaveAdd", function(e){
		addMod(false, true)
	})

	// Retrait
	$("body").on("click", ".modalOpeInputRetrait", function(e){

		$(this).parent().parent('.modalOpeListeAdd').remove()

		$('body').find('.modalOpeListeAdd').length == 0
			? addMod(false)
			: calculSolde()
	})

	// Retrait all
	$("body").on("click", "#modalOpeDeleteAllAdd", function(e){
		$('.modalOpeListeAdd').remove()
		addMod(false)
	})

	// Delete
	$("body").on("click", ".modalOpeDelete", function(e){
		let ope_id = $(this).data('opeid')
		$('#ope_id_' + ope_id).remove()
		calculSolde()
	})

	// Control + calcul
	$("body").on("input", ".modalOpeInputNumber, .modalOpeInputAnticipe", function(e){
		controlGestion()
		calculSolde()
	})
	$("body").on("click", ".modalOpeInputRetrait, #modalOpeAdd, #modalOpeEdit, .modalOpeDelete ", function(e){
		controlGestion()
		calculSolde()
	})


	////////////
	// FONCTIONS
	////////////

	function modalGetOperations(sc_id, type, months, month, year){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_gestion', { sc: sc_id, year: year, month: month, type: type }),
			timeout: 15000,
			beforeSend: function(){
				modalOpMeta1(year, months[month], type)
				modalOpSpinner(true)
			},
			success: function(response){
				modalOpMeta2(response.category_libelle, response.subcategory_libelle)
				modalOpShow(response.operations, month, year, response.days_in_month, sc_id, type)
				modalOpSpinner(false)
				getModalOpAdd(month, year, response.days_in_month, type)

			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
				modalOpSpinner(false)
			}
		})
	}
	function getModalOpAdd(month, year, daysInMonth, type){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_operation_add', { month: month, year: year, daysInMonth: daysInMonth, type: type }),
			timeout: 15000,
			success: function(response){
				modalOpeAdd = response.render
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
			}
		})
	}

	function modalOpSpinner(etat){
		if (etat){
			$('.spinner').show()
			$('#modal_reel_contenu').hide()
			$('#modal_avenir_contenu').hide()
			$('#myTab').hide()

		} else {
			$('.spinner').hide()
			$('#modal_reel_contenu').show()
			$('#modal_avenir_contenu').show()
			$('#myTab').show()
		}
	}

	function modalOpMeta1(year, month, type){

		// HEAD DATE
		$('#modal_date_annee').text(year)
		$('#modal_date_mois').text(ucFirst(month))

		// HEAD LIBELLE + COLOR
		let classType = 'total_month_full_' + type
		$('#modal_category').text('.............')
		$('#modal_subcategory').text('.............')
		$('#modal_category, #modal_subcategory, #modalOpeSolde').removeClass("total_month_full_pos").removeClass("total_month_full_neg").addClass(classType)

		// BODY
		$('#modalOpeListe tbody tr').not('#modalOpeSolde_tr, #modalOpeSolde_tr_collabo').remove()
		$('#modalOpeSolde_tr').hide()
		$('#modalOpeSolde').text('0').hide()

		// FOOTER BUTTON
		$('#modalOpeSaveEdit, #modalOpeSaveAdd').prop('disabled', true).hide()
	}
	function modalOpMeta2(cat_libelle, subcat_libelle){

		// HEAD LIBELLE
		$('#modal_category').text(ucFirst(cat_libelle))
		$('#modal_subcategory').text(ucFirst(subcat_libelle))
		$('#modalOpeSolde, #modalOpeSolde_tr').show()
	}

	function modalOpShow(operations, month, year, daysInMonth, sc_id, type){

		save_operations = operations

		// UPDATE TBODY
		$('#modalOpeListeTbody')
			.data('year', year)
			.data('month', month)
			.data('daysinmonth', daysInMonth)
			.data('scid', sc_id)
			.data('type', type)
		;
		$('#modalOpeListeTbody tr')
			.not('#modalOpeSolde_tr, #modalOpeSolde_tr_collabo')
			.remove()
		;

		let
			tr = "",
			day = ''
		;

		operations.forEach(function(item, index){

			day = item.day < 10 ? '0' + item.day : item.day
			month = item.month < 10 ? '0' + item.month : item.month

			tr =
				"<tr id='ope_id_" + item.id + "'>" +
					"<td class='td_number p-1'>" + (item.anticipe ? '' : item.number) + "</td>" +
					"<td class='td_switch'>" +
						"<span class='switch hide'><i class='fa-solid fa-repeat'></i></span>" +
					"</td>" +
					"<td class='td_anticipe p-1'>" +
						(item.anticipe ? item.number : '') +
					"</td>" +
					"<td class='td_date p-1'>" + day + '/' + month + '/' + item.year + "</td>" +
					"<td class='td_comment p-1'>" + ucFirst(item.comment) + "</td>" +
					"<td class='modal_ope_th_actions'>" +
						"<button class='modalOpeDelete hide m-1 disabled' data-opeid='" + item.id + "'>Supprimer</button> " +
					"</td>" +
				"</tr>"

			$('#modalOpeListeTbody').append(tr)

			tr = ''
		})

		$('#modalOpeSolde_tr_collabo').insertAfter('#modalOpeListeTbody tr:last')
		$('#modalOpeSolde_tr').insertAfter('#modalOpeListeTbody tr:last')
		calculSolde()
	}

	function addMod(etat, save){

		// addMod ON
		if (etat){
			$('#modalOpeSaveAdd, .modalOpeRetrait, #modalOpeDeleteAllAdd').prop('disabled', false).show()
			$('#modalOpeEdit, #modalOpeSaveEdit').prop('disabled', true).hide()
			$('#modalOpeClose, #modalOpeEdit').prop('disabled', true).prop('title', 'Veuillez valider les changements avant de fermer la fenêtre.')

		// addMod OFF
		} else {

			// Save
			if (save){

				if (controlGestion()){

					addModOff()
					sauvegarde()

					$('.switch').hide()
					$(".modalOpeListeAdd").each(function(index, value){
						let
							inputNumber = $(this).find('.modalOpeInputNumber'),
							inputAnticipe = $(this).find('.modalOpeInputAnticipe'),
							inputNumberVal = inputNumber.val() == 0 ? '' : inputNumber.val(),
							inputAnticipeVal = inputAnticipe.val() == 0 ? '' : inputAnticipe.val(),
							inputDay = $(this).find('.modalOpeInputDay'),
							inputComment = $(this).find('.modalOpeInputComment'),
							day = inputDay.val()
						;

						inputNumber.after(inputNumberVal).remove()
						inputAnticipe.after(inputAnticipeVal).remove()
						inputDay.after(day < 10 ? '0'+ day : day).remove()
						inputComment.after(inputComment.val()).remove()
					})
				}

			// Cancel
			} else {

				addModOff()

				modalOpShow(
					save_operations,
					$('#modalOpeListeTbody').data('month'),
					$('#modalOpeListeTbody').data('year'),
					$('#modalOpeListeTbody').data('daysinmonth')
				)
			}
			calculSolde()
		}
	}
	function addModOff(){
		$('#modalOpeSaveAdd, .modalOpeDelete, #modalOpeDeleteAllAdd, .modalOpeInputRetrait').prop('disabled', true).hide()
		$('#modalOpeEdit').prop('disabled', false).show()
		$('#modalOpeClose, #modalOpeEdit').prop('disabled', false).prop('title', 'Fermer la fenêtre')
	}

	function editMod(etat, save){

		let
			daysInMonth = $('#modalOpeListeTbody').data('daysinmonth'),
			month = $('#modalOpeListeTbody').data('month'),
			year = $('#modalOpeListeTbody').data('year'),
			type = $('#modalOpeListeTbody').data('type'),
			limit = type == 'neg' ? 'max' : 'min'
		;

		// editMod ON
		if (etat){
			addMod(false)
			$('#modalOpeEdit').text("Annuler les modifications").addClass('btn btn-danger').val(1)
			$('#modalOpeAdd').prop('disabled', true).hide()
			$('#modalOpeSaveEdit, .modalOpeDelete, .switch').prop('disabled', false).show()
			$('#modalOpeClose').prop('disabled', true).prop('title', 'Veuillez valider/annuler les changements avant de fermer la fenêtre.')

			$(".td_number").each(function(index, value){
				let number = $(this).text()
				if (number != ''){
					$(this).empty()
					let input = "<input class='modalOpeInputNumber' type='number' step='0.01' pattern='^\d*(\.\d{0,2})?$' value='" + number + "' "+limit+"='0' />"
					$(this).append(input)
				}
			})
			$(".td_anticipe").each(function(index, value){
				let number = $(this).text()
				if (number != ''){
					$(this).empty()
					let input = "<input class='modalOpeInputAnticipe' type='number' step='0.01' pattern='^\d*(\.\d{0,2})?$' value='" + number + "' "+limit+"='0' />"
					$(this).append(input)
				}
			})
			$(".td_date").each(function(index, value){
				let date = $(this).text().split('/')
				$(this).empty()
				let input = "<select class='modalOpeInputDay'/>"

					for (var i = 1; i <= daysInMonth; i++) {

						let loopFirst = i == date[0]
							? 'selected'
							: ''

						input = input + "<option value='" + i + "' " + loopFirst + ">" + i + "</option>"
					}

				input = input + "</select>/"+ date[1] + "/" + date[2]

				$(this).append(input)
			})
			$(".td_comment").each(function(index, value){
				let text = $(this).text()
				$(this).empty()
				let input = "<input class='modalOpeInputComment' type='text' value='" + text + "' />"
				$(this).append(input)
			})

		// editMod OFF
		} else {

			// Save
			if (save){

				if (controlGestion()){

					sauvegarde()
					editModOff()

					$('.switch').hide()
					$(".modalOpeInputNumber").each(function(index, value){
						$(this).parent('.td_number').append($(this).val())
						$(this).remove()
					})
					$(".modalOpeInputAnticipe").each(function(index, value){
						$(this).parent('.td_anticipe').append($(this).val())
						$(this).remove()
					})
					$(".modalOpeInputDay").each(function(index, value){
						let day = $(this).val()
						$(this).after(day < 10 ? '0'+ day : day)
						$(this).remove()
					})
					$(".modalOpeInputComment").each(function(index, value){
						$(this).parent('.td_comment').append($(this).val())
						$(this).remove()
					})
				}

			// Cancel
			} else {

				editModOff()

				modalOpShow(
					save_operations,
					$('#modalOpeListeTbody').data('month'),
					$('#modalOpeListeTbody').data('year'),
					$('#modalOpeListeTbody').data('daysinmonth')
				)
			}
			calculSolde()
		}
	}
	function editModOff(){
		$('#modalOpeEdit').text("Modifier").removeClass('btn btn-danger').val(0)
		$('#modalOpeAdd').prop('disabled', false).show()
		$('#modalOpeSaveEdit, .modalOpeDelete').prop('disabled', true).hide()
		$('#modalOpeClose').prop('disabled', false).prop('title', 'Fermer la fenêtre')
	}

	function toSwitch(tr){
		let
			input = tr.find('input'),
			val = input.val(),
			clas = input.attr('class'),
			type = $('#modalOpeListeTbody').data('type'),
			limit = type == 'neg' ? 'max' : 'min'
		;

		// Add anticipe
		if (clas == 'modalOpeInputNumber'){
			tr.find('.td_anticipe').append("<input class='modalOpeInputAnticipe' type='number' step='0.01' pattern='^\d*(\.\d{0,2})?$' value='"+val+"' "+limit+"='0' />")
			tr.find('.td_number').empty()

		// Add number
		} else if(clas == 'modalOpeInputAnticipe'){
			tr.find('.td_number').append("<input class='modalOpeInputNumber' type='number' step='0.01' pattern='^\d*(\.\d{0,2})?$' value='"+val+"' "+limit+"='0' />")
			tr.find('.td_anticipe').empty()
		}
		controlGestion()
		calculSolde()
	}

	function controlGestion(){

		let	control = true;

		$("#modalOpeListeTbody tr").not('#modalOpeSolde_tr_collabo, #modalOpeSolde_tr').each(function(index, value){

			let
				switch_icon = $(this).find('.switch'),
				input_number = $(this).find('.modalOpeInputNumber'),
				input_anticipe = $(this).find('.modalOpeInputAnticipe'),
				input_number_val = input_number.val(),
				input_anticipe_val = input_anticipe.val()
			;

			// Input existant
			if (input_number_val != undefined && input_anticipe_val != undefined){

				let input_number_valid = input_number_val == '' || input_number_val == '0' || input_number_val == 0
					? false
					: true

				let input_anticipe_valid = input_anticipe_val == '' || input_anticipe_val == '0' || input_anticipe_val == 0
					? false
					: true

				// 2 vides
				if (!input_number_valid && !input_anticipe_valid){
					input_number.addClass('alerte').removeClass('alerte-doublon')
					input_anticipe.addClass('alerte').removeClass('alerte-doublon')
					$("#modalOpeListeTbody .alerte:first").focus()
					$('#modalOpeSaveAdd, #modalOpeSaveEdit').text('Champs à remplir').removeClass('btn-success').addClass('btn-danger')
					control = false

				// 2 remplis (ne doit plus apparaitre)
				} else if(input_number_valid && input_anticipe_valid){
					input_number.addClass('alerte-doublon').removeClass('alerte')
					input_anticipe.addClass('alerte-doublon').removeClass('alerte')
					$("#modalOpeListeTbody .alerte-doublon:first").focus()
					$('#modalOpeSaveAdd, #modalOpeSaveEdit').text('Un seul montant autorisé par ligne').removeClass('btn-success').addClass('btn-danger')
					control = false

				// 1 rempli et 1 vide
				} else {
					switch_icon.show()
					input_number_valid
						? input_anticipe.remove() && input_number.removeClass('alerte').removeClass('alerte-doublon')
						: input_number.remove() && input_anticipe.removeClass('alerte').removeClass('alerte-doublon')
				}

			// Only Number valid
			} else if(input_number_val != undefined && input_anticipe_val == undefined){

				let input_number_valid = input_number_val == '' || input_number_val == '0' || input_number_val == 0
					? false
					: true

				if (input_number_valid){
					input_number.removeClass('alerte').removeClass('alerte-doublon')
					switch_icon.show()
				} else {
					input_number.addClass('alerte').removeClass('alerte-doublon')
					switch_icon.hide()
					$("#modalOpeListeTbody .alerte:first").focus()
					$('#modalOpeSaveAdd, #modalOpeSaveEdit').text('Champs à remplir').removeClass('btn-success').addClass('btn-danger')
					control = false
				}

			// Only Anticipe valid
			} else if(input_number_val == undefined && input_anticipe_val != undefined){

				let input_anticipe_valid = input_anticipe_val == '' || input_anticipe_val == '0' || input_anticipe_val == 0
					? false
					: true

				if (input_anticipe_valid){
					input_anticipe.removeClass('alerte').removeClass('alerte-doublon')
					switch_icon.show()
				} else {
					input_anticipe.addClass('alerte').removeClass('alerte-doublon')
					switch_icon.hide()
					$("#modalOpeListeTbody .alerte:first").focus()
					$('#modalOpeSaveAdd, #modalOpeSaveEdit').text('Champs à remplir').removeClass('btn-success').addClass('btn-danger')
					control = false
				}
			}
		})

		if(control){
			$('#modalOpeSaveAdd, #modalOpeSaveEdit').text('Enregistrer').removeClass('btn-danger').addClass('btn-success')
		}

		return control
	}
	function calculSolde(){

		let
			type = $('#modalOpeListeTbody').data('type'),
			counterType = type == 'pos' ? 'neg' : 'pos',
			solde_fait = 0,
			solde_anticipe = 0
		;

		// Sous-total
		$(".td_number").each(function(index, value){
			if ($(this).is(":visible")){

				solde_fait = $(this).find('.modalOpeInputNumber').val() != undefined
					? solde_fait + parseFloat($(this).find('.modalOpeInputNumber').val())
					: $(this).text().trim() != ''
						? solde_fait + parseFloat($(this).text())
						: solde_fait
			}
		})

		$(".td_anticipe").each(function(index, value){
			if ($(this).is(":visible")){

				solde_anticipe = $(this).find('.modalOpeInputAnticipe').val() != undefined
					? solde_anticipe + parseFloat($(this).find('.modalOpeInputAnticipe').val())
					: $(this).text().trim() != ''
						? solde_anticipe + parseFloat($(this).text())
						: solde_anticipe
			}
		})

		// Math
		solde_fait = Math.round((solde_fait)*100)/100
		solde_anticipe = Math.round((solde_anticipe)*100)/100

		solde_fait = (solde_fait % 1 != 0) == true
			? solde_fait.toFixed(2)
			: solde_fait

		solde_anticipe = (solde_anticipe % 1 != 0) == true
			? solde_anticipe.toFixed(2)
			: solde_anticipe

		$('#modalOpeSoldeReel').text(solde_fait)
		$('#modalOpeSoldeAnticipe').text(solde_anticipe)
		$('#modalOpeSolde').text(Math.round((parseFloat(solde_fait) + parseFloat(solde_anticipe))*100)/100)

		// Color Reel
		$('#modalOpeSoldeReel').addClass('total_month_detail_'+ type).removeClass('total_month_detail_' + counterType)
	}

	function sauvegarde(){

		let
			datas = [],
			sc_id = $('#modalOpeListeTbody').data('scid'),
			month = $('#modalOpeListeTbody').data('month'),
			year = $('#modalOpeListeTbody').data('year'),
			type = $('#modalOpeListeTbody').data('type')
		;

		$("#modalOpeListeTbody tr").not('#modalOpeSolde_tr_collabo, #modalOpeSolde_tr').each(function(index, value){

			let
				id_array = value.id.split('_'),
				id = id_array[2] ? id_array[2] : null,
				number = $(this).find('.modalOpeInputNumber').val() == undefined ? null : $(this).find('.modalOpeInputNumber').val(),
				number_anticipe = $(this).find('.modalOpeInputAnticipe').val() == undefined ? null : $(this).find('.modalOpeInputAnticipe').val(),
				day = $(this).find('.modalOpeInputDay').val() == undefined ? null : $(this).find('.modalOpeInputDay').val(),
				comment = $(this).find('.modalOpeInputComment').val() == undefined ? null : $(this).find('.modalOpeInputComment').val()
			;

			datas.push({
				id: id,
				number: number,
				number_anticipe: number_anticipe,
				day: day,
				month: month,
				year: year,
				comment: comment,
			})
		})

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_gestion_save', { sc: sc_id, year: year, month: month, type: type }),
			data: { datas: datas },
			dataType: 'JSON',
			timeout: 15000,
			beforeSend: function(){

			},
			success: function(response){
				if (response.save == true){
					save_operations = response.operations
				}
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
			}
		})
	}

	function calculTable(){
		//TODO
	}
})
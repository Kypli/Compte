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

	// Open Modal Operations
	$("body").not('.counterEdit').on("click", ".edit td:not(.counterEdit)", function(e){

		let
			sc_id = $(this).data('scid'),
			type = $(this).data('type'),
			anticipe = $(this).data('anticipe'),
			month = $(this).data('month'),
			months = $('#datas').data('months'),
			year = $('#datas').data('year')
		;

		modalGetOperations(sc_id, type, anticipe, months, month, year)
	})

	// Modal Ope editMod
	$("body").on("click", "#modalOpeEdit", function(e){
		$(this).val() == 0
			? editMod(true, false)
			: editMod(false, false)
	})

	// Modal ope Add + addMod
	$("body").on("click", "#modalOpeAdd", function(e){
		addMod(true)
		$('#modalOpeListeTbody').append(modalOpeAdd)
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
	});

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
	$("body").on("input", ".modalOpeInputNumber", function(e){
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

	function modalGetOperations(sc_id, type, anticipe, months, month, year){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_gestion', { sc: sc_id, year: year, month: month, type: type, anticipe: anticipe }),
			timeout: 15000,
			beforeSend: function(){
				modalOpMeta1(year, months[month], type)
				modalOpSpinner(true)
			},
			success: function(response){
				modalOpMeta2(response.category_libelle, response.subcategory_libelle)
				modalOpShow(response.operations, month, year, response.days_in_month, sc_id, type, anticipe)
				modalOpSpinner(false)
				getModalOpAdd(month, year, response.days_in_month, anticipe)

			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
				modalOpSpinner(false)
			}
		})
	}
	function getModalOpAdd(month, year, daysInMonth, anticipe){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_operation_add', { month: month, year: year, daysInMonth: daysInMonth, anticipe: anticipe }),
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

	function modalOpShow(operations, month, year, daysInMonth, sc_id, type, anticipe){

		save_operations = operations

		// UPDATE TBODY
		$('#modalOpeListeTbody')
			.data('year', year)
			.data('month', month)
			.data('daysinmonth', daysInMonth)
			.data('scid', sc_id)
			.data('type', type)
			.data('anticipe', anticipe)
			.empty()
		;

		let
			tr = "",
			day = '',
			montant = 0
		;

		operations.forEach(function(item, index){

			day = item.day < 10 ? '0' + item.day : item.day
			month = item.month < 10 ? '0' + item.month : item.month
			montant = montant + item.number

			tr = 
				"<tr id='ope_id_" + item.id + "'>" +
					"<td class='number'>" + item.number + "</td>" +
					"<td class='date'>" + day + '/' + month + '/' + item.year + "</td>" +
					"<td class='comment'>" + ucFirst(item.comment) + "</td>" +
					"<td class='modal_ope_th_actions'>" +
						"<button class='modalOpeDelete hide m-1 disabled' data-opeid='" + item.id + "'>Supprimer</button> " +
					"</td>" +
				"</tr>"

			$('#modalOpeListeTbody').append(tr)

			tr = ''
		})

		$('#modalOpeSolde').text(Math.round(montant*100)/100)
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

		// FOOTER BUTTON
		$('#modalOpeSaveEdit, #modalOpeSaveAdd').prop('disabled', true).hide()
	}
	function modalOpMeta2(cat_libelle, subcat_libelle){

		// HEAD LIBELLE
		$('#modal_category').text(ucFirst(cat_libelle))
		$('#modal_subcategory').text(ucFirst(subcat_libelle))
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

					$(".modalOpeListeAdd").each(function(index, value){
						let
							inputNumber = $(this).find('.modalOpeInputNumber'),
							inputDay = $(this).find('.modalOpeInputDay'),
							inputComment = $(this).find('.modalOpeInputComment'),
							day = inputDay.val()
						;

						inputNumber.after(inputNumber.val()).remove()
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
			year = $('#modalOpeListeTbody').data('year')
		;

		// editMod ON
		if (etat){
			addMod(false)
			$('#modalOpeEdit').text("Annuler les modifications").addClass('btn btn-danger').val(1)
			$('#modalOpeAdd').prop('disabled', true).hide()
			$('#modalOpeSaveEdit, .modalOpeDelete').prop('disabled', false).show()
			$('#modalOpeClose').prop('disabled', true).prop('title', 'Veuillez valider/annuler les changements avant de fermer la fenêtre.')

			$(".number").each(function(index, value){
				let number = $(this).text()
				$(this).empty()
				let input = "<input class='modalOpeInputNumber' type='number' step='0.01' pattern='^\d*(\.\d{0,2})?$' value='" + number + "' />"
				$(this).append(input)
			})
			$(".date").each(function(index, value){
				let date = $(this).text().split('/')
				$(this).empty()
				let input = "<select class='modalOpeInputDay'/>"

					for (var i = 1; i <= daysInMonth; i++) {

						let loopFirst = i == date[0]
							? 'selected'
							: ''

						input = input + "<option value='" + i + "' " + loopFirst + ">" + i + "</option>"
					}

				input = input + "</select> / "+ date[1] + " / " + date[2]

				$(this).append(input)
			})
			$(".comment").each(function(index, value){
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

					editModOff()
					sauvegarde()

					$(".modalOpeInputNumber").each(function(index, value){
						$(this).parent('.number').append($(this).val())
						$(this).remove()
					})
					$(".modalOpeInputDay").each(function(index, value){
						let day = $(this).val()
						$(this).after(day < 10 ? '0'+ day : day)
						$(this).remove()
					})
					$(".modalOpeInputComment").each(function(index, value){
						$(this).parent('.comment').append($(this).val())
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

	function controlGestion(){

		let	control = true;

		$("#modalOpeListeTbody tr").each(function(index, value){

			let
				input = $(this).find('.modalOpeInputNumber'),
				val = input.val()
			;

			if (val == '' || val == '0' || val == 0){
				input.addClass('alerte')
				$("#modalOpeListeTbody .alerte:first").focus()
				$('#modalOpeSaveAdd, #modalOpeSaveEdit').text('Champs à remplir').removeClass('btn-success').addClass('btn-danger')
				control = false
			} else {
				input.removeClass('alerte')
			}
		})

		if(control){
			$('#modalOpeSaveAdd, #modalOpeSaveEdit').text('Enregistrer').removeClass('btn-danger').addClass('btn-success')
		}

		return control
	}
	function sauvegarde(){

		let
			datas = [],
			sc_id = $('#modalOpeListeTbody').data('scid'),
			month = $('#modalOpeListeTbody').data('month'),
			year = $('#modalOpeListeTbody').data('year'),
			type = $('#modalOpeListeTbody').data('type'),
			anticipe = $('#modalOpeListeTbody').data('anticipe')
		;

		$("#modalOpeListeTbody tr").each(function(index, value){

			let 
				id_array = value.id.split('_'),
				id = id_array[2] ? id_array[2] : null,
				number = $(this).find('.modalOpeInputNumber').val() == undefined ? null : $(this).find('.modalOpeInputNumber').val(),
				day = $(this).find('.modalOpeInputDay').val() == undefined ? null : $(this).find('.modalOpeInputDay').val(),
				comment = $(this).find('.modalOpeInputComment').val() == undefined ? null : $(this).find('.modalOpeInputComment').val()
			;

			datas.push({ 
				id: id,
				number: number,
				day: day,
				month: month,
				year: year,
				comment: comment,
			})
		})

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_gestion_save', { sc: sc_id, year: year, month: month, type: type, anticipe: anticipe }),
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

	function calculSolde(){
		let solde = 0
		$(".number").each(function(index, value){
			if ($(this).is(":visible")){

				solde = $(this).find('.modalOpeInputNumber').val() != undefined
					? solde + parseFloat($(this).find('.modalOpeInputNumber').val())
					: solde + parseFloat($(this).text())
			}
		})
		$('#modalOpeSolde').text(Math.round(solde*100)/100)
	}

	function calculTable(){
		
	}
})
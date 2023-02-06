// JS IMPORT
import { ucFirst } from '../service/service.js';

// CSS
import '../../styles/compte/modalGestion.css';

$(document).ready(function(){

	////////////
	// ON LOAD
	////////////

	var
		add = '',
		save_operations = []
	;

	////////////
	// ON EVENTS
	////////////

	// Get Operations
	$("body").not('.counterEdit, .td_category_libelle, .td_subcategory_libelle').on("click", ".edit td:not(.counterEdit, .td_category_libelle, .td_subcategory_libelle)", function(e){

		let
			sc_id = $(this).data('scid'),
			sign = $(this).data('sign'),
			month = $(this).data('month'),
			months = $('#datas').data('months'),
			year = $('#datas').data('year')
		;

		getOperations(sc_id, sign, months, month, year)
	})

	// EditMod
	$("body").on("click", "#edit", function(e){
		$(this).val() == 0
			? editMod(true, false)
			: editMod(false, false)
	})

	// AddMod + Add 1 input
	$("body").on("click", "#add", function(e){
		addMod(true)
		$('#gestion_tab tbody').append(add)
		$('#solde_tr_collabo').insertAfter('#gestion_tab tbody tr:last')
		$('#solde_tr').insertAfter('#gestion_tab tbody tr:last')
	})

	// Switch
	$("body").on("click", ".switch", function(e){
		toSwitch($(this).parent('td').parent('tr'))
	})

	// Save edit
	$("body").on("click", "#saveEdit", function(e){
		editMod(false, true)
	})

	// Save add
	$("body").on("click", "#saveAdd", function(e){
		addMod(false, true)
	})

	// Retrait
	$("body").on("click", ".inputRetrait", function(e){

		$(this).parent().parent('.listeAdd').remove()

		$('body').find('.listeAdd').length == 0
			? addMod(false)
			: calculSolde()
	})

	// Retrait all
	$("body").on("click", "#deleteAllAdd", function(e){
		$('.listeAdd').remove()
		addMod(false)
	})

	// Delete
	$("body").on("click", ".delete", function(e){
		let ope_id = $(this).data('opeid')
		$('#ope_id_' + ope_id).remove()
		calculSolde()
	})

	// Control + calcul
	$("body").on("input", ".inputNumber, .inputAnticipe", function(e){
		controlGestion()
		calculSolde()
	})
	$("body").on("click", ".inputRetrait, #add, #edit, .delete ", function(e){
		controlGestion()
		calculSolde()
	})


	////////////
	// FONCTIONS
	////////////

	function getOperations(sc_id, sign, months, month, year){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_gestion', { sc: sc_id, year: year, month: month, sign: sign }),
			timeout: 15000,
			beforeSend: function(){
				meta1(year, months[month], sign)
				spinner(true)
			},
			success: function(response){
				meta2(response.category_libelle, response.subcategory_libelle)
				show(response.operations, month, year, response.days_in_month, sc_id, sign)
				spinner(false)
				getInputAdd(month, year, response.days_in_month, sign)

			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
				spinner(false)
			}
		})
	}
	function getInputAdd(month, year, daysInMonth, sign){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_gestion_add', { month: month, year: year, daysInMonth: daysInMonth, sign: sign }),
			timeout: 15000,
			success: function(response){
				add = response.render
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
			}
		})
	}

	function spinner(etat){
		if (etat){
			$('.spinner').show()

		} else {
			$('.spinner').hide()
		}
	}

	function meta1(year, month, sign){

		// HEAD DATE
		$('#date_annee').text(year)
		$('#date_mois').text(ucFirst(month))

		// HEAD LIBELLE + COLOR
		let classSign = 'total_month_full_' + sign
		$('#category').text('.............')
		$('#subcategory').text('.............')
		$('#category, #subcategory, #solde').removeClass("total_month_full_pos").removeClass("total_month_full_neg").addClass(classSign)

		// BODY
		$('#liste tbody tr').not('#solde_tr, #solde_tr_collabo').remove()
		$('#solde_tr').hide()
		$('#solde').text('0').hide()

		// FOOTER BUTTON
		$('#saveEdit, #saveAdd').prop('disabled', true).hide()
	}
	function meta2(cat_libelle, subcat_libelle){

		// HEAD LIBELLE
		$('#category').text(ucFirst(cat_libelle))
		$('#subcategory').text(ucFirst(subcat_libelle))
		$('#solde, #solde_tr').show()
	}

	function show(operations, month, year, daysInMonth, sc_id, sign){

		save_operations = operations

		// UPDATE TBODY
		$('#gestion_tab tbody')
			.data('year', year)
			.data('month', month)
			.data('daysinmonth', daysInMonth)
			.data('scid', sc_id)
			.data('sign', sign)
		;
		$('#gestion_tab tbody tr')
			.not('#solde_tr, #solde_tr_collabo')
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
					"<td class='th_actions'>" +
						"<button class='delete hide m-1 disabled' data-opeid='" + item.id + "'>Supprimer</button> " +
					"</td>" +
				"</tr>"

			$('#gestion_tab tbody').append(tr)

			tr = ''
		})

		$('#solde_tr_collabo').insertAfter('#gestion_tab tbody tr:last')
		$('#solde_tr').insertAfter('#gestion_tab tbody tr:last')
		calculSolde()
	}

	function addMod(etat, save){

		// addMod ON
		if (etat){
			$('.modal-footer').show()
			$('#saveAdd, .retrait, #deleteAllAdd').prop('disabled', false).show()
			$('#edit, #saveEdit').prop('disabled', true).hide()
			$('#close, #edit').prop('disabled', true).prop('title', 'Veuillez valider les changements avant de fermer la fenêtre.')

		// addMod OFF
		} else {

			// Save
			if (save){

				if (controlGestion()){

					addModOff()
					sauvegarde()

					$('.switch').hide()
					$(".listeAdd").each(function(index, value){

						let
							inputNumber = $(this).find('.inputNumber'),
							inputAnticipe = $(this).find('.inputAnticipe'),
							inputNumberVal = inputNumber.val() == 0 ? '' : correctNumber(inputNumber.val()),
							inputAnticipeVal = inputAnticipe.val() == 0 ? '' : correctNumber(inputAnticipe.val()),
							inputDay = $(this).find('.inputDay'),
							inputComment = $(this).find('.inputComment'),
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

				show(
					save_operations,
					$('#gestion_tab tbody').data('month'),
					$('#gestion_tab tbody').data('year'),
					$('#gestion_tab tbody').data('daysinmonth')
				)
			}
			calculSolde()
		}
	}
	function addModOff(){
		$('#saveAdd, .delete, #deleteAllAdd, .inputRetrait').prop('disabled', true).hide()
		$('#edit').prop('disabled', false).show()
		$('#close').prop('disabled', false).prop('title', 'Fermer la fenêtre')
	}

	function editMod(etat, save){

		let
			daysInMonth = $('#gestion_tab tbody').data('daysinmonth'),
			month = $('#gestion_tab tbody').data('month'),
			year = $('#gestion_tab tbody').data('year')
		;

		// editMod ON
		if (etat){
			addMod(false)
			$('.modal-footer').show()
			$('#edit').text("Annuler les modifications").addClass('btn btn-danger').val(1)
			$('#add').prop('disabled', true).hide()
			$('#saveEdit, .delete, .switch').prop('disabled', false).show()
			$('#close').prop('disabled', true).prop('title', 'Veuillez valider/annuler les changements avant de fermer la fenêtre.')

			$(".td_number").each(function(index, value){
				let number = $(this).text()
				if (number != ''){
					$(this).empty()
					let input = "<input class='inputNumber' type='number' step='0.01' value='" + number + "' min='0' />"
					$(this).append(input)
				}
			})
			$(".td_anticipe").each(function(index, value){
				let number = $(this).text()
				if (number != ''){
					$(this).empty()
					let input = "<input class='inputAnticipe' type='number' step='0.01' value='" + number + "' min='0' />"
					$(this).append(input)
				}
			})
			$(".td_date").each(function(index, value){
				let date = $(this).text().split('/')
				$(this).empty()
				let input = "<select class='inputDay'/>"

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
				let input = "<input class='inputComment' type='text' value='" + text + "' />"
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
					$(".inputNumber").each(function(index, value){
						$(this).parent('.td_number').append(correctNumber($(this).val()))
						$(this).remove()
					})
					$(".inputAnticipe").each(function(index, value){
						$(this).parent('.td_anticipe').append(correctNumber($(this).val()))
						$(this).remove()
					})
					$(".inputDay").each(function(index, value){
						let day = $(this).val()
						$(this).after(day < 10 ? '0'+ day : day)
						$(this).remove()
					})
					$(".inputComment").each(function(index, value){
						$(this).parent('.td_comment').append($(this).val())
						$(this).remove()
					})
				}

			// Cancel
			} else {

				editModOff()

				show(
					save_operations,
					$('#gestion_tab tbody').data('month'),
					$('#gestion_tab tbody').data('year'),
					$('#gestion_tab tbody').data('daysinmonth')
				)
			}
			calculSolde()
		}
	}
	function editModOff(){
		$('#edit').text("Modifier").removeClass('btn btn-danger').val(0)
		$('#add').prop('disabled', false).show()
		$('#saveEdit, .delete').prop('disabled', true).hide()
		$('#close').prop('disabled', false).prop('title', 'Fermer la fenêtre')
	}

	function toSwitch(tr){
		let
			input = tr.find('input'),
			val = input.val(),
			clas = input.attr('class')
		;

		// Add anticipe
		if (clas == 'inputNumber'){
			tr.find('.td_anticipe').append("<input class='inputAnticipe' type='number' step='0.01' value='"+val+"' min='0' />")
			tr.find('.td_number').empty()

		// Add number
		} else if(clas == 'inputAnticipe'){
			tr.find('.td_number').append("<input class='inputNumber' type='number' step='0.01' value='"+val+"' min='0' />")
			tr.find('.td_anticipe').empty()
		}
		controlGestion()
		calculSolde()
	}

	function controlGestion(){

		let	control = true;

		$("#gestion_tab tbody tr").not('#solde_tr_collabo, #solde_tr').each(function(index, value){

			let
				switch_icon = $(this).find('.switch'),
				input_number = $(this).find('.inputNumber'),
				input_anticipe = $(this).find('.inputAnticipe'),
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
					$("#gestion_tab tbody .alerte:first").focus()
					$('#saveAdd, #saveEdit').text('Champs à remplir').removeClass('btn-success').addClass('btn-danger')
					control = false

				// 2 remplis (ne doit plus apparaitre)
				} else if(input_number_valid && input_anticipe_valid){
					input_number.addClass('alerte-doublon').removeClass('alerte')
					input_anticipe.addClass('alerte-doublon').removeClass('alerte')
					$("#gestion_tab tbody .alerte-doublon:first").focus()
					$('#saveAdd, #saveEdit').text('Un seul montant autorisé par ligne').removeClass('btn-success').addClass('btn-danger')
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
					$("#gestion_tab tbody .alerte:first").focus()
					$('#saveAdd, #saveEdit').text('Champs à remplir').removeClass('btn-success').addClass('btn-danger')
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
					$("#gestion_tab tbody .alerte:first").focus()
					$('#saveAdd, #saveEdit').text('Champs à remplir').removeClass('btn-success').addClass('btn-danger')
					control = false
				}
			}
		})

		if(control){
			$('#saveAdd, #saveEdit').text('Enregistrer').removeClass('btn-danger').addClass('btn-success')
		}

		return control
	}
	function calculSolde(){

		let
			sign = $('#gestion_tab tbody').data('sign'),
			counterSign = sign == 'pos' ? 'neg' : 'pos',
			solde_fait = 0,
			solde_anticipe = 0
		;

		// Sous-total
		$(".td_number").each(function(index, value){
			if ($(this).is(":visible")){

				solde_fait = $(this).find('.inputNumber').val() != undefined
					? solde_fait + parseFloat($(this).find('.inputNumber').val())
					: $(this).text().trim() != ''
						? solde_fait + parseFloat($(this).text())
						: solde_fait
			}
		})

		$(".td_anticipe").each(function(index, value){
			if ($(this).is(":visible")){

				solde_anticipe = $(this).find('.inputAnticipe').val() != undefined
					? solde_anticipe + parseFloat($(this).find('.inputAnticipe').val())
					: $(this).text().trim() != ''
						? solde_anticipe + parseFloat($(this).text())
						: solde_anticipe
			}
		})

		// Math
		solde_fait = Math.round((solde_fait)*100)/100
		solde_anticipe = Math.round((solde_anticipe)*100)/100

		solde_fait = correctNumber(solde_fait)
		solde_anticipe = correctNumber(solde_anticipe)

		$('#soldeReel').text(solde_fait)
		$('#soldeAnticipe').text(solde_anticipe)
		$('#solde').text(Math.round((parseFloat(solde_fait) + parseFloat(solde_anticipe))*100)/100)

		// Color Reel
		$('#soldeReel').addClass('total_month_detail_'+ sign).removeClass('total_month_detail_' + counterSign)
	}

	function sauvegarde(){

		let
			datas = [],
			sc_id = $('#gestion_tab tbody').data('scid'),
			month = $('#gestion_tab tbody').data('month'),
			year = $('#gestion_tab tbody').data('year'),
			sign = $('#gestion_tab tbody').data('sign')
		;

		$("#gestion_tab tbody tr").not('#solde_tr_collabo, #solde_tr').each(function(index, value){

			let
				id_array = value.id.split('_'),
				id = id_array[2] ? id_array[2] : null,
				number = $(this).find('.inputNumber').val() == undefined ? null : parseFloat($(this).find('.inputNumber').val()).toFixed(2),
				number_anticipe = $(this).find('.inputAnticipe').val() == undefined ? null : parseFloat($(this).find('.inputAnticipe').val()).toFixed(2),
				day = $(this).find('.inputDay').val() == undefined ? null : $(this).find('.inputDay').val(),
				comment = $(this).find('.inputComment').val() == undefined ? null : $(this).find('.inputComment').val()
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
			url: Routing.generate('compte_gestion_save', { sc: sc_id, year: year, month: month, sign: sign }),
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

	function correctNumber(number){
		return parseFloat(number) % 1 == 0
			? parseFloat(number)
			: parseFloat(number).toFixed(2)
	}
})
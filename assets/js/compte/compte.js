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
		month = null,
		months = $('#datas').data('months'),
		year = $('#datas').data('year')
	;

	////////////
	// ON EVENTS
	////////////

	// Modal Operations
	$("body").not('.counterEdit').on("click", ".edit td:not(.counterEdit)", function(e){

		modalOpSpinner(true)

		let type = $(this).data('type')
		let sc_id = $(this).data('scid')
		let month = $(this).data('month')
		let anticipe = $(this).data('anticipe')

		modalOpMeta1(year, months[month], type)

		modalGetOperations(sc_id, year, month, type, anticipe)
	})

	$("body").on("click", "#modalOpeListe", function(e){
		$('#modalOpeEdit, #modalOpeCancel').prop('disabled', false).show()
	})

	////////////
	// FONCTIONS
	////////////

	function modalGetOperations(sc_id, year, month, type, anticipe){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_gestion', { sc: sc_id, year: year, month: month, type: type, anticipe: anticipe }),
			timeout: 15000,
			success: function(response){
				modalOpMeta2(response.category_libelle, response.subcategory_libelle)
				modalOpShow(response.operations)
				modalOpSpinner(false)
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
				modalOpSpinner(false)
			}
		})
	}

	function modalOpSpinner(etat){
		if (etat){
			$('.spinner').show()
			$('#modal_reel_contenu').hide()
			$('#modal_avenir_contenu').hide()

		} else {
			$('.spinner').hide()
			$('#modal_reel_contenu').show()
			$('#modal_avenir_contenu').show()
		}
	}

	function modalOpMeta1(year, month, type){

		let classType = 'total_month_full_' + type

		$('#modalOpeListeTbody').empty()
		$('#modalOpeEdit, #modalOpeCancel').prop('disabled', true).hide()

		$('#modal_date_annee').text(year)
		$('#modal_date_mois').text(ucFirst(month))

		$('#modal_category').text('.............')
		$('#modal_subcategory').text('.............')
		$('#modal_category, #modal_subcategory, #modalOpeSolde').removeClass("total_month_full_pos").removeClass("total_month_full_neg").addClass(classType)
	}

	function modalOpMeta2(category_libelle, subcategory_libelle, type, anticipe){
		$('#modal_category').text(ucFirst(category_libelle))
		$('#modal_subcategory').text(ucFirst(subcategory_libelle))
		$('#myTab').show()
	}

	function modalOpShow(operations){

		let
			tr = "",
			day = '',
			month = '',
			montant = 0
		;

		operations.forEach(function(item, index){

			console.log(item.number)

			day = item.day < 10 ? '0' + item.day : item.day
			month = item.month < 10 ? '0' + item.month : item.month
			montant = montant + item.number

			tr = 
				"<tr>" +
					"<td class='modal_ope_th'>" + item.number + "</td>" +
					"<td class='modal_ope_th'>" + day + '/' + month + '/' + item.year + "</td>" +
					"<td class='modal_ope_th_comment'>" + ucFirst(item.comment) + "</td>" +
					"<td class='modal_ope_th_actions'>" +
						"<button class='m-1'>Test</button>" +
						"<button class='m-1'>Truc</button> " +
					"</td>" +
				"</tr>"

			$('#modalOpeListeTbody').append(tr)

			tr = ''
		})

		$('#modalOpeSolde').text(Math.round(montant*100)/100)
	}
})
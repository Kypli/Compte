// JS
import { ucFirst } from '../service/service.js';

// CSS
import '../../styles/compte/compte.css';

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

	$("body").not('.counterEdit').on("click", ".edit td:not(.counterEdit)", function(e){

		modalOpSpinner(true)

		let type = $(this).data('type')
		let sc_id = $(this).data('scid')
		let month = $(this).data('month')
		let anticipe = $(this).data('anticipe')

		modalOpMeta1(year, months[month], type, anticipe)

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
	})

	////////////
	// FONCTIONS
	////////////

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

	function modalOpMeta1(year, month, type, anticipe){

		let type_text = type == 'pos' ? '(+)' : '(-)'
		type = 'total_month_full_' + type
		anticipe = anticipe ? 'A venir' : 'Réel'

		$('#modal_date_annee').text(year)
		$('#modal_date_mois').text(ucFirst(month))
		$('#modal_anticipe').text(anticipe)
		$('#modal_type').text(type_text).removeClass("total_month_full_pos").removeClass("total_month_full_neg").addClass(type)

		$('#modal_category').text('.............')
		$('#modal_subcategory').text('.............')

	}

	function modalOpMeta2(category_libelle, subcategory_libelle, type, anticipe){
		$('#modal_category').text(ucFirst(category_libelle))
		$('#modal_subcategory').text(ucFirst(subcategory_libelle))
		$('#myTab').show()
	}

	function modalOpShow(operations){
		console.log(operations)

	}
})
// JS
import './modalOperation.js';
import './modalCategory.js';
import { number_format } from '../service/service.js';

// CSS
import '../../styles/compte/compte.css';

////////////
// EXPORT FONCTIONS
////////////

// update tables
export function updateTables(){

	$.ajax({
		type: "POST",
		url: Routing.generate('compte_tables', { id: $('#datas').data('compteid') }),
		timeout: 15000,
		success: function(response){
			$('#tables').empty().append(response.render)
			editSolde(response.solde)
		},
		error: function(error){
			console.log('Erreur ajax: ' + error)
		}
	})
}


////////////
// FONCTIONS
////////////

// Color soldeActuel
function editSolde(solde){

	$('#soldeActuelNb').text(number_format(solde, 2, ',', ' '))

	let 
		decouvert = $('#datas').data('decouvert'),
		hideAlert = true
	;

	if (solde == 0){
		$('#soldeActuel')
			.addClass('total_month_full_neutre')
			.removeClass('total_month_full_pos')
			.removeClass('total_month_full_neg')
			.removeClass('total_month_full_dec')

	} else if(solde > 0){
		$('#soldeActuel')
			.addClass('total_month_full_pos')
			.removeClass('total_month_full_neutre')
			.removeClass('total_month_full_neg')
			.removeClass('total_month_full_dec')

	} else if (solde < decouvert){
		hideAlert = false
		$('#soldeActuel')
			.addClass('total_month_full_neg')
			.removeClass('total_month_full_pos')
			.removeClass('total_month_full_neutre')
			.removeClass('total_month_full_dec')

	} else {
		$('#soldeActuel')
			.addClass('total_month_full_dec')
			.removeClass('total_month_full_pos')
			.removeClass('total_month_full_neutre')
			.removeClass('total_month_full_neg')
	}

	hideAlert
		? $('#soldeAlert').hide()
		: $('#soldeAlert').show()
}

$(document).ready(function(){

	////////////
	// ON EVENTS
	////////////

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
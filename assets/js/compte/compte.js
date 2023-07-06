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
		url: Routing.generate('compte_tables', { id: $('#datas').data('compteid'), year: $('#datas').data('year') }),
		timeout: 15000,
		beforeSend: function(){
			spinner(true)
		},
		success: function(response){
			$('#tables').empty().append(response.render)
			$('#last-actions-div').empty().append(response.render_last_actions)
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

	hideAlert
		? $('#solde'+text+'Alert').hide()
		: $('#solde'+text+'Alert').show()
}

// Spinner
function spinner(etat){
	etat
		? $('#show_spinner').removeClass('hide').show()
		: $('.spinner').addClass('hide').hide()
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
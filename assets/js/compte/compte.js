// JS
import './modalOperation.js';
import './modalCategory.js';

// CSS
import '../../styles/compte/compte.css';

// EXPORT FONCTIONS
export function updateTable(){

	$.ajax({
		type: "POST",
		url: Routing.generate('compte_tables', { id: $('#datas').data('compteid') }),
		timeout: 15000,
		success: function(response){
			$('#tables').empty().append(response.render)
			$('#soldeActuel').text(response.solde)
		},
		error: function(error){
			console.log('Erreur ajax: ' + error)
		}
	})
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
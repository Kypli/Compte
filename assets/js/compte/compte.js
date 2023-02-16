// JS
import './modalGestion.js';
import './modalCategory.js';

// CSS
import '../../styles/compte/compte.css';

	////////////
	// FONCTIONS
	////////////

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
	)
})
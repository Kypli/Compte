// JS
import './modalGestion.js';
import './modalCategory.js';

// CSS
import '../../styles/compte/compte.css';

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


	////////////
	// FONCTIONS
	////////////

	function calculTable(){
		// TODO
	}
})
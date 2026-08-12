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

	return $.ajax({
		type: "POST",
		url: Routing.generate('compte_tables', {
			id: $('#datas').data('compteid'),
			year: $('#datas').data('year'),
			months: $('#datas').data('monthdisplay')
		}),
		timeout: 15000,
		beforeSend: function(){
			spinner(true)
		},
		success: function(response){
			$('#tables').empty().append(response.render)
			centerCompactTables()
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

function centerCompactTables(){
	document.querySelectorAll('.compte-table-viewport').forEach(viewport => {
		const table = viewport.querySelector('.month-display-compact')
		if (!table) {
			return
		}

		const visibleMonths = table.querySelectorAll('tr:first-child .month-visible')
		if (!visibleMonths.length) {
			return
		}

		table.style.marginLeft = '0px'
		table.style.marginRight = '0px'

		const firstMonth = visibleMonths[0]
		const lastMonth = visibleMonths[visibleMonths.length - 1]
		const monthsCenter = (firstMonth.offsetLeft + lastMonth.offsetLeft + lastMonth.offsetWidth) / 2
		const viewportCenter = viewport.clientWidth / 2
		const tableWidth = table.offsetWidth

		const leftSpace = Math.max(0, viewportCenter - monthsCenter)
		const rightSpace = Math.max(0, viewportCenter - (tableWidth - monthsCenter))
		table.style.marginLeft = `${leftSpace}px`
		table.style.marginRight = `${rightSpace}px`
		viewport.scrollLeft = leftSpace + monthsCenter - viewportCenter
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
	centerCompactTables()
	let resizeFrame
	window.addEventListener('resize', function(){
		window.cancelAnimationFrame(resizeFrame)
		resizeFrame = window.requestAnimationFrame(centerCompactTables)
	})

	////////////
	// ON EVENTS
	////////////
	$('#monthDisplay').on('change', function(){
		const select = this
		const datas = $('#datas')
		const previousMonthDisplay = datas.data('monthdisplay')
		const monthDisplay = select.value
		const url = new URL(window.location.href)

		datas
			.data('monthdisplay', monthDisplay)
			.attr('data-monthdisplay', monthDisplay)
		url.searchParams.set('months', monthDisplay)
		window.history.replaceState(window.history.state, '', url)
		select.disabled = true

		updateTables()
			.fail(function(){
				datas
					.data('monthdisplay', previousMonthDisplay)
					.attr('data-monthdisplay', previousMonthDisplay)
				select.value = previousMonthDisplay
				url.searchParams.set('months', previousMonthDisplay)
				window.history.replaceState(window.history.state, '', url)
			})
			.always(function(){
				select.disabled = false
			})
	})

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

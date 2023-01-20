// CSS
import '../../styles/compte/modalCategory.css';

$(document).ready(function(){

	////////////
	// ON LOAD
	////////////
	var
		add = '',
		input_datas = {}
	;

	////////////
	// ON EVENTS
	////////////

	// Open Modal Category
	$("body").on("click", ".td_category_libelle, .td_subcategory_libelle", function(e){
		getCategories($(this).data('sign'), $(this).data('focusa'))
	})

	// Add Sc
	$("body").on("click", ".add_sub", function(e){
		$('#cat_tab tbody').append(add)
		$('.tr_add').insertAfter('#cat_tab tbody tr:last')
		$('#cat_tab tbody input:last').focus()
		chevronAddToggle()
		editMod(true)
	})

	// Delete Sc
	$("body").on("click", ".delete_sc", function(e){
		$(this).parent().parent().remove()
		chevronAddToggle()
		etatEditMod()
	})

	// Edit name
	$("body").on("keyup", "#modalCategory input", function(e){
		alertEditInput()
		etatEditMod()
		controlForm()
	})

	// Input mouseover
	$("body").on("mouseover", ".tr_subcategories", function(e){
		$(this).find('.fa-chevron-up, .fa-chevron-down, .delete_sc').show();
		chevronToggle($(this))
	})

	// Input mouseout
	$("body").on("mouseout", ".tr_subcategories", function(e){
		$(this).find('.fa-chevron-up, .fa-chevron-down, .delete_sc').hide();
	})

	// Position up
	$("body").on("click", ".td_chevron_up", function(e){
		posChange(true, $(this).parent('tr'))
	})

	// Position down
	$("body").on("click", ".td_chevron_down", function(e){
		posChange(false, $(this).parent('tr'))
	})

	// Reset
	$("body").on("click", "#cancel_cat", function(e){
		reset()
		alertEditInput()
		editMod(false)
	})

	// Save & Close
	$("body").on("click", "#modalCatSaveClose", function(e){
		if (controlForm()){
			sauvegarde()
		}
	})

	////////////
	// FONCTIONS
	////////////

	/** Chargement **/

	// Récupère le render de la catégorie
	function getCategories(sign, focus){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_categorie', { id: $('#datas').data('compteid'), sign: sign }),
			timeout: 15000,
			beforeSend: function(){
				$('#cat_name').text('')
				editMod(false)
				spinner(true)
			},
			success: function(response){
				show(response.render, focus)
				$('.fa-chevron-up, .fa-chevron-down').hide()
				spinner(false)
				getInputAdd()
				getInputDatas()
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
				spinner(false)
			}
		})
	}

	// Affiche le render et focus
	function show(tr, focus){
		$('#cat_tab tbody').append(tr)
		$('#' + focus).focus()
		$('#cat_name').text($('.tr_category input').val())
	}

	// Récupère le tr_add
	function getInputAdd(){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_scategory_add'),
			timeout: 15000,
			success: function(response){
				add = response.render
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
			}
		})
	}

	// Récupère les val des input
	function getInputDatas(){

		$('#modalCategory input').not('.tr_category input').each(function(index, input){
			input_datas[input.id] = $(this).val()
		})
	}

	function spinner(etat){
		if (etat){
			$('#cat_tab tbody').empty()
			$('.spinner').show()

		} else {
			$('.spinner').hide()
		}
	}


	/** Position **/

	// Change la position d'un tr
	function posChange(pos, tr){

		// Up
		if (pos){

			let tr_prev = tr.prev('tr')
			if (!tr_prev.hasClass('tr_category')){
				tr.insertBefore(tr_prev)
			}

		// Down
		} else {

			let tr_next = tr.next('tr')
			if (!tr_next.hasClass('tr_add')){
				tr.insertAfter(tr_next)
			}
		}
		chevronAddToggle()
	}

	// Cache/Montre les chevrons
	function chevronToggle(tr){

		let tr_prev = tr.prev('tr')
		tr_prev.hasClass('tr_category')
			? tr.find('.td_chevron_up .fa-chevron-up').hide()
			: tr.find('.td_chevron_up .fa-chevron-up').show()

		let tr_next = tr.next('tr')
		tr_next.hasClass('tr_add')
			? tr.find('.td_chevron_down .fa-chevron-down').hide()
			: tr.find('.td_chevron_down .fa-chevron-down').show()

	}

	// Cache/Montre les chevrons des input ajoutés
	function chevronAddToggle(){

		$('#modalCategory .tr_subcategories_add').each(function(index, tr){

			let tr_prev = $(this).prev('tr')
			tr_prev.hasClass('tr_category')
				? $(this).find('.td_chevron_up .fa-chevron-up').hide()
				: $(this).find('.td_chevron_up .fa-chevron-up').show()

			let tr_next = $(this).next('tr')
			tr_next.hasClass('tr_add')
				? $(this).find('.td_chevron_down .fa-chevron-down').hide()
				: $(this).find('.td_chevron_down .fa-chevron-down').show()
		})
	}


	/** Édition **/

	// Check si application du mode Edition
	function etatEditMod(){

		let etat = false

		// Edit sc ?
		$.each(input_datas, function(index, value){
			if ($('#' + index).val() != value){
				etat = true
			}
		})

		// Add sc ?
		let nb_sc_add = $('#cat_tab .tr_subcategories_add').length
		if(nb_sc_add > 0){ etat = true }

			console.log(etat)
		
		editMod(etat)
	}

	// Mode édition
	function editMod(etat){

		if (etat){
			$('#modalCatClose').text('Fermer sans enregistrer')
			$('#modalCatSaveClose').prop('disabled', false).show()
			$('#cancel_cat').prop('disabled', false).show()

		} else {
			$('#modalCatClose').text('Fermer')
			$('#modalCatSaveClose').prop('disabled', true).hide()
			$('#cancel_cat').prop('disabled', true).hide()
		}
	}

	// Input en bleu si édition
	function alertEditInput(){
		$('#modalCategory .tr_subcategories input').each(function(index, tr){
			input_datas[tr.id] == $(this).val()
				? $(this).removeClass('blue')
				: $(this).addClass('blue')
		})
	}

	function reset(){

		// Retire les add
		$('#modalCategory .tr_subcategories_add').each(function(index, tr){
			$(this).remove()
		})

		// Reset la value des input
		$.each(input_datas, function(index, value){
			$('#' + index).val(value)
		})

		controlForm()
	}


	/** Sauvegarde **/

	function controlForm(){

		let	control = true;

		$(".tr_category input, #cat_tab .tr_subcategories input").each(function(index, value){

			if ($(this).val() == ''){
				control = false
				$(this).addClass('alerte')
			} else {
				$(this).removeClass('alerte')
			}
		})

		if (control){
			$('#modalCatSaveClose').prop('disabled', false)
		} else {
			$('#modalCatSaveClose').prop('disabled', true)
		}

		return control
	}

	function sauvegarde(){

		let	datas = []

		// Retire les add vides
		$('#cat_tab .tr_subcategories_add').each(function(index, tr){
			if ($(this).find('input').val() == ''){
				$(this).remove()
			}
		})

		// Datas cat
		$(".tr_category").each(function(index, tr){

			let id = tr.id
			if (id != ''){ id = id.split('_')[2] }

			datas.push({
				id: id,
				libelle: $(this).find('input').val(),
				type: 'cat',
				position: 0,
			})
		})

		// Datas subcat + subcat add
		$("#cat_tab .tr_subcategories, #cat_tab .tr_subcategories_add").each(function(index, tr){

			let id = tr.id
			if (id != ''){ id = id.split('_')[2] }

			datas.push({
				id: id,
				libelle: $(this).find('input').val(),
				type: 'sc',
				position: index + 1,
			})
		})

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_categorie_edit', { id: $('#datas').data('compteid'), year: $('#datas').data('year') }),
			data: { datas: datas },
			dataType: 'JSON',
			timeout: 15000,
			beforeSend: function(){

			},
			success: function(response){
				if (response.save == true){
					console.log('ok')
				}
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
			}
		})
	}
})
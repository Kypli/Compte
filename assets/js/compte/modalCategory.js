// CSS
import '../../styles/compte/modalCategory.css';

$(document).ready(function(){

	////////////
	// ON LOAD
	////////////

	var
		_addSc = '',
		_addSc_disabled = '',
		sc_back_nb = 0,
		input_datas = {},
		reset_render = '',
		cat_add_render = ''
	;

	getAddSc()
	getAddSc(true)

	////////////
	// ON EVENTS
	////////////

	/** Chargement **/

	// Open Modal Category
	$("body").on("click", ".td_category_libelle, .td_subcategory_libelle", function(e){
		let sign = $(this).parent().parent().parent().data('sign')
		$('#cat_tab').data('sign', sign)
		getCategory($(this).data('id'), sign, $(this).data('focusa'))
	})


	/** Position **/

	// Cat chevron mouseover
	$("body").on("mouseover", ".tr_category", function(e){
		$(this)
			.find('.fa-chevron-circle-up, .fa-chevron-circle-down, .delete_cat')
			.addClass('opacity_full')
			.removeClass('opacity_low');
		cat_chevronToggle()
	})

	// Cat chevron mouseout
	$("body").on("mouseout", ".tr_category", function(e){
		$(this)
			.find('.fa-chevron-circle-up, .fa-chevron-circle-down, .delete_cat')
			.addClass('opacity_low')
			.removeClass('opacity_full')
	})

	// Cat position up
	$("body").on("click", "#td_chevron_cat_up", function(e){
		cat_posChange(true, $(this))
	})

	// Cat position down
	$("body").on("click", "#td_chevron_cat_down", function(e){
		cat_posChange(false, $(this))
	})

	// Sc chevron/button mouseover
	$("body").on("mouseover", ".tr_subcategories, .tr_subcategories_add", function(e){
		$(this)
			.find('.fa-chevron-up, .fa-chevron-down, .delete_sc, .not_delete_sc')
			.addClass('opacity_full')
			.removeClass('opacity_low')
	})

	// Sc chevron/button mouseout
	$("body").on("mouseout", ".tr_subcategories, .tr_subcategories_add", function(e){
		$(this)
			.find('.fa-chevron-up, .fa-chevron-down, .delete_sc, .not_delete_sc')
			.addClass('opacity_low')
			.removeClass('opacity_full')
	})

	// Sub-Cat position up
	$("body").on("click", ".td_chevron_up", function(e){
		sc_posChange(true, $(this).parent('tr'))
	})

	// Sub-Cat position down
	$("body").on("click", ".td_chevron_down", function(e){
		sc_posChange(false, $(this).parent('tr'))
	})


	/** Add **/

	// Add Cat from table
	$("body").on("click", ".add_cat button", function(e){
		let sign = $(this).parents('.table ').data('sign')
		$('#cat_tab').data('sign', sign)
		getAddCategory(sign)
	})

	// Add Cat from modal
	$("body").on("click", "#add_cat", function(e){
		getAddCategory($('#cat_tab').data('sign'))
		$('#modalCatSaveClose').prop('disabled', true)
	})

	// Add Sc
	$("body").on("click", ".add_sub", function(e){
		addSc()
	})


	/** Édition **/

	// Edit name
	$("body").on("keyup", "#modalCategory input", function(e){
		editAlert()
		controlForm()
	})


	/** Delete **/

	// Delete Cat
	$("body").on("click", ".delete_cat", function(e){
		if (confirm('Êtes-vous certain de supprimer cette catégorie ? Attention, cette action est irréversible !')){
			deleteCat($(this))
		}
	})

	// Delete Sc
	$("body").on("click", ".delete_sc", function(e){
		deleteSc($(this))
	})

	// Delete Back Sc
	$("body").on("click", ".fa-rotate-right", function(e){
		getSc($(this))
	})


	/** Sauvegarde **/

	// Reset
	$("body").on("click", "#cancel_cat", function(e){
		editReset()
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

	// Récupère le render d'une nouvelle catégorie
	function getAddCategory(sign){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_category_add', { id: $('#datas').data('compteid'), sign: sign }),
			timeout: 15000,
			beforeSend: function(){
				$('#cat_name').text('')
				addMod(true)
				spinner(true)
			},
			success: function(response){
				reset_render = response.render
				show(response.render, 'tr_category_add input')
				$('.tr_category input').addClass('input_add')
				addSc(true)
				getInputDatas()
				spinner(false)
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
				addMod(false)
				spinner(false)
			}
		})
	}

	// Récupère le render d'une catégorie
	function getCategory(cat_id, sign, focus){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_category', { id: $('#datas').data('compteid'), cat_id: cat_id, sign: sign }),
			timeout: 15000,
			beforeSend: function(){
				$('#cat_name').text('')
				editMod(false)
				spinner(true)
			},
			success: function(response){
				reset_render = response.render
				show(response.render, focus)
				spinner(false)
				getInputDatas()
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
				spinner(false)
			}
		})
	}

	// Récupère le render d'une nouvelle subCatégorie
	function getAddSc(addMod = false){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_subcategory_add', { addMod: addMod == true ? '1' : '0' }),
			timeout: 15000,
			success: function(response){
				addMod
					? _addSc = response.render
					: _addSc_disabled = response.render
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
			}
		})
	}

	// Récupère le render d'une subCatégorie
	function getSc(icon){

		let
			div = icon.parent('div'),
			id = div.prop('id'),
			pos = div.data('pos')
		;

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_subcategory', { id: id }),
			timeout: 15000,
			beforeSend: function(){
			},
			success: function(response){
				backSc(response.render, pos)
				div.remove()
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
			}
		})
	}

	// Affiche le render et focus
	function show(tr, focus){
		$('#cat_tab tbody').append(tr)
		$('#' + focus).focus()
		$('#cat_name').text($('.tr_category input').val())
		sc_chevronToggle()
		cat_chevronToggle()
	}

	// Récupère les datas des input pour control édition
	function getInputDatas(){

		// Cat
		input_datas[$('.tr_category input').prop('id')] = $('.tr_category input').val()
		input_datas['pos_' + $('.tr_category input').prop('id')] = $('.tr_category input').data('pos')

		// Sc
		$('#modalCategory input').not('.tr_category input').each(function(index, input){
			input_datas[input.id] = $(this).val()
			input_datas['pos_' + input.id] = $(this).data('pos')
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

	// Change la position d'un tr de catégory
	function cat_posChange(pos, icon){

		let
			speed = 700,
			number = $('.tr_category input').data('pos')
		;

		// Délai
		if (!icon.hasClass('grey')){

			icon.addClass('grey').removeClass('pointeur')

			// Up
			if (pos){

				let div_before = $('.before_actif')

				// Limite non atteinte
				if (!div_before.hasClass('limite')){

					let text = div_before.text().trim()
					let id = div_before.data('id')

					// Before
					div_before.slideUp(speed)
					div_before.prev('div').slideDown(speed).addClass('before_actif')

					setTimeout(function(){
						div_before.remove()
					}, speed);

					// After
					let div_after = $('.after_actif').removeClass('after_actif')
					div_after.before("<div data-id='" + id + "' class='hide after_actif'> " + text + " </div>")

					div_after.slideUp(speed)
					$('.after_actif').slideDown(speed)

					// Change data pos
					$('.tr_category input').data('pos', number - 1)
				}
			}

			// Down
			else {

				let div_after = $('.after_actif')

				// Limite non atteinte
				if (!div_after.hasClass('limite')){

					let text = div_after.text().trim()
					let id = div_after.data('id')

					// After
					div_after.slideUp(speed)
					div_after.next('div').slideDown(speed).addClass('after_actif')

					setTimeout(function(){
						div_after.remove()
					}, speed);

					// Before
					let div_before = $('.before_actif').removeClass('before_actif')
					div_before.after("<div data-id='" + id + "' class='hide before_actif'> " + text + " </div>")

					div_before.slideUp(speed)
					$('.before_actif').slideDown(speed)

					// Change data pos
					$('.tr_category input').data('pos', number + 1)
				}
			}

			cat_chevronToggle()
			editAlert()
		}
		setTimeout(function(){
			icon.removeClass('grey ').addClass('pointeur')
		}, speed);
	}

	// Cache/Montre les chevrons si limite atteinte
	function cat_chevronToggle(){

		$('.before_actif').hasClass('limite')
			? $('.fa-chevron-circle-up').hide()
			: $('.fa-chevron-circle-up').show()

		$('.after_actif').hasClass('limite')
			? $('.fa-chevron-circle-down').hide()
			: $('.fa-chevron-circle-down').show()
	}

	// Change la position d'un tr de subCategory
	function sc_posChange(pos, tr){

		// Up
		if (pos){

			let tr_prev = tr.prev('tr')
			if (!tr_prev.hasClass('tr_category_after')){

				// Move
				tr.insertBefore(tr_prev)
			}
		}

		// Down
		else {

			let tr_next = tr.next('tr')
			if (!tr_next.hasClass('tr_add')){

				// Move
				tr.insertAfter(tr_next)
			}
		}

		sc_chevronToggle()
		editAlert()
	}

	// Cache/Montre les chevrons si limite atteinte
	function sc_chevronToggle(){

		$(".td_chevron_up, .td_chevron_down").each(function(){
			$(this).addClass('pointeur').show().find('.fa-chevron-up, .fa-chevron-down').show()
		})

		$(".tr_category_after").next('tr').find('.td_chevron_up').removeClass('pointeur')
		$(".tr_category_after").next('tr').find('.fa-chevron-up').hide()

		$(".tr_add").prev('tr').find('.td_chevron_down').removeClass('pointeur')
		$(".tr_add").prev('tr').find('.fa-chevron-down').hide()
	}


	/** Add **/

	// Mode add
	function addMod(etat){

		if (etat){
			$('#add_cat').hide()
			$('.modal-footer').show()
			$('#modalCatClose').text('Fermer sans enregistrer')
			$('#modalCatSaveClose').prop('disabled', true).show()

		} else {
			$('#add_cat').show()
			$('.modal-footer').hide()
			$('#modalCatClose').text('Fermer')
			$('.tr_category input').removeClass('input_add')
		}
		$('#cancel_cat').prop('disabled', false).hide()
	}

	// Add Sc
	function addSc(addMod = false){

		if (addMod){
			$('#cat_tab tbody').append(_addSc_disabled)

		} else {
			$('#cat_tab tbody').append(_addSc)
			$('#cat_tab tbody input:last').focus()
			editAlert()
		}

		$('.tr_add').insertAfter('#cat_tab tbody tr:last')
		sc_chevronToggle()
		controlForm()
	}

	// Back Sc
	function backSc(tr_back, pos){

		let add = false

		// Pos 1
		if (pos == 1){
			add = true
			$('.tr_category_after').after(tr_back)
		}

		// Pos x
		$("#body_cat tbody tr:not(.tr_category_before, .tr_category, .tr_category_after, .tr_add)").each(function(){

			let tr = $(this).find('input'),
				tr_pos = tr.data('pos')
			;

			if (tr_pos == pos && pos != 1){
				add = true
				$(this).before(tr_back)
			}
		})

		// Pos last
		if (!add){
			$('.tr_add').before(tr_back)
		}

		sc_back_nb -= 1

		sc_chevronToggle()
		editAlert()
	}


	/** Édition **/

	// Mode édition
	function editMod(etat){

		if (etat){
			$('#modalCatClose').text('Fermer sans enregistrer')
			$('#modalCatSaveClose').prop('disabled', false).show()
			$('#cancel_cat').prop('disabled', false).show()
			$('.modal-footer').show()

		} else {
			$('#modalCatClose').text('Fermer')
			$('#modalCatSaveClose').prop('disabled', true).hide()
			$('#cancel_cat').prop('disabled', true).hide()
			$('.modal-footer').hide()
		}
		$('#add_cat').show()
	}

	// Alerte visuelle d'édition
	function editAlert(){

		// Pas d'alerte si addMod
		if ($('#tr_category_add').length == 1){
			return true
		}

		let checkEditMod = false


		/** Cat **/
		let cat_input = $('.tr_category input')

		// Val
		input_datas[cat_input.prop('id')] == cat_input.val()
			? cat_input.removeClass('input_edit_val')
			: checkEditMod = true && cat_input.addClass('input_edit_val')

		// Pos
		input_datas['pos_' + cat_input.prop('id')] == cat_input.data('pos')
			? cat_input.removeClass('input_edit')
			: checkEditMod = true && cat_input.addClass('input_edit')


		/** Sc **/

		// Set Pos
		$("#body_cat tbody tr:not(.tr_category_before, .tr_category, .tr_category_after, .tr_add)").each(function(index){
			$(this).find('input').data('pos', index + 1)
		})

		// Check Val && Pos
		$('#modalCategory .tr_subcategories input').each(function(index, inputText){

			// Val
			input_datas[inputText.id] == $(this).val()
				? $(this).removeClass('input_edit_val')
				: checkEditMod = true && $(this).addClass('input_edit_val')

			// Pos
			if (input_datas['pos_' + inputText.id] == $(this).data('pos')){
				$(this).removeClass('input_edit')
			} else {
				$(this).addClass('input_edit')
				checkEditMod = true
			}
		})

		// Add sc ?
		let nb_sc_add = $('#cat_tab .tr_subcategories_add').length
		if(nb_sc_add > 0){ checkEditMod = true }

		// Delete sc ?
		sc_back_nb > 0
			? checkEditMod = true
			: $('.delete_zone').hide()

		// Check EditMod
		editMod(checkEditMod)
	}

	// Reset les éditions
	function editReset(){
		$('#cat_tab tbody').empty()
		$('.delete_zone').empty().hide()
		sc_back_nb = 0
		show(reset_render, 'tr_category input')
		editAlert()
		controlForm()
	}


	/** Delete **/

	// Delete Sc
	function deleteCat(button_del){

		let
			tr = button_del.parent().parent(),
			input = tr.find('input'),
			datas = {
				compte_id: $('#datas').data('compteid'),
				cat_id: input.prop('id').substr(4),
				year: $('#datas').data('year'),
			}
		;

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_category_delete'),
			timeout: 15000,
			dataType: 'JSON',
			data: { datas: datas },
			// beforeSend: function(){

			// },
			success: function(response){
				if (response.save == true){
					console.log('ok')
				}
			},
			error: function(error){
				console.log('Erreur ajax: ' + error)
			}
		})

		// Fait cliquer sur le bouton close
		$('#modalCatClose').trigger('click');
	}

	// Delete Sc
	function deleteSc(button_del){

		let
			tr = button_del.parent().parent(),
			input = tr.find('input'),
			pos_delete = input.data('pos'),
			id = input.prop('id').substr(3),
			div = 
			"<div id='"+id+"' class='col-2 m-3 delete_zone_div' data-pos='" + pos_delete + "'>" +
				"<i class='pointeur fa-solid fa-rotate-right'></i>" +
				"&nbsp;&nbsp;&nbsp;<span class='barre red taille18'>" + input.val() + "</span>" +
			"</div>"
		;

		// Add input_delete to delete_zone
		if (!tr.hasClass('tr_subcategories_add')){
			$('.delete_zone').append(div).show()
			sc_back_nb += 1
		}

		tr.remove()
		sc_chevronToggle()
		editAlert()
		controlForm()
	}


	/** Sauvegarde **/

	// Vérifie s'il n'y a pas d'erreur dans les input
	function controlForm(){

		let
			control = true,
			nb_Sc = 0
		;

		// Input vide
		$("#cat_tab .tr_category input, #cat_tab .tr_subcategories input").each(function(index, value){

			if ($(this).val() == ''){
				control = false
				$(this).addClass('alerte')
			} else {
				$(this).removeClass('alerte')
			}
		})

		// Au moins 1 sc
		$("#cat_tab .tr_subcategories input, #cat_tab .tr_subcategories_add input").each(function(index, value){

			if ($(this).val() != ''){
				++nb_Sc
			}
		})

		if (control && nb_Sc > 0){
			$('#modalCatSaveClose').prop('disabled', false)
		} else {
			$('#modalCatSaveClose').prop('disabled', true)
		}

		return control
	}

	// Sauvegarde
	function sauvegarde(){

		let	datas = []

		// Compte_id, Year
		datas.push({compte_id: $('#datas').data('compteid'), year: $('#datas').data('year')})

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
				sign: $('#cat_tab').data('sign'),
				position: $(this).find('input').data('pos'),
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
			url: Routing.generate('compte_category_save'),
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
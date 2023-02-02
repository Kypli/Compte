// CSS
import '../../styles/compte/modalCategory.css';

$(document).ready(function(){

	////////////
	// ON LOAD
	////////////

	var
		add = '',
		input_datas = {},
		reset_render = '',
		reset_focus = ''
	;

	////////////
	// ON EVENTS
	////////////

	/** Chargement **/

	// Open Modal Category
	$("body").on("click", ".td_category_libelle, .td_subcategory_libelle", function(e){
		getCategories($(this).data('id'), $(this).data('sign'), $(this).data('focusa'))
	})


	/** Position **/

	// Cat chevron mouseover
	$("body").on("mouseover", ".tr_category", function(e){
		$(this).find('.fa-chevron-circle-up, .fa-chevron-circle-down').show();
		cat_chevronToggle()
	})

	// Cat chevron mouseout
	$("body").on("mouseout", ".tr_category", function(e){
		$(this).find('.fa-chevron-circle-up, .fa-chevron-circle-down').hide();
	})

	// Sc chevron mouseover
	$("body").on("mouseover", ".tr_subcategories", function(e){
		$(this).find('.fa-chevron-up, .fa-chevron-down, .delete_sc').show();
		sc_chevronToggle($(this))
	})

	// Sc chevron mouseout
	$("body").on("mouseout", ".tr_subcategories", function(e){
		$(this).find('.fa-chevron-up, .fa-chevron-down, .delete_sc').hide();
	})

	// Cat position up
	$("body").on("click", "#td_chevron_cat_up", function(e){
		cat_posChange(true, $(this))
	})

	// Cat position down
	$("body").on("click", "#td_chevron_cat_down", function(e){
		cat_posChange(false, $(this))
	})

	// Sub-Cat position up
	$("body").on("click", ".td_chevron_up", function(e){
		sc_posChange(true, $(this).parent('tr'))
	})

	// Sub-Cat position down
	$("body").on("click", ".td_chevron_down", function(e){
		sc_posChange(false, $(this).parent('tr'))
	})


	/** Édition **/

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
		editAlert()
	})

	// Edit name
	$("body").on("keyup", "#modalCategory input", function(e){
		editAlert()
		controlForm()
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

	// Récupère le render de la catégorie
	function getCategories(cat_id, sign, focus){

		$.ajax({
			type: "POST",
			url: Routing.generate('compte_categorie', { id: $('#datas').data('compteid'), cat_id: cat_id, sign: sign }),
			timeout: 15000,
			beforeSend: function(){
				$('#cat_name').text('')
				editMod(false)
				spinner(true)
			},
			success: function(response){
				reset_focus = focus
				reset_render = response.render
				show(response.render, focus)
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
		$('.fa-chevron-up, .fa-chevron-down, .fa-chevron-circle-up, .fa-chevron-circle-down').hide()
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

	// Change la position d'un tr
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

			editAlert()
		}
		setTimeout(function(){
			icon.removeClass('grey ').addClass('pointeur')
		}, speed);
	}

	// Cache/Montre les chevrons si limite atteinte
	function cat_chevronToggle(){

		if ($('.before_actif').hasClass('limite')){
			$('.fa-chevron-circle-up').hide()
		}

		if ($('.after_actif').hasClass('limite')){
			$('.fa-chevron-circle-down').hide()
		}
	}

	// Change la position d'un tr
	function sc_posChange(pos, tr){

		let
			input = tr.find('input'),
			number = input.data('pos')
		;

		// Up
		if (pos){

			let tr_prev = tr.prev('tr')
			if (!tr_prev.hasClass('tr_category_after')){

				// Move
				tr.insertBefore(tr_prev)

				// Change pos
				input.data('pos', number - 1)
				tr_prev.find('input').data('pos', number)
			}
		}

		// Down
		else {

			let tr_next = tr.next('tr')
			if (!tr_next.hasClass('tr_add')){

				// Move
				tr.insertAfter(tr_next)

				// Change pos
				input.data('pos', number + 1)
				tr_next.find('input').data('pos', number)
			}
		}
		
		chevronAddToggle()
		editAlert()
	}

	// Cache/Montre les chevrons si limite atteinte
	function sc_chevronToggle(tr){

		let tr_prev = tr.prev('tr')
		tr_prev.hasClass('tr_category_after')
			? tr.find('.td_chevron_up .fa-chevron-up').hide() && tr.find('.td_chevron_up').removeClass('pointeur')
			: tr.find('.td_chevron_up .fa-chevron-up').show() && tr.find('.td_chevron_up').addClass('pointeur')

		let tr_next = tr.next('tr')
		tr_next.hasClass('tr_add')
			? tr.find('.td_chevron_down .fa-chevron-down').hide() && tr.find('.td_chevron_down').removeClass('pointeur')
			: tr.find('.td_chevron_down .fa-chevron-down').show() && tr.find('.td_chevron_down').addClass('pointeur')
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

	// Alerte visuelle d'édition
	function editAlert(){

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
		$('#modalCategory .tr_subcategories input').each(function(index, inputText){

			// Val
			input_datas[inputText.id] == $(this).val()
				? $(this).removeClass('input_edit_val')
				: checkEditMod = true && $(this).addClass('input_edit_val')

			// Pos
			input_datas['pos_' + inputText.id] == $(this).data('pos')
				? $(this).removeClass('input_edit')
				: checkEditMod = true && $(this).addClass('input_edit')
		})

		// Add sc ?
		let nb_sc_add = $('#cat_tab .tr_subcategories_add').length
		if(nb_sc_add > 0){ checkEditMod = true }
		
		// Check EditMod
		editMod(checkEditMod)
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

	// Reset les éditions
	function editReset(){

		$('#cat_tab tbody').empty()
		show(reset_render, reset_focus)
		editAlert()
		controlForm()
	}


	/** Sauvegarde **/

	// Vérifie s'il n'y a pas d'erreur dans les input
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

	// Sauvegarde
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
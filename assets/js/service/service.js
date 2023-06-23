
////////////
// FONCTION EXPORT
////////////

// Convertit ou crée Date en string
export function dateToString(date){

	var 
		newDate = new Date(),
		dateString = typeof date == Date && date != "" && date != null
			? date
			: newDate,
		jour_0 = dateString.getDate() < 10
			? '0'
			: '',
		mois_0 = dateString.getMonth() < 10
			? '0'
			: ''
	;

	return jour_0 + dateString.getDate() + "/" + mois_0 + (dateString.getMonth() + 1) + "/" + dateString.getFullYear()
}


// 1ere lettre Majuscule
export function ucFirst(str){

	return (str + '').charAt(0).toUpperCase() + str.substr(1)
}

// 1ere lettre Minuscule
export function lcFirst(str){

	return (str + '').charAt(0).toLowerCase() + str.substr(1)
}

// Rajoute un 0 a l'unité des cts si besoin
export function monnaieStyle(monnaie){

	monnaie = monnaie.toString()

	if (monnaie.length == 0){ return 0 }

	if (monnaie.indexOf(".") > -1){

		let cts = monnaie.split('.')

		return cts[1].length == 1
			? monnaie + '0'
			: monnaie
	}

	return monnaie
}
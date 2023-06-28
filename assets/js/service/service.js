
////////////
// FONCTION EXPORT
////////////

/** Date **/

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


/** Font **/

// 1ere lettre Majuscule
export function ucFirst(str){

	return (str + '').charAt(0).toUpperCase() + str.substr(1)
}

// 1ere lettre Minuscule
export function lcFirst(str){

	return (str + '').charAt(0).toLowerCase() + str.substr(1)
}


/** Math **/

// Convert to input number
export function number_toInput(str){

	return number_del0cts(str.replace(",", ".").replace(" ", ""))
}

// Renvoie selon le format souhaité
export function number_format(number, decimals, dec_point, thousands_sep){

    // Strip all characters but numerical ones.
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };

    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return number_del00cts(s.join(dec));
}

// Retire 0 a l'unité des centimes si existant
function number_del0cts(monnaie){

	monnaie = monnaie.toString()

	if (monnaie.length == 0){ return monnaie }

	if (monnaie.indexOf(".") > -1){

		let cts = monnaie.split('.')

		return cts[1].substr(cts[1].length - 1) == '0'
			? monnaie.slice(0,-1)
			: monnaie
	}

	return monnaie
}

// Retire les 00 des centimes si existant
function number_del00cts(monnaie){


	monnaie = monnaie.toString()


	if (monnaie.length == 0){ return monnaie }

	if (monnaie.indexOf(",") > -1){

		let cts = monnaie.split(',')

		return cts[1] == '00'
			? monnaie.slice(0,-3)
			: monnaie
	}

	return monnaie
}
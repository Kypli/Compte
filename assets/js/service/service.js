
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

	if (str == null) {
		return ''
	}

	let number = (str + '').trim().replace(/\s/g, '')

	if (number === '') {
		return ''
	}

	const sign = number.startsWith('-') ? '-' : ''
	number = number
		.replace(/^[+-]/, '')
		.replace(/CHF|CAD|USD|EUR|GBP|JPY/gi, '')
		.replace(/[\u00a3$\u00a5]/g, '')

	if (/^\d+\u20ac\d+$/.test(number)) {
		number = number.replace('\u20ac', '.')
	} else {
		number = number.replace(/\u20ac/g, '')
	}

	number = number.replace(/'/g, '')

	const lastDot = number.lastIndexOf('.')
	const lastComma = number.lastIndexOf(',')

	if (lastDot > -1 && lastComma > -1) {
		const decimalSeparator = lastDot > lastComma ? '.' : ','
		const thousandsSeparator = decimalSeparator === '.' ? ',' : '.'
		number = number
			.replace(new RegExp(`\\${thousandsSeparator}`, 'g'), '')
			.replace(decimalSeparator, '.')
	} else if (lastComma > -1) {
		const decimals = number.length - lastComma - 1
		number = decimals > 0 && decimals <= 2
			? number.replace(',', '.')
			: number.replace(/,/g, '')
	} else if (lastDot > -1) {
		const decimals = number.length - lastDot - 1
		number = decimals === 3
			? number.replace(/\./g, '')
			: number
	}

	return number_del0cts(`${sign}${number.replace(/[^0-9.Ee]/g, '')}`)
}

// Renvoie selon le format souhaite
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

// Renvoie un montant selon le format monetaire choisi dans les preferences
export function money_display(number, format = 'comma', currency = 'EUR', trimZeros = false, showZeroDecimals = true){

    if (typeof currency === 'boolean') {
        showZeroDecimals = trimZeros
        trimZeros = currency
        currency = 'EUR'
    }

    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');

    const value = !isFinite(+number) ? 0 : +number
    const shouldTrimZeros = trimZeros || ['one_decimal', 'comma_one_decimal', 'euro_one_decimal'].includes(format)
    const legacyCurrencyByFormat = {
        us_dollar: 'USD',
        uk_pound: 'GBP',
        swiss_franc: 'CHF',
        german_euro: 'EUR'
    }
    let selectedCurrency = String(currency || 'EUR').toUpperCase()
    if (legacyCurrencyByFormat[format] && selectedCurrency === 'EUR') {
        selectedCurrency = legacyCurrencyByFormat[format]
    }
    const normalizedFormat = {
        one_decimal: 'dot',
        comma_one_decimal: 'comma',
        euro_one_decimal: 'euro_cents',
        us_dollar: 'dot',
        uk_pound: 'dot',
        swiss_franc: 'dot',
        german_euro: 'german'
    }[format] || format
    const selectedFormat = ['dot', 'comma', 'euro_cents', 'german'].includes(normalizedFormat)
        ? normalizedFormat
        : 'comma'
    const currencyConfig = getCurrencyConfig(selectedCurrency)

    if (selectedFormat === 'dot') {
        return formatFixed(value, '.', ' ', currencyConfig, shouldTrimZeros, showZeroDecimals)
    }
    if (selectedFormat === 'euro_cents') {
        return formatCurrencyCents(value, currencyConfig, shouldTrimZeros, showZeroDecimals)
    }
    if (selectedFormat === 'german') {
        return formatFixed(value, ',', '.', currencyConfig, shouldTrimZeros, showZeroDecimals)
    }

    return formatFixed(value, ',', ' ', currencyConfig, shouldTrimZeros, showZeroDecimals)
}

export function money_symbol(currency = 'EUR'){
    return getCurrencyConfig(String(currency || 'EUR').toUpperCase()).symbol
}

function getCurrencyConfig(currency){
    const currencies = {
        EUR: { symbol: '\u20ac', prefix: '', suffix: ' ', fractionDigits: 2 },
        USD: { symbol: '$', prefix: '$', suffix: '', fractionDigits: 2 },
        GBP: { symbol: '\u00a3', prefix: '\u00a3', suffix: '', fractionDigits: 2 },
        CHF: { symbol: 'CHF', prefix: 'CHF ', suffix: '', fractionDigits: 2 },
        JPY: { symbol: '\u00a5', prefix: '\u00a5', suffix: '', fractionDigits: 0 },
        CAD: { symbol: 'CA$', prefix: 'CA$', suffix: '', fractionDigits: 2 }
    }

    return currencies[currency] || currencies.EUR
}

function formatFixed(value, decimalSeparator, thousandsSeparator, currency, trimZeros = false, showZeroDecimals = true){

    const fractionDigits = currency.fractionDigits
    const roundedValue = fractionDigits === 0 ? Math.round(value) : value
    const sign = roundedValue < 0 ? '-' : ''
    const absoluteValue = Math.abs(roundedValue)
    const parts = absoluteValue.toFixed(fractionDigits).split('.')
    const units = parts[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, thousandsSeparator)
    let formatted = fractionDigits > 0 ? `${units}${decimalSeparator}${parts[1]}` : units

    if (fractionDigits > 0 && !trimZeros && !showZeroDecimals && Number(formatted.replace(decimalSeparator, '.').replace(/[^0-9+\-Ee.]/g, '')) === 0) {
        formatted = '0'
    } else if (fractionDigits > 0 && trimZeros) {
        formatted = formatted.replace(new RegExp(`\\${decimalSeparator}0+$`), '').replace(/([.,]\d*[1-9])0+$/, '$1')
    }

    return `${sign}${formatted}`
}

function formatCurrencyCents(value, currency, trimZeros = false, showZeroDecimals = true){

    const sign = value < 0 ? '-' : ''
    const symbol = currency.symbol

    if (currency.fractionDigits === 0) {
        const whole = String(Math.round(Math.abs(value))).replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ')
        return `${sign}${whole}${symbol}`
    }

    const rounded = Math.round(Math.abs(value) * 100)
    const whole = String(Math.floor(rounded / 100)).replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ')
    const centsNumber = rounded % 100
    const cents = String(centsNumber).padStart(2, '0')

    if (!trimZeros && !showZeroDecimals && rounded === 0) {
        return `${sign}0${symbol}`
    }

    if (trimZeros && centsNumber === 0) {
        return `${sign}${whole}${symbol}`
    }
    if (trimZeros && centsNumber % 10 === 0) {
        return `${sign}${whole}${symbol}${centsNumber / 10}`
    }

    return `${sign}${whole}${symbol}${cents}`
}


// Retire 0 a l'unite des centimes si existant
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

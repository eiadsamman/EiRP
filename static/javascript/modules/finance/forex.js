export default class Forex {
	defaultCurrency = 0;
	rates = [];

	constructor(hash = "") {
		fetch('_/ForexTable/json/' + hash + '/json_ForexTable.a', {
			method: 'GET',
			cache: "default", /* force-cache, reload, default */
			mode: "same-origin",
			headers: {
				'Accept': "application/json",
				"Content-Type": "application/json",
			},
		})
			.then(response => response.json())
			.then((payload) => {
				this.rates = payload;
			});
	}

	find(id) {
		let a = false;
		id = parseInt(id);
		this.rates.forEach(rate => {
			if (rate.id == id) a = rate;
		});
		return a;
	}
	exchangeRate(from, to) {
		let a = 0;
		let b = 0;
		from = parseInt(from);
		to = parseInt(to);
		this.rates.forEach(rate => {
			if (rate.id == from) a = rate.rate;
			if (rate.id == to) b = rate.rate;
		});
		if (a == 0 || b == 0) {
			return false;
		}
		return [a, b];
	}


}
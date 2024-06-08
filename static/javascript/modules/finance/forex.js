export default class Forex {
	defaultCurrency = 0;
	rates = [];

	constructor(hash = "") {
		fetch('_/ForexTable/json/' + hash + '/json_ForexTable.a', {
			method: 'GET',
			cache: "reload", /* force-cache, reload, default */
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

	sellingRates(from, to) {
		let a = 0;
		let b = 0;
		from = parseInt(from);
		to = parseInt(to);
		this.rates.forEach(rate => {
			if (rate.id == from) a = rate.rate_sell;
			if (rate.id == to) b = rate.rate_sell;
		});
		if (a == 0 || b == 0) {
			return false;
		}
		return [a, b];
	}

	buyingRates(from, to) {
		let a = 0;
		let b = 0;
		from = parseInt(from);
		to = parseInt(to);
		this.rates.forEach(rate => {
			if (rate.id == from) a = rate.rate_buy;
			if (rate.id == to) b = rate.rate_buy;
		});
		if (a == 0 || b == 0) {
			return false;
		}
		return [a, b];
	}




}
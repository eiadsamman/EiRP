import Currency from "./currency.js";

export default class Account {
	id = null;
	name = null;
	currency = null;
	company = null;
	term = null;
	category = null;

	constructor(company, id, name, currency, category, term) {
		this.company = parseInt(company);
		this.id = parseInt(id);
		this.name = name;
		this.currency = currency;
		this.category = category;
		this.term = term;
	}

}
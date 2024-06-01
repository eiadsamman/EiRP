import Currency from "./currency.js";

export default class Account {
	id = null;
	name = null;
	currency = null;
	company = null;

	constructor(company, id, name, currency) {
		this.company = parseInt(company);
		this.id = parseInt(id);
		this.name = name;
		this.currency = currency;
	}

}
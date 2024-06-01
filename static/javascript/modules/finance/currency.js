export default class Currency {
	id = null;
	name = null;
	symbol = null;
	shortname = null;
	constructor(id, name, shortname, symbol) {
		this.id = parseInt(id);
		this.name = name;
		this.shortname = shortname;
		this.symbol = symbol;
	}
}
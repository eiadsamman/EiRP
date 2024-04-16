export default class App {
	constructor() {

	}
}

export class Navigator {
	constructor(init_state, url) {
		this.history_state = init_state;
		this.history_vars = { ...init_state };
		this.url = url;
		/* this.replaceState(); */
		return this;
	}

	onPopState(callable) {
		let self = this;
		window.onpopstate = function (e) {
			if (e.state != null)
				for (const [key, value] of Object.entries(self.history_vars)) {
					if (Object.hasOwn(e.state, key)) {
						self.history_vars[key] = e.state[key];
					}
				}
			callable.call(self, e);
		};
	}

	setProperty(property, value) {
		this.history_state[property] = value;
		this.history_vars[property] = value;
	}

	getProperty(property) {
		return this.history_state[property];
	}

	getVariable(property) {
		return this.history_vars[property];
	}

	pushState() {
		window.history.pushState(this.history_vars, "", this.url + this.uriBuild());
	}

	replaceState() {
		window.history.replaceState(this.history_vars, "", this.url + this.uriBuild());
	}

	replaceVariableState() {
		window.history.replaceState(this.history_vars, "");
	}

	uriBuild() {
		let uri = "";
		let delm = "";
		let served = false;
		for (const [key, value] of Object.entries(this.history_state)) {
			if (value != null) {
				uri += delm + key + "=" + (value == null ? "" : value);
				delm = "&";
				served = true;
			}
		}
		return (served ? "/?" : "") + uri;
	}

}

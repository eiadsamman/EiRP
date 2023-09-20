class Navigator {
	constructor(init_state, url) {
		this.history_state = init_state;
		this.url = url;
		/* this.replaceState(); */
		return this;
	}

	onPopState(callable) {
		let self = this;
		window.onpopstate = function (e) {
			if (e.state != null)
				for (const [key, value] of Object.entries(self.history_state)) {
					if (Object.hasOwn(e.state, key)) {
						self.history_state[key] = e.state[key];
					}
				}
			callable.call(self);
		};
	}

	setProperty(property, value) {
		this.history_state[property] = value;
	}
	getProperty(property) {
		return this.history_state[property];
	}
	pushState() {
		history.pushState(this.history_state, "", this.url + "/?" + this.uriBuild());
	}
	replaceState() {
		window.history.replaceState(this.history_state, "", this.url + "/?" + this.uriBuild());
	}
	uriBuild() {
		let uri = "";
		let delm = "";
		for (const [key, value] of Object.entries(this.history_state)) {
			uri += delm + key + "=" + (value == null ? "" : value);
			delm = "&";
		}
		return uri;
	}

}

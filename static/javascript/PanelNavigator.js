class PanelNavigator {
	constructor() {

		this.placeholderTemplate = "<div class=\"panel-item place-holder statment-panel\" />";

		this.sourceUrl = "";
		this.itemPerRequest = 20;
		this.navigator = new Navigator({}, '');

		this.runtime = new Object();
		this.runtime.isLoading = false;
		this.runtime.isFinished = false;
		this.runtime.totalPages = 1;
		this.runtime.currentPage = 1;
		this.runtime.activeItem = null;
		this.runtime.scrollArea = document.getElementById("PanelNavigator-Scroll");
		this.runtime.container = document.getElementById("PanelNavigator-Window");
		this.runtime.informative = document.getElementById("PanelNavigator-Informative").querySelector("div");

		this.runtime.scrollArea.addEventListener("scroll", () => {
			if (this.checkAvailability() &&
				this.runtime.scrollArea.scrollHeight - this.runtime.scrollArea.scrollTop <= this.runtime.scrollArea.clientHeight * 1.2
			) {
				this.runtime.currentPage += 1;
				this.xhttp_request();
			}
		});
		return this;
	}

	onclick = function (event) {
	}

	listitemHandler = function (data) {
	}

	init = function () {
		this.xhttp_request();
		this.navigator.history_state.id = pageConfig.id;
		this.navigator.history_vars.id = pageConfig.id;
		this.navigator.history_vars.method = pageConfig.method;
		this.navigator.history_vars.url = pageConfig.url;
		this.navigator.history_vars.title = pageConfig.title;
		this.navigator.replaceVariableState();

		this.navigator.onPopState((event) => {
			this.loader(
				event.state.url,
				event.state.title,
				{ "method": event.state.method, "id": event.state.id },
				(event.state.method == "new" ? function () { initInvokers() } : null)
			);
			if (event.state.method == "view") {
				let listitem = this.runtime.scrollArea.querySelector(`.panel-item[data-listitem_id="${event.state.id}"]`);
				if (listitem) {
					this.clearActiveItem();
					this.setActiveItem(listitem);
				}
			}
		});
	}

	getFreePlaceholder = function () {
		let freePlaceholder = this.runtime.scrollArea.querySelector(".panel-item.place-holder");
		if (freePlaceholder == null) {
			return false;
		} else {
			return freePlaceholder;
		}
	}

	clearEmptyPlaceholders = function () {
		let emptyPlaceholders = this.runtime.scrollArea.querySelectorAll(".panel-item.place-holder");
		let emptyPlaceholdersCount = emptyPlaceholders.length
		emptyPlaceholders.forEach(element => {
			element.remove();
		});
		return emptyPlaceholdersCount;
	}

	xhttp_request = async () => {
		if (this.runtime.isFinished) {
			this.clearEmptyPlaceholders();
			return;
		}
		this.runtime.informative.innerText = "Loading...";
		this.generatePlaceholders();

		let response = await fetch(this.sourceUrl, {
			method: 'POST',
			mode: "cors",
			cache: "no-cache",
			credentials: "same-origin",
			referrerPolicy: "no-referrer",
			headers: {
				"X-Requested-With": "fetch",
				"Application-From": "same",
				'Accept': 'application/json',
				"Content-Type": "application/json",
			},
			body: JSON.stringify({ ...{ "method": "fetch", "page": this.runtime.currentPage } })
		});


		if (response.ok) {
			const payload = await response.json()
			this.runtime.totalPages = parseInt(payload.headers.pages);
			payload.contents.forEach(element => {
				let freeObject = this.getFreePlaceholder();
				if (freeObject) {
					freeObject.classList.remove("place-holder");
					this.buildItem(freeObject, element);
				}
			});
			this.assigneClickEvents();
			this.runtime.isFinished = !this.checkAvailability() && this.clearEmptyPlaceholders() > 0;
			if (this.runtime.isFinished) {
				this.runtime.informative.innerText = "No more records";
				return;
			}
			if (this.checkAvailability() && (this.runtime.scrollArea.scrollHeight <= this.runtime.scrollArea.clientHeight)) {
				this.runtime.currentPage += 1;
				this.xhttp_request();
			}
		}
		this.runtime.isLoading = false;

	}

	generatePlaceholders = function () {
		if (this.runtime.isLoading) return;
		this.runtime.isLoading = true;
		for (let i = 0; i < this.itemPerRequest; i++) {
			this.runtime.container.innerHTML += this.placeholderTemplate;
		}
	}

	setActiveItem = function (item) {
		item.classList.add("active");
		this.runtime.activeItem = item;
	}
	clearActiveItem = function () {
		if (this.runtime.activeItem != null) {
			this.runtime.scrollArea.querySelectorAll(".panel-item.active").forEach(element => {
				element.classList.remove("active");
			});
			this.runtime.activeItem = null;
		}
	}
	assigneClickEvents = function () {
		let instance = this;
		let listitems = this.runtime.scrollArea.querySelectorAll(".panel-item:not(.place-holder)");
		listitems.forEach(element => {
			element.addEventListener("click", function () {
				if (instance.runtime.activeItem != null) {
					if (instance.runtime.activeItem.dataset.listitem_id === this.dataset.listitem_id)
						return;
					instance.clearActiveItem()
				}
				instance.navigator.history_vars.method = "view";
				instance.setActiveItem(this);
				instance.onclick(this);
			});
		});
	}

	buildItem = function (obj, data = {}) {
		obj.dataset.listitem_id = data.id;
		obj.innerHTML = this.listitemHandler(data);
	}

	checkAvailability = function () {
		return (this.runtime.currentPage < this.runtime.totalPages);
	}

	loader = function (url, title, options = {}, callback) {
		var formData = new FormData();
		for (var key in options) {
			formData.append(key, options[key]);
		}
		fetch(url, {
			method: 'POST',
			mode: "cors",
			cache: "no-cache",
			credentials: "same-origin",
			referrerPolicy: "no-referrer",
			headers: {
				"X-Requested-With": "fetch",
			},
			body: formData
		}).then(response => {
			if (response.ok) {
				return response.text();
			}
			return Promise.reject(response);
		}).then(body => {
			document.title = title;
			document.getElementById("PanelNavigator-Body").innerHTML = (body);

			if (typeof callback === "function") {
				callback.call(this);
			}
		}).catch(response => {
			messagesys.failure("Server response `" + response.statusText + "`");
		})
	}
}

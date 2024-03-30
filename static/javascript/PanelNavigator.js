class PanelNavigator {
	constructor() {
		this.sourceUrl = "";
		this.onClickUrl = "";
		this.itemPerRequest = 20;
		this.navigator = new Navigator({}, '');
		this.classList = [];

		this.runtime = new Object();
		this.runtime.isLoading = false;
		this.runtime.isFinished = false;
		this.runtime.isContentLoading = false;
		this.runtime.totalPages = 1;
		this.runtime.currentPage = 1;
		this.runtime.activeItem = null;
		this.runtime.scrollArea = document.getElementById("PanelNavigator-Scroll");
		this.runtime.container = document.getElementById("PanelNavigator-Window");
		this.runtime.loadingScreen = document.getElementById("PanelNavigator-LoadingScreen");
		this.runtime.outputScreen = document.getElementById("PanelNavigator-Body");
		this.runtime.informative = document.getElementById("PanelNavigator-Informative").querySelector("div");

		this.latency = null;
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

	onclick = function () {
	}

	listitemHandler = function () {
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

		this.runtime.scrollArea.tabIndex = 0;
		this.runtime.scrollArea.autofocus = true;
		this.runtime.scrollArea.addEventListener("keydown", (e) => {
			if (e.key == "ArrowDown") {
				e.preventDefault();
				if (this.runtime.activeItem != null && !this.runtime.isContentLoading) {
					let listitem = this.runtime.scrollArea.querySelector(`.panel-item[data-listitem_id="${this.runtime.activeItem.dataset.listitem_id}"]`);
					if (listitem) {
						let nextSibling = listitem.nextSibling;
						if (nextSibling && nextSibling.dataset != undefined && nextSibling.dataset.listitem_id != undefined) {
							this.clearActiveItem()
							this.navigator.history_vars.method = "view";
							this.setActiveItem(nextSibling);
							this.onclick(nextSibling);
							nextSibling.scrollIntoView({ behavior: "smooth", block: "nearest" });
						}
					}
				}
				return false;
			} else if (e.key == "ArrowUp") {
				e.preventDefault();
				if (this.runtime.activeItem != null && !this.runtime.isContentLoading) {
					let listitem = this.runtime.scrollArea.querySelector(`.panel-item[data-listitem_id="${this.runtime.activeItem.dataset.listitem_id}"]`);
					if (listitem) {
						let previousSibling = listitem.previousSibling;
						if (previousSibling && previousSibling.dataset != undefined && previousSibling.dataset.listitem_id != undefined) {
							this.clearActiveItem()
							this.navigator.history_vars.method = "view";
							this.setActiveItem(previousSibling);
							this.onclick(previousSibling);
							previousSibling.scrollIntoView({ behavior: "smooth", block: "nearest" });
						}
					}
				}
				return false;
			} else if (e.key == "Enter") {
				e.preventDefault()
				let activeElement = document.activeElement;
				if (activeElement.classList.contains("panel-item")) {
					this.clearActiveItem()
					this.navigator.history_vars.method = "view";
					this.setActiveItem(activeElement);
					this.onclick(activeElement);
					activeElement.scrollIntoView({ behavior: "smooth", block: "nearest" });
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
			const payload = await response.json();
			this.runtime.totalPages = parseInt(payload.headers.pages);
			if (document.getElementById("PanelNavigator-TotalRecords"))
				document.getElementById("PanelNavigator-TotalRecords").innerText = payload.headers.count + " records"
			payload.contents.forEach(element => {
				let freePlaceholder = this.getFreePlaceholder();
				if (freePlaceholder) {
					freePlaceholder.classList.remove("place-holder");
					this.buildItem(freePlaceholder, element);
					this.assigneEvents(freePlaceholder);
				}
			});
			this.runtime.isFinished = !this.checkAvailability() && this.clearEmptyPlaceholders() > 0;
			if (this.runtime.isFinished) {
				this.runtime.informative.innerText = "No more records";
				return;
			}
			/* if (this.checkAvailability() &&
				this.runtime.scrollArea.scrollHeight > 0 &&
				this.runtime.scrollArea.clientHeight > 0 &&
				(this.runtime.scrollArea.scrollHeight <= this.runtime.scrollArea.clientHeight)) {
				this.runtime.currentPage += 1;
				this.xhttp_request();
			} */
			this.runtime.isLoading = false;
		}
	}

	generatePlaceholders = function () {
		if (this.runtime.isLoading) return;
		this.runtime.isLoading = true;
		for (let i = 0; i < this.itemPerRequest; i++) {
			var content = document.createElement("a");
			content.classList.add("panel-item")
			content.classList.add("place-holder")
			content.classList.add(...this.classList);
			this.runtime.container.append(content);
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

	assigneEvents = function (element) {
		let instance = this;
		element.addEventListener("click", function (e) {
			e.preventDefault();
			if (instance.runtime.activeItem != null) {
				if (instance.runtime.activeItem.dataset.listitem_id === this.dataset.listitem_id)
					return;
				instance.clearActiveItem()
			}
			instance.navigator.history_vars.method = "view";
			instance.setActiveItem(this);
			instance.onclick(this);
			return false;
		});
	}

	buildItem = function (obj, data = {}) {
		obj.dataset.listitem_id = data.id;
		obj.innerHTML = (this.listitemHandler(data));
		obj.href = this.onClickUrl + "/?id=" + data.id;
	}

	checkAvailability = function () {
		return (this.runtime.currentPage < this.runtime.totalPages);
	}



	loader = function (url, title, options = {}, callback) {
		this.runtime.isContentLoading = true;
		var formData = new FormData();

		this.runtime.outputScreen.classList.add("busy");
		this.latency = setTimeout(() => {
			this.runtime.outputScreen.innerHTML = this.runtime.loadingScreen.innerHTML;
		}, 500);

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
			this.runtime.isContentLoading = false;
			if (response.ok) {
				return response.text();
			}
			return Promise.reject(response);
		}).then(body => {
			if (this.latency) {
				clearTimeout(this.latency);
			}
			this.runtime.outputScreen.classList.remove("busy");
			this.runtime.isContentLoading = false;
			if (title != null)
				document.title = title;
			this.runtime.outputScreen.innerHTML = body;

			if (typeof callback === "function") {
				callback.call(this);
			}
		}).catch(response => {
			this.runtime.isContentLoading = false;
			messagesys.failure("Server response `" + response.statusText + "`");
		})
	}
}

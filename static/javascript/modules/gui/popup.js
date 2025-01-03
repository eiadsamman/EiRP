class Modals extends EventTarget {
	static queue = [];
	static isRaised = false;

	isOpen = false;
	controlContainer;
	controlContent;
	#sourceRequestEventDetail = {};
	#eventClose = new CustomEvent("close");
	#eventShow = new CustomEvent("show");

	constructor() { super(); }
	static raiseEvents() {
		if (Modals.isRaised) return;
		Modals.isRaised = true;
		document.addEventListener("keydown", (e) => {
			if (e.key == "Escape") {
				const queueObject = Modals.getObject();
				if (queueObject) {
					queueObject.close();
				}
			}
		});
	}
	static getObject() {
		if (Modals.queue.length > 0) {
			const que = Modals.queue.pop();
			if (que.isOpen) {
				return que;
			}
			Modals.getObject();
		}
		return false;
	}
	static add(object) {
		Modals.queue.push(object);
	}

	controller() {
		return this.controlContainer;
	}
	content(data) {
		this.controlContent.innerHTML = data;
		return this;
	}

	contentForm(header = {}, data = "") {
		header.title = header.title ?? "";
		header.submitButton = header.submitButton == true ? "<cite><button data-role=\"submit\" type=\"submit\"></button></cite>" : "";
		this.controlContent.innerHTML = `
			<div class="gremium"><div class="content">
				<header style="position:sticky; top: calc(0px);">
					<a href="#" class="previous" data-role="previous">&nbsp;</a>
					<h1>${header.title}</h1>${header.submitButton}
				</header>
				<article style="width:auto;">
					${data}
				</article>
			</div></div>`;
		//<cite><button data-role="submit" type="submit" id="jQsubmit"></button></cite>
		return this;
	}

	destroy() {
		this.isOpen = null;
		setTimeout(() => {
			this.controlContent.remove();
			this.controlContainer.remove();
		}, 1000);
	}

	dispatchSubmitEvent() {
		this.controlContent.addEventListener("submit", (e) => {
			e.preventDefault();
			this.dispatchEvent(new CustomEvent("submit", {
				detail: this.#sourceRequestEventDetail,
			}));
			return false;
		});
	}
	height(height) {
		this.controlContent.style.height = height;
	}
	show(eventData = {}) {
		this.#sourceRequestEventDetail = eventData;
		this.isOpen = true;
		let controlPreviousBtn = this.controlContent.querySelectorAll("[data-role=\"previous\"]");
		if (controlPreviousBtn) {
			controlPreviousBtn.forEach((e) => {
				e.addEventListener("click", (e) => {
					e.preventDefault();
					this.close();
					return false;
				})
			});
		}
		let autoFocus = this.controlContent.querySelector("[autofocus]");
		if (autoFocus) {
			autoFocus.focus();
			autoFocus.select();
		}

		Modals.add(this);
		this.dispatchEvent(this.#eventShow);
	}
	close() {
		this.isOpen = false;
		this.dispatchEvent(this.#eventClose);
	}

}

class Dialog extends Modals {
	constructor(elementId = null) {
		super();
		if (elementId == null) {
			this.controlContainer = document.createElement("dialog");
			document.body.appendChild(this.controlContainer);
		} else {
			this.controlContainer = document.getElementById(elementId);
			this.controlContainer.classList.add("appHtmlDialog");
			this.controlContent = this.controlContainer.querySelector("div");
		}
		this.controlContainer.classList.add("appHtmlDialog");
		Modals.raiseEvents();
		return this;
	}
	show(eventData = {}) {
		this.controlContainer.showModal();
		super.show(eventData);
		return this;
	}
	close() {
		super.close();
		this.controlContainer.close();
		return this;
	}
}

class Popup extends Modals {
	constructor(elementId = null) {
		super();
		this.controlContainer = document.createElement("span");
		this.controlContainer.classList.add("appHtmlPopup");
		document.body.appendChild(this.controlContainer);

		if (elementId == null) {
			this.controlContent = document.createElement("form");
			this.controlContainer.appendChild(this.controlContent);

		} else {
			this.controlContent = document.getElementById(elementId);
			if (this.controlContent == null || this.controlContent == undefined) {
				this.controlContent = document.createElement("form");
			}
			this.controlContent.style.display = "block";
			this.controlContainer.appendChild(this.controlContent);
		}

		if (this.controlContent.tagName == "FORM") {
			super.dispatchSubmitEvent();
		}
		Modals.raiseEvents();
		return this;
	}


	show(eventData = {}) {
		this.controlContainer.setAttribute("open", null);
		super.show(eventData);
		return this;
	}
	close() {
		super.close();
		this.controlContainer.removeAttribute("open");
		return this;
	}
}

export { Popup, Dialog }
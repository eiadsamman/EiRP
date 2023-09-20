class Navigator {
	constructor(initState) {
		this.historyState = initState;
	}
	uriBuild() {
		let uri = "";
		let delm = "";
		for (const [key, value] of Object.entries(this.historyState)) {
			uri += delm + key + "=" + (value == null ? "" : value);
			delm = "&";
		}
		return uri;
	}
}
const navigator = new Navigator({
	"group": "test",
	"view": "mex",
	"auth": "cornflakes",
});


let url = "/" + navigator.uribuild();



navigator.historyState.view = "1";
history.pushState(navigator.historyState, "title", "dir" + "/?" + navigator.uribuild());



navigator.historyState.view = e.state.view;
switch (navigator.historyState.view) {
	case "0":
		break;
	case "1":
		break;
	case "2":
		break;
}
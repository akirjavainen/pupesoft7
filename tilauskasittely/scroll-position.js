"use strict";

// ----------------------------------------------------------------------------------------
// Keeps the reader's place across a form post.
//
// Pupesoft's order and quote editing posts the whole page and draws it again for every
// change: a price, a quantity, one row added. The browser then puts the new page at the top,
// so editing a quote with forty rows means scrolling back down forty times. Nothing here
// changes how the page is built; it only remembers where the page was and puts it back.
//
// Two things are remembered, and the better one wins:
//
//   The field that was being used. If it is still there after the reload it is placed back at
//   the same height on the screen. This survives the page growing or shrinking above it,
//   which is what happens when a row is added or a message appears -- a remembered pixel
//   offset would be wrong by exactly the height of the new row, which is the one case that
//   matters most.
//
//   The scroll offset, as a fallback for when the field is gone: after deleting a row, or on
//   a page whose fields have no names.
//
// EDITING A ROW is a round trip and is treated as one. Muokkaa reloads the page with that row
// opened for editing in the entry form at the top, which is where the work now is, so the
// place is NOT given back on that page -- it is held. It is handed back on the page after
// that, when Lisää has put the row away and the list is on screen again. Anything posted in
// between leaves the held place alone, so the list comes back where it was before Muokkaa was
// pressed rather than at the top of the edit form.
//
// Include after the page's own scripts. No dependencies, no build step:
//   <script src="scroll-position.js"></script>
// ----------------------------------------------------------------------------------------

(function (global) {

	var doc = global.document;
	var store = null;

	// A locked-down browser, or private mode in older Safari, throws on access rather than on
	// use. Without it the page simply behaves as it did before.
	try {
		store = global.sessionStorage;
		store.setItem("scroll_position_test", "1");
		store.removeItem("scroll_position_test");
	} catch (e) {
		return;
	}

	var PREFIX = "jwio_scroll:";

	// Older than this and the page is not the one that was left; going back to a document from
	// this morning and being dropped halfway down it is worse than starting at the top.
	var MAX_AGE_MS = 30 * 60 * 1000;

	// Never restore a position that would leave the page looking untouched at the top anyway:
	var MIN_OFFSET = 40;

	// A form that opens something for editing further up the page, and is expected to bring the
	// reader back where they were once they are done with it. Either the form itself or
	// anything inside it may match. tilaus_myynti.php's Muokkaa posts both of these; one is
	// enough, and the pair means a rename of either still leaves this working.
	//
	// Add to this list if another button turns out to work the same way.
	var HOLD_SELECTORS = [
		"form[name='muokkaa']",
		"input[name='tapa'][value='MUOKKAA']"
	];

	// A submit writes the place, and the pagehide that follows it a moment later would write it
	// again -- by which time the focus has moved to the button that was pressed, and the better
	// of the two records would be lost. Anything this recent is left alone.
	var SUBMIT_WINS_MS = 1000;


	// ----------------------------------------------------------------------------------------
	// Which document this is. The path alone is not enough -- every order is edited through the
	// same script -- so the identifiers Pupesoft carries in the URL are part of the key. Without
	// them, opening order B would restore order A's position.
	// ----------------------------------------------------------------------------------------
	function getKey() {
		var params = new URLSearchParams(global.location.search);
		var parts = [global.location.pathname];
		var names = ["tunnus", "otunnus", "tilausnumero", "laskunro", "toim", "tila"];

		names.forEach(function (name) {
			var value = params.get(name);

			if (value) parts.push(name + "=" + value);
		});

		// Forms post their identifiers in hidden fields rather than in the URL, so those are
		// worth as much as the query string:
		if (parts.length === 1) {
			names.forEach(function (name) {
				var field = doc.querySelector("input[name='" + name + "']");

				if (field && field.value) parts.push(name + "=" + field.value);
			});
		}

		return PREFIX + parts.join("&");
	}


	// ----------------------------------------------------------------------------------------
	function read() {
		try {
			return JSON.parse(store.getItem(getKey()) || "null");
		} catch (e) {
			return null;
		}
	}

	function write(state) {
		try {
			store.setItem(getKey(), JSON.stringify(state));
		} catch (e) {
			// Storage full. Not a reason to stop the form being sent.
		}
	}

	function forget() {
		try {
			store.removeItem(getKey());
		} catch (e) {
			// Nothing to do about it.
		}
	}


	// ----------------------------------------------------------------------------------------
	// How to find this field again on the next page. Pupesoft names its row fields after the
	// row, so the name survives a redraw even though the element does not.
	// ----------------------------------------------------------------------------------------
	function getSelector(element) {
		if (!element || !element.tagName) return "";

		var tag = element.tagName.toLowerCase();

		if (tag !== "input" && tag !== "select" && tag !== "textarea") return "";

		if (element.id) return "#" + global.CSS.escape(element.id);
		if (element.name) return tag + "[name='" + element.name.replace(/'/g, "\\'") + "']";

		return "";
	}


	// ----------------------------------------------------------------------------------------
	function isHoldingForm(form) {
		if (!form || form.tagName !== "FORM") return false;

		return HOLD_SELECTORS.some(function (selector) {
			try {
				return form.matches(selector) || form.querySelector(selector) !== null;
			} catch (e) {
				return false;
			}
		});
	}


	// ----------------------------------------------------------------------------------------
	function save(event) {
		var previous = read();
		var form = (event && event.target && event.target.tagName === "FORM")
			? event.target
			: null;

		// A round trip is under way: whatever is posted while the row is open for editing must
		// not move the place that is being kept for afterwards.
		if (previous && previous.hold && !isHoldingForm(form)) return;

		// The submit that has just written this record is a better witness than the pagehide
		// coming along behind it.
		if (previous && !isHoldingForm(form)
			&& (Date.now() - previous.time) < SUBMIT_WINS_MS) return;

		var active = doc.activeElement;
		var selector = getSelector(active);
		var state = {
			y: global.scrollY || global.pageYOffset || 0,

			// Where on the screen the field sat, so it can be put back at the same height
			// instead of jumping to the top of the window:
			offset: selector ? active.getBoundingClientRect().top : 0,
			selector: selector,

			// hold says a round trip is running and lasts until the place is handed back; skip
			// counts the pages that go by without handing it back. They are two different
			// things: skip is already back to zero while the row sits open for editing, and
			// pressing Muokkaa again from there must still keep the place the first one saved.
			hold: false,
			skip: 0,
			time: Date.now()
		};

		if (isHoldingForm(form)) {
			// Pressing Muokkaa a second time, from the page the first one opened, must still
			// come back to where the list was before any of it started.
			if (previous && previous.hold) {
				state.y = previous.y;
				state.selector = previous.selector;
				state.offset = previous.offset;
				state.time = previous.time;
			}

			// One page -- the one with the row opened for editing -- goes by without the place
			// being handed back.
			state.hold = true;
			state.skip = 1;
		}
		else if (state.y < MIN_OFFSET && !selector) {
			return;
		}

		write(state);
	}


	// ----------------------------------------------------------------------------------------
	function restore() {
		var state = read();

		if (!state) return;

		if ((Date.now() - state.time) > MAX_AGE_MS) {
			forget();
			return;
		}

		// The row is open for editing further up the page. That is where the work is now, so
		// the page is left where the browser put it and the place is kept for afterwards.
		if (state.skip > 0) {
			state.skip -= 1;
			write(state);
			return;
		}

		// Once only, then the note is thrown away. It is written again the moment the page is
		// left, so leaving and coming back to the same order lands where it was left -- but a
		// position is never handed out twice, and never to a document nobody left.
		forget();

		var target = state.selector ? doc.querySelector(state.selector) : null;

		if (target) {
			// Back to the same height on the screen, whatever has appeared above it:
			var y = target.getBoundingClientRect().top + (global.scrollY || 0) - state.offset;

			global.scrollTo(0, Math.max(0, Math.round(y)));

			// Focus, but deliberately without selecting the contents: the next keystroke would
			// then replace a price instead of continuing it.
			if (!target.disabled && !target.readOnly && target.offsetParent !== null) {
				target.focus({ preventScroll: true });
			}
			return;
		}

		if (state.y >= MIN_OFFSET) global.scrollTo(0, state.y);
	}


	// ----------------------------------------------------------------------------------------
	// The browser's own restoration works from its history and fights with this one on back and
	// forward. This script knows more than it does, so it is asked to stand aside.
	// ----------------------------------------------------------------------------------------
	if ("scrollRestoration" in global.history) {
		global.history.scrollRestoration = "manual";
	}

	// Submit catches the ordinary case. It is listened for on the document during the capture
	// phase so that it is seen even when a page's own handler stops the event, and so that no
	// form has to be changed to opt in.
	doc.addEventListener("submit", save, true);

	// A button that posts through JavaScript never fires submit, and neither does a link that
	// leads back into the same page. pagehide covers both, and unlike unload it does not stop
	// the browser from putting the page in its back-forward cache.
	global.addEventListener("pagehide", save);

	if (doc.readyState === "loading") {
		doc.addEventListener("DOMContentLoaded", restore);
	} else {
		restore();
	}

})(window);

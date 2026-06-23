// Shim for TrongateCodeGenerator — provides the functions that
// mothership views reference in mx-after-swap attributes.
window.TrongateCodeGenerator = {
	focusOnInput: function () {
		// Remove cloak from any hidden content
		document.querySelectorAll('.center-stage.cloak')
			.forEach(function (el) { el.classList.remove('cloak'); });
		// Focus the first text input
		var targetEl = document.querySelector('main > .center-stage input[type=text]');
		if (targetEl) { targetEl.focus(); }
	},

	handleAfterMx: function () {
		// Remove cloak from any hidden content
		document.querySelectorAll('.center-stage.cloak')
			.forEach(function (el) { el.classList.remove('cloak'); });
		// Focus the first text input in main
		var targetEl = document.querySelector('main input[type=text]');
		if (targetEl) { targetEl.focus(); }
	}
};

function focusOnInput() {
		document.querySelectorAll('.center-stage.cloak')
			.forEach(function (el) { el.classList.remove('cloak'); });
		var targetEl = document.querySelector('main > .center-stage input[type=text]');
		if (targetEl) { targetEl.focus(); }
	}

	function doReset() {
	var frame = document.querySelector('.blue-frame');
	var main = document.querySelector('main');

	if (!frame || !main) return;

	// 1. Greyscale the entire modal — Atari-style reset
	frame.classList.add('greyscale');

	// 2. Show blinking reset text
	main.innerHTML = '<div class="center-stage mt-3"><span class="blink">~ Resetting ~</span></div>';

	// 3. After 1.2 seconds, restore colour and load home menu
	var baseUrl = document.querySelector('base').getAttribute('href');

	setTimeout(function () {
		frame.classList.remove('greyscale');

		fetch(baseUrl + 'trongate_control-evo/home')
			.then(function (r) { return r.text(); })
			.then(function (html) {
				main.innerHTML = html;
				TrongateCodeGenerator.focusOnInput();
			});

		// Clear server-side wizard state
		fetch(baseUrl + 'trongate_control-evo/reset', { method: 'POST' });
	}, 1200);
}
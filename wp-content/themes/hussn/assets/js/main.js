/**
 * Hussn theme front-end behaviour.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var nav = document.getElementById('site-navigation');
		if (!nav) {
			return;
		}

		var toggle = nav.querySelector('.menu-toggle');
		var menu = nav.querySelector('#primary-menu');
		if (!toggle || !menu) {
			return;
		}

		toggle.addEventListener('click', function () {
			var expanded = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', String(!expanded));
			nav.classList.toggle('is-open');
		});

		// Close the menu when a link is followed (mobile).
		menu.addEventListener('click', function (event) {
			if (event.target.tagName === 'A' && nav.classList.contains('is-open')) {
				toggle.setAttribute('aria-expanded', 'false');
				nav.classList.remove('is-open');
			}
		});
	});
})();

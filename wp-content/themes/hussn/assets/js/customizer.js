/**
 * Live-preview wiring for the Customizer.
 */
(function ($) {
	'use strict';

	// Site title.
	wp.customize('blogname', function (value) {
		value.bind(function (to) {
			$('.site-title a').text(to);
		});
	});

	// Site description.
	wp.customize('blogdescription', function (value) {
		value.bind(function (to) {
			$('.site-description').text(to);
		});
	});

	// Accent color.
	wp.customize('hussn_accent_color', function (value) {
		value.bind(function (to) {
			document.documentElement.style.setProperty('--color-primary', to);
		});
	});
})(jQuery);

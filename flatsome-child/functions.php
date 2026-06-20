<?php
/**
 * Flatsome Child theme functions.
 *
 * Add custom PHP (hooks, filters, snippets) here. This file is loaded in
 * addition to the parent theme's functions.php, so the whole Flatsome API is
 * available.
 *
 * @package Flatsome_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the child theme stylesheet.
 *
 * Flatsome loads its own CSS through its asset pipeline, so we only need to
 * register this child's style.css (where custom CSS lives).
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'flatsome-child-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}, 100 );

/**
 * SAMPLE: render a small "Flatsome Child active" badge in the footer.
 *
 * This pairs with the .flatsome-child-active-badge rule in style.css to prove
 * the child theme's PHP and CSS are both live. Delete this block (and the
 * matching CSS) once you've confirmed the setup.
 */
add_action( 'wp_footer', function () {
	echo '<div class="flatsome-child-active-badge">Flatsome Child active</div>';
} );

/*
 * ---------------------------------------------------------------------------
 * Add your customizations below.
 * ---------------------------------------------------------------------------
 *
 * Example — register a custom Flatsome footer payment icon (see the Flatsome
 * "Payment Icons" docs). Drop an SVG at
 * assets/img/payment-icons/icon-<key>.svg.php inside THIS child theme, then:
 *
 * add_filter( 'flatsome_payment_icons', function ( $icons ) {
 *     $icons['mycustomicon'] = 'My Custom Icon'; // key => backend label
 *     return $icons;
 * } );
 */

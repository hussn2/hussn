<?php
/**
 * Hussn theme functions and definitions.
 *
 * @package Hussn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

if ( ! defined( 'HUSSN_VERSION' ) ) {
	define( 'HUSSN_VERSION', '1.0.0' );
}

/**
 * Core theme supports and registrations.
 */
function hussn_setup() {
	// Let WordPress manage the document title.
	add_theme_support( 'title-tag' );

	// Featured images.
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'hussn-card', 720, 480, true );

	// Custom logo.
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 64,
			'width'       => 220,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	// HTML5 markup.
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	// Block editor goodies.
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/main.css' );

	// Custom color palette available in the block editor.
	add_theme_support(
		'editor-color-palette',
		array(
			array(
				'name'  => __( 'Primary', 'hussn' ),
				'slug'  => 'primary',
				'color' => '#2563eb',
			),
			array(
				'name'  => __( 'Ink', 'hussn' ),
				'slug'  => 'ink',
				'color' => '#0f172a',
			),
			array(
				'name'  => __( 'Muted', 'hussn' ),
				'slug'  => 'muted',
				'color' => '#64748b',
			),
			array(
				'name'  => __( 'Surface', 'hussn' ),
				'slug'  => 'surface',
				'color' => '#f8fafc',
			),
			array(
				'name'  => __( 'White', 'hussn' ),
				'slug'  => 'white',
				'color' => '#ffffff',
			),
		)
	);

	// Navigation menus.
	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'hussn' ),
			'footer'  => __( 'Footer Menu', 'hussn' ),
		)
	);

	// Translation ready.
	load_theme_textdomain( 'hussn', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'hussn_setup' );

/**
 * Set the content width in pixels.
 */
function hussn_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'hussn_content_width', 1140 );
}
add_action( 'after_setup_theme', 'hussn_content_width', 0 );

/**
 * Enqueue styles and scripts.
 */
function hussn_assets() {
	// Required theme stylesheet (carries the header).
	wp_enqueue_style( 'hussn-style', get_stylesheet_uri(), array(), HUSSN_VERSION );

	// Main presentation stylesheet.
	wp_enqueue_style(
		'hussn-main',
		get_template_directory_uri() . '/assets/css/main.css',
		array( 'hussn-style' ),
		HUSSN_VERSION
	);

	// Front-end behaviour (mobile nav toggle, etc.).
	wp_enqueue_script(
		'hussn-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		HUSSN_VERSION,
		true
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'hussn_assets' );

/**
 * Register widget areas.
 */
function hussn_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'hussn' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Widgets shown in the right sidebar.', 'hussn' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);

	for ( $i = 1; $i <= 3; $i++ ) {
		register_sidebar(
			array(
				/* translators: %d: footer column number. */
				'name'          => sprintf( __( 'Footer %d', 'hussn' ), $i ),
				'id'            => 'footer-' . $i,
				'description'   => __( 'Widgets shown in the footer.', 'hussn' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}
}
add_action( 'widgets_init', 'hussn_widgets_init' );

/**
 * Fallback menu when no primary menu is assigned.
 */
function hussn_primary_menu_fallback() {
	echo '<ul id="primary-menu" class="nav-menu">';
	wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
		)
	);
	echo '</ul>';
}

require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/customizer.php';

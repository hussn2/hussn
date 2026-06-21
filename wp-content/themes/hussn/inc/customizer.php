<?php
/**
 * Theme Customizer settings — drives the front-page hero and accent color.
 *
 * @package Hussn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
 */
function hussn_customize_register( $wp_customize ) {
	// Live-preview the site title and description.
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';

	// --- Hero section -----------------------------------------------------
	$wp_customize->add_section(
		'hussn_hero',
		array(
			'title'       => __( 'Front Page Hero', 'hussn' ),
			'priority'    => 30,
			'description' => __( 'Customize the hero shown on the front page.', 'hussn' ),
		)
	);

	$hussn_hero_fields = array(
		'hussn_hero_eyebrow'   => array(
			'default' => __( 'Welcome', 'hussn' ),
			'label'   => __( 'Eyebrow text', 'hussn' ),
			'type'    => 'text',
		),
		'hussn_hero_title'     => array(
			'default' => __( 'Build something people love.', 'hussn' ),
			'label'   => __( 'Hero title', 'hussn' ),
			'type'    => 'text',
		),
		'hussn_hero_subtitle'  => array(
			'default' => __( 'A clean, fast, and flexible WordPress theme to launch your next idea in minutes.', 'hussn' ),
			'label'   => __( 'Hero subtitle', 'hussn' ),
			'type'    => 'textarea',
		),
		'hussn_hero_cta_label' => array(
			'default' => __( 'Get started', 'hussn' ),
			'label'   => __( 'Button label', 'hussn' ),
			'type'    => 'text',
		),
		'hussn_hero_cta_url'   => array(
			'default' => '#features',
			'label'   => __( 'Button URL', 'hussn' ),
			'type'    => 'url',
		),
	);

	foreach ( $hussn_hero_fields as $hussn_id => $hussn_field ) {
		$wp_customize->add_setting(
			$hussn_id,
			array(
				'default'           => $hussn_field['default'],
				'sanitize_callback' => ( 'url' === $hussn_field['type'] ) ? 'esc_url_raw' : 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$hussn_id,
			array(
				'label'   => $hussn_field['label'],
				'section' => 'hussn_hero',
				'type'    => $hussn_field['type'],
			)
		);
	}

	// --- Accent color -----------------------------------------------------
	$wp_customize->add_setting(
		'hussn_accent_color',
		array(
			'default'           => '#2563eb',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'hussn_accent_color',
			array(
				'label'   => __( 'Accent color', 'hussn' ),
				'section' => 'colors',
			)
		)
	);
}
add_action( 'customize_register', 'hussn_customize_register' );

/**
 * Output the chosen accent color as a CSS variable override.
 */
function hussn_customizer_css() {
	$accent = get_theme_mod( 'hussn_accent_color', '#2563eb' );
	if ( '#2563eb' === $accent ) {
		return; // Default already in the stylesheet.
	}
	printf(
		'<style id="hussn-customizer-css">:root{--color-primary:%s;}</style>',
		esc_attr( $accent )
	);
}
add_action( 'wp_head', 'hussn_customizer_css' );

/**
 * Enqueue the Customizer live-preview script.
 */
function hussn_customize_preview_js() {
	wp_enqueue_script(
		'hussn-customizer',
		get_template_directory_uri() . '/assets/js/customizer.js',
		array( 'customize-preview' ),
		HUSSN_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'hussn_customize_preview_js' );

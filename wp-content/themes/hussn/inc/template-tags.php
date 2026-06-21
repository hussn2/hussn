<?php
/**
 * Custom template tags for this theme.
 *
 * @package Hussn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hussn_post_meta' ) ) :
	/**
	 * Print HTML with meta information for the current post (date and author).
	 */
	function hussn_post_meta() {
		if ( 'post' !== get_post_type() ) {
			return;
		}

		$time_string = sprintf(
			'<time class="entry-date published" datetime="%1$s">%2$s</time>',
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( get_the_date() )
		);

		printf(
			'<div class="entry-meta"><span class="posted-on">%1$s</span><span class="byline"> %2$s <a href="%3$s">%4$s</a></span></div>',
			$time_string, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
			esc_html__( 'by', 'hussn' ),
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			esc_html( get_the_author() )
		);
	}
endif;

if ( ! function_exists( 'hussn_post_taxonomies' ) ) :
	/**
	 * Print the categories and tags for the current post.
	 */
	function hussn_post_taxonomies() {
		if ( 'post' !== get_post_type() ) {
			return;
		}

		$categories_list = get_the_category_list( ', ' );
		if ( $categories_list ) {
			printf(
				'<span class="cat-links">%1$s %2$s</span>',
				esc_html__( 'Posted in', 'hussn' ),
				wp_kses_post( $categories_list )
			);
		}

		$tags_list = get_the_tag_list( '', ', ' );
		if ( $tags_list ) {
			printf(
				'<span class="tags-links">%1$s %2$s</span>',
				esc_html__( 'Tagged', 'hussn' ),
				wp_kses_post( $tags_list )
			);
		}
	}
endif;

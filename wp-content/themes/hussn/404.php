<?php
/**
 * The template for displaying 404 (not found) pages.
 *
 * @package Hussn
 */

get_header();
?>

<div class="container content-area">
	<main id="primary" class="site-main">
		<section class="error-404 not-found">
			<header class="page-header">
				<h1 class="page-title"><?php esc_html_e( '404', 'hussn' ); ?></h1>
				<p class="error-404__lead"><?php esc_html_e( 'That page could not be found.', 'hussn' ); ?></p>
			</header>

			<div class="page-content">
				<p><?php esc_html_e( 'It might have been moved or deleted. Try a search instead:', 'hussn' ); ?></p>
				<?php get_search_form(); ?>
				<p><a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'hussn' ); ?></a></p>
			</div>
		</section>
	</main><!-- #primary -->
</div><!-- .container -->

<?php
get_footer();

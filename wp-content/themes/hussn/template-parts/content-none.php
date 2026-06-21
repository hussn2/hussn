<?php
/**
 * Shown when no posts are found.
 *
 * @package Hussn
 */

?>
<section class="no-results not-found">
	<header class="page-header">
		<h1 class="page-title"><?php esc_html_e( 'Nothing found', 'hussn' ); ?></h1>
	</header>

	<div class="page-content">
		<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
			<p>
				<?php
				/* translators: %s: link to the new post screen. */
				printf( wp_kses_post( __( 'Ready to publish your first post? <a href="%s">Get started here.</a>', 'hussn' ) ), esc_url( admin_url( 'post-new.php' ) ) );
				?>
			</p>
		<?php elseif ( is_search() ) : ?>
			<p><?php esc_html_e( 'Sorry, nothing matched your search. Try different keywords.', 'hussn' ); ?></p>
			<?php get_search_form(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'It seems we cannot find what you are looking for. Perhaps a search will help.', 'hussn' ); ?></p>
			<?php get_search_form(); ?>
		<?php endif; ?>
	</div>
</section>

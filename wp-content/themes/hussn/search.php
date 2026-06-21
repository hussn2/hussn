<?php
/**
 * The template for displaying search results.
 *
 * @package Hussn
 */

get_header();
?>

<div class="container content-area has-sidebar">
	<main id="primary" class="site-main">
		<?php if ( have_posts() ) : ?>
			<header class="page-header">
				<h1 class="page-title">
					<?php
					/* translators: %s: search query. */
					printf( esc_html__( 'Search results for: %s', 'hussn' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
					?>
				</h1>
			</header>

			<div class="posts-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'card' );
				endwhile;
				?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'prev_text' => __( '&larr; Older', 'hussn' ),
					'next_text' => __( 'Newer &rarr;', 'hussn' ),
				)
			);
			?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</main><!-- #primary -->

	<?php get_sidebar(); ?>
</div><!-- .container -->

<?php
get_footer();

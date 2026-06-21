<?php
/**
 * The template for displaying archive pages (categories, tags, dates, authors).
 *
 * @package Hussn
 */

get_header();
?>

<div class="container content-area has-sidebar">
	<main id="primary" class="site-main">
		<?php if ( have_posts() ) : ?>
			<header class="page-header">
				<?php
				the_archive_title( '<h1 class="page-title">', '</h1>' );
				the_archive_description( '<div class="archive-description">', '</div>' );
				?>
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

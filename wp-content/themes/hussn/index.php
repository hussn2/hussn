<?php
/**
 * The main template file — the fallback for all views.
 *
 * @package Hussn
 */

get_header();
?>

<div class="container content-area has-sidebar">
	<main id="primary" class="site-main">
		<?php if ( have_posts() ) : ?>

			<?php if ( is_home() && ! is_front_page() ) : ?>
				<header class="page-header">
					<h1 class="page-title"><?php single_post_title(); ?></h1>
				</header>
			<?php endif; ?>

			<div class="posts-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', get_post_type() );
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

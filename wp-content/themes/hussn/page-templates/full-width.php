<?php
/**
 * Template Name: Full Width
 * Template Post Type: page
 *
 * A page template with no sidebar and edge-to-edge content width.
 *
 * @package Hussn
 */

get_header();
?>

<div class="container content-area is-full-width">
	<main id="primary" class="site-main">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
				<header class="entry-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
				</header>

				<div class="entry-content">
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'hussn' ),
							'after'  => '</div>',
						)
					);
					?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</main><!-- #primary -->
</div><!-- .container -->

<?php
get_footer();

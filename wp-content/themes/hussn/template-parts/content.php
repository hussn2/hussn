<?php
/**
 * Default post content for the main loop (excerpt-style listing).
 *
 * @package Hussn
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="post-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'hussn-card' ); ?>
		</a>
	<?php endif; ?>

	<div class="post-card__body">
		<header class="entry-header">
			<?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>
			<?php hussn_post_meta(); ?>
		</header>

		<div class="entry-summary"><?php the_excerpt(); ?></div>

		<a class="read-more" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Read more', 'hussn' ); ?> &rarr;
		</a>
	</div>
</article>

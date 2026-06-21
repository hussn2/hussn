<?php
/**
 * Compact card used in grids (front page, archives, search).
 *
 * @package Hussn
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<a class="post-card__media" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'hussn-card' ); ?>
		<?php else : ?>
			<span class="post-card__placeholder" aria-hidden="true"></span>
		<?php endif; ?>
	</a>

	<div class="post-card__body">
		<?php the_title( '<h3 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' ); ?>
		<?php hussn_post_meta(); ?>
		<p class="entry-summary"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
	</div>
</article>

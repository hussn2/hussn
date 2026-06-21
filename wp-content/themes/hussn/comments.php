<?php
/**
 * The template for displaying comments.
 *
 * @package Hussn
 */

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			$hussn_comment_count = get_comments_number();
			if ( '1' === (string) $hussn_comment_count ) {
				esc_html_e( 'One comment', 'hussn' );
			} else {
				printf(
					/* translators: %s: comment count. */
					esc_html( _n( '%s comment', '%s comments', $hussn_comment_count, 'hussn' ) ),
					esc_html( number_format_i18n( $hussn_comment_count ) )
				);
			}
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>

		<?php
		the_comments_navigation();

		if ( ! comments_open() ) :
			?>
			<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'hussn' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php comment_form(); ?>
</div><!-- #comments -->

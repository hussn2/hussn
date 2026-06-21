<?php
/**
 * The front page: a marketing-style landing layout with hero, feature
 * highlights, and the latest posts. Customizer settings drive the hero text.
 *
 * @package Hussn
 */

get_header();
?>

<section class="hero">
	<div class="container hero__inner">
		<p class="hero__eyebrow"><?php echo esc_html( get_theme_mod( 'hussn_hero_eyebrow', __( 'Welcome', 'hussn' ) ) ); ?></p>
		<h1 class="hero__title">
			<?php echo esc_html( get_theme_mod( 'hussn_hero_title', __( 'Build something people love.', 'hussn' ) ) ); ?>
		</h1>
		<p class="hero__subtitle">
			<?php echo esc_html( get_theme_mod( 'hussn_hero_subtitle', __( 'A clean, fast, and flexible WordPress theme to launch your next idea in minutes.', 'hussn' ) ) ); ?>
		</p>
		<div class="hero__actions">
			<?php
			$hussn_cta_url   = get_theme_mod( 'hussn_hero_cta_url', '#features' );
			$hussn_cta_label = get_theme_mod( 'hussn_hero_cta_label', __( 'Get started', 'hussn' ) );
			?>
			<a class="btn btn--primary" href="<?php echo esc_url( $hussn_cta_url ); ?>"><?php echo esc_html( $hussn_cta_label ); ?></a>
			<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/?page_id=2' ) ); ?>"><?php esc_html_e( 'Learn more', 'hussn' ); ?></a>
		</div>
	</div>
</section>

<section id="features" class="features">
	<div class="container">
		<div class="features__grid">
			<?php
			$hussn_features = array(
				array(
					'icon'  => '&#9889;',
					'title' => __( 'Fast by default', 'hussn' ),
					'text'  => __( 'Lightweight markup and minimal assets keep your pages quick to load.', 'hussn' ),
				),
				array(
					'icon'  => '&#9881;',
					'title' => __( 'Fully customizable', 'hussn' ),
					'text'  => __( 'Tweak the hero, colors, logo, and menus right from the Customizer.', 'hussn' ),
				),
				array(
					'icon'  => '&#128241;',
					'title' => __( 'Responsive everywhere', 'hussn' ),
					'text'  => __( 'Looks great on phones, tablets, and desktops out of the box.', 'hussn' ),
				),
			);

			foreach ( $hussn_features as $hussn_feature ) :
				?>
				<div class="feature-card">
					<div class="feature-card__icon" aria-hidden="true"><?php echo esc_html( $hussn_feature['icon'] ); ?></div>
					<h3 class="feature-card__title"><?php echo esc_html( $hussn_feature['title'] ); ?></h3>
					<p class="feature-card__text"><?php echo esc_html( $hussn_feature['text'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
// If a static front page has page content, show it.
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		if ( trim( get_the_content() ) ) :
			?>
			<section class="container content-area front-page-content">
				<div class="entry-content"><?php the_content(); ?></div>
			</section>
			<?php
		endif;
	endwhile;
endif;
?>

<section class="latest-posts">
	<div class="container">
		<h2 class="section-title"><?php esc_html_e( 'Latest from the blog', 'hussn' ); ?></h2>
		<?php
		$hussn_recent = new WP_Query(
			array(
				'post_type'           => 'post',
				'posts_per_page'      => 3,
				'ignore_sticky_posts' => true,
			)
		);

		if ( $hussn_recent->have_posts() ) :
			?>
			<div class="posts-grid">
				<?php
				while ( $hussn_recent->have_posts() ) :
					$hussn_recent->the_post();
					get_template_part( 'template-parts/content', 'card' );
				endwhile;
				?>
			</div>
			<?php
			wp_reset_postdata();
		else :
			?>
			<p class="no-posts"><?php esc_html_e( 'No posts yet — your latest articles will appear here.', 'hussn' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();

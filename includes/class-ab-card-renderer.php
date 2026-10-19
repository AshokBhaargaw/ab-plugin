<?php
/**
 * AB Card Renderer
 *
 * Standalone post card renderer for AB Blog Grid and Basic Posts widgets.
 * Independent of Elementor lifecycle so it is always available during AJAX requests.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AB_Card_Renderer {

	/**
	 * Render a single post card using the provided settings.
	 *
	 * @param array $settings Widget settings array.
	 * @param int   $post_id  Optional. Post ID to render. Defaults to current post.
	 */
	public static function render_blog_card( $settings = array(), $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return;
		}

		$permalink   = get_permalink( $post_id );
		$title       = get_the_title( $post_id );
		$title_tag   = ! empty( $settings['title_tag'] ) ? sanitize_key( $settings['title_tag'] ) : 'h3';
		if ( ! in_array( $title_tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p' ), true ) ) {
			$title_tag = 'h3';
		}

		$card_style = ! empty( $settings['card_style'] ) ? $settings['card_style'] : ( ! empty( $settings['card_design'] ) ? $settings['card_design'] : 'modern' );

		// Image aspect ratio class
		$ratio_raw = ! empty( $settings['image_style_ratio'] ) ? $settings['image_style_ratio'] : ( ! empty( $settings['image_aspect_ratio'] ) ? $settings['image_aspect_ratio'] : '16/9' );
		$ratio_class = 'ratio-' . str_replace( array( '/', 'ratio-' ), array( '-', '' ), $ratio_raw );

		$show_image     = isset( $settings['show_image'] ) ? $settings['show_image'] : 'yes';
		$show_badge     = isset( $settings['show_badge'] ) ? $settings['show_badge'] : 'yes';
		$show_title     = isset( $settings['show_title'] ) ? $settings['show_title'] : 'yes';
		$show_excerpt   = isset( $settings['show_excerpt'] ) ? $settings['show_excerpt'] : 'yes';
		$excerpt_len    = ! empty( $settings['excerpt_length'] ) ? intval( $settings['excerpt_length'] ) : 18;
		$show_meta      = isset( $settings['show_meta'] ) ? $settings['show_meta'] : 'yes';
		$show_author    = isset( $settings['show_author'] ) ? $settings['show_author'] : 'yes';
		$show_date      = isset( $settings['show_date'] ) ? $settings['show_date'] : 'yes';
		$show_read_more = isset( $settings['show_read_more'] ) ? $settings['show_read_more'] : 'yes';
		$read_more_text = ! empty( $settings['read_more_text'] ) ? $settings['read_more_text'] : esc_html__( 'Read Story', 'ab-addon' );

		// Category / Taxonomy detection
		$primary_cat = null;
		$categories  = get_the_category( $post_id );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$primary_cat = $categories[0];
		} else {
			$post_type = get_post_type( $post_id );
			if ( $post_type ) {
				$taxonomies = get_object_taxonomies( $post_type );
				if ( ! empty( $taxonomies ) ) {
					$terms = get_the_terms( $post_id, $taxonomies[0] );
					if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						$primary_cat = $terms[0];
					}
				}
			}
		}

		$badge_url = '';
		if ( $primary_cat ) {
			$term_link = get_term_link( $primary_cat );
			if ( ! is_wp_error( $term_link ) ) {
				$badge_url = $term_link;
			}
		}

		// Author Name
		$author_id   = get_post_field( 'post_author', $post_id );
		$author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
		?>
		<article class="ab-card-item ab-card-<?php echo esc_attr( $card_style ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
			<div class="ab-card-inner">

				<?php if ( 'yes' === $show_image && has_post_thumbnail( $post_id ) ) : ?>
					<div class="ab-card-media <?php echo esc_attr( $ratio_class ); ?>">
						<a href="<?php echo esc_url( $permalink ); ?>" class="ab-card-image-link">
							<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'ab-card-img', 'alt' => esc_attr( $title ) ) ); ?>
						</a>

						<?php if ( 'yes' === $show_badge && $primary_cat ) : ?>
							<div class="ab-card-badge">
								<?php if ( $badge_url ) : ?>
									<a href="<?php echo esc_url( $badge_url ); ?>">
										<?php echo esc_html( $primary_cat->name ); ?>
									</a>
								<?php else : ?>
									<span><?php echo esc_html( $primary_cat->name ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php elseif ( 'yes' === $show_image && ! has_post_thumbnail( $post_id ) ) : ?>
					<div class="ab-card-media <?php echo esc_attr( $ratio_class ); ?> ab-card-media-placeholder">
						<a href="<?php echo esc_url( $permalink ); ?>" class="ab-card-image-link">
							<div class="ab-placeholder-pattern">
								<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" opacity="0.35">
									<rect x="4" y="6" width="40" height="36" rx="4"></rect>
									<circle cx="15" cy="17" r="4"></circle>
									<path d="M4 34l12-10 10 9 8-6 10 9"></path>
								</svg>
							</div>
						</a>

						<?php if ( 'yes' === $show_badge && $primary_cat ) : ?>
							<div class="ab-card-badge">
								<?php if ( $badge_url ) : ?>
									<a href="<?php echo esc_url( $badge_url ); ?>">
										<?php echo esc_html( $primary_cat->name ); ?>
									</a>
								<?php else : ?>
									<span><?php echo esc_html( $primary_cat->name ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php elseif ( 'yes' === $show_badge && $primary_cat && 'overlay' !== $card_style ) : ?>
					<div class="ab-card-badge-inline">
						<?php if ( $badge_url ) : ?>
							<a href="<?php echo esc_url( $badge_url ); ?>">
								<?php echo esc_html( $primary_cat->name ); ?>
							</a>
						<?php else : ?>
							<span><?php echo esc_html( $primary_cat->name ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="ab-card-content">

					<?php if ( 'yes' === $show_meta ) : ?>
						<div class="ab-card-meta">
							<?php if ( 'yes' === $show_author && ! empty( $author_name ) ) : ?>
								<span class="ab-meta-item ab-meta-author">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
									<?php echo esc_html( $author_name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( 'yes' === $show_date ) : ?>
								<span class="ab-meta-item ab-meta-date">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
									<?php echo esc_html( get_the_date( 'M j, Y', $post_id ) ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( 'yes' === $show_title ) : ?>
						<<?php echo esc_attr( $title_tag ); ?> class="ab-card-title">
							<a href="<?php echo esc_url( $permalink ); ?>">
								<?php echo esc_html( $title ); ?>
							</a>
						</<?php echo esc_attr( $title_tag ); ?>>
					<?php endif; ?>

					<?php if ( 'yes' === $show_excerpt ) : ?>
						<div class="ab-card-excerpt">
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), $excerpt_len, '...' ) ); ?></p>
						</div>
					<?php endif; ?>

					<?php if ( 'yes' === $show_read_more ) : ?>
						<div class="ab-card-footer">
							<a href="<?php echo esc_url( $permalink ); ?>" class="ab-card-btn">
								<span><?php echo esc_html( $read_more_text ); ?></span>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
							</a>
						</div>
					<?php endif; ?>

				</div>
			</div>
		</article>
		<?php
	}
}

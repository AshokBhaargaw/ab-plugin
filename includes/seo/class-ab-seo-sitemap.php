<?php
/**
 * AB SEO XML Sitemap Generator
 *
 * Generates an automated XML sitemap with image support, post type exclusions,
 * and automated search engine pinging on post publish.
 *
 * Accessible via /sitemap.xml or ?ab_sitemap=1
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_SEO_Sitemap {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'render_sitemap' ) );
		add_action( 'publish_post', array( $this, 'ping_search_engines' ) );
		add_action( 'publish_page', array( $this, 'ping_search_engines' ) );
	}

	/**
	 * Add rewrite rule for /sitemap.xml
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?ab_sitemap=1', 'top' );
	}

	/**
	 * Register query variable.
	 */
	public function register_query_vars( $vars ) {
		$vars[] = 'ab_sitemap';
		return $vars;
	}

	/**
	 * Check if sitemap is requested and render.
	 */
	public function render_sitemap() {
		$is_sitemap = get_query_var( 'ab_sitemap' );
		if ( ! $is_sitemap && ( ! isset( $_GET['ab_sitemap'] ) || '1' !== $_GET['ab_sitemap'] ) ) {
			return;
		}

		$options = AB_SEO::get_options();
		if ( empty( $options['sitemap_enable'] ) ) {
			return;
		}

		if ( ob_get_length() ) {
			ob_clean();
		}

		header( 'Content-Type: text/xml; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, follow', true );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
		echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

		// Homepage
		$home_url = home_url( '/' );
		$latest_mod = get_lastpostmodified( 'GMT' );
		$home_date = ! empty( $latest_mod ) ? gmdate( 'Y-m-d\TH:i:s+00:00', strtotime( $latest_mod ) ) : gmdate( 'Y-m-d\TH:i:s+00:00' );

		echo '  <url>' . "\n";
		echo '    <loc>' . esc_url( $home_url ) . '</loc>' . "\n";
		echo '    <lastmod>' . esc_html( $home_date ) . '</lastmod>' . "\n";
		echo '    <changefreq>daily</changefreq>' . "\n";
		echo '    <priority>1.0</priority>' . "\n";
		echo '  </url>' . "\n";

		// Post Types
		$post_types = (array) ( $options['sitemap_post_types'] ?? array( 'post', 'page' ) );
		$include_images = ! empty( $options['sitemap_include_images'] );

		foreach ( $post_types as $pt ) {
			$posts = get_posts( array(
				'post_type'      => $pt,
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			) );

			foreach ( $posts as $p ) {
				$seo_meta = get_post_meta( $p->ID, '_ab_seo_meta', true );
				if ( is_array( $seo_meta ) ) {
					// Check noindex or explicit sitemap exclusion
					if ( ! empty( $seo_meta['noindex'] ) || ! empty( $seo_meta['exclude_sitemap'] ) ) {
						continue;
					}
				}

				$loc      = get_permalink( $p );
				$lastmod  = gmdate( 'Y-m-d\TH:i:s+00:00', strtotime( $p->post_modified_gmt ) );
				$priority = ( 'post' === $pt ) ? '0.8' : '0.6';

				echo '  <url>' . "\n";
				echo '    <loc>' . esc_url( $loc ) . '</loc>' . "\n";
				echo '    <lastmod>' . esc_html( $lastmod ) . '</lastmod>' . "\n";
				echo '    <changefreq>weekly</changefreq>' . "\n";
				echo '    <priority>' . esc_html( $priority ) . '</priority>' . "\n";

				// Images
				if ( $include_images ) {
					$images = array();
					// Featured image
					if ( has_post_thumbnail( $p->ID ) ) {
						$thumb_id  = get_post_thumbnail_id( $p->ID );
						$img_src   = wp_get_attachment_image_url( $thumb_id, 'full' );
						$img_title = get_the_title( $thumb_id );
						if ( $img_src ) {
							$images[] = array( 'loc' => $img_src, 'title' => $img_title );
						}
					}

					// Content images
					if ( ! empty( $p->post_content ) ) {
						preg_match_all( '/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $p->post_content, $img_matches );
						if ( ! empty( $img_matches[1] ) ) {
							foreach ( array_slice( $img_matches[1], 0, 5 ) as $src ) {
								$images[] = array( 'loc' => $src, 'title' => $p->post_title );
							}
						}
					}

					foreach ( $images as $img ) {
						echo '    <image:image>' . "\n";
						echo '      <image:loc>' . esc_url( $img['loc'] ) . '</image:loc>' . "\n";
						if ( ! empty( $img['title'] ) ) {
							echo '      <image:title>' . esc_html( $img['title'] ) . '</image:title>' . "\n";
						}
						echo '    </image:image>' . "\n";
					}
				}

				echo '  </url>' . "\n";
			}
		}

		// Taxonomies
		$taxonomies = (array) ( $options['sitemap_taxonomies'] ?? array( 'category', 'post_tag' ) );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			) );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$loc = get_term_link( $term );
					if ( ! is_wp_error( $loc ) ) {
						echo '  <url>' . "\n";
						echo '    <loc>' . esc_url( $loc ) . '</loc>' . "\n";
						echo '    <changefreq>weekly</changefreq>' . "\n";
						echo '    <priority>0.5</priority>' . "\n";
						echo '  </url>' . "\n";
					}
				}
			}
		}

		echo '</urlset>';
		exit;
	}

	/**
	 * Ping Google and Bing when a new post is published.
	 */
	public function ping_search_engines( $post_id ) {
		$options = AB_SEO::get_options();
		if ( empty( $options['sitemap_ping_google'] ) || empty( $options['sitemap_enable'] ) ) {
			return;
		}

		$sitemap_url = rawurlencode( home_url( '/sitemap.xml' ) );

		// Ping Google
		wp_remote_get( 'https://www.google.com/ping?sitemap=' . $sitemap_url, array( 'timeout' => 5, 'blocking' => false ) );

		// Ping Bing
		wp_remote_get( 'https://www.bing.com/ping?sitemap=' . $sitemap_url, array( 'timeout' => 5, 'blocking' => false ) );
	}
}

AB_SEO_Sitemap::instance();

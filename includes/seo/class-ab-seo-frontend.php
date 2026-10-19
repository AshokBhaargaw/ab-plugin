<?php
/**
 * AB SEO Frontend Output Engine
 *
 * Renders SEO Title, Meta Description, Robots Meta, Canonical Link,
 * OpenGraph Tags, Twitter Cards, and Advanced Schema.org JSON-LD Graph.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_SEO_Frontend {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		// Document Title
		add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ), 15 );
		add_filter( 'wp_title', array( $this, 'filter_wp_title' ), 15, 2 );

		// Head Output (Meta tags, OpenGraph, Twitter, Schema)
		add_action( 'wp_head', array( $this, 'render_head_meta_tags' ), 1 );
	}

	/**
	 * Get current SEO metadata for queried post or page.
	 *
	 * @return array
	 */
	public function get_current_seo_meta() {
		if ( is_singular() ) {
			$post_id  = get_queried_object_id();
			$seo_meta = get_post_meta( $post_id, '_ab_seo_meta', true );
			return is_array( $seo_meta ) ? $seo_meta : array();
		}
		return array();
	}

	/**
	 * Filter WordPress document title.
	 *
	 * @param string $title
	 * @return string
	 */
	public function filter_document_title( $title ) {
		$options = AB_SEO::get_options();

		if ( is_front_page() || is_home() ) {
			$home_title = ! empty( $options['home_title'] ) ? $options['home_title'] : '%%sitename%% %%sep%% %%tagline%%';
			return esc_html( AB_SEO::replace_template_tags( $home_title ) );
		}

		if ( is_singular() ) {
			$post     = get_queried_object();
			$seo_meta = $this->get_current_seo_meta();

			if ( ! empty( $seo_meta['seo_title'] ) ) {
				return esc_html( AB_SEO::replace_template_tags( $seo_meta['seo_title'], $post ) );
			}

			$tpl = ( 'page' === $post->post_type ) ? ( $options['page_title_tpl'] ?? '' ) : ( $options['post_title_tpl'] ?? '' );
			if ( ! empty( $tpl ) ) {
				return esc_html( AB_SEO::replace_template_tags( $tpl, $post ) );
			}
		}

		return $title;
	}

	/**
	 * Filter wp_title for classic themes.
	 */
	public function filter_wp_title( $title, $sep = '—' ) {
		return $this->filter_document_title( $title );
	}

	/**
	 * Render all SEO meta tags in wp_head.
	 */
	public function render_head_meta_tags() {
		$options  = AB_SEO::get_options();
		$seo_meta = $this->get_current_seo_meta();
		$post     = is_singular() ? get_queried_object() : null;

		echo "\n<!-- AB SEO by Ashok Bhaargaw -->\n";

		// 1. Meta Description
		$description = '';
		if ( is_front_page() || is_home() ) {
			$description = $options['home_desc'] ?? get_bloginfo( 'description' );
		} elseif ( is_singular() && ! empty( $seo_meta['meta_description'] ) ) {
			$description = AB_SEO::replace_template_tags( $seo_meta['meta_description'], $post );
		} elseif ( is_singular() ) {
			$tpl = ( 'page' === $post->post_type ) ? ( $options['page_desc_tpl'] ?? '' ) : ( $options['post_desc_tpl'] ?? '' );
			if ( ! empty( $tpl ) ) {
				$description = AB_SEO::replace_template_tags( $tpl, $post );
			}
		}

		if ( ! empty( $description ) ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( wp_strip_all_tags( $description ) ) );
		}

		// 2. Robots Meta
		$robots = array();
		if ( ! empty( $seo_meta['noindex'] ) || ! get_option( 'blog_public' ) ) {
			$robots[] = 'noindex';
		} else {
			$robots[] = 'index';
		}

		if ( ! empty( $seo_meta['nofollow'] ) ) {
			$robots[] = 'nofollow';
		} else {
			$robots[] = 'follow';
		}

		$robots[] = 'max-snippet:-1';
		$robots[] = 'max-image-preview:large';
		$robots[] = 'max-video-preview:-1';

		if ( ! empty( $seo_meta['noarchive'] ) ) {
			$robots[] = 'noarchive';
		}
		if ( ! empty( $seo_meta['nosnippet'] ) ) {
			$robots[] = 'nosnippet';
		}

		printf( '<meta name="robots" content="%s" />' . "\n", esc_attr( implode( ', ', $robots ) ) );

		// 3. Canonical URL
		$canonical = '';
		if ( is_singular() && ! empty( $seo_meta['canonical_url'] ) ) {
			$canonical = esc_url( $seo_meta['canonical_url'] );
		} elseif ( is_singular() ) {
			$canonical = get_permalink( $post );
		} elseif ( is_front_page() || is_home() ) {
			$canonical = home_url( '/' );
		}

		if ( ! empty( $canonical ) ) {
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
		}

		// 4. OpenGraph Tags
		if ( ! empty( $options['og_enable'] ) ) {
			$this->render_opengraph_tags( $seo_meta, $post, $canonical, $description, $options );
		}

		// 5. Twitter Card Tags
		if ( ! empty( $options['twitter_enable'] ) ) {
			$this->render_twitter_tags( $seo_meta, $post, $description, $options );
		}

		// 6. Schema.org JSON-LD Graph
		$this->render_schema_graph( $seo_meta, $post, $canonical, $description, $options );

		echo "<!-- /AB SEO -->\n\n";
	}

	/**
	 * Render OpenGraph meta tags.
	 */
	private function render_opengraph_tags( $seo_meta, $post, $canonical, $description, $options ) {
		$title = ! empty( $seo_meta['og_title'] ) ? $seo_meta['og_title'] : ( ! empty( $seo_meta['seo_title'] ) ? $seo_meta['seo_title'] : get_the_title( $post ) );
		$desc  = ! empty( $seo_meta['og_description'] ) ? $seo_meta['og_description'] : $description;

		$image = '';
		if ( ! empty( $seo_meta['og_image'] ) ) {
			$image = $seo_meta['og_image'];
		} elseif ( is_singular() && has_post_thumbnail( $post ) ) {
			$image = get_the_post_thumbnail_url( $post, 'large' );
		} elseif ( ! empty( $options['default_share_image'] ) ) {
			$image = $options['default_share_image'];
		}

		$type = is_singular( 'post' ) ? 'article' : 'website';

		printf( '<meta property="og:locale" content="%s" />' . "\n", esc_attr( get_locale() ) );
		printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( $type ) );
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( wp_strip_all_tags( $title ) ) );
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );
		if ( ! empty( $canonical ) ) {
			printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $canonical ) );
		}
		printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );

		if ( ! empty( $image ) ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
		}

		if ( is_singular( 'post' ) && $post ) {
			printf( '<meta property="article:published_time" content="%s" />' . "\n", esc_attr( get_the_date( 'c', $post ) ) );
			printf( '<meta property="article:modified_time" content="%s" />' . "\n", esc_attr( get_the_modified_date( 'c', $post ) ) );
		}
	}

	/**
	 * Render Twitter Card meta tags.
	 */
	private function render_twitter_tags( $seo_meta, $post, $description, $options ) {
		$card = ! empty( $options['twitter_card_type'] ) ? $options['twitter_card_type'] : 'summary_large_image';
		$title = ! empty( $seo_meta['twitter_title'] ) ? $seo_meta['twitter_title'] : ( ! empty( $seo_meta['seo_title'] ) ? $seo_meta['seo_title'] : get_the_title( $post ) );
		$desc  = ! empty( $seo_meta['twitter_description'] ) ? $seo_meta['twitter_description'] : $description;

		$image = '';
		if ( ! empty( $seo_meta['twitter_image'] ) ) {
			$image = $seo_meta['twitter_image'];
		} elseif ( ! empty( $seo_meta['og_image'] ) ) {
			$image = $seo_meta['og_image'];
		} elseif ( is_singular() && has_post_thumbnail( $post ) ) {
			$image = get_the_post_thumbnail_url( $post, 'large' );
		} elseif ( ! empty( $options['default_share_image'] ) ) {
			$image = $options['default_share_image'];
		}

		printf( '<meta name="twitter:card" content="%s" />' . "\n", esc_attr( $card ) );
		printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( wp_strip_all_tags( $title ) ) );
		printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );

		if ( ! empty( $image ) ) {
			printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image ) );
		}

		if ( ! empty( $options['social_twitter'] ) ) {
			$handle = trim( str_replace( array( 'https://twitter.com/', 'https://x.com/', '@' ), '', $options['social_twitter'] ) );
			if ( ! empty( $handle ) ) {
				printf( '<meta name="twitter:site" content="@%s" />' . "\n", esc_attr( $handle ) );
			}
		}
	}

	/**
	 * Render Schema.org structured data JSON-LD graph.
	 * Enhanced Schema Framework matching Yoast SEO Premium.
	 */
	private function render_schema_graph( $seo_meta, $post, $canonical, $description, $options ) {
		$site_url = home_url( '/' );
		$graph = array();

		// 1. Organization or Person
		$is_org = ( 'Organization' === ( $options['site_type'] ?? 'Organization' ) );
		$same_as = array_filter( array(
			$options['social_facebook'] ?? '',
			$options['social_twitter'] ?? '',
			$options['social_instagram'] ?? '',
			$options['social_linkedin'] ?? '',
			$options['social_youtube'] ?? '',
			$options['social_pinterest'] ?? '',
		) );

		if ( $is_org ) {
			$org_schema = array(
				'@type' => 'Organization',
				'@id'   => $site_url . '#organization',
				'name'  => $options['org_name'] ?? get_bloginfo( 'name' ),
				'url'   => $site_url,
			);
			if ( ! empty( $options['org_alternate_name'] ) ) {
				$org_schema['alternateName'] = $options['org_alternate_name'];
			}
			if ( ! empty( $options['org_logo'] ) ) {
				$org_schema['logo'] = array(
					'@type' => 'ImageObject',
					'@id'   => $site_url . '#logo',
					'url'   => $options['org_logo'],
				);
				$org_schema['image'] = array( '@id' => $site_url . '#logo' );
			}
			if ( ! empty( $same_as ) ) {
				$org_schema['sameAs'] = array_values( $same_as );
			}
			$graph[] = $org_schema;
		}

		// 2. WebSite with SearchAction
		$website_schema = array(
			'@type' => 'WebSite',
			'@id'   => $site_url . '#website',
			'url'   => $site_url,
			'name'  => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'publisher'   => array( '@id' => $site_url . '#organization' ),
			'potentialAction' => array(
				array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => $site_url . '?s={search_term_string}',
					),
					'query-input' => 'required name=search_term_string',
				),
			),
		);
		$graph[] = $website_schema;

		// 3. WebPage
		if ( ! empty( $canonical ) ) {
			$page_type = ! empty( $seo_meta['schema_page_type'] ) ? $seo_meta['schema_page_type'] : 'WebPage';
			$webpage_schema = array(
				'@type'           => $page_type,
				'@id'             => $canonical . '#webpage',
				'url'             => $canonical,
				'name'            => wp_strip_all_tags( $this->filter_document_title( '' ) ),
				'isPartOf'        => array( '@id' => $site_url . '#website' ),
				'inLanguage'      => get_locale(),
			);

			if ( ! empty( $description ) ) {
				$webpage_schema['description'] = wp_strip_all_tags( $description );
			}

			if ( is_singular() && $post ) {
				$webpage_schema['datePublished'] = get_the_date( 'c', $post );
				$webpage_schema['dateModified']  = get_the_modified_date( 'c', $post );
			}

			$graph[] = $webpage_schema;
		}

		// 4. Article / BlogPosting / NewsArticle
		if ( is_singular() && $post ) {
			$article_type = ! empty( $seo_meta['schema_article_type'] ) ? $seo_meta['schema_article_type'] : ( ( 'post' === $post->post_type ) ? 'BlogPosting' : '' );

			if ( ! empty( $article_type ) && 'None' !== $article_type ) {
				$author_id = $post->post_author;
				$article_schema = array(
					'@type'            => $article_type,
					'@id'              => $canonical . '#article',
					'isPartOf'         => array( '@id' => $canonical . '#webpage' ),
					'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
					'datePublished'    => get_the_date( 'c', $post ),
					'dateModified'     => get_the_modified_date( 'c', $post ),
					'mainEntityOfPage' => array( '@id' => $canonical . '#webpage' ),
					'author'           => array(
						'@type' => 'Person',
						'name'  => get_the_author_meta( 'display_name', $author_id ),
						'url'   => get_author_posts_url( $author_id ),
					),
					'publisher'        => array( '@id' => $site_url . '#organization' ),
				);

				if ( ! empty( $description ) ) {
					$article_schema['description'] = wp_strip_all_tags( $description );
				}

				if ( has_post_thumbnail( $post ) ) {
					$thumb_id = get_post_thumbnail_id( $post );
					$img_url  = wp_get_attachment_image_url( $thumb_id, 'full' );
					if ( $img_url ) {
						$article_schema['image'] = array(
							'@type' => 'ImageObject',
							'url'   => $img_url,
						);
					}
				}

				$graph[] = $article_schema;
			}
		}

		// 5. Dynamic FAQPage Schema (Yoast Premium FAQ block & builder)
		if ( ! empty( $seo_meta['faq_items'] ) && is_array( $seo_meta['faq_items'] ) ) {
			$faq_entities = array();
			foreach ( $seo_meta['faq_items'] as $item ) {
				if ( ! empty( $item['q'] ) && ! empty( $item['a'] ) ) {
					$faq_entities[] = array(
						'@type'          => 'Question',
						'name'           => wp_strip_all_tags( $item['q'] ),
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => wp_kses_post( $item['a'] ),
						),
					);
				}
			}

			if ( ! empty( $faq_entities ) ) {
				$graph[] = array(
					'@type'      => 'FAQPage',
					'@id'        => $canonical . '#faq',
					'mainEntity' => $faq_entities,
				);
			}
		}

		if ( ! empty( $graph ) ) {
			$schema = array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			);
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "</script>\n";
		}
	}
}

AB_SEO_Frontend::instance();

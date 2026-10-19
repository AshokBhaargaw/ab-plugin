<?php
/**
 * AB SEO Breadcrumbs Engine
 *
 * Generates an accessible, hierarchically accurate breadcrumb trail
 * with Schema.org BreadcrumbList JSON-LD / Microdata markup.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_SEO_Breadcrumbs {

	public static function init() {
		add_shortcode( 'ab_breadcrumbs', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Shortcode handler: [ab_breadcrumbs]
	 */
	public static function render_shortcode( $atts ) {
		return self::render( $atts );
	}

	/**
	 * Render the breadcrumbs HTML with Schema.org metadata.
	 *
	 * @param array $args
	 * @return string HTML
	 */
	public static function render( $args = array() ) {
		$options = AB_SEO::get_options();

		if ( empty( $options['breadcrumbs_enable'] ) ) {
			return '';
		}

		$defaults = array(
			'separator'    => $options['breadcrumbs_sep'] ?? '›',
			'home_text'    => $options['breadcrumbs_home'] ?? __( 'Home', 'ab-addon' ),
			'prefix'       => $options['breadcrumbs_prefix'] ?? '',
			'wrapper_tag'  => 'nav',
			'wrapper_class'=> 'ab-breadcrumbs',
			'echo'         => false,
		);

		$parsed_args = wp_parse_args( $args, $defaults );

		$items = array();

		// Home item
		$items[] = array(
			'title' => $parsed_args['home_text'],
			'url'   => home_url( '/' ),
		);

		if ( is_front_page() || is_home() ) {
			// Just homepage, no trail needed
			return '';
		} elseif ( is_category() ) {
			$cat = get_queried_object();
			if ( $cat->parent != 0 ) {
				$parents = array_reverse( get_ancestors( $cat->term_id, 'category' ) );
				foreach ( $parents as $parent_id ) {
					$parent = get_term( $parent_id, 'category' );
					$items[] = array(
						'title' => $parent->name,
						'url'   => get_category_link( $parent_id ),
					);
				}
			}
			$items[] = array(
				'title' => single_cat_title( '', false ),
				'url'   => '',
			);
		} elseif ( is_tag() ) {
			$items[] = array(
				'title' => single_tag_title( '', false ),
				'url'   => '',
			);
		} elseif ( is_tax() ) {
			$term = get_queried_object();
			$taxonomy = $term->taxonomy;
			if ( $term->parent != 0 ) {
				$parents = array_reverse( get_ancestors( $term->term_id, $taxonomy ) );
				foreach ( $parents as $parent_id ) {
					$parent = get_term( $parent_id, $taxonomy );
					$items[] = array(
						'title' => $parent->name,
						'url'   => get_term_link( $parent ),
					);
				}
			}
			$items[] = array(
				'title' => $term->name,
				'url'   => '',
			);
		} elseif ( is_singular( 'post' ) ) {
			$categories = get_the_category();
			if ( ! empty( $categories ) ) {
				$cat = $categories[0];
				if ( $cat->parent != 0 ) {
					$parents = array_reverse( get_ancestors( $cat->term_id, 'category' ) );
					foreach ( $parents as $parent_id ) {
						$parent = get_term( $parent_id, 'category' );
						$items[] = array(
							'title' => $parent->name,
							'url'   => get_category_link( $parent_id ),
						);
					}
				}
				$items[] = array(
					'title' => $cat->name,
					'url'   => get_category_link( $cat->term_id ),
				);
			}
			$items[] = array(
				'title' => get_the_title(),
				'url'   => '',
			);
		} elseif ( is_page() ) {
			global $post;
			if ( $post && $post->post_parent ) {
				$ancestors = array_reverse( get_post_ancestors( $post->ID ) );
				foreach ( $ancestors as $ancestor_id ) {
					$items[] = array(
						'title' => get_the_title( $ancestor_id ),
						'url'   => get_permalink( $ancestor_id ),
					);
				}
			}
			$items[] = array(
				'title' => get_the_title(),
				'url'   => '',
			);
		} elseif ( is_singular() ) {
			$post_type = get_post_type();
			$post_type_obj = get_post_type_object( $post_type );
			if ( $post_type_obj && $post_type_obj->has_archive ) {
				$items[] = array(
					'title' => $post_type_obj->labels->name,
					'url'   => get_post_type_archive_link( $post_type ),
				);
			}
			$items[] = array(
				'title' => get_the_title(),
				'url'   => '',
			);
		} elseif ( is_post_type_archive() ) {
			$post_type = get_post_type();
			$post_type_obj = get_post_type_object( $post_type );
			if ( $post_type_obj ) {
				$items[] = array(
					'title' => $post_type_obj->labels->name,
					'url'   => '',
				);
			}
		} elseif ( is_search() ) {
			$items[] = array(
				'title' => sprintf( __( 'Search Results for "%s"', 'ab-addon' ), get_search_query() ),
				'url'   => '',
			);
		} elseif ( is_404() ) {
			$items[] = array(
				'title' => __( 'Page Not Found (404)', 'ab-addon' ),
				'url'   => '',
			);
		}

		if ( count( $items ) <= 1 ) {
			return '';
		}

		// Build Schema.org BreadcrumbList JSON-LD
		$json_ld = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(),
		);

		// Build HTML
		$output = sprintf(
			'<%1$s class="%2$s" aria-label="%3$s">',
			esc_attr( $parsed_args['wrapper_tag'] ),
			esc_attr( $parsed_args['wrapper_class'] ),
			esc_attr__( 'Breadcrumbs', 'ab-addon' )
		);

		if ( ! empty( $parsed_args['prefix'] ) ) {
			$output .= '<span class="ab-breadcrumbs__prefix">' . esc_html( $parsed_args['prefix'] ) . ' </span>';
		}

		$output .= '<ol class="ab-breadcrumbs__list">';
		$total = count( $items );

		foreach ( $items as $index => $item ) {
			$pos = $index + 1;
			$is_last = ( $pos === $total );

			$json_ld['itemListElement'][] = array(
				'@type'    => 'ListItem',
				'position' => $pos,
				'name'     => $item['title'],
				'item'     => ! empty( $item['url'] ) ? $item['url'] : home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ),
			);

			$output .= '<li class="ab-breadcrumbs__item' . ( $is_last ? ' ab-breadcrumbs__item--current' : '' ) . '">';
			if ( ! empty( $item['url'] ) && ! $is_last ) {
				$output .= '<a href="' . esc_url( $item['url'] ) . '" class="ab-breadcrumbs__link">' . esc_html( $item['title'] ) . '</a>';
			} else {
				$output .= '<span class="ab-breadcrumbs__current" aria-current="page">' . esc_html( $item['title'] ) . '</span>';
			}

			if ( ! $is_last ) {
				$output .= ' <span class="ab-breadcrumbs__separator" aria-hidden="true">' . esc_html( $parsed_args['separator'] ) . '</span> ';
			}
			$output .= '</li>';
		}

		$output .= '</ol>';
		$output .= sprintf( '</%s>', esc_attr( $parsed_args['wrapper_tag'] ) );

		// Append JSON-LD script tag
		$output .= '<script type="application/ld+json">' . wp_json_encode( $json_ld ) . '</script>';

		if ( ! empty( $parsed_args['echo'] ) ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return '';
		}

		return $output;
	}
}

AB_SEO_Breadcrumbs::init();

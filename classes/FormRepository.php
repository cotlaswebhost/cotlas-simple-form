<?php
/**
 * Form data access helpers.
 *
 * @package CotlasSimpleForms
 */

namespace Cotlas\SimpleForms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads form statistics without changing existing storage.
 */
class FormRepository {

	/**
	 * Count all forms.
	 *
	 * @return int
	 */
	public function count_all() {
		$counts = wp_count_posts( 'csf_form' );
		$total  = 0;

		if ( ! empty( $counts ) ) {
			foreach ( $counts as $count ) {
				$total += (int) $count;
			}
		}

		return $total;
	}

	/**
	 * Count forms by stored form type.
	 *
	 * @param string $type Form type meta value.
	 * @return int
	 */
	public function count_by_type( $type ) {
		$query = new \WP_Query(
			array(
				'post_type'              => 'csf_form',
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => 'csf_form_type',
						'value' => $type,
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Count normal/contact forms including legacy forms without a type meta.
	 *
	 * @return int
	 */
	public function count_contact_forms() {
		$query = new \WP_Query(
			array(
				'post_type'              => 'csf_form',
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => 'csf_form_type',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => 'csf_form_type',
						'value' => '',
					),
					array(
						'key'   => 'csf_form_type',
						'value' => 'normal',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}
}

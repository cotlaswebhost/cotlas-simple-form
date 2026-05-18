<?php
/**
 * Submission data access helpers.
 *
 * @package CotlasSimpleForms
 */

namespace Cotlas\SimpleForms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads submission statistics from the existing csf_submission CPT.
 */
class SubmissionRepository {

	/**
	 * Count all stored submissions.
	 *
	 * @return int
	 */
	public function count_all() {
		$counts = wp_count_posts( 'csf_submission' );

		return ! empty( $counts->publish ) ? (int) $counts->publish : 0;
	}

	/**
	 * Get latest submissions.
	 *
	 * @param int $limit Number of submissions.
	 * @return array
	 */
	public function latest( $limit = 5 ) {
		return get_posts(
			array(
				'post_type'              => 'csf_submission',
				'post_status'            => 'publish',
				'posts_per_page'         => absint( $limit ),
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);
	}
}

<?php
/**
 * Content exporter class.
 *
 * @package JSONExport
 * @since   1.0.0
 */

namespace Calm\JSONExport;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the export process and file generation.
 *
 * @since 1.0.0
 */
class Exporter {

	/**
	 * Formatter instance.
	 *
	 * @since 1.0.0
	 * @var   Formatter
	 */
	private $formatter;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->formatter = new Formatter();
	}

	/**
	 * Exports posts as JSON.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $args    Query arguments for getting posts.
	 * @param  array $options Export options.
	 * @return array|\WP_Error Array containing posts and filename, or WP_Error on failure.
	 */
	public function export_posts( $args = array(), $options = array() ) {
		// Default query args.
		$default_args = array(
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'post_type'      => 'post',
		);

		// Merge with defaults.
     // @phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Args are validated upstream in Admin class.
		$args = wp_parse_args( $args, $default_args );

		// Default options.
		$default_options = array(
			'llm_friendly'         => false,
			'custom_excerpts_only' => false,
		);

		// Merge provided options with defaults for export settings.
		$options = wp_parse_args( $options, $default_options );

		// Get posts.
     // @phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Args sanitization and nonce check happen upstream in Admin class.
		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			return new \WP_Error( 'no_posts', __( 'No posts found matching your criteria.', 'json-export' ) );
		}

		// Build the posts array.
		$posts_array = $this->_build_posts_array( $posts, $options );

		// Create filename based on export type, including site name and timestamp.
		$site_name   = sanitize_title( get_bloginfo( 'name' ) );
		$date_string = current_time( 'Y-m-d-His' ); // Use YYYY-MM-DD-HHMMSS format.
		$base_prefix = $site_name . '-export';

		if ( isset( $args['category__in'] ) && is_array( $args['category__in'] ) && ! empty( $args['category__in'] ) ) {
         // @phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Category ID validated upstream.
			$category_id = reset( $args['category__in'] ); // Get the first category ID from the array.
			$category    = get_term( $category_id, 'category' );
			if ( ! $category || is_wp_error( $category ) ) {
				return new \WP_Error( 'invalid_category', __( 'Invalid category selected.', 'json-export' ) );
			}
			$category_slug = sanitize_title( $category->name );
			$filename      = $base_prefix . '-category-' . $category_slug . '-' . $date_string . '.json';
		} else {
			$filename = $base_prefix . '-all-' . $date_string . '.json';
		}

		// Build export metadata.
		$metadata = array(
			'export_date'    => current_time( 'mysql' ),
			'site_title'     => get_bloginfo( 'name' ),
			'site_url'       => get_bloginfo( 'url' ),
			'post_count'     => count( $posts_array ),
			'export_options' => array(
				'llm_friendly'         => $options['llm_friendly'],
				'custom_excerpts_only' => $options['custom_excerpts_only'],
				'export_type'          => isset( $args['category__in'] ) ? 'category' : 'all',
				'category_id'          => isset( $args['category__in'] ) && ! empty( $args['category__in'] ) ? reset( $args['category__in'] ) : null,
			),
			'wp_version'     => get_bloginfo( 'version' ),
			'plugin_version' => CALM_JSON_EXPORT_VERSION,
		);

		// Combine metadata and posts into a single array.
		$export_data = array(
			'metadata' => $metadata,
			'posts'    => $posts_array,
		);

		// Allow filtering of the exported data.
		$export_data = apply_filters( 'calm_json_export_data', $export_data, $args );

		return array(
			'data'     => $export_data,
			'filename' => $filename,
		);
	}

	/**
	 * Build the posts array for export.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $posts   Array of post objects.
	 * @param  array $options Export options.
	 * @return array Array of formatted posts.
	 */
	private function _build_posts_array( $posts, $options ) {
		$formatted_posts = array();

		// Default options check.
		$options = wp_parse_args(
			$options,
			array(
				'llm_friendly'         => false,
				'custom_excerpts_only' => false,
			)
		);

		foreach ( $posts as $post ) {
			// Basic post data.
			$post_data = array(
				'id'      => absint( $post->ID ),
				'title'   => html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, 'UTF-8' ),
				'date'    => get_the_date( 'Y-m-d', $post->ID ),
				'slug'    => sanitize_title( $post->post_name ),
				'content' => apply_filters( 'the_content', $post->post_content ),
				'author'  => get_the_author_meta( 'display_name', $post->post_author ),
				'url'     => esc_url( get_permalink( $post->ID ) ),
			);

			// Only get excerpt if needed.
			$has_custom_excerpt = has_excerpt( $post->ID );
			$include_excerpt    = ! $options['custom_excerpts_only'] || $has_custom_excerpt;
			$excerpt            = '';

			if ( $include_excerpt ) {
				$excerpt = $has_custom_excerpt ? $post->post_excerpt : wp_trim_words( $post->post_content, 55, ' ...' );
			}

			// Store original HTML content for word count before potential conversion.
			$original_content = $post_data['content'];

			// Calculate word count from the original content.
			$post_data['word_count'] = $this->formatter->get_word_count( $original_content );

			// Format if LLM friendly.
			if ( $options['llm_friendly'] ) {
				// Convert content to Markdown.
				$post_data['content'] = $this->formatter->convert_to_llm_friendly( $original_content );
				// Convert excerpt to Markdown.
				$excerpt = $this->formatter->convert_to_llm_friendly( $excerpt );
				// Set format to llm_friendly.
				$post_data['format'] = 'llm_friendly';
			} else {
				// If not LLM friendly, set format to html.
				$post_data['format'] = 'html';
			}

			// Only include excerpt if it's custom or if we're not filtering.
			if ( $include_excerpt ) {
				$post_data['excerpt'] = $excerpt; // Assign the potentially converted excerpt.
			}

			// Get categories and tags.
			$categories    = get_the_category( $post->ID );
			$category_data = array();
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $category ) {
					$category_data[] = array(
						'id'   => $category->term_id,
						'name' => $category->name,
						'slug' => $category->slug,
					);
				}
			}

			$tags     = get_the_tags( $post->ID );
			$tag_data = array();
			if ( ! empty( $tags ) ) {
				foreach ( $tags as $tag ) {
					$tag_data[] = array(
						'id'   => $tag->term_id,
						'name' => $tag->name,
						'slug' => $tag->slug,
					);
				}
			}

			// Only include non-empty category and tag arrays.
			if ( ! empty( $category_data ) ) {
				$post_data['categories'] = $category_data;
			}

			if ( ! empty( $tag_data ) ) {
				$post_data['tags'] = $tag_data;
			}

			$formatted_posts[] = $post_data;
		}

		wp_reset_postdata(); // Restore original post data.

		return $formatted_posts;
	}

	/**
	 * Outputs JSON file for download.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data     Data to encode and output.
	 * @param string $filename Filename for the download.
	 */
	public function output_json_file( array $data, string $filename ): void {
		// Prevent caching of the response.
		nocache_headers();

		// Set headers for file download.
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON data is properly structured, escaping here would break it.
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		// End execution.
		exit;
	}
}

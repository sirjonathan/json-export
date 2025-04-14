<?php
/**
 * Formatter Class
 *
 * @package JSONExport
 * @since   1.0.0
 */

namespace Calm\JSONExport;

use League\HTMLToMarkdown\HtmlConverter;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles content formatting for LLM-friendly output and utility functions.
 *
 * @since 1.0.0
 */
class Formatter {

	/**
	 * Converts HTML content to a basic Markdown format suitable for LLMs.
	 *
	 * This uses the league/html-to-markdown library for robust conversion.
	 *
	 * @param  string $content The HTML content to convert.
	 * @return string The converted content.
	 */
	public function convert_to_llm_friendly( string $content ): string {

		$converter = new HtmlConverter();

		// Perform the conversion.
		$markdown_content = $converter->convert( $content );

		// Return the pure Markdown content.
		return trim( $markdown_content );
	}

	/**
	 * Get word count from HTML content.
	 *
	 * @param  string $content HTML content.
	 * @return int Word count.
	 */
	public function get_word_count( string $content ): int {
		// Use wp_strip_all_tags to accurately count words in plain text from the original HTML.
		return str_word_count( wp_strip_all_tags( $content ) );
	}
}

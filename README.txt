=== JSON Export ===
Contributors: jonathanwold
Tags: json, export, llm, content, api
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Exports WordPress posts as JSON, with formatting options for use with LLMs.

== Description ==

JSON Export provides a simple way to export your WordPress posts as JSON data. Perfect for developers, content migration, or feeding content to language models (LLMs).

**Key Features:**

* Export all posts or by category
* Option to apply LLM-friendly formatting
* Option to include only custom excerpts
* Includes relevant Metadata

== Installation ==

1. Upload `json-export` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu
3. Go to Tools > JSON Export to use

== Usage ==

1. Select content to export (all posts or specific category)
2. Choose formatting options
3. Click "Download JSON File"

== Frequently Asked Questions ==

= What is LLM-friendly format? =

When enabled, this option converts the HTML content and excerpt of each post into Markdown using the `league/html-to-markdown` library. This provides a cleaner, more structured input for Large Language Models (LLMs) by converting common HTML tags into their Markdown equivalents.

= Does this work with custom post types? =

Currently, the plugin only exports standard WordPress posts.

== Changelog ==

= 1.0.1 =
* Addressed feedback regarding unique prefixes (see issue #2, props to @lukecarbis)
* Removed dev dependencies from /vendor

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release

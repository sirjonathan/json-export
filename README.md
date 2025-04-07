# JSON Export

Exports WordPress posts as JSON, with formatting options for use with LLMs

## Features

- Export all posts or by category
- Option to apply LLM-friendly formatting 
- Option to include only custom excerpts
- Includes relevant Metadata

## Installation

1. Upload `json-export` to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu
3. Go to Tools > JSON Export

## Usage

1. Select content to export
2. Choose formatting options
3. Click "Download JSON File"

## LLM-Friendly Formatting

When enabled, this option converts the HTML content and excerpt of each post into Markdown using the `league/html-to-markdown` library. This provides a cleaner, more structured input for Large Language Models (LLMs) by converting common HTML tags into their Markdown equivalents.

## Requirements

- WordPress 5.0+
- PHP 7.4+

## License

GPL v2 or later

## Credits

Originally inspired by a plugin by Doug Belshaw, rewritten with modern standards by Jonathan Wold using Windsurf and Claude 3.7 Sonnet with Gemini 2.5 Pro.

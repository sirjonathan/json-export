<?php
/**
 * Admin class.
 *
 * Handles admin UI and hooks for the JSON Export plugin.
 *
 * @package JSONExport
 * @since   1.0.0
 */

namespace Calm\JSONExport;

/**
 * Handles admin-specific functionality.
 */
class Admin {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Exporter instance.
	 *
	 * @var Exporter
	 */
	private $exporter;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 */
	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
		$this->exporter    = new Exporter();

		// Register hooks.
		$this->register_hooks();
	}

	/**
	 * Register admin hooks.
	 */
	private function register_hooks() {
		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Handle export requests.
		add_action( 'admin_init', array( $this, 'process_export_request' ) );

		// Enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Add plugin action links.
		add_filter( 'plugin_action_links_' . CALM_JSON_EXPORT_BASENAME, array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		// Only load on our plugin page (Tools -> JSON Export).
		if ( 'tools_page_' . $this->plugin_name !== $hook_suffix ) {
			return;
		}

		// Enqueue JavaScript.
		wp_enqueue_script(
			$this->plugin_name . '-admin-script',
			CALM_JSON_EXPORT_URL . 'assets/js/admin.js',
			array( 'jquery' ), // Dependency.
			CALM_JSON_EXPORT_VERSION,
			true // Load in footer.
		);
	}

	/**
	 * Add admin menu item.
	 */
	public function add_admin_menu() {
		add_management_page(
			esc_html__( 'JSON Export', 'calm-json-export' ),
			esc_html__( 'JSON Export', 'calm-json-export' ),
			'export',
			$this->plugin_name,
			array( $this, 'display_admin_page' )
		);
	}

	/**
	 * Display the admin page.
	 */
	public function display_admin_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'JSON Export', 'calm-json-export' ); ?></h1>

			<p><?php esc_html_e( 'When you click the button below, WordPress will generate a JSON file containing your selected posts for you to save to your computer.', 'calm-json-export' ); ?></p>

			<form method="post" action="">
				<?php wp_nonce_field( 'calm_json_export_nonce', 'calm_json_export_nonce' ); ?>

				<h2><?php esc_html_e( 'Choose what to export', 'calm-json-export' ); ?></h2>

				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Content to export', 'calm-json-export' ); ?></legend>
					<p>
						<label>
							<input type="radio" name="content_selection" value="all" checked="checked" aria-describedby="all-posts-desc" />
							<?php esc_html_e( 'All Posts', 'calm-json-export' ); ?>
						</label>
					</p>
					<p class="description" id="all-posts-desc"><?php esc_html_e( 'Exports all published posts.', 'calm-json-export' ); ?></p>

					<p>
						<label>
							<input type="radio" name="content_selection" value="category" aria-describedby="category-posts-desc" />
							<?php esc_html_e( 'Posts from Category', 'calm-json-export' ); ?>
						</label>
					</p>
					<p class="description" id="category-posts-desc"><?php esc_html_e( 'Exports published posts only from the selected category.', 'calm-json-export' ); ?></p>

					<div id="category-selection" class="export-filters" style="display: none;">
						<label for="category-select"><?php esc_html_e( 'Select Category:', 'calm-json-export' ); ?></label>
						<?php
						wp_dropdown_categories(
							array(
								'show_option_none' => esc_html__( 'Select a Category', 'calm-json-export' ),
								'name'             => 'category',
								'id'               => 'category-select',
								'orderby'          => 'name',
								'hierarchical'     => 1,
								'show_count'       => 1,
								'hide_empty'       => 0,
							)
						);
						?>
					</div>
				</fieldset>

				<h2><?php esc_html_e( 'Formatting options', 'calm-json-export' ); ?></h2>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'LLM Formatting Options', 'calm-json-export' ); ?></legend>
					<p>
						<label>
							<input type="checkbox" name="llm_friendly" value="1" checked="checked"/>
							<?php esc_html_e( 'LLM Friendly Formatting', 'calm-json-export' ); ?>
						</label>
					</p>
					<p class="description"><?php esc_html_e( 'Converts HTML to Markdown, better optimizing it for use by Large Language Models (LLMs).', 'calm-json-export' ); ?></p>

					<p>
						<label>
							<input type="checkbox" name="llm_custom_excerpts_only" value="1" checked="checked" />
							<?php esc_html_e( 'Custom Excerpts Only', 'calm-json-export' ); ?>
						</label>
					</p>
					<p class="description"><?php esc_html_e( 'Only include the excerpt field if a custom excerpt exists for the post.', 'calm-json-export' ); ?></p>

				</fieldset>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__( 'Download JSON File', 'calm-json-export' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Process export request and generate JSON file.
	 */
	public function process_export_request() {
		// Validate request method and permissions.
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! current_user_can( 'export' ) ) {
			return;
		}

		// Security check.
		if ( ! isset( $_POST['calm_json_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['calm_json_export_nonce'] ) ), 'calm_json_export_nonce' ) ) {
			return;
		}

		// Determine export type.
		$content_selection = isset( $_POST['content_selection'] ) ? sanitize_text_field( wp_unslash( $_POST['content_selection'] ) ) : '';
		$export_args       = array();
		$export_options    = array(
			'llm_friendly'         => isset( $_POST['llm_friendly'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['llm_friendly'] ) ),
			'include_headings'     => isset( $_POST['llm_include_headings'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['llm_include_headings'] ) ),
			'include_images'       => isset( $_POST['llm_include_images'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['llm_include_images'] ) ),
			'include_links'        => isset( $_POST['llm_include_links'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['llm_include_links'] ) ),
			'custom_excerpts_only' => isset( $_POST['llm_custom_excerpts_only'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['llm_custom_excerpts_only'] ) ),
		);

		// Set up query arguments based on selection.
		if ( 'category' === $content_selection && isset( $_POST['category'] ) ) {
			$category_id = absint( $_POST['category'] );
			$category    = get_term( $category_id, 'category' );

			if ( ! $category || is_wp_error( $category ) ) {
				wp_die( esc_html__( 'Invalid category selected.', 'calm-json-export' ), '', array( 'response' => 400 ) );
			}
			$export_args['category__in'] = array( $category_id );
		} else {
			// Default to exporting all posts if 'all' or nothing specific is chosen.
			$export_args['post_type'] = 'post'; // Ensure we are targeting posts.
		}

		// Export posts.
		$result = $this->exporter->export_posts( $export_args, $export_options );

		// Handle errors during export.
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 400 ) );
		}

		// Output the JSON file.
		$this->exporter->output_json_file( $result['data'], $result['filename'] );
	}

	/**
	 * Add plugin action links in the plugins list.
	 *
	 * @param array $links Plugin action links.
	 * @return array Modified action links.
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'tools.php?page=' . $this->plugin_name ),
			esc_html__( 'Export', 'calm-json-export' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}

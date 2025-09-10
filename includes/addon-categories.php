<?php
/**
 * BuddyPress Docs Categories Addon
 *
 * @package BuddyPress_Docs
 * @since 1.9.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for the Categories feature.
 */
class BP_Docs_Categories_Addon {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up the actions and filters.
	 */
	private function setup_hooks() {
		add_action( 'bp_docs_init', array( $this, 'register_taxonomy' ), 6 );
		add_action( 'bp_docs_after_doc_settings', array( $this, 'meta_box_html' ) );
		add_action( 'bp_docs_doc_saved', array( $this, 'save_categories' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		// Add categories to the Docs directory sidebar.
		add_action( 'bp_docs_directory_sidebar', array( $this, 'directory_widget_output' ) );
	}

	/**
	 * Register the bp_doc_category taxonomy.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Doc Categories', 'taxonomy general name', 'buddypress-docs' ),
			'singular_name'     => _x( 'Doc Category', 'taxonomy singular name', 'buddypress-docs' ),
			'search_items'      => __( 'Search Doc Categories', 'buddypress-docs' ),
			'all_items'         => __( 'All Doc Categories', 'buddypress-docs' ),
			'parent_item'       => __( 'Parent Doc Category', 'buddypress-docs' ),
			'parent_item_colon' => __( 'Parent Doc Category:', 'buddypress-docs' ),
			'edit_item'         => __( 'Edit Doc Category', 'buddypress-docs' ),
			'update_item'       => __( 'Update Doc Category', 'buddypress-docs' ),
			'add_new_item'      => __( 'Add New Doc Category', 'buddypress-docs' ),
			'new_item_name'     => __( 'New Doc Category Name', 'buddypress-docs' ),
			'menu_name'         => __( 'Categories', 'buddypress-docs' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => bp_docs_get_slug() . '/' . _x( 'category', 'category slug', 'buddypress-docs' ) ),
		);

		register_taxonomy( 'bp_doc_category', array( bp_docs_get_post_type_name() ), $args );
	}

	/**
	 * Output the HTML for the category meta box on the Doc edit screen.
	 *
	 * @param int $doc_id ID of the doc being edited.
	 */
	public function meta_box_html( $doc_id ) {
		$taxonomy = 'bp_doc_category';
		?>
		<div class="bp-docs-settings-section" id="bp-docs-category-settings">
			<h3 class="bp-docs-settings-section-title"><?php _e( 'Categories', 'buddypress-docs' ); ?></h3>
			<div class="bp-docs-settings-section-content">
				<div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>" class="categorydiv">
					<ul id="<?php echo esc_attr( $taxonomy ); ?>checklist" class="categorychecklist form-no-clear">
						<?php
						wp_terms_checklist(
							$doc_id,
							array(
								'taxonomy'      => $taxonomy,
								'popular_cats'  => false,
								'walker'        => new Walker_Category_Checklist(),
								'checked_ontop' => false,
							)
						);
						?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save category data when a Doc is saved.
	 *
	 * @param int $doc_id ID of the doc being saved.
	 */
	public function save_categories( $doc_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Nonce check is handled in BP_Docs_Query::save().
		if ( ! current_user_can( 'bp_docs_edit', $doc_id ) ) {
			return;
		}

		$taxonomy = 'bp_doc_category';

		// The tax_input is sent in a different format when using the classic editor vs the block editor.
        if ( isset( $_POST[ $taxonomy ] ) ) {
            $term_slugs = (array) $_POST[ $taxonomy ];
            $term_ids = array();
            foreach ( $term_slugs as $term_slug ) {
                $term = get_term_by( 'slug', $term_slug, $taxonomy );
                if ( $term ) {
                    $term_ids[] = $term->term_id;
                }
            }
            wp_set_object_terms( $doc_id, $term_ids, $taxonomy, false );
        } elseif ( isset( $_POST['tax_input'][ $taxonomy ] ) ) {
			$term_ids = array_map( 'intval', $_POST['tax_input'][ $taxonomy ] );
			wp_set_object_terms( $doc_id, $term_ids, $taxonomy, false );
		}
	}

	/**
	 * Register the categories widget.
	 */
	public function register_widget() {
		require_once dirname( __FILE__ ) . '/class-wp-widget-docs-categories.php';
		register_widget( 'BP_Docs_Categories_Widget' );
	}

	/**
	 * Display the category widget on the Docs directory sidebar.
	 * This is a non-widgetized way to show the categories list.
	 */
	public function directory_widget_output() {
		?>
		<div class="widget widget_bp_docs_categories">
			<h3 class="widget-title"><?php _e( 'Doc Categories', 'buddypress-docs' ); ?></h3>
			<ul>
				<?php
				wp_list_categories(
					array(
						'taxonomy'   => 'bp_doc_category',
						'title_li'   => '',
						'show_count' => 1,
					)
				);
				?>
			</ul>
		</div>
		<?php
	}
}
new BP_Docs_Categories_Addon();

/**
 * Template tag to display the categories for the current Doc.
 *
 * @param array $args See get_the_term_list() for arguments.
 */
function bp_docs_the_doc_categories( $args = array() ) {
	echo bp_docs_get_the_doc_categories( $args );
}

/**
 * Template tag to get the categories for the current Doc.
 *
 * @param array $args See get_the_term_list() for arguments.
 * @return string HTML list of categories.
 */
function bp_docs_get_the_doc_categories( $args = array() ) {
	$defaults = array(
		'before' => '',
		'sep'    => ', ',
		'after'  => '',
	);
	$r = wp_parse_args( $args, $defaults );

	return get_the_term_list( bp_docs_get_doc_id(), 'bp_doc_category', $r['before'], $r['sep'], $r['after'] );
}

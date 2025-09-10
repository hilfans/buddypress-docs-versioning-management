<?php
/**
 * BuddyPress Docs Categories Widget
 *
 * @package BuddyPress_Docs
 * @since 1.9.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A widget to display a list of BuddyPress Doc categories.
 */
class BP_Docs_Categories_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 */
	public function __construct() {
		parent::__construct(
			'bp_docs_categories_widget',
			__( 'BuddyPress Doc Categories', 'buddypress-docs' ),
			array( 'description' => __( 'A list of your site\'s BuddyPress Doc categories.', 'buddypress-docs' ) )
		);
	}

	/**
	 * The widget's front-end display.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 * 'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Doc Categories', 'buddypress-docs' ) : $instance['title'], $instance, $this->id_base );
		$c     = ! empty( $instance['count'] ) ? '1' : '0';
		$h     = ! empty( $instance['hierarchical'] ) ? '1' : '0';

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$cat_args = array(
			'orderby'      => 'name',
			'show_count'   => $c,
			'hierarchical' => $h,
			'taxonomy'     => 'bp_doc_category',
			'title_li'     => '',
		);
		?>
		<ul>
			<?php wp_list_categories( apply_filters( 'widget_categories_args', $cat_args, $instance ) ); ?>
		</ul>
		<?php
		echo $args['after_widget'];
	}

	/**
	 * The widget's back-end form.
	 *
	 * @param array $instance The widget options.
	 * @return string
	 */
	public function form( $instance ) {
		// Set up some default widget settings.
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'count' => 0, 'hierarchical' => 0 ) );
		$title = sanitize_text_field( $instance['title'] );
		$count = $instance['count'] ? 'checked="checked"' : '';
		$hierarchical = $instance['hierarchical'] ? 'checked="checked"' : '';
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddypress-docs' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" <?php echo $count; ?> />
		<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show post counts', 'buddypress-docs' ); ?></label><br />

		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'hierarchical' ); ?>" name="<?php echo $this->get_field_name( 'hierarchical' ); ?>" <?php echo $hierarchical; ?> />
		<label for="<?php echo $this->get_field_id( 'hierarchical' ); ?>"><?php _e( 'Show hierarchy', 'buddypress-docs' ); ?></label></p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                 = $old_instance;
		$instance['title']        = sanitize_text_field( $new_instance['title'] );
		$instance['count']        = ! empty( $new_instance['count'] ) ? 1 : 0;
		$instance['hierarchical'] = ! empty( $new_instance['hierarchical'] ) ? 1 : 0;

		return $instance;
	}
}

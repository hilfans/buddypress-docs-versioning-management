// ... existing code ...
 *
 * @since 1.2
 */
function bp_docs_register_widgets() {
	register_widget( 'BP_Docs_Recent_Docs_Widget' );
}
add_action( 'widgets_init', 'bp_docs_register_widgets' );


/**
// ... existing code ...

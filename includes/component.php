<?php
// ... existing code ...
class BP_Docs_Component extends BP_Component {
	// ... existing code ...
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		// ... existing code ...

		// Add a "History Log" tab to the main Docs directory.
		bp_core_new_subnav_item( array(
			'name'            => __( 'History Log', 'buddypress-docs' ),
			'slug'            => 'history',
			'parent_slug'     => $this->slug,
			'parent_url'      => $docs_link,
			'screen_function' => array( $this, 'screen_docs_history' ),
			'position'        => 50,
		) );

        if ( bp_is_active( 'groups' ) && bp_is_group() ) {
            // Add a "History Log" tab to the group's Docs section.
            $group = groups_get_current_group();
            bp_core_new_subnav_item( array(
                'name'            => __( 'History Log', 'buddypress-docs' ),
                'slug'            => 'history',
                'parent_slug'     => $this->slug,
                'parent_url'      => bp_get_group_permalink( $group ) . $this->slug . '/',
                'screen_function' => array( $this, 'screen_docs_history' ),
                'position'        => 50,
                'user_has_access' => $group->is_member, // Only group members can see.
            ) );
        }
	}

    /**
     * DigiWuz MSP ENHANCEMENT: Screen function for the history log.
     */
    public function screen_docs_history() {
        add_action( 'bp_template_content', array( $this, 'content_docs_history' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    /**
     * DigiWuz MSP ENHANCEMENT: Content for the history log screen.
     */
    public function content_docs_history() {
        bp_docs_locate_template( 'docs/history-log.php', true );
    }
}
// ... existing code ...


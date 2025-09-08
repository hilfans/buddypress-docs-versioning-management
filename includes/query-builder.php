<?php
/**
 * Query builder for BuddyPress Docs.
 *
 * @package BuddyPressDocs\Includes
 */

// ... (Existing code) ...

class BP_Docs_Query {
	// ... (Existing properties) ...

	public function do_query() {
		global $wpdb, $bp;

		// ... (Existing query setup) ...

		// ** DigiWuz MSP ENHANCEMENT: Filter by approval status **
		// Group admins and site admins can see all statuses.
		// Regular members can only see 'approved' docs.
		$group_id = 0;
		if ( bp_is_active( 'groups' ) && ! empty( $bp->groups->current_group->id ) ) {
			$group_id = $bp->groups->current_group->id;
		}

		if ( ! current_user_can( 'bp_docs_manage_in_group', $group_id ) ) {
			$this->query_vars['meta_query'][] = array(
				'key'     => 'bp_doc_status',
				'value'   => BP_DOCS_STATUS_APPROVED,
				'compare' => '=',
			);
            // Also handle docs that don't have the meta key yet (legacy content).
            $this->query_vars['meta_query']['relation'] = 'OR';
            $this->query_vars['meta_query'][] = array(
                'key' => 'bp_doc_status',
                'compare' => 'NOT EXISTS'
            );
		}

        // ** DigiWuz MSP ENHANCEMENT: Add taxonomy query for category filtering **
        if ( ! empty( $_GET['bp_docs_cat'] ) && is_numeric( $_GET['bp_docs_cat'] ) ) {
            $this->query_vars['tax_query'][] = array(
                'taxonomy' => BP_DOCS_CATEGORY_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => intval( $_GET['bp_docs_cat'] ),
            );
        }


		// Fallback to a WP_Query.
		if ( empty( $this->query_vars['suppress_filters'] ) ) {
			$this->query_vars['suppress_filters'] = false;
		}
		$this->query_obj = new WP_Query( $this->query_vars );

		// ... (Existing code) ...
	}

	// ... (Rest of the class) ...
}


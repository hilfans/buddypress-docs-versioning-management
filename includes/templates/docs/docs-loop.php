<?php
/**
 * The main template for the Docs loop.
 *
 * @package BuddyPressDocs
 */
?>
<?php if ( bp_docs_has_docs() ) : ?>

	<table class="doctable">
	<thead>
		<tr>
			<th class="title-cell"><?php _e( 'Title', 'buddypress-docs' ); ?></th>
			<th class="author-cell"><?php _e( 'Author', 'buddypress-docs' ); ?></th>
			<th class="created-date-cell"><?php _e( 'Created', 'buddypress-docs' ); ?></th>
			<th class="edited-date-cell"><?php _e( 'Last Edited', 'buddypress-docs' ); ?></th>
		</tr>
	</thead>

	<tbody>
	<?php while ( bp_docs_has_docs() ) : bp_docs_the_doc(); ?>
		<?php
			$doc_id = get_the_ID();
			$doc_status = bp_docs_get_doc_status( $doc_id );
		?>
		<tr>
			<td class="title-cell">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				<?php // ** DigiWuz MSP ENHANCEMENT: Display Doc Status ** ?>
				<?php if ( $doc_status === BP_DOCS_STATUS_DRAFT ) : ?>
					<span class="doc-status-label draft"><?php _e( '(Draft)', 'buddypress-docs' ); ?></span>
				<?php elseif ( $doc_status === BP_DOCS_STATUS_REJECTED ) : ?>
					<span class="doc-status-label rejected"><?php _e( '(Rejected)', 'buddypress-docs' ); ?></span>
				<?php endif; ?>

                <?php // ** DigiWuz MSP ENHANCEMENT: Display Categories ** ?>
                <div class="doc-categories">
                    <?php
                        echo get_the_term_list( get_the_ID(), BP_DOCS_CATEGORY_TAXONOMY, __( 'Categories: ', 'buddypress-docs' ), ', ' );
                    ?>
                </div>

				<div class="row-actions">
					<?php bp_docs_doc_action_links(); ?>
				</div>
			</td>
			<td class="author-cell">
				<a href="<?php echo bp_core_get_user_domain( get_the_author_meta( 'ID' ) ); ?>"><?php the_author(); ?></a>
			</td>
			<td class="created-date-cell">
				<?php echo get_the_date(); ?>
			</td>
			<td class="edited-date-cell">
				<?php echo bp_docs_get_last_edited_text(); ?>
			</td>
		</tr>
	<?php endwhile; ?>
	</tbody>
	</table>

	<div id="doc-pagination">
		<?php bp_docs_paginate_links(); ?>
	</div>

<?php else : ?>
	<div class="info" id="message">
		<p>
			<?php
			if ( bp_docs_is_my_docs_page() ) {
				_e( 'You have not created any Docs yet.', 'buddypress-docs' );
			} else {
				_e( 'There are no Docs to view.', 'buddypress-docs' );
			}
			?>
		</p>
	</div>
<?php endif; ?>


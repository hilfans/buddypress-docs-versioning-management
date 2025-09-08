<?php
/**
 * Single document view.
 *
 * @package BuddyPressDocs
 */
?>

<?php get_header(); ?>

<div id="content">
	<div class="padder">

		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
			<div id="bp-docs-single-content">
				<?php
					$doc_id = get_the_ID();
					$doc_status = bp_docs_get_doc_status( $doc_id );
					$group_id = bp_docs_get_associated_group_id( $doc_id );
				?>

				<?php // ** DigiWuz MSP ENHANCEMENT: Moderation & Status Display ** ?>

				<?php // 1. Show moderation panel for Admins on Draft documents. ?>
				<?php if ( $doc_status === BP_DOCS_STATUS_DRAFT && current_user_can( 'bp_docs_manage_in_group', $group_id ) ) : ?>
					<div class="bp-template-notice info">
						<p><?php _e( 'This document is a draft and is awaiting your review.', 'buddypress-docs' ); ?></p>
						<form class="bp-docs-moderation-form" method="post">
							<h4><?php _e( 'Moderation', 'buddypress-docs' ); ?></h4>
							<textarea name="rejection_reason" placeholder="<?php esc_attr_e( 'Optional: Provide a reason for rejection...', 'buddypress-docs' ); ?>"></textarea>
							<br/>
							<button type="submit" name="bp_docs_approve" class="button-primary"><?php _e( 'Approve', 'buddypress-docs' ); ?></button>
							<button type="submit" name="bp_docs_reject" class="button"><?php _e( 'Reject', 'buddypress-docs' ); ?></button>
							<input type="hidden" name="doc_id" value="<?php echo esc_attr( $doc_id ); ?>">
							<?php wp_nonce_field( 'bp_docs_status_change', 'bp_docs_status_nonce' ); ?>
						</form>
					</div>
				<?php endif; ?>

				<?php // 2. Show status notice for the author or other viewers. ?>
				<?php if ( $doc_status === BP_DOCS_STATUS_DRAFT && ! current_user_can( 'bp_docs_manage_in_group', $group_id ) ) : ?>
					<div class="bp-template-notice">
						<p><?php _e( 'This document is currently pending approval from a group administrator.', 'buddypress-docs' ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( $doc_status === BP_DOCS_STATUS_REJECTED ) : ?>
					<div class="bp-template-notice error">
						<p><strong><?php _e( 'This document has been rejected.', 'buddypress-docs' ); ?></strong></p>
						<?php
							$reason = get_post_meta( $doc_id, 'bp_doc_rejection_reason', true );
						if ( $reason ) :
							?>
							<p><strong><?php _e( 'Reason:', 'buddypress-docs' ); ?></strong> <?php echo esc_html( $reason ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>


				<div class="bp-doc-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<?php bp_docs_doc_meta(); ?>
				</div>

				<div class="bp-doc-content">
					<?php the_content(); ?>
				</div>

				<?php bp_docs_attachment_list(); ?>

				<?php comments_template( '', true ); ?>
			</div>
		<?php endwhile; endif; ?>

	</div><!-- .padder -->
</div><!-- #content -->

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer(); ?>


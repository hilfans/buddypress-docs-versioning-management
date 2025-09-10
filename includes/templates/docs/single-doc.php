<?php
/**
 * This template is used when a Doc is viewed.
 *
 * It will be loaded by the BP Docs component, and should not be called directly.
 *
 * @package BuddyPress_Docs
 * @since 1.0
 */

?>

<?php if ( bp_docs_has_docs( array( 'docs_slug' => bp_docs_get_current_doc_slug() ) ) ) : ?>
	<?php while ( bp_docs_docs() ) : bp_docs_the_doc(); ?>
		<?php
		$doc_id = get_the_ID();
		?>
		<div class="entry-title">
			<span class="doc-title-text"><?php the_title(); ?></span>
			<span class="doc-buttons">
				<?php bp_docs_header_tabs(); ?>
			</span>
		</div>

		<div class="doc-content">
			<?php the_content(); ?>

			<?php if ( bp_docs_get_doc_tags() ) : ?>
				<div class="doc-tags">
					<?php bp_docs_the_doc_tags(); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="entry-utility">
			<div class="doc-author">
				<?php printf( __( 'Created by %1$s', 'buddypress-docs' ), bp_core_get_userlink( $post->post_author ) ); ?>
			</div>

			<div class="doc-last-edited-by">
				<?php echo bp_docs_get_last_edited_by_text(); ?>
			</div>

			<?php
			$category_list = bp_docs_get_the_doc_categories(
				array(
					'before' => '<div class="doc-categories">' . __( 'Categories:', 'buddypress-docs' ) . ' ',
					'after'  => '</div>',
				)
			);

			if ( $category_list ) {
				echo $category_list;
			}
			?>
		</div><!-- .entry-utility -->
	<?php endwhile; ?>

<?php else : ?>
	<?php
	// 404
	?>
<?php endif; ?>

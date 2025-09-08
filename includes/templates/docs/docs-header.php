<?php do_action( 'bp_docs_before_doc_header' ) ?>

<?php /* Subnavigation on user pages is handled by BP's core functions */ ?>
<?php if ( ! bp_is_user() ): ?>
	<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
		<?php bp_docs_tabs( current_user_can( 'bp_docs_create' ) ); ?>
	</div><!-- .item-list-tabs -->
<?php endif ?>

<?php do_action( 'bp_docs_before_doc_header_content' ) ?>

<?php if ( bp_docs_is_existing_doc() ) : ?>

	<div id="bp-docs-single-doc-header">
		<?php if ( ! bp_docs_is_theme_compat_active() ) : ?>
			<h2 class="doc-title"><?php bp_docs_the_breadcrumb() ?><?php if ( bp_docs_is_doc_trashed() ) : ?> <span class="bp-docs-trashed-doc-notice" title="<?php esc_html_e( 'This Doc is in the Trash', 'buddypress-docs' ) ?>"><?php esc_html_e( 'Trash', 'buddypress-docs' ); ?></span><?php endif ?></h2>
		<?php endif ?>

		<?php do_action( 'bp_docs_single_doc_header_fields' ) ?>
	</div>

	<div class="doc-tabs">
		<ul>
			<li<?php if ( bp_docs_is_doc_read() ) : ?> class="current"<?php endif ?>>
				<a href="<?php bp_docs_doc_link() ?>"><?php _e( 'Read', 'buddypress-docs' ) ?></a>
			</li>

			<?php if ( current_user_can( 'bp_docs_edit' ) ) : ?>
				<li<?php if ( bp_docs_is_doc_edit() ) : ?> class="current"<?php endif ?>>
					<a href="<?php bp_docs_doc_edit_link() ?>"><?php _e( 'Edit', 'buddypress-docs' ) ?></a>
				</li>
			<?php endif ?>

			<?php do_action( 'bp_docs_header_tabs' ) ?>
		</ul>
	</div>

<?php elseif ( bp_docs_is_doc_create() ) : ?>

	<h2><?php _e( 'New Doc', 'buddypress-docs' ); ?></h2>

<?php endif ?>

<?php do_action( 'bp_docs_after_doc_header_content' ) ?>

<div class="docs-header-widgets">
    <div id="docs-header-widgets-left">
		<?php // ** DigiWuz MSP ENHANCEMENT: Add Category Filter Dropdown ** ?>
		<div class="widget">
			<form action="" method="get" class="category-filter-form">
				<label for="bp_docs_cat_filter" class="screen-reader-text"><?php _e( 'Filter by Category', 'buddypress-docs' ); ?></label>
				<?php
				wp_dropdown_categories( array(
					'taxonomy'         => BP_DOCS_CATEGORY_TAXONOMY,
					'name'             => 'bp_docs_cat',
					'id'               => 'bp_docs_cat_filter',
					'show_option_all'  => __( 'All Categories', 'buddypress-docs' ),
					'hierarchical'     => true,
					'orderby'          => 'name',
					'selected'         => isset( $_GET['bp_docs_cat'] ) ? intval( $_GET['bp_docs_cat'] ) : 0,
				) );
				?>
				<input type="submit" value="<?php esc_attr_e( 'Filter', 'buddypress-docs' ); ?>">
			</form>
		</div>
    </div>
    <div id="docs-header-widgets-right">
        <?php bp_docs_header_widgets(); ?>
    </div>
</div>

<div class="item-list-tabs">

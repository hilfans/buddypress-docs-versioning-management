<?php
/**
 * Frontend History Log view.
 *
 * @package BuddyPressDocs
 */
?>
<h4><?php _e( 'Document History Log', 'buddypress-docs' ); ?></h4>

<?php
global $wpdb;
$table = bp_docs_get_log_table_name();
$paged = ( bp_action_variable( 0 ) && is_numeric( bp_action_variable( 0 ) ) ) ? bp_action_variable( 0 ) : 1;
$per_page = 15;
$offset = ( $paged - 1 ) * $per_page;
$where_clauses = array();

// Check if we are in a group context.
$group_id = bp_is_active('groups') && bp_is_group() ? bp_get_current_group_id() : 0;
if ( $group_id ) {
    $where_clauses[] = $wpdb->prepare( "group_id = %d", $group_id );
}

$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$where_sql} ORDER BY log_date DESC LIMIT %d OFFSET %d", $per_page, $offset ) );

$total_logs = $wpdb->get_var( "SELECT COUNT(log_id) FROM {$table} {$where_sql}" );

if ( $logs ) : ?>
	<table class="doctable">
		<thead>
			<tr>
				<th><?php _e( 'Document', 'buddypress-docs' ); ?></th>
				<th><?php _e( 'User', 'buddypress-docs' ); ?></th>
				<th><?php _e( 'Action', 'buddypress-docs' ); ?></th>
				<th><?php _e( 'Date', 'buddypress-docs' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $logs as $log ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( get_permalink( $log->doc_id ) ); ?>"><?php echo esc_html( get_the_title( $log->doc_id ) ); ?></a></td>
					<td><?php echo bp_core_get_userlink( $log->user_id ); ?></td>
					<td>
						<?php echo esc_html( ucwords( str_replace( '_', ' ', $log->action ) ) ); ?>
						<?php if ( ! empty( $log->details ) ) : ?>
							<p class="history-details"><em><?php echo esc_html( $log->details ); ?></em></p>
						<?php endif; ?>
					</td>
					<td><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->log_date ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php
		// Simple pagination.
		$total_pages = ceil( $total_logs / $per_page );
	if ( $total_pages > 1 ) {
		echo '<div id="doc-pagination" class="pagination">';
		for ( $i = 1; $i <= $total_pages; $i++ ) {
			$class = ( $paged == $i ) ? 'current' : '';
			$base_url = bp_get_group_permalink( groups_get_current_group() ) . 'docs/history/';
			echo '<a href="' . esc_url( $base_url . $i ) . '" class="' . esc_attr( $class ) . '">' . $i . '</a> ';
		}
		echo '</div>';
	}
	?>

<?php else: ?>
	<div class="info" id="message"><p><?php _e( 'No history found for this view.', 'buddypress-docs' ); ?></p></div>
<?php endif; ?>


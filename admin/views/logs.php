<?php
/**
 * FlowSoft WP — Logs View
 *
 * Activity logs table with filters and pagination.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$logger      = FlowSoft_Logger::get_instance();
$filter_mod  = isset( $_GET['filter_module'] ) ? sanitize_text_field( $_GET['filter_module'] ) : '';
$filter_stat = isset( $_GET['filter_status'] ) ? sanitize_text_field( $_GET['filter_status'] ) : '';
$current_pg  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page    = 20;

$log_args = array(
    'module'   => $filter_mod,
    'status'   => $filter_stat,
    'per_page' => $per_page,
    'page'     => $current_pg,
);

$logs        = $logger->get_logs( $log_args );
$total_logs  = $logger->get_logs_count( $log_args );
$total_pages = ceil( $total_logs / $per_page );

$module_names = array(
    'database'   => __( 'Base de Datos', 'flowsoft-wp' ),
    'transients' => __( 'Transients', 'flowsoft-wp' ),
    'heartbeat'  => __( 'Heartbeat', 'flowsoft-wp' ),
    'revisions'  => __( 'Revisiones', 'flowsoft-wp' ),
    'assets'     => __( 'Assets', 'flowsoft-wp' ),
    'cron'       => __( 'Cron', 'flowsoft-wp' ),
    'media'      => __( 'Medios', 'flowsoft-wp' ),
);
?>

<div class="flowsoft-page-header">
    <div class="flowsoft-page-header__left">
        <h2 class="flowsoft-page-title"><?php esc_html_e( 'Registros de Actividad', 'flowsoft-wp' ); ?></h2>
        <p class="flowsoft-page-desc">
            <?php printf( esc_html__( 'Mostrando %d de %d registros totales', 'flowsoft-wp' ), count( $logs ), $total_logs ); ?>
        </p>
    </div>
    <div class="flowsoft-page-header__right">
        <button class="flowsoft-btn flowsoft-btn--danger flowsoft-btn--sm" id="flowsoft-clear-logs">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
            <?php esc_html_e( 'Borrar Registros', 'flowsoft-wp' ); ?>
        </button>
    </div>
</div>

<!-- Filters -->
<div class="flowsoft-card">
    <div class="flowsoft-card__body">
        <form method="get" class="flowsoft-filters">
            <input type="hidden" name="page" value="flowsoft-wp" />
            <input type="hidden" name="tab" value="logs" />

            <div class="flowsoft-filter-group">
                <label class="flowsoft-filter-label"><?php esc_html_e( 'Módulo', 'flowsoft-wp' ); ?></label>
                <select name="filter_module" class="flowsoft-select">
                    <option value=""><?php esc_html_e( 'Todos los Módulos', 'flowsoft-wp' ); ?></option>
                    <?php foreach ( $module_names as $key => $name ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filter_mod, $key ); ?>>
                        <?php echo esc_html( $name ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flowsoft-filter-group">
                <label class="flowsoft-filter-label"><?php esc_html_e( 'Estado', 'flowsoft-wp' ); ?></label>
                <select name="filter_status" class="flowsoft-select">
                    <option value=""><?php esc_html_e( 'Todos', 'flowsoft-wp' ); ?></option>
                    <option value="success" <?php selected( $filter_stat, 'success' ); ?>><?php esc_html_e( 'Éxito', 'flowsoft-wp' ); ?></option>
                    <option value="warning" <?php selected( $filter_stat, 'warning' ); ?>><?php esc_html_e( 'Advertencia', 'flowsoft-wp' ); ?></option>
                    <option value="error" <?php selected( $filter_stat, 'error' ); ?>><?php esc_html_e( 'Error', 'flowsoft-wp' ); ?></option>
                </select>
            </div>

            <button type="submit" class="flowsoft-btn flowsoft-btn--secondary flowsoft-btn--sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <?php esc_html_e( 'Filtrar', 'flowsoft-wp' ); ?>
            </button>

            <?php if ( $filter_mod || $filter_stat ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flowsoft-wp&tab=logs' ) ); ?>" class="flowsoft-btn flowsoft-btn--ghost flowsoft-btn--sm">
                <?php esc_html_e( 'Limpiar Filtros', 'flowsoft-wp' ); ?>
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="flowsoft-card">
    <div class="flowsoft-card__body flowsoft-card__body--no-padding">
        <?php if ( ! empty( $logs ) ) : ?>
        <table class="flowsoft-table flowsoft-table--striped" id="flowsoft-logs-table">
            <thead>
                <tr>
                    <th class="flowsoft-table__th--narrow">#</th>
                    <th><?php esc_html_e( 'Módulo', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Acción', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Mensaje', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Items', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Liberado', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Estado', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Fecha', 'flowsoft-wp' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $logs as $log ) : ?>
                <tr>
                    <td class="flowsoft-table__td--muted"><?php echo esc_html( $log->id ); ?></td>
                    <td>
                        <span class="flowsoft-module-badge">
                            <?php echo esc_html( isset( $module_names[ $log->module ] ) ? $module_names[ $log->module ] : $log->module ); ?>
                        </span>
                    </td>
                    <td><code class="flowsoft-code"><?php echo esc_html( $log->action_type ); ?></code></td>
                    <td class="flowsoft-table__message"><?php echo esc_html( $log->message ); ?></td>
                    <td><?php echo esc_html( number_format_i18n( $log->items_affected ) ); ?></td>
                    <td><?php echo $log->bytes_freed > 0 ? esc_html( size_format( $log->bytes_freed ) ) : '—'; ?></td>
                    <td>
                        <span class="flowsoft-status-badge flowsoft-status-badge--<?php echo esc_attr( $log->status ); ?>">
                            <?php echo esc_html( ucfirst( $log->status ) ); ?>
                        </span>
                    </td>
                    <td class="flowsoft-table__date">
                        <span title="<?php echo esc_attr( $log->created_at ); ?>">
                            <?php echo esc_html( human_time_diff( strtotime( $log->created_at ) ) . ' atrás' ); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
        <div class="flowsoft-pagination">
            <span class="flowsoft-pagination__info">
                <?php printf( esc_html__( 'Página %d de %d', 'flowsoft-wp' ), $current_pg, $total_pages ); ?>
            </span>
            <div class="flowsoft-pagination__buttons">
                <?php if ( $current_pg > 1 ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $current_pg - 1 ) ); ?>" class="flowsoft-btn flowsoft-btn--ghost flowsoft-btn--sm">
                    ← <?php esc_html_e( 'Anterior', 'flowsoft-wp' ); ?>
                </a>
                <?php endif; ?>

                <?php for ( $i = max( 1, $current_pg - 2 ); $i <= min( $total_pages, $current_pg + 2 ); $i++ ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>" 
                   class="flowsoft-btn flowsoft-btn--sm <?php echo $i === $current_pg ? 'flowsoft-btn--primary' : 'flowsoft-btn--ghost'; ?>">
                    <?php echo esc_html( $i ); ?>
                </a>
                <?php endfor; ?>

                <?php if ( $current_pg < $total_pages ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $current_pg + 1 ) ); ?>" class="flowsoft-btn flowsoft-btn--ghost flowsoft-btn--sm">
                    <?php esc_html_e( 'Siguiente', 'flowsoft-wp' ); ?> →
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else : ?>
        <div class="flowsoft-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p><?php esc_html_e( 'No se encontraron registros que coincidan con los filtros actuales.', 'flowsoft-wp' ); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

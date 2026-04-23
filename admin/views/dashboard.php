<?php
/**
 * FlowSoft WP — Dashboard View
 *
 * Main dashboard with health score, stats cards, and recent activity.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$logger         = FlowSoft_Logger::get_instance();
$dash_stats     = $logger->get_dashboard_stats();
$recent_logs    = $logger->get_logs( array( 'per_page' => 8 ) );
$scheduler      = FlowSoft_Scheduler::get_instance();

// Module info for status grid
$module_info = array(
    'database'   => array( 'name' => __( 'Base de Datos', 'flowsoft-wp' ),   'hook' => 'flowsoft_daily_optimization' ),
    'transients' => array( 'name' => __( 'Transients', 'flowsoft-wp' ), 'hook' => 'flowsoft_sixhours_optimization' ),
    'heartbeat'  => array( 'name' => __( 'Heartbeat', 'flowsoft-wp' ),  'hook' => '' ),
    'revisions'  => array( 'name' => __( 'Revisiones', 'flowsoft-wp' ), 'hook' => 'flowsoft_weekly_optimization' ),
    'assets'     => array( 'name' => __( 'Assets', 'flowsoft-wp' ),     'hook' => '' ),
    'cron'       => array( 'name' => __( 'Cron', 'flowsoft-wp' ),       'hook' => 'flowsoft_daily_optimization' ),
    'media'      => array( 'name' => __( 'Medios', 'flowsoft-wp' ),      'hook' => 'flowsoft_weekly_optimization' ),
    'cache'      => array( 'name' => __( 'Caché', 'flowsoft-wp' ),       'hook' => 'flowsoft_sixhours_optimization' ),
);

// Icon map
$module_icons = array(
    'database'   => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>',
    'transients' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M2 12h20"/><circle cx="12" cy="12" r="10"/></svg>',
    'heartbeat'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/></svg>',
    'revisions'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>',
    'assets'     => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/></svg>',
    'cron'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'media'      => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>',
    'cache'      => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/><circle cx="12" cy="12" r="4"/></svg>',
);

$total_bytes   = isset( $dash_stats['all_time']['total_bytes_freed'] ) ? $dash_stats['all_time']['total_bytes_freed'] : 0;
$total_items   = isset( $dash_stats['all_time']['total_items_cleaned'] ) ? $dash_stats['all_time']['total_items_cleaned'] : 0;
$total_ops     = isset( $dash_stats['all_time']['total_optimizations'] ) ? $dash_stats['all_time']['total_optimizations'] : 0;
$last_opt      = isset( $dash_stats['all_time']['last_optimization'] ) ? $dash_stats['all_time']['last_optimization'] : '';
?>

<!-- Stats Cards -->
<div class="flowsoft-stats-row">
    <div class="flowsoft-stat-card flowsoft-stat-card--health">
        <div class="flowsoft-stat-card__icon-wrap flowsoft-stat-card__icon-wrap--indigo">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="flowsoft-stat-card__content">
            <span class="flowsoft-stat-card__label"><?php esc_html_e( 'Puntuación de Salud', 'flowsoft-wp' ); ?></span>
            <span class="flowsoft-stat-card__value" id="flowsoft-health-value" data-score="<?php echo esc_attr( $health ); ?>"><?php echo esc_html( $health ); ?>%</span>
        </div>
        <div class="flowsoft-stat-card__gauge" id="flowsoft-health-gauge">
            <svg viewBox="0 0 120 120" class="flowsoft-gauge-svg">
                <circle cx="60" cy="60" r="50" class="flowsoft-gauge-bg" />
                <circle cx="60" cy="60" r="50" class="flowsoft-gauge-fill" data-value="<?php echo esc_attr( $health ); ?>" />
            </svg>
            <span class="flowsoft-gauge-text"><?php echo esc_html( $health ); ?></span>
        </div>
    </div>

    <div class="flowsoft-stat-card">
        <div class="flowsoft-stat-card__icon-wrap flowsoft-stat-card__icon-wrap--emerald">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" x2="12" y1="22" y2="12"/></svg>
        </div>
        <div class="flowsoft-stat-card__content">
            <span class="flowsoft-stat-card__label"><?php esc_html_e( 'Espacio Liberado', 'flowsoft-wp' ); ?></span>
            <span class="flowsoft-stat-card__value"><?php echo esc_html( size_format( $total_bytes ) ); ?></span>
        </div>
    </div>

    <div class="flowsoft-stat-card">
        <div class="flowsoft-stat-card__icon-wrap flowsoft-stat-card__icon-wrap--amber">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
        </div>
        <div class="flowsoft-stat-card__content">
            <span class="flowsoft-stat-card__label"><?php esc_html_e( 'Items Limpiados', 'flowsoft-wp' ); ?></span>
            <span class="flowsoft-stat-card__value"><?php echo esc_html( number_format_i18n( $total_items ) ); ?></span>
        </div>
    </div>

    <div class="flowsoft-stat-card">
        <div class="flowsoft-stat-card__icon-wrap flowsoft-stat-card__icon-wrap--rose">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
        <div class="flowsoft-stat-card__content">
            <span class="flowsoft-stat-card__label"><?php esc_html_e( 'Optimizaciones', 'flowsoft-wp' ); ?></span>
            <span class="flowsoft-stat-card__value"><?php echo esc_html( number_format_i18n( $total_ops ) ); ?></span>
        </div>
    </div>
</div>

<!-- Two Column Layout: Module Status + Quick Actions -->
<div class="flowsoft-dashboard-grid">
    <!-- Module Status -->
    <div class="flowsoft-card">
        <div class="flowsoft-card__header">
            <h2 class="flowsoft-card__title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                <?php esc_html_e( 'Estado de Módulos', 'flowsoft-wp' ); ?>
            </h2>
        </div>
        <div class="flowsoft-card__body">
            <div class="flowsoft-module-grid">
                <?php foreach ( $module_info as $mod_id => $mod_data ) :
                    $is_enabled  = ! empty( $options[ $mod_id ]['enabled'] );
                    $next_run    = ! empty( $mod_data['hook'] ) ? $scheduler->get_next_run( $mod_data['hook'] ) : __( 'Tiempo real', 'flowsoft-wp' );
                ?>
                <div class="flowsoft-module-status-card <?php echo $is_enabled ? 'is-enabled' : 'is-disabled'; ?>"
                     data-module="<?php echo esc_attr( $mod_id ); ?>">
                    <div class="flowsoft-module-status-card__icon">
                        <?php echo $module_icons[ $mod_id ]; ?>
                    </div>
                    <div class="flowsoft-module-status-card__info">
                        <span class="flowsoft-module-status-card__name"><?php echo esc_html( $mod_data['name'] ); ?></span>
                        <span class="flowsoft-module-status-card__next">
                            <?php echo $next_run ? esc_html( $next_run ) : esc_html__( 'No programado', 'flowsoft-wp' ); ?>
                        </span>
                    </div>
                    <span class="flowsoft-module-status-card__badge <?php echo $is_enabled ? 'is-on' : 'is-off'; ?>">
                        <?php echo $is_enabled ? esc_html__( 'ON', 'flowsoft-wp' ) : esc_html__( 'OFF', 'flowsoft-wp' ); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="flowsoft-card">
        <div class="flowsoft-card__header">
            <h2 class="flowsoft-card__title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                <?php esc_html_e( 'Acciones Rápidas', 'flowsoft-wp' ); ?>
            </h2>
        </div>
        <div class="flowsoft-card__body">
            <div class="flowsoft-quick-actions">
                <button class="flowsoft-btn flowsoft-btn--primary flowsoft-btn--block" id="flowsoft-run-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    <?php esc_html_e( 'Ejecutar Todas las Optimizaciones', 'flowsoft-wp' ); ?>
                </button>

                <button class="flowsoft-btn flowsoft-btn--secondary flowsoft-btn--block" data-run-module="database">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
                    <?php esc_html_e( 'Optimizar Base de Datos', 'flowsoft-wp' ); ?>
                </button>

                <button class="flowsoft-btn flowsoft-btn--secondary flowsoft-btn--block" data-run-module="transients">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    <?php esc_html_e( 'Limpiar Transients', 'flowsoft-wp' ); ?>
                </button>

                <button class="flowsoft-btn flowsoft-btn--secondary flowsoft-btn--block" data-run-module="cron">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php esc_html_e( 'Verificar Salud del Cron', 'flowsoft-wp' ); ?>
                </button>

                <?php if ( ! empty( $last_opt ) ) : ?>
                <div class="flowsoft-last-run">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php printf( esc_html__( 'Última ejecución: %s', 'flowsoft-wp' ), esc_html( human_time_diff( strtotime( $last_opt ) ) . ' atrás' ) ); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="flowsoft-card">
    <div class="flowsoft-card__header">
        <h2 class="flowsoft-card__title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
            <?php esc_html_e( 'Actividad Reciente', 'flowsoft-wp' ); ?>
        </h2>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flowsoft-wp&tab=logs' ) ); ?>" class="flowsoft-card__link">
            <?php esc_html_e( 'Ver Todo', 'flowsoft-wp' ); ?> →
        </a>
    </div>
    <div class="flowsoft-card__body flowsoft-card__body--no-padding">
        <?php if ( ! empty( $recent_logs ) ) : ?>
        <table class="flowsoft-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Módulo', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Acción', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Mensaje', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Items', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Estado', 'flowsoft-wp' ); ?></th>
                    <th><?php esc_html_e( 'Fecha', 'flowsoft-wp' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $recent_logs as $log ) : ?>
                <tr>
                    <td>
                        <span class="flowsoft-module-badge">
                            <?php if ( isset( $module_icons[ $log->module ] ) ) echo $module_icons[ $log->module ]; ?>
                            <?php echo esc_html( ucfirst( $log->module ) ); ?>
                        </span>
                    </td>
                    <td><code><?php echo esc_html( $log->action_type ); ?></code></td>
                    <td class="flowsoft-table__message"><?php echo esc_html( $log->message ); ?></td>
                    <td><?php echo esc_html( number_format_i18n( $log->items_affected ) ); ?></td>
                    <td>
                        <span class="flowsoft-status-badge flowsoft-status-badge--<?php echo esc_attr( $log->status ); ?>">
                            <?php echo esc_html( ucfirst( $log->status ) ); ?>
                        </span>
                    </td>
                    <td class="flowsoft-table__date"><?php echo esc_html( human_time_diff( strtotime( $log->created_at ) ) . ' atrás' ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="flowsoft-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p><?php esc_html_e( 'Aún no se ha registrado actividad. Las optimizaciones se registrarán aquí automáticamente.', 'flowsoft-wp' ); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

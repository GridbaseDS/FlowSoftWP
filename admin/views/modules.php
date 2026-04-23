<?php
/**
 * FlowSoft WP — Modules View
 *
 * Simple module cards with toggle and run button.
 * Settings are managed in the Configuración tab.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$core      = FlowSoft_Core::get_instance();
$all_mods  = $core->get_modules();
$options   = get_option( 'flowsoft_modules', array() );

$schedule_labels = array(
    'immediate' => __( 'Tiempo real', 'flowsoft-wp' ),
    'daily'     => __( 'Diario', 'flowsoft-wp' ),
    'sixhours'  => __( 'Cada 6 horas', 'flowsoft-wp' ),
    'weekly'    => __( 'Semanal', 'flowsoft-wp' ),
);
?>

<div class="flowsoft-page-header">
    <h2 class="flowsoft-page-title"><?php esc_html_e( 'Módulos de Optimización', 'flowsoft-wp' ); ?></h2>
    <p class="flowsoft-page-desc"><?php esc_html_e( 'Activa y ejecuta cada módulo de optimización de tu sitio.', 'flowsoft-wp' ); ?></p>
</div>

<div class="flowsoft-modules-grid">
    <?php foreach ( $all_mods as $mod_id => $module ) :
        $is_enabled   = ! empty( $options[ $mod_id ]['enabled'] );
        $mod_stats    = $module->get_stats();
        $schedule     = $module->get_schedule();
        $schedule_str = isset( $schedule_labels[ $schedule ] ) ? $schedule_labels[ $schedule ] : $schedule;
    ?>
    <div class="flowsoft-module-card <?php echo $is_enabled ? 'is-enabled' : 'is-disabled'; ?>" data-module="<?php echo esc_attr( $mod_id ); ?>">
        <div class="flowsoft-module-card__header">
            <div class="flowsoft-module-card__icon">
                <?php echo $module->get_icon(); ?>
            </div>
            <div class="flowsoft-module-card__title-wrap">
                <h3 class="flowsoft-module-card__title"><?php echo esc_html( $module->get_name() ); ?></h3>
                <span class="flowsoft-module-card__schedule">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo esc_html( $schedule_str ); ?>
                </span>
            </div>
            <label class="flowsoft-toggle" for="flowsoft-toggle-<?php echo esc_attr( $mod_id ); ?>">
                <input type="checkbox" id="flowsoft-toggle-<?php echo esc_attr( $mod_id ); ?>" 
                       class="flowsoft-toggle__input" 
                       data-module-toggle="<?php echo esc_attr( $mod_id ); ?>"
                       <?php checked( $is_enabled ); ?> />
                <span class="flowsoft-toggle__slider"></span>
            </label>
        </div>

        <p class="flowsoft-module-card__desc"><?php echo esc_html( $module->get_description() ); ?></p>

        <div class="flowsoft-module-card__stats">
            <?php
            $stat_count = 0;
            foreach ( $mod_stats as $stat_key => $stat_value ) :
                if ( $stat_count >= 3 ) break;
                $label = ucwords( str_replace( '_', ' ', $stat_key ) );
                if ( is_bool( $stat_value ) ) {
                    $display = $stat_value ? 'Sí' : 'No';
                } elseif ( is_numeric( $stat_value ) && $stat_value > 1000 ) {
                    $display = number_format_i18n( $stat_value );
                } else {
                    $display = $stat_value;
                }
            ?>
            <div class="flowsoft-module-card__stat">
                <span class="flowsoft-module-card__stat-value"><?php echo esc_html( $display ); ?></span>
                <span class="flowsoft-module-card__stat-label"><?php echo esc_html( $label ); ?></span>
            </div>
            <?php 
                $stat_count++;
            endforeach; ?>
        </div>

        <div class="flowsoft-module-card__actions">
            <button type="button" class="flowsoft-btn flowsoft-btn--sm flowsoft-btn--primary" 
                    data-run-module="<?php echo esc_attr( $mod_id ); ?>"
                    <?php echo ! $is_enabled ? 'disabled' : ''; ?>>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <?php esc_html_e( 'Ejecutar Ahora', 'flowsoft-wp' ); ?>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

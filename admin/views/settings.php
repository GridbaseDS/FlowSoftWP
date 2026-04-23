<?php
/**
 * FlowSoft WP — Settings View
 *
 * Global settings with module-specific configuration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$core    = FlowSoft_Core::get_instance();
$modules = $core->get_modules();
$options = get_option( 'flowsoft_modules', array() );
?>

<div class="flowsoft-page-header">
    <h2 class="flowsoft-page-title"><?php esc_html_e( 'Configuración', 'flowsoft-wp' ); ?></h2>
    <p class="flowsoft-page-desc"><?php esc_html_e( 'Configura cada módulo de optimización individualmente.', 'flowsoft-wp' ); ?></p>
</div>

<form id="flowsoft-settings-form">
    <?php wp_nonce_field( 'flowsoft_nonce', 'flowsoft_settings_nonce', false ); ?>
    <?php foreach ( $modules as $mod_id => $module ) :
        $fields    = $module->get_settings_fields();
        $mod_opts  = isset( $options[ $mod_id ] ) ? $options[ $mod_id ] : array();

        if ( empty( $fields ) ) continue;
    ?>
    <div class="flowsoft-card flowsoft-settings-section">
        <div class="flowsoft-card__header">
            <div class="flowsoft-settings-section__title-wrap">
                <span class="flowsoft-settings-section__icon"><?php echo $module->get_icon(); ?></span>
                <h3 class="flowsoft-card__title"><?php echo esc_html( $module->get_name() ); ?></h3>
            </div>
        </div>
        <div class="flowsoft-card__body">
            <div class="flowsoft-settings-fields">
                <?php foreach ( $fields as $field ) :
                    $field_id    = $mod_id . '_' . $field['id'];
                    $field_name  = "settings[{$mod_id}][{$field['id']}]";
                    $field_value = isset( $mod_opts[ $field['id'] ] ) ? $mod_opts[ $field['id'] ] : $field['default'];
                ?>
                <div class="flowsoft-field">
                    <label class="flowsoft-field__label" for="<?php echo esc_attr( $field_id ); ?>">
                        <?php echo esc_html( $field['label'] ); ?>
                    </label>

                    <?php if ( $field['type'] === 'toggle' ) : ?>
                    <label class="flowsoft-toggle" for="<?php echo esc_attr( $field_id ); ?>">
                        <input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="0" />
                        <input type="checkbox" 
                               id="<?php echo esc_attr( $field_id ); ?>" 
                               name="<?php echo esc_attr( $field_name ); ?>" 
                               value="1"
                               class="flowsoft-toggle__input"
                               <?php checked( $field_value ); ?> />
                        <span class="flowsoft-toggle__slider"></span>
                    </label>

                    <?php elseif ( $field['type'] === 'number' ) : ?>
                    <input type="number" 
                           id="<?php echo esc_attr( $field_id ); ?>" 
                           name="<?php echo esc_attr( $field_name ); ?>"
                           value="<?php echo esc_attr( $field_value ); ?>"
                           class="flowsoft-input flowsoft-input--number"
                           min="<?php echo isset( $field['min'] ) ? esc_attr( $field['min'] ) : ''; ?>"
                           max="<?php echo isset( $field['max'] ) ? esc_attr( $field['max'] ) : ''; ?>" />

                    <?php elseif ( $field['type'] === 'select' ) : ?>
                    <select id="<?php echo esc_attr( $field_id ); ?>" 
                            name="<?php echo esc_attr( $field_name ); ?>"
                            class="flowsoft-select">
                        <?php foreach ( $field['options'] as $opt_value => $opt_label ) : ?>
                        <option value="<?php echo esc_attr( $opt_value ); ?>" <?php selected( $field_value, $opt_value ); ?>>
                            <?php echo esc_html( $opt_label ); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <?php else : ?>
                    <input type="text" 
                           id="<?php echo esc_attr( $field_id ); ?>" 
                           name="<?php echo esc_attr( $field_name ); ?>"
                           value="<?php echo esc_attr( $field_value ); ?>"
                           class="flowsoft-input" />
                    <?php endif; ?>

                    <?php if ( ! empty( $field['description'] ) ) : ?>
                    <p class="flowsoft-field__help"><?php echo esc_html( $field['description'] ); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Danger Zone -->
    <div class="flowsoft-card flowsoft-card--danger">
        <div class="flowsoft-card__header">
            <h3 class="flowsoft-card__title flowsoft-card__title--danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                <?php esc_html_e( 'Zona de Peligro', 'flowsoft-wp' ); ?>
            </h3>
        </div>
        <div class="flowsoft-card__body">
            <div class="flowsoft-danger-actions">
                <div class="flowsoft-danger-action">
                    <div>
                        <strong><?php esc_html_e( 'Borrar Todos los Registros', 'flowsoft-wp' ); ?></strong>
                        <p class="flowsoft-field__help"><?php esc_html_e( 'Elimina permanentemente todos los registros de optimización.', 'flowsoft-wp' ); ?></p>
                    </div>
                    <button type="button" class="flowsoft-btn flowsoft-btn--danger flowsoft-btn--sm" id="flowsoft-clear-logs-settings">
                        <?php esc_html_e( 'Borrar Registros', 'flowsoft-wp' ); ?>
                    </button>
                </div>
                <div class="flowsoft-danger-action">
                    <div>
                        <strong><?php esc_html_e( 'Restablecer Configuración', 'flowsoft-wp' ); ?></strong>
                        <p class="flowsoft-field__help"><?php esc_html_e( 'Restablece toda la configuración de los módulos a sus valores predeterminados.', 'flowsoft-wp' ); ?></p>
                    </div>
                    <button type="button" class="flowsoft-btn flowsoft-btn--danger flowsoft-btn--sm" id="flowsoft-reset-settings">
                        <?php esc_html_e( 'Restablecer', 'flowsoft-wp' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flowsoft-settings-footer">
        <button type="submit" class="flowsoft-btn flowsoft-btn--primary flowsoft-btn--lg" id="flowsoft-save-settings">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <?php esc_html_e( 'Guardar Configuración', 'flowsoft-wp' ); ?>
        </button>
    </div>
</form>

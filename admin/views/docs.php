<?php
/**
 * FlowSoft WP — Documentación
 *
 * @package FlowSoft_WP
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$active_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : 'general';
$sections = array(
    'general'    => __( 'General', 'flowsoft-wp' ),
    'database'   => __( 'Base de Datos', 'flowsoft-wp' ),
    'transients' => __( 'Transients', 'flowsoft-wp' ),
    'heartbeat'  => __( 'Heartbeat', 'flowsoft-wp' ),
    'revisions'  => __( 'Revisiones', 'flowsoft-wp' ),
    'assets'     => __( 'Assets', 'flowsoft-wp' ),
    'cron'       => __( 'Cron', 'flowsoft-wp' ),
    'media'      => __( 'Medios', 'flowsoft-wp' ),
    'interface'  => __( 'Interfaz', 'flowsoft-wp' ),
);
?>

<div class="flowsoft-page-header">
    <div class="flowsoft-page-header__left">
        <h2 class="flowsoft-page-title"><?php esc_html_e( 'Documentación', 'flowsoft-wp' ); ?></h2>
        <p class="flowsoft-page-desc"><?php esc_html_e( 'Guía completa de todos los módulos y funciones de FlowSoft WP.', 'flowsoft-wp' ); ?></p>
    </div>
</div>

<div class="flowsoft-docs-layout">
    <!-- Sidebar Navigation -->
    <aside class="flowsoft-docs-sidebar">
        <nav class="flowsoft-docs-sidebar__nav">
            <?php foreach ( $sections as $key => $label ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=flowsoft-wp&tab=docs&section=' . $key ) ); ?>"
                   class="flowsoft-docs-sidebar__item <?php echo $active_section === $key ? 'is-active' : ''; ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Content -->
    <div class="flowsoft-docs-content">

        <?php if ( 'general' === $active_section ) : ?>

            <div class="flowsoft-docs-hero">
                <h3><?php esc_html_e( '¿Qué es FlowSoft WP?', 'flowsoft-wp' ); ?></h3>
                <p><?php esc_html_e( 'FlowSoft WP es un agente de rendimiento inteligente que trabaja de forma autónoma en segundo plano. No es un simple plugin de caché — monitorea, limpia y optimiza tu sitio WordPress 24/7, asegurando que el servidor opere siempre en su punto máximo de velocidad sin intervención manual.', 'flowsoft-wp' ); ?></p>
            </div>

            <div class="flowsoft-docs-features">
                <div class="flowsoft-docs-feature">
                    <div class="flowsoft-docs-feature__icon flowsoft-docs-feature__icon--indigo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    </div>
                    <div>
                        <strong><?php esc_html_e( 'Totalmente Autónomo', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Los módulos se ejecutan automáticamente según su programación (diario, cada 6 horas, semanal). No necesitas hacer nada.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-feature">
                    <div class="flowsoft-docs-feature__icon flowsoft-docs-feature__icon--emerald">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </div>
                    <div>
                        <strong><?php esc_html_e( 'Sin Impacto en el Rendimiento', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Las tareas pesadas se ejecutan vía WP-Cron en segundo plano. Los módulos en tiempo real son ultraligeros.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-feature">
                    <div class="flowsoft-docs-feature__icon flowsoft-docs-feature__icon--amber">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div>
                        <strong><?php esc_html_e( 'Seguro y Reversible', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Solo elimina datos innecesarios (spam, papelera, borradores viejos). Nunca toca tu contenido real publicado.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-feature">
                    <div class="flowsoft-docs-feature__icon flowsoft-docs-feature__icon--rose">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </div>
                    <div>
                        <strong><?php esc_html_e( 'Puntuación de Salud', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Un algoritmo calcula en tiempo real la salud de tu sitio basándose en overhead de BD, transients, revisiones, spam y papelera.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
            </div>

        <?php elseif ( 'database' === $active_section ) : ?>

            <span class="flowsoft-docs-badge flowsoft-docs-badge--schedule"><?php esc_html_e( 'Ejecución: Diaria (3:00 AM)', 'flowsoft-wp' ); ?></span>
            <h3><?php esc_html_e( 'Optimizador de Base de Datos', 'flowsoft-wp' ); ?></h3>
            <p><?php esc_html_e( 'Este módulo limpia y optimiza las tablas de tu base de datos WordPress automáticamente. Con el tiempo, WordPress acumula datos innecesarios que ralentizan las consultas y aumentan el tamaño del backup.', 'flowsoft-wp' ); ?></p>

            <h4><?php esc_html_e( '¿Qué limpia exactamente?', 'flowsoft-wp' ); ?></h4>
            <div class="flowsoft-docs-list">
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Revisiones de entradas', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Cada vez que guardas un post, WordPress crea una revisión. Un post editado 50 veces tendrá 50 revisiones en la BD. Este módulo las elimina.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Borradores automáticos', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'WordPress crea auto-drafts cada vez que abres el editor. Con el tiempo se acumulan cientos de estos registros fantasma.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Papelera y Spam', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Posts y comentarios en la papelera, comentarios marcados como spam. Si no los limpias, se quedan en tu base de datos para siempre.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Metadatos huérfanos', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Cuando eliminas un post o comentario, a veces sus metadatos quedan en la BD sin estar vinculados a nada. Este módulo los detecta y elimina.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Optimización de tablas', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Ejecuta OPTIMIZE TABLE en las tablas con overhead para recuperar espacio en disco y mejorar la velocidad de consultas.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
            </div>

        <?php elseif ( 'transients' === $active_section ) : ?>

            <span class="flowsoft-docs-badge flowsoft-docs-badge--schedule"><?php esc_html_e( 'Ejecución: Cada 6 Horas', 'flowsoft-wp' ); ?></span>
            <h3><?php esc_html_e( 'Gestor de Transients', 'flowsoft-wp' ); ?></h3>
            <p><?php esc_html_e( 'Los transients son datos temporales que WordPress y los plugins guardan en la tabla wp_options para evitar consultas repetidas (como datos de APIs externas, feeds RSS, etc).', 'flowsoft-wp' ); ?></p>

            <h4><?php esc_html_e( '¿Por qué es importante?', 'flowsoft-wp' ); ?></h4>
            <p><?php esc_html_e( 'El problema surge cuando los transients expiran pero no se eliminan automáticamente, o cuando plugins desactivados dejan transients huérfanos. Esto infla la tabla wp_options, que es una de las tablas más consultadas de WordPress, ralentizando todo el sitio.', 'flowsoft-wp' ); ?></p>

            <div class="flowsoft-docs-callout">
                <strong><?php esc_html_e( 'Dato importante', 'flowsoft-wp' ); ?></strong>
                <p><?php esc_html_e( 'Hemos visto sitios con más de 5,000 transients expirados en wp_options. Limpiarlos puede reducir el tamaño de esa tabla en un 30-50% y acelerar notablemente la carga.', 'flowsoft-wp' ); ?></p>
            </div>

        <?php elseif ( 'heartbeat' === $active_section ) : ?>

            <span class="flowsoft-docs-badge flowsoft-docs-badge--realtime"><?php esc_html_e( 'Ejecución: Tiempo Real', 'flowsoft-wp' ); ?></span>
            <h3><?php esc_html_e( 'Control de Heartbeat', 'flowsoft-wp' ); ?></h3>
            <p><?php esc_html_e( 'El Heartbeat API de WordPress envía peticiones AJAX al servidor cada 15 segundos para mantener funciones como auto-guardado, bloqueo de posts y notificaciones en tiempo real.', 'flowsoft-wp' ); ?></p>

            <h4><?php esc_html_e( '¿Cuál es el problema?', 'flowsoft-wp' ); ?></h4>
            <p><?php esc_html_e( 'Cada petición consume CPU y memoria del servidor. Si tienes 5 pestañas abiertas en el admin, son 20 peticiones por minuto innecesarias. En hosting compartido esto puede causar lentitud severa.', 'flowsoft-wp' ); ?></p>

            <h4><?php esc_html_e( '¿Qué hace este módulo?', 'flowsoft-wp' ); ?></h4>
            <div class="flowsoft-docs-list">
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Panel de admin', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Reduce la frecuencia a 60 segundos (configurable). Solo verifica la sesión.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Editor de posts', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Reduce a 30 segundos (configurable). Mantiene el auto-guardado funcional.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Frontend', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Lo desactiva completamente. Los visitantes no necesitan Heartbeat para nada.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
            </div>

            <div class="flowsoft-docs-callout">
                <strong><?php esc_html_e( 'Resultado típico', 'flowsoft-wp' ); ?></strong>
                <p><?php esc_html_e( 'Reducir el Heartbeat puede bajar el uso de CPU del servidor entre un 10-30%, especialmente en hosting compartido.', 'flowsoft-wp' ); ?></p>
            </div>

        <?php elseif ( 'revisions' === $active_section ) : ?>

            <span class="flowsoft-docs-badge flowsoft-docs-badge--schedule"><?php esc_html_e( 'Ejecución: Semanal', 'flowsoft-wp' ); ?></span>
            <h3><?php esc_html_e( 'Gestor de Revisiones', 'flowsoft-wp' ); ?></h3>
            <p><?php esc_html_e( 'WordPress guarda una copia completa de tu post cada vez que lo guardas o actualizas. Por defecto no tiene límite, así que un post puede tener cientos de revisiones.', 'flowsoft-wp' ); ?></p>

            <h4><?php esc_html_e( '¿Qué hace este módulo?', 'flowsoft-wp' ); ?></h4>
            <p><?php esc_html_e( 'Limita la cantidad de revisiones que WordPress mantiene por cada entrada (por defecto 5) y semanalmente limpia las revisiones excedentes. Esto mantiene la BD liviana sin perder la capacidad de restaurar cambios recientes.', 'flowsoft-wp' ); ?></p>

            <div class="flowsoft-docs-callout">
                <strong><?php esc_html_e( 'Configurable', 'flowsoft-wp' ); ?></strong>
                <p><?php esc_html_e( 'Puedes ajustar cuántas revisiones mantener en Configuración. Valor 0 = desactivar el limiter (WordPress guardará todas las revisiones).', 'flowsoft-wp' ); ?></p>
            </div>

        <?php elseif ( 'assets' === $active_section ) : ?>

            <span class="flowsoft-docs-badge flowsoft-docs-badge--realtime"><?php esc_html_e( 'Ejecución: Tiempo Real', 'flowsoft-wp' ); ?></span>
            <h3><?php esc_html_e( 'Optimizador de Assets', 'flowsoft-wp' ); ?></h3>
            <p><?php esc_html_e( 'Optimiza la forma en que WordPress carga archivos CSS y JavaScript en el frontend para reducir el tiempo de carga.', 'flowsoft-wp' ); ?></p>

            <div class="flowsoft-docs-list">
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Desactivar emojis', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'WordPress carga un script de emojis (wp-emoji-release.min.js) en TODAS las páginas. La mayoría de navegadores modernos ya soportan emojis nativamente, haciendo este script innecesario.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Desactivar oEmbed', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'WordPress carga scripts para permitir que otros sitios incrusten tu contenido. Si no necesitas esta función, desactivarla elimina una petición HTTP extra.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Eliminar query strings', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Archivos como style.css?ver=6.7 no pueden ser cacheados por CDNs. Este módulo elimina el parámetro ?ver= para mejorar el cacheo.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Diferir JavaScript', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Agrega el atributo "defer" a los scripts para que se carguen sin bloquear el renderizado de la página. Mejora el FCP y LCP. Nota: algunos plugins pueden no ser compatibles con esta opción.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
            </div>

        <?php elseif ( 'cron' === $active_section ) : ?>

            <span class="flowsoft-docs-badge flowsoft-docs-badge--schedule"><?php esc_html_e( 'Ejecución: Diaria', 'flowsoft-wp' ); ?></span>
            <h3><?php esc_html_e( 'Monitor de Cron', 'flowsoft-wp' ); ?></h3>
            <p><?php esc_html_e( 'WP-Cron es el sistema de tareas programadas de WordPress. Plugins como WooCommerce, Yoast SEO y otros registran eventos cron para tareas en segundo plano.', 'flowsoft-wp' ); ?></p>

            <h4><?php esc_html_e( '¿Qué puede salir mal?', 'flowsoft-wp' ); ?></h4>
            <div class="flowsoft-docs-list">
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Eventos duplicados', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Bugs en plugins pueden registrar el mismo evento cron múltiples veces, causando ejecuciones repetidas y carga innecesaria.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Eventos huérfanos', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Cuando desactivas un plugin que no limpia sus crons, esos eventos quedan intentando ejecutarse sin éxito.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Eventos atrasados', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Este módulo detecta eventos que deberían haber corrido pero no lo hicieron, lo cual puede indicar problemas con WP-Cron.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
            </div>

        <?php elseif ( 'media' === $active_section ) : ?>

            <span class="flowsoft-docs-badge flowsoft-docs-badge--schedule"><?php esc_html_e( 'Ejecución: Semanal', 'flowsoft-wp' ); ?></span>
            <h3><?php esc_html_e( 'Optimizador de Medios', 'flowsoft-wp' ); ?></h3>
            <p><?php esc_html_e( 'Analiza tu biblioteca de medios para identificar archivos que podrían estar desperdiciando espacio en el servidor.', 'flowsoft-wp' ); ?></p>

            <div class="flowsoft-docs-list">
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Archivos sin adjuntar', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Imágenes que se subieron pero nunca se insertaron en ningún post o página. Ocupan espacio sin usarse.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Imágenes sobredimensionadas', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Detecta imágenes que exceden el tamaño máximo configurado (por defecto 2MB). Subir imágenes de 5-10MB ralentiza enormemente la carga de la página.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
            </div>

            <div class="flowsoft-docs-callout flowsoft-docs-callout--warning">
                <strong><?php esc_html_e( 'Solo análisis', 'flowsoft-wp' ); ?></strong>
                <p><?php esc_html_e( 'Este módulo NO elimina archivos automáticamente. Solo reporta lo que encuentra para que tú decidas qué hacer. La eliminación de medios debe hacerse manualmente desde la biblioteca de medios de WordPress.', 'flowsoft-wp' ); ?></p>
            </div>

        <?php elseif ( 'interface' === $active_section ) : ?>

            <h3><?php esc_html_e( 'Guía de la Interfaz', 'flowsoft-wp' ); ?></h3>

            <div class="flowsoft-docs-list">
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Panel (Dashboard)', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Vista general con la puntuación de salud, estadísticas globales, estado de módulos y acciones rápidas. Desde aquí puedes ejecutar optimizaciones manuales con un clic.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Módulos', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Aquí puedes activar/desactivar cada módulo individualmente. Cada tarjeta muestra el estado, estadísticas y un botón para ejecutar el módulo manualmente.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Registros', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Historial completo de todas las optimizaciones realizadas. Puedes filtrar por módulo y estado. Útil para verificar que todo funciona correctamente.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Configuración', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Ajusta los parámetros de cada módulo: intervalos de Heartbeat, máximo de revisiones, tamaño máximo de imágenes, etc. También incluye una zona de peligro para borrar registros o restablecer todo.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
                <div class="flowsoft-docs-list__item">
                    <div>
                        <strong><?php esc_html_e( 'Puntuación de Salud', 'flowsoft-wp' ); ?></strong>
                        <p><?php esc_html_e( 'Se calcula verificando: overhead de la BD, transients expirados, cantidad de revisiones, posts en papelera, spam, y cuántos módulos tienes activos. 80-100 = Excelente, 50-79 = Aceptable, 0-49 = Necesita atención.', 'flowsoft-wp' ); ?></p>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

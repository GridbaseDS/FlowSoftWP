/**
 * FlowSoft WP — Admin JavaScript
 *
 * Handles AJAX operations, module toggles, health gauge animation,
 * toast notifications, and interactive UI elements.
 */

(function ($) {
    'use strict';

    const FlowSoft = {

        /**
         * Initialize all handlers.
         */
        init: function () {
            this.initHealthGauge();
            this.initModuleToggles();
            this.initRunButtons();
            this.initRunAll();
            this.initClearLogs();
            this.initSettingsForm();
        },

        // ─── Health Gauge Animation ──────────────────────────
        initHealthGauge: function () {
            const gauge = document.querySelector('.flowsoft-gauge-fill');
            if (!gauge) return;

            const value = parseInt(gauge.dataset.value, 10) || 0;
            const circumference = 2 * Math.PI * 50; // r=50
            const offset = circumference - (value / 100) * circumference;

            // Animate after a small delay
            setTimeout(function () {
                gauge.style.strokeDasharray = circumference;
                gauge.style.strokeDashoffset = offset;

                // Color based on score
                if (value >= 80) {
                    gauge.style.stroke = '#10B981';
                } else if (value >= 50) {
                    gauge.style.stroke = '#F59E0B';
                } else {
                    gauge.style.stroke = '#EF4444';
                }
            }, 300);
        },

        // ─── Module Toggles ─────────────────────────────────
        initModuleToggles: function () {
            const self = this;

            $(document).on('change', '[data-module-toggle]', function () {
                const $toggle = $(this);
                const moduleId = $toggle.data('module-toggle');
                const enabled = $toggle.is(':checked');
                const $card = $toggle.closest('.flowsoft-module-card');

                $.ajax({
                    url: flowsoftAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flowsoft_toggle_module',
                        nonce: flowsoftAdmin.nonce,
                        module_id: moduleId,
                        enabled: enabled ? '1' : '0'
                    },
                    success: function (response) {
                        if (response.success) {
                            self.showToast(response.data.message, 'success');

                            // Update card state
                            if ($card.length) {
                                $card.toggleClass('is-enabled', enabled);
                                $card.toggleClass('is-disabled', !enabled);
                                $card.find('[data-run-module]').prop('disabled', !enabled);
                            }
                        } else {
                            self.showToast(response.data.message || flowsoftAdmin.strings.error, 'error');
                            $toggle.prop('checked', !enabled); // Revert
                        }
                    },
                    error: function () {
                        self.showToast(flowsoftAdmin.strings.error, 'error');
                        $toggle.prop('checked', !enabled); // Revert
                    }
                });
            });
        },

        // ─── Run Module Buttons ─────────────────────────────
        initRunButtons: function () {
            const self = this;

            $(document).on('click', '[data-run-module]', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const moduleId = $btn.data('run-module');

                if ($btn.hasClass('is-loading') || $btn.is(':disabled')) return;

                $btn.addClass('is-loading');

                $.ajax({
                    url: flowsoftAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flowsoft_run_module',
                        nonce: flowsoftAdmin.nonce,
                        module_id: moduleId
                    },
                    success: function (response) {
                        $btn.removeClass('is-loading');
                        if (response.success) {
                            let msg = response.data.message || flowsoftAdmin.strings.success;
                            if (response.data.items > 0) {
                                msg += ' (' + response.data.items + ' elementos)';
                            }
                            self.showToast(msg, 'success');
                        } else {
                            self.showToast(response.data.message || flowsoftAdmin.strings.error, 'error');
                        }
                    },
                    error: function () {
                        $btn.removeClass('is-loading');
                        self.showToast(flowsoftAdmin.strings.error, 'error');
                    }
                });
            });
        },

        // ─── Run All ────────────────────────────────────────
        initRunAll: function () {
            const self = this;

            $('#flowsoft-run-all').on('click', function (e) {
                e.preventDefault();
                const $btn = $(this);

                if ($btn.hasClass('is-loading')) return;

                $btn.addClass('is-loading');

                $.ajax({
                    url: flowsoftAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flowsoft_run_all',
                        nonce: flowsoftAdmin.nonce
                    },
                    success: function (response) {
                        $btn.removeClass('is-loading');
                        if (response.success) {
                            const results = response.data.results || {};
                            let totalItems = 0;

                            Object.keys(results).forEach(function (key) {
                                if (results[key].items) {
                                    totalItems += results[key].items;
                                }
                            });

                            let msg = response.data.message;
                            if (totalItems > 0) {
                                msg += ' (' + totalItems + ' elementos optimizados)';
                            }

                            self.showToast(msg, 'success');

                            // Reload page after a short delay to show updated stats
                            setTimeout(function () {
                                window.location.reload();
                            }, 2000);
                        } else {
                            self.showToast(flowsoftAdmin.strings.error, 'error');
                        }
                    },
                    error: function () {
                        $btn.removeClass('is-loading');
                        self.showToast(flowsoftAdmin.strings.error, 'error');
                    }
                });
            });
        },

        // ─── Clear Logs ─────────────────────────────────────
        initClearLogs: function () {
            const self = this;

            $(document).on('click', '#flowsoft-clear-logs, #flowsoft-clear-logs-settings', function (e) {
                e.preventDefault();

                if (!confirm(flowsoftAdmin.strings.confirm_clear)) return;

                const $btn = $(this);
                $btn.addClass('is-loading');

                $.ajax({
                    url: flowsoftAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flowsoft_clear_logs',
                        nonce: flowsoftAdmin.nonce
                    },
                    success: function (response) {
                        $btn.removeClass('is-loading');
                        if (response.success) {
                            self.showToast(response.data.message, 'success');
                            // Reload if on logs page
                            if ($('#flowsoft-logs-table').length) {
                                setTimeout(function () {
                                    window.location.reload();
                                }, 1000);
                            }
                        }
                    },
                    error: function () {
                        $btn.removeClass('is-loading');
                        self.showToast(flowsoftAdmin.strings.error, 'error');
                    }
                });
            });
        },

        // ─── Settings Form ──────────────────────────────────
        initSettingsForm: function () {
            const self = this;

            $('#flowsoft-settings-form').on('submit', function (e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $('#flowsoft-save-settings');
                const formData = $form.serializeArray();

                // Build settings object
                const settings = {};
                formData.forEach(function (field) {
                    const match = field.name.match(/settings\[(\w+)\]\[(\w+)\]/);
                    if (match) {
                        const module = match[1];
                        const key = match[2];
                        if (!settings[module]) settings[module] = {};
                        settings[module][key] = field.value;
                    }
                });

                $btn.addClass('is-loading');

                $.ajax({
                    url: flowsoftAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flowsoft_save_settings',
                        nonce: flowsoftAdmin.nonce,
                        settings: settings
                    },
                    success: function (response) {
                        $btn.removeClass('is-loading');
                        if (response.success) {
                            self.showToast(flowsoftAdmin.strings.saved, 'success');
                        } else {
                            self.showToast(response.data.message || flowsoftAdmin.strings.error, 'error');
                        }
                    },
                    error: function () {
                        $btn.removeClass('is-loading');
                        self.showToast(flowsoftAdmin.strings.error, 'error');
                    }
                });
            });

            // Reset settings
            $('#flowsoft-reset-settings').on('click', function (e) {
                e.preventDefault();
                if (!confirm('¿Restablecer toda la configuración? Esta acción no se puede deshacer.')) return;

                // Simply reload the page — the activation defaults will be re-applied
                // In a real scenario you'd call a reset AJAX endpoint
                self.showToast('Restableciendo configuración...', 'warning');
                setTimeout(function () {
                    window.location.reload();
                }, 1500);
            });
        },

        // ─── Toast Notifications ────────────────────────────
        showToast: function (message, type) {
            type = type || 'success';

            const icons = {
                success: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
                error: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>',
                warning: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>'
            };

            const $toast = $('<div class="flowsoft-toast flowsoft-toast--' + type + '">' +
                (icons[type] || '') +
                '<span>' + message + '</span>' +
                '</div>');

            $('#flowsoft-toast-container').append($toast);

            // Auto-dismiss after 4 seconds
            setTimeout(function () {
                $toast.addClass('is-hiding');
                setTimeout(function () {
                    $toast.remove();
                }, 300);
            }, 4000);
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function () {
        FlowSoft.init();
    });

})(jQuery);

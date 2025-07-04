/**
 * SecurePress X Admin Fallback JavaScript
 * 
 * Basic functionality for the admin interface when React is not available
 */

(function($) {
    'use strict';
    
    // Wait for DOM to be ready
    $(document).ready(function() {
        // Initialize fallback interface
        SecurePressX.init();
    });
    
    // Main SecurePress X object
    window.SecurePressX = {
        
        /**
         * Initialize the fallback interface
         */
        init: function() {
            this.bindEvents();
            this.loadDashboardData();
            
            // Show fallback interface after a delay if React hasn't loaded
            setTimeout(function() {
                if (!$('.securepress-x-react-loaded').length) {
                    $('#securepress-x-fallback').show();
                    $('.securepress-x-loading p').text('Using fallback interface');
                }
            }, 3000);
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Toggle module enable/disable
            $(document).on('change', '.securepress-x-module-toggle', function() {
                var moduleId = $(this).data('module');
                var enabled = $(this).is(':checked');
                self.toggleModule(moduleId, enabled);
            });
            
            // Save settings forms
            $(document).on('submit', '.securepress-x-settings-form', function(e) {
                e.preventDefault();
                var moduleId = $(this).data('module');
                var formData = $(this).serialize();
                self.saveModuleSettings(moduleId, formData);
            });
            
            // Run security scan
            $(document).on('click', '.securepress-x-scan-button', function() {
                var scanType = $(this).data('scan-type') || 'quick';
                self.startSecurityScan(scanType);
            });
            
            // Refresh data
            $(document).on('click', '.securepress-x-refresh', function() {
                self.loadDashboardData();
            });
        },
        
        /**
         * Load dashboard data
         */
        loadDashboardData: function() {
            var self = this;
            
            if (typeof securePressX === 'undefined') {
                console.warn('SecurePress X: Admin data not available');
                return;
            }
            
            // Show loading state
            $('.securepress-x-loading-data').show();
            
            $.ajax({
                url: securePressX.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'securepress_x_api',
                    action_type: 'get_dashboard_data',
                    nonce: securePressX.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDashboard(response.data);
                    } else {
                        self.showError('Failed to load dashboard data');
                    }
                },
                error: function() {
                    self.showError('Network error while loading data');
                },
                complete: function() {
                    $('.securepress-x-loading-data').hide();
                }
            });
        },
        
        /**
         * Update dashboard with new data
         */
        updateDashboard: function(data) {
            // Update security status
            if (data.security_status) {
                $('.securepress-x-security-status').text(data.security_status);
            }
            
            // Update active modules count
            if (typeof data.active_modules !== 'undefined') {
                $('.securepress-x-active-modules').text(data.active_modules);
            }
            
            // Update recent events
            if (data.recent_events && data.recent_events.length) {
                var eventsList = $('.securepress-x-recent-events');
                eventsList.empty();
                
                data.recent_events.forEach(function(event) {
                    eventsList.append(
                        '<li><strong>' + event.level + ':</strong> ' + 
                        event.message + ' <em>(' + event.date + ')</em></li>'
                    );
                });
            }
        },
        
        /**
         * Toggle module on/off
         */
        toggleModule: function(moduleId, enabled) {
            var self = this;
            
            $.ajax({
                url: securePressX.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'securepress_x_api',
                    action_type: 'update_module_settings',
                    module_id: moduleId,
                    settings: { enabled: enabled },
                    nonce: securePressX.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Module ' + (enabled ? 'enabled' : 'disabled') + ' successfully', 'success');
                        
                        // Update status display
                        var statusElement = $('.securepress-x-module-status[data-module="' + moduleId + '"]');
                        if (enabled) {
                            statusElement.removeClass('securepress-x-status-disabled')
                                        .addClass('securepress-x-status-enabled')
                                        .text('Enabled');
                        } else {
                            statusElement.removeClass('securepress-x-status-enabled')
                                        .addClass('securepress-x-status-disabled')
                                        .text('Disabled');
                        }
                    } else {
                        self.showError('Failed to update module');
                    }
                },
                error: function() {
                    self.showError('Network error while updating module');
                }
            });
        },
        
        /**
         * Save module settings
         */
        saveModuleSettings: function(moduleId, formData) {
            var self = this;
            
            // Parse form data
            var settings = {};
            var pairs = formData.split('&');
            pairs.forEach(function(pair) {
                var parts = pair.split('=');
                if (parts.length === 2) {
                    var key = decodeURIComponent(parts[0]);
                    var value = decodeURIComponent(parts[1]);
                    
                    // Convert string booleans
                    if (value === 'true') value = true;
                    else if (value === 'false') value = false;
                    else if (!isNaN(value) && value !== '') value = Number(value);
                    
                    settings[key] = value;
                }
            });
            
            $.ajax({
                url: securePressX.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'securepress_x_api',
                    action_type: 'update_module_settings',
                    module_id: moduleId,
                    settings: settings,
                    nonce: securePressX.nonce
                },
                beforeSend: function() {
                    $('.securepress-x-save-button').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Settings saved successfully', 'success');
                    } else {
                        self.showError('Failed to save settings');
                    }
                },
                error: function() {
                    self.showError('Network error while saving settings');
                },
                complete: function() {
                    $('.securepress-x-save-button').prop('disabled', false).text('Save Changes');
                }
            });
        },
        
        /**
         * Start security scan
         */
        startSecurityScan: function(scanType) {
            var self = this;
            
            $.ajax({
                url: securePressX.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'securepress_x_api',
                    action_type: 'start_scan',
                    scan_type: scanType,
                    nonce: securePressX.nonce
                },
                beforeSend: function() {
                    $('.securepress-x-scan-button').prop('disabled', true).text('Starting scan...');
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Security scan started', 'info');
                        // TODO: Implement scan progress monitoring
                    } else {
                        self.showError('Failed to start security scan');
                    }
                },
                error: function() {
                    self.showError('Network error while starting scan');
                },
                complete: function() {
                    $('.securepress-x-scan-button').prop('disabled', false).text('Run Scan');
                }
            });
        },
        
        /**
         * Show success/info notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            var noticeClass = 'notice-' + type;
            
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible">' +
                          '<p>' + message + '</p>' +
                          '<button type="button" class="notice-dismiss"></button>' +
                          '</div>');
            
            $('.wrap').prepend(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
            
            // Handle manual dismiss
            notice.find('.notice-dismiss').on('click', function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            });
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            this.showNotice(message, 'error');
        },
        
        /**
         * Format date for display
         */
        formatDate: function(timestamp) {
            var date = new Date(timestamp * 1000);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        },
        
        /**
         * Debounce function for search inputs
         */
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // Expose utility functions
    window.SecurePressX.utils = {
        
        /**
         * Create toggle switch HTML
         */
        createToggle: function(name, checked, moduleId) {
            checked = checked ? 'checked' : '';
            return '<label class="securepress-x-toggle">' +
                   '<input type="checkbox" name="' + name + '" ' + checked + 
                   ' class="securepress-x-module-toggle" data-module="' + moduleId + '">' +
                   '<span class="slider"></span>' +
                   '</label>';
        },
        
        /**
         * Sanitize HTML
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        },
        
        /**
         * Get security score color class
         */
        getScoreClass: function(score) {
            if (score >= 80) return 'securepress-x-score-good';
            if (score >= 60) return 'securepress-x-score-warning';
            return 'securepress-x-score-danger';
        }
    };
    
})(jQuery); 
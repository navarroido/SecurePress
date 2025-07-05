import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader, ToggleControl, TextControl, SelectControl, Button } from '@wordpress/components';
import { Lock, Globe, FileText, Shield, Settings as SettingsIcon, ChevronDown, CheckCircle } from 'lucide-react';

const Settings = () => {
    const [settings, setSettings] = useState({
        login_protection: {
            enabled: false,
            custom_slug: 'secure-login',
            redirect_404_to_home: true,
        },
        http_headers: {
            enabled: false,
            hsts_enabled: false,
            xframe_enabled: false,
            x_content_type_options: false,
            x_xss_protection: false,
            referrer_policy: false,
            csp_enabled: false,
            custom_csp_policy: '',
            custom_ido_header_enabled: false,
            custom_ido_header_value: '',
        },
        file_integrity: {
            enabled: false,
            scan_frequency: 'daily',
            scan_core: true,
            scan_plugins: true,
            scan_themes: true,
            notify_email: false,
            notify_webhook: false,
        },
        brute_force: {
            enabled: false,
            max_attempts: 5,
            lockout_duration: 30,
            recaptcha_enabled: false,
        },
        api_access: {
            xmlrpc_enabled: false,
            rest_api_enabled: true,
            rest_api_restricted: false,
        },
        two_factor: {
            enabled: false,
            enforcement_roles: ['administrator'],
        },
        auto_update: {
            enabled: false,
        },
        hardening: {
            secure_all_enabled: false,
            file_editor_disabled: false,
            debug_disabled: false,
            disable_user_enumeration: false,
            disable_version_info: false,
        },
        audit_log: {
            enabled: false,
            retention_days: 30,
            notifications_enabled: false,
            notification_type: 'email',
            notification_email: '',
            log_level: 'all',
        },
    });

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [showSuccessAlert, setShowSuccessAlert] = useState(false);
    const [expandedSections, setExpandedSections] = useState({
        login_protection: true,
        http_headers: true,
        file_integrity: true,
        brute_force: true,
        api_access: true,
        two_factor: true,
        auto_update: true,
        hardening: true,
        audit_log: true,
    });

    useEffect(() => {
        fetchSettings();
    }, []);

    const fetchSettings = async () => {
        try {
            const response = await fetch('/wp-json/securepressx/v1/settings', {
                headers: {
                    'X-WP-Nonce': window.securePressx.nonce,
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                console.log('Fetched settings:', data);
                setSettings(data);
            } else {
                console.error('Error fetching settings:', response.statusText);
                // Keep using default settings if fetch fails
            }
        } catch (error) {
            console.error('Error fetching settings:', error);
            // Keep using default settings if fetch fails
        } finally {
            setLoading(false);
        }
    };

    const handleSaveSettings = async () => {
        setSaving(true);
        try {
            // Debug: Log settings being sent
            console.log('Saving settings:', settings);

            const response = await fetch('/wp-json/securepressx/v1/settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.securePressx.nonce,
                },
                body: JSON.stringify({ settings }),
            });

            if (response.ok) {
                const responseData = await response.json();
                console.log('Settings saved successfully:', responseData);
                
                // Update local state with returned settings if available
                if (responseData.settings) {
                    setSettings(responseData.settings);
                }
                
                // Show success message
                setShowSuccessAlert(true);
                setTimeout(() => setShowSuccessAlert(false), 3000);
            } else {
                // Handle error response
                const errorText = await response.text();
                let errorMessage = __('Failed to save settings. Please try again.', 'securepress-x');
                
                try {
                    const errorData = JSON.parse(errorText);
                    console.error('Error saving settings:', errorData);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', errorText);
                }
                
                alert(errorMessage);
            }
        } catch (error) {
            console.error('Error saving settings:', error);
            alert(__('Failed to save settings. Please check your connection.', 'securepress-x'));
        } finally {
            setSaving(false);
        }
    };

    const updateSetting = (section, key, value) => {
        setSettings(prev => ({
            ...prev,
            [section]: {
                ...prev[section],
                [key]: value
            }
        }));
    };

    const toggleSection = (section) => {
        setExpandedSections(prev => ({
            ...prev,
            [section]: !prev[section]
        }));
    };

    // Render improved setting field with better alignment
    const renderSettingField = (label, value, onChange, type = 'toggle', options = null, description = null) => {
        return (
            <div className="modern-setting-field">
                <div className="setting-main">
                    <div className="setting-info">
                        <label className="setting-label">{label}</label>
                        {description && <p className="setting-description">{description}</p>}
                    </div>
                    <div className="setting-control">
                        {type === 'toggle' && (
                            <ToggleControl
                                checked={value}
                                onChange={(newValue) => onChange(newValue)}
                                __nextHasNoMarginBottom
                            />
                        )}
                        {type === 'text' && (
                            <TextControl
                                value={value}
                                onChange={onChange}
                                __nextHasNoMarginBottom
                            />
                        )}
                        {type === 'number' && (
                            <TextControl
                                type="number"
                                value={value}
                                onChange={onChange}
                                __nextHasNoMarginBottom
                            />
                        )}
                        {type === 'select' && options && (
                            <SelectControl
                                value={value}
                                options={options}
                                onChange={onChange}
                                __nextHasNoMarginBottom
                            />
                        )}
                        {type === 'email' && (
                            <TextControl
                                type="email"
                                value={value}
                                onChange={onChange}
                                __nextHasNoMarginBottom
                            />
                        )}
                    </div>
                </div>
            </div>
        );
    };

    const renderModernCard = (section, title, icon, children) => (
        <Card key={section} className="modern-settings-card">
            <CardHeader
                className="modern-card-header"
                onClick={() => toggleSection(section)}
            >
                <div className="card-header-content">
                    <div className="card-header-main">
                        {icon}
                        <h3 className="card-title">{title}</h3>
                    </div>
                    <ChevronDown 
                        className={`chevron-icon ${expandedSections[section] ? 'expanded' : ''}`}
                    />
                </div>
            </CardHeader>
            {expandedSections[section] && (
                <CardBody className="modern-card-body">
                    {children}
                </CardBody>
            )}
        </Card>
    );

    if (loading) {
        return (
            <div className="securepress-settings">
                <div className="settings-grid">
                    {[1, 2, 3, 4, 5, 6, 7, 8, 9].map((i) => (
                        <Card key={i} className="modern-settings-card">
                            <CardBody>
                                <div className="animate-pulse">
                                    <div className="h-6 bg-gray-200 rounded w-3/4 mb-4"></div>
                                    <div className="space-y-3">
                                        <div className="h-4 bg-gray-200 rounded"></div>
                                        <div className="h-4 bg-gray-200 rounded w-2/3"></div>
                                    </div>
                                </div>
                            </CardBody>
                        </Card>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="securepress-settings fade-in">
            {/* Success Alert */}
            {showSuccessAlert && (
                <div className="securepress-success-alert">
                    <CheckCircle className="alert-icon" />
                    {__('Settings saved successfully.', 'securepress-x')}
                </div>
            )}

            <div className="settings-header">
                <h2>{__('SecurePress X Settings', 'securepress-x')}</h2>
                <Button
                    isPrimary
                    onClick={handleSaveSettings}
                    isBusy={saving}
                    disabled={saving}
                    className="save-button"
                >
                    {saving ? __('Saving...', 'securepress-x') : __('Save All Settings', 'securepress-x')}
                </Button>
            </div>

            <div className="modern-settings-grid">
                {/* Login Protection */}
                {renderModernCard('login_protection', __('Login Protection', 'securepress-x'), <Lock className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable Login Protection', 'securepress-x'),
                            settings.login_protection.enabled,
                            (value) => updateSetting('login_protection', 'enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Custom Login URL', 'securepress-x'),
                            settings.login_protection.custom_slug,
                            (value) => updateSetting('login_protection', 'custom_slug', value),
                            'text',
                            null,
                            __('Replace the login URL with a custom slug', 'securepress-x')
                        )}
                        
                        {renderSettingField(
                            __('Redirect to Homepage', 'securepress-x'),
                            settings.login_protection.redirect_404_to_home,
                            (value) => updateSetting('login_protection', 'redirect_404_to_home', value),
                            'toggle',
                            null,
                            __('Redirect 404 errors to homepage', 'securepress-x')
                        )}
                    </div>
                ))}

                {/* HTTP Security Headers */}
                {renderModernCard('http_headers', __('HTTP Security Headers', 'securepress-x'), <Globe className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable HTTP Security Headers', 'securepress-x'),
                            settings.http_headers.enabled,
                            (value) => updateSetting('http_headers', 'enabled', value)
                        )}
                        
                        {settings.http_headers.enabled && (
                            <>
                                <h4 className="settings-subheading">{__('Individual Headers', 'securepress-x')}</h4>
                                
                                {renderSettingField(
                                    __('Strict Transport Security (HSTS)', 'securepress-x'),
                                    settings.http_headers.hsts_enabled,
                                    (value) => updateSetting('http_headers', 'hsts_enabled', value),
                                    'toggle',
                                    null,
                                    __('Force HTTPS connections and prevent protocol downgrade attacks', 'securepress-x')
                                )}
                                
                                {renderSettingField(
                                    __('X-Frame-Options', 'securepress-x'),
                                    settings.http_headers.xframe_enabled,
                                    (value) => updateSetting('http_headers', 'xframe_enabled', value),
                                    'toggle',
                                    null,
                                    __('Prevent clickjacking by controlling iframe embedding', 'securepress-x')
                                )}
                                
                                {renderSettingField(
                                    __('X-Content-Type-Options', 'securepress-x'),
                                    settings.http_headers.x_content_type_options,
                                    (value) => updateSetting('http_headers', 'x_content_type_options', value),
                                    'toggle',
                                    null,
                                    __('Prevent MIME type sniffing attacks', 'securepress-x')
                                )}
                                
                                {renderSettingField(
                                    __('X-XSS-Protection', 'securepress-x'),
                                    settings.http_headers.x_xss_protection,
                                    (value) => updateSetting('http_headers', 'x_xss_protection', value),
                                    'toggle',
                                    null,
                                    __('Enable browser XSS filtering', 'securepress-x')
                                )}
                                
                                {renderSettingField(
                                    __('Referrer Policy', 'securepress-x'),
                                    settings.http_headers.referrer_policy,
                                    (value) => updateSetting('http_headers', 'referrer_policy', value),
                                    'toggle',
                                    null,
                                    __('Control how much referrer information is shared', 'securepress-x')
                                )}
                                
                                {renderSettingField(
                                    __('Content Security Policy (CSP)', 'securepress-x'),
                                    settings.http_headers.csp_enabled,
                                    (value) => updateSetting('http_headers', 'csp_enabled', value),
                                    'toggle',
                                    null,
                                    __('Advanced protection against XSS and data injection attacks', 'securepress-x')
                                )}
                                
                                {settings.http_headers.csp_enabled && renderSettingField(
                                    __('Custom CSP Directives', 'securepress-x'),
                                    settings.http_headers.custom_csp_policy,
                                    (value) => updateSetting('http_headers', 'custom_csp_policy', value),
                                    'textarea',
                                    null,
                                    __('Custom Content Security Policy directives (leave empty for default)', 'securepress-x')
                                )}
                                
                                <h4 className="settings-subheading">{__('Custom Headers', 'securepress-x')}</h4>
                                
                                {renderSettingField(
                                    __('Custom X-Ido Header', 'securepress-x'),
                                    settings.http_headers.custom_ido_header_enabled,
                                    (value) => updateSetting('http_headers', 'custom_ido_header_enabled', value),
                                    'toggle',
                                    null,
                                    __('Enable custom X-Ido header for testing', 'securepress-x')
                                )}
                                
                                {settings.http_headers.custom_ido_header_enabled && renderSettingField(
                                    __('Custom X-Ido Header Value', 'securepress-x'),
                                    settings.http_headers.custom_ido_header_value,
                                    (value) => updateSetting('http_headers', 'custom_ido_header_value', value),
                                    'text',
                                    null,
                                    __('Value for the custom X-Ido header', 'securepress-x')
                                )}
                            </>
                        )}
                    </div>
                ))}

                {/* File Integrity Scanner */}
                {renderModernCard('file_integrity', __('File Integrity Scanner', 'securepress-x'), <FileText className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable File Integrity Scanner', 'securepress-x'),
                            settings.file_integrity.enabled,
                            (value) => updateSetting('file_integrity', 'enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Scan Frequency', 'securepress-x'),
                            settings.file_integrity.scan_frequency,
                            (value) => updateSetting('file_integrity', 'scan_frequency', value),
                            'select',
                            [
                                { label: __('Daily', 'securepress-x'), value: 'daily' },
                                { label: __('Weekly', 'securepress-x'), value: 'weekly' },
                                { label: __('Monthly', 'securepress-x'), value: 'monthly' },
                            ]
                        )}
                        
                        {renderSettingField(
                            __('Scan WordPress Core', 'securepress-x'),
                            settings.file_integrity.scan_core,
                            (value) => updateSetting('file_integrity', 'scan_core', value)
                        )}
                        
                        {renderSettingField(
                            __('Scan Plugins', 'securepress-x'),
                            settings.file_integrity.scan_plugins,
                            (value) => updateSetting('file_integrity', 'scan_plugins', value)
                        )}
                        
                        {renderSettingField(
                            __('Scan Themes', 'securepress-x'),
                            settings.file_integrity.scan_themes,
                            (value) => updateSetting('file_integrity', 'scan_themes', value)
                        )}
                        
                        {renderSettingField(
                            __('Email Notifications', 'securepress-x'),
                            settings.file_integrity.notify_email,
                            (value) => updateSetting('file_integrity', 'notify_email', value)
                        )}
                        
                        {renderSettingField(
                            __('Webhook Notifications', 'securepress-x'),
                            settings.file_integrity.notify_webhook,
                            (value) => updateSetting('file_integrity', 'notify_webhook', value)
                        )}
                    </div>
                ))}

                {/* Brute Force Protection */}
                {renderModernCard('brute_force', __('Brute Force Protection', 'securepress-x'), <Shield className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable Brute Force Protection', 'securepress-x'),
                            settings.brute_force.enabled,
                            (value) => updateSetting('brute_force', 'enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Maximum Login Attempts', 'securepress-x'),
                            settings.brute_force.max_attempts,
                            (value) => updateSetting('brute_force', 'max_attempts', parseInt(value)),
                            'number'
                        )}
                        
                        {renderSettingField(
                            __('Lockout Duration (Minutes)', 'securepress-x'),
                            settings.brute_force.lockout_duration,
                            (value) => updateSetting('brute_force', 'lockout_duration', parseInt(value)),
                            'number'
                        )}
                        
                        {renderSettingField(
                            __('Enable reCAPTCHA', 'securepress-x'),
                            settings.brute_force.recaptcha_enabled,
                            (value) => updateSetting('brute_force', 'recaptcha_enabled', value)
                        )}
                    </div>
                ))}

                {/* API Access */}
                {renderModernCard('api_access', __('API Access', 'securepress-x'), <Globe className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable XML-RPC', 'securepress-x'),
                            settings.api_access.xmlrpc_enabled,
                            (value) => updateSetting('api_access', 'xmlrpc_enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Enable REST API', 'securepress-x'),
                            settings.api_access.rest_api_enabled,
                            (value) => updateSetting('api_access', 'rest_api_enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Restrict REST API Access', 'securepress-x'),
                            settings.api_access.rest_api_restricted,
                            (value) => updateSetting('api_access', 'rest_api_restricted', value)
                        )}
                    </div>
                ))}

                {/* Two-Factor Authentication */}
                {renderModernCard('two_factor', __('Two-Factor Authentication', 'securepress-x'), <Lock className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable 2FA (TOTP)', 'securepress-x'),
                            settings.two_factor.enabled,
                            (value) => updateSetting('two_factor', 'enabled', value),
                            'toggle',
                            null,
                            __('Phase 2 Feature - Coming Soon', 'securepress-x')
                        )}
                    </div>
                ))}

                {/* Auto-Update Security Patches */}
                {renderModernCard('auto_update', __('Auto-Update Security Patches', 'securepress-x'), <SettingsIcon className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable Auto-Updates for Security Patches', 'securepress-x'),
                            settings.auto_update.enabled,
                            (value) => updateSetting('auto_update', 'enabled', value),
                            'toggle',
                            null,
                            __('Phase 2 Feature - Coming Soon', 'securepress-x')
                        )}
                    </div>
                ))}

                {/* Security Hardening */}
                {renderModernCard('hardening', __('Security Hardening', 'securepress-x'), <Shield className="card-icon" />, (
                    <div className="settings-group">
                        <Button
                            isSecondary
                            onClick={() => updateSetting('hardening', 'secure_all_enabled', !settings.hardening.secure_all_enabled)}
                            className="secure-all-button"
                        >
                            <Shield className="button-icon" />
                            {__('Secure All', 'securepress-x')}
                        </Button>

                        {renderSettingField(
                            __('Disable File Editor', 'securepress-x'),
                            settings.hardening.file_editor_disabled,
                            (value) => updateSetting('hardening', 'file_editor_disabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Disable Debug Mode', 'securepress-x'),
                            settings.hardening.debug_disabled,
                            (value) => updateSetting('hardening', 'debug_disabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Disable User Enumeration', 'securepress-x'),
                            settings.hardening.disable_user_enumeration,
                            (value) => updateSetting('hardening', 'disable_user_enumeration', value)
                        )}
                        
                        {renderSettingField(
                            __('Hide WordPress Version', 'securepress-x'),
                            settings.hardening.disable_version_info,
                            (value) => updateSetting('hardening', 'disable_version_info', value)
                        )}
                    </div>
                ))}

                {/* Audit Log & Notifications */}
                {renderModernCard('audit_log', __('Audit Log & Notifications', 'securepress-x'), <FileText className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable Audit Logging', 'securepress-x'),
                            settings.audit_log.enabled,
                            (value) => updateSetting('audit_log', 'enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Log Retention (Days)', 'securepress-x'),
                            settings.audit_log.retention_days,
                            (value) => updateSetting('audit_log', 'retention_days', parseInt(value)),
                            'number'
                        )}
                        
                        {renderSettingField(
                            __('Enable Notifications', 'securepress-x'),
                            settings.audit_log.notifications_enabled,
                            (value) => updateSetting('audit_log', 'notifications_enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Notification Method', 'securepress-x'),
                            settings.audit_log.notification_type,
                            (value) => updateSetting('audit_log', 'notification_type', value),
                            'select',
                            [
                                { label: __('Email', 'securepress-x'), value: 'email' },
                                { label: __('Webhook', 'securepress-x'), value: 'webhook' },
                                { label: __('Both', 'securepress-x'), value: 'both' },
                            ]
                        )}
                        
                        {renderSettingField(
                            __('Notification Email', 'securepress-x'),
                            settings.audit_log.notification_email,
                            (value) => updateSetting('audit_log', 'notification_email', value),
                            'email'
                        )}
                        
                        {renderSettingField(
                            __('Log Level', 'securepress-x'),
                            settings.audit_log.log_level,
                            (value) => updateSetting('audit_log', 'log_level', value),
                            'select',
                            [
                                { label: __('All Events', 'securepress-x'), value: 'all' },
                                { label: __('Warnings & Errors', 'securepress-x'), value: 'warnings' },
                                { label: __('Errors Only', 'securepress-x'), value: 'errors' },
                            ]
                        )}
                    </div>
                ))}
            </div>

            <div className="settings-footer">
                <Button
                    isPrimary
                    onClick={handleSaveSettings}
                    isBusy={saving}
                    disabled={saving}
                    className="save-button"
                    size="large"
                >
                    {saving ? __('Saving...', 'securepress-x') : __('Save All Settings', 'securepress-x')}
                </Button>
            </div>
        </div>
    );
};

export default Settings; 
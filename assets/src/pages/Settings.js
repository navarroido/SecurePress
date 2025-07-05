import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader, ToggleControl, TextControl, SelectControl, Button } from '@wordpress/components';
import { Lock, Globe, FileText, Shield, Settings as SettingsIcon, ChevronDown, CheckCircle } from 'lucide-react';

const Settings = () => {
    const [settings, setSettings] = useState({
        loginProtection: {
            enabled: true,
            customUrl: 'secure-login',
            redirectToHomepage: true,
        },
        httpSecurity: {
            enabled: true,
        },
        fileIntegrity: {
            enabled: true,
            frequency: 'daily',
            scanCore: true,
            scanPlugins: true,
            scanThemes: true,
            emailNotifications: true,
            webhookNotifications: false,
        },
        bruteForce: {
            enabled: true,
            maxAttempts: 5,
            lockoutDuration: 30,
            recaptcha: false,
        },
        apiAccess: {
            xmlrpc: true,
            restApi: true,
            restrictRestApi: false,
        },
        twoFactor: {
            enabled: false,
            totpEnabled: false,
        },
        autoUpdate: {
            enabled: false,
        },
        securityHardening: {
            secureAll: true,
            disableFileEditor: true,
            disableDebugMode: true,
            disableUserEnumeration: true,
            hideWpVersion: true,
        },
        auditLog: {
            enabled: true,
            retention: 30,
            notifications: true,
            notificationMethod: 'email',
            notificationEmail: 'admin@example.com',
            logLevel: 'all',
        },
    });

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [showSuccessAlert, setShowSuccessAlert] = useState(false);
    const [expandedSections, setExpandedSections] = useState({
        loginProtection: true,
        httpSecurity: true,
        fileIntegrity: true,
        bruteForce: true,
        apiAccess: true,
        twoFactor: true,
        autoUpdate: true,
        securityHardening: true,
        auditLog: true,
    });

    useEffect(() => {
        fetchSettings();
    }, []);

    const fetchSettings = async () => {
        try {
            const response = await fetch('/wp-json/securepressx/v1/settings');
            if (response.ok) {
                const data = await response.json();
                setSettings(data);
            }
        } catch (error) {
            console.error('Error fetching settings:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSaveSettings = async () => {
        setSaving(true);
        try {
            const response = await fetch('/wp-json/securepressx/v1/settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.securePressx.nonce,
                },
                body: JSON.stringify(settings),
            });

            if (response.ok) {
                setShowSuccessAlert(true);
                setTimeout(() => setShowSuccessAlert(false), 3000);
            }
        } catch (error) {
            console.error('Error saving settings:', error);
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
                {renderModernCard('loginProtection', __('Login Protection', 'securepress-x'), <Lock className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable Login Protection', 'securepress-x'),
                            settings.loginProtection.enabled,
                            (value) => updateSetting('loginProtection', 'enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Custom Login URL', 'securepress-x'),
                            settings.loginProtection.customUrl,
                            (value) => updateSetting('loginProtection', 'customUrl', value),
                            'text',
                            null,
                            __('Replace the login URL with a custom slug', 'securepress-x')
                        )}
                        
                        {renderSettingField(
                            __('Redirect to Homepage', 'securepress-x'),
                            settings.loginProtection.redirectToHomepage,
                            (value) => updateSetting('loginProtection', 'redirectToHomepage', value),
                            'toggle',
                            null,
                            __('Redirect 404 errors to homepage', 'securepress-x')
                        )}
                    </div>
                ))}

                {/* HTTP Security Headers */}
                {renderModernCard('httpSecurity', __('HTTP Security Headers', 'securepress-x'), <Globe className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable HTTP Security Headers', 'securepress-x'),
                            settings.httpSecurity.enabled,
                            (value) => updateSetting('httpSecurity', 'enabled', value)
                        )}
                    </div>
                ))}

                {/* File Integrity Scanner */}
                {renderModernCard('fileIntegrity', __('File Integrity Scanner', 'securepress-x'), <FileText className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable File Integrity Scanner', 'securepress-x'),
                            settings.fileIntegrity.enabled,
                            (value) => updateSetting('fileIntegrity', 'enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Scan Frequency', 'securepress-x'),
                            settings.fileIntegrity.frequency,
                            (value) => updateSetting('fileIntegrity', 'frequency', value),
                            'select',
                            [
                                { label: __('Daily', 'securepress-x'), value: 'daily' },
                                { label: __('Weekly', 'securepress-x'), value: 'weekly' },
                                { label: __('Monthly', 'securepress-x'), value: 'monthly' },
                            ]
                        )}
                        
                        {renderSettingField(
                            __('Scan WordPress Core', 'securepress-x'),
                            settings.fileIntegrity.scanCore,
                            (value) => updateSetting('fileIntegrity', 'scanCore', value)
                        )}
                        
                        {renderSettingField(
                            __('Scan Plugins', 'securepress-x'),
                            settings.fileIntegrity.scanPlugins,
                            (value) => updateSetting('fileIntegrity', 'scanPlugins', value)
                        )}
                        
                        {renderSettingField(
                            __('Scan Themes', 'securepress-x'),
                            settings.fileIntegrity.scanThemes,
                            (value) => updateSetting('fileIntegrity', 'scanThemes', value)
                        )}
                        
                        {renderSettingField(
                            __('Email Notifications', 'securepress-x'),
                            settings.fileIntegrity.emailNotifications,
                            (value) => updateSetting('fileIntegrity', 'emailNotifications', value)
                        )}
                        
                        {renderSettingField(
                            __('Webhook Notifications', 'securepress-x'),
                            settings.fileIntegrity.webhookNotifications,
                            (value) => updateSetting('fileIntegrity', 'webhookNotifications', value)
                        )}
                    </div>
                ))}

                {/* Brute Force Protection */}
                {renderModernCard('bruteForce', __('Brute Force Protection', 'securepress-x'), <Shield className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable Brute Force Protection', 'securepress-x'),
                            settings.bruteForce.enabled,
                            (value) => updateSetting('bruteForce', 'enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Maximum Login Attempts', 'securepress-x'),
                            settings.bruteForce.maxAttempts,
                            (value) => updateSetting('bruteForce', 'maxAttempts', parseInt(value)),
                            'number'
                        )}
                        
                        {renderSettingField(
                            __('Lockout Duration (Minutes)', 'securepress-x'),
                            settings.bruteForce.lockoutDuration,
                            (value) => updateSetting('bruteForce', 'lockoutDuration', parseInt(value)),
                            'number'
                        )}
                        
                        {renderSettingField(
                            __('Enable reCAPTCHA', 'securepress-x'),
                            settings.bruteForce.recaptcha,
                            (value) => updateSetting('bruteForce', 'recaptcha', value)
                        )}
                    </div>
                ))}

                {/* API Access */}
                {renderModernCard('apiAccess', __('API Access', 'securepress-x'), <Globe className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable XML-RPC', 'securepress-x'),
                            settings.apiAccess.xmlrpc,
                            (value) => updateSetting('apiAccess', 'xmlrpc', value)
                        )}
                        
                        {renderSettingField(
                            __('Enable REST API', 'securepress-x'),
                            settings.apiAccess.restApi,
                            (value) => updateSetting('apiAccess', 'restApi', value)
                        )}
                        
                        {renderSettingField(
                            __('Restrict REST API Access', 'securepress-x'),
                            settings.apiAccess.restrictRestApi,
                            (value) => updateSetting('apiAccess', 'restrictRestApi', value)
                        )}
                    </div>
                ))}

                {/* Two-Factor Authentication */}
                {renderModernCard('twoFactor', __('Two-Factor Authentication', 'securepress-x'), <Lock className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable 2FA (TOTP)', 'securepress-x'),
                            settings.twoFactor.enabled,
                            (value) => updateSetting('twoFactor', 'enabled', value),
                            'toggle',
                            null,
                            __('Phase 2 Feature - Coming Soon', 'securepress-x')
                        )}
                    </div>
                ))}

                {/* Auto-Update Security Patches */}
                {renderModernCard('autoUpdate', __('Auto-Update Security Patches', 'securepress-x'), <SettingsIcon className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable Auto-Updates for Security Patches', 'securepress-x'),
                            settings.autoUpdate.enabled,
                            (value) => updateSetting('autoUpdate', 'enabled', value),
                            'toggle',
                            null,
                            __('Phase 2 Feature - Coming Soon', 'securepress-x')
                        )}
                    </div>
                ))}

                {/* Security Hardening */}
                {renderModernCard('securityHardening', __('Security Hardening', 'securepress-x'), <Shield className="card-icon" />, (
                    <div className="settings-group">
                        <Button
                            isSecondary
                            onClick={() => updateSetting('securityHardening', 'secureAll', !settings.securityHardening.secureAll)}
                            className="secure-all-button"
                        >
                            <Shield className="button-icon" />
                            {__('Secure All', 'securepress-x')}
                        </Button>

                        {renderSettingField(
                            __('Disable File Editor', 'securepress-x'),
                            settings.securityHardening.disableFileEditor,
                            (value) => updateSetting('securityHardening', 'disableFileEditor', value)
                        )}
                        
                        {renderSettingField(
                            __('Disable Debug Mode', 'securepress-x'),
                            settings.securityHardening.disableDebugMode,
                            (value) => updateSetting('securityHardening', 'disableDebugMode', value)
                        )}
                        
                        {renderSettingField(
                            __('Disable User Enumeration', 'securepress-x'),
                            settings.securityHardening.disableUserEnumeration,
                            (value) => updateSetting('securityHardening', 'disableUserEnumeration', value)
                        )}
                        
                        {renderSettingField(
                            __('Hide WordPress Version', 'securepress-x'),
                            settings.securityHardening.hideWpVersion,
                            (value) => updateSetting('securityHardening', 'hideWpVersion', value)
                        )}
                    </div>
                ))}

                {/* Audit Log & Notifications */}
                {renderModernCard('auditLog', __('Audit Log & Notifications', 'securepress-x'), <FileText className="card-icon" />, (
                    <div className="settings-group">
                        {renderSettingField(
                            __('Enable Audit Logging', 'securepress-x'),
                            settings.auditLog.enabled,
                            (value) => updateSetting('auditLog', 'enabled', value)
                        )}
                        
                        {renderSettingField(
                            __('Log Retention (Days)', 'securepress-x'),
                            settings.auditLog.retention,
                            (value) => updateSetting('auditLog', 'retention', parseInt(value)),
                            'number'
                        )}
                        
                        {renderSettingField(
                            __('Enable Notifications', 'securepress-x'),
                            settings.auditLog.notifications,
                            (value) => updateSetting('auditLog', 'notifications', value)
                        )}
                        
                        {renderSettingField(
                            __('Notification Method', 'securepress-x'),
                            settings.auditLog.notificationMethod,
                            (value) => updateSetting('auditLog', 'notificationMethod', value),
                            'select',
                            [
                                { label: __('Email', 'securepress-x'), value: 'email' },
                                { label: __('Webhook', 'securepress-x'), value: 'webhook' },
                                { label: __('Both', 'securepress-x'), value: 'both' },
                            ]
                        )}
                        
                        {renderSettingField(
                            __('Notification Email', 'securepress-x'),
                            settings.auditLog.notificationEmail,
                            (value) => updateSetting('auditLog', 'notificationEmail', value),
                            'email'
                        )}
                        
                        {renderSettingField(
                            __('Log Level', 'securepress-x'),
                            settings.auditLog.logLevel,
                            (value) => updateSetting('auditLog', 'logLevel', value),
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
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

    const renderCollapsibleCard = (section, title, icon, children) => (
        <Card key={section} className="settings-card">
            <CardHeader
                className="card-header"
                onClick={() => toggleSection(section)}
            >
                <h3 className="card-title">
                    <div className="title-content">
                        {icon}
                        {title}
                    </div>
                    <ChevronDown 
                        className={`chevron-icon ${expandedSections[section] ? 'rotate-180' : ''}`}
                        style={{ transform: expandedSections[section] ? 'rotate(180deg)' : 'rotate(0deg)' }}
                    />
                </h3>
            </CardHeader>
            {expandedSections[section] && (
                <CardBody className="card-content">
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
                        <Card key={i} className="settings-card">
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

            <p className="settings-description">
                {__('Configure your WordPress security settings. Each section below controls different aspects of your site security.', 'securepress-x')}
            </p>

            <div className="settings-grid">
                {/* Login Protection */}
                {renderCollapsibleCard('loginProtection', __('Login Protection', 'securepress-x'), <Lock className="card-icon" />, (
                    <div className="space-y-4">
                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable Login Protection', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.loginProtection.enabled}
                                    onChange={(value) => updateSetting('loginProtection', 'enabled', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <TextControl
                                label={__('Custom Login URL', 'securepress-x')}
                                value={settings.loginProtection.customUrl}
                                onChange={(value) => updateSetting('loginProtection', 'customUrl', value)}
                            />
                            <p className="setting-description">
                                {__('Replace the login URL with a custom slug', 'securepress-x')}
                            </p>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Redirect to Homepage', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.loginProtection.redirectToHomepage}
                                    onChange={(value) => updateSetting('loginProtection', 'redirectToHomepage', value)}
                                />
                            </div>
                            <p className="setting-description">
                                {__('Redirect 404 errors to homepage', 'securepress-x')}
                            </p>
                        </div>
                    </div>
                ))}

                {/* HTTP Security Headers */}
                {renderCollapsibleCard('httpSecurity', __('HTTP Security Headers', 'securepress-x'), <Globe className="card-icon" />, (
                    <div className="setting-field">
                        <div className="setting-control">
                            <label>{__('Enable HTTP Security Headers', 'securepress-x')}</label>
                            <ToggleControl
                                checked={settings.httpSecurity.enabled}
                                onChange={(value) => updateSetting('httpSecurity', 'enabled', value)}
                            />
                        </div>
                    </div>
                ))}

                {/* File Integrity Scanner */}
                {renderCollapsibleCard('fileIntegrity', __('File Integrity Scanner', 'securepress-x'), <FileText className="card-icon" />, (
                    <div className="space-y-4">
                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable File Integrity Scanner', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.fileIntegrity.enabled}
                                    onChange={(value) => updateSetting('fileIntegrity', 'enabled', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <SelectControl
                                label={__('Scan Frequency', 'securepress-x')}
                                value={settings.fileIntegrity.frequency}
                                options={[
                                    { label: __('Daily', 'securepress-x'), value: 'daily' },
                                    { label: __('Weekly', 'securepress-x'), value: 'weekly' },
                                    { label: __('Monthly', 'securepress-x'), value: 'monthly' },
                                ]}
                                onChange={(value) => updateSetting('fileIntegrity', 'frequency', value)}
                            />
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Scan WordPress Core', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.fileIntegrity.scanCore}
                                    onChange={(value) => updateSetting('fileIntegrity', 'scanCore', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Scan Plugins', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.fileIntegrity.scanPlugins}
                                    onChange={(value) => updateSetting('fileIntegrity', 'scanPlugins', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Scan Themes', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.fileIntegrity.scanThemes}
                                    onChange={(value) => updateSetting('fileIntegrity', 'scanThemes', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Email Notifications', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.fileIntegrity.emailNotifications}
                                    onChange={(value) => updateSetting('fileIntegrity', 'emailNotifications', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Webhook Notifications', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.fileIntegrity.webhookNotifications}
                                    onChange={(value) => updateSetting('fileIntegrity', 'webhookNotifications', value)}
                                />
                            </div>
                        </div>
                    </div>
                ))}

                {/* Brute Force Protection */}
                {renderCollapsibleCard('bruteForce', __('Brute Force Protection', 'securepress-x'), <Shield className="card-icon" />, (
                    <div className="space-y-4">
                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable Brute Force Protection', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.bruteForce.enabled}
                                    onChange={(value) => updateSetting('bruteForce', 'enabled', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <TextControl
                                label={__('Maximum Login Attempts', 'securepress-x')}
                                type="number"
                                value={settings.bruteForce.maxAttempts}
                                onChange={(value) => updateSetting('bruteForce', 'maxAttempts', parseInt(value))}
                            />
                        </div>

                        <div className="setting-field">
                            <TextControl
                                label={__('Lockout Duration (Minutes)', 'securepress-x')}
                                type="number"
                                value={settings.bruteForce.lockoutDuration}
                                onChange={(value) => updateSetting('bruteForce', 'lockoutDuration', parseInt(value))}
                            />
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable reCAPTCHA', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.bruteForce.recaptcha}
                                    onChange={(value) => updateSetting('bruteForce', 'recaptcha', value)}
                                />
                            </div>
                        </div>
                    </div>
                ))}

                {/* API Access Control */}
                {renderCollapsibleCard('apiAccess', __('API Access Control', 'securepress-x'), <Globe className="card-icon" />, (
                    <div className="space-y-4">
                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable XML-RPC', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.apiAccess.xmlrpc}
                                    onChange={(value) => updateSetting('apiAccess', 'xmlrpc', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable REST API', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.apiAccess.restApi}
                                    onChange={(value) => updateSetting('apiAccess', 'restApi', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Restrict REST API Access', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.apiAccess.restrictRestApi}
                                    onChange={(value) => updateSetting('apiAccess', 'restrictRestApi', value)}
                                />
                            </div>
                        </div>
                    </div>
                ))}

                {/* Two-Factor Authentication */}
                {renderCollapsibleCard('twoFactor', __('Two-Factor Authentication', 'securepress-x'), <Lock className="card-icon" />, (
                    <div className="space-y-4">
                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable 2FA (TOTP)', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.twoFactor.enabled}
                                    onChange={(value) => updateSetting('twoFactor', 'enabled', value)}
                                />
                            </div>
                        </div>
                        <p className="setting-description">
                            {__('Phase 2 Feature - Coming Soon', 'securepress-x')}
                        </p>
                    </div>
                ))}

                {/* Security Hardening */}
                {renderCollapsibleCard('securityHardening', __('Security Hardening', 'securepress-x'), <Shield className="card-icon" />, (
                    <div className="space-y-4">
                        <Button
                            isSecondary
                            onClick={() => updateSetting('securityHardening', 'secureAll', !settings.securityHardening.secureAll)}
                            className="secure-all-button"
                        >
                            <Shield className="button-icon" />
                            {__('Secure All', 'securepress-x')}
                        </Button>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Disable File Editor', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.securityHardening.disableFileEditor}
                                    onChange={(value) => updateSetting('securityHardening', 'disableFileEditor', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Disable Debug Mode', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.securityHardening.disableDebugMode}
                                    onChange={(value) => updateSetting('securityHardening', 'disableDebugMode', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Disable User Enumeration', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.securityHardening.disableUserEnumeration}
                                    onChange={(value) => updateSetting('securityHardening', 'disableUserEnumeration', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Hide WordPress Version', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.securityHardening.hideWpVersion}
                                    onChange={(value) => updateSetting('securityHardening', 'hideWpVersion', value)}
                                />
                            </div>
                        </div>
                    </div>
                ))}

                {/* Auto-Update Security Patches */}
                {renderCollapsibleCard('autoUpdate', __('Auto-Update Security Patches', 'securepress-x'), <SettingsIcon className="card-icon" />, (
                    <div className="space-y-4">
                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable Auto-Updates for Security Patches', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.autoUpdate.enabled}
                                    onChange={(value) => updateSetting('autoUpdate', 'enabled', value)}
                                />
                            </div>
                        </div>
                        <p className="setting-description">
                            {__('Phase 2 Feature - Coming Soon', 'securepress-x')}
                        </p>
                    </div>
                ))}

                {/* Audit Log & Notifications */}
                {renderCollapsibleCard('auditLog', __('Audit Log & Notifications', 'securepress-x'), <FileText className="card-icon" />, (
                    <div className="space-y-4">
                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable Audit Logging', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.auditLog.enabled}
                                    onChange={(value) => updateSetting('auditLog', 'enabled', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <TextControl
                                label={__('Log Retention (Days)', 'securepress-x')}
                                type="number"
                                value={settings.auditLog.retention}
                                onChange={(value) => updateSetting('auditLog', 'retention', parseInt(value))}
                            />
                        </div>

                        <div className="setting-field">
                            <div className="setting-control">
                                <label>{__('Enable Notifications', 'securepress-x')}</label>
                                <ToggleControl
                                    checked={settings.auditLog.notifications}
                                    onChange={(value) => updateSetting('auditLog', 'notifications', value)}
                                />
                            </div>
                        </div>

                        <div className="setting-field">
                            <SelectControl
                                label={__('Notification Method', 'securepress-x')}
                                value={settings.auditLog.notificationMethod}
                                options={[
                                    { label: __('Email', 'securepress-x'), value: 'email' },
                                    { label: __('Webhook', 'securepress-x'), value: 'webhook' },
                                    { label: __('Both', 'securepress-x'), value: 'both' },
                                ]}
                                onChange={(value) => updateSetting('auditLog', 'notificationMethod', value)}
                            />
                        </div>

                        <div className="setting-field">
                            <TextControl
                                label={__('Notification Email', 'securepress-x')}
                                type="email"
                                value={settings.auditLog.notificationEmail}
                                onChange={(value) => updateSetting('auditLog', 'notificationEmail', value)}
                            />
                        </div>

                        <div className="setting-field">
                            <SelectControl
                                label={__('Log Level', 'securepress-x')}
                                value={settings.auditLog.logLevel}
                                options={[
                                    { label: __('All Events', 'securepress-x'), value: 'all' },
                                    { label: __('Warnings & Errors', 'securepress-x'), value: 'warnings' },
                                    { label: __('Errors Only', 'securepress-x'), value: 'errors' },
                                ]}
                                onChange={(value) => updateSetting('auditLog', 'logLevel', value)}
                            />
                        </div>
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
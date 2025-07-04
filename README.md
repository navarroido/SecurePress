# SecurePress X - WordPress Security Plugin

A comprehensive WordPress security plugin with a modern React-based interface and fallback admin UI.

## Features

### Core Security Modules

1. **Hide Login** - Customize the login URL to hide it from attackers
2. **Security Headers** - Add HTTP security headers (HSTS, CSP, X-Frame-Options, etc.)
3. **File Integrity Scanner** - Monitor file changes and detect potential threats
4. **Brute Force Protection** - Rate limiting and blocking for login attempts
5. **Two Factor Authentication** - TOTP-based 2FA for enhanced login security
6. **Auto-Patcher** - Automated security patching for WordPress core and plugins
7. **Security Hardening** - Security presets and configuration improvements

### Admin Interface

- **React Dashboard** - Modern, responsive interface for security management
- **Fallback Interface** - jQuery-based admin for environments without React
- **REST API** - RESTful API for all admin operations
- **Audit Logging** - Comprehensive security event logging

## File Structure

```
securepressx/
├── securepressx.php              # Main plugin file
├── includes/
│   ├── class-securepress-x.php   # Core plugin class
│   ├── abstract-class-module.php  # Base module class
│   ├── class-installer.php       # Database installer
│   ├── class-logger.php          # Security event logger
│   ├── class-utils.php           # Utility functions
│   ├── modules/                  # Security modules
│   │   ├── class-hide-login.php
│   │   ├── class-security-headers.php
│   │   ├── class-file-integrity.php
│   │   ├── class-bruteforce-protection.php
│   │   ├── class-two-factor-auth.php
│   │   ├── class-auto-patcher.php
│   │   └── class-security-hardening.php
│   └── admin/                    # Admin interface
│       ├── class-admin.php       # Main admin class
│       └── class-rest-api.php    # REST API handler
├── assets/
│   ├── css/
│   │   └── admin.css            # Admin styles
│   ├── js/
│   │   └── admin.js             # Fallback JavaScript
│   └── react/                   # React components (to be built)
└── README.md
```

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'SecurePress X' in the admin menu to configure security settings

## Module Architecture

Each security module extends the base `SecurePress_Module` class and provides:

- **Activation/Deactivation** - Enable or disable specific security features
- **Settings Management** - Configurable options for each module
- **Event Logging** - Security events tracked in audit log
- **Hook Registration** - WordPress hooks for security enforcement

## API Endpoints

The plugin provides REST API endpoints for admin operations:

- `GET /wp-json/securepressx/v1/dashboard` - Get dashboard data
- `POST /wp-json/securepressx/v1/modules/{module}/toggle` - Toggle module state
- `POST /wp-json/securepressx/v1/modules/{module}/settings` - Update module settings
- `POST /wp-json/securepressx/v1/scan` - Start security scan
- `GET /wp-json/securepressx/v1/logs` - Retrieve audit logs

## Development Status

This is a skeleton implementation. Each module contains TODO comments indicating areas that need implementation:

### Immediate Development Priorities

1. **Hide Login Module** - Complete URL rewriting logic
2. **File Integrity Scanner** - Implement file scanning algorithm
3. **Brute Force Protection** - Add rate limiting and IP blocking
4. **React Interface** - Build React components for modern admin UI

### Security Considerations

- All user inputs are sanitized and validated
- Nonce verification for all admin actions
- Capability checks for admin operations
- SQL injection prevention with prepared statements
- XSS protection with output escaping

## License

GPL-2.0+ - See WordPress plugin guidelines for licensing requirements.

## Support

For support and development questions, please refer to the plugin documentation or contact the development team. 
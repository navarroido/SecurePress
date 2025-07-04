import { Routes, Route, useNavigate, useLocation } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';
import { Shield } from 'lucide-react';

// Import pages
import Dashboard from '../pages/Dashboard';
import AuditLog from '../pages/AuditLog';
import Settings from '../pages/Settings';

const App = () => {
    const navigate = useNavigate();
    const location = useLocation();

    const tabs = [
        {
            name: 'dashboard',
            title: __('Dashboard', 'securepress-x'),
            className: 'tab-dashboard'
        },
        {
            name: 'audit-log',
            title: __('Audit Log', 'securepress-x'),
            className: 'tab-audit-log'
        },
        {
            name: 'settings',
            title: __('Settings', 'securepress-x'),
            className: 'tab-settings'
        }
    ];

    // Get active tab based on current route
    const getActiveTab = () => {
        const pathname = location.pathname;
        if (pathname === '/audit-log') return 'audit-log';
        if (pathname === '/settings') return 'settings';
        return 'dashboard'; // default for '/', '/dashboard', or any other route
    };

    // Handle tab selection
    const handleTabSelect = (tabName) => {
        switch (tabName) {
            case 'dashboard':
                navigate('/');
                break;
            case 'audit-log':
                navigate('/audit-log');
                break;
            case 'settings':
                navigate('/settings');
                break;
            default:
                navigate('/');
        }
    };

    return (
        <div className="securepress-admin-wrap">
            {/* WordPress Admin Header */}
            <div className="securepress-header">
                <div className="header-content">
                    <Shield className="header-icon" />
                    <h1>{__('SecurePress X', 'securepress-x')}</h1>
                </div>
            </div>

            {/* Main Content */}
            <div className="securepress-content">
                <TabPanel
                    key={location.pathname}
                    className="securepress-tabs"
                    activeClass="active-tab"
                    tabs={tabs}
                    initialTabName={getActiveTab()}
                    onSelect={handleTabSelect}
                >
                    {(tab) => (
                        <div className="securepress-tab-content">
                            <Routes>
                                <Route path="/" element={<Dashboard />} />
                                <Route path="/dashboard" element={<Dashboard />} />
                                <Route path="/audit-log" element={<AuditLog />} />
                                <Route path="/settings" element={<Settings />} />
                                <Route path="*" element={<Dashboard />} />
                            </Routes>
                        </div>
                    )}
                </TabPanel>
            </div>

            {/* WordPress Footer */}
            <div className="securepress-footer">
              
            </div>
        </div>
    );
};

export default App; 
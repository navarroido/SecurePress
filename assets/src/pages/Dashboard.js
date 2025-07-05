import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import { 
  Shield, 
  Settings, 
  AlertTriangle, 
  CheckCircle, 
  Activity, 
  FileCheck, 
  Lock, 
  Server,
  RefreshCw,
  XCircle,
  Zap
} from 'lucide-react';

const Dashboard = () => {
    const [dashboardData, setDashboardData] = useState({
        securityScore: 85,
        activeModules: 5,
        threatsBlocked: 42,
        fileIntegrity: {
            ok: 1247,
            modified: 3,
            missing: 1,
            unknown: 0
        },
        loginStats: {
            totalAttempts: 23,
            blockedIps: 5,
            recentAttempts: 8
        },
        systemInfo: {
            wpVersion: '6.4.2',
            phpVersion: '8.2.0',
            pluginVersion: '1.0.0',
            activePlugins: 15,
            currentTheme: 'Twenty Twenty-Four'
        },
        recentEvents: [
            {
                eventType: 'file_integrity_scan',
                severity: 'medium',
                description: '3 modified files detected in active theme',
                createdAt: '2024-01-15 14:30:00'
            },
            {
                eventType: 'login_blocked',
                severity: 'high',
                description: 'IP address 192.168.1.100 blocked after 5 failed attempts',
                createdAt: '2024-01-15 13:45:00'
            },
            {
                eventType: 'malware_scan',
                severity: 'low',
                description: 'Malware scan completed - no threats found',
                createdAt: '2024-01-15 12:00:00'
            }
        ]
    });
    const [loading, setLoading] = useState(true);
    const [scanning, setScanning] = useState(false);

    useEffect(() => {
        fetchDashboardData();
    }, []);

    const fetchDashboardData = async () => {
        try {
            const response = await fetch('/wp-json/securepressx/v1/dashboard');
            if (response.ok) {
                const data = await response.json();
                setDashboardData(data);
            }
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    const runIntegrityScan = async () => {
        setScanning(true);
        // Simulate API call
        setTimeout(() => {
            setScanning(false);
            // Update some mock data
            setDashboardData((prev) => ({
                ...prev,
                fileIntegrity: {
                    ...prev.fileIntegrity,
                    ok: prev.fileIntegrity.ok + 1,
                    modified: Math.max(0, prev.fileIntegrity.modified - 1),
                }
            }));
        }, 3000);
    };

    const getScoreColor = (score) => {
        if (score >= 80) return "text-green-600";
        if (score >= 60) return "text-yellow-600";
        return "text-red-600";
    };

    const getScoreStatus = (score) => {
        if (score >= 80) return "Excellent";
        if (score >= 60) return "Good";
        return "Needs Improvement";
    };

    const getScoreBgColor = (score) => {
        if (score >= 80) return "from-green-500 to-green-600";
        if (score >= 60) return "from-yellow-500 to-yellow-600";
        return "from-red-500 to-red-600";
    };

    if (loading) {
        return (
            <div className="securepress-dashboard">
                <div className="dashboard-grid">
                    {[1, 2, 3].map((i) => (
                        <Card key={i} className="dashboard-card">
                            <CardBody>
                                <div className="animate-pulse">
                                    <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div className="h-8 bg-gray-200 rounded w-1/2 mb-1"></div>
                                    <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </CardBody>
                        </Card>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="securepress-dashboard fade-in">
            {/* Security Score */}
            <Card className="relative overflow-hidden mb-6">
                <CardHeader>
                    <h3 className="flex items-center gap-2 text-lg font-bold">
                        <Activity className="h-5 w-5" />
                        {__('Overall Security Score', 'securepress-x')}
                    </h3>
                </CardHeader>
                <CardBody>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-6">
                            <div className="relative">
                                <div className={`w-24 h-24 rounded-full bg-gradient-to-br ${getScoreBgColor(dashboardData.securityScore)} flex items-center justify-center`}>
                                    <span className="text-2xl font-bold text-white">{dashboardData.securityScore}</span>
                                </div>
                                <div className="absolute -bottom-2 -right-2 bg-white rounded-full p-1">
                                    {dashboardData.securityScore >= 80 ? (
                                        <CheckCircle className="h-6 w-6 text-green-600" />
                                    ) : (
                                        <AlertTriangle className="h-6 w-6 text-yellow-600" />
                                    )}
                                </div>
                            </div>
                            <div>
                                <h3 className={`text-2xl font-bold ${getScoreColor(dashboardData.securityScore)}`}>
                                    {getScoreStatus(dashboardData.securityScore)}
                                </h3>
                                <p className="text-gray-600">out of 100 points</p>
                                <div className="w-48 mt-2 bg-gray-200 rounded-full h-2.5">
                                    <div 
                                        className={`h-2.5 rounded-full ${dashboardData.securityScore >= 80 ? 'bg-green-600' : dashboardData.securityScore >= 60 ? 'bg-yellow-600' : 'bg-red-600'}`}
                                        style={{ width: `${dashboardData.securityScore}%` }}
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <div className="text-right">
                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span className="text-sm">Active Security</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span className="text-sm">Needs Attention</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-3 h-3 bg-red-500 rounded-full"></div>
                                    <span className="text-sm">High Risk</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardBody>
            </Card>

            {/* Quick Actions */}
            <Card className="mb-6">
                <CardHeader>
                    <h3 className="flex items-center gap-2 text-lg font-bold">
                        <Zap className="h-5 w-5" />
                        {__('Quick Actions', 'securepress-x')}
                    </h3>
                </CardHeader>
                <CardBody>
                    <div className="flex flex-wrap gap-3">
                        <button 
                            onClick={runIntegrityScan} 
                            disabled={scanning} 
                            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-blue-300"
                        >
                            {scanning ? <RefreshCw className="h-4 w-4 animate-spin" /> : <FileCheck className="h-4 w-4" />}
                            {scanning ? __('Scanning...', 'securepress-x') : __('Scan File Integrity', 'securepress-x')}
                        </button>
                        <button className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded hover:bg-gray-100">
                            <Shield className="h-4 w-4" />
                            {__('Run Security Wizard', 'securepress-x')}
                        </button>
                        <button className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded hover:bg-gray-100">
                            <Settings className="h-4 w-4" />
                            {__('Update Settings', 'securepress-x')}
                        </button>
                    </div>
                </CardBody>
            </Card>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                {/* File Integrity */}
                <Card>
                    <CardHeader>
                        <h3 className="flex items-center gap-2 text-lg font-bold">
                            <FileCheck className="h-5 w-5" />
                            {__('File Integrity', 'securepress-x')}
                        </h3>
                    </CardHeader>
                    <CardBody>
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="text-center p-3 bg-green-50 rounded-lg">
                                    <div className="text-2xl font-bold text-green-600">{dashboardData.fileIntegrity.ok}</div>
                                    <div className="text-sm text-gray-600">{__('Protected', 'securepress-x')}</div>
                                </div>
                                <div className="text-center p-3 bg-red-50 rounded-lg">
                                    <div className="text-2xl font-bold text-red-600">{dashboardData.fileIntegrity.modified}</div>
                                    <div className="text-sm text-gray-600">{__('Modified', 'securepress-x')}</div>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between text-sm mb-1">
                                    <span>{__('Overall Health', 'securepress-x')}</span>
                                    <span>100%</span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-2.5">
                                    <div className="bg-green-600 h-2.5 rounded-full" style={{ width: '100%' }}></div>
                                </div>
                            </div>

                            {dashboardData.fileIntegrity.modified > 0 && (
                                <div className="flex p-3 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                                    <AlertTriangle className="h-4 w-4 text-yellow-500 mr-2 flex-shrink-0 mt-0.5" />
                                    <p className="text-sm">
                                        {dashboardData.fileIntegrity.modified} {__('files have been modified. Check details in File Integrity tab.', 'securepress-x')}
                                    </p>
                                </div>
                            )}
                        </div>
                    </CardBody>
                </Card>

                {/* Login Security */}
                <Card>
                    <CardHeader>
                        <h3 className="flex items-center gap-2 text-lg font-bold">
                            <Lock className="h-5 w-5" />
                            {__('Login Security', 'securepress-x')}
                        </h3>
                    </CardHeader>
                    <CardBody>
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 gap-3">
                                <div className="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span className="text-sm">{__('Failed Login Attempts:', 'securepress-x')}</span>
                                    <span className={`px-2 py-1 text-xs font-semibold rounded ${dashboardData.loginStats.totalAttempts > 10 ? 'bg-red-100 text-red-800' : 'bg-gray-200 text-gray-800'}`}>
                                        {dashboardData.loginStats.totalAttempts}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span className="text-sm">{__('Blocked IPs:', 'securepress-x')}</span>
                                    <span className={`px-2 py-1 text-xs font-semibold rounded ${dashboardData.loginStats.blockedIps > 0 ? 'bg-red-100 text-red-800' : 'bg-gray-200 text-gray-800'}`}>
                                        {dashboardData.loginStats.blockedIps}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span className="text-sm">{__('24-Hour Activity:', 'securepress-x')}</span>
                                    <span className={`px-2 py-1 text-xs font-semibold rounded ${dashboardData.loginStats.recentAttempts > 5 ? 'bg-red-100 text-red-800' : 'bg-gray-200 text-gray-800'}`}>
                                        {dashboardData.loginStats.recentAttempts}
                                    </span>
                                </div>
                            </div>

                            {dashboardData.loginStats.blockedIps > 0 && (
                                <div className="flex p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                                    <Shield className="h-4 w-4 text-blue-500 mr-2 flex-shrink-0 mt-0.5" />
                                    <p className="text-sm">
                                        {dashboardData.loginStats.blockedIps} {__('IP addresses currently blocked due to suspicious activity.', 'securepress-x')}
                                    </p>
                                </div>
                            )}
                        </div>
                    </CardBody>
                </Card>

                {/* System Info */}
                <Card>
                    <CardHeader>
                        <h3 className="flex items-center gap-2 text-lg font-bold">
                            <Server className="h-5 w-5" />
                            {__('System Information', 'securepress-x')}
                        </h3>
                    </CardHeader>
                    <CardBody>
                        <div className="space-y-3">
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">{__('WordPress:', 'securepress-x')}</span>
                                <span className="font-medium">{dashboardData.systemInfo.wpVersion}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">{__('PHP:', 'securepress-x')}</span>
                                <span className="font-medium">{dashboardData.systemInfo.phpVersion}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">{__('Security Plugin:', 'securepress-x')}</span>
                                <span className="font-medium">{dashboardData.systemInfo.pluginVersion}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">{__('Active Plugins:', 'securepress-x')}</span>
                                <span className="font-medium">{dashboardData.systemInfo.activePlugins}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-sm text-gray-600">{__('Current Theme:', 'securepress-x')}</span>
                                <span className="font-medium">{dashboardData.systemInfo.currentTheme}</span>
                            </div>
                        </div>
                    </CardBody>
                </Card>
            </div>

            {/* Recent Events */}
            <Card>
                <CardHeader>
                    <h3 className="flex items-center gap-2 text-lg font-bold">
                        <Activity className="h-5 w-5" />
                        {__('Recent Security Events', 'securepress-x')}
                    </h3>
                </CardHeader>
                <CardBody>
                    <div className="space-y-3">
                        {dashboardData.recentEvents && dashboardData.recentEvents.length > 0 ? (
                            dashboardData.recentEvents.map((event, index) => (
                                <div key={index} className="flex items-start justify-between p-3 border rounded-lg">
                                    <div className="flex-1">
                                        <p className="text-sm font-medium">{event.description}</p>
                                        <p className="text-xs text-gray-500 mt-1">{event.createdAt}</p>
                                    </div>
                                    <span className={`px-2 py-1 text-xs font-semibold rounded ${
                                        event.severity === 'low' ? 'bg-green-100 text-green-800' : 
                                        event.severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                        'bg-red-100 text-red-800'
                                    }`}>
                                        {event.severity}
                                    </span>
                                </div>
                            ))
                        ) : (
                            <div className="text-center py-8 text-gray-500">
                                {__('No recent security events', 'securepress-x')}
                            </div>
                        )}
                    </div>
                </CardBody>
            </Card>
        </div>
    );
};

export default Dashboard; 
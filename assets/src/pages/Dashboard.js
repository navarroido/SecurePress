import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import { Shield, Settings, AlertTriangle, CheckCircle } from 'lucide-react';

const Dashboard = () => {
    const [dashboardData, setDashboardData] = useState({
        securityScore: 75,
        activeModules: 5,
        threatsBlocked: 42,
        recentEvents: []
    });
    const [loading, setLoading] = useState(true);

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
            <div className="dashboard-grid">
                {/* Security Score Card */}
                <Card className="dashboard-card">
                    <div className="card-header">
                        <h3 className="card-title">{__('Security Score', 'securepress-x')}</h3>
                        <Shield className="card-icon" />
                    </div>
                    <div className="card-value">{dashboardData.securityScore}%</div>
                    <p className="card-description">{__('Security Score', 'securepress-x')}</p>
                </Card>

                {/* Active Modules Card */}
                <Card className="dashboard-card">
                    <div className="card-header">
                        <h3 className="card-title">{__('Active Modules', 'securepress-x')}</h3>
                        <Settings className="card-icon" />
                    </div>
                    <div className="card-value">{dashboardData.activeModules}</div>
                    <p className="card-description">{__('Active Modules', 'securepress-x')}</p>
                </Card>

                {/* Threats Blocked Card */}
                <Card className="dashboard-card">
                    <div className="card-header">
                        <h3 className="card-title">{__('Threats Blocked', 'securepress-x')}</h3>
                        <AlertTriangle className="card-icon" />
                    </div>
                    <div className="card-value">{dashboardData.threatsBlocked}</div>
                    <p className="card-description">{__('Threats Blocked', 'securepress-x')}</p>
                </Card>

                {/* Recent Events Card */}
                <Card className="dashboard-card recent-events-card">
                    <CardHeader>
                        <h3 className="card-title">{__('Recent Events', 'securepress-x')}</h3>
                    </CardHeader>
                    <CardBody>
                        <div className="card-content">
                            {dashboardData.recentEvents && dashboardData.recentEvents.length > 0 ? (
                                <div className="space-y-3">
                                    {dashboardData.recentEvents.slice(0, 5).map((event, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                                            <div className="flex items-center gap-3">
                                                {event.severity === 'warning' ? (
                                                    <AlertTriangle className="h-4 w-4 text-yellow-500" />
                                                ) : event.severity === 'error' ? (
                                                    <AlertTriangle className="h-4 w-4 text-red-500" />
                                                ) : (
                                                    <CheckCircle className="h-4 w-4 text-blue-500" />
                                                )}
                                                <div>
                                                    <div className="font-medium text-sm">{event.event}</div>
                                                    <div className="text-xs text-gray-500">{event.time}</div>
                                                </div>
                                            </div>
                                            <div className="text-xs text-gray-500">{event.user}</div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    {__('No recent security events', 'securepress-x')}
                                </div>
                            )}
                        </div>
                    </CardBody>
                </Card>
            </div>
        </div>
    );
};

export default Dashboard; 
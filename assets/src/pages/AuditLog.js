import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import { FileText, Search, AlertTriangle, XCircle, Info } from 'lucide-react';

const AuditLog = () => {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        type: '',
        severity: '',
        date_from: '',
        date_to: '',
        search: ''
    });
    const [pagination, setPagination] = useState({
        page: 1,
        pages: 1,
        total: 0,
        per_page: 20
    });

    useEffect(() => {
        fetchLogs();
    }, [filters, pagination.page]);

    const fetchLogs = async () => {
        try {
            setLoading(true);
            
            const params = new URLSearchParams({
                page: pagination.page,
                per_page: pagination.per_page,
                ...filters
            });

            // Get nonce from wp_rest
            const nonce = wpApiSettings.nonce;
            
            const response = await fetch(`/wp-json/securepressx/v1/logs?${params}`, {
                headers: {
                    'X-WP-Nonce': nonce,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`API error: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data && data.logs) {
                setLogs(data.logs);
                setPagination({
                    page: parseInt(pagination.page),
                    pages: parseInt(data.pages) || 1,
                    total: parseInt(data.total) || 0,
                    per_page: pagination.per_page
                });
            } else {
                console.error('Invalid response format:', data);
                setLogs([]);
            }
        } catch (error) {
            console.error('Error fetching audit logs:', error);
            setLogs([]);
        } finally {
            setLoading(false);
        }
    };

    const handleFilterChange = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value }));
        setPagination(prev => ({ ...prev, page: 1 }));
    };

    const getSeverityIcon = (severity) => {
        switch (severity) {
            case 'warning':
                return <AlertTriangle className="severity-icon warning" />;
            case 'error':
                return <XCircle className="severity-icon error" />;
            case 'info':
            default:
                return <Info className="severity-icon info" />;
        }
    };

    const formatEventType = (type) => {
        return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    };
    
    const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleString();
    };

    if (loading && logs.length === 0) {
        return (
            <div className="securepress-audit-log">
                <Card className="audit-card">
                    <CardBody>
                        <div className="animate-pulse">
                            <div className="h-8 bg-gray-200 rounded w-1/4 mb-4"></div>
                            <div className="space-y-3">
                                {[1, 2, 3, 4, 5].map((i) => (
                                    <div key={i} className="h-12 bg-gray-200 rounded"></div>
                                ))}
                            </div>
                        </div>
                    </CardBody>
                </Card>
            </div>
        );
    }

    return (
        <div className="securepress-audit-log fade-in">
            <Card className="audit-card">
                <CardHeader className="card-header">
                    <h2 className="card-title">
                        <FileText className="title-icon" />
                        {__('Security Audit Log', 'securepress-x')}
                    </h2>

                    {/* Filters */}
                    <div className="filters-grid">
                        <div className="filter-field">
                            <label htmlFor="event-type">{__('Event Type', 'securepress-x')}</label>
                            <select
                                id="event-type"
                                value={filters.type}
                                onChange={(e) => handleFilterChange('type', e.target.value)}
                            >
                                <option value="">{__('All Types', 'securepress-x')}</option>
                                <option value="login_success">{__('Login Success', 'securepress-x')}</option>
                                <option value="login_failed">{__('Login Failed', 'securepress-x')}</option>
                                <option value="logout">{__('Logout', 'securepress-x')}</option>
                                <option value="option_update">{__('Option Update', 'securepress-x')}</option>
                                <option value="plugin_activation">{__('Plugin Activation', 'securepress-x')}</option>
                                <option value="plugin_deactivation">{__('Plugin Deactivation', 'securepress-x')}</option>
                                <option value="user_registration">{__('User Registration', 'securepress-x')}</option>
                                <option value="user_deletion">{__('User Deletion', 'securepress-x')}</option>
                            </select>
                        </div>

                        <div className="filter-field">
                            <label htmlFor="severity">{__('Severity', 'securepress-x')}</label>
                            <select
                                id="severity"
                                value={filters.severity}
                                onChange={(e) => handleFilterChange('severity', e.target.value)}
                            >
                                <option value="">{__('All Severities', 'securepress-x')}</option>
                                <option value="info">{__('Info', 'securepress-x')}</option>
                                <option value="warning">{__('Warning', 'securepress-x')}</option>
                                <option value="error">{__('Error', 'securepress-x')}</option>
                            </select>
                        </div>

                        <div className="filter-field">
                            <label htmlFor="from-date">{__('From Date', 'securepress-x')}</label>
                            <input
                                type="date"
                                id="from-date"
                                value={filters.date_from}
                                onChange={(e) => handleFilterChange('date_from', e.target.value)}
                            />
                        </div>

                        <div className="filter-field">
                            <label htmlFor="to-date">{__('To Date', 'securepress-x')}</label>
                            <input
                                type="date"
                                id="to-date"
                                value={filters.date_to}
                                onChange={(e) => handleFilterChange('date_to', e.target.value)}
                            />
                        </div>

                        <div className="filter-field">
                            <label htmlFor="search">{__('Search', 'securepress-x')}</label>
                            <div className="search-field">
                                <Search className="search-icon" />
                                <input
                                    type="text"
                                    id="search"
                                    placeholder={__('Search logs...', 'securepress-x')}
                                    value={filters.search}
                                    onChange={(e) => handleFilterChange('search', e.target.value)}
                                />
                            </div>
                        </div>
                    </div>
                </CardHeader>

                <CardBody className="card-content">
                    <table className="audit-table">
                        <thead>
                            <tr>
                                <th>{__('Time', 'securepress-x')}</th>
                                <th>{__('Type', 'securepress-x')}</th>
                                <th>{__('Message', 'securepress-x')}</th>
                                <th>{__('IP', 'securepress-x')}</th>
                                <th>{__('User', 'securepress-x')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.length > 0 ? (
                                logs.map((log) => (
                                    <tr key={log.id}>
                                        <td className="time-cell">{formatDate(log.timestamp)}</td>
                                        <td>
                                            <div className="type-content">
                                                {getSeverityIcon(log.severity)}
                                                <span>{formatEventType(log.type)}</span>
                                            </div>
                                        </td>
                                        <td>{log.message}</td>
                                        <td className="ip-cell">{log.ip}</td>
                                        <td>{log.user}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="text-center py-8 text-gray-500">
                                        {__('No audit logs found', 'securepress-x')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>

                    {/* Pagination */}
                    {pagination.total > 0 && (
                        <div className="pagination">
                            {__('Items:', 'securepress-x')} {pagination.total} | 
                            {pagination.page > 1 && (
                                <button
                                    onClick={() => setPagination(prev => ({ ...prev, page: prev.page - 1 }))}
                                    className="ml-2 text-blue-600 hover:underline"
                                >
                                    {__('Previous', 'securepress-x')}
                                </button>
                            )}
                            <span className="mx-2">
                                {pagination.page} {__('of', 'securepress-x')} {pagination.pages}
                            </span>
                            {pagination.page < pagination.pages && (
                                <button
                                    onClick={() => setPagination(prev => ({ ...prev, page: prev.page + 1 }))}
                                    className="text-blue-600 hover:underline"
                                >
                                    {__('Next', 'securepress-x')}
                                </button>
                            )}
                        </div>
                    )}
                </CardBody>
            </Card>
        </div>
    );
};

export default AuditLog; 
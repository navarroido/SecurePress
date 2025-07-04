import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Card, CardBody, CardHeader } from '@wordpress/components';
import { FileText, Search, AlertTriangle, XCircle, Info } from 'lucide-react';

const AuditLog = () => {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        eventType: 'all',
        severity: 'all',
        fromDate: '',
        toDate: '',
        search: ''
    });
    const [pagination, setPagination] = useState({
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
        itemsPerPage: 10
    });

    useEffect(() => {
        fetchLogs();
    }, [filters, pagination.currentPage]);

    const fetchLogs = async () => {
        try {
            const params = new URLSearchParams({
                page: pagination.currentPage,
                per_page: pagination.itemsPerPage,
                ...filters
            });

            const response = await fetch(`/wp-json/securepressx/v1/audit-log?${params}`);
            if (response.ok) {
                const data = await response.json();
                setLogs(data.logs || []);
                setPagination(prev => ({
                    ...prev,
                    totalPages: data.total_pages || 1,
                    totalItems: data.total_items || 0
                }));
            }
        } catch (error) {
            console.error('Error fetching audit logs:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilterChange = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value }));
        setPagination(prev => ({ ...prev, currentPage: 1 }));
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

    if (loading) {
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
                                value={filters.eventType}
                                onChange={(e) => handleFilterChange('eventType', e.target.value)}
                            >
                                <option value="all">{__('All Types', 'securepress-x')}</option>
                                <option value="settings_update">{__('Settings Update', 'securepress-x')}</option>
                                <option value="module_update">{__('Module Update', 'securepress-x')}</option>
                                <option value="blocked_admin_access">{__('Blocked Admin Access', 'securepress-x')}</option>
                                <option value="blocked_login_access">{__('Blocked Login Access', 'securepress-x')}</option>
                                <option value="login_attempt">{__('Login Attempt', 'securepress-x')}</option>
                            </select>
                        </div>

                        <div className="filter-field">
                            <label htmlFor="severity">{__('Severity', 'securepress-x')}</label>
                            <select
                                id="severity"
                                value={filters.severity}
                                onChange={(e) => handleFilterChange('severity', e.target.value)}
                            >
                                <option value="all">{__('All Severities', 'securepress-x')}</option>
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
                                value={filters.fromDate}
                                onChange={(e) => handleFilterChange('fromDate', e.target.value)}
                            />
                        </div>

                        <div className="filter-field">
                            <label htmlFor="to-date">{__('To Date', 'securepress-x')}</label>
                            <input
                                type="date"
                                id="to-date"
                                value={filters.toDate}
                                onChange={(e) => handleFilterChange('toDate', e.target.value)}
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
                                <th>{__('Event', 'securepress-x')}</th>
                                <th>{__('IP', 'securepress-x')}</th>
                                <th>{__('User', 'securepress-x')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.length > 0 ? (
                                logs.map((log, index) => (
                                    <tr key={index}>
                                        <td className="time-cell">{log.time}</td>
                                        <td>
                                            <div className="type-content">
                                                {getSeverityIcon(log.severity)}
                                                <span>{formatEventType(log.type)}</span>
                                            </div>
                                        </td>
                                        <td>{log.event}</td>
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
                    <div className="pagination">
                        {__('Items:', 'securepress-x')} {pagination.totalItems} | 
                        {pagination.currentPage > 1 && (
                            <button
                                onClick={() => setPagination(prev => ({ ...prev, currentPage: prev.currentPage - 1 }))}
                                className="ml-2 text-blue-600 hover:underline"
                            >
                                {__('Previous', 'securepress-x')}
                            </button>
                        )}
                        <span className="mx-2">
                            {pagination.currentPage} {__('of', 'securepress-x')} {pagination.totalPages}
                        </span>
                        {pagination.currentPage < pagination.totalPages && (
                            <button
                                onClick={() => setPagination(prev => ({ ...prev, currentPage: prev.currentPage + 1 }))}
                                className="text-blue-600 hover:underline"
                            >
                                {__('Next', 'securepress-x')}
                            </button>
                        )}
                    </div>
                </CardBody>
            </Card>
        </div>
    );
};

export default AuditLog; 
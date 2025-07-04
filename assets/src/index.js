import { render } from '@wordpress/element';
import { HashRouter } from 'react-router-dom';
import App from './components/App';
import './styles/main.scss';

// Configure API fetch with credentials and nonce
import apiFetch from '@wordpress/api-fetch';

// Debug: Log available global variables
console.log('SecurePress X: Available globals:', window.securePressx);
console.log('SecurePress X: All window properties:', Object.keys(window));

// Check if our global variable exists
if (window.securePressx) {
    console.log('SecurePress X: Global config found:', window.securePressx);
    apiFetch.use(apiFetch.createNonceMiddleware(window.securePressx.nonce));
    apiFetch.use(apiFetch.createRootURLMiddleware(window.securePressx.apiUrl));
} else {
    console.error('SecurePress X: Global config not found! Available globals:', Object.keys(window));
}

// Initialize the app
const initApp = () => {
    console.log('SecurePress X: DOM ready, initializing app...');
    const rootElement = document.getElementById('securepress-admin-app');
    console.log('SecurePress X: Root element:', rootElement);
    
    if (rootElement) {
        console.log('SecurePress X: Rendering app...');
        try {
            render(
                <HashRouter>
                    <App />
                </HashRouter>,
                rootElement
            );
            console.log('SecurePress X: App rendered successfully');
        } catch (error) {
            console.error('SecurePress X: Error rendering app:', error);
        }
    } else {
        console.error('SecurePress X: Root element #securepress-admin-app not found');
        console.log('SecurePress X: Available elements with id:', 
            Array.from(document.querySelectorAll('[id]')).map(el => el.id)
        );
    }
};

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    console.log('SecurePress X: DOM still loading, waiting...');
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    console.log('SecurePress X: DOM already ready, initializing...');
    initApp();
} 
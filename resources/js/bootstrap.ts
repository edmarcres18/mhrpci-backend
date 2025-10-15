import axios from 'axios';

/**
 * Configure Axios for Laravel
 * This sets up CSRF token handling and default headers
 */
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.withCredentials = true;

// Function to get fresh CSRF token from meta tag
function getCSRFToken(): string | null {
    const token = document.head.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    return token ? token.content : null;
}

// Set initial CSRF token
const token = getCSRFToken();
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
} else {
    console.error('CSRF token not found. Make sure the meta tag is present in your HTML.');
}

// Flag to prevent multiple reload attempts
let isReloadScheduled = false;

// Add request interceptor to always use fresh CSRF token
axios.interceptors.request.use(
    (config) => {
        const freshToken = getCSRFToken();
        if (freshToken) {
            config.headers['X-CSRF-TOKEN'] = freshToken;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Add response interceptor for better error handling
axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response?.status === 419) {
            // CSRF token mismatch - try to refresh once before reloading
            if (!isReloadScheduled) {
                console.warn('CSRF token mismatch detected. Attempting to refresh...');
                
                // Check if we can get a fresh token from the meta tag
                const freshToken = getCSRFToken();
                if (freshToken && freshToken !== error.config.headers['X-CSRF-TOKEN']) {
                    // Update the token and retry the request once
                    error.config.headers['X-CSRF-TOKEN'] = freshToken;
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = freshToken;
                    
                    try {
                        return await axios.request(error.config);
                    } catch (retryError) {
                        // If retry also fails, schedule reload
                        console.error('CSRF token refresh failed. Reloading page...');
                        isReloadScheduled = true;
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } else {
                    // No fresh token available, reload the page
                    console.error('Session expired. Reloading page...');
                    isReloadScheduled = true;
                    setTimeout(() => window.location.reload(), 1000);
                }
            }
        } else if (error.response?.status === 401) {
            // Unauthorized - session might have expired
            console.warn('Unauthorized request. Session may have expired.');
        }
        return Promise.reject(error);
    }
);

export default axios;

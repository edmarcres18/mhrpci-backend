import axios from 'axios';

/**
 * Configure Axios for Laravel
 * This sets up CSRF token handling and default headers
 */

// Get CSRF token from meta tag
const token = document.head.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');

// Configure axios with a single defaults object
Object.assign(axios.defaults.headers.common, {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    ...(token && { 'X-CSRF-TOKEN': token.content })
});

if (!token) {
    console.error('CSRF token not found. Make sure the meta tag is present in your HTML.');
}

// Add response interceptor for better error handling
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 419) {
            // CSRF token mismatch - reload page to get new token
            console.error('CSRF token mismatch. Reloading page...');
            window.location.reload();
        }
        return Promise.reject(error);
    }
);

export default axios;

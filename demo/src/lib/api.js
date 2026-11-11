// Small helper for talking to the Laravel backend.
// Every request goes to http://localhost:8080/api + the path we pass in.

const API_BASE = 'http://localhost:8080/api';

// Reads the saved login token (written by auth.svelte.js after login).
function getToken() {
	return localStorage.getItem('expense-app-token');
}

/**
 * Calls one endpoint of the backend.
 *
 * @param {string} path - e.g. '/expenses' or '/login'
 * @param {object} [options] - { method: 'POST', body: { ... } }
 * @returns the parsed JSON from the backend
 */
export async function api(path, options = {}) {
	const headers = { Accept: 'application/json' };

	// If we send a body, it is always JSON.
	if (options.body) {
		headers['Content-Type'] = 'application/json';
	}

	// Attach the login token if we have one (Sanctum bearer tokens).
	const token = getToken();
	if (token) {
		headers['Authorization'] = 'Bearer ' + token;
	}

	const response = await fetch(API_BASE + path, {
		method: options.method || 'GET',
		headers,
		body: options.body ? JSON.stringify(options.body) : undefined
	});

	// The backend always answers with JSON, even for errors.
	const data = await response.json();

	if (!response.ok) {
		// Backend errors look like { "message": "..." }.
		// We throw so the page can show the message to the user.
		const error = new Error(data.message || 'Something went wrong');
		error.status = response.status; // e.g. 401, 404...
		throw error;
	}

	return data;
}

// Removes the saved token + user from the browser.
// Used when logging out, or when the backend rejects our token.
export function clearLoginStorage() {
	localStorage.removeItem('expense-app-token');
	localStorage.removeItem('expense-app-user');
}

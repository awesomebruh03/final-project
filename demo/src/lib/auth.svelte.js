// Handles register / login / logout by calling the backend,
// and remembers who is logged in while the app is open.
//
// Note for later: the token is kept in localStorage, which is fine for
// a university project but not how a big production app would do it.

import { api, clearLoginStorage } from './api.js';

const USER_KEY = 'expense-app-user';
const TOKEN_KEY = 'expense-app-token';

// The currently logged-in user, shared across the whole app.
// Starts from what was saved in localStorage, so refreshing the
// page keeps you logged in. null means "not logged in".
export const auth = $state({
	user: JSON.parse(localStorage.getItem(USER_KEY) || 'null')
});

// Save the user + token coming back from the backend.
function saveLogin(user, token) {
	auth.user = user;
	localStorage.setItem(USER_KEY, JSON.stringify(user));
	localStorage.setItem(TOKEN_KEY, token);
}

// Creates a new account. The backend logs us in right away.
// Throws (with the backend's message) if the email is already used.
export async function register(name, email, password) {
	const data = await api('/register', {
		method: 'POST',
		body: { name, email, password }
	});
	saveLogin(data.user, data.token);
}

// Logs in with email + password. Throws if they are wrong.
export async function login(email, password) {
	const data = await api('/login', {
		method: 'POST',
		body: { email, password }
	});
	saveLogin(data.user, data.token);
}

// Logs out: asks the backend to revoke the token, then forgets
// everything locally. Even if the backend call fails (e.g. it is
// down), we still log out on our side.
export async function logout() {
	try {
		await api('/logout', { method: 'POST' });
	} catch {
		// ignore — we are logging out anyway
	}
	auth.user = null;
	clearLoginStorage();
}

// Called when the backend says our token is no longer valid (401):
// forget the login so the app sends the user to the auth page.
export function expireSession() {
	auth.user = null;
	clearLoginStorage();
}

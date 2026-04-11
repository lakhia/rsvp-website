import { goto } from '$app/navigation';

export function getCookie(name) {
	const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
	return match ? decodeURIComponent(match[1]) : null;
}

export function setCookie(name, value, days = 60) {
	const expires = new Date(Date.now() + days * 86400000).toUTCString();
	document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/`;
}

export function clearCookie(name) {
	document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/`;
}

export function isLoggedIn() {
	return !!getCookie('thaali');
}

export function isAdmin() {
	return getCookie('adv') === '1';
}

export function requireAdmin() {
	if (!isAdmin()) {
		goto('/');
		return false;
	}
	return true;
}

export function logout() {
	clearCookie('token');
	clearCookie('thaali');
	clearCookie('email');
	clearCookie('adv');
	localStorage.clear();
}

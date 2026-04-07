const TIMEOUT_MS = 8000;

function buildUrl(url, params = {}) {
	const u = new URL(url, window.location.origin);
	for (const [k, v] of Object.entries(params)) {
		if (v !== undefined && v !== null && v !== '') {
			u.searchParams.set(k, v);
		}
	}
	return u.toString();
}

export async function get(url, params = {}) {
	const controller = new AbortController();
	const timer = setTimeout(() => controller.abort(), TIMEOUT_MS);
	try {
		const res = await fetch(buildUrl(url, params), {
			signal: controller.signal,
			credentials: 'include'
		});
		return await res.json();
	} finally {
		clearTimeout(timer);
	}
}

export async function post(url, params = {}, body) {
	const controller = new AbortController();
	const timer = setTimeout(() => controller.abort(), TIMEOUT_MS);
	try {
		const res = await fetch(buildUrl(url, params), {
			method: 'POST',
			signal: controller.signal,
			credentials: 'include',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(body)
		});
		return await res.json();
	} finally {
		clearTimeout(timer);
	}
}

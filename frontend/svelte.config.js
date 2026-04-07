import adapter from '@sveltejs/adapter-static';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	kit: {
		adapter: adapter({
			pages: '../build-svelte',
			assets: '../build-svelte',
			fallback: 'index.html'
		})
	}
};

export default config;

import adapter from '@sveltejs/adapter-static';

const inline = process.env.BUILD_SINGLE === '1';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	kit: {
		adapter: adapter({
			pages: '../build',
			assets: '../build',
			fallback: 'index.html'
		}),
		...(inline && {
			output: { bundleStrategy: 'inline' }
		})
	}
};

export default config;

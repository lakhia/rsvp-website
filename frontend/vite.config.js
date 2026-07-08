import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
	// Load .env from the repo root (one level up from frontend/)
	const env = loadEnv(process.env.BUILD_ENV || mode, '../', '');
	const singleFile = process.env.BUILD_SINGLE === '1';
	return {
		plugins: [tailwindcss(), sveltekit()],
		build: singleFile ? { minify: 'oxc' } : {},
		server: {
			host: true,
			proxy: {
				'^/.*\\.php(\\?.*)?$': { target: 'http://localhost:8010', changeOrigin: false }
			}
		},
		define: {
			__THAALI_SIZES__:   JSON.stringify((env.THAALI_SIZES  || 'XS,SM,MD,LG,XL').split(',')),
			__THAALI_RATIOS__:  JSON.stringify((env.THAALI_RATIOS || '0.25,0.5,1.0,1.5,2.0').split(',').map(Number)),
			__BASE_PATH__:      JSON.stringify(env.BASE_PATH       || ''),
			__APP_NAME__:       JSON.stringify(env.APP_NAME        || 'RSVP'),
			__LINK_PLANNING__:  JSON.stringify(env.LINK_PLANNING   || '#'),
			__LINK_FEEDBACK__:  JSON.stringify(env.LINK_FEEDBACK   || '#'),
			__EMAIL_CONTACT__:  JSON.stringify(env.EMAIL_CONTACT   || ''),
			__EMAIL_SECRETARY__: JSON.stringify(env.EMAIL_SECRETARY || ''),
			__SECRETARY_TITLE__: JSON.stringify(env.SECRETARY_TITLE || 'Secretary'),
			__WELCOME_MESSAGE__: JSON.stringify(env.WELCOME_MESSAGE || '#'),
		}
	};
});

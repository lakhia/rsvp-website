import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
	// Load .env from the repo root (one level up from frontend/)
	const env = loadEnv(mode, '../', '');
	return {
		plugins: [tailwindcss(), sveltekit()],
		define: {
			__APP_NAME__:       JSON.stringify(env.APP_NAME        || 'RSVP'),
			__LINK_PLANNING__:  JSON.stringify(env.LINK_PLANNING   || '#'),
			__LINK_FEEDBACK__:  JSON.stringify(env.LINK_FEEDBACK   || '#'),
			__EMAIL_CONTACT__:  JSON.stringify(env.EMAIL_CONTACT   || ''),
			__EMAIL_SECRETARY__: JSON.stringify(env.EMAIL_SECRETARY || ''),
			__SECRETARY_TITLE__: JSON.stringify(env.SECRETARY_TITLE || 'Secretary'),
		}
	};
});

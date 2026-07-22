import adapter from '@sveltejs/adapter-static';
import { readFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));

function readEnvFile(filePath) {
	try {
		return Object.fromEntries(
			readFileSync(filePath, 'utf8').split(/\r?\n/).flatMap((line) => {
				const m = line.match(/^\s*([^#=\s]+)\s*=\s*(.*?)\s*$/);
				return m ? [[m[1], m[2].replace(/^["'](.*)["']$/, '$1')]] : [];
			})
		);
	} catch {
		return {};
	}
}

const buildEnv = process.env.BUILD_ENV;
const env = {
	...readEnvFile(resolve(__dirname, '../.env')),
	...(buildEnv ? readEnvFile(resolve(__dirname, `../.env.${buildEnv}`)) : {}),
};
const basePath = process.env.BASE_PATH || env.BASE_PATH || '';

const inline = process.env.BUILD_SINGLE === '1';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	kit: {
		adapter: adapter({
			pages: '../build',
			assets: '../build',
			fallback: 'index.html'
		}),
		paths: { base: basePath },
		...(inline && {
			output: { bundleStrategy: 'inline' }
		})
	}
};

export default config;

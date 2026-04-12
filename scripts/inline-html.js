'use strict';

const fs = require('fs');
const path = require('path');
const { minify } = require('html-minifier');

const BUILD_DIR = path.resolve('build');
const HTML_FILE = path.join(BUILD_DIR, 'index.html');

const html = fs.readFileSync(HTML_FILE, 'utf8');

const minified = minify(html, {
    collapseWhitespace: true,
    removeComments: true,
    minifyJS: false, // already minified by Vite/oxc
    minifyCSS: false, // already minified by Tailwind/Vite
});

// Strip newlines + indentation inside <script> blocks (left by html-minifier when minifyJS: false)
const cleaned = minified.replace(
    /(<script[^>]*>)([\s\S]*?)(<\/script>)/g,
    (_, open, content, close) => open + content.replace(/\n\s*/g, ' ') + close
);

fs.writeFileSync(HTML_FILE, cleaned);

const kb = n => (n / 1024).toFixed(1) + 'KB';
console.log(`build/index.html: ${kb(html.length)} → ${kb(cleaned.length)}`);

// Remove _app/ — everything is inlined into index.html
fs.rmSync(path.join(BUILD_DIR, '_app'), { recursive: true, force: true });
console.log('Removed build/_app/');

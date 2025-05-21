const fs = require('fs');
const { minify } = require('html-minifier');

function buildIndex() {
  let html = fs.readFileSync('app/index.html', 'utf8');
  const allJs = fs.readFileSync('build/.tmp/all.js', 'utf8');
  const allCss = fs.readFileSync('build/.tmp/all.css', 'utf8');
  const cdnHtml = fs.readFileSync('app/lib/cdn.html', 'utf8');
  
  // Replace script placeholder
  html = html.replace(
    /<!-- build:script -->[\s\S]*?<!-- endbuild -->/g,
    `<script>${allJs}</script>`
  );
  
  // Replace style placeholder
  html = html.replace(
    /<!-- build:style -->[\s\S]*?<!-- endbuild -->/g,
    `<style>${allCss}</style>`
  );
  
  // Replace CDN placeholder
  html = html.replace('#includeCDN', cdnHtml);
  
  // Minify HTML
  const minified = minify(html, {
    collapseWhitespace: true,
    removeComments: true,
    minifyJS: true,
    minifyCSS: true
  });
  
  fs.writeFileSync('build/index.html', minified);
  console.log('Built index.html');
}

buildIndex();

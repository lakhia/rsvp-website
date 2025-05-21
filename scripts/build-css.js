const fs = require('fs');
const CleanCSS = require('clean-css');

function buildCss() {
  const cssFiles = fs.readdirSync('app/css')
    .filter(file => file.endsWith('.css'))
    .map(file => fs.readFileSync(`app/css/${file}`, 'utf8'));
  
  const combined = cssFiles.join('\n');
  const minified = new CleanCSS().minify(combined);
  
  if (!fs.existsSync('build/.tmp')) {
    fs.mkdirSync('build/.tmp', { recursive: true });
  }
  
  fs.writeFileSync('build/.tmp/all.css', minified.styles);
  console.log('Built all.css');
}

buildCss();

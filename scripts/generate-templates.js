const fs = require('fs');
const path = require('path');
const { minify } = require('html-minifier');

function generateTemplateCache() {
  const htmlFiles = fs.readdirSync('app')
    .filter(file => file.endsWith('.html') && file !== 'index.html');
  
  let templates = [];
  
  htmlFiles.forEach(file => {
    const content = fs.readFileSync(path.join('app', file), 'utf8');
    const minified = minify(content, {
      collapseWhitespace: true,
      removeComments: true
    });
    
    templates.push({
      id: file,
      content: minified.replace(/'/g, "\\'").replace(/\n/g, '\\n')
    });
  });
  
  const templateJs = `angular.module('rsvp').run(['$templateCache', function($templateCache) {
${templates.map(t => `  $templateCache.put('${t.id}', '${t.content}');`).join('\n')}
}]);`;
  
  if (!fs.existsSync('build/.tmp')) {
    fs.mkdirSync('build/.tmp', { recursive: true });
  }
  
  fs.writeFileSync('build/.tmp/templates.js', templateJs);
  console.log('Generated templates.js');
}

generateTemplateCache();

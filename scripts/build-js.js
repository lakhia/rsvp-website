const fs = require('fs');
const { minify } = require('terser');

async function buildJs() {

  const files = [
    'app/js/route.js',
    ...fs.readdirSync('app/js').filter(f => f !== 'route.js' && f.endsWith('.js')).map(f => `app/js/${f}`),
    ...fs.readdirSync('app/lib').filter(f => f.endsWith('.js')).map(f => `app/lib/${f}`),
    'build/.tmp/templates.js'
  ];
  
  let combined = '';
  
  files.forEach(file => {
    if (fs.existsSync(file)) {
      combined += fs.readFileSync(file, 'utf8') + '\n';
    }
  });
  
  const result = await minify(combined);
  
  if (!fs.existsSync('build/.tmp')) {
    fs.mkdirSync('build/.tmp', { recursive: true });
  }
  
  fs.writeFileSync('build/.tmp/all.js', result.code);
  console.log('Built all.js');
}

buildJs().catch(console.error);

const fs = require('node:fs');
const path = require('node:path');
const target = path.resolve(__dirname, '../../assets/vendor');
fs.mkdirSync(target, { recursive: true });
for (const name of ['dexie', 'dompurify', 'interactjs', 'marked', 'version-parser']) {
    const root = path.resolve(__dirname, '../node_modules', name);
    const licenses = fs.readdirSync(root).filter(file => /^LICENSE(?:[-.].*)?$/i.test(file));
    if (!licenses.length) throw new Error(`Missing license for ${name}`);
    for (const file of licenses) {
        fs.copyFileSync(path.join(root, file), path.join(target, `${name === 'dompurify' ? 'DOMPurify' : name}.${file}`));
    }
}

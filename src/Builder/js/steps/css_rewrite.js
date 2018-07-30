let path = require('path');
let url = require('url');

module.exports = function (file, config) {
    let content = file.content.toString().replace(/@font-face\s*{([^}]+)}/g, function (match, content) {
        let rewrittenContent = content.replace(/url\(('([^']+)'|"([^"]+)")\)/g, function (match, quoted, u1, u2) {
            let originalFile = u1 || u2;

            // If the font was absolute, leave it be since it must be included in the application itself.
            if (path.isAbsolute(originalFile)) {
                return match;
            }

            // strip away and store any query or hash stuff
            let parsed = url.parse(originalFile);

            let cssPath = path.join(config.paths.out, 'fonts', path.basename(parsed.pathname));
            let fontFile = path.normalize(path.join(config.paths.root, path.dirname(file.name), parsed.pathname));

            file.addAdditionalFile(cssPath, [fontFile]);

            let relativePath = path.relative(path.dirname(path.join(config.paths.out, file.outputFile)), cssPath);

            // make sure to always use the '/' separator, since that is what CSS expects
            relativePath = relativePath.replace(new RegExp('\\' + path.sep, 'g'), '/');

            // Add back any query and hash stuff
            relativePath += (parsed.search || '') + (parsed.hash || '');

            return 'url(' + JSON.stringify(relativePath) + ')';
        });
        return '@font-face {' + rewrittenContent + '}';
    });

    return file.update(Buffer.from(content));
};

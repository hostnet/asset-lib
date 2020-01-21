<p align="center"><a href="http://www.hostnet.nl" target="_blank">
    <img width="400" src="https://www.hostnet.nl/images/hostnet.svg">
</a></p>

[![Travis Status](https://travis-ci.org/hostnet/asset-lib.svg?branch=master)](https://travis-ci.org/hostnet/asset-lib)
[![AppVeyor Status](https://ci.appveyor.com/api/projects/status/github/hostnet/asset-lib?svg=true)](https://ci.appveyor.com/project/yannickl88/asset-lib)

Assets can be manged in different ways. This project aims to provide a way to build assets incrementally without the need of specialized tools such as watchers and therefore requires no change in your development workflow.

This library allows you to create a generic asset pipeline and also provides some built-in processors to get you started quickly.

Core features are:
 - Incremental changes without a watcher
 - Plugin architecture to add your own processing
 - Leverages the parallelization of NodeJS for fast processing
 - Works on both Linux and Windows
 
#### Why use this over webpack?
This project allows for incremental changes without the need for a watcher. Moreover, because the code to check for changes is build in PHP, it has very little overhead when integrated into a PHP development workflow.

#### Why not use a watcher?
Watchers are great if you develop a single project. However, the resource usage can grow when having multiple project. Building assets (in development) when reloading a page only taxes your system when actually needed.

Installation
------------
Instalation can be done using composer:
```bash
composer require hostnet/asset-lib
```

Usage
------------
In the following example we have a setup for building multiple JS files into one large JS file and we added support for less.

For our input we have: 
- `./assets/app.js`
- `./assets/module-a.js` (imported by app.js)
- `./assets/module-b.js` (imported by app.js)
- `./assets/styles.less`

This will output:
- `./web/dist/app.js`
- `./web/dist/styles.css`

Using the following snippet to build the assets. It is recommended to add it to something like a front-controller to build every time a request comes in so once a response comes back, all your assets are ready.

```php
$config = new \Hostnet\Component\Resolver\Config\SimpleConfig(
    true, // is dev
    __DIR__,
    [],
    ['app.js'],
    ['styles.less'],
    'web',
    'dist',
    'assets',
    __DIR__ . '/var',
    [
        new \Hostnet\Component\Resolver\Plugin\CorePlugin(),
        new \Hostnet\Component\Resolver\Plugin\LessPlugin(),
    ],
    new \Hostnet\Component\Resolver\Import\Nodejs\Executable('/usr/bin/node', __DIR__ . '/node_modules/')
);

$packer = new \Hostnet\Component\Resolver\Packer();
$packer->pack($config);
```
> The project root should be the folder which contains your `package.json` and `composer.json`.

For the Symfony framework integration already exists in the form of the [asset-bundle](https://github.com/hostnet/asset-bundle).

### Including the made javascript
To use the entry-points, you need to do:
1. Include the outputted `require.js` file in the head of your HTML files
2. Include the outputted assets (in the example `dist/app.js`)
3. require the entry point module.

As an example:
```html
<html>
<head>
     <script type="text/javascript" src="/dist/require.js"></script>
</head>
<body>
    <script type="text/javascript" src="/dist/app.js"></script>
    <script>
        require('app.js');
    </script>
</body>
</html>
```

### Built-in processors
The library comes with some built-in processors to get you started more quickly with common tasks. These are:
* `\Hostnet\Component\Resolver\Plugin\CorePlugin`
  * Allows for combining Javascript files into modules
  * CSS assets
* `\Hostnet\Component\Resolver\Plugin\BrotliPlugin`
  * Used to also compress output files with Brotli
  * requires additionally `brotli/compress` 
* `\Hostnet\Component\Resolver\Plugin\CssFontRewritePlugin`
  * Re-writes fonts in CSS files and adds them to the outputted list
* `\Hostnet\Component\Resolver\Plugin\GzipPlugin`
  * Used to also compress output files with Gzip
* `\Hostnet\Component\Resolver\Plugin\LessPlugin`
  * Allows for less compilation
  * requires additionally `less` 
* `\Hostnet\Component\Resolver\Plugin\MinifyPlugin`
  * Compresses output files using minification
  * requires additionally `uglifyjs` and `cleancss` 
* `\Hostnet\Component\Resolver\Plugin\TsPlugin`
  * Allows for typescript compilation
  * requies additionally `typescript`

#### Creating your own plugin
If the built-in support is not sufficent enough, you can add your own by creating a plugin. You do this by implementing the `Hostnet\Component\Resolver\Plugin\PluginInterface` interface and adding your class to the configuration.

```php
<?php
use Hostnet\Component\Resolver\Plugin\PluginApi;
use Hostnet\Component\Resolver\Plugin\PluginInterface;

class MyPlugin implements PluginInterface
{
    public function activate(PluginApi $plugin_api): void
    {
        // $plugin_api->addBuildStep(new MyBuildStep());
        // $plugin_api->addCollector(new MyCollector());
        // $plugin_api->addWriter(new MyWriter());
    }
}
```

##### BuildStep classes 
Each build step represent a processing action for either a file or a module. This can be converting typescript to javascript, uglification or anything other that needs to be done to a file before it can be outputted.

See [Hostnet\Component\Resolver\Builder\AbstractBuildStep](https://github.com/hostnet/asset-lib/blob/master/src/Builder/AbstractBuildStep.php) for more information.

##### ImportCollector classes 
Each import collector is responsible for finding additional files to include in the build steps so your assets will work correctly. All files returned by the collector will be tracked for changes. For instance, imported javascript files or fonts in stylesheets etc.

See [Hostnet\Component\Resolver\Import\ImportCollectorInterface](https://github.com/hostnet/asset-lib/blob/master/src/Import/ImportCollectorInterface.php) for more information.

##### Writer classes 
Each writer will write the content of a generated asset to disk. Having multiple writers means you can write the file in different formats. For instance, you might want to output a gziped version of a file if your web-server does not gzip this natively.

See [Hostnet\Component\Resolver\Builder\AbstractWriter](https://github.com/hostnet/asset-lib/blob/master/src/Builder/AbstractWriter.php) for more information.

License
-------------
The `hostnet/asset-lib` is licensed under the [MIT License](https://github.com/hostnet/form-handler-bundle/blob/master/LICENSE), meaning you can reuse the code within proprietary software provided that all copies of the licensed software include a copy of the MIT License terms and the copyright notice.

Get in touch
------------
 * If you have a question, issue or feature request, you can use the github issue tracker.
 * Or via our email: opensource@hostnet.nl.

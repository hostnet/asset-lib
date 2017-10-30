<?php
/**
 * @copyright 2017 Hostnet B.V.
 */
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\Nodejs;

use Hostnet\Component\Resolver\Config\ConfigInterface;
use Hostnet\Component\Resolver\File;
use Hostnet\Component\Resolver\Import\FileResolverInterface;
use Hostnet\Component\Resolver\Import\Import;
use Hostnet\Component\Resolver\Import\Nodejs\Exception\FileNotFoundException;
use Hostnet\Component\Resolver\Module;

/**
 * NodeJS implementation for resolving files. This follows the NodeJS require
 * logic.
 *
 * @see https://nodejs.org/api/modules.html#modules_all_together
 */
final class FileResolver implements FileResolverInterface
{
    private $config;
    private $extensions;
    /**
     * Caching properties. Required since file IO is slow and finding the same resource again and again is slow.
     */
    private $as_module = [];
    private $as_file   = [];
    private $as_index  = [];
    private $as_dir    = [];

    /**
     * @param ConfigInterface $config
     * @param string[]        $extensions
     */
    public function __construct(ConfigInterface $config, array $extensions)
    {
        $this->config     = $config;
        $this->extensions = array_map(function (string $extension) {


            return '.' . ltrim($extension, '.');
        }, $extensions);
    }

    /**
     * {@inheritdoc}
     */
    public function asRequire(string $name, File $parent): Import
    {
        // 1. If X is a core module,
        if (File::isAbsolutePath($name)) {
            // 2. If X begins with '/'
            // a. LOAD_AS_FILE(Y + X)
            try {
                return new Import($name, new File($this->asFile($name)));
            } catch (FileNotFoundException $e) {
                // b. LOAD_AS_DIRECTORY(Y + X)
                $f = new File($this->asDir($name));

                return new Import($name, $f);
            }
        }

        if ($name[0] === '.' && ($name[1] === '/' || ($name[1] === '.' && $name[2] === '/'))) {
            // 3. If X begins with './' or '/' or '../'
            // a. LOAD_AS_FILE(Y + X)
            try {
                $f = new File($this->asFile($parent->dir . '/' . $name));

                if ($parent instanceof Module) {
                    $f = new Module(File::clean($parent->getParentName() . '/' . $name), $f->path);
                }

                return new Import($name, $f);
            } catch (FileNotFoundException $e) {
                // b. LOAD_AS_DIRECTORY(Y + X)
                $f = new File($this->asDir($parent->dir . '/' . $name));

                if ($parent instanceof Module) {
                    $f = new Module(File::clean($parent->getParentName() . '/' . $name), $f->path);
                }

                return new Import($name, $f);
            }
        }

        // 4. LOAD_NODE_MODULES(X, dirname(Y))
        return new Import($name, new Module($name, $this->asModule($name)));
    }

    /**
     * Try to resolve the import as a File.
     *
     * @param string $name
     * @throws FileNotFoundException when no file could be resolved.
     * @return string
     */
    private function asFile(string $name): string
    {
        if (isset($this->as_file[$name])) {
            if ($this->as_file[$name] === false) {
                throw new FileNotFoundException(sprintf('File %s could not be be found!', $name));
            }
            return $this->as_file[$name];
        }
        $path = File::makeAbsolutePath($name, $this->config->getProjectRoot());

        // 1. If X is a file, load X as JavaScript text.  STOP
        if (is_file($path)) {
            return $this->as_file[$name] = File::clean($name);
        }

        // 2, If X.js is a file, load X.js as JavaScript text.  STOP
        // 3. If X.json is a file, parse X.json to a JavaScript Object.  STOP
        // 4. If X.node is a file, load X.node as binary addon.  STOP
        foreach ($this->extensions as $ext) {
            if (is_file($path . $ext)) {
                return $this->as_file[$name] = File::clean($name . $ext);
            }
        }

        $this->as_file[$name] = false;
        throw new FileNotFoundException(sprintf('File %s could not be be found!', $name));
    }

    /**
     * Try to resolve the import as a Index file of a Directory.
     *
     * @param string $name
     * @throws FileNotFoundException when no file could be resolved.
     * @return string
     */
    private function asIndex(string $name): string
    {
        if (isset($this->as_index[$name])) {
            if ($this->as_index[$name] === false) {
                throw new FileNotFoundException(sprintf('File %s could not be be found!', $name));
            }
            return $this->as_index[$name];
        }

        $path = File::makeAbsolutePath($name, $this->config->getProjectRoot());

        foreach ($this->extensions as $ext) {
            if (is_file($path . '/index' . $ext)) {
                return $this->as_index[$name] = File::clean($name . '/index' . $ext);
            }
        }

        $this->as_index[$name] = false;
        // ERROR
        throw new FileNotFoundException(sprintf('File %s could not be be found!', $name));
    }

    /**
     * Try to resolve the import as a Directory.
     *
     * @param string $name
     * @throws FileNotFoundException when no file could be resolved.
     * @return string
     */
    private function asDir(string $name): string
    {
        if (isset($this->as_dir[$name])) {
            if ($this->as_dir[$name] === false) {
                throw new FileNotFoundException(sprintf('File %s could not be be found!', $name));
            }
            return $this->as_dir[$name];
        }

        $package_info_path = File::makeAbsolutePath($name . '/package.json', $this->config->getProjectRoot());
        // 1. If X/package.json is a file,
        if (is_file($package_info_path)) {
            // a. Parse X/package.json, and look for "main" field.
            $package_info = json_decode(file_get_contents($package_info_path), true);

            // b. let M = X + (json main field)
            // c. LOAD_AS_FILE(M)
            try {
                return $this->as_dir[$name] = $this->asFile($name . '/' . $package_info['main']);
            } catch (FileNotFoundException $e) {
                // d. LOAD_INDEX(M)
                return $this->as_dir[$name] = $this->asIndex($name . '/' . $package_info['main']);
            }
        }

        // 2. LOAD_INDEX(X)
        return $this->as_dir[$name] = $this->asIndex($name);
    }

    /**
     * Try to resolve the import as a Module.
     *
     * @param string $name
     * @throws FileNotFoundException when no file could be resolved.
     * @return string
     */
    private function asModule(string $name): string
    {
        if (isset($this->as_module[$name])) {
            if ($this->as_module[$name] === false) {
                throw new FileNotFoundException(sprintf('File %s could not be be found!', $name));
            }
            return $this->as_module[$name];
        }
        // 1. let DIRS=NODE_MODULES_PATHS(START)
        $dirs = array_merge(['node_modules'], $this->config->getIncludePaths());

        // 2. for each DIR in DIRS:
        foreach ($dirs as $dir) {
            // a. LOAD_AS_FILE(DIR/X)
            try {
                return $this->as_module[$name] = $this->asFile($dir . '/' . $name);
            } catch (FileNotFoundException $e) {
                // b. LOAD_AS_DIRECTORY(DIR/X)
                try {
                    return $this->as_module[$name] = $this->asDir($dir . '/' . $name);
                } catch (FileNotFoundException $e) {
                    continue; // skip
                }
            }
        }

        $this->as_module[$name] = false;
        throw new FileNotFoundException(sprintf('File %s could not be be found!', $name));
    }
}

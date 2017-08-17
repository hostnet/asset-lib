<?php
declare(strict_types=1);

namespace Hostnet\Component\Resolver\Import\Nodejs;

use Hostnet\Component\Resolver\Import\File;
use Hostnet\Component\Resolver\Import\Import;
use Hostnet\Component\Resolver\Import\ImportInterface;
use Hostnet\Component\Resolver\Import\Module;

class FileResolver
{
    private $cwd;
    private $extensions;

    public function __construct(string $cwd, array $extensions)
    {
        $this->cwd = $cwd;
        $this->extensions = $extensions;
    }

    public function asImport(string $name): Import
    {
        try {
            return new Import($name, new File($this->asFile($name)));
        } catch (\RuntimeException $e) {
            // do nothing
        }

        try {
            return new Import($name, new File($this->asDir($name)));
        } catch (\RuntimeException $e) {
            // do nothing
        }

        return new Import($name, new Module($name, new File($this->asModule($name))));
    }

    public function asRequire(string $name, ImportInterface $parent): Import
    {
        // 1. If X is a core module,
        if ($name[0] === '/') {
            // 2. If X begins with '/'
            // a. LOAD_AS_FILE(Y + X)
            try {
                return new Import($name, new File($this->asFile($name)));
            } catch (\RuntimeException $e) {
                // do nothing
            }

            // b. LOAD_AS_DIRECTORY(Y + X)
            $f = new File($this->asDir($name));

            if ($parent instanceof Module) {
                return new Import($name, new Module(File::clean($name), $f));
            }

            return new Import($name, $f);
        }
        if ($name[0] === '.' && ($name[1] === '/' || ($name[1] === '.' && $name[2] === '/'))) {
            // 3. If X begins with './' or '/' or '../'
            // a. LOAD_AS_FILE(Y + X)
            try {
                $f = new File($this->asFile($parent->getDirectory() . '/' . $name));

                if ($parent instanceof Module) {
                    return new Import($name, new Module(File::clean($parent->getParentName() . '/' . $name), $f));
                }

                return new Import($name, $f);
            } catch (\RuntimeException $e) {
                // do nothing
            }

            // b. LOAD_AS_DIRECTORY(Y + X)
            $f = new File($this->asDir($parent->getDirectory() . '/' . $name));

            if ($parent instanceof Module) {
                return new Import($name, new Module(File::clean($parent->getParentName() . '/' . $name), $f));
            }

            return new Import($name, $f);
        }

        // 4. LOAD_NODE_MODULES(X, dirname(Y))
        return new Import($name, new Module($name, new File($this->asModule($name))));
    }

    public function asFile(string $name): string
    {
        $path = $name;

        if ($path[0] !== '/') {
            $path = $this->cwd . '/' . $path;
        }

        // 1. If X is a file, load X as JavaScript text.  STOP
        if (is_file($path)) {
            return File::clean($name);
        }

        // 2, If X.js is a file, load X.js as JavaScript text.  STOP
        // 3. If X.json is a file, parse X.json to a JavaScript Object.  STOP
        // 4. If X.node is a file, load X.node as binary addon.  STOP
        foreach ($this->extensions as $ext) {
            if (is_file($path . $ext)) {
                return File::clean($name . $ext);
            }
        }

        throw new \RuntimeException(sprintf('File %s could not be be found!', $name));
    }

    public function asIndex(string $name): string
    {
        $path = $name;

        if ($path[0] !== '/') {
            $path = $this->cwd . '/' . $path;
        }

        // 1. If X/index.js is a file, load X/index.js as JavaScript text.  STOP
        if (is_file($path . '/index.js')) {
            return File::clean($name . '/index.js');
        }
        // 2. If X/index.json is a file, parse X/index.json to a JavaScript object. STOP
        if (is_file($path . '/index.json')) {
            return File::clean($name . '/index.json');
        }
        // 3. If X/index.node is a file, load X/index.node as binary addon.  STOP
        if (is_file($path . '/index.node')) {
            return File::clean($name . '/index.node');
        }

        // ERROR
        throw new \RuntimeException(sprintf('File %s could not be be found!', $name));
    }

    public function asDir(string $name): string
    {
        $package_info_path = $name . '/package.json';

        if ($package_info_path[0] !== '/') {
            $package_info_path = $this->cwd . '/' . $package_info_path;
        }

        // 1. If X/package.json is a file,
        if (is_file($package_info_path)) {
            // a. Parse X/package.json, and look for "main" field.
            $package_info = json_decode(file_get_contents($this->cwd . '/' . $name . '/package.json'), true);

            // b. let M = X + (json main field)
            // c. LOAD_AS_FILE(M)
            try {
                return $this->asFile($name . '/' . $package_info['main']);
            } catch (\RuntimeException $e) {
                // do nothing
            }

            // d. LOAD_INDEX(M)
            return $this->asIndex($name . '/' . $package_info['main']);
        }

        // 2. LOAD_INDEX(X)
        return $this->asIndex($name);
    }

    public function asModule(string $name): string
    {
        // 1. let DIRS=NODE_MODULES_PATHS(START)
        $module = 'node_modules/' . $name;
        // 2. for each DIR in DIRS:
        // a. LOAD_AS_FILE(DIR/X)
        try {
            return $this->asFile($module);
        } catch (\RuntimeException $e) {
            // do nothing
        }

        // b. LOAD_AS_DIRECTORY(DIR/X)
        return $this->asDir($module);
    }
}

package main

import (
	"os"
	"path/filepath"
	"regexp"
	"io/ioutil"
	"fmt"
	"encoding/json"
	"strings"
	"strconv"
	"flag"
)

type regex struct {
	re_less *regexp.Regexp
	re_ts *regexp.Regexp
	re_js *regexp.Regexp
}

type package_json struct {
	Main string `json:"main"`
}

type Dependency struct {
	name string
	file string
}

type Cache struct {
	file string
	deps map[string][]Dependency
	module map[string]string
	mtime map[string]int64
	changed bool
}

func resolveAsFile(file string, ext []string) (string, bool) {
	// 1. If X is a file, load X as JavaScript text.  STOP
	if  s, err := os.Stat(file); !os.IsNotExist(err) && s.Mode().IsRegular() {
		return file, false
	}

	// 2. If X.js is a file, load X.js as JavaScript text.  STOP
	// This is generalized to a list of base extensions
	for _, e := range ext {
		if s, err := os.Stat(file + e); !os.IsNotExist(err) && s.Mode().IsRegular() {
			return file + e, false
		}
	}
	// 3. If X.json is a file, parse X.json to a JavaScript Object.  STOP
	if s, err := os.Stat(file + ".json"); !os.IsNotExist(err) && s.Mode().IsRegular() {
		return file + ".json", false
	}
	// 4. If X.node is a file, load X.node as binary addon.  STOP
	if s, err := os.Stat(file + ".node"); !os.IsNotExist(err) && s.Mode().IsRegular() {
		return file + ".node", false
	}

	// ERROR
	return "", true
}

func resolveAsIndex(file string) (string, bool) {
	// 1. If X/index.js is a file, load X/index.js as JavaScript text.  STOP
	if  s, err := os.Stat(file + "/index.js"); !os.IsNotExist(err) && s.Mode().IsRegular() {
		return file + "/index.js", false
	}
	// 2. If X/index.json is a file, parse X/index.json to a JavaScript object. STOP
	if  s, err := os.Stat(file + "/index.json"); !os.IsNotExist(err) && s.Mode().IsRegular() {
		return file + "/index.json", false
	}
	// 3. If X/index.node is a file, load X/index.node as binary addon.  STOP
	if  s, err := os.Stat(file + "/index.node"); !os.IsNotExist(err) && s.Mode().IsRegular() {
		return file + "/index.node", false
	}

	// ERROR
	return "", true
}

func resolveAsDir(dir string, ext []string) (string, bool) {
	// 1. If X/package.json is a file,
	if  s, err := os.Stat(dir + "/package.json"); !os.IsNotExist(err) && s.Mode().IsRegular() {
		// a. Parse X/package.json, and look for "main" field.
		var m package_json
		buf, _ := ioutil.ReadFile(dir + "/package.json")
		json.Unmarshal(buf, &m)

		// b. let M = X + (json main field)
		// c. LOAD_AS_FILE(M)
		file, e := resolveAsFile(filepath.Clean(dir + "/" + m.Main), ext)
		if !e {
			return file, false
		}
		// d. LOAD_INDEX(M)
		return resolveAsIndex(filepath.Clean(dir + "/" + m.Main))
	}

	// 2. LOAD_INDEX(X)
	return resolveAsIndex(dir)
}

func resolveAsNodeModule(file string) (string, bool) {
	// 1. let DIRS=NODE_MODULES_PATHS(START)
	module := filepath.Clean("./node_modules/" + file)
	// 2. for each DIR in DIRS:
	// a. LOAD_AS_FILE(DIR/X)
	file, e := resolveAsFile(module, []string {".js"})
	if !e {
		return file, false
	}
	// b. LOAD_AS_DIRECTORY(DIR/X)
	return resolveAsDir(module, []string {".js"})
}

func resolveImport(file string, cwd string, ext []string) (string, bool) {
	// 1. If X is a core module,
	if file[0] == '/' {
		// 2. If X begins with '/'
		// a. LOAD_AS_FILE(Y + X)
		file, e := resolveAsFile(filepath.Clean(file), ext)
		if !e {
			return file, false
		}
		// b. LOAD_AS_DIRECTORY(Y + X)
		return resolveAsDir(filepath.Clean(file), ext)
	} else if file[0] == '.' && (file[1] == '/' || (file[1] == '.' && file[2] == '/')){
		// 3. If X begins with './' or '/' or '../'
		// a. LOAD_AS_FILE(Y + X)
		file, e := resolveAsFile(filepath.Clean(cwd + "/" + file), ext)
		if !e {
			return file, false
		}
		// b. LOAD_AS_DIRECTORY(Y + X)
		return resolveAsDir(filepath.Clean(cwd + "/" + file), ext)
	} else {
		// 4. LOAD_NODE_MODULES(X, dirname(Y))
		return resolveAsNodeModule(file)
	}

	// 5. THROW "not found"
	return "", true
}

func dependenciesLess(file string, buf []byte, r regex) []Dependency {
	matches := r.re_less.FindAllStringSubmatch(string(buf), -1)

	result := []Dependency{}
	cwd := filepath.Dir(file)

	for _, m := range matches {
		path := m[4]
		if len(path) == 0 {
			path = m[5]
		}

		// there can be :// which indicates a transport protocol, it (should) never be to a file.
		if strings.Contains(path, "://") {
			continue
		}

		ext := filepath.Ext(path)

        if "" == ext {
            path = path + ".less"
        }

		result = append(result, Dependency{path, filepath.Clean(cwd + string(os.PathSeparator) + path)})
	}

	return result
}

func dependenciesTs(file string, buf []byte, r regex) []Dependency {
	matches := r.re_ts.FindAllStringSubmatch(string(buf), -1)

	tests := []string {".ts", ".d.ts"}
	// First get all the regular requires
	result := dependenciesJs(file, buf, r, tests)
	cwd := filepath.Dir(file)

	for _, m := range matches {
		file, e := resolveImport(m[2], cwd, tests)

		if e {
			continue
		}

		result = append(result, Dependency{m[2], file})
	}

	return result
}

func dependenciesJs(file string, buf []byte, r regex, ext []string) []Dependency {
	matches := r.re_js.FindAllStringSubmatch(string(buf), -1)

	result := []Dependency{}
	cwd := filepath.Dir(file)

	for _, m := range matches {
		path := m[2]
		if len(path) == 0 {
			path = m[3]
		}

		file, e := resolveImport(path, cwd, ext)

		if e {
			continue
		}

		result = append(result, Dependency{path, file})
	}

	return result
}

func dependencies(file string, r regex) []Dependency {
	buf, _ := ioutil.ReadFile(file)
	ext := filepath.Ext(file)

	if ext == ".less" {
		return dependenciesLess(file, buf, r)
	}
	if ext == ".ts" {
		return dependenciesTs(file, buf, r)
	}

	return dependenciesJs(file, buf, r, []string {".js"})
}

func dependenciesCached(file Dependency, r regex, c *Cache) []Dependency {
	filestat, _ := os.Stat(file.file)
	fmktime := filestat.ModTime().Unix()

	if mtime, has := c.mtime[file.file]; has && fmktime <= mtime  {
		return c.deps[file.file]
	}

	deps := dependencies(file.file, r)
	c.deps[file.file] = deps
	c.mtime[file.file] = fmktime
	c.module[file.file] = file.name
	c.changed = true

	return deps
}

func seen(needle Dependency, total []Dependency, queue []Dependency) bool {
	for _, b := range total {
		if b.file == needle.file {
			return true
		}
	}
	for _, b := range queue {
		if b.file == needle.file {
			return true
		}
	}
	return false
}

func readCache(file string) *Cache {
	c := Cache {file: file, deps:make(map[string][]Dependency), module:make(map[string]string), mtime:make(map[string]int64), changed: true}

	if  s, err := os.Stat(c.file); !os.IsNotExist(err) && s.Mode().IsRegular() {
		buf, _ := ioutil.ReadFile(file)

		for _, file := range strings.Split(string(buf), "\n") {
			if len(file) == 0 {
				continue
			}

			// filename, mtime, deps
			values := strings.Split(file, ";")
			name := strings.Split(values[0], "=")
			formatted_deps := strings.Split(values[2], ",")
			deps := make([]Dependency, len(formatted_deps))

			mtime, err := strconv.ParseInt(values[1], 10, 64)
			if err != nil {
				panic(err)
			}

			for i, d := range formatted_deps {
				if len(d) == 0 {
					continue
				}

				data := strings.Split(d, "=")
				deps[i] = Dependency{data[0], data[1]}
			}

			c.mtime[name[1]] = mtime
			c.module[name[1]] = name[0]
			c.deps[name[1]] = deps
		}

		c.changed = false
	}

	return &c
}

func writeCache(c *Cache) {
	data := ""

	for file, deps := range c.deps {
		formatted_deps := ""

		for _, d := range deps {
			if len(formatted_deps) > 0 {
				formatted_deps += ","
			}
			formatted_deps += d.name + "=" + d.file
		}

		data += fmt.Sprintf("%s=%s;%d;%s\n", c.module[file], file, c.mtime[file], formatted_deps)
	}

	ioutil.WriteFile(c.file, []byte(data), 0644)
}

func resolveTree(input_files []string) {
	re_less := regexp.MustCompile(`@import (\([a-z,\s]*\)\s*)?(url\()?('([^']+)'|"([^"]+)")`)
	re_ts := regexp.MustCompile(`import(.*from)?\s+["'](.*)["'];`)
	re_js := regexp.MustCompile(`[^a-z0-9_]require\(([']([^']+)[']|["]([^"]+)["])\)`)

	r := regex{re_less, re_ts, re_js}

	queue := []Dependency{}
	files := []Dependency{}
	cache := readCache(".deps")

	// Add input element to input
	for _, f := range input_files {
		queue = append(queue, Dependency{f, filepath.Clean(f)})
	}

	for {
		if len(queue) == 0 {
			break
		}

		file := queue[0] // shift
		queue = queue[1:] // replace queue

		_, err := os.Stat(file.file)

		if !os.IsNotExist(err) {
			files = append(files, file)

			for _, dep := range dependenciesCached(file, r, cache) {
				if !seen(dep, files, queue) {
					queue = append(queue, dep)
				}
			}
		}
	}

	if cache.changed {
		writeCache(cache)
	}

	for _, f := range files {
		fmt.Println(f.file)
	}
}

func resolveImports(input_files []string) {
	fmt.Println(input_files)
}

func main() {
	simple := flag.Bool("s", false, "Simple, this only output the contents of the requires.")

	flag.Parse()
	files := flag.Args()

	if *simple {
		resolveImports(files)
	} else {
		resolveTree(files)
	}
}

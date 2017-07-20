package main

import (
	"os"
	"path/filepath"
	"regexp"
	"io/ioutil"
	"fmt"
	"encoding/json"
)

type regex struct {
	re_less *regexp.Regexp;
	re_ts *regexp.Regexp;
	re_js *regexp.Regexp
}

type package_json struct {
	Main string `json:"main"`
}

func in_array(needle string, haystack []string) bool {
	for _, b := range haystack {
		if b == needle {
			return true
		}
	}
	return false
}

func resolveAsFile(file string, ext string) (string, bool) {
	// 1. If X is a file, load X as JavaScript text.  STOP
	if  s, err := os.Stat(file); !os.IsNotExist(err) && s.Mode().IsRegular() {
		return file, false
	}
	// 2. If X.js is a file, load X.js as JavaScript text.  STOP
	if s, err := os.Stat(file + ext); !os.IsNotExist(err) && s.Mode().IsRegular() {
		return file + ext, false
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

func resolveAsDir(dir string, ext string) (string, bool) {
	// If X/package.json is a file,
	if  s, err := os.Stat(dir + "/package.json"); !os.IsNotExist(err) && s.Mode().IsRegular() {
		var m package_json
		buf, _ := ioutil.ReadFile(dir + "/package.json")
		json.Unmarshal(buf, &m)

		file, e := resolveAsFile(filepath.Clean(dir + "/" + m.Main), ext)
		if !e {
			return file, false
		}
		return resolveAsIndex(filepath.Clean(dir + "/" + m.Main))
	}

	return resolveAsIndex(dir)
}

func resolveImport(file string, cwd string, ext string) (string, bool) {
	// is relative?
	if file[0] == '/' {
		return resolveAsFile(filepath.Clean(file), ext)
	} else if file[0] == '.' && (file[1] == '/' || (file[1] == '.' && file[2] == '/')){
		return resolveAsFile(filepath.Clean(cwd + "/" + file), ext)
	} else {
		module := filepath.Clean("./node_modules/" + file)

		file, e := resolveAsFile(module, ".js")
		if e {
			return resolveAsDir(module, ".js")
		}

		return file, false
	}

	return "", true
}

func dependenciesLess(file string, buf []byte, r regex) []string {
	matches := r.re_less.FindAllStringSubmatch(string(buf), -1)

	result := []string{}
	cwd := filepath.Dir(file)

	for _, m := range matches {
		path := m[4]
		if len(path) == 0 {
			path = m[5]
		}

		ext := filepath.Ext(path)

        if "" == ext {
            path = path + ".less"
        }

		result = append(result, filepath.Clean(cwd + string(os.PathSeparator) + path))
	}

	return result
}

func dependenciesTs(file string, buf []byte, r regex) []string {
	matches := r.re_ts.FindAllStringSubmatch(string(buf), -1)

	// First get all the regular requires
	result := dependenciesJs(file, buf, r, ".ts")
	cwd := filepath.Dir(file)
	tests := []string {".ts", ".d.ts", ""}

	for _, m := range matches {
		var file string
		var e bool

		for _, t := range tests {
			file, e = resolveImport(m[2] + t, cwd, ".ts")

			if !e {
				break
			}
		}

		result = append(result, file)
	}

	return result
}

func dependenciesJs(file string, buf []byte, r regex, ext string) []string {
	matches := r.re_js.FindAllStringSubmatch(string(buf), -1)

	result := []string{}
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


		result = append(result, file)
	}

	return result
}

func dependencies(file string, r regex) []string {
	buf, _ := ioutil.ReadFile(file)
	ext := filepath.Ext(file)

	if ext == ".less" {
		return dependenciesLess(file, buf, r)
	}
	if ext == ".ts" {
		return dependenciesTs(file, buf, r)
	}

	return dependenciesJs(file, buf, r, ".js")
}

func main() {
	re_less := regexp.MustCompile(`@import (\([a-z\,\s]*\)\s*)?(url\()?('([^']+)'|"([^"]+)")`)
	re_ts := regexp.MustCompile(`import(.*from)?\s+["'](.*)["'];`)
	re_js := regexp.MustCompile(`[^a-z0-9_]require\(([']([^']+)[']|["]([^"]+)["])\)`)

	r := regex{re_less, re_ts, re_js}

	queue := []string{}
	files := []string{}

	// Add input element to input
	for _, f := range os.Args[1:] {
		queue = append(queue, filepath.Clean(f))
	}

	for {
		file := queue[0] // shift
		queue = queue[1:] // replace queue

		_, err := os.Stat(file)

		if !os.IsNotExist(err) {
			files = append(files, file)

			for _, dep := range dependencies(file, r) {
				if !in_array(dep, files) && !in_array(dep, queue) {
					queue = append(queue, dep)
				}
			}
		}

		if len(queue) == 0 {
			break
		}
	}

	for _, f := range files {
		fmt.Println(f)
	}
}

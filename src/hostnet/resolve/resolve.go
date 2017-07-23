package resolve

import (
	"encoding/json"
	"io/ioutil"
	"os"
	"path/filepath"
)

type package_json struct {
	Main string `json:"main"`
}

func AsFile(file string, ext []string) (string, bool) {
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

func AsIndex(file string) (string, bool) {
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

func AsDir(dir string, ext []string) (string, bool) {
	// 1. If X/package.json is a file,
	if  s, err := os.Stat(dir + "/package.json"); !os.IsNotExist(err) && s.Mode().IsRegular() {
		// a. Parse X/package.json, and look for "main" field.
		var m package_json
		buf, _ := ioutil.ReadFile(dir + "/package.json")
		json.Unmarshal(buf, &m)

		// b. let M = X + (json main field)
		// c. LOAD_AS_FILE(M)
		file, e := AsFile(filepath.Clean(dir + "/" + m.Main), ext)
		if !e {
			return file, false
		}
		// d. LOAD_INDEX(M)
		return AsIndex(filepath.Clean(dir + "/" + m.Main))
	}

	// 2. LOAD_INDEX(X)
	return AsIndex(dir)
}

func AsNodeModule(file string) (string, bool) {
	// 1. let DIRS=NODE_MODULES_PATHS(START)
	module := filepath.Clean("./node_modules/" + file)
	// 2. for each DIR in DIRS:
	// a. LOAD_AS_FILE(DIR/X)
	file, e := AsFile(module, []string {".js"})
	if !e {
		return file, false
	}
	// b. LOAD_AS_DIRECTORY(DIR/X)
	return AsDir(module, []string {".js"})
}

func File(file string, cwd string, ext []string) (string, bool) {
	// 1. If X is a core module,
	if file[0] == '/' {
		// 2. If X begins with '/'
		// a. LOAD_AS_FILE(Y + X)
		file, e := AsFile(filepath.Clean(file), ext)
		if !e {
			return file, false
		}
		// b. LOAD_AS_DIRECTORY(Y + X)
		return AsDir(filepath.Clean(file), ext)
	} else if file[0] == '.' && (file[1] == '/' || (file[1] == '.' && file[2] == '/')){
		// 3. If X begins with './' or '/' or '../'
		// a. LOAD_AS_FILE(Y + X)
		file, e := AsFile(filepath.Clean(cwd + "/" + file), ext)
		if !e {
			return file, false
		}
		// b. LOAD_AS_DIRECTORY(Y + X)
		return AsDir(filepath.Clean(cwd + "/" + file), ext)
	} else {
		// 4. LOAD_NODE_MODULES(X, dirname(Y))
		return AsNodeModule(file)
	}

	// 5. THROW "not found"
	return "", true
}

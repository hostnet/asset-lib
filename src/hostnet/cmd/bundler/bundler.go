package bundler

import (
	"fmt"
	"io/ioutil"
	"os"
	"path/filepath"
	"../../dependency"
	"../../cache"
	"../../resolve"
)

func dependencies(file dependency.FileContent) []dependency.File {
	return dependency.Js(file, []string {".js"}, false)
}

func dependenciesCached(file dependency.FileContent, c *cache.Cache) []dependency.File {
	filestat, _ := os.Stat(file.Meta.File)
	fmktime := filestat.ModTime().Unix()

	if hit, deps := cache.Hit(c, file.Meta, fmktime); hit {
		return deps
	}

	return cache.Put(c, file.Meta, dependencies(file), fmktime)
}

func bundle(input_files []string, use_names bool) {
	c := cache.Load()

	// Add input element to input
	for _, name := range input_files {
		file_loc := name

		if use_names {
			module_file, e := resolve.File(file_loc, filepath.Dir("."), []string {"js"})
			if e {
				// fallback to file location...
				if  s, err := os.Stat(file_loc); os.IsNotExist(err) || !s.Mode().IsRegular() {
					file_loc += ".js";
					if  s, err := os.Stat(file_loc); os.IsNotExist(err) || !s.Mode().IsRegular() {
						panic("File not found: " + file_loc)
					}
				}
			} else {
				file_loc = module_file
			}
		}

		path := filepath.Clean(file_loc)
		buf, _ := ioutil.ReadFile(path)

		file := dependency.FileContent{Meta: dependency.File{Name: name, File: path}, Content: buf}

		js := "define('"+ file.Meta.Name + "', function (require, exports, module) {\n"

		// module
		js += string(file.Content)

		js += "\n});"

		fmt.Println(js)
	}

	cache.Save(c)
}

func Init(files []string, use_names bool) {
	bundle(files, use_names)
}
package resolver

import (
	"fmt"
	"io/ioutil"
	"os"
	"path/filepath"
	"../../dependency"
	"../../cache"
	"strings"
)

func dependencies(file dependency.File) []dependency.File {
	buf, _ := ioutil.ReadFile(file.File)
	ext := filepath.Ext(file.File)

	content := dependency.FileContent{Meta: file, Content: buf}

	if ext == ".less" {
		return dependency.Less(content)
	}
	if ext == ".ts" {
		return dependency.Ts(content)
	}

	return dependency.Js(content, []string {".js"}, false)
}

func dependenciesCached(file dependency.File, c *cache.Cache) []dependency.File {
	filestat, _ := os.Stat(file.File)
	fmktime := filestat.ModTime().Unix()

	if hit, deps := cache.Hit(c, file, fmktime); hit {
		return deps
	}

	return cache.Put(c, file, dependencies(file), fmktime)
}

func resolveTree(input_files []string) []dependency.File {
	queue := []dependency.File{}
	files := []dependency.File{}

	c := cache.Load()

	// Add input element to input
	for _, f := range input_files {
		_, err := os.Stat(f)

		if !os.IsNotExist(err) {
			queue = append(queue, dependency.File{Name: f, File: filepath.Clean(f)})
		} else {
			panic("FILE NOT FOUND: " + f)
		}
	}

	for {
		if len(queue) == 0 {
			break
		}

		file := queue[0] // shift
		queue = queue[1:] // replace queue

		_, err := os.Stat(file.File)

		if !os.IsNotExist(err) {
			files = append(files, file)
			for _, dep := range dependenciesCached(file, c) {

				if !dependency.In(dep, files, queue) {
					queue = append(queue, dep)
				}
			}
		} else {
			panic("FILE NOT FOUND: " + file.Name)
		}
	}

	cache.Save(c)

	return files
}

func Init(files []string, output_file string, print_name bool, exclude string) {
	out := ""

	for _, f := range resolveTree(files) {
		if len(exclude) > 0 && strings.HasPrefix(f.File, exclude) {
			continue
		}

		name := f.File

		if print_name {
			name = f.Name

			// does it start with node_modules?
			if strings.HasPrefix(name, "node_modules/") {
				name = name[13:]
			}
		}

		out += name + "\n"
	}

	if len(output_file) > 0 {
		// check if there are differences
		_, err := os.Stat(output_file)

		if !os.IsNotExist(err) {
			buf, _ := ioutil.ReadFile(output_file)

			if string(buf) == out {
				return
			}
		}

		ioutil.WriteFile(output_file, []byte(out), 0644)
	} else {
		fmt.Print(out)
	}
}

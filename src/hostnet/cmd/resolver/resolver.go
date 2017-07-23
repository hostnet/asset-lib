package resolver

import (
	"fmt"
	"io/ioutil"
	"os"
	"path/filepath"
	"../../dependency"
	"../../cache"
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

	return dependency.Js(content, []string {".js"})
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
		queue = append(queue, dependency.File{Name: f, File: filepath.Clean(f)})
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
		}
	}

	cache.Save(c)

	return files
}

func Init(files []string) {
	for _, f := range resolveTree(files) {
		fmt.Println(f.File)
	}
}

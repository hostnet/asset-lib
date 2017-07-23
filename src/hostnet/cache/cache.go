package cache

import (
	"fmt"
	"io/ioutil"
	"os"
	"strconv"
	"strings"
	"../dependency"
)

var file = ".deps"

type Cache struct {
	file string
	deps map[string][]dependency.File
	module map[string]string
	mtime map[string]int64
	changed bool
}

func Put(c *Cache, file dependency.File, deps []dependency.File, mtime int64) []dependency.File {
	c.deps[file.File] = deps
	c.mtime[file.File] = mtime
	c.module[file.File] = file.Name
	c.changed = true

	return deps
}

func Hit(c *Cache, file dependency.File, fmktime int64) (bool, []dependency.File) {
	if mtime, has := c.mtime[file.File]; has && fmktime <= mtime  {
		return true, c.deps[file.File]
	}

	return false, nil
}

func Load() *Cache {
	c := Cache {file: file, deps:make(map[string][]dependency.File), module:make(map[string]string), mtime:make(map[string]int64), changed: true}

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
			deps := []dependency.File{}

			mtime, err := strconv.ParseInt(values[1], 10, 64)
			if err != nil {
				panic(err)
			}

			for _, d := range formatted_deps {
				if len(d) == 0 {
					continue
				}

				data := strings.Split(d, "=")
				deps = append(deps, dependency.File{Name: data[0], File: data[1]})
			}

			c.mtime[name[1]] = mtime
			c.module[name[1]] = name[0]
			c.deps[name[1]] = deps
		}

		c.changed = false
	}

	return &c
}

func Save(c *Cache) {
	if !c.changed {
		return
	}

	data := ""

	for file, deps := range c.deps {
		formatted_deps := ""

		if len(deps) > 0 {
			for _, d := range deps {
				if len(formatted_deps) > 0 {
					formatted_deps += ","
				}
				formatted_deps += d.Name + "=" + d.File
			}
		}

		data += fmt.Sprintf("%s=%s;%d;%s\n", c.module[file], file, c.mtime[file], formatted_deps)
	}

	ioutil.WriteFile(c.file, []byte(data), 0644)
}

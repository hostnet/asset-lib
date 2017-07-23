package dependency

import (
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"../resolve"
)

type regex struct {
	re_less *regexp.Regexp
	re_ts *regexp.Regexp
	re_js *regexp.Regexp
}

type File struct {
	Name string
	File string
}

type FileContent struct {
	Meta File
	Content []byte
}

func In(needle File, total ...[]File) bool {
	for _, list := range total {
		for _, b := range list {
			if b.File == needle.File {
				return true
			}
		}
	}
	return false
}

var regex_init = regex{
	re_less: regexp.MustCompile(`@import (\([a-z,\s]*\)\s*)?(url\()?('([^']+)'|"([^"]+)")`),
	re_ts: regexp.MustCompile(`import(.*from)?\s+["'](.*)["'];`),
	re_js: regexp.MustCompile(`[^a-z0-9_]require\(([']([^']+)[']|["]([^"]+)["])\)`),
}

func Less(file FileContent) []File {
	matches := regex_init.re_less.FindAllStringSubmatch(string(file.Content), -1)

	result := []File{}
	cwd := filepath.Dir(file.Meta.File)

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

		result = append(result, File{Name: path, File: filepath.Clean(cwd + string(os.PathSeparator) + path)})
	}

	return result
}

func Ts(file FileContent) []File {
	matches := regex_init.re_ts.FindAllStringSubmatch(string(file.Content), -1)

	exts := []string {".ts", ".d.ts"}
	// First get all the regular requires
	result := Js(file, exts)
	cwd := filepath.Dir(file.Meta.File)

	for _, m := range matches {
		file, e := resolve.File(m[2], cwd, exts)

		if e {
			continue
		}

		result = append(result, File{Name: m[2], File: file})
	}

	return result
}

func Js(file FileContent, ext []string) []File {
	matches := regex_init.re_js.FindAllStringSubmatch(string(file.Content), -1)

	result := []File{}
	cwd := filepath.Dir(file.Meta.File)

	for _, m := range matches {
		path := m[2]
		if len(path) == 0 {
			path = m[3]
		}

		file, e := resolve.File(path, cwd, ext)

		if e {
			continue
		}

		result = append(result, File{Name: path, File: file})
	}

	return result
}
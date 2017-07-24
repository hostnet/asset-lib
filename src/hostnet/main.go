package main

import (
	"flag"
	"./cmd/bundler"
	"./cmd/resolver"
	"fmt"
	"os"
)

func main() {
	exclude := flag.String("e", "", "Directory to exclude")
	output := flag.String("o", "", "Output file")
	names := flag.Bool("n", false, "Use the package name instead of the file name")

	flag.Parse()
	args := flag.Args()

	if len(args) > 0 {
		command := args[0]

		if "bundle" == command {
			bundler.Init(args[1:], *names)
			return
		} else if "resolve" == command {
			resolver.Init(args[1:], *output, *names, *exclude)
			return
		}
	}
	fmt.Println("Command missing, possible options: bundle, resolve.")
	os.Exit(1)
}

package main

import (
	"flag"
	"./cmd/bundler"
	"./cmd/resolver"
	"fmt"
	"os"
)

func main() {
	flag.Parse()
	args := flag.Args()

	if len(args) > 0 {
		command := args[0]

		if "bundle" == command {
			bundler.Init(args[1:])
			return
		} else if "resolve" == command {
			resolver.Init(args[1:])
			return
		}
	}
	fmt.Println("Command missing, possible options: bundle, resolve.")
	os.Exit(1)
}

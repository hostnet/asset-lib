all: bin/resolver

bin/resolver: src/hostnet/resolver.go
	@cd ./bin; go build ../$<

clean:
	@rm -rf bin/resolver bin/resolver.exe

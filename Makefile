ifeq ($(OS),Windows_NT)
    TARGET := bin\resolver.exe
else
    TARGET := bin/resolver
endif
SRC=$(shell find ./src/ -name '*.go')

all: $(TARGET)

$(TARGET): src/hostnet/main.go $(SRC)
	go build -o $@ $<

clean:
	@$(RM) $(TARGET)

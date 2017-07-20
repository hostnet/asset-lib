ifeq ($(OS),Windows_NT)
    TARGET := bin\resolver.exe
else
    TARGET := bin/resolver
endif

all: $(TARGET)

$(TARGET): src/hostnet/resolver.go
	go build -o $@ $<

clean:
	@$(RM) $(TARGET)

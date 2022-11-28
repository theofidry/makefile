MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

# This is the problematic code: this command will no longer work as expected as
# soon as the working directory changes.
FILES = $(shell find . -type f | sed 's/ /\\ /g')

help:
	@echo $(FILES)

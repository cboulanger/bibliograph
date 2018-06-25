#!/usr/bin/env bash

DIRS="src/server/modules src/client/bibliograph/source/class"
grep --include \*.js --include \*.php -rIhEo '"app/[^"]+"' $DIRS | sort -u


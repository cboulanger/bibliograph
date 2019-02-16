#!/usr/bin/env bash
if [ -x "$(command -v travis)" ] && git diff --name-only | grep .travis.yml > /dev/null; then travis lint ./.travis.yml -x || exit 1; fi
if [ -x "$(command -v shellcheck)" ] && git diff --name-only | grep build/script/ > /dev/null; then shellcheck -S error build/script/*.sh || exit 1; fi
if [ -x "$(command -v shellcheck)" ] && git diff --name-only | grep test/script/ > /dev/null; then shellcheck -S error test/script/*.sh || exit 1; fi

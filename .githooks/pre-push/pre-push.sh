#!/usr/bin/env bash
# echo "Pre-push hooks are disabled"
if [ -x "$(command -v travis)" ] ; then travis lint ./.travis.yml -x || exit 1; fi
# shellcheck build/script/*.sh
# shellcheck test/script/*.sh

#!/usr/bin/env bash
travis lint ./.travis.yml -x || exit 1
# shellcheck build/script/*.sh
# shellcheck test/script/*.sh

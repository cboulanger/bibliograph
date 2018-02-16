#!/bin/bash

#set -o errexit # Exit on error

bash test/setup.sh || exit $?
bash test/codeception-all.sh || exit $?
bash test/mocha.sh || exit $?
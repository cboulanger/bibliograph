#!/bin/bash

set -o errexit # Exit on error

bash test/setup.sh
bash test/codeception-all.sh
bash test/mocha.sh
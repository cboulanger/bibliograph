#!/usr/bin/env bash
logfile=src/server/runtime/logs/${1:-app}.log
touch $logfile
tail -F -n 1000 $logfile
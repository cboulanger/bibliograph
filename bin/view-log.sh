#!/usr/bin/env bash
logfile=src/server/runtime/logs/app.log
touch $logfile
tail -F -n 100 $logfile
#!/bin/bash

#set -o errexit # Exit on error

ps | grep "[p]hp yii serve -t=@app/tests localhost:8080" > /dev/null
if [ $? -eq 0 ]; then
  echo "Bibliograph test server is running..."
else
  echo "Starting Bibliograph test server..."
  pushd ./bibliograph/server > /dev/null
  php yii serve -t=@app/tests localhost:8080 &
  popd > /dev/null
fi

ps | grep "[p]hp yii serve localhost:8081" > /dev/null
if [ $? -eq 0 ]; then
  echo "Bibliograph 'production' server is running..."
else
  echo "Starting Bibliograph 'production' server..."
  pushd ./bibliograph/server > /dev/null
  php yii serve localhost:8081 &
  popd > /dev/null
fi

pushd ./bibliograph/server > /dev/null

echo "Running Codeception tests..."
php vendor/bin/codecept run unit

echo "Redoing migrations..."
echo "travis_fold:start:migrations"
php yii migrate/fresh --interactive=0 --db=testdb -p=@app/migrations/schema
php yii migrate/up --interactive=0 --db=testdb -p=@app/migrations/data
echo "travis_fold:end:migrations"
popd > /dev/null

echo "Running Mocha tests..."
mocha -- ./test/**/*.test.js



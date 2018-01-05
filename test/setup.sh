#!/bin/bash

#set -o errexit # Exit on error

ps | grep "[p]hp yii serve -t=@app/tests 127.0.0.1:8080" > /dev/null
if [ $? -eq 0 ]; then
  echo "Bibliograph test server is running..."
else
  echo "Starting Bibliograph test server..."
  pushd ./bibliograph/server > /dev/null
  php yii serve -t=@app/tests 127.0.0.1:8080 &
  popd > /dev/null
fi

ps | grep "[p]hp yii serve 127.0.0.1:8081" > /dev/null
if [ $? -eq 0 ]; then
  echo "Bibliograph 'production' server is running..."
else
  echo "Starting Bibliograph 'production' server..."
  pushd ./bibliograph/server > /dev/null
  php yii serve 127.0.0.1:8081 &
  popd > /dev/null
fi

echo "Running migrations..."
echo "travis_fold:start:migrations"
pushd ./bibliograph/server > /dev/null
php yii migrate/fresh --interactive=0 --db=testdb -p=@app/migrations/schema
echo "travis_fold:end:migrations"
popd > /dev/null

npm install


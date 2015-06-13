#!/bin/bash
bash ./create-zip.sh
server=demo.panya.de
user=bibliograph
path=public_html/
version=$(cat version.txt)
filename="bibliograph-$version.zip"
echo ">>> Copying files to $server..."
scp ../$filename $user@$server:$path
echo ">>> Extracting files..."
ssh $user@$server "cd $path; unzip -qo $filename; rm $filename"
echo ">>> Deployed $filename on $server."
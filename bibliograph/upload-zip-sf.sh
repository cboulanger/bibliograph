# upload the latest distribution zip package to sourceforge
version=$(cat version.txt)
filename="../bibliograph-$version.zip"
scp -p -i /home/ubuntu/.ssh/id_dsa $filename cboulanger,bibliograph@shell.sourceforge.net:/home/frs/project/bibliograph/
#!/usr/bin/env bash

# decrypt and use deployment SSH config
openssl aes-256-cbc -K $encrypted_f52210703301_key -iv $encrypted_f52210703301_iv -in install/travis/deploy_secrets.tar.enc -out deploy_secrets.tar -d
tar xvf deploy_secrets.tar
eval "$(ssh-agent -s)"
mv ./deploy_rsa ~/.ssh/
chmod 600 ~/.ssh/deploy_rsa
cat ./deploy_config >> ~/.ssh/config
rm -f ./deploy_*
ssh-add ~/.ssh/deploy_rsa

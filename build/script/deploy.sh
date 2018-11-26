#!/usr/bin/env bash
DEPLOY_TARGET=$1
TARGET_DIR=${2:-/var/www/bibliograph}
CONFIG_FILE=${3:-none}

if [[ "$DEPLOY_TARGET" == "" ]]; then
    echo "Usage:"
    echo "deploy.sh <server> <path> <config file>"
    echo "   - name of the deployment server as configured in .ssh/config"
    echo "   - target path on that server (optional, default: /var/www/bibliograph)"
    echo "   - name of config file to copy into installation (optional)"
    exit 1
fi

echo
echo "This will deploy the current code of bibliograph to the following target:"
echo "   Server:           $DEPLOY_TARGET"
echo "   Path:             $TARGET_DIR"
echo "   Config file used: $CONFIG_FILE"
read -r -p "Proceed? [y/N] " response
case "$response" in
  [yY][eE][sS]|[yY])
      # pass
      ;;
  *)
      echo "Aborted."
      exit 0;
      ;;
esac
PARENT_DIR=$(dirname $TARGET_DIR)
ssh $DEPLOY_TARGET mkdir -p $TARGET_DIR
scp dist/*.zip $DEPLOY_TARGET:$PARENT_DIR/bibliograph.zip
ssh $DEPLOY_TARGET unzip -qq -u $PARENT_DIR/bibliograph.zip -d $TARGET_DIR
[ -f $CONFIG_FILE ] && scp $CONFIG_FILE $DEPLOY_TARGET:$TARGET_DIR/server/config/app.conf.toml
# chmod -R 0755 + chown -R www-data
ssh $DEPLOY_TARGET chmod -R 0777 $TARGET_DIR/server/runtime
ssh $DEPLOY_TARGET rm $PARENT_DIR/bibliograph.zip
echo "Done."
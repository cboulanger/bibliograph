#!/bin/bash
# Extract translatable messages from php source files

LOCALE_IDS="de fr en"
BIBLIOGRAPH_DIR=../bibliograph

for LOCALE_ID in $LOCALE_IDS; do

  # bibliograph
  
  DOMAIN=bibliograph_$LOCALE_ID
  SOURCE_DIR=$BIBLIOGRAPH_DIR/services/class/bibliograph
  LOCALE_DIR=$BIBLIOGRAPH_DIR/services/locale/C.UTF-8/LC_MESSAGES
  touch $LOCALE_DIR/$DOMAIN.po
  
  find $SOURCE_DIR -iname "*.php" | xargs \
  xgettext \
   --default-domain $DOMAIN \
   --from-code=UTF-8 \
   --output-dir=$LOCALE_DIR \
   --language=PHP \
   --join-existing \
   --keyword=tr \
   --keyword=trn:1,2 \
   --keyword=marktr \
   --no-wrap \
   --package-name=bibliograph \
   --package-version=$(cat ../version.txt)
   
  msgfmt -o $LOCALE_DIR/$DOMAIN.mo $LOCALE_DIR/$DOMAIN.po
  
  # qcl
  
  DOMAIN=qcl_$LOCALE_ID
  SOURCE_DIR=$BIBLIOGRAPH_DIR/services/class/qcl
  LOCALE_DIR=$BIBLIOGRAPH_DIR/services/class/qcl/locale/C.UTF-8/LC_MESSAGES
  touch $LOCALE_DIR/$DOMAIN.po
  
  find $SOURCE_DIR -iname "*.php" | xargs \
  xgettext \
   --default-domain $DOMAIN \
   --from-code=UTF-8 \
   --output-dir=$LOCALE_DIR \
   --language=PHP \
   --join-existing \
   --keyword=tr \
   --keyword=trn:1,2 \
   --keyword=marktr \
   --no-wrap \
   --package-name=qcl
   
  msgfmt -o $LOCALE_DIR/$DOMAIN.mo $LOCALE_DIR/$DOMAIN.po
  
  # plugins
   
  for d in $BIBLIOGRAPH_DIR/plugins/*/; do
    SOURCE_DIR=$d/services/class
    LOCALE_DIR=$d/services/locale/C.UTF-8/LC_MESSAGES
    
    if [ ! -d "$LOCALE_DIR" ]; then
      continue
    fi
    
    DOMAIN=$(basename $d)_$LOCALE_ID
    touch $LOCALE_DIR/$DOMAIN.po

    find $d -iname "*.php" | xargs \
    xgettext \
    --default-domain $DOMAIN \
    --from-code=UTF-8 \
    --output-dir=$LOCALE_DIR \
    --language=PHP \
    --join-existing \
    --keyword=tr \
    --keyword=trn:1,2 \
    --keyword=marktr \
    --no-wrap \
    --package-name=$(basename $d)
     
    msgfmt -o $LOCALE_DIR/$DOMAIN.mo $LOCALE_DIR/$DOMAIN.po
  done
done
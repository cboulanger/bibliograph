#!/usr/bin/env bash

FILE=$1

declare -a named_ids=(

)

i=0
for SEARCH in "${named_ids[@]}"; do
  REPLACE="user_$i"
  echo " - Replacing $SEARCH with $REPLACE"
  sed -i.bak "s/$SEARCH/$REPLACE/g" $FILE
  let "i=i+1"
done

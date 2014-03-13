#!/bin/sh
MPATH=$1
PO=$2
if [ $3 = 'yes' ]; then
  FORCEFUZZY='-f'
else
  FORCEFUZZY=''
fi

mkdir -p $MPATH

if [ -d $MPATH ]; then
  cd $MPATH/$COMPONENT
  mv $PO messages.po
  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to move .po file."
    exit 1
  fi
  msgfmt $FORCEFUZZY messages.po
  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to compile message catalog to binary format (msgfmt)."
    exit 1
  fi
  rm -f messages.po
  exit 0;
fi
exit 1;

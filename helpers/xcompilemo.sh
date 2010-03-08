#!/bin/sh
MPATH=$1
PO=$2
if [ $3 -eq 'yes' ]; then
  FORCEFUZZY='-f'
else
  FORCEFUZZY=''
fi

mkdir -p $MPATH

if [ -d $MPATH ]; then
  cd $MPATH/$COMPONENT
  mv $PO messages.po
  msgfmt $FORCEFUZZY messages.po
  rm -f messages.po
  exit 0;
fi
exit 1;

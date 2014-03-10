#!/bin/sh
# check command line params
if [ $# -ne 3 ]; then
  echo 1>&2 Usage: $0 [helperpath] [langpath] [corelangfile]
  exit 127
fi

HELPERS=$1
LANGPATH=$2
CORELANG=$3

for c in `find . -iname "*.php"|sed 's!\./!!g'|grep -v lang|grep -v svn`;
do 
  php -f $HELPERS/xmigratepnmlphp.php $c $LANGPATH $CORELANG
done

for c in `find . |grep templates| egrep "htm|tpl"|sed 's!\./!!g'|grep -v lang|grep -v svn`;
do 
  php -f $HELPERS/xmigratepnmltemplate.php $c $LANGPATH $CORELANG
done

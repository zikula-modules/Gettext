#!/bin/sh
# check command line params
if [ $# -ne 3 ]; then
  echo 1>&2 Usage: $0 [/path/to/extension] [/path/to/zikula/root] [domain]
  exit 127
fi

PID=$$
TMPD=/tmp/xtractcomponent.$PID
mkdir -p $TMPD
WHEREAMI=`pwd`
MPATH=$1
MYROOT=$2
DOMAIN=$3
BEFORE=`pwd`
POT=$DOMAIN.pot

cat >$TMPD/pofile.pot <<EOF
# SOME DESCRIPTIVE TITLE.
# Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER
# This file is distributed under the same license as the PACKAGE package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
#, fuzzy
msgid ""
msgstr ""
"Project-Id-Version: Zikula component\n"
"Report-Msgid-Bugs-To: PACKAGE VERSION\n"
"POT-Creation-Date: 2009-08-20 14:41-0400\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
EOF

if [ -d "$MPATH/locale" ]; then
  echo "COPYING TREE"
  cp -a $MPATH/* $TMPD
  cd $TMPD

  echo "EXTRACTING CORE FILES..."
  echo "Finding PHP files..."
  find . -type f -iname "*.php" > filelist.txt
  ls *.php >> filelist.txt
  
  echo "Finding templates..."
  egrep -r "(<\!--\[|\{) {0,}gt [a-zA-Z0-9]+=|(<\!--\[|\{) {0,}[a-zA-Z0-9]+ .+__[a-zA-Z0-9]+=|__\(|_n\(|_f\(|_fn\(|no__\(|_gettext\(|_ngettext\(|_dgettext\(|_dngettext|\{gettext" * |awk -F: '{print $1}'|grep -v .svn|grep -v .php|uniq > t_filelist.txt
  echo "Compiling templates..."
  for TEMPLATE in `cat t_filelist.txt`
  do
    echo $TEMPLATE
    /usr/bin/php -f $MYROOT/modules/Gettext/helpers/xcompile.php $TEMPLATE 
  done
  
  cat t_filelist.txt >> filelist.txt
  echo "EXTRACTING KEYS..."
  xgettext --language=PHP --add-comments=! --from-code=utf-8 \
    --keyword=_gettext:1 --keyword=_ngettext:1,2 --keyword=_dgettext:2 \
    --keyword=_dngettext:2,3 --keyword=__:1 --keyword=_n:1,2 \
    --keyword=__f:1 --keyword=_fn:1,2 --keyword=no__:1 \
    --output-dir=locale -o $POT -f filelist.txt
  msgmerge -U pofile.pot locale/$POT
  cp -f pofile.pot $MPATH/locale/$POT
  echo "Keys created in $MPATH/locale/$POT"    
  rm -rf $TMPD
  cd $BEFORE
  exit 0;
fi
cd "$BEFORE"
echo "ERROR: there is no locale directory, please check"
exit 1;
#!/bin/sh
MPATH=$1
ARCHIVE=$2
COMPONENT=$3
DOMAIN=$4
ARCHV=$5

PO=$DOMAIN.po
POT=$DOMAIN.pot

mkdir -p $MPATH
cd $MPATH
cat >pofile.pot <<EOF
# SOME DESCRIPTIVE TITLE.
# Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER
# This file is distributed under the same license as the PACKAGE package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
#, fuzzy
msgid ""
msgstr ""
"Project-Id-Version: Zikula 1.2.x\n"
"Report-Msgid-Bugs-To: PACKAGE VERSION\n"
"POT-Creation-Date: 2009-08-20 14:41-0400\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
EOF

if [ $ARCHV == 'zip' ]; then
    tar zxf $ARCHIVE >/dev/null 2>/dev/null
fi
unzip $ARCHIVE >/dev/null 2>/dev/null

if [ -d "$MPATH/$COMPONENT/locale" ]; then
  cd $MPATH/$COMPONENT
  touch $PO

  echo "COMPILING TEMPLATES..."
  egrep -r "(<\!--\[|\{) {0,}gt [a-zA-Z0-9]+=|(<\!--\[|\{) {0,}[a-zA-Z0-9]+ .+__[a-zA-Z0-9]+=|__\(|_n\(|_f\(|_fn\(|no__\(|_gettext\(|_ngettext\(|_dgettext\(|_dngettext" * |awk -F: '{print $1}'|grep -v .svn|grep -v .php|uniq > t_filelist.txt
  for TEMPLATE in `cat t_filelist.txt`
  do
    /usr/bin/php -f /tmp/xcompile.php $TEMPLATE
    if [ $? -ne 0 ]; then
      echo "ERROR: Failed to compile $TEMPLATE see output for further information."
    exit 1
  fi
  done
  echo ""

  find . -type f -iname "*.php" > filelist.txt;
  cat t_filelist.txt >> filelist.txt
  echo "SCANNING THE FOLLOWING FILES FOR TRANSLATION STRINGS"
  cat filelist.txt
  echo ""

  echo "EXTRACTING TRANSLATION STRINGS..."
  xgettext --debug --language=PHP --add-comments=! --from-code=utf-8 \
    --keyword=_gettext:1 --keyword=_ngettext:1,2 --keyword=_dgettext:2 \
    --keyword=_dngettext:2,3 --keyword=__:1 --keyword=_n:1,2 \
    --keyword=__f:1 --keyword=_fn:1,2 --keyword=no__:1 \
    --output-dir=$MPATH -o $POT -f filelist.txt 2>&1
  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to extract translation keys - see output for reason."
    exit 1
  fi
  echo "Done."

  echo "GENERATING POT FILE..."
  msgmerge -U $MPATH/pofile.pot $MPATH/$POT 2>&1
  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to generate POT file - see output for explanation."
    exit 1
  fi
  mv $MPATH/pofile.pot $MPATH/$COMPONENT/locale/$POT

  if [ $ARCHV == 'zip' ]; then
    cd ..
    zip -r $DOMAIN.zip $COMPONENT/locale/$POT >/dev/null 2>/dev/null
    if [ $? -ne 0 ]; then
      echo "ERROR: Failed to create ZIP file - see output for explanation."
      exit 1
    fi
  fi
  exit 0
fi
echo "ERROR: This doesn't look like a Gettext enabled module."
echo "    1. Make sure this is a module that has been written for Zikula 1.2.0+"
echo "       The module should NOT have a pnlang/ folder and it must have a locale/"
echo "    2. First make sure the name you entered for the folder matches exactly"
echo "       with the module Folder name (CaSE SenSITive)"
exit 1

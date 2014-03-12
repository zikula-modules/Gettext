#!/bin/sh

if [ "$1" = '-h' -o $# -lt 5 ]
then
    echo 'Usage: '"$0"' outputfolder inputpackage packagename [module|theme]_packagename [zip|plain] [tmp|local]'
    echo ''
    echo ' - outputfolder: absolute path to the automatically created folder for extraction.'
    echo ' - inputpackage: absolute path to the zipfile or folder where the source is located.'
    echo ' - packagename:  the name of the module or theme, e.g. HelloWorld.'
    echo ' - [zip|plain]:  specifies whether the inputpackage is a zip file of a plain folder.'
    echo ' - [tmp|local]:  run scripts from /tmp (default) or relative from local current folder.'
    echo 'Note: specified paths should be absolute'
    exit 1
fi

# assign arguments to variables
MPATH=$1
ARCHIVE=$2
COMPONENT=$3
DOMAIN=$4
ARCHV=$5
if [ "$6" = 'local' ]
then
    # extract the current working dir
    SCRIPTLOC="$(cd "$(dirname "$0")" && pwd)"
else
    SCRIPTLOC="/tmp"
fi

# define the output files
PO=$DOMAIN.po
POJS=${DOMAIN}_js.po
POT=$DOMAIN.pot
POTJS=${DOMAIN}_js.pot

# --- function that will compile the list of strings from the javascript files
compilejsfiles() {
  echo "COMPILING JAVASCRIPT FILES..."
  for TEMPLATE in `cat js_filelist.txt`
  do
    /usr/bin/php -f $SCRIPTLOC/xcompilejs.php $TEMPLATE
    if [ $? -ne 0 ]; then
      echo "ERROR: Failed to compile javascript $TEMPLATE see output for further information."
      exit 1
    fi
  done
}

# --- function that will generate the JS pot file
extractstringsjs() {
  echo "EXTRACTING TRANSLATION STRINGS FROM JS FILES..."
  xgettext --debug --language=PHP --add-comments=! --from-code=utf-8 \
    --keyword=_gettext:1 \
    --keyword=_ngettext:1,2 \
    --keyword=_dgettext:2 \
    --keyword=_dngettext:2,3 \
    --keyword="_pgettext:1c,2" \
    --keyword="_dpgettext:2c,3" \
    --keyword="_npgettext:1c,2,3" \
    --keyword="_dnpgettext:2c,3,4" \
    --keyword=__:1 \
    --keyword=_n:1,2 \
    --keyword=__f:1 \
    --keyword=_fn:1,2 \
    --keyword=no__:1 \
    --keyword=__p:1c,2 \
    --keyword=_np:1c,2,3 \
    --keyword=__fp:1c,2 \
    --keyword=_fnp:1c,2,3 \
    --output-dir=$MPATH -o $POTJS -f js_filelist.txt 2>&1

  echo "GENERATING JS POT FILE..."
  msgmerge -U $MPATH/pofile_js.pot $MPATH/$POTJS 2>&1
  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to generate POT file - see output for explanation."
    exit 1
  fi
  mv $MPATH/pofile_js.pot $MPATH/$COMPONENT/locale/$POTJS
}

# create outputfolder, extract inputpackage and generate empty pot files
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
"Project-Id-Version: Zikula 1.x\n"
"Report-Msgid-Bugs-To: PACKAGE VERSION\n"
"POT-Creation-Date: 2010-01-20 14:41-0400\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
EOF
cp pofile.pot pofile_js.pot

if [ $ARCHV == 'zip' ]; then
  tar zxf $ARCHIVE >/dev/null 2>/dev/null
fi
unzip $ARCHIVE >/dev/null 2>/dev/null

# start the actual extraction
if [ -d "$MPATH/$COMPONENT/locale" ] || [ -d "$MPATH/$COMPONENT/Resources/locale" ]; then
  cd $MPATH/$COMPONENT
  touch $PO
  touch $POJS

  echo "Finding templates..."
  egrep -r "(<\!--\[|\{) {0,}gt [a-zA-Z0-9]+=|(<\!--\[|\{) {0,}[a-zA-Z0-9]+ .+__[a-zA-Z0-9]+=|__p\(|__fp\(|_np\(|_fnp\(|__\(|_n\(|__f\(|_fn\(|no__\(|_gettext\(|_ngettext\(|_dgettext\(|_dngettext\(|_pgettext\(|_npgettext\(|_dpgettext\(|_dnpgettext\(|\{gettext" * |awk -F: '{print $1}'|grep -v .svn|grep -v .php|grep -v .js|uniq > t_filelist.txt

  # separate javascript
  echo "Finding javascript..."
  egrep -r "__p\(|__fp\(|_np\(|_fnp\(|__\(|_n\(|__f\(|_fn\(|no__\(|_gettext\(|_ngettext\(|_dgettext\(|_dngettext\(|_pgettext\(|_npgettext\(|_dpgettext\(|_dnpgettext\(|\{gettext" * |awk -F: '{print $1}'|grep -v .svn|grep .js|uniq > js_filelist.txt

  # determine if there are JS strings, 0 means no, non-zero means yes
  HASJSSTRINGS=`ls -s js_filelist.txt | awk -F" " '{print $1}'`

  echo "COMPILING PHP FILES AND TEMPLATES..."
  for TEMPLATE in `cat t_filelist.txt`
  do
    /usr/bin/php -f $SCRIPTLOC/xcompile.php $TEMPLATE
    if [ $? -ne 0 ]; then
      echo "ERROR: Failed to compile $TEMPLATE see output for further information."
      exit 1
    fi
  done

  if [ "$HASJSSTRINGS" -ne 0 ]; then
    compilejsfiles
  fi

  echo ""

  find . -type f -iname "*.php" > filelist.txt;
  cat t_filelist.txt >> filelist.txt
  echo "SCANNING THE FOLLOWING FILES FOR TRANSLATION STRINGS"
  cat filelist.txt
  echo ""

  # Extraction process for normal PHP and template files.

  echo "EXTRACTING TRANSLATION STRINGS FROM TEMPLATES AND PHP FILES..."
  xgettext --debug --language=PHP --add-comments=! --from-code=utf-8 \
    --keyword=_gettext:1 \
    --keyword=_ngettext:1,2 \
    --keyword=_dgettext:2 \
    --keyword=_dngettext:2,3 \
    --keyword="_pgettext:1c,2" \
    --keyword="_dpgettext:2c,3" \
    --keyword="_npgettext:1c,2,3" \
    --keyword="_dnpgettext:2c,3,4" \
    --keyword=__:1 \
    --keyword=_n:1,2 \
    --keyword=__f:1 \
    --keyword=_fn:1,2 \
    --keyword=no__:1 \
    --keyword=__p:1c,2 \
    --keyword=_np:1c,2,3 \
    --keyword=__fp:1c,2 \
    --keyword=_fnp:1c,2,3 \
    --output-dir=$MPATH -o $POT -f filelist.txt 2>&1
  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to extract translation keys - see output for reason."
    exit 1
  fi

  echo "GENERATING POT FILE..."
  msgmerge -U $MPATH/pofile.pot $MPATH/$POT 2>&1
  if [ $? -ne 0 ]; then
    echo "ERROR: Failed to generate POT file - see output for explanation."
    exit 1
  fi
  mv $MPATH/pofile.pot $MPATH/$COMPONENT/locale/$POT

  # Repeat process for JS
  if [ "$HASJSSTRINGS" -ne 0 ]; then
    extractstringsjs
  fi

  echo "Done."

  if [ $ARCHV == 'zip' ]; then
    cd ..
    zip -r $DOMAIN.zip $COMPONENT/locale/$POT
    # >/dev/null 2>/dev/null
    if [ $? -ne 0 ]; then
      echo "ERROR: Failed to create ZIP file - see output for explanation."
      exit 1
    fi

    if [ "$HASJSSTRINGS" -ne 0 ]; then
      zip -r $DOMAIN.zip $COMPONENT/locale/$POTJS >/dev/null 2>/dev/null
      if [ $? -ne 0 ]; then
        echo "ERROR: Failed to add JS POT to ZIP file - see output for explanation."
        exit 1
      fi
    fi
  fi
  exit 0
fi
echo "ERROR: This doesn't look like a Gettext enabled module."
echo "    1. Make sure this is a module that has been written for Zikula >= 1.2.0"
echo "       The module should NOT have a pnlang/ folder and it must have a locale/ or Resources/locale"
echo "    2. First make sure the name you entered for the folder matches exactly"
echo "       with the module Folder name (CaSE SenSITive)"
exit 1

#!/bin/sh
# check command line params
if [ $# -ne 1 ]; then
  echo 1>&2 Usage: $0 [/path/to/zikula/root]
  exit 127
fi

PID=$$
TMPD=/tmp/xcorecompile.$PID
mkdir -p $TMPD
WHEREAMI=`pwd`
MPATH=$1
DOMAIN=zikula
BEFORE=`pwd`
POT=$DOMAIN.pot
POTJS=${DOMAIN}_js.pot

cat >$TMPD/pofile.pot <<EOF
# SOME DESCRIPTIVE TITLE.
# Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER
# This file is distributed under the same license as the PACKAGE package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
#, fuzzy
msgid ""
msgstr ""
"Project-Id-Version: Zikula Core\n"
"Report-Msgid-Bugs-To: PACKAGE VERSION\n"
"POT-Creation-Date: 2009-08-20 14:41-0400\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
EOF

cp -f $TMPD/pofile.pot $TMPD/pofile_js.pot
echo "COPYING TREE"
cp -a $MPATH/* $TMPD
cd $TMPD

if [ -d "$TMPD/app/Resources/locale" ]; then
  echo "EXTRACTING CORE FILES..."
  echo "Finding PHP files..."
  find -type f -iname "*.php" |egrep "\./lib/|\./includes/|\./install/|\./system/|\./themes/Zikula/Theme/Andreas08Theme/|\.themes/Zikula/Theme/AtomTheme/|\.themes/Zikula/Theme/BootstrapTheme/|\.themes/Zikula/Theme/PrinterTheme/|\.themes/Zikula/Theme/RssTheme/|\.themes/SeaBreezeTheme/" > filelist.txt
  ls *.php >> filelist.txt
  
  echo "Finding templates..."
  egrep -r "(<\!--\[|\{) {0,}gt [a-zA-Z0-9]+=|(<\!--\[|\{) {0,}[a-zA-Z0-9]+ .+__[a-zA-Z0-9]+=|__p\(|__fp\(|_np\(|_fnp\(|__\(|_n\(|__f\(|_fn\(|no__\(|_gettext\(|_ngettext\(|_dgettext\(|_dngettext\(|_pgettext\(|_npgettext\(|_dpgettext\(|_dnpgettext\(|\{gettext" * |awk -F: '{print $1}'|grep -v .svn|grep -v .php|grep -v .js|uniq \
    |egrep "includes/templates/|install/|system/|themes/Zikula/Theme/Andreas08Theme/|themes/Zikula/Theme/AtomTheme/|themes/Zikula/Theme/PrinterTheme/|themes/Zikula/Theme/RssTheme/|themes/SeaBreezeTheme/" > t_filelist.txt

  # separate javascript
  echo "Finding javascript..."
  egrep -r "__p\(|__fp\(|_np\(|_fnp\(|__\(|_n\(|__f\(|_fn\(|no__\(|_gettext\(|_ngettext\(|_dgettext\(|_dngettext\(|_pgettext\(|_npgettext\(|_dpgettext\(|_dnpgettext\(|\{gettext" * |awk -F: '{print $1}'|grep -v .svn|grep .js|uniq \
    |egrep "javascript/|includes/templates/|install/|system/|themes/Zikula/Theme/Andreas08Theme/|themes/Zikula/Theme/AtomTheme/|themes/Zikula/Theme/BootstrapTheme/|themes/Zikula/Theme/PrinterTheme/|themes/Zikula/Theme/RssTheme/|themes/SeaBreezeTheme/" > js_filelist.txt

  echo "Compiling templates..."
  for TEMPLATE in `cat t_filelist.txt`
  do
    echo $TEMPLATE
    /usr/bin/php -f $MPATH/modules/Gettext/Helper/xcompile.php $TEMPLATE
  done

  echo "Compiling javascript files..."
  for TEMPLATE in `cat js_filelist.txt`
  do
    echo $TEMPLATE
    /usr/bin/php -f $MPATH/modules/Gettext/Helper/xcompilejs.php $TEMPLATE
  done
  
  cat t_filelist.txt >> filelist.txt
  echo "EXTRACTING KEYS..."
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
    --output-dir=app/Resources/locale -o $POT -f filelist.txt
  msgmerge -U pofile.pot app/Resources/locale/$POT
  cp -f pofile.pot $MPATH/app/Resources/locale/$POT

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
    --output-dir=app/Resources/locale -o $POTJS -f js_filelist.txt
  msgmerge -U pofile_js.pot app/Resources/locale/$POTJS
  cp -f pofile_js.pot $MPATH/app/Resources/locale/$POTJS

  echo "Keys created in $MPATH/app/Resources/locale/$POT"
  echo "Keys created in $MPATH/app/Resources/locale/$POTJS"

  rm -rf $TMPD
  cd $BEFORE
  exit 0;
fi
cd "$BEFORE"
echo "ERROR: directory {$TMPD} not found or has no locale directory"
exit 1;

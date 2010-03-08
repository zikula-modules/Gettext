#!/bin/sh
#######################################################################################
# Converts language pack of a component
#
# Must have core and module/theme language packs installed for each language
# Gettext must be installed (apt-get install gettext)
# Run the program from the zikula root
# 
# e.g.
# modules/Gettext/helpers/convertlanguagepack.sh [foreignlangcode] [newL2Code] [path to component] [domain] [source encoding]
# NB: Source encoding must be one of ISO-8859-x or UTF-8
# modules/Gettext/helpers/convertlanguagepack.sh deu de modules/Foo module_foo ISO-8859-1
#
#######################################################################################

# check command line params
if [ $# -ne 5 ]; then
  echo 1>&2 Usage: $0 [foreignlangcode] [newL2Code] [path to component] [domain] [source encoding]
  echo 1>&2 Note: Source encoding must be one of ISO-8859-x or UTF-8
  echo 1>&2 Example: modules/Gettext/helpers/convertlanguagepack.sh deu de modules/Foo module_foo ISO-8859-1
  exit 127
fi

FOR=$1
NEWL2CODE=$2
PATHTOCOMPONENT=$3
DOMAIN=$4
SRCENCODING=$5
COREFOR="languages/$FOR/core.php"
COREENG="languages/eng/core.php"
OUTPUT=/tmp/conversion
TRANSLATEHELPER=modules/Gettext/helpers/convertlanguagepackhelper.php
rm -f $FOR.po
rm -f $FOR.po~
rm -f $OUTPUT
rm -f $OUTPUT.tmp
rm -f pofile.po
rm -f pofile.header
cat >pofile.header <<EOF
# SOME DESCRIPTIVE TITLE.
# Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER
# This file is distributed under the same license as the PACKAGE package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
#, fuzzy
msgid ""
msgstr ""
"Project-Id-Version: zWebstore\n"
"Report-Msgid-Bugs-To: PACKAGE VERSION\n"
"POT-Creation-Date: 2009-08-20 14:41-0400\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Poedit-Language: \n"
"X-Poedit-Country: \n"
"X-Poedit-SourceCharset: utf-8\n"
EOF
cp -a pofile.header pofile.po 
if [ -f $TRANSLATEHELPER ]; then

echo "Scanning for language files..."
find . -type d |grep -v '\.svn'| grep $PATHTOCOMPONENT|grep pnlang|awk -F/ '{print $2 "/" $3 "/" $4}'|uniq>dirlist

for DIR in `cat dirlist`;
do
  for FILE in `ls $DIR/eng/*.php`
  do
    DST=`echo $FILE| sed "s#pnlang/eng#pnlang/$FOR#g"`
    echo "processing $DST..."
    #echo "php -f $TRANSLATEHELPER $FILE $DST $COREENG $COREFOR $SRCENCODING $OUTPUT"
    php -f $TRANSLATEHELPER $FILE $DST $COREENG $COREFOR $SRCENCODING $OUTPUT
    if [ $? -eq 1 ]; then
      echo "Merging keys with pofile.po..."
      msgmerge -U pofile.po $OUTPUT
      echo "Done."
      if [ $? -eq 1 ]; then
        echo "FATAL ERROR in msgmerge occured, cannot proceed"
        exit;
      fi
    fi
  done
done
mv -f pofile.po pofile.pot
cp -a pofile.header $FOR.po
echo "Creating the final $FOR.po"
msgmerge -U $FOR.po pofile.pot
if [ -f $PATHTOCOMPONENT/locale/$DOMAIN.pot ]; then
  echo "FOUND $DOMAIN.pot, so I'm merging that - you have a final merge file: $PATHTOCOMPONENT/locale/$NEWL2CODE/LC_MESSAGES/$DOMAIN.po"
  mkdir -p $PATHTOCOMPONENT/locale/$NEWL2CODE/LC_MESSAGES/
  msgmerge -U $FOR.po $PATHTOCOMPONENT/locale/$DOMAIN.pot
  mv -f $FOR.po $PATHTOCOMPONENT/locale/$NEWL2CODE/LC_MESSAGES/$DOMAIN.po
fi
else
  echo "helper file $TRANSLATEHELPER not found"
fi

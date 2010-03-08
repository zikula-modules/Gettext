<?php
if ($_SERVER['argc'] != 7) {
    die("Usage: " . $_SERVER['argv'][0] . " <english_define_file> <foreign_define_file> <english_core_language_file> <foreign_core_language_file> <SRC Encoding> <output_file>\n");
}

$eng = $_SERVER['argv'][1];
$for = $_SERVER['argv'][2];
$coreeng = $_SERVER['argv'][3];
$corefor = $_SERVER['argv'][4];
$srcenc = $_SERVER['argv'][5];
$output = $_SERVER['argv'][6];

if (!is_readable($for)) {
    die ("$for not found\n");
}

$pot = poheader().transcribe($eng, $for, $coreeng, $corefor, $srcenc);
file_put_contents($output, $pot);
exit(1);

function transcribe($eng, $for, $coreeng, $corefor, $srcenc)
{
    $helper1 = "<?php
function pnModLangLoad(\$a=null, \$b=null, \$c=null) {}
if (is_readable('$coreeng')) {
    include_once '$coreeng';
}
include_once '$eng';" . '
$constants = get_defined_constants();
$ini = "";
foreach ($constants as $k => $v) {
    if (substr($k, 0, 1) == "_") {
        //$v = addslashes($v);
$v = str_replace("{", "%%%%%LEFTBRACE%%%%%", $v);
$v = str_replace("}", "%%%%%RIGHTBRACE%%%%%", $v);
$v = str_replace("|", "%%%%%PIPE%%%%%", $v);
$v = str_replace("&", "%%%%%AMP%%%%%", $v);
$v = str_replace("[", "%%%%%LEFTSQUARE%%%%%", $v);
$v = str_replace("(", "%%%%%LEFTBRACKET%%%%%", $v);
$v = str_replace(")", "%%%%%RIGHTBRACKET%%%%%", $v);
$v = str_replace(":", "%%%%%COLON%%%%%", $v);
$v = str_replace("\"", "%%%%%DQUOTE%%%%%", $v);
$v = str_replace("\'", "%%%%%SQUOTE%%%%%", $v);
$v = str_replace("/", "%%%%%FWDSLASH%%%%%", $v);
$v = str_replace("<", "%%%%%LEFTANGLE%%%%%", $v);
$v = str_replace(">", "%%%%%RIGHTANGLE%%%%%", $v);
$v = str_replace(".", "%%%%%POINT%%%%%", $v);
$v = str_replace("=", "%%%%%EQUALS%%%%%", $v);
$v = str_replace("!", "%%%%%PLING%%%%%", $v);
$v = str_replace("\$", "%%%%%DOLLAR%%%%%", $v);

        $ini .= "$k = $v\n";
    }
}
file_put_contents("/tmp/eng.ini", $ini);
';

    $helper2 = "<?php
function pnModLangLoad(\$a=null, \$b=null, \$c=null) {}
if (is_readable('$corefor')) {
    include_once '$corefor';
}
include '$for';" . '
$constants = get_defined_constants();
$ini = "";
foreach ($constants as $k => $v) {
    if (substr($k, 0, 1) == "_") {
$v = str_replace("{", "%%%%%LEFTBRACE%%%%%", $v);
$v = str_replace("}", "%%%%%RIGHTBRACE%%%%%", $v);
$v = str_replace("|", "%%%%%PIPE%%%%%", $v);
$v = str_replace("&", "%%%%%AMP%%%%%", $v);
$v = str_replace("[", "%%%%%LEFTSQUARE%%%%%", $v);
$v = str_replace("(", "%%%%%LEFTBRACKET%%%%%", $v);
$v = str_replace(")", "%%%%%RIGHTBRACKET%%%%%", $v);
//$v = str_replace(":", "%%%%%COLON%%%%%", $v);
$v = str_replace("\"", "%%%%%DQUOTE%%%%%", $v);
$v = str_replace("\'", "%%%%%SQUOTE%%%%%", $v);
//$v = str_replace("/", "%%%%%FWDSLASH%%%%%", $v);
//$v = str_replace("<", "%%%%%LEFTANGLE%%%%%", $v);
//$v = str_replace(">", "%%%%%RIGHTANGLE%%%%%", $v);
//$v = str_replace(".", "%%%%%POINT%%%%%", $v);
$v = str_replace("=", "%%%%%EQUALS%%%%%", $v);
$v = str_replace("!", "%%%%%PLING%%%%%", $v);
$v = str_replace("\$", "%%%%%DOLLAR%%%%%", $v);
$ini .= "$k = $v\n";
    }
}
file_put_contents("/tmp/for.ini", $ini);
';

    file_put_contents('/tmp/translation_helper.php', $helper1);
    `php -f /tmp/translation_helper.php`;
    file_put_contents('/tmp/translation_helper.php', $helper2);
    `php -f /tmp/translation_helper.php`;

    $eng = parse_ini_file('/tmp/eng.ini');
    $for = parse_ini_file('/tmp/for.ini');


    $po = '';
    foreach ($for as $k => $v) {
        // do the translation matching
        $str = decode($eng[$k]);
        $v = decode($v);

        // convert to utf8
        $srcenc = strtoupper($srcenc);
        if ($srcenc != 'UTF-8') {
            $str = iconv($srcenc, 'UTF-8', $str);
            $v = iconv($srcenc, 'UTF-8', $v);
        }

        // deal with nonesense
        $str = str_replace('"', '\"', $str);
        $v = str_replace('"', '\"', $v);
        $str = str_replace('\n\n', '[CRCR]', $str);
        $v = str_replace('\n\n', '[CRCR]', $v);
        $str = str_replace('\n\t', '[EOO]', $str);
        $v = str_replace('\n\t', '', $v);
        $str = str_replace('\$', '$', $str);
        $v = str_replace('\$', '$', $v);

        // prevent duplicate keys
        $array[$str] = $v;
    }

    foreach ($array as $k => $v) {
        if (!empty($k)) {
            $po .= "msgid \"$k\"\n";
            $po .= "msgstr \"$v\"\n\n";
        }
    }

    return $po;
}

function decode($str)
{
    $str = str_replace("%%%%%LEFTBRACE%%%%%", "{", $str);
    $str = str_replace("%%%%%RIGHTBRACE%%%%%", "}", $str);
    $str = str_replace("%%%%%PIPE%%%%%", "|", $str);
    $str = str_replace("%%%%%AMP%%%%%", "&", $str);
    $str = str_replace("%%%%%LEFTSQUARE%%%%%", "[", $str);
    $str = str_replace("%%%%%LEFTBRACKET%%%%%", "(", $str);
    $str = str_replace("%%%%%RIGHTBRACKET%%%%%", ")", $str);
    $str = str_replace("%%%%%COLON%%%%%", ":", $str);
    $str = str_replace("%%%%%DQUOTE%%%%%", "\"", $str);
    $str = str_replace("%%%%%SQUOTE%%%%%", "'", $str);
    $str = str_replace("%%%%%FWDSLASH%%%%%", "/", $str);
    $str = str_replace("%%%%%LEFTANGLE%%%%%", "<", $str);
    $str = str_replace("%%%%%RIGHTANGLE%%%%%", ">", $str);
    $str = str_replace("%%%%%POINT%%%%%", ".", $str);
    $str = str_replace("%%%%%EQUALS%%%%%", "=", $str);
    $str = str_replace("%%%%%PLING%%%%%", "!", $str);
    $str = str_replace("%%%%%DOLLAR%%%%%", '$', $str);
    return $str;
}

function poheader()
{
    $header ="# SOME DESCRIPTIVE TITLE.
# Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER
# This file is distributed under the same license as the PACKAGE package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
#, fuzzy
";
    $header .= "msgid \"\"\n";
    $header .= "msgstr \"\"\n";
    $header .= '"Project-Id-Version: Zikula 1.2.x\n"' . "\n";
    $header .= '"Report-Msgid-Bugs-To: PACKAGE VERSION\n"' . "\n";
    $header .= '"POT-Creation-Date: 2009-08-20 14:41-0400\n"' . "\n";
    $header .= '"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"' . "\n";
    $header .= '"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"' . "\n";
    $header .= '"Language-Team: LANGUAGE <LL@li.org>\n"' . "\n";
    $header .= '"MIME-Version: 1.0\n"' . "\n";
    $header .= '"Content-Type: text/plain; charset=UTF-8\n"' . "\n";
    $header .= '"Content-Transfer-Encoding: 8bit\n"' . "\n";
    $header .= '"X-Poedit-Language: \n"' . "\n";
    $header .= '"X-Poedit-Country: \n"' . "\n";
    $header .= '"X-Poedit-SourceCharset: utf-8\n"' . "\n\n";

    return $header;
}


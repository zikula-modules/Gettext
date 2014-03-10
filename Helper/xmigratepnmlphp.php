<?php

/**
 * Zikula Application Framework
 *
 * @copyright (c) 2014, Zikula Foundation
 * @link http://www.zikula.org
 * @license GNU/LGPL - http://www.gnu.org/copyleft/lgpl.html
 */

/**
 * requires
 * $1 target to convert
 * $2 language path e.g. pnlang/eng
 * $3 core language file
 */
error_reporting(E_ALL);
$phpfile = $_SERVER['argv'][1];
$langpath = $_SERVER['argv'][2];
$corelangfile = $_SERVER['argv'][3];

// get file
$contents = file_get_contents($phpfile);

// include language defines
require_once $corelangfile;
if ($dir = opendir($langpath)) {
    while ($file = readdir($dir)) {
        if (!in_array($file, array('.', '..', '.svn'))) {
            require_once ($langpath.DIRECTORY_SEPARATOR.$file);
        }
    }
    closedir($dir);
}

// do replace
$contents = replaceConstants($contents);

// write file
echo "converted $phpfile\n";
file_put_contents($phpfile, $contents);

function replaceConstants($contents)
{
    $contents = str_replace('__', '#######', $contents);
    $contents = preg_replace('#(\s[^_])([A-Z0-9]+)_([A-Z0-9]+)_([A-Z0-9]+)#', '$1$2%%%%%$3%%%%%$4', $contents);
    $contents = preg_replace('#(\s[^_])([A-Z0-9]+)_([A-Z0-9]+)#', '$1$2%%%%%$3', $contents);

    $constants = get_defined_constants();

    // reverse the order so short defines dont ruin long ones
    krsort($constants);
    foreach ($constants as $k => $v) {
        if (preg_match('#^_[a-zA-Z0-9].*#', $k)) {
            $len = strlen($k);
            $c[$k] = $len;
        }
    }
    arsort($c);
    foreach ($c as $k => $v) {
        $final[$k] = $constants[$k];
    }

    foreach ($final as $key => $value) {
        $contents = str_ireplace('pnML(', 'pnML(', $contents);
        $contents = str_ireplace("pnML('$key',", "__f('$value';,", $contents);
        $contents = str_ireplace("pnML($key,", "__f('$value';,", $contents);
        $contents = str_ireplace("pnML($key", "__f('$value';", $contents);
        $contents = str_replace("'$key',", "__('$value', \$dom),", $contents);
        $contents = str_replace("\"$key\",", "__(\"$value\", \$dom),", $contents);
        $contents = str_replace("$key,", "__('$value', \$dom),", $contents);
        $contents = str_replace("$key)", "__('$value', \$dom))", $contents);
        //$contents = preg_replace('#.([^_]_{1}[a-zA-Z0-9]+[a-zA-Z0-9_]+))#', ".__('$value', \$dom))", $contents);
        $contents = str_replace("$key;", "__('$value', \$dom);", $contents);
        //$contents = preg_replace('#([^_]_{1}[a-zA-Z0-9]+[a-zA-Z0-9_]+));#', "__('$value', \$dom);", $contents);
        $contents = str_replace(".$key", ".__('$value', \$dom)", $contents);
        $contents = str_replace("$key.", "__('$value', \$dom).", $contents);
        //$contents = preg_replace('#\.([^_]_{1}[a-zA-Z0-9]+[a-zA-Z0-9_]+))#', ".__('$value', \$dom))", $contents);

    }

    $contents = str_replace('%%%%%', '_', $contents);
    $contents = str_replace('#######', '__', $contents);
    return $contents;
}


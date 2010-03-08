<?php
/**
 * requires
 * $1 target to convert
 * $2 language path e.g. pnlang/eng
 * $3 core language file
 */
error_reporting(E_ALL);
$template = $_SERVER['argv'][1];
$langpath = $_SERVER['argv'][2];
$corelangfile = $_SERVER['argv'][3];

// get initial template
$contents = file_get_contents($template);

// include language defines
require_once $corelangfile;
if ($dir = opendir($langpath)) {
    while ($file = readdir($dir)) {
        if (!in_array($file, array(
            '.',
            '..',
            '.svn'))) {
            require_once ($langpath . DIRECTORY_SEPARATOR . $file);
        }
    }
    closedir($dir);
}

/// convert tags
$contents = preg_replace("#<!--\[\s*pnml\sname='#si", '<!--[gt text=\'', $contents);
$contents = preg_replace("#<!--\[\s*pnml\sname=\"#si", '<!--[gt text="', $contents);
$contents = preg_replace("#<!--\[\s*pnml\sname=#si", '<!--[gt text="', $contents);

// handle inconsistencies
$contents = processContent($contents, true);

//replace constants with real translation strings
$contents = replaceTemplateConstants($contents);

// write file
echo "converted $template\n";
file_put_contents($template, $contents);

function replaceTemplateConstants($contents)
{
    $contents = preg_replace('#(=[^_])([A-Z0-9]+)_([A-Z0-9]+)_([A-Z0-9]+)_([A-Z0-9]+)#', '$1$2%%%%%$3%%%%%$4%%%%%$5', $contents);
    $contents = preg_replace('#(=[^_])([A-Z0-9]+)_([A-Z0-9]+)_([A-Z0-9]+)#', '$1$2%%%%%$3%%%%%$4', $contents);
    $contents = preg_replace('#(=[^_])([A-Z0-9]+)_([A-Z0-9]+)#', '$1$2%%%%%$3', $contents);

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
        $contents = str_replace("$key", "$value", $contents);
    }

    $contents = str_replace('%%%%%', '_', $contents);
    return $contents;
}

function parse($text)
{
    $name = '';
    $value = '';
    $nodes = array();
    $length = strlen($text);
    $position = 0;
    $startDelim = '';
    while ($position < $length) {
        // value expected
        if (!empty($name)) {
            while ($position < $length) {
                $c = substr($text, $position, 1);

                if ($c == '\\' && strpos('\'"', substr($text, $position + 1, 1))) {
                    $value .= substr($text, $position, 2);
                    $position += 2;
                    continue;
                }

                // check EOV
                if (empty($startDelim)) {
                    if (strpos("\r\n\t ", $c) !== false) {
                        $position += 1;
                        break;
                    }
                } elseif ($c == $startDelim) {
                    $position += 1;
                    break;
                }

                $value .= substr($text, $position, 1);
                $position += 1;
            }

            // store key
            $nodes[trim($name)] = $startDelim . $value . $startDelim;

            // reset
            $name = '';
            $value = '';
            $startDelim = '';
        } else {
            // skip all whitespaces etc.
            while ($position < $length) {
                $c = substr($text, $position, 1);
                if (strpos("\r\n\t ", $c) === false) {
                    break;
                }
                $position++;
            }

            // get name
            while ($position < $length) {
                $c = substr($text, $position, 1);

                if (strpos("=", $c) !== false) {
                    $position++;
                    while ($position < $length) {
                        $c = substr($text, $position, 1);
                        if (strpos("'\"", $c) !== false) {
                            $startDelim = $c;
                            $position++;
                            break;
                        }
                        if (strpos("\r\n\t ", $c) === false) {
                            break;
                        }
                        $position++;
                    }
                    break;
                }
                $name .= $c;
                $position++;
            }

            // fix spaces in the name
            $temp = split("[\r\n\t ]+", $name);
            if (count($temp) > 1) {
                $name = array_pop($temp);
            }
        }
    }
    return ($nodes);
}

function handleNode($node, $cleanup = false)
{
    if (preg_match('#\s*([^\s]+)\s*(.*)#si', $node, $match)) {
        if (isset($match[1])) {
            $tags = '';
            $name = strtolower(trim($match[1]));
            $result = parse($match[2]);

            if ($name == 'gt') {
                foreach ($result as $k => $v) {
                    switch ($k) {
                        case 'html':
                        case 'noprocess':
                            break;
                        default:
                            $tags .= " $k=$v";
                            break;
                    }
                }
                $node = '<!--[' . $name . $tags . ']-->';
            } elseif (preg_match('#^pnform|^content|^pnimg|^pnicon|^pnbutton|extension#', $name)) {
                foreach ($result as $k => $v) {
                    switch ($k) {
                        case 'text':
                        case 'confirmMessage':
                        case 'tooltip':
                        case 'toolTip':
						case 'alt':
					    case 'title':
                            $k = '__' . $k;
                    }
                    if (substr($v, 0, 1) == '$' || preg_match('#[\'|"]#', substr($v, 0, 1))) {
                        $tags .= " $k=$v";
                    } else {
                        $tags .= " $k='$v'";
                    }
                }
                $node = '<!--[' . $name . $tags . ']-->';
            } else {
                $node = '<!--[' . $match[0] . ']-->';
            }
        }

    }

    // add a little magic for good luck
    $node = preg_replace('#\salt=([\'"][^<\$][A-Za-z]*)#', ' __alt=$1', $node);
    $node = preg_replace('#\stitle=([\'"][^<\$][A-Za-z]*)#', ' __title=$1', $node);
    $node = preg_replace('#altml=[\'"]{0,1}true[\'"]{0,1}|titleml=[\'"]true[\'"]#', '', $node);
    return $node;
}

function processContent($content, $cleanup = false)
{
    return (preg_replace_callback('#<!--\[\s*(.*?)\s*\]-->#si', create_function('$matches', 'return handleNode($matches[1]' . ($cleanup ? ', true' : '') . ');'), $content));
}




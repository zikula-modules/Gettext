<?php
if ($_SERVER['argc'] < 2) {
    die("Usage: php -f xcompile.php /full/path/to/templatefile.htm [old|new]\n");
}

// defines to make this more readable otherwise it's symbol soup.
define('PO', '<?php ');
define('PC', ' ?>');
define('GO', ' echo __(');
define('GC', ');');
define('NO', ' echo _n(');
define('NC', ');');
define('COMMA', ' ,');
$delimiterType = (isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : 'old');
if ($delimiterType == 'new') {
    define('TDO', '{');
    define('TDC', '}');
} else {
    define('TDO', '<!--[');
    define('TDC', ']-->');
}

$file = $_SERVER['argv'][1];
// transform shortcuts into valid markup
processFile($file);
/* test cases
$content = "<!--[pnimg src=drak.jpg __alt='hello']-->\n";
$content .= "<!--[gt text='hello %s %s' plural=\"hello are %s %s\" count=2 tag1=\"world\" __tag2='drak' tag3=\$var comment='this is a comment']-->\n";
$content = "{pnimg src=drak.jpg __alt='hello'}\n";
$content .= "{gt text='hello %s %s' plural=\"hello are %s %s\" count=2 tag1=\"world\" __tag2=\"google's search engine\" tag3=\$var comment='this is a comment'}\n";
$content .= "%%%'first shortcut'%%%\n";
$content .= "%%%\"second shortcut\"%%%";
$content = z_filter_gettext_params($content);

$content = processContent($content);
*/

echo "compiled $file\n";
exit(0);

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
                } elseif (($c == $startDelim) && substr($text, $position - 1, 1) !== '\\') {
                    $position += 1;
                    break;
                }

                $value .= substr($text, $position, 1);
                $position += 1;
            }

            // store key
            $nodes[strtolower(trim($name))] = $startDelim . $value . $startDelim;

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
            $temp = preg_split("/[\r\n\t\s]+/", $name);
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
            $name = strtolower(trim($match[1]));
            $result = parse($match[2]);

            /* compile any instances of __var=value to var=<?php __($value); ?> */
            $tags = '';
            foreach ($result as $k => $v) {
                if (substr($k, 0, 2) == '__') {
                    unset($result[$k]);
                    $QQ = substr($v, 0, 1);
                    $k = substr($k, 2, strlen($k));
                    if (substr($v, 0, 1) !== '$') {
                        $php = PO . GO . $v . GC . PC;
                        $tags .= " $k=" . $QQ . PO . GO . $v . GC . PC . $QQ;
                    } else {
                        $tags .= " $k=" . $v;
                    }
                    $result[$k] = $php;
                }
            }

            // replace values in the original match
            $match[0] = $tags;

            $valid = 0;
            if ($name == 'gt') {
                $comment = (isset($result['comment']) ? "/*! {" . $result['comment'] . "}*/" : '');
                if (isset($result['text']) && substr($result['text'], 0, 1) !== '$') {
                    $text = $result['text'];
                    if (isset($result['plural'])) {
                        $plural = $result['plural'];
                        if (isset($result['count'])) {
                            $valid = 2;
                        } else {
                            $valid = 0;
                        }
                    } else {
                        $valid = 1;
                    }
                } else {
                    $valid = 0;
                }

                switch ($valid) {
                    case 1:
                        $result['text'] = PO . GO . $text . GC . PC;
                        break;
                    case 2:
                        $result['text'] = PO . NO . $comment . $text . COMMA . $plural . COMMA . $result['count'] . NC . PC;
                        unset($result['plural']);
                        break;
                    default:
                        break;
                }
                $tags = '';
                foreach ($result as $k => $v) {
                    $tags .= " $k=$v";
                }

            }
        }
    }
    //return $node = TDO . $name . $tags . TDC;
    return $node = $name . $tags;
}

function processContent($content, $cleanup = false)
{
    $content = preg_replace_callback('`(<(script|style)[^>]*>)(.*?)(</\2>)`s', 'z_prefilter_add_literal_callback', $content);

    // process {gettext} blocks
    $content = preg_replace_callback('#(\{gettext\s{0,}comment=)((["|\'])(.+)(["|\'])\})(.*)\{/gettext\}#Usimx', 'z_block_gettext_comment', $content);
    $content = preg_replace_callback('#\{gettext\}(.*)\{/gettext\}#Usimx', 'z_block_gettext_nocomment', $content);
    
    // compile plugin tags
    $regex = '#{\s*(.*?)\s*}#';
    $content = preg_replace_callback($regex, create_function('$matches', 'return handleNode($matches[1]' . ($cleanup ? ', true' : '') . ');'), $content);

    // compile any template php open tags
    $regex = '#{\s*php\s*}#i';
    $content = preg_replace($regex, PO, $content);

    // compile any template php close tags
    $regex = '#{\s*/php\s*}#i';
    $content = preg_replace($regex, PC, $content);

    // compile plugin tags
    $regex = '#<!--\[\s*(.*?)\s*\]-->#';
    $content = preg_replace_callback($regex, create_function('$matches', 'return handleNode($matches[1]' . ($cleanup ? ', true' : '') . ');'), $content);

    // compile any template php open tags
    $regex = '#<!--\[\s*php\s*\]-->#i';
    $content = preg_replace($regex, PO, $content);

    // compile any template php close tags
    $regex = '#<!--\[\s*/php\s*\]-->#i';
    $content = preg_replace($regex, PC, $content);

    return $content;
}

function processFile($filename)
{
    if (($content = file_get_contents($filename))) {
        // transform shortcuts
        $content = z_filter_gettext_params($content);
        // compile
        $content = processContent($content);
        file_put_contents($filename, $content);
    }
}

function z_filter_gettext_params($tpl_source)
{
    return (preg_replace_callback('#%(("|\')(.*)("|\'))%#', create_function('$m', 'return TDO . "gt text=" . $m[1] . TDC;'), $tpl_source));
}

function z_prefilter_add_literal_callback($matches)
{
    $tagOpen = $matches[1];
    $script = $matches[3];
    $tagClose = $matches[4];

    $script = str_replace('{{', '{', str_replace('}}', '}', $script));

    return $tagOpen . $script . $tagClose;
}

function z_block_gettext_comment($matches)
{
    // 3 = delimiter
    // 4 = comment
    // 6 = gettext
    $gettext = str_replace("'", "\\'", $matches[6]);
    return PO . GO . '/*! ' . $matches[4] . ' */' . "'$gettext'" . GC . PC;
}

function z_block_gettext_nocomment($matches)
{
    $gettext = str_replace("'", "\\'", $matches[1]);
    return PO . GO . "'$gettext'" . GC . PC;
}
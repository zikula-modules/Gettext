<?php
if ($_SERVER['argc'] < 2) {
    die("Usage: php -f xcompilejs.php /full/path/to/templatefile.htm [old|new]\n");
}

define('PO', '<?php ');
define('PC', ' ?>');

$file = $_SERVER['argv'][1];
// transform javascript into gettext parsable markup
processFile($file);

echo "compiled $file\n";
exit(0);

function compile_js($matches)
{
    return PO . 'echo ' . $matches[1] . ');' . PC;
}

function processFile($filename)
{
    if (($content = file_get_contents($filename))) {
        // transform shortcuts
        $content = preg_replace_callback('%Zikula.((__|__f|_n|_fn)\((.*)\))%Usimx', 'compile_js', $content);
        file_put_contents($filename, $content);
    }
}

<?php

/**
 * Zikula Application Framework
 *
 * @copyright (c) 2014, Zikula Foundation
 * @link http://www.zikula.org
 * @license GNU/LGPL - http://www.gnu.org/copyleft/lgpl.html
 */

// PHP4 safe!
error_reporting(~E_STRICT);
if ($_SERVER['argc'] < 2) {
    die("Usage: php -f compileall.php /full/path/to/zikula/root\n");
}

$compiler = new Compiler();
$compiler->setDirs('modules');
$compiler->process('modules');
$compiler->setDirs('themes');
$compiler->process('themes');
$compiler->cleanup();

class Compiler
{
    var $dirs;
    var $path;
    var $root;
    var $mtype;
    var $scripthome;

    function __construct()
    {
        $this->scripthome = getcwd();
        $time = microtime();
        $pid = sha1($time);
        $this->path = "/tmp/$pid";

        $path = $_SERVER['argv'][1];
        if (!is_dir($path)) {
            die("ERROR: Path $path does not exist\n");
        }

        $this->root = $path;
        chdir($this->root);
        `mkdir $this->path`;

    }

    function setDirs($dir)
    {
        $this->mtype = $dir;
        $this->dirs = $this->getI18nCapableDirs($dir);
    }

    function process($cd)
    {
        $cd = $this->root . "/$cd";
        chdir($cd);
        foreach ($this->dirs as $dir) {
            $archive = "$this->path/$dir.zip";
            $command = "zip -r $archive $dir";
            `$command`;

            if ($this->mtype == 'modules') {
                $mtype = 'module';
            } else if ($this->mtype == 'themes') {
                $mtype = 'theme';
            }

            $component = $dir;
            $domain = strtolower("{$mtype}_$component");
            echo "    compiling $domain...\n";
            $this->compile($this->path, $archive, $component, $domain);
        }
        // reset location for next round
        chdir($this->root);
    }
    function getI18nCapableDirs($dir)
    {
        // check the language folders in $dir
        $localeArray = array();
        echo "Scanning $dir...\n";
        if ($h = opendir($dir)) {
            while ($module = readdir($h)) {
                if (is_dir("$dir/$module/locale") && !in_array($module, array('.', '..', '.svn'))) {
                    $localeArray[] = "$module";
                    echo "  found $dir/$module\n";
                }
            }
            closedir($h);
        }

        return $localeArray;
    }

    function compile($path, $archive, $component, $domain)
    {
        $home = $this->scripthome;
        // get files
        $helper = file_get_contents("$home/xtractor.sh");
        file_put_contents('/tmp/xtractor.sh', $helper);
        echo `chmod 755 /tmp/xtractor.sh`;
        $helper = file_get_contents("$home/xcompile.php");
        file_put_contents('/tmp/xcompile.php', $helper);
        $command = "/tmp/xtractor.sh $path $archive $component $domain none";
        echo "$command\n";
        echo `$command`;
        $command = "cp -f $path/$component/locale/$domain.pot $this->root/$this->mtype/$component/locale";
        echo "$command\n";
        echo `$command`;
    }

    function cleanup()
    {
        `rm -rf $this->path`;
    }
}
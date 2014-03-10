<?php

/**
 * Zikula Application Framework
 *
 * @copyright (c) 2009, Zikula Foundation
 * @link http://www.zikula.org
 * @license GNU/LGPL - http://www.gnu.org/copyleft/lgpl.html
 */

namespace Zikula\GettextModule\Controller;

use LogUtil;
use SecurityUtil;
use Zikula_View;
use ZLanguage;
use FormUtil;
use ModUtil;
use System;

class UserController extends \Zikula_AbstractController
{
    /**
     * entry point for the module
     */
    public function main($args)
    {
        // security check
        if (!SecurityUtil::checkPermission('Gettext::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        // create a new output object
        $view = Zikula_View::getInstance('Gettext', false);
        $view->assign('mtype', '');
        return $view->fetch('gettext_user_extract.htm');
    }
    
    public function extract($args)
    {
        // security check
        if (!SecurityUtil::checkPermission('Gettext::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $thisdomain = ZLanguage::getModuleDomain('Gettext');
        $view = Zikula_View::getInstance('Gettext', false);
        $component = FormUtil::getPassedValue('component', '', 'POST');
        $component = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $component);
        $mtype = FormUtil::getPassedValue('mtype', '', 'POST');
        $domain = strtolower("{$mtype}_{$component}");
        $submit = FormUtil::getPassedValue('submit', null, 'POST');
        if (!isset($submit)) {
            return $view->fetch('gettext_user_extract.htm');
        }
        if (!$mtype) {
            LogUtil::registerError(__('You must select module or theme', $thisdomain));
            return $view->fetch('gettext_user_extract.htm');
        }
        if (!$component) {
            LogUtil::registerError(__('You must enter the name of the module or theme (case sensitive)', $thisdomain));
            return $view->fetch('gettext_user_extract.htm');
        }
        if (empty($_FILES['archive']['tmp_name'])) {
            LogUtil::registerError(__('Please specify zip/tgz file!', $thisdomain));
            return $view->fetch('gettext_user_extract.htm');
        }
        // get files
        $archive = $_FILES['archive']['tmp_name'];
        // setup
        $time = microtime();
        $pid = sha1($time);
        $path = "/tmp/{$pid}";
        // get files
        $archive = $_FILES['archive']['tmp_name'];
        $helper = file_get_contents('modules/Gettext/helpers/xtractor.sh');
        file_put_contents('/tmp/xtractor.sh', $helper);
        `chmod 755 /tmp/xtractor.sh`;
        $helper = file_get_contents('modules/Gettext/helpers/xcompile.php');
        file_put_contents('/tmp/xcompile.php', $helper);
        $helper = file_get_contents('modules/Gettext/helpers/xcompilejs.php');
        file_put_contents('/tmp/xcompilejs.php', $helper);
        $command = "/tmp/xtractor.sh {$path} {$archive} {$component} {$domain} zip none";
        $output = '';
        exec($command, $outputArray, $result);
        foreach ($outputArray as $out) {
            $output .= "{$out}\n";
        }
        `/bin/rm -f /tmp/xtractor.sh`;
        `/bin/rm -f /tmp/xcompile.php`;
        `/bin/rm -f /tmp/xcompilejs.php`;
        `rm -rf /{$path}/{$component}`;
        $view->assign('result', $result);
        $view->assign('key', $pid);
        $view->assign('c', $component);
        $view->assign('d', $domain);
        $view->assign('output', $output);
        return $view->fetch('gettext_user_download.htm');
    }
    
    public function download($args)
    {
        // security check
        if (!SecurityUtil::checkPermission('Gettext::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $key = FormUtil::getPassedValue('key', null, 'GET');
        $key = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $key);
        $c = FormUtil::getPassedValue('c', null, 'GET');
        $c = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $c);
        $d = FormUtil::getPassedValue('d', null, 'GET');
        $d = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $d);
        $file = "/tmp/{$key}/{$d}.zip";
        $contents = file_get_contents($file);
        $length = filesize($file);
        if ($length < 1) {
            System::redirect(ModUtil::url('Gettext'));
        }
        ob_end_clean();
        ini_set('zlib.output_compression', 0);
        header('Cache-Control: no-store, no-cache');
        header('Content-Type: application/x-zip');
        header("Content-Length: {$length}");
        header("Content-Disposition: attachment;filename={$c}-extracted.zip");
        header('Content-Description: Gettext POT File');
        echo $contents;
        `rm -rf /tmp/{$key}`;
        die;
    }
    
    public function compilemo()
    {
        $domain = ZLanguage::getModuleDomain('Gettext');
        $view = Zikula_View::getInstance('Gettext', false);
        // security check
        if (!SecurityUtil::checkPermission('Gettext::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $submit = FormUtil::getPassedValue('submit', null, 'POST');
        $forcefuzzy = FormUtil::getPassedValue('forcefuzzy', false, 'POST');
        $forcefuzzy = $forcefuzzy ? 'yes' : 'no';
        if (!isset($submit)) {
            return $view->fetch('gettext_user_compilemo.htm');
        }
        if (empty($_FILES['po']['tmp_name'])) {
            LogUtil::registerError(__('Please specify .po file!', $domain));
            return $view->fetch('gettext_user_compilemo.htm');
        }
        // get files
        $po = $_FILES['po']['tmp_name'];
        $helper = file_get_contents('modules/Gettext/helpers/xcompilemo.sh');
        file_put_contents('/tmp/xcompilemo.sh', $helper);
        `chmod 755 /tmp/xcompilemo.sh`;
        // setup
        $time = microtime();
        $pid = sha1($time);
        $path = "/tmp/{$pid}";
        `/tmp/xcompilemo.sh {$path} {$po} {$forcefuzzy}`;
        `rm -f /tmp/xcompilemo.sh`;
        $view->assign('key', $pid);
        return $view->fetch('gettext_user_downloadmo.htm');
    }
    
    public function downloadmo($args)
    {
        // security check
        if (!SecurityUtil::checkPermission('Gettext::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $key = FormUtil::getPassedValue('key', null, 'GET');
        $key = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $key);
        $file = "/tmp/{$key}/messages.mo";
        $contents = file_get_contents($file);
        $length = strlen($contents);
        if ($length < 1) {
            System::redirect(ModUtil::url('Gettext'));
        }
        header('Content-Type: application/octet-stream');
        header("Content-Length: {$length}");
        header('Content-Disposition: attachment;filename=messages.mo');
        header('Content-Description: gettext mo file');
        echo $contents;
        `rm -rf /tmp/{$key}`;
        return 'Finished!';
    }

}
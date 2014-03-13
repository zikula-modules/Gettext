<?php

/**
 * Zikula Application Framework
 *
 * @copyright (c) 2014, Zikula Foundation
 * @link http://www.zikula.org
 * @license GNU/LGPL - http://www.gnu.org/copyleft/lgpl.html
 */

namespace Zikula\GettextModule\Controller;

use LogUtil;
use SecurityUtil;
use ModUtil;
use System;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserController extends \Zikula_AbstractController
{
    /**
     * entry point for the module
     */
    public function mainAction()
    {
        return new RedirectResponse(System::normalizeUrl(ModUtil::url('Gettext', 'user', 'extract')));
    }
    
    public function extractAction()
    {
        // security check
        if (!SecurityUtil::checkPermission('Gettext::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $this->view->setCaching(false);
        $component = $this->request->request->get('component', '');
        $component = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $component);
        $mtype = $this->request->request->get('mtype', '');
        $this->view->assign('mtype', $mtype);
        $domain = strtolower("{$mtype}_{$component}");
        $submit = $this->request->request->get('submit', null);
        if (!isset($submit)) {
            $this->view->assign('mtype', '');
            return $this->response($this->view->fetch('User/extract.tpl'));
        }
        if (!$mtype) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('You must select module or theme'));
            return $this->response($this->view->fetch('User/extract.tpl'));
        }
        if (!$component) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('You must enter the name of the module or theme (case sensitive)'));
            return $this->response($this->view->fetch('User/extract.tpl'));
        }
        /** @var $archive \Symfony\Component\HttpFoundation\File\UploadedFile */
        $archive = $this->request->files->get('archive', null);
        if (!$archive->isValid()) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__f('Please specify valid %s file!', 'zip/tgz'));
            $this->request->getSession()->getFlashBag()->add('error', $archive->getErrorMessage());
            return $this->response($this->view->fetch('User/extract.tpl'));
        }

        // setup
        $time = microtime();
        $pid = sha1($time);
        $path = "/tmp/{$pid}";
        // get files
        $helper = file_get_contents('modules/Gettext/Helper/xtractor.sh');
        file_put_contents('/tmp/xtractor.sh', $helper);
        `chmod 755 /tmp/xtractor.sh`;
        $helper = file_get_contents('modules/Gettext/Helper/xcompile.php');
        file_put_contents('/tmp/xcompile.php', $helper);
        $helper = file_get_contents('modules/Gettext/Helper/xcompilejs.php');
        file_put_contents('/tmp/xcompilejs.php', $helper);
        $archivePath = $archive->getRealPath();
        $command = "/tmp/xtractor.sh {$path} {$archivePath} {$component} {$domain} zip none";
        $output = '';
        exec($command, $outputArray, $result);
        foreach ($outputArray as $out) {
            $output .= "{$out}\n";
        }
        `/bin/rm -f /tmp/xtractor.sh`;
        `/bin/rm -f /tmp/xcompile.php`;
        `/bin/rm -f /tmp/xcompilejs.php`;
        `rm -rf /{$path}/{$component}`;
        $this->view->assign('result', $result);
        $this->view->assign('key', $pid);
        $this->view->assign('c', $component);
        $this->view->assign('d', $domain);
        $this->view->assign('output', $output);
        return $this->response($this->view->fetch('User/download.tpl'));
    }
    
    public function downloadAction()
    {
        // security check
        if (!SecurityUtil::checkPermission('Gettext::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $key = $this->request->query->get('key', null);
        $key = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $key);
        $c = $this->request->query->get('c', null);
        $c = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $c);
        $d = $this->request->query->get('d', null);
        $d = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $d);
        $file = "/tmp/{$key}/{$d}.zip";
        $contents = file_get_contents($file);
        $length = filesize($file);
        if ($length < 1) {
            return new RedirectResponse(System::normalizeUrl(ModUtil::url('Gettext', 'user', 'extract')));
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
    
    public function compilemoAction()
    {
        $this->view->setCaching(false);
        // security check
        if (!SecurityUtil::checkPermission('Gettext::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $submit = $this->request->request->get('submit', null);
        $forcefuzzy = $this->request->request->get('forcefuzzy', false);
        $forcefuzzy = $forcefuzzy ? 'yes' : 'no';
        if (!isset($submit)) {
            return $this->response($this->view->fetch('User/compilemo.tpl'));
        }
        /** @var $po \Symfony\Component\HttpFoundation\File\UploadedFile */
        $po = $this->request->files->get('po', null);
        if (!$po->isValid()) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__f('Please specify valid %s file!', '.po'));
            $this->request->getSession()->getFlashBag()->add('error', $po->getErrorMessage());
            return $this->response($this->view->fetch('User/compilemo.tpl'));
        }
        // get files
        $helper = file_get_contents('modules/Gettext/Helper/xcompilemo.sh');
        file_put_contents('/tmp/xcompilemo.sh', $helper);
        `chmod 755 /tmp/xcompilemo.sh`;
        // setup
        $time = microtime();
        $pid = sha1($time);
        $path = "/tmp/{$pid}";
        $poPath = $po->getRealPath();
        $command = "/tmp/xcompilemo.sh {$path} {$poPath} {$forcefuzzy}";
        $output = '';
        exec($command, $outputArray, $result);
        foreach ($outputArray as $out) {
            $output .= "{$out}\n";
        }
        `/bin/rm -f /tmp/xcompilemo.sh`;
        $this->view->assign('key', $pid);
        return $this->response($this->view->fetch('User/downloadmo.tpl'));
    }
    
    public function downloadmoAction()
    {
        // security check
        if (!SecurityUtil::checkPermission('Gettext::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $key = $this->request->query->get('key', null);
        $key = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $key);
        $file = "/tmp/{$key}/messages.mo";
        $contents = file_get_contents($file);
        $length = strlen($contents);
        if ($length < 1) {
            return new RedirectResponse(System::normalizeUrl(ModUtil::url('Gettext', 'user', 'extract')));
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
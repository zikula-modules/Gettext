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
use System;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route; // used in annotations - do not remove
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends \Zikula_AbstractController
{

    /**
     * @Route("")
     *
     * module entry point
     *
     * @return RedirectResponse
     */
    public function indexAction()
    {
        return new RedirectResponse($this->get('router')->generate('zikulagettextmodule_user_extract'));
    }

    /**
     * @Route("/extract")
     *
     * extract a .POT file from a zip
     *
     * @return false|Response
     */
    public function extractAction()
    {
        // security check
        if (!SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_READ)) {
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
        $this->view->assign('result', $result)
            ->assign('key', $pid)
            ->assign('c', $component)
            ->assign('d', $domain)
            ->assign('output', $output);
        return $this->response($this->view->fetch('User/download.tpl'));
    }

    /**
     * @Route("/download")
     *
     * download the extracted .POT files
     *
     * @return false|RedirectResponse
     */
    public function downloadAction()
    {
        // security check
        if (!SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $key = $this->request->query->get('key', null);
        $key = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $key);
        $c = $this->request->query->get('c', null);
        $c = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $c);
        $d = $this->request->query->get('d', null);
        $d = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $d);
        $file = "/tmp/{$key}/{$d}.zip";
        $length = filesize($file);
        if ($length < 1) {
            return new RedirectResponse($this->get('router')->generate('zikulagettextmodule_user_extract'));
        }
        ob_end_clean();
        ini_set('zlib.output_compression', 0);
        $response = new Response(file_get_contents($file), Response::HTTP_OK, array(
            'Cache-Control' => 'no-store, no-cache',
            'Content-Type' => 'application/x-zip',
            'Content-Length' => $length,
            'Content-Disposition' => "attachment;filename={$c}-extracted.zip",
            'Content-Description' => "Gettext POT file",
        ));
        $response->send();

        `rm -rf /tmp/{$key}`;
        exit;
    }

    /**
     * @Route("/compile")
     *
     * Compile and .MO file from a .PO file
     *
     * @return false|Response
     */
    public function compilemoAction()
    {
        $this->view->setCaching(false);
        // security check
        if (!SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_READ)) {
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

    /**
     * @Route("/downloadmo")
     *
     * Download a compiled .MO file
     *
     * @return false|string|RedirectResponse
     */
    public function downloadmoAction()
    {
        // security check
        if (!SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $key = $this->request->query->get('key', null);
        $key = preg_replace('/([^a-zA-Z0-9|^\\-|^_])/', '', $key);
        $file = "/tmp/{$key}/messages.mo";
        $length = filesize($file);
        if ($length < 1) {
            return new RedirectResponse($this->get('router')->generate('zikulagettextmodule_user_extract'));
        }
        $response = new Response(file_get_contents($file), Response::HTTP_OK, array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => $length,
            'Content-Disposition' => "attachment;filename=messages.mo",
            'Content-Description' => "Gettext mo file",
        ));
        $response->send();

        `rm -rf /tmp/{$key}`;
        exit;
    }

}
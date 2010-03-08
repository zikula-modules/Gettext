<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2009, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnversion.php 26119 2009-08-05 05:06:43Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

$domain = ZLanguage::getModuleDomain('Gettext');
$modversion['name']           = 'Gettext';
$modversion['displayname']    = __('Gettext', $domain);
//! this is the URL that will be displayed for the module
$modversion['url']            = __('gettext', $domain);
$modversion['description']    = __('Extract translation strings from themes and modules', $domain);
$modversion['version']        = '1.0';
$modversion['credits']        = '';
$modversion['help']           = '';
$modversion['changelog']      = '';
$modversion['license']        = '';
$modversion['official']       = 1;
$modversion['author']         = 'Drak';
$modversion['contact']        = 'drak@zikula.org';
$modversion['securityschema'] = array('Gettext::' => '::');

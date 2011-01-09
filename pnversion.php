<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2009, Zikula Foundation
 * @link http://www.zikula.org
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

$domain = ZLanguage::getModuleDomain('Gettext');
$modversion['name']           = 'Gettext';
$modversion['displayname']    = __('Gettext', $domain);
//! this is the URL that will be displayed for the module
$modversion['url']            = __('gettext', $domain);
$modversion['description']    = __('Extract translation strings from themes and modules', $domain);
$modversion['version']        = '1.0.1';
$modversion['contact']        = 'drak@zikula.org';
$modversion['securityschema'] = array('Gettext::' => '::');

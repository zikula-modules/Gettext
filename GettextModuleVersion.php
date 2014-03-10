<?php

/**
 * Zikula Application Framework
 *
 * @copyright (c) 2009, Zikula Foundation
 * @link http://www.zikula.org
 * @license GNU/LGPL - http://www.gnu.org/copyleft/lgpl.html
 */

namespace Zikula\GettextModule;


class GettextModuleVersion extends \Zikula_AbstractVersion
{
    public function getMetaData()
    {
        $version = array();
        $version['name'] = 'Gettext';
        $version['displayname'] = $this->__('Gettext');
        //! this is the URL that will be displayed for the module
        $version['url'] = $this->__('gettext');
        $version['description'] = $this->__('Extract translation strings from themes and modules');
        $version['version'] = '1.3.0';
        $version['core_min'] = '1.4.0';
        $version['contact'] = 'drak@zikula.org';
        $version['securityschema'] = array('Gettext::' => '::');
        return $version;
    }

}
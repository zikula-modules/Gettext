<?php

/**
 * Zikula Application Framework
 *
 * @copyright (c) 2014, Zikula Foundation
 * @link http://www.zikula.org
 * @license GNU/LGPL - http://www.gnu.org/copyleft/lgpl.html
 */

namespace Zikula\GettextModule;

class GettextModuleVersion extends \Zikula_AbstractVersion
{
    public function getMetaData()
    {
        return array(
            'oldnames' => array('Gettext'),
            'displayname' => $this->__('Gettext'),
            'url' => $this->__('gettext'),
            'description' => $this->__('Extract translation strings from extensions'),
            'version' => '1.3.0',
            'core_min' => '1.4.0',
            'securityschema' => array($this->name . '::' => '::'),
        );
    }

}
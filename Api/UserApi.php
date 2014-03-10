<?php

/**
 * Zikula Application Framework
 *
 * @copyright (c) 2014, Zikula Foundation
 * @link http://www.zikula.org
 * @license GNU/LGPL - http://www.gnu.org/copyleft/lgpl.html
 */

namespace Zikula\GettextModule\Api;

use ModUtil;

class UserApi extends \Zikula_AbstractApi
{

    /**
     * get available user panel links
     *
     * @return array array of user links
     */
    public function getlinks()
    {
        $links = array();
        $links[] = array(
            'url' => ModUtil::url($this->name, 'user', 'extract'),
            'text' => $this->__f('Extract %s from Theme or Module', '.POT'),
            'title' => $this->__f('Extract %s from Theme or Module', '.POT'),
            'icon' => 'gears');
        $links[] = array(
            'url' => ModUtil::url($this->name, 'user', 'compilemo'),
            'text' => $this->__f('Compile a %s file to %s', array('.PO', '.MO')),
            'title' => $this->__f('Compile a %s file to %s', array('.PO', '.MO')),
            'icon' => 'gears');

        return $links;
    }

}

<?php

/**
 * Zikula Application Framework
 *
 * @copyright (c) 2009, Zikula Foundation
 * @link http://www.zikula.org
 * @license GNU/LGPL - http://www.gnu.org/copyleft/lgpl.html
 */

namespace Zikula\GettextModule;


class GettextModuleInstaller extends \Zikula_AbstractInstaller
{
    public function install()
    {
        // Initialisation successful
        return true;
    }
    
    public function upgrade($oldversion)
    {
        // Update successful
        return true;
    }
    
    /**
     * @return bool true if successful, false otherwise
     */
    public function uninstall()
    {
        // Deletion fail - we dont want users disabling this module!
        return false;
    }

}
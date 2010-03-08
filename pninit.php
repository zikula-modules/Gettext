<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2002, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pninit.php 26122 2009-08-05 09:32:35Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

/**
 * initialise the settings module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 * @return bool true if successful, false otherwise
 */
function gettext_init()
{
    $domain = ZLanguage::getModuleDomain('settings');

    // Initialisation successful
    return true;
}

/**
 * @param int $oldversion version to upgrade from
 * @return bool true if successful, false otherwise
 */
function gettext_upgrade($oldversion)
{
    // Update successful
    return true;
}

/**
 * @return bool true if successful, false otherwise
 */
function gettext_delete()
{
    // Deletion fail - we dont want users disabling this module!
    return false;
}

<?php
/**
*
* @author alightner
*
* @package phpBB Calendar
* @version CVS/SVN: $Id$
* @copyright (c) 2009 alightner
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @package module_install
*/

class acp_calendar_info
{
    function module()
    {
		return array(
			'filename'	=> 'acp_calendar',
			'title'		=> 'ACP_CALENDAR',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'calsettings'	=> array('title' => 'ACP_CALENDAR_SETTINGS', 'auth' => 'acl_a_calendar', 'cat' => array('ACP_BOARD_CONFIGURATION')),
				'caletypes'		=> array('title' => 'ACP_CALENDAR_ETYPES', 'auth' => 'acl_a_calendar', 'cat' => array('ACP_BOARD_CONFIGURATION')),
			),
		);


    }
    function install()
    {
    }

    function uninstall()
    {
    }
}

?>

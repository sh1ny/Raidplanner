<?php
/**
* Powered by bbdkp © 2010 The bbDkp Project Team
* If you use this software and find it to be useful, we ask that you
* retain the copyright notice below.  While not required for free use,
* it will help build interest in the bbDkp project.
* 
* @package raidplanner.acp
* @copyright (c) 2009 bbdkp http://code.google.com/p/bbdkp/
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* $Id $
* 
*  
**/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}


/**
* @package module_install
*/

class acp_raidplanner_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_raidplanner',
			'title'		=> 'ACP_RPLAN_MAINPAGE',
			'version'	=> '0.0.1',
			'modes'		=> array(
				'config'	=> array('title' => 'RP_CONFIG',  'display' => 1, 'auth' => 'acl_a_raid_config', 'cat' => array('ACP_CAT_RPLAN')),
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

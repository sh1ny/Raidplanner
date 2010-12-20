<?php
/** 
*
* @package ucp
* @copyright (c) 2007 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/
							
/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class ucp_planner_info
{
	function module()
	{
		return array(
			'filename'	=> 'ucp_planner',
			'title'		=> 'UCP_DKP',
			'version'	=> '0.0.1',
			'modes'		=> array(
				'raidplanner_registration'	=> array('title' => 'UCP_MAIN_RAIDPLANNER_REGISTRATION', 'auth' => 'acl_u_raidplanner_signup_events', 'cat' => array('UCP_DKP')),
				'raidplanner_myevents'	=> array('title' => 'UCP_MAIN_RAIDPLANNER_MYEVENTS', 'auth' => 'acl_u_raidplanner_view_events', 'cat' => array('UCP_DKP')),
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
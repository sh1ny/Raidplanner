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
			'version'	=> '0.2.0',
			'modes'		=> array(
				'raidplanner_registration'	=> array('title' => 'UCP_MAIN_RAIDPLANNER_REGISTRATION', 'auth' => 'acl_u_raidplanner_signup_raidplans', 'cat' => array('UCP_DKP')),
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
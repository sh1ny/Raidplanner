<?php
/**
* This class manages the Raidplanner settings
*
* @author Sajaki@bbdkp.com
* @package bbDkp.acp
* @copyright (c) 2010 bbdkp http://code.google.com/p/bbdkp/
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* $Id$
*  
**/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class acp_raidplanner 
{
	var $u_action;
	function main($id, $mode) 
	{
		global $db, $user, $auth, $template, $sid, $cache;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		$user->add_lang ( array ('mods/raidplanner' ));
        switch ($mode) 
		{
			case 'rp_settings' :
    				$this->tpl_name = 'dkp/acp_' . $mode;
					$this->page_title = $user->lang ['ACP_RAIDPLANNER_SETTINGS'];
				break;
		}
	}
}

?>

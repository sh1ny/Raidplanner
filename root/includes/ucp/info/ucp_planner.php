<?php
/** 
*
* @package ucp
* @copyright (c) 2010 bbDKP 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
* @author Sajaki
* This is the user interface for the ucp planner integration
*/
			
/**
* @package ucp
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class ucp_dkp
{
	var $u_action;
					
	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $config, $phpbb_root_path, $phpEx;
		$s_hidden_fields = '';
		$submit = (isset($_POST['submit'])) ? true : false;
		
		// Attach the language file
		$user->add_lang('mods/dkp_common');
		if ($submit)
		{
			// user pressed submit
			// Verify the form key is unchanged
			if (!check_form_key('digests'))
			{
				trigger_error('FORM_INVALID');
			}
			
			switch ($mode)
			{
				case 'raidplanner_registration':
				
					
				break;
			}
			
			// Generate confirmation page. It will redirect back to the calling page
			meta_refresh(3, $this->u_action);
			$message = $user->lang['CHARACTERS_UPDATED'] . '<br /><br />' . sprintf($user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a>');
			trigger_error($message);
		}
		
		else
		{

			// Set up the page
			$this->tpl_name 	= 'dkp/ucp_dkp';
			
			// GET processing logic
			add_form_key('digests');
			switch ($mode)
			{
				// this mode is shown to users in order to select the character with which they will raid
				case 'raidplanner_myevents':
					
				$user->add_lang('mods/raidplanner');
				if ( !class_exists('displayplanner')) 
		        {
					include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_display.' . $phpEx);
				}
				$cal = new displayplanner;
				$daycount = request_var('daycount', 7 );
				$cal->display_posters_next_events_for_x_days($daycount, $user->data['user_id'] );
				$template->assign_vars(array(
						'U_COUNT_ACTION'	=> $this->u_action,
						'DAYCOUNT'			=> $daycount ));
				unset($cal);
					
				break;
						
			case 'raidplanner_registration' :
					$user->add_lang('mods/raidplanner');
				if ( !class_exists('displayplanner')) 
		        {
					include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_display.' . $phpEx);
		        }
		        $cal = new displayplanner;
				$daycount = request_var('daycount', 7 );
				$cal->display_users_next_events_for_x_days($daycount, $user->data['user_id'] );
				$template->assign_vars(array(
						'U_COUNT_ACTION'	=> $this->u_action,
						'DAYCOUNT'			=> $daycount ));
				unset($cal); 
				break;
			
			}	
		}
	}
}
?>
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

class ucp_planner
{
	var $u_action;
					
	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $config, $phpbb_root_path, $phpEx;
		define('IN_BBDKP', true);
		
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplans.' . $phpEx);
		$raidplans = new raidplans();
		
		$user->add_lang(array('mods/raidplanner', 'mods/dkp_common'));
		
	    if ( !function_exists('group_memberships') )
	    {
	        include_once($phpbb_root_path . 'includes/functions_user.'.$phpEx);
	    }
	    
	    // get the groups of which this user is part of. 
	    $groups = group_memberships(false,$user->data['user_id']);
	    $group_options = "";
	    // build the sql to get access
	    foreach ($groups as $grouprec)
	    {
			if( $group_options != "" )
			{
				$group_options .= " OR ";
			}
			$group_options .= "group_id = ".$grouprec['group_id']. " OR group_id_list LIKE '%,".$grouprec['group_id']. ",%'";
	    }  
		$subject_limit = $config['rp_display_truncated_name'];
		
		$submit = (isset($_POST['submit'])) ? true : false;
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

		// build template
		
		add_form_key('digests');
		// GET processing logic
		
		$daycount = request_var('daycount', 7 );
		$start_temp_date = time() - 86400 ;
		$sort_timestamp_cutoff = $start_temp_date + 86400*365;
		$disp_date_format = $config['rp_date_format'];
	    $disp_date_time_format = $config['rp_date_time_format'];
		
	    $calEType = request_var('calEType', 0);
		$etype_url_opts= '';
		$etype_options= "";
		if( $calEType != 0 )
		{
			$etype_url_opts = "&amp;calEType=".$calEType;
			$etype_options = " AND etype_id = ".$db->sql_escape($calEType)." ";
		}
		
		// get all raids to which poster can sign up for...
		$sql_array = array(
		    'SELECT'    => ' * ',
		
		    'FROM'      => array(
		        RP_RAIDS_TABLE => 'r',
		    ),
		
		    'WHERE'     =>  'poster_id = '. $user->data['user_id'].' 
		    	OR ( (raidplan_access_level = 2) OR  (poster_id = '. $db->sql_escape($user->data['user_id']) .' ) )
		    	OR  (raidplan_access_level = 1 AND ('.$group_options.') )  '.$etype_options.' 	
		        AND raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($sort_timestamp_cutoff) ,
		     
		    'ORDER_BY' => 'sort_timestamp ASC ', 
		);

		$sql = $db->sql_build_query('SELECT', $sql_array);		
		$result = $db->sql_query($sql);

		$template_output = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$template_output['EVENT_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts);
			$template_output['IMAGE'] = $raidplans->raid_plan_images[$row['etype_id']];
			$template_output['COLOR'] = $raidplans->raid_plan_colors[$row['etype_id']];
			$template_output['ETYPE_DISPLAY_NAME'] = $raidplans->raid_plan_displaynames[$row['etype_id']];
			$template_output['FULL_SUBJECT'] = censor_text($row['raidplan_subject']);
			$template_output['SUBJECT'] = $template_output['FULL_SUBJECT'];
			if( $subject_limit > 0 )
			{
				if(utf8_strlen($template_output['SUBJECT']) > $subject_limit)
				{
					$template_output['SUBJECT'] = truncate_string($template_output['SUBJECT'], $subject_limit) . '...';
				}
			}
			
			// who made the raid?
			$poster_url = '';
			$invite_list = '';
			 
			$raidplans->get_raidplan_invites($row, $poster_url, $invite_list );
			$template_output['POSTER'] = $poster_url;
			$template_output['INVITED'] = $invite_list;
			
			$template_output['ALL_DAY'] = 0;
			$template_output['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_time_format, true);
			
			// is recurring ?
			$template_output['IS_RECURRING'] = $row['recurr_id'];
			$string = '';
			$sql = 'SELECT * FROM ' . RP_RECURRING ."
					WHERE recurr_id ='".$db->sql_escape($row['recurr_id'])."'";
			$result2 = $db->sql_query($sql);
			
			if (!class_exists('raidplanner_display'))
			{
				include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_display.' . $phpEx);
			}
			$raidplanner_display = new raidplanner_display();	
			
			if($row2 = $db->sql_fetchrow($result))
			{
				$string = $raidplanner_display->get_recurring_raidplan_string($row2);
			}
			
			$db->sql_freeresult($result2);
			$template_output['RECURRING_TXT'] = $string;


			
			$delete_url = "";	
			$delete_all_url = "";
			$edit_url = "";
			$edit_all_url = "";
			if($user->data['is_registered'])
			{
				// can user edit ?
				if( $auth->acl_get('u_raidplanner_edit_raidplans') &&
					(($user->data['user_id'] == $row['poster_id']) || $auth->acl_get('m_raidplanner_edit_other_users_raidplans') ))
				    {
					 $edit_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=edit&amp;calEid=".$row['raidplan_id']);
					 
					 if( $row['recurr_id'] > 0 )
					 {
						$edit_all_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=edit&amp;calEditAll=1");
					 }
				}
				
				//can user delete ?
				if( $auth->acl_get('u_raidplanner_delete_raidplans') &&
					(($user->data['user_id'] == $row['poster_id'])|| $auth->acl_get('m_raidplanner_delete_other_users_raidplans') ))
				{
					$delete_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=delete&amp;calEid=". $row['raidplan_id']);
					if( $row['recurr_id'] > 0 )
					{
						$delete_all_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=delete&amp;calDelAll=1&amp;calEid=". $row['raidplan_id']."&amp;".$etype_url_opts);
					}
				}
			}
			$template_output['U_DELETE'] = $delete_url;
			$template_output['U_DELETE_ALL'] = $delete_all_url;
			$template_output['U_EDIT'] = $edit_url;
			$template_output['U_EDIT_ALL'] = $edit_all_url;
			
			$template->assign_block_vars('raids', $template_output);
			
			// get signups
			$signups = array();
			$raidplanner_display->get_signuplist($row['raidplan_id'],$signups);
			foreach($signups as $signup)
			{
				
				if( $signup['signup_val'] == 0 )
				{
					$signupcolor = '#00FF00';
					$signuptext = $user->lang['YES'];
				}
				else if( $signup['signup_val'] == 1 )
				{
					$signupcolor = '#FF0000';
					$signuptext = $user->lang['NO'];
				}
				else
				{
					$signupcolor = '#FFCC33';
					$signuptext = $user->lang['MAYBE'];
				}
						
				$signup_output['COLOR'] = $signupcolor;
				$signup_output['CHARNAME']  = $signup['member_name'];
				$signup_output['COLORCODE']  = ($signup['colorcode'] == '') ? '#123456' : $signup['colorcode'];
				$signup_output['CLASS_IMAGE'] = (strlen($signup['imagename']) > 1) ? $phpbb_root_path . "images/class_images/" . $signup['imagename'] . ".png" : '';
				$signup_output['S_CLASS_IMAGE_EXISTS'] = (strlen($signup['imagename']) > 1) ? true : false; 
				$signup_output['VALUE_TXT'] = " : " . $signuptext;
				
				$template->assign_block_vars('raids.signups', $signup_output);
				unset($signup_output);
								
			}
			unset($signups); 
			
			
		}
		$db->sql_freeresult($result);
			
		switch ($mode)
		{
			
			case 'raidplanner_myraidplans':
			$this->tpl_name 	= 'planner/ucp_planner_myraidplans';
			$template->assign_vars(array(
					'U_COUNT_ACTION'	=> $this->u_action,
					'DAYCOUNT'			=> $daycount ));
			break;
					
		case 'raidplanner_registration' :
			$this->tpl_name 	= 'planner/ucp_planner_registration';
			$template->assign_vars(array(
					'U_COUNT_ACTION'	=> $this->u_action,
					'DAYCOUNT'			=> $daycount ));
			break;
		
		}	
		
	}
}
?>
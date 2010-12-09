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
		$s_hidden_fields = '';
		
		// WHAT GROUPS IS THIS USER A PART OF ?
		$sql = 'SELECT g.group_id, g.group_name, g.group_type
		FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
		WHERE ug.user_id = '. $db->sql_escape( $user->data['user_id'] ).'
			AND g.group_id = ug.group_id
			AND ug.user_pending = 0
		ORDER BY g.group_type, g.group_name';
		$result = $db->sql_query($sql);
	
		$group_options = '';
		while ($row = $db->sql_fetchrow($result))
		{
			if( $group_options != "" )
			{
				$group_options .= " OR ";
			}
			$group_options .= "group_id = ".$row['group_id']. " OR group_id_list LIKE '%,".$row['group_id']. ",%'";
		}
		$db->sql_freeresult($result);
		
		$subject_limit = $config['rp_display_truncated_name'];
		
		$submit = (isset($_POST['submit'])) ? true : false;
		// Attach the language file
		$user->add_lang('mods/raidplanner', 'mods/dkp_common');

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
			add_form_key('digests');
			// GET processing logic
			
			$daycount = request_var('daycount', 7 );
			$start_temp_date = time();
			
			$end_temp_date = $start_temp_date + ($x * 86400);
			// find all day events that are still taking place
			$sort_timestamp_cutoff = $start_temp_date - 86400+1;
	
		    $disp_date_format = $config['rp_date_format'];
		    $disp_date_time_format = $config['rp_date_time_format'];
	
			$calEType = request_var('calEType', 0);
			if( $calEType == 0 )
			{
				$etype_options= "";
			}
			else 
			{
				$etype_options = " AND etype_id = ".$db->sql_escape($calEType)." ";
			}
			
			// don't list events that are more than 1 year in the future
			$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
					WHERE poster_id = '. $user->data['user_id'].' 
					AND ( (event_access_level = 2) OR
					(poster_id = '. $db->sql_escape($user->data['user_id']) .' ) 
					OR (event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' 
					AND ((( event_start_time >= '.$db->sql_escape($start_temp_date).' 
					AND event_start_time <= '.$db->sql_escape($end_temp_date).' ) 
					OR ( event_end_time > '.$db->sql_escape($start_temp_date).' 
					AND event_end_time <= '.$db->sql_escape($end_temp_date).' ) 
					OR ( event_start_time < '.$db->sql_escape($start_temp_date).' 
					AND event_end_time > '.$db->sql_escape($end_temp_date)." )) 
					OR (sort_timestamp > ".$db->sql_escape($sort_timestamp_cutoff)." 
					AND sort_timestamp <= ".$db->sql_escape($end_temp_date)." 
					AND event_all_day = 1) ) ORDER BY sort_timestamp ASC";
			
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$events['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=event&amp;calEid=".$row['event_id'].$etype_url_opts);
				$events['IMAGE'] = $this->available_etype_images[$row['etype_id']];
				$events['COLOR'] = $this->available_etype_colors[$row['etype_id']];
				$events['ETYPE_DISPLAY_NAME'] = $this->available_etype_display_names[$row['etype_id']];
	
				$events['FULL_SUBJECT'] = censor_text($row['event_subject']);
				$events['SUBJECT'] = $events['FULL_SUBJECT'];
				if( $subject_limit > 0 )
				{
					if(utf8_strlen($events['SUBJECT']) > $subject_limit)
					{
						$events['SUBJECT'] = truncate_string($events['SUBJECT'], $subject_limit) . '...';
					}
				}
				$events['IS_RECURRING'] = $row['recurr_id'];
				$events['RECURRING_TXT'] = $this->get_recurring_event_string_via_id( $row['recurr_id'] );
	
				$poster_url = '';
				$invite_list = '';
				$revents = new raidevents; 
				$revents->get_event_invite_list_and_poster_url($row, $poster_url, $invite_list );
				$events['POSTER'] = $poster_url;
				$events['INVITED'] = $invite_list;
				$events['ALL_DAY'] = 0;
				if( $row['event_all_day'] == 1 )
				{
					list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $row['event_day']);
					$row['event_start_time'] = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
					$row['event_end_time'] = $row['event_start_time']+86399;
					$events['ALL_DAY'] = 1;
					$events['START_TIME'] = $user->format_date($row['event_start_time'], $disp_date_format, true);
					$events['END_TIME'] = $user->format_date($row['event_end_time'], $disp_date_format, true);
				}
				else
				{
					$events['START_TIME'] = $user->format_date($row['event_start_time'], $disp_date_time_format, true);
					$events['END_TIME'] = $user->format_date($row['event_end_time'], $disp_date_time_format, true);
				}
	
				$edit_url = "";
				$edit_all_url = "";
				if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_events') &&
					(($user->data['user_id'] == $row['poster_id'])|| $auth->acl_get('m_raidplanner_edit_other_users_events') ))
				    {
					 $edit_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=edit&amp;calEid=".$row['event_id']."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
					 if( $row['recurr_id'] > 0 )
					 {
						$edit_all_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=edit&amp;calEditAll=1&amp;calEid=".$row['event_id']."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
					 }
				}
				$delete_url = "";
				$delete_all_url = "";
				if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_events') &&
					(($user->data['user_id'] == $row['poster_id'])|| $auth->acl_get('m_raidplanner_delete_other_users_events') ))
	
				{
					$delete_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=delete&amp;calEid=".$row['event_id']."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
					if( $row['recurr_id'] > 0 )
					{
						$delete_all_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=delete&amp;calDelAll=1&amp;calEid=".$row['event_id']."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
					}
				}
				$events['U_EDIT'] = $edit_url;
				$events['U_EDIT_ALL'] = $edit_all_url;
				$events['U_DELETE'] = $delete_url;
				$events['U_DELETE_ALL'] = $delete_all_url;
	
	
				$template->assign_block_vars('myevents', $events);
			}
			$db->sql_freeresult($result);
				
			switch ($mode)
			{
				// this mode is shown to users in order to select the character with which they will raid
				case 'raidplanner_myevents':
				$this->tpl_name 	= 'planner/ucp_planner_myevents';
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
}
?>
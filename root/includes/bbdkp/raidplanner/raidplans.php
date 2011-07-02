<?php
/**
*
* @author alightner
* @author Sajaki
* @package bbDKP Raidplanner
* @copyright (c) 2009 alightner
* @copyright (c) 2011 Sajaki : refactoring, adapting to bbdkp
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* 
* 
*/

/**
 * @ignore
 */
if ( !defined('IN_PHPBB') OR !defined('IN_BBDKP') )
{
	exit;
}

/*
 * raidplan functions
 */
class raidplans extends raidplanner_display 
{
	
	public function __construct()
	{
		//get parent variables
		parent::__construct();
	}
	
	/**
	 * show raidinfo in calendar template
	 *
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * @return int
	 */
	public function showraidinfo($month, $day, $year , $mode='x')
	{
		global $db, $user, $template, $config, $phpbb_root_path, $auth, $phpEx;
		
		$raidplan_output = array();
		$raidplan_counter = 0;
		if($mode=='day')
		{
			// find birthdays
			if( $auth->acl_get('u_viewprofile') )
			{
				$birthday_list = $this->generate_birthday_list( $day, $month,$year );
				if( $birthday_list != "" )
				{
					$raidplan_output['PRE_PADDING'] = "";
					$raidplan_output['PADDING'] = "96";
					$raidplan_output['DATA'] = $birthday_list;
					$raidplan_output['POST_PADDING'] = "";
					$template->assign_block_vars('raidplans', $raidplan_output);
					$raidplan_counter++;
				}
			}
			
		}
			
		//find any raidplans on this day
		$start_temp_date = gmmktime(0,0,0,$month, $day, $year)  - $user->timezone - $user->dst;
		$end_temp_date = $start_temp_date + 86399;
		$group_options = $this->group_options;
		$etype_options = $this->get_etype_filter();
		$etype_url_opts = $this->get_etype_url_opts();
			
		if( $config['rp_disp_raidplans_only_on_start'] == 0 )
		{

			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
					WHERE ( (raidplan_access_level = 2) OR
							(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
							(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
						((( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
						 ( raidplan_end_time > '.$db->sql_escape($start_temp_date).' AND raidplan_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
						 ( raidplan_start_time < '.$db->sql_escape($start_temp_date).' AND raidplan_end_time > '.$db->sql_escape($end_temp_date)." )) OR
						 ((raidplan_all_day = 1) AND (raidplan_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $month, $day, $year)) . "'))) ORDER BY raidplan_start_time ASC";
		}
		else
		{

			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
					WHERE ( (raidplan_access_level = 2) OR
							(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
							(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
						 (( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date)." ) OR
						 ((raidplan_all_day = 1) AND (raidplan_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $month, $day, $year)) . "'))) ORDER BY raidplan_start_time ASC";

		}


		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$pre_padding = 0;
			$post_padding = 0;
			$raidplan_output['PRE_PADDING'] = "";
			$raidplan_output['PADDING'] = "";
			$raidplan_output['POST_PADDING'] = "";
				
			$raidplan_output['COLOR'] = $this->raid_plan_colors[$row['etype_id']];
			$raidplan_output['IMAGE'] = $phpbb_root_path . "images/event_images/" . $this->raid_plan_images[$row['etype_id']] . ".png";
			$raidplan_output['S_EVENT_IMAGE_EXISTS'] = (strlen( $this->raid_plan_images[$row['etype_id']] ) > 1) ? true : false;
			
			$raidplan_output['EVENT_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts);
			$raidplan_output['RAID_ID'] = $row['raidplan_id'];

			$raidplan_output['INVITETIME'] = $user->format_date($row['raidplan_invite_time'], $config['rp_time_format'], true);   
			$raidplan_output['STARTTIME'] = $user->format_date($row['raidplan_start_time'], $config['rp_time_format'], true);   

			// if the raidplan was created by this user
			// display it in bold
			if( $user->data['user_id'] == $row['poster_id'] )
			{
				$raidplan_output['DISPLAY_BOLD'] = true;
			}
			else
			{
				$raidplan_output['DISPLAY_BOLD'] = false;
			}

			$raidplan_output['ETYPE_DISPLAY_NAME'] = $this->raid_plan_displaynames[$row['etype_id']];

			$raidplan_output['FULL_SUBJECT'] = censor_text($row['raidplan_subject']);
			$raidplan_output['EVENT_SUBJECT'] = $raidplan_output['FULL_SUBJECT'];
			if( $config['rp_display_truncated_name'] > 0 )
			{
				if(utf8_strlen($raidplan_output['EVENT_SUBJECT']) > $config['rp_display_truncated_name'])
				{
					$raidplan_output['EVENT_SUBJECT'] = truncate_string($raidplan_output['EVENT_SUBJECT'], $config['rp_display_truncated_name']) . '...';
				}
			}

			$raidplan_output['SHOW_TIME'] = true;
			if( $row['raidplan_all_day'] == 1 )
			{
				$raidplan_output['ALL_DAY'] = true;
			}
			else
			{
				$raidplan_output['ALL_DAY'] = false;
				$correct_format = $config['rp_time_format'];
				if( $row['raidplan_end_time'] - $row['raidplan_start_time'] > 86400 )
				{
					$correct_format = $config['rp_date_time_format'];
				}
				$raidplan_output['START_TIME'] = $user->format_date($row['raidplan_start_time'], $correct_format, true);
				$raidplan_output['END_TIME'] = $user->format_date($row['raidplan_end_time'], $correct_format, true);
				
				if($mode=='day')
				{
					if( $row['raidplan_start_time'] > $start_temp_date )
					{
						// find pre-padding value...
						$start_diff = $row['raidplan_start_time'] - $start_temp_date;
						$pre_padding = round($start_diff/900);
						if( $pre_padding > 0 )
						{
							$raidplan_output['PRE_PADDING'] = $pre_padding;
						}
					}
					if( $row['raidplan_end_time'] < $end_temp_date )
					{
						// find pre-padding value...
						$end_diff = $end_temp_date - $row['raidplan_end_time'];
						$post_padding = round($end_diff/900);
						if( $post_padding > 0 )
						{
							$raidplan_output['POST_PADDING'] = $post_padding;
						}
					}
					$raidplan_output['PADDING'] = 96 - $pre_padding - $post_padding;
				}
					
			}
			
			if($mode=='day')
			{
				$template->assign_block_vars('raidplans', $raidplan_output);	
			}
			else 
			{
				$template->assign_block_vars('calendar_days.raidplans', $raidplan_output);
			}
			
			$raidplan_counter++;
		}
		
		unset($raidplan_output);
		$db->sql_freeresult($result);
		
		return $raidplan_counter;
		
		
	}
	
	
	/* get_raidplan_data()
	**
	** Given an raidplan id, find all the data associated with the raidplan
	*/
	public function get_raidplan_data( $id, &$raidplan_data )
	{
		global $auth, $db, $user, $config;
		if( $id < 1 )
		{
			trigger_error('NO_RAIDPLAN');
		}
		$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
				WHERE raidplan_id = '.$db->sql_escape($id);
		$result = $db->sql_query($sql);
		$raidplan_data = $db->sql_fetchrow($result);
		if( !$raidplan_data )
		{
			trigger_error('NO_RAIDPLAN');
		}
	
	    $db->sql_freeresult($result);
	
		if( $raidplan_data['recurr_id'] > 0 )
		{
		    $raidplan_data['is_recurr'] = 1;
			$sql = 'SELECT * FROM ' . RP_RECURRING . '
						WHERE recurr_id = '.$db->sql_escape( $raidplan_data['recurr_id'] );
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
	    	$db->sql_freeresult($result);

	    	$raidplan_data['frequency_type'] = $row['frequency_type'];
		    $raidplan_data['frequency'] = $row['frequency'];
		    $raidplan_data['final_occ_time'] = $row['final_occ_time'];
		    $raidplan_data['week_index'] = $row['week_index'];
		    $raidplan_data['first_day_of_week'] = $row['first_day_of_week'];
		}
		else
		{
			$raidplan_data['is_recurr'] = 0;
		    $raidplan_data['frequency_type'] = 0;
		    $raidplan_data['frequency'] = 0;
		    $raidplan_data['final_occ_time'] = 0;
		    $raidplan_data['week_index'] = 0;
		    $raidplan_data['first_day_of_week'] = $config["rp_first_day_of_week"];
		}
	}
	

	/**
	 * get the the group invite list
	 *
	 * @param array $raidplan_data
	 * @param string $poster_url
	 * @param array $invite_list
	 */
	public function get_raidplan_invites($raidplan_data, &$poster_url, &$invite_list )
	{
		global $auth, $db, $user, $config;
		global $phpEx, $phpbb_root_path;
	
		$sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . '
				WHERE user_id = '.$db->sql_escape($raidplan_data['poster_id']);
		$result = $db->sql_query($sql);
		$poster_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
	
		$poster_url = get_username_string( 'full', $raidplan_data['poster_id'], $poster_data['username'], $poster_data['user_colour'] );
	
		$invite_list = "";
	
		switch( $raidplan_data['raidplan_access_level'] )
		{
			case 0:
				// personal raidplan... only raidplan creator is invited
				$invite_list = $poster_url;
				break;
			case 1:
				if( $raidplan_data['group_id'] != 0 )
				{
					// group raidplan... only phpbb accounts of this group are invited
					$sql = 'SELECT group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
							WHERE group_id = '.$db->sql_escape($raidplan_data['group_id']);
					$result = $db->sql_query($sql);
					$group_data = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
					$temp_list = (($group_data['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $group_data['group_name']] : $group_data['group_name']);
					$temp_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=group&amp;g=".$raidplan_data['group_id']);
					$temp_color_start = "";
					$temp_color_end = "";
					if( $group_data['group_colour'] !== "" )
					{
						$temp_color_start = "<span style='color:#".$group_data['group_colour']."'>";
						$temp_color_end = "</span>";
					}
					$invite_list = "<a href='".$temp_url."'>".$temp_color_start.$temp_list.$temp_color_end."</a>";
				}
				else 
				{
					// multiple groups invited	
					$group_list = explode( ',', $raidplan_data['group_id_list'] );
					$num_groups = sizeof( $group_list );
					for( $i = 0; $i < $num_groups; $i++ )
					{
						if( $group_list[$i] == "")
						{
							continue;
						}
						// group raidplan... only phpbb accounts  of specified group are invited
						$sql = 'SELECT group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
								WHERE group_id = '.$db->sql_escape($group_list[$i]);
						$result = $db->sql_query($sql);
						$group_data = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);
						$temp_list = (($group_data['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $group_data['group_name']] : $group_data['group_name']);
						$temp_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=group&amp;g=".$raidplan_data['group_id']);
						$temp_color_start = "";
						$temp_color_end = "";
						if( $group_data['group_colour'] !== "" )
						{
							$temp_color_start = "<span style='color:#".$group_data['group_colour']."'>";
							$temp_color_end = "</span>";
						}
						if( $invite_list == "" )
						{
							$invite_list = "<a href='".$temp_url."'>".$temp_color_start.$temp_list.$temp_color_end."</a>";
						}
						else
						{
							$invite_list = $invite_list . ", " . "<a href='".$temp_url."'>".$temp_color_start.$temp_list.$temp_color_end."</a>";
						}
					}
				}
				break;
			case 2:
				// public raidplan... everyone is invited
				$invite_list = $user->lang['EVERYONE'];
				break;
		}
	
	}
		
	

		
}
?>
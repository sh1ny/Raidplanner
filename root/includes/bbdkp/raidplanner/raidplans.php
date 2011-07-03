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

// Include the base class
if (!class_exists('raidplanner_display'))
{
	require($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_display.' . $phpEx);
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
					// place birthday in the middle
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
		$etype_url_opts = $this->get_etype_url_opts();
		
		// filter on event type ?
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			$etype_options =  "";
		}
		else 
		{
			$etype_options = " AND etype_id = ".$db->sql_escape($calEType)." ";
		}
		
		// build sql 
		$sql_array = array(
   			'SELECT'    => 'r.*', 
			'FROM'		=> array(RP_RAIDS_TABLE => 'r'), 
			'WHERE'		=>  ' ( (raidplan_access_level = 2) OR (poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR (raidplan_access_level = 1 AND ('.$group_options.')) )  
							   ' .  $etype_options . '
							  AND ((raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date). " ) 
							  OR ((raidplan_all_day = 1) AND (raidplan_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $month, $day, $year)) . "')) ) ",
			'ORDER_BY'	=> 'r.raidplan_start_time ASC');
		$sql = $db->sql_build_query('SELECT', $sql_array);
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

			$correct_format = $config['rp_time_format'];
			if( $row['raidplan_end_time'] - $row['raidplan_start_time'] > 86400 )
			{
				$correct_format = $config['rp_date_time_format'];
			}
				
			$raidplan_output['INVITE_TIME'] = $user->format_date($row['raidplan_invite_time'], $correct_format, true);   
			$raidplan_output['START_TIME'] = $user->format_date($row['raidplan_start_time'], $correct_format, true);   
			$raidplan_output['END_TIME'] = $user->format_date($row['raidplan_end_time'], $correct_format, true);
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
			
			// if there is no endtime then switch alldaymode on
			if( $row['raidplan_all_day'] == 1 )
			{
				$raidplan_output['ALL_DAY'] = true;
			}
			else
			{
				$raidplan_output['ALL_DAY'] = false;
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

			switch ($mode)
			{
				case "day":
					$raidplan_output['SHOW_TIME'] = true;
					
					/* sets the colspan width */
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
					
					$template->assign_block_vars('raidplans', $raidplan_output);	
					
					break;
				case "month":
				case "week":
					// dont show time on calendar
					$raidplan_output['SHOW_TIME'] = false;
					$template->assign_block_vars('calendar_days.raidplans', $raidplan_output);
					break;
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
	
	/**
	 * handles signing up to a raid (called from display_plannedraid)
	 *
	 * @param array $raidplan_data
	 * @param array $signup_data
	 */
	public function signup(&$raidplan_data, $signup_data)
	{
		global $user, $db, $config;
			
		// get the chosen raidrole 1-6, this changes the signup value
		$newrole_id = request_var('signuprole', 0);
		// get the attendance value
		$new_signup_val	= request_var('signup_val', 2);
		
		$uid = $bitfield = $options = '';
		$allow_bbcode = $allow_urls = $allow_smilies = true;
		generate_text_for_storage($new_signup_detail, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
		
		// get the chosen raidchar
		$signup_data['dkpmember_id'] = request_var('signupchar', 0);
		// update the ip address and time
		$signup_data['poster_ip'] = $user->ip;
		$signup_data['post_time'] = time();
		$signup_data['signup_count'] =  request_var('signup_count', 1);
		$signup_data['signup_detail'] = utf8_normalize_nfc( request_var('signup_detail', '', true) );
		
		$delta_yes_count = 0;
		$delta_no_count = 0;
		$delta_maybe_count = 0;
		
		// identify the signup. if user returns to signup screen he can change
		$signup_id = request_var('hidden_signup_id', 0);
		
		if ($signup_id ==0)
		{
			//doublecheck in database
			$signup_id = $this->check_if_subscribed($signup_data['poster_id'],$signup_data['dkpmember_id'], $signup_data['raidplan_id']);
		}
			
		// save the user's signup data...
		if( $signup_id > 0)
		{
			
			//get old role
			$old_role_id = (int) $signup_data['role_id'];
			$signup_data['role_id'] = $newrole_id;
			$sql = " select role_signedup from " . RP_RAIDPLAN_ROLES . " where role_id = " . 
			$old_role_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$result = $db->sql_query($sql);
			$db->sql_query($sql);
			$role_signedup = (int) $db->sql_fetchfield('role_signedup',0,$result);  
			$role_signedup = max(0, $role_signedup - 1);
			$db->sql_freeresult ( $result );
			// decrease old role
			$sql = " update " . RP_RAIDPLAN_ROLES . ' set role_signedup = ' . $role_signedup . ' where role_id = ' . 
			$old_role_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$db->sql_query($sql);
			
			// increase new role
			$sql = " update " . RP_RAIDPLAN_ROLES . " set role_signedup = (role_signedup  + 1) where role_id = " . 
			$newrole_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$db->sql_query($sql);
			
			// fetch existing signup value
			if ($signup_data['signup_val'] != $new_signup_val)
			{
				// new role selected 
				
				// decrease the current yes-no-maybe stat
				$old_signup_val = $signup_data['signup_val'];
				$signup_data['signup_val'] = $new_signup_val;
				
				switch($old_signup_val)
				{
					case 0:
						$delta_yes_count -= 1;
						break;
					case 1:
						$delta_no_count -= 1;
						break;
					case 2:
						$delta_maybe_count -= 1;
						break;
				}
				
				// NEW Signup
				switch($new_signup_val)
				{
					case 0:
						$delta_yes_count += 1;
						break;
					case 1:
						$delta_no_count += 1;
						break;
					case 2:
						$delta_maybe_count += 1;
						break;
				}

			}
			
			$sql = 'UPDATE ' . RP_SIGNUPS . '
				SET ' . $db->sql_build_array('UPDATE', array(
					'poster_id'			=> (int) $signup_data['poster_id'],
					'poster_name'		=> (string) $signup_data['poster_name'],
					'poster_colour'		=> (string) $signup_data['poster_colour'],
					'poster_ip'			=> (string) $signup_data['poster_ip'],
					'post_time'			=> (int) $signup_data['post_time'],
					'signup_val'		=> (int) $signup_data['signup_val'],
					'signup_count'		=> (int) $signup_data['signup_count'],
					'signup_detail'		=> (string) $signup_data['signup_detail'],
					'dkpmember_id'		=> $signup_data['dkpmember_id'], 
					'role_id'			=> (int) $newrole_id,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_uid'		=> $uid,
					'bbcode_options'	=> $options,
					)) . "
				WHERE signup_id = $signup_id";
			$db->sql_query($sql);
		}
		else
		{
			//NEW SIGNUP
			$sql = " update " . RP_RAIDPLAN_ROLES . " set role_signedup = (role_signedup  + 1) where role_id = " . 
			$newrole_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$db->sql_query($sql);
				
			switch($new_signup_val)
			{
				case 0:
					$delta_yes_count += 1;
					break;
				case 1:
					$delta_no_count += 1;
					break;
				case 2:
					$delta_maybe_count += 1;
					break;
			}
			
			$signup_data['signup_val'] = $new_signup_val;
			$signup_data['role_id'] = $newrole_id;
			
			$sql = 'INSERT INTO ' . RP_SIGNUPS . ' ' . $db->sql_build_array('INSERT', array(
					'raidplan_id'		=> (int) $signup_data['raidplan_id'],
					'poster_id'			=> (int) $signup_data['poster_id'],
					'poster_name'		=> (string) $signup_data['poster_name'],
					'poster_colour'		=> (string) $signup_data['poster_colour'],
					'poster_ip'			=> (string) $signup_data['poster_ip'],
					'post_time'			=> (int) $signup_data['post_time'],
					'signup_val'		=> (int) $signup_data['signup_val'],
					'signup_count'		=> (int) $signup_data['signup_count'],
					'signup_detail'		=> (string) $signup_data['signup_detail'],
					'dkpmember_id'		=> $signup_data['dkpmember_id'], 
					'role_id'			=> $newrole_id,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_uid'		=> $uid,
					'bbcode_options'	=> $options,
					)
				);
			$db->sql_query($sql);
			
			$signup_id = $db->sql_nextid();
			$signup_data['signup_id'] = $signup_id;
		}
		
		// update the raidplan id's signup stats
		$sql = 'UPDATE ' . RP_RAIDS_TABLE . ' SET signup_yes = signup_yes + ' . (int) $delta_yes_count . ', signup_no = signup_no + ' . 
			(int) $delta_no_count . ', signup_maybe = signup_maybe + ' . (int) $delta_maybe_count . '
		WHERE raidplan_id = ' . (int) $signup_data['raidplan_id'];
		$db->sql_query($sql);
		
		$raidplan_data['signup_yes'] = $raidplan_data['signup_yes'] + $delta_yes_count;
		$raidplan_data['signup_no'] = $raidplan_data['signup_no'] + $delta_no_count;
		$raidplan_data['signup_maybe'] = $raidplan_data['signup_maybe'] + $delta_maybe_count;
		
		$this->calendar_add_or_update_reply( $signup_data['raidplan_id'] );
		
	}
	
	
	
		
	

		
}
?>
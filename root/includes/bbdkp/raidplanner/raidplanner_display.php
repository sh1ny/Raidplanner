<?php
/**
*
* @author alightner, Sajaki
* @package bbDKP Raidplanner
* @version CVS/SVN: $Id: raidplanner_display.php 1761 2010-11-23 01:27:59Z sajaki9 $
* @copyright (c) 2009 alightner
* @copyright (c) 2010 Sajaki : refactoring, adapting to bbdkp
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* 
* 
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

// Include the base class
if (!class_exists('raidplanner_base'))
{
	require($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_base.' . $phpEx);
}

class displayplanner extends raidplanner_base 
{
	public function displaymonth()
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
		$etype_url_opts = $this->get_etype_url_opts();
		
		$this->_init_calendar_data();
		$this->_init_view_selection_code("month");
		//create next and prev links
		$this->set_date_prev_next( "month" );
		$prev_link = append_sid("{$phpbb_root_path}planner.$phpEx", "calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year'].$etype_url_opts);
		$next_link = append_sid("{$phpbb_root_path}planner.$phpEx", "calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year'].$etype_url_opts);
	
		//find the first day of the week
		$first_day_of_week = $config['rp_first_day_of_week'];
		$this->get_weekday_names( $first_day_of_week, $sunday, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday );
	
		//get the first day of the month
		$this->date['num'] = "01";
		$this->date['fday'] = $this->get_fday( $this->date['num'], $this->date['month_no'], $this->date['year'], $first_day_of_week );
	
		$number_days = gmdate("t", gmmktime( 0,0,0,$this->date['month_no'], $this->date['day'], $this->date['year']));
	
		$calendar_header_txt = $user->lang['MONTH_OF'] . sprintf($user->lang['LOCAL_DATE_FORMAT'], $user->lang['datetime'][$this->date['month']], $this->date['day'], $this->date['year'] );
		$subject_limit = $config['rp_display_truncated_name'];
	
		// Is the user able to view ANY raidplans?
		$user_can_view_raidplans = false;
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			$user_can_view_raidplans = true;
	
			/* find the group options here so we do not have to look them up again for each day */
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
		}
		$disp_raidplans_only_on_start = $config['rp_disp_raidplans_only_on_start'];
	
		$counter = 0;
		for ($j = 1; $j < $number_days+1; $j++, $counter++)
		{
			// if it is the first week
			if ($j == 1)
			{
				// find how many place holders we need before day 1
				if ($this->date['fday'] < 7)
				{
					$this->date['fday'] = $this->date['fday']+1;
					for ($i = 1; $i < $this->date['fday']; $i++, $counter++)
					{
						// create dummy days (place holders)
						if( $i == 1 )
						{
							$calendar_days['START_WEEK'] = true;
						}
						else
						{
							$calendar_days['START_WEEK'] = false;
						}
						$calendar_days['END_WEEK'] = false;
						$calendar_days['HEADER_CLASS'] = '';
						$calendar_days['DAY_CLASS'] = '';
						$calendar_days['NUMBER'] = 0;
						$calendar_days['DUMMY_DAY'] = true;
						$calendar_days['ADD_LINK'] = '';
						$calendar_days['BIRTHDAYS'] = '';
						$template->assign_block_vars('calendar_days', $calendar_days);
					}
				}
			}
			// start creating the data for the real days
			$calendar_days['START_WEEK'] = false;
			$calendar_days['END_WEEK'] = false;
			$calendar_days['DUMMY_DAY'] = false;
			$calendar_days['HEADER_CLASS'] = '';
			$calendar_days['DAY_CLASS'] = '';
			$calendar_days['NUMBER'] = 0;
			$calendar_days['ADD_LINK'] = '';
			$calendar_days['BIRTHDAYS'] = '';
	
			if($counter % 7 == 0)
			{
				$calendar_days['START_WEEK'] = true;
			}
			if($counter % 7 == 6 )
			{
				$calendar_days['END_WEEK'] = true;
			}
			$calendar_days['NUMBER'] = $j;
			
			if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans') )
			{
				$calendar_days['ADD_LINK'] = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=post&amp;calD=".$j."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']. $etype_url_opts);
			}
			$calendar_days['DAY_VIEW_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=day&amp;calD=".$j."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			$calendar_days['WEEK_VIEW_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$j."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
	
			//highlight selected day
			if( $j == $this->date['day'] )
			{
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
	
			//highlight current day
			$test_start_hi_time = mktime( 0,0,0,$this->date['month_no'], $j, $this->date['year']) + date('Z');
			$test_end_hi_time = $test_start_hi_time + 86399;
			$test_hi_time = time() + $user->timezone + $user->dst;
	
			if( ($test_start_hi_time <= $test_hi_time) &&
			    ($test_end_hi_time >= $test_hi_time))
			{
				$calendar_days['HEADER_CLASS'] = 'highlight';
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
	
			if ( $user_can_view_raidplans && $auth->acl_get('u_viewprofile') )
			{
				// find birthdays
				$calendar_days['BIRTHDAYS'] = $this->generate_birthday_list( $j, $this->date['month_no'], $this->date['year'] );
			}
	
			$template->assign_block_vars('calendar_days', $calendar_days);
	
			if ( $user_can_view_raidplans )
			{
				//find any raidplans on this day
				$start_temp_date = gmmktime(0,0,0,$this->date['month_no'], $j, $this->date['year'])  - $user->timezone - $user->dst;
				$end_temp_date = $start_temp_date + 86399;
	
				if( $disp_raidplans_only_on_start == 0 )
				{
	
					$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
							WHERE ( (raidplan_access_level = 2) OR
								(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
								(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
							((( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
							 ( raidplan_end_time > '.$db->sql_escape($start_temp_date).' AND raidplan_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
							 ( raidplan_start_time < '.$db->sql_escape($start_temp_date).' AND raidplan_end_time > '.$db->sql_escape($end_temp_date)." )) OR
							 ((raidplan_all_day = 1) AND (raidplan_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $j, $this->date['month_no'], $this->date['year'])) . "'))) ORDER BY raidplan_start_time ASC";
				}
				else
				{
	
					$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
							WHERE ( (raidplan_access_level = 2) OR
								(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
								(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
								(( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date)." ) OR
							 	((raidplan_all_day = 1) AND (raidplan_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $j, $this->date['month_no'], $this->date['year'])) . "'))) ORDER BY raidplan_start_time ASC";
	
				}
	
	
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$raidplan_output['COLOR'] = $this->raid_plan_colors[$row['etype_id']];
					$raidplan_output['IMAGE'] = $this->raid_plan_images[$row['etype_id']];
					$raidplan_output['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts);
	
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
					if( $subject_limit > 0 )
					{
						if(utf8_strlen($raidplan_output['EVENT_SUBJECT']) > $subject_limit)
						{
							$raidplan_output['EVENT_SUBJECT'] = truncate_string($raidplan_output['EVENT_SUBJECT'], $subject_limit) . '...';
						}
					}
					$template->assign_block_vars('calendar_days.raidplans', $raidplan_output);
				}
				$db->sql_freeresult($result);
			}
	
		}
		$counter--;
		$dummy_end_day_count = 6 - ($counter % 7);
		for ($i = 1; $i <= $dummy_end_day_count; $i++)
		{
			// create dummy days (place holders)
			$calendar_days['START_WEEK'] = false;
			if( $i == $dummy_end_day_count )
			{
				$calendar_days['END_WEEK'] = true;
			}
			else
			{
				$calendar_days['END_WEEK'] = false;
			}
			$calendar_days['HEADER_CLASS'] = '';
			$calendar_days['DAY_CLASS'] = '';
			$calendar_days['NUMBER'] = 0;
			$calendar_days['DUMMY_DAY'] = true;
			$calendar_days['ADD_LINK'] = '';
			$calendar_days['BIRTHDAYS'] = '';
			$template->assign_block_vars('calendar_days', $calendar_days);
		}
	
		$template->assign_vars(array(
			'CALENDAR_HEADER'	=> $calendar_header_txt,
			'DAY_IMG'			=> $user->img('button_calendar_day', 'DAY'),
			'WEEK_IMG'			=> $user->img('button_calendar_week', 'WEEK'),
			'CALENDAR_PREV'		=> $prev_link,
			'CALENDAR_NEXT'		=> $next_link,
			'CALENDAR_VIEW_OPTIONS' => $this->mode_sel_code.' '.$this->month_sel_code.' '.$this->day_sel_code.' '.$this->year_sel_code,
			'SUNDAY'			=> $sunday,
			'MONDAY'			=> $monday,
			'TUESDAY'			=> $tuesday,
			'WEDNESDAY'			=> $wednesday,
			'THURSDAY'			=> $thursday,
			'FRIDAY'			=> $friday,
			'SATURDAY'			=> $saturday,
			'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}planner.$phpEx", $this->get_etype_post_opts() ),
		));
	}
	
	/* main function to display an individual week in the calendar */
	public function display_week( $index_display )
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
	
		$this->_init_calendar_data();
		$this->_init_view_selection_code("week");
		$index_display_var = request_var('indexWk', 0);
	
		$etype_url_opts = $this->get_etype_url_opts();
	
		// create next and prev links
		$this->set_date_prev_next( "week" );
		$prev_link = "";
		$next_link = "";
	
		//find the first day of the week
		if( $index_display == 0 && $index_display_var == 0)
		{
			$first_day_of_week = $config['rp_first_day_of_week'];
			$prev_link = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$this->date['prev_day']."&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year'].$etype_url_opts);
			$next_link = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$this->date['next_day']."&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year'].$etype_url_opts);
		}
		else
		{
			/* get current weekday so we show this upcoming week's raidplans */
			$temp_date = time() + $user->timezone + $user->dst;
			$first_day_of_week = gmdate("w", $temp_date);
	
			$prev_link = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$this->date['prev_day']."&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year']."&amp;indexWk=1".$etype_url_opts);
			$next_link = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$this->date['next_day']."&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year']."&amp;indexWk=1".$etype_url_opts);
		}
		$this->get_weekday_names( $first_day_of_week, $sunday, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday );
	
		$this->date['fday'] = $this->get_fday($this->date['day'], $this->date['month_no'], $this->date['year'], $first_day_of_week);
	
		$number_days = 7;
		$calendar_header_txt = $user->lang['WEEK_OF'] . sprintf($user->lang['LOCAL_DATE_FORMAT'], $user->lang['datetime'][$this->date['month']], $this->date['day'], $this->date['year'] );
		$subject_limit = $config['rp_display_truncated_name'];
	
		$counter = 0;
		$j_start = $this->date['day'];
		if( $this->date['fday']<7 )
		{
			$j_start = $this->date['day']-$this->date['fday'];
		}
		$prev_month_no = $this->date['month_no'] - 1;
		$prev_year_no = $this->date['year'];
		if( $prev_month_no == 0 )
		{
			$prev_month_no = 12;
			$prev_year_no--;
		}
		$prev_month_day_count = date("t",mktime( 0,0,0,$prev_month_no, 25, $prev_year_no));
		// how many days are in this month?
		$month_day_count = date("t",mktime(0,0,0,$this->date['month_no'], 25, $this->date['year']));
		$next_month_no = $this->date['month_no'] + 1;
		$next_year_no = $this->date['year'];
		if( $next_month_no == 13 )
		{
			$next_month_no = 1;
			$next_year_no++;
		}
	
	
		// Is the user able to view ANY raidplans?
		$user_can_view_raidplans = false;
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			$user_can_view_raidplans = true;
	
			/* find the group options here so we do not have to look them up again for each day */
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
		}
	
		$disp_raidplans_only_on_start = $config['rp_disp_raidplans_only_on_start'];
		$disp_time_format = $config['rp_time_format'];
		$disp_date_time_format = $config['rp_date_time_format'];
	
		for ($j = $j_start; $j < $j_start+7; $j++, $counter++)
		{
			if( $j < 1 )
			{
				$true_j = $prev_month_day_count + $j;
				$true_m = $prev_month_no;
				$true_y = $prev_year_no;
			}
			else if ($j > $month_day_count )
			{
				$true_j = $j - $month_day_count;
				$true_m = $next_month_no;
				$true_y = $next_year_no;
			}
			else
			{
				$true_j = $j;
				$true_m = $this->date['month_no'];
				$true_y = $this->date['year'];
			}
	
			// start creating the data for the real days
			$calendar_days['START_WEEK'] = false;
			$calendar_days['END_WEEK'] = false;
			$calendar_days['DUMMY_DAY'] = false;
			$calendar_days['HEADER_CLASS'] = '';
			$calendar_days['DAY_CLASS'] = '';
			$calendar_days['NUMBER'] = 0;
			$calendar_days['ADD_LINK'] = '';
			$calendar_days['BIRTHDAYS'] = '';
	
			if($counter % 7 == 0)
			{
				$calendar_days['START_WEEK'] = true;
			}
			if($counter % 7 == 6 )
			{
				$calendar_days['END_WEEK'] = true;
			}
			$calendar_days['NUMBER'] = $true_j;
			//if( $auth->acl_get('u_raidplanner_create_raidplans') )
			if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans') )
			{
				$calendar_days['ADD_LINK'] = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=post&amp;calD=".$true_j."&amp;calM=".$true_m."&amp;calY=".$true_y.$etype_url_opts);
			}
			$calendar_days['DAY_VIEW_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=day&amp;calD=".$true_j."&amp;calM=".$true_m."&amp;calY=".$true_y.$etype_url_opts);
			$calendar_days['MONTH_VIEW_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=month&amp;calD=".$true_j."&amp;calM=".$true_m."&amp;calY=".$true_y.$etype_url_opts);
	
			if( ($true_j == $this->date['day']) &&
			    ($true_m == $this->date['month_no']) &&
			    ($true_y == $this->date['year']) )
			{
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
	
			//highlight current day
			$test_start_hi_time = mktime( 0,0,0,$true_m, $true_j, $true_y) + date('Z');
			$test_end_hi_time = $test_start_hi_time + 86399;
			$test_hi_time = time() + $user->timezone + $user->dst;
	
			if( ($test_start_hi_time <= $test_hi_time) &&
			    ($test_end_hi_time >= $test_hi_time))
			{
				$calendar_days['HEADER_CLASS'] = 'highlight';
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
			if ( $user_can_view_raidplans && $auth->acl_get('u_viewprofile') )
			{
				// find birthdays
				$calendar_days['BIRTHDAYS'] = $this->generate_birthday_list( $true_j, $true_m, $true_y );
			}
	
			$template->assign_block_vars('calendar_days', $calendar_days);
	
			if ( $user_can_view_raidplans )
			{
				//find any raidplans on this day
				$start_temp_date = gmmktime(0,0,0,$true_m, $true_j, $true_y)  - $user->timezone - $user->dst;
	
				$end_temp_date = $start_temp_date + 86399;
	
				if( $disp_raidplans_only_on_start == 0 )
				{
					$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
							WHERE ( (raidplan_access_level = 2) OR
									(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
									(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
								((( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
								 ( raidplan_end_time > '.$db->sql_escape($start_temp_date).' AND raidplan_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
								 ( raidplan_start_time < '.$db->sql_escape($start_temp_date).' AND raidplan_end_time > '.$db->sql_escape($end_temp_date)." )) OR
								 ((raidplan_all_day = 1) AND (raidplan_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $true_j, $true_m, $true_y)) . "'))) ORDER BY raidplan_start_time ASC";
				}
				else
				{
	
					$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
							WHERE ( (raidplan_access_level = 2) OR
									(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
									(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
								 (( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date)." ) OR
								 ((raidplan_all_day = 1) AND (raidplan_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $true_j, $true_m, $true_y)) . "'))) ORDER BY raidplan_start_time ASC";
	
				}
	
	
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$raidplan_output['COLOR'] = $this->raid_plan_colors[$row['etype_id']];
					$raidplan_output['IMAGE'] = $this->raid_plan_images[$row['etype_id']];
					$raidplan_output['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts);
	
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
					if( $subject_limit > 0 )
					{
						if(utf8_strlen($raidplan_output['EVENT_SUBJECT']) > $subject_limit)
						{
							$raidplan_output['EVENT_SUBJECT'] = truncate_string($raidplan_output['EVENT_SUBJECT'], $subject_limit) . '...';
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
						$correct_format = $disp_time_format;
						if( $row['raidplan_end_time'] - $row['raidplan_start_time'] > 86400 )
						{
							$correct_format = $disp_date_time_format;
						}
						$raidplan_output['START_TIME'] = $user->format_date($row['raidplan_start_time'], $correct_format, true);
						$raidplan_output['END_TIME'] = $user->format_date($row['raidplan_end_time'], $correct_format, true);
					}
	
					$template->assign_block_vars('calendar_days.raidplans', $raidplan_output);
				}
				$db->sql_freeresult($result);
			}
	
		}
	
	
		// A typical usage for sending your variables to your template.
		$template->assign_vars(array(
				'CALENDAR_HEADER'	=> $calendar_header_txt,
				'DAY_IMG'			=> $user->img('button_calendar_day', 'DAY'),
				'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
				'CALENDAR_PREV'		=> $prev_link,
				'CALENDAR_NEXT'		=> $next_link,
				'CALENDAR_VIEW_OPTIONS' => $this->mode_sel_code.' '.$this->month_sel_code.' '.$this->day_sel_code.' '.$this->year_sel_code,
				'SUNDAY'			=> $sunday,
				'MONDAY'			=> $monday,
				'TUESDAY'			=> $tuesday,
				'WEDNESDAY'			=> $wednesday,
				'THURSDAY'			=> $thursday,
				'FRIDAY'			=> $friday,
				'SATURDAY'			=> $saturday,
				'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}planner.$phpEx", $this->get_etype_post_opts() ),
		));
	
	}
		
	/* main function to display an individual day in the calendar */
	public function display_day()
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
		$this->_init_calendar_data();
		$this->_init_view_selection_code("day");
		$etype_url_opts = $this->get_etype_url_opts();
	
		// create next and prev links
		$this->set_date_prev_next( "day" );
		$prev_link = append_sid("{$phpbb_root_path}planner.$phpEx", "view=day&amp;calD=".$this->date['prev_day']."&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year'].$etype_url_opts);
		$next_link = append_sid("{$phpbb_root_path}planner.$phpEx", "view=day&amp;calD=".$this->date['next_day']."&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year'].$etype_url_opts);
	
		$calendar_header_txt = $user->lang['DAY_OF'] . sprintf($user->lang['LOCAL_DATE_FORMAT'], $user->lang['datetime'][$this->date['month']], $this->date['day'], $this->date['year'] );
		$subject_limit = $config['rp_display_truncated_name'];
	
		$hour_mode = $config['rp_hour_mode'];
		if( $hour_mode == 12 )
		{
			for( $i = 0; $i < 24; $i++ )
			{
				$time_header['TIME'] = $i % 12;
				if( $time_header['TIME'] == 0 )
				{
					$time_header['TIME'] = 12;
				}
				$time_header['AM_PM'] = $user->lang['PM'];
				if( $i < 12 )
				{
					$time_header['AM_PM'] = $user->lang['AM'];
				}
				$template->assign_block_vars('time_headers', $time_header);
			}
		}
		else
		{
			for( $i = 0; $i < 24; $i++ )
			{
				$o = "";
				if($i < 10 )
				{
					$o="0";
				}
				$time_header['TIME'] = $o . $i;
				$time_header['AM_PM'] = "";
				$template->assign_block_vars('time_headers', $time_header);
			}
		}
	
		//$disp_raidplans_only_on_start = $config['rp_disp_raidplans_only_on_start", 0);
		// the day view is a graphical layout... we probably want to ignore the "display only on start rule" here
		$disp_raidplans_only_on_start = 0;
		$disp_time_format = $config['rp_time_format']; 
		$disp_date_time_format = $config['rp_date_time_format'];
	
	    $raidplan_counter = 0;
		// Is the user able to view ANY raidplans?
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			// find birthdays
			if( $auth->acl_get('u_viewprofile') )
			{
				$birthday_list = $this->generate_birthday_list( $this->date['day'], $this->date['month_no'], $this->date['year'] );
				if( $birthday_list != "" )
				{
					$raidplans['PRE_PADDING'] = "";
					$raidplans['PADDING'] = "96";
					$raidplans['DATA'] = $birthday_list;
					$raidplans['POST_PADDING'] = "";
					$template->assign_block_vars('raidplans', $raidplans);
					$raidplan_counter++;
				}
			}
	
	
			//find any raidplans on this day
			$start_temp_date = gmmktime(0,0,0,$this->date['month_no'], $this->date['day'], $this->date['year'])  - $user->timezone - $user->dst;
			$end_temp_date = $start_temp_date + 86399;
	
	
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
			if( $disp_raidplans_only_on_start == 0 )
			{
				$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
						WHERE ( (raidplan_access_level = 2) OR
								(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
								(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
						((( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
						 ( raidplan_end_time > '.$db->sql_escape($start_temp_date).' AND raidplan_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
						 ( raidplan_start_time < '.$db->sql_escape($start_temp_date).' AND raidplan_end_time > '.$db->sql_escape($end_temp_date)." )) OR
						 ((raidplan_all_day = 1) AND (raidplan_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $this->date['day'], $this->date['month_no'], $this->date['year'])) . "'))) ORDER BY raidplan_start_time ASC";
			}
			else
			{
				$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
						WHERE ( (raidplan_access_level = 2) OR
								(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
								(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
						(( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date)." ) OR
						 ((raidplan_all_day = 1) AND (raidplan_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $this->date['day'], $this->date['month_no'], $this->date['year'])) . "'))) ORDER BY raidplan_start_time ASC";
	
			}
	
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$pre_padding = 0;
				$padding = 0;
				$post_padding = 0;
				$raidplans['PRE_PADDING'] = "";
				$raidplans['PADDING'] = "";
				$raidplans['POST_PADDING'] = "";
				$raidplans['COLOR'] = $this->raid_plan_colors[$row['etype_id']];
				$raidplans['IMAGE'] = $this->raid_plan_images[$row['etype_id']];
				$raidplans['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$row['raidplan_id']. $etype_url_opts);
				// if the raidplan was created by this user
				// display it in bold
				if( $user->data['user_id'] == $row['poster_id'] )
				{
					$raidplans['DISPLAY_BOLD'] = true;
				}
				else
				{
					$raidplans['DISPLAY_BOLD'] = false;
				}
	
				$raidplans['ETYPE_DISPLAY_NAME'] = $this->raid_plan_displaynames[$row['etype_id']];
	
				$raidplans['FULL_SUBJECT'] = censor_text($row['raidplan_subject']);
				$raidplans['EVENT_SUBJECT'] = $raidplans['FULL_SUBJECT'];
				if( $subject_limit > 0 )
				{
					if(utf8_strlen($raidplans['EVENT_SUBJECT']) > $subject_limit)
					{
						$raidplans['EVENT_SUBJECT'] = truncate_string($raidplans['EVENT_SUBJECT'], $subject_limit) . '...';
					}
				}
	
				if( $row['raidplan_all_day'] == 1 )
				{
					$raidplans['ALL_DAY'] = true;
					$raidplans['PADDING'] = "96";
				}
				else
				{
					$raidplans['ALL_DAY'] = false;
					$correct_format = $disp_time_format;
					if( $row['raidplan_end_time'] - $row['raidplan_start_time'] > 86400 )
					{
						$correct_format = $this->disp_date_time_format;
					}
					$raidplans['START_TIME'] = $user->format_date($row['raidplan_start_time'], $correct_format, true);
					$raidplans['END_TIME'] = $user->format_date($row['raidplan_end_time'], $correct_format, true);
	
	
					if( $row['raidplan_start_time'] > $start_temp_date )
					{
						// find pre-padding value...
						$start_diff = $row['raidplan_start_time'] - $start_temp_date;
						$pre_padding = round($start_diff/900);
						if( $pre_padding > 0 )
						{
							$raidplans['PRE_PADDING'] = $pre_padding;
						}
					}
					if( $row['raidplan_end_time'] < $end_temp_date )
					{
						// find pre-padding value...
						$end_diff = $end_temp_date - $row['raidplan_end_time'];
						$post_padding = round($end_diff/900);
						if( $post_padding > 0 )
						{
							$raidplans['POST_PADDING'] = $post_padding;
						}
					}
					$raidplans['PADDING'] = 96 - $pre_padding - $post_padding;
	
				}
				$template->assign_block_vars('raidplans', $raidplans);
				$raidplan_counter++;
			}
			$db->sql_freeresult($result);
		}
	
		$week_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		$month_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=month&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		$add_raidplan_url = "";
	
		if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans') )
		{
			$add_raidplan_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=post&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		}
	
		// A typical usage for sending your variables to your template.
		$template->assign_vars(array(
			'CALENDAR_HEADER'	=> $calendar_header_txt,
			'WEEK_IMG'			=> $user->img('button_calendar_week', 'WEEK'),
			'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
			'ADD_LINK'			=> $add_raidplan_url,
			'WEEK_VIEW_URL'		=> $week_view_url,
			'MONTH_VIEW_URL'	=> $month_view_url,
			'CALENDAR_PREV'		=> $prev_link,
			'CALENDAR_NEXT'		=> $next_link,
			'CALENDAR_VIEW_OPTIONS' => $this->mode_sel_code.' '.$this->month_sel_code.' '.$this->day_sel_code.' '.$this->year_sel_code,
			'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}planner.$phpEx", $this->get_etype_post_opts() ),
			'EVENT_COUNT'		=> $raidplan_counter,
		));

	}
	
	
	/**
	 * displays a planned raid
	 * called from planner.php
	 *
	 */
	public function display_plannedraid()
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
	
		// define month_names, raid_plan_ids, names, colors, images, date
		$this->_init_calendar_data();
		$etype_url_opts = $this->get_etype_url_opts();

		$planned_raid_id = request_var('calEid', 0);
		$raidplan_display_name = "";
		$raidplan_color = "";
		$raidplan_image = "";
		$raidplan_details = "";
		$all_day = 1;
		$start_date_txt = "";
		$end_date_txt = "";
		$subject="";
		$message="";
		$back_url = append_sid("{$phpbb_root_path}planner.$phpEx", "calD=".$this->date['day']."&amp;calM=".
				$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts );
		
		if( $planned_raid_id > 0)
		{
			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
					WHERE raidplan_id = '.$db->sql_escape($planned_raid_id);
			$result = $db->sql_query($sql);
			
			// get raiddata into one recordset 
			$raidplan_data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if( !$raidplan_data )
			{
				trigger_error( 'INVALID_RAIDPLAN' );
			}
	
			// Is the user able to view ANY raidplans?
			if ( !$auth->acl_get('u_raidplanner_view_raidplans') )
			{
				trigger_error( 'USER_CANNOT_VIEW_RAIDPLAN' );
			}
			
			// Is user authorized to view THIS raidplan?
			$user_auth_for_raidplan = $this->is_user_authorized_to_view_raidplan( $user->data['user_id'], $raidplan_data);
			if( $user_auth_for_raidplan == 0 )
			{
				trigger_error( 'PRIVATE_RAIDPLAN' );
			}
			if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
			{
				$calWatchE = request_var( 'calWatchE', 2 );
				$watchclass = new calendar_watch();
				
				if( $calWatchE < 2 )
				{
					$watchclass->calendar_watch_raidplan( $planned_raid_id, $calWatchE );
				}
				else
				{
					$watchclass->calendar_mark_user_read_raidplan( $planned_raid_id, $user->data['user_id'] );
				}
			}
	
			$disp_date_format = $config['rp_date_format'];
		    $disp_date_time_format = $config['rp_date_time_format'];
	
			$start_date_txt = $user->format_date($raidplan_data['raidplan_start_time'], $disp_date_time_format, true);
			$end_date_txt = $user->format_date($raidplan_data['raidplan_end_time'], $disp_date_time_format, true);
	
			// translate raidplan start and end time into user's timezone
			$raidplan_start = $raidplan_data['raidplan_start_time'] + $user->timezone + $user->dst;
			$raidplan_end = $raidplan_data['raidplan_end_time'] + $user->timezone + $user->dst;
	
			if( $raidplan_data['raidplan_all_day'] == 1 )
			{
				// All day raidplan - find the string for the raidplan day
				if ($raidplan_data['raidplan_day'])
				{
					list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $raidplan_data['raidplan_day']);
	
					$raidplan_days_time = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
					$start_date_txt = $user->format_date($raidplan_days_time, $disp_date_format, true);
					$this->date['day'] = $eday['eday_day'];
					$this->date['month_no'] = $eday['eday_month'];
					$this->date['year'] = $eday['eday_year'];
				}
				else
				{
					// We should never get here
					// (this would be an all day raidplan with no specified day for the raidplan)
					$start_date_txt = "";
				}
			}
			else
			{
				$all_day = 0;
				$this->date['day'] = gmdate("d", $raidplan_start);
				$this->date['month_no'] = gmdate("n", $raidplan_start);
				$this->date['year']	=	gmdate('Y', $raidplan_start);
			}
			$back_url = append_sid("{$phpbb_root_path}planner.$phpEx", "calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts );
	
			$raidplan_body = $raidplan_data['raidplan_body'];
			$raidplan_data['bbcode_options'] = (($raidplan_data['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +   
			 (($raidplan_data['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +     (($raidplan_data['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
	
			$message = generate_text_for_display($raidplan_body, $raidplan_data['bbcode_uid'], $raidplan_data['bbcode_bitfield'], $raidplan_data['bbcode_options']);
			$raidplan_display_name = $this->raid_plan_displaynames[$raidplan_data['etype_id']];
			$raidplan_color = $this->raid_plan_colors[$raidplan_data['etype_id']];
			$raidplan_image = $this->raid_plan_images[$raidplan_data['etype_id']];
	
			$subject = censor_text($raidplan_data['raidplan_subject']);
	
			$poster_url = '';
			$invite_list = '';
			
			$raidplans = new raidplans();
			$raidplans->get_raidplan_invite_list_and_poster_url($raidplan_data, $poster_url, $invite_list );
	
			$edit_url = "";
			$edit_all_url = "";
			if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_raidplans') &&
			    (($user->data['user_id'] == $raidplan_data['poster_id'])|| $auth->acl_get('m_raidplanner_edit_other_users_raidplans') ))
			{
				$edit_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=edit&amp;calEid=".$planned_raid_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
				if( $raidplan_data['recurr_id'] > 0 )
				{
					$edit_all_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=edit&amp;calEditAll=1&amp;calEid=".$planned_raid_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
				}
			}
			$delete_url = "";
			$delete_all_url = "";
			if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_raidplans') &&
			    (($user->data['user_id'] == $raidplan_data['poster_id'])|| $auth->acl_get('m_raidplanner_delete_other_users_raidplans') ))
			{
				$delete_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=delete&amp;calEid=".$planned_raid_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
				if( $raidplan_data['recurr_id'] > 0 )
				{
					$delete_all_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=delete&amp;calDelAll=1&amp;calEid=".$planned_raid_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
				}
			}

			// does this raidplan have attendance tracking turned on?
			if( $raidplan_data['track_signups'] == 1 )
			{
				
				// get profiles wanted for this raid
				$sql_array = array(
			    	'SELECT'    => 'r.role_id, r.role_name, er.role_needed ', 
			    	'FROM'      => array(
						RP_ROLES   => 'r'
			    	),
			    
			    	'LEFT_JOIN' => array(
			        	array(
			            	'FROM'  => array( RP_EVENTROLES  => 'er'),
			            	'ON'    => 'r.role_id = er.role_id AND er.raidplan_id = ' . $planned_raid_id)
			    			),
			    	'ORDER_BY'  => 'r.role_id'
				);
		
				$sql = $db->sql_build_query('SELECT', $sql_array);
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
				    $template->assign_block_vars('raidroles', array(
				        'ROLE_ID'        => $row['role_id'],
					    'ROLE_NAME'      => $row['role_name'],
				    	'ROLE_NEEDED'    => $row['role_needed'],
				    ));
				}
				$db->sql_freeresult($result);
				
			
				$signup_id	= request_var('signup_id', 0);
				$submit		= (isset($_POST['post'])) ? true : false;
				$signup_data = array();
				if( $signup_id !== 0 )
				{
					$this->get_signup_data( $signup_id, $signup_data );
					if( $signup_data['raidplan_id'] != $planned_raid_id )
					{
						trigger_error('NO_SIGNUP');
					}
				}
				else
				{
					$signup_data['signup_id'] = 0;
					$signup_data['raidplan_id'] = $planned_raid_id;
					$signup_data['poster_id'] = $user->data['user_id'];
					$signup_data['poster_name'] = $user->data['username'];
					$signup_data['poster_colour'] = $user->data['user_colour'];
					$signup_data['poster_ip'] = $user->ip;
					$signup_data['post_time'] = time();
					$signup_data['signup_val'] = 2;
					$signup_data['signup_count'] = 1;
					$signup_data['signup_detail'] = "";
					$signup_data['signup_detail_edit'] = "";
				}
	
	
				// Can we edit this reply ... if we're a moderator with rights then always yes
				// else it depends on editing times, lock status and if we're the correct user
				if ( $signup_id !== 0 && !$auth->acl_get('m_raidplanner_edit_other_users_signups'))
				{
					if ($user->data['user_id'] != $signup_data['poster_id'])
					{
						trigger_error('USER_CANNOT_EDIT_SIGNUP');
					}
				}
	
				
				// save updated raid
				if( $submit )
				{
					// what were the old raidplan_data head counts?
					$old_yes_count = $raidplan_data['signup_yes'];
					$old_no_count = $raidplan_data['signup_no'];
					$old_maybe_count = $raidplan_data['signup_maybe'];
	
					$old_user_yes_count = 0;
					$old_user_maybe_count = 0;
					$old_user_no_count = 0;
	
					$new_signup_val	= request_var('signup_val', 2);
					$new_signup_count	= request_var('signup_count', 1);
					$new_signup_detail = utf8_normalize_nfc( request_var('signup_detail', '', true) );
	
					$uid = $bitfield = $options = '';
					$allow_bbcode = $allow_urls = $allow_smilies = true;
					generate_text_for_storage($new_signup_detail, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
	
					$new_user_yes_count = 0;
					$new_user_maybe_count = 0;
					$new_user_no_count = 0;
	
					if( $signup_id !== 0 )
					{
						if( $signup_data['signup_val'] == 0 )
						{
							$old_user_yes_count = $signup_data['signup_count'];
						}
						else if( $signup_data['signup_val'] == 1 )
						{
							$old_user_no_count = $signup_data['signup_count'];
						}
						else
						{
							$old_user_maybe_count = $signup_data['signup_count'];
						}
					}
					
					if( $new_signup_val == 0 )
					{
						$new_user_yes_count = $new_signup_count;
					}
					else if( $new_signup_val == 1 )
					{
						$new_user_no_count = $new_signup_count;
					}
					else
					{
						$new_user_maybe_count = $new_signup_count;
					}
	
					$new_yes_count = $old_yes_count - $old_user_yes_count + $new_user_yes_count;
					$new_no_count = $old_no_count - $old_user_no_count + $new_user_no_count;
					$new_maybe_count = $old_maybe_count - $old_user_maybe_count + $new_user_maybe_count;
	
					// save the user's signup data...
	
					// update the ip address and time
					$signup_data['poster_ip'] = $user->ip;
					$signup_data['post_time'] = time();
					$signup_data['signup_val'] = $new_signup_val;
					$signup_data['signup_count'] = $new_signup_count;
					$signup_data['signup_detail'] = $new_signup_detail;
					if( $signup_id > 0 )
					{
						$sql = 'UPDATE ' . RP_SIGNUPS . '
							SET ' . $db->sql_build_array('UPDATE', array(
								'poster_id'			=> (int) $signup_data['poster_id'],
								'poster_name'		=> (string) $signup_data['poster_name'],
								'poster_colour'		=> (string) $signup_data['poster_colour'],
								'poster_ip'			=> (string) $signup_data['poster_ip'],
								'post_time'			=> (int) $signup_data['post_time'],
								'signup_val'				=> (int) $signup_data['signup_val'],
								'signup_count'			=> (int) $signup_data['signup_count'],
								'signup_detail'			=> (string) $signup_data['signup_detail'],
								'bbcode_bitfield'	=> $bitfield,
								'bbcode_uid'		=> $uid,
								'bbcode_options'	=> $options,
								)) . "
							WHERE signup_id = $signup_id";
						$db->sql_query($sql);
					}
					else
					{
						$sql = 'INSERT INTO ' . RP_SIGNUPS . ' ' . $db->sql_build_array('INSERT', array(
								'raidplan_id'			=> (int) $signup_data['raidplan_id'],
								'poster_id'			=> (int) $signup_data['poster_id'],
								'poster_name'		=> (string) $signup_data['poster_name'],
								'poster_colour'		=> (string) $signup_data['poster_colour'],
								'poster_ip'			=> (string) $signup_data['poster_ip'],
								'post_time'			=> (int) $signup_data['post_time'],
								'signup_val'				=> (int) $signup_data['signup_val'],
								'signup_count'			=> (int) $signup_data['signup_count'],
								'signup_detail'			=> (string) $signup_data['signup_detail'],
								'bbcode_bitfield'	=> $bitfield,
								'bbcode_uid'		=> $uid,
								'bbcode_options'	=> $options,
								)
							);
						$db->sql_query($sql);
						//$signup_id = $db->sql_nextid();
					}
					// update the raidplan id's signup stats
						$sql = 'UPDATE ' . RP_RAIDS_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', array(
								'signup_yes'		=> (int) $new_yes_count,
								'signup_no'		=> (int) $new_no_count,
								'signup_maybe'	=> (int) $new_maybe_count,
								)) . "
							WHERE raidplan_id = $planned_raid_id";
						$db->sql_query($sql);
					$raidplan_data['signup_yes'] = $new_yes_count;
					$raidplan_data['signup_no'] = $new_no_count;
					$raidplan_data['signup_maybe'] = $new_maybe_count;
					
						
					$this->calendar_add_or_update_reply( $planned_raid_id );
				
				}
	
				$sql = 'SELECT * FROM ' . RP_SIGNUPS . '
						WHERE raidplan_id = '.$db->sql_escape($planned_raid_id). ' ORDER BY signup_val ASC';
				$result = $db->sql_query($sql);
	
				$edit_signups = 0;
				if( $auth->acl_get('m_raidplanner_edit_other_users_signups') )
				{
					$edit_signups = 1;
					$edit_signup_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$planned_raid_id.$etype_url_opts );
					$edit_signup_url .="&amp;signup_id=";
				}
	
				while ($signup_row = $db->sql_fetchrow($result) )
				{
					if( ($signup_id == 0 && $signup_data['poster_id'] == $signup_row['poster_id']) ||
					    ($signup_id != 0 && $signup_id == $signup_row['signup_id']) )
					{
						$signup_data['signup_id'] = $signup_row['signup_id'];
						$signup_data['post_time'] = $signup_row['post_time'];
						$signup_data['signup_val'] = $signup_row['signup_val'];
						$signup_data['signup_count'] = $signup_row['signup_count'];
						$edit_text_array = generate_text_for_edit( $signup_row['signup_detail'], $signup_row['bbcode_uid'], $signup_row['bbcode_options']);
						$signup_data['signup_detail_edit'] = $edit_text_array['text'];
					}
					$signup_out['POSTER'] = $signup_row['poster_name'];
					$signup_out['POSTER_URL'] = get_username_string( 'full', $signup_row['poster_id'], $signup_row['poster_name'], $signup_row['poster_colour'] );
					$signup_out['VALUE'] = $signup_row['signup_val'];
					if( $signup_row['signup_val'] == 0 )
					{
						$signup_out['COLOR'] = '#00ff00';
						$signup_out['VALUE_TXT'] = $user->lang['YES'];
					}
					else if( $signup_row['signup_val'] == 1 )
					{
						$signup_out['COLOR'] = '#ff0000';
						$signup_out['VALUE_TXT'] = $user->lang['NO'];
					}
					else
					{
						$signup_out['COLOR'] = '#0000ff';
						$signup_out['VALUE_TXT'] = $user->lang['MAYBE'];
					}
					$signup_out['U_EDIT'] = "";
					if( $edit_signups === 1 )
					{
						$signup_out['U_EDIT'] = $edit_signup_url . $signup_row['signup_id'];
					}
					$signup_out['HEADCOUNT'] = $signup_row['signup_count'];
					$signup_out['DETAILS'] = generate_text_for_display($signup_row['signup_detail'], $signup_row['bbcode_uid'], $signup_row['bbcode_bitfield'], $signup_row['bbcode_options']);
					$signup_out['POST_TIMESTAMP'] = $signup_row['post_time'];
					$signup_out['POST_TIME'] = $user->format_date($signup_row['post_time']);
					$template->assign_block_vars('signups', $signup_out);
	
				}
				$db->sql_freeresult($result);
				$show_current_response = 0;
				
				/* Build the signup form */
				/* if its not a bot and not anon show form */
				if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
				{
					$show_current_response = 1;
					$sel_attend_code  = "<select name='signup_val' id='signup_val''>\n";
					$sel_attend_code .= "<option value='0'>".$user->lang['YES']."</option>\n";
					$sel_attend_code .= "<option value='1'>".$user->lang['NO']."</option>\n";
					$sel_attend_code .= "<option value='2'>".$user->lang['MAYBE']."</option>\n";
					$sel_attend_code .= "</select>\n";
	
					$temp_find_str = "value='".$signup_data['signup_val']."'";
					$temp_replace_str = "value='".$signup_data['signup_val']."' selected='selected'";
					$sel_attend_code = str_replace( $temp_find_str, $temp_replace_str, $sel_attend_code );
	
					$template->assign_vars( array(
						'S_SIGNUP_MODE_ACTION'=> append_sid("{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$planned_raid_id.$etype_url_opts ),
						'S_CURRENT_SIGNUP'	=> $show_current_response,
						'S_EDIT_SIGNUP'		=> $edit_signups,
						'CURR_SIGNUP_ID'		=> $signup_data['signup_id'],
						'CURR_POSTER_URL'	=> get_username_string( 'full', $signup_data['poster_id'], $signup_data['poster_name'], $signup_data['poster_colour'] ),
						'CURR_SIGNUP_COUNT'	=> $signup_data['signup_count'],
						'CURR_SIGNUP_DETAIL'	=> $signup_data['signup_detail_edit'],
						'SEL_ATTEND'		=> $sel_attend_code,
						'SEL_ROLE'			=> $sel_role, 
						)
					);
	
				}
				$template->assign_vars( array(
					'CURR_YES_COUNT'	=> $raidplan_data['signup_yes'],
					'CURR_NO_COUNT'		=> $raidplan_data['signup_no'],
					'CURR_MAYBE_COUNT'	=> $raidplan_data['signup_maybe'],
					)
				);
			}
		
			$add_raidplan_url = "";
			//if( $auth->acl_get('u_raidplanner_create_raidplans') )
			if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans') )
			{
				$add_raidplan_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=post&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			}
			$day_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=day&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			$week_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			$month_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=month&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
	
			$s_signup_headcount = false;
			if( ($user->data['user_id'] == $raidplan_data['poster_id'])|| $auth->acl_get('u_raidplanner_view_headcount') )
			{
				$s_signup_headcount = true;
			}
			
			$s_watching_raidplan = array();
			$this->calendar_init_s_watching_raidplan_data( $planned_raid_id, $s_watching_raidplan );
	
			$template->assign_vars(array(
				'U_CALENDAR'		=> $back_url,
				'ETYPE_DISPLAY_NAME'=> $raidplan_display_name,
				'EVENT_COLOR'		=> $raidplan_color,
				'EVENT_IMAGE'		=> $raidplan_image,
				'SUBJECT'			=> $subject,
				'MESSAGE'			=> $message,
				'START_DATE'		=> $start_date_txt,
				'END_DATE'			=> $end_date_txt,
				'IS_RECURRING'		=> $raidplan_data['recurr_id'],
				'RECURRING_TXT'		=> $this->get_recurring_raidplan_string_via_id( $raidplan_data['recurr_id'] ),
				'POSTER'			=> $poster_url,
				'ALL_DAY'			=> $all_day,
				'INVITED'			=> $invite_list,
				'U_EDIT'			=> $edit_url,
				'U_EDIT_ALL'		=> $edit_all_url,
				'U_DELETE'			=> $delete_url,
				'U_DELETE_ALL'		=> $delete_all_url,
				'DAY_IMG'			=> $user->img('button_calendar_day', 'DAY'),
				'WEEK_IMG'			=> $user->img('button_calendar_week', 'WEEK'),
				'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
				'ADD_LINK'			=> $add_raidplan_url,
				'DAY_VIEW_URL'		=> $day_view_url,
				'WEEK_VIEW_URL'		=> $week_view_url,
				'MONTH_VIEW_URL'	=> $month_view_url,
				'S_CALENDAR_SIGNUPS'	=> $raidplan_data['track_signups'],
				'S_SIGNUP_HEADCOUNT'	=> $s_signup_headcount,
				
				'U_WATCH_RAIDPLAN' 		=> $s_watching_raidplan['link'],
				'L_WATCH_RAIDPLAN' 		=> $s_watching_raidplan['title'],
				'S_WATCHING_RAIDPLAN'		=> $s_watching_raidplan['is_watching'],
	
				)
			);
		}
		
		
	}
	
	
	
		
	/* calendar_init_s_watching_raidplan_data()
	**
	** Determines if the current user is watching the specified raidplan, and
	** generates the data required for the overall_footer to display
	** the watch/unwatch link.
	**
	** INPUT
	**   $raidplan_id - raidplan currently being displayed
	**
	** OUTPUT
	**   $s_watching_raidplan - filled with data for the overall_footer template
	*/
	function calendar_init_s_watching_raidplan_data( $raidplan_id, &$s_watching_raidplan )
	{
		global $db, $user;
		global $phpEx, $phpbb_root_path;
	
		$s_watching_raidplan['link'] = "";
		$s_watching_raidplan['title'] = "";
		$s_watching_raidplan['is_watching'] = false;
		if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
		{
			$sql = 'SELECT * FROM ' . RP_EVENTS_WATCH . '
				WHERE user_id = '.$user->data['user_id'].' AND raidplan_id = ' .$raidplan_id;
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$s_watching_raidplan['is_watching'] = true;
			}
			$db->sql_freeresult($result);
			if( $s_watching_raidplan['is_watching'] )
			{
				$s_watching_raidplan['link'] = append_sid( "{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$raidplan_id."&amp;calWatchE=0" );
				$s_watching_raidplan['title'] = $user->lang['WATCH_EVENT_TURN_OFF'];
			}
			else
			{
				$s_watching_raidplan['link'] = append_sid( "{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$raidplan_id."&amp;calWatchE=1" );
				$s_watching_raidplan['title'] = $user->lang['WATCH_EVENT_TURN_ON'];
			}
		}
	}
		
	
	/* get_signup_data()
	**
	** Gets the signup data for the selected signup id
	*/
	function get_signup_data( $id, &$signup_data )
	{
		global $auth, $db, $user;
		if( $id < 1 )
		{
			trigger_error('NO_SIGNUP');
		}
		$sql = 'SELECT * FROM ' . RP_SIGNUPS . '
				WHERE signup_id = '.$db->sql_escape($id);
		$result = $db->sql_query($sql);
		$signup_data = $db->sql_fetchrow($result);
		if( !$signup_data )
		{
			trigger_error('NO_SIGNUP');
		}
	
	    $db->sql_freeresult($result);
	    $signup_data['signup_detail_edit'] = "";
	}
		
	
	
	
		
	/* get_recurring_raidplan_string_via_id()
	**
	** Gets the displayable string that describes the frequency of a
	** recurring raidplan
	**
	** INPUT
	**   $recurr_id - the recurring raidplan id.
	*/
	private function get_recurring_raidplan_string_via_id( $recurr_id )
	{
		global $db, $user;
	
		$string = "";
	
		if( $recurr_id == 0 )
		{
			return $string;
		}
	
		$sql = 'SELECT * FROM ' . RP_RECURRING ."
				WHERE recurr_id ='".$db->sql_escape($recurr_id)."'";
		$result = $db->sql_query($sql);
		if($row = $db->sql_fetchrow($result))
		{
			$string = $this->get_recurring_raidplan_string( $row );
		}
		$db->sql_freeresult($result);
	
		return $string;
	}
	
	/* get_recurring_raidplan_string()
	**
	** Gets the displayable string that describes the frequency of a
	** recurring raidplan
	**
	** INPUT
	**   $row - the row of data from the RP_RECURRING
	**          describing this recurring raidplan.
	*/
	private function get_recurring_raidplan_string( $row )
	{
		global $user;
	
		$string = "";
	
		if( $row['recurr_id']== 0 && $row['is_recurr'] != 1 )
		{
			return string;
		}
	
		$week_index = $row['week_index'];
	
		$case_string = (string)$row['frequency_type'];
		if( $row['frequency_type'] == 3 ||
			$row['frequency_type'] == 5 ||
			$row['frequency_type'] == 8 ||
			$row['frequency_type'] == 10 )
		{
			if( $row['week_index'] == 0 )
			{
				$case_string = $case_string."b";
			}
		}
		if( $row['frequency_type'] == 4 ||
			$row['frequency_type'] == 5 ||
			$row['frequency_type'] == 9 ||
			$row['frequency_type'] == 10 )
		{
			$week_index--;
		}
	
		$case_string = 'RECURRING_EVENT_CASE_'.$case_string.'_STR';
		$timestamp = 0;
		$timezone_string = "";
		if( $row['first_occ_time'] > 0 )
		{
			$timestamp = $row['first_occ_time'];
		}
		else if( $row['next_calc_time'] > 0 )
		{
			$timestamp = $row['next_calc_time'];
		}
	
		if( $row['raidplan_all_day'] == 0 )
		{
			$timestamp = $timestamp + (($row['poster_timezone'] + $row['poster_dst'])*3600);
	
			// we only need to display a timezone reference if it's different from the viewer
			// and it's a timed (not all day) raidplan
			if( ($user->data['user_timezone'] + $user->data['user_dst']) !=
				($row['poster_timezone'] + $row['poster_dst']) )
			{
				$poster_time = strval(doubleval($row['poster_timezone']));
				$timezone_string = " " .$user->lang['tz'][$poster_time];
				if( $row['poster_dst'] > 0 )
				{
					$timezone_string = $timezone_string . " " . $user->lang['tz']['dst'];
				}
			}
		}
	
		$month = gmdate("F", $timestamp);
		$day = gmdate("j", $timestamp);
		$weekdayName = gmdate("l", $timestamp);
	
		$string = sprintf($user->lang[$case_string], $user->lang['numbertext'][$day], $user->lang['datetime'][$weekdayName], $user->lang['numbertext'][$week_index], $user->lang['datetime'][$month], $row['frequency'] );
	
		$zeroth = $user->lang['ZEROTH_FROM'];
		$temp_replace_str = "";
		$string = str_replace( $zeroth, $temp_replace_str, $string );
		$string = $string . $timezone_string;
	
		return $string;
	
	}
		
			
	/* used to generate the UCP "manage my raidplans" module */
	public function display_posters_next_raidplans_for_x_days( $x, $user_id )
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
		$etype_url_opts = $this->get_etype_url_opts();
	
		// Is the user able to view ANY raidplans?
		$user_can_view_raidplans = false;
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			$start_temp_date = time();
			//$end_temp_date = $start_temp_date + 31536000;
			$end_temp_date = $start_temp_date + ($x * 86400);
			// find all day raidplans that are still taking place
			$sort_timestamp_cutoff = $start_temp_date - 86400+1;
	
		    $disp_date_format = $config['rp_date_format'];
		    $disp_date_time_format = $config['rp_date_time_format'];
	
			// don't list raidplans that are more than 1 year in the future
			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
					WHERE poster_id = '.$user_id.' AND( (raidplan_access_level = 2) OR
					(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
					(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
					((( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
					( raidplan_end_time > '.$db->sql_escape($start_temp_date).' AND raidplan_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
					( raidplan_start_time < '.$db->sql_escape($start_temp_date).' AND raidplan_end_time > '.$db->sql_escape($end_temp_date)." )) 
					OR (sort_timestamp > ".$db->sql_escape($sort_timestamp_cutoff)." AND sort_timestamp <= ".$db->sql_escape($end_temp_date)." 
					AND raidplan_all_day = 1) ) ORDER BY sort_timestamp ASC";
			
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$raidplans['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts);
				$raidplans['IMAGE'] = $this->raid_plan_images[$row['etype_id']];
				$raidplans['COLOR'] = $this->raid_plan_colors[$row['etype_id']];
				$raidplans['ETYPE_DISPLAY_NAME'] = $this->raid_plan_displaynames[$row['etype_id']];
	
				$raidplans['FULL_SUBJECT'] = censor_text($row['raidplan_subject']);
				$raidplans['SUBJECT'] = $raidplans['FULL_SUBJECT'];
				if( $subject_limit > 0 )
				{
					if(utf8_strlen($raidplans['SUBJECT']) > $subject_limit)
					{
						$raidplans['SUBJECT'] = truncate_string($raidplans['SUBJECT'], $subject_limit) . '...';
					}
				}
				$raidplans['IS_RECURRING'] = $row['recurr_id'];
				$raidplans['RECURRING_TXT'] = $this->get_recurring_raidplan_string_via_id( $row['recurr_id'] );
	
				$poster_url = '';
				$invite_list = '';
				$rraidplans = new raidplans; 
				$rraidplans->get_raidplan_invite_list_and_poster_url($row, $poster_url, $invite_list );
				$raidplans['POSTER'] = $poster_url;
				$raidplans['INVITED'] = $invite_list;
				$raidplans['ALL_DAY'] = 0;
				if( $row['raidplan_all_day'] == 1 )
				{
					list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $row['raidplan_day']);
					$row['raidplan_start_time'] = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
					$row['raidplan_end_time'] = $row['raidplan_start_time']+86399;
					$raidplans['ALL_DAY'] = 1;
					$raidplans['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_format, true);
					$raidplans['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_format, true);
				}
				else
				{
					$raidplans['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_time_format, true);
					$raidplans['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_time_format, true);
				}
	
				$edit_url = "";
				$edit_all_url = "";
				if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_raidplans') &&
					(($user->data['user_id'] == $row['poster_id'])|| $auth->acl_get('m_raidplanner_edit_other_users_raidplans') ))
				{
					$edit_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=edit&amp;calEid=".$row['raidplan_id']."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
					if( $row['recurr_id'] > 0 )
					{
						$edit_all_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=edit&amp;calEditAll=1&amp;calEid=".$row['raidplan_id']."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
					}
				}
				$delete_url = "";
				$delete_all_url = "";
				if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_raidplans') &&
					(($user->data['user_id'] == $row['poster_id'])|| $auth->acl_get('m_raidplanner_delete_other_users_raidplans') ))
	
				{
					$delete_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=delete&amp;calEid=".$row['raidplan_id']."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
					if( $row['recurr_id'] > 0 )
					{
						$delete_all_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=delete&amp;calDelAll=1&amp;calEid=".$row['raidplan_id']."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
					}
				}
				$raidplans['U_EDIT'] = $edit_url;
				$raidplans['U_EDIT_ALL'] = $edit_all_url;
				$raidplans['U_DELETE'] = $delete_url;
				$raidplans['U_DELETE_ALL'] = $delete_all_url;
	
	
				$template->assign_block_vars('myraidplans', $raidplans);
			}
			$db->sql_freeresult($result);
		}
	}
	
	/* displays the calendar - either week view or upcoming raidplan list
	   as specified in the ACP on the index */
	public function display_calendar_on_index()
	{
		global $auth, $db, $user, $config, $template;
		$user->setup('mods/raidplanner');
	
		//find the first day of the week
		if( $config['rp_index_display_week'] === "1" )
		{
			$template->assign_vars(array(
				'S_CALENDAR_WEEK'	=> true,
			));
			$this->display_week(1);
		}
		else
		{
			//see if we should display X number of upcoming raidplans
			$s_next_raidplans = false;
			if( $config['rp_index_display_next_raidplans'] > 0 )
			{
				$s_next_raidplans = true;
			}
	
			$template->assign_vars(array(
				'S_CALENDAR_WEEK'	=> false,
				'S_CALENDAR_NEXT_EVENTS'	=> $s_next_raidplans,
			));
			$this->_display_next_raidplans( $config['rp_index_display_next_raidplans'] );
		}
	}	
	
		
	
	/* displays the next x number of upcoming raidplans */
	private function _display_next_raidplans( $x )
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
	
		$etype_url_opts = $this->get_etype_url_opts(); 
	
		// Is the user able to view ANY raidplans?
		$user_can_view_raidplans = false;
		if ( $auth->acl_get('u_calendar_view_raidplans') )
		{
	
			$this->_init_calendar_data();
			$subject_limit = $config['display_truncated_name']; 
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
	
			$start_temp_date = time();
			$end_temp_date = $start_temp_date + 31536000;
			// find all day raidplans that are still taking place
			$sort_timestamp_cutoff = $start_temp_date - 86400+1;
	
		    $disp_date_format = $config['rp_date_format'];
		    $disp_date_time_format = $config['date_time_format']; 
	
			// don't list raidplans that are more than 1 year in the future
			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
					WHERE ( (raidplan_access_level = 2) OR
						(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
						(raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
					((( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
					 ( raidplan_end_time > '.$db->sql_escape($start_temp_date).' AND raidplan_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
					 ( raidplan_start_time < '.$db->sql_escape($start_temp_date).' AND raidplan_end_time > '.$db->sql_escape($end_temp_date)." )) OR (sort_timestamp > ".
						$db->sql_escape($sort_timestamp_cutoff)." AND raidplan_all_day = 1) ) ORDER BY sort_timestamp ASC";
	
			$result = $db->sql_query_limit($sql, $x, 0);
			while ($row = $db->sql_fetchrow($result))
			{
				$raidplans['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts);
				$raidplans['IMAGE'] = $this->raid_plan_images[$row['etype_id']];
				$raidplans['COLOR'] = $this->raid_plan_colors[$row['etype_id']];
				$raidplans['ETYPE_DISPLAY_NAME'] = $this->raid_plan_displaynames[$row['etype_id']];
	
				$raidplans['FULL_SUBJECT'] = censor_text($row['raidplan_subject']);
				$raidplans['SUBJECT'] = $raidplans['FULL_SUBJECT'];
				if( $subject_limit > 0 )
				{
					if(utf8_strlen($raidplans['SUBJECT']) > $subject_limit)
					{
						$raidplans['SUBJECT'] = truncate_string($raidplans['SUBJECT'], $subject_limit) . '...';
					}
				}
	
				$poster_url = '';
				$invite_list = '';
				$rraidplans = new raidplans; 
				$rraidplans->get_raidplan_invite_list_and_poster_url($row, $poster_url, $invite_list );
				$raidplans['POSTER'] = $poster_url;
				$raidplans['INVITED'] = $invite_list;
				$raidplans['ALL_DAY'] = 0;
				if( $row['raidplan_all_day'] == 1 )
				{
					list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $row['raidplan_day']);
					$row['raidplan_start_time'] = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
					$row['raidplan_end_time'] = $row['raidplan_start_time']+86399;
					$raidplans['ALL_DAY'] = 1;
					$raidplans['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_format, true);
					$raidplans['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_format, true);
				}
				else
				{
					$raidplans['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_time_format, true);
					$raidplans['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_time_format, true);
				}
	
				$template->assign_block_vars('raidplans', $raidplans);
			}
			$db->sql_freeresult($result);
		}
	}
	
	/* displays the upcoming raidplans for the next x number of days */
	public function display_next_raidplans_for_x_days( $x )
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
		$etype_url_opts = $this->get_etype_url_opts();
	
		// Is the user able to view ANY raidplans?
		$user_can_view_raidplans = false;
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
	
			$this->_init_calendar_data();
			$subject_limit = $config['rp_display_truncated_name'];
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
	
			$start_temp_date = time();
			//$end_temp_date = $start_temp_date + 31536000;
			$end_temp_date = $start_temp_date + ( $x * 86400 );
			// find all day raidplans that are still taking place
			$sort_timestamp_cutoff = $start_temp_date - 86400+1;
	
		    $disp_date_format = $config['rp_date_format'];
		    $disp_date_time_format = $config['rp_date_time_format'];
	
			// don't list raidplans that are more than 1 year in the future
			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
					WHERE ( (raidplan_access_level = 2) OR
						(poster_id = '.$db->sql_escape($user->data['user_id']).' ) 
						OR (raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' 
					AND ((( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' 
					AND raidplan_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
					 	(raidplan_end_time > '.$db->sql_escape($start_temp_date).' 
					AND raidplan_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
					 	(raidplan_start_time < '.$db->sql_escape($start_temp_date).' 
					AND raidplan_end_time > '.$db->sql_escape($end_temp_date)." )) OR (sort_timestamp > ".$db->sql_escape($sort_timestamp_cutoff)." 
					AND sort_timestamp <= ".$db->sql_escape($end_temp_date)." 
					AND raidplan_all_day = 1) ) ORDER BY sort_timestamp ASC";
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$raidplans['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts);
				$raidplans['IMAGE'] = $this->raid_plan_images[$row['etype_id']];
				$raidplans['COLOR'] = $this->raid_plan_colors[$row['etype_id']];
				$raidplans['ETYPE_DISPLAY_NAME'] = $this->raid_plan_displaynames[$row['etype_id']];
	
				$raidplans['FULL_SUBJECT'] = censor_text($row['raidplan_subject']);
				$raidplans['SUBJECT'] = $raidplans['FULL_SUBJECT'];
				if( $subject_limit > 0 )
				{
					if(utf8_strlen($raidplans['SUBJECT']) > $subject_limit)
					{
						$raidplans['SUBJECT'] = truncate_string($raidplans['SUBJECT'], $subject_limit) . '...';
					}
				}
	
				$poster_url = '';
				$invite_list = '';
				$rraidplans = new raidplans; 
				$rraidplans->get_raidplan_invite_list_and_poster_url($row, $poster_url, $invite_list );
				$raidplans['POSTER'] = $poster_url;
				$raidplans['INVITED'] = $invite_list;
				$raidplans['ALL_DAY'] = 0;
				if( $row['raidplan_all_day'] == 1 )
				{
					list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $row['raidplan_day']);
					$row['raidplan_start_time'] = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
					$row['raidplan_end_time'] = $row['raidplan_start_time']+86399;
					$raidplans['ALL_DAY'] = 1;
					$raidplans['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_format, true);
					$raidplans['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_format, true);
				}
				else
				{
					$raidplans['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_time_format, true);
					$raidplans['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_time_format, true);
				}

				$template->assign_block_vars('raidplans', $raidplans);
			}
			$db->sql_freeresult($result);
		}
	}
	

	

	
	
	/* Initialize the pulldown menus that allow the user
	   to jump from one calendar display mode/time to another
		ok
	*/
	private function _init_view_selection_code( $view_mode )
	{
		global $auth, $db, $user, $config; 
	
		// create RP_VIEW_OPTIONS
		$this->month_sel_code  = "<select name='calM' id='calM'>\n";
		for( $i = 1; $i <= 12; $i++ )
		{
			$this->month_sel_code .= "<option value='".$i."'>".$user->lang['datetime'][$this->month_names[$i]]."</option>\n";
		}
		$this->month_sel_code .= "</select>\n";
	
		$this->day_sel_code  = "<select name='calD' id='calD'>\n";
		for( $i = 1; $i <= 31; $i++ )
		{
			$this->day_sel_code .= "<option value='".$i."'>".$i."</option>\n";
		}
		$this->day_sel_code .= "</select>\n";
	
		$temp_year	=	gmdate('Y');
	
		$this->year_sel_code  = "<select name='calY' id='calY'>\n";
		for( $i = $temp_year-1; $i < ($temp_year+5); $i++ )
		{
			$this->year_sel_code .= "<option value='".$i."'>".$i."</option>\n";
		}
		$this->year_sel_code .= "</select>\n";
	
		$temp_find_str = "value='".$this->date['month_no']."'>";
		$temp_replace_str = "value='".$this->date['month_no']."' selected='selected'>";
		$this->month_sel_code = str_replace( $temp_find_str, $temp_replace_str, $this->month_sel_code );
		$temp_find_str = "value='".(int) $this->date['day']."'>";
		$temp_replace_str = "value='".(int)$this->date['day']."' selected='selected'>";
		$this->day_sel_code = str_replace( $temp_find_str, $temp_replace_str, $this->day_sel_code );
		$temp_find_str = "value='".$this->date['year']."'>";
		$temp_replace_str = "value='".$this->date['year']."' selected='selected'>";
		$this->year_sel_code = str_replace( $temp_find_str, $temp_replace_str, $this->year_sel_code );
	
		$this->mode_sel_code = "<select name='view' id='view'>\n";
		$this->mode_sel_code .= "<option value='month'>".$user->lang['MONTH']."</option>\n";
		$this->mode_sel_code .= "<option value='week'>".$user->lang['WEEK']."</option>\n";
		$this->mode_sel_code .= "<option value='day'>".$user->lang['DAY']."</option>\n";
		$this->mode_sel_code .= "</select>\n";
		$temp_find_str = "value='".$view_mode."'>";
		$temp_replace_str = "value='".$view_mode."' selected='selected'>";
		$this->mode_sel_code = str_replace( $temp_find_str, $temp_replace_str, $this->mode_sel_code );
	}
	
	/* used to find info about the previous and next [day, week, or month]
	 * ok
	*/
	private function set_date_prev_next( $view_mode )
	{
		
		if( $view_mode === "month" )
		{
			$this->date['prev_year'] = $this->date['year'];
			$this->date['next_year'] = $this->date['year'];
			$this->date['prev_month'] = $this->date['month_no'] - 1;
			$this->date['next_month'] = $this->date['month_no'] + 1;
			if( $this->date['prev_month'] == 0 )
			{
				$this->date['prev_month'] = 12;
				$this->date['prev_year']--;
			}
			if( $this->date['next_month'] == 13 )
			{
				$this->date['next_month'] = 1;
				$this->date['next_year']++;
			}
		}
		else
		{
			$delta_time = 0;
			if( $view_mode === "week" )
			{
				// delta = 7 days
				$delta_time = 604800;
			}
			else if( $view_mode === "day" )
			{
				// delta = 1 day
				$delta_time = 86400;
			}
			// get timestamp of current view date:
			$display_day = gmmktime(0,0,0, $this->date['month_no'], $this->date['day'], $this->date['year']);
			$prev_day = $display_day - $delta_time;
			$next_day = $display_day + $delta_time;
	
			$this->date['prev_day'] = gmdate("d", $prev_day);
			$this->date['next_day'] = gmdate("d", $next_day);
			$this->date['prev_month'] = gmdate("n", $prev_day);
			$this->date['next_month'] = gmdate("n", $next_day);
	
			$this->date['prev_year'] = gmdate("Y", $prev_day);
			$this->date['next_year'] = gmdate("Y", $next_day);
		}
	}
	
		
	/* 
	 * "shift" names of weekdays depending on which day we want to display as the first day of the week
	*/
	private function get_weekday_names( $first_day_of_week, &$sunday, &$monday, &$tuesday, &$wednesday, &$thursday, &$friday, &$saturday )
	{
		global $user;
		switch( $first_day_of_week )
		{
			case 0:
				$sunday = $user->lang['datetime']['Sunday'];
				$monday = $user->lang['datetime']['Monday'];
				$tuesday = $user->lang['datetime']['Tuesday'];
				$wednesday = $user->lang['datetime']['Wednesday'];
				$thursday = $user->lang['datetime']['Thursday'];
				$friday = $user->lang['datetime']['Friday'];
				$saturday = $user->lang['datetime']['Saturday'];
				break;
			case 1:
				$saturday = $user->lang['datetime']['Sunday'];
				$sunday = $user->lang['datetime']['Monday'];
				$monday = $user->lang['datetime']['Tuesday'];
				$tuesday = $user->lang['datetime']['Wednesday'];
				$wednesday = $user->lang['datetime']['Thursday'];
				$thursday = $user->lang['datetime']['Friday'];
				$friday = $user->lang['datetime']['Saturday'];
				break;
			case 2:
				$friday = $user->lang['datetime']['Sunday'];
				$saturday = $user->lang['datetime']['Monday'];
				$sunday = $user->lang['datetime']['Tuesday'];
				$monday = $user->lang['datetime']['Wednesday'];
				$tuesday = $user->lang['datetime']['Thursday'];
				$wednesday = $user->lang['datetime']['Friday'];
				$thursday = $user->lang['datetime']['Saturday'];
				break;
			case 3:
				$thursday = $user->lang['datetime']['Sunday'];
				$friday = $user->lang['datetime']['Monday'];
				$saturday = $user->lang['datetime']['Tuesday'];
				$sunday = $user->lang['datetime']['Wednesday'];
				$monday = $user->lang['datetime']['Thursday'];
				$tuesday = $user->lang['datetime']['Friday'];
				$wednesday = $user->lang['datetime']['Saturday'];
				break;
			case 4:
				$wednesday = $user->lang['datetime']['Sunday'];
				$thursday = $user->lang['datetime']['Monday'];
				$friday = $user->lang['datetime']['Tuesday'];
				$saturday = $user->lang['datetime']['Wednesday'];
				$sunday = $user->lang['datetime']['Thursday'];
				$monday = $user->lang['datetime']['Friday'];
				$tuesday = $user->lang['datetime']['Saturday'];
				break;
			case 5:
				$tuesday = $user->lang['datetime']['Sunday'];
				$wednesday = $user->lang['datetime']['Monday'];
				$thursday = $user->lang['datetime']['Tuesday'];
				$friday = $user->lang['datetime']['Wednesday'];
				$saturday = $user->lang['datetime']['Thursday'];
				$sunday = $user->lang['datetime']['Friday'];
				$monday = $user->lang['datetime']['Saturday'];
				break;
			case 6:
				$monday = $user->lang['datetime']['Sunday'];
				$tuesday = $user->lang['datetime']['Monday'];
				$wednesday = $user->lang['datetime']['Tuesday'];
				$thursday = $user->lang['datetime']['Wednesday'];
				$friday = $user->lang['datetime']['Thursday'];
				$saturday = $user->lang['datetime']['Friday'];
				$sunday = $user->lang['datetime']['Saturday'];
				break;
		}
	}
	
	/* fday is used to determine in what day we are starting with */
	private function get_fday($day, $month, $year, $first_day_of_week)
	{
		$fday = 0;
	
		
		$fday = gmdate("N",gmmktime(0,0,0, $month, $day, $year));
		$fday = $fday - $first_day_of_week;
		if( $fday < 0 )
		{
			$fday = $fday + 7;
		}
		return $fday;
	}
		
				
	/* we need to find out what group this user is a member of,
	   and create a list of or options for an sql command so we can
	   find raidplans for all of the groups this user is a member of.
	*/
	private function get_sql_group_options($user_id)
	{
		global $auth, $db;
	
		// What groups is this user a member of?
	
		/* don't check for hidden group setting -
		  if the raidplan was made by the admin for a hidden group -
		  members of the hidden group need to be able to see the raidplan in the calendar */
	
		$sql = 'SELECT g.group_id, g.group_name, g.group_type
				FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
				WHERE ug.user_id = '.$db->sql_escape($user_id).'
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
		return $group_options;
	}
	
	
	 
	/* Generates the list of birthdays for the given date
	*/
	private function generate_birthday_list( $day, $month, $year )
	{
		global $db, $user, $config;
	
		$birthday_list = "";
		if ($config['load_birthdays'] && $config['allow_birthdays'])
		{
			$sql = 'SELECT user_id, username, user_colour, user_birthday
					FROM ' . USERS_TABLE . "
					WHERE user_birthday LIKE '" . $db->sql_escape(sprintf('%2d-%2d-', $day, $month)) . "%'
					AND user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				// TBD TRANSLATION ISSUE HERE!!!
				$birthday_list .= (($birthday_list != '') ? ', ' : '') . get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
				if ($age = (int) substr($row['user_birthday'], -4))
				{
					// TBD TRANSLATION ISSUE HERE!!!
					$birthday_list .= ' (' . ($year - $age) . ')';
				}
			}
			if( $birthday_list != "" )
			{
				// TBD TRANSLATION ISSUE HERE!!!
				$birthday_list = $user->lang['BIRTHDAYS'].": ". $birthday_list;
			}
			$db->sql_freeresult($result);
		}
	
		return $birthday_list;
	}
		
	
	/* is_user_authorized_to_view_raidplan()
	**
	** Is the specified user allowed to view the raidplan defined
	** by the given raidplan_data?
	*/
	private function is_user_authorized_to_view_raidplan($user_id, $raidplan_data)
	{
		global $auth, $db;
		$user_auth_for_raidplan = 0;
	
		// no matter what the author can always see their own raidplans
		if( $user_id === $raidplan_data['poster_id'] )
		{
			$user_auth_for_raidplan = 1;
		}
		else
		{
			switch( $raidplan_data['raidplan_access_level'] )
			{
				case 0:
					// personal raidplan... only raidplan creator is invited
					break;
				case 1:
					// group raidplan... only members of specified group are invited
					// is this user a member of the group?
					if( $raidplan_data['group_id'] != 0 )
					{
						$sql = 'SELECT g.group_id
								FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
								WHERE ug.user_id = '.$db->sql_escape($user_id).'
									AND g.group_id = ug.group_id
									AND g.group_id = '.$db->sql_escape($raidplan_data['group_id']).'
									AND ug.user_pending = 0';
						$result = $db->sql_query($sql);
						if( $result )
						{
							$group_data = $db->sql_fetchrow($result);
							if( $group_data['group_id'] == $raidplan_data['group_id'] )
							{
								$user_auth_for_raidplan = 1;
							}
						}
						$db->sql_freeresult($result);
					}
					else
					{
						$group_list = explode( ',', $raidplan_data['group_id_list'] );
						$num_groups = sizeof( $group_list );
						$group_options = '';
						for( $i = 0; $i < $num_groups; $i++ )
						{
						    if( $group_list[$i] == "" )
						    {
						    	continue;
						    }
							if( $group_options != "" )
							{
								$group_options = $group_options . " OR ";
							}
							$group_options = $group_options . "g.group_id = ".$group_list[$i];
						}
						$sql = 'SELECT g.group_id
								FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
								WHERE ug.user_id = '.$db->sql_escape($user_id).'
									AND g.group_id = ug.group_id
									AND ('.$group_options.')
									AND ug.user_pending = 0';
						$result = $db->sql_query($sql);
						if( $result )
						{
							$group_data = $db->sql_fetchrow($result);
							// TBD test to make sure we never get here if it wasn't in the list
							//if( $group_data['group_id'] == $raidplan_data['group_id'] )
							{
								$user_auth_for_raidplan = 1;
							}
						}
						$db->sql_freeresult($result);
					}
					break;
				case 2:
					// public raidplan... everyone is invited
					$user_auth_for_raidplan = 1;
					break;
			}
		}
		return $user_auth_for_raidplan;
	}
	

	/* calendar_add_or_update_reply()
	**
	** Notifies users who are watching the raidplan of the new reply
	** or update.  Note if the user doesn't have permission to view
	** detailed replies - we don't notify them about new/updated replies,
	** we will only notify them when the raidplan information itself is updated.
	**
	** INPUT
	**   $raidplan_id - the id of the raidplan with updated info/replies.
	**   $is_reply - is this a reply, or update to the raidplan information itself?
	*/
	public function calendar_add_or_update_reply( $raidplan_id, $is_reply = true )
	{
		global $auth, $db, $user, $config, $phpEx, $phpbb_root_path;
	
		$user_id = $user->data['user_id'];
		$user_notify = $user->data['user_notify'];
	
		$raidplan_data = array();
		
		$raidplans = new raidplans();
		$raidplans->get_raidplan_data( $raidplan_id, $raidplan_data );
	
		include_once($phpbb_root_path . 'includes/functions.' . $phpEx);
		include_once($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);
		$messenger = new messenger();
	
		$sql_track_replies = "";
		if( $is_reply )
		{
			$sql_track_replies = " AND w.track_replies = 1 ";
		}
	
		$sql = 'SELECT w.*, u.username, u.username_clean, u.user_email, u.user_notify_type,
			u.user_jabber, u.user_lang FROM ' . RP_EVENTS_WATCH . ' w, ' . USERS_TABLE . ' u
			WHERE w.user_id = u.user_id '. $sql_track_replies .' AND w.raidplan_id = ' .$raidplan_id.' AND u.user_id <> '.$user_id;
		$db->sql_query($sql);
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			if( $row['notify_status'] == 0 )
			{
				if( $is_reply )
				{
					$messenger->template('calendar_updated_reply', $row['user_lang']);
				}
				else
				{
					$messenger->template('calendar_updated_raidplan', $row['user_lang']);
				}
				$messenger->to($row['user_email'], $row['username']);
				$messenger->im($row['user_jabber'], $row['username']);
	
				$messenger->assign_vars(array(
								'USERNAME'			=> htmlspecialchars_decode($row['username']),
								'EVENT_SUBJECT'		=> $raidplan_data['raidplan_subject'],
								'U_UNWATCH_RAIDPLAN'=> generate_board_url() . "/planner.$phpEx?view=raidplan&calEid=$raidplan_id&calWatchE=0",
								'U_RAIDPLAN'			=> generate_board_url() . "/planner.$phpEx?view=raidplan&calEid=$raidplan_id", )
							);
	
				$messenger->send($row['user_notify_type']);
	
				$sql = 'UPDATE ' . RP_EVENTS_WATCH . '
					SET ' . $db->sql_build_array('UPDATE', array(
					'notify_status'		=> (int) 1,
										)) . "
					WHERE raidplan_id = $raidplan_id AND user_id = " . $row['user_id'];
				$db->sql_query($sql);
			}
	
		}
		$db->sql_freeresult($result);
		$messenger->save_queue();
	
		if( $user_notify == 1 )
		{
			calendar_watch_raidplan( $raidplan_id, 1);
		}
	}
	

}



/*
 * collects functions for watching certain raidplan types
 * 
 */
class calendar_watch extends raidplanner_base 
{
	/* calendar_watch_raidplan()
	**
	** Adds/removes the current user into the RP_EVENTS_WATCH table
	** so that they can start/stop recieving notifications about updates
	** and replies to the specified raidplan.
	**
	** INPUT
	**    $raidplan_id - the raidplan the want to start/stop watching
	**    $turn_on = 1 - the user wants to START watching the raidplan
	**    $turn_on = 0 - the user wants to STOP watching the raidplan
	*/
	public function calendar_watch_raidplan( $raidplan_id, $turn_on = 1 )
	{
		global $db, $user, $auth;
		global $phpEx, $phpbb_root_path;
	
		$user_id = $user->data['user_id'];
	
		if( $turn_on == 1 )
		{
			$is_watching_raidplan = false;
			$sql = 'SELECT * FROM ' . RP_EVENTS_WATCH . '
				WHERE user_id = '.$user_id.' AND raidplan_id = ' .$raidplan_id;
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$is_watching_raidplan = true;
			}
			$db->sql_freeresult($result);
			if( $is_watching_raidplan )
			{
				$this->calendar_mark_user_read_raidplan( $raidplan_id, $user_id );
			}
			else
			{
				$sql = 'INSERT INTO ' . RP_EVENTS_WATCH . ' ' . 
				$db->sql_build_array('INSERT', array(
						'raidplan_id'		=> (int) $raidplan_id,
						'user_id'		=> (int) $user_id,
						'notify_status'	=> (int) 0,
						'track_replies' => (int) 1,
						)
					);
				$db->sql_query($sql);
			}
		}
		else if( $turn_on == 0 )
		{
			$sql = 'DELETE FROM ' . RP_EVENTS_WATCH . '
					WHERE raidplan_id = ' .$db->sql_escape($raidplan_id). '
					AND user_id = '.$db->sql_escape($user_id);
			$db->sql_query($sql);
		}
	}
	
	/* calendar_mark_user_read_raidplan()
	**
	** Changes the user's notify_status in the RP_EVENTS_WATCH table
	** This indicates that the user has re-visited the raidplan, and
	** they will recieve a notification the next time there is
	** an update/reply posted to this raidplan.
	**
	** INPUT
	**   $user_id - the user who just viewed a raidplan.
	*/
	public function calendar_mark_user_read_raidplan( $raidplan_id, $user_id )
	{
		global $db;
	
		$sql = 'UPDATE ' . RP_EVENTS_WATCH . '
			SET ' . $db->sql_build_array('UPDATE', array(
			'notify_status'		=> (int) 0,
								)) . "
			WHERE raidplan_id = $raidplan_id AND user_id = $user_id";
		$db->sql_query($sql);
	}
	
	/* calendar_mark_user_read_calendar()
	**
	** Changes the user's notify_status in the RP_WATCH table
	** This indicates that the user has re-visited the page, and
	** they will recieve a notification the next time there is
	** a new raidplan posted.
	**
	** INPUT
	**   $user_id - the user who just viewed a calendar page.
	*/
	public function calendar_mark_user_read_calendar( $user_id )
	{
		global $db;

		$sql = 'UPDATE ' . RP_WATCH . '
			SET ' . $db->sql_build_array('UPDATE', array(
			'notify_status'		=> (int) 0,
								)) . "
			WHERE user_id = $user_id";
		$db->sql_query($sql);
	}
	
	/* calendar_init_s_watching_calendar()
	**
	** Determines if the current user is watching the calendar, and
	** generates the data required for the overall_footer to display
	** the watch/unwatch link.
	**
	** OUTPUT
	**   $s_watching_calendar - filled with data for the overall_footer template
	*/
	public function calendar_init_s_watching_calendar( &$s_watching_calendar )
	{
		global $db, $user;
		global $phpEx, $phpbb_root_path;
	
		$s_watching_calendar['link'] = "";
		$s_watching_calendar['title'] = "";
		$s_watching_calendar['is_watching'] = false;
		if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
		{
			$sql = 'SELECT * FROM ' . RP_WATCH . '
				WHERE user_id = '.$user->data['user_id'];
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$s_watching_calendar['is_watching'] = true;
			}
			$db->sql_freeresult($result);
			if( $s_watching_calendar['is_watching'] )
			{
				$s_watching_calendar['link'] = append_sid( "{$phpbb_root_path}planner.$phpEx", "calWatch=0" );
				$s_watching_calendar['title'] = $user->lang['WATCH_CALENDAR_TURN_OFF'];
			}
			else
			{
				$s_watching_calendar['link'] = append_sid( "{$phpbb_root_path}planner.$phpEx", "calWatch=1" );
				$s_watching_calendar['title'] = $user->lang['WATCH_CALENDAR_TURN_ON'];
			}
		}
	}
		
	
	
}

/*
 * raidplan functions
 */
class raidplans extends raidplanner_base 
{
	
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
	
	
	/* get the the invite list for an raidplan and the poster url
	*/
	public function get_raidplan_invite_list_and_poster_url($raidplan_data, &$poster_url, &$invite_list )
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
					// group raidplan... only members of specified group are invited
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
					$group_list = explode( ',', $raidplan_data['group_id_list'] );
					$num_groups = sizeof( $group_list );
					for( $i = 0; $i < $num_groups; $i++ )
					{
						if( $group_list[$i] == "")
						{
							continue;
						}
						// group raidplan... only members of specified group are invited
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
<?php
/**
*
* @author alightner, Sajaki
* @package bbDKP Raidplanner
* @version CVS/SVN: $Id$
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
	
		// Is the user able to view ANY events?
		$user_can_view_events = false;
		if ( $auth->acl_get('u_raidplanner_view_events') )
		{
			$user_can_view_events = true;
	
			/* find the group options here so we do not have to look them up again for each day */
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
		}
		$disp_events_only_on_start = $config['rp_disp_events_only_on_start'];
	
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
			
			if ( $auth->acl_gets('u_raidplanner_create_public_events', 'u_raidplanner_create_group_events', 'u_raidplanner_create_private_events') )
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
	
			if ( $user_can_view_events && $auth->acl_get('u_viewprofile') )
			{
				// find birthdays
				$calendar_days['BIRTHDAYS'] = $this->generate_birthday_list( $j, $this->date['month_no'], $this->date['year'] );
			}
	
			$template->assign_block_vars('calendar_days', $calendar_days);
	
			if ( $user_can_view_events )
			{
				//find any events on this day
				$start_temp_date = gmmktime(0,0,0,$this->date['month_no'], $j, $this->date['year'])  - $user->timezone - $user->dst;
				$end_temp_date = $start_temp_date + 86399;
	
				if( $disp_events_only_on_start == 0 )
				{
	
					$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
							WHERE ( (event_access_level = 2) OR
								(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
								(event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
							((( event_start_time >= '.$db->sql_escape($start_temp_date).' AND event_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
							 ( event_end_time > '.$db->sql_escape($start_temp_date).' AND event_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
							 ( event_start_time < '.$db->sql_escape($start_temp_date).' AND event_end_time > '.$db->sql_escape($end_temp_date)." )) OR
							 ((event_all_day = 1) AND (event_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $j, $this->date['month_no'], $this->date['year'])) . "'))) ORDER BY event_start_time ASC";
				}
				else
				{
	
					$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
							WHERE ( (event_access_level = 2) OR
								(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
								(event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
								(( event_start_time >= '.$db->sql_escape($start_temp_date).' AND event_start_time <= '.$db->sql_escape($end_temp_date)." ) OR
							 	((event_all_day = 1) AND (event_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $j, $this->date['month_no'], $this->date['year'])) . "'))) ORDER BY event_start_time ASC";
	
				}
	
	
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$event_output['COLOR'] = $this->available_etype_colors[$row['etype_id']];
					$event_output['IMAGE'] = $this->available_etype_images[$row['etype_id']];
					$event_output['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=event&amp;calEid=".$row['event_id'].$etype_url_opts);
	
					// if the event was created by this user
					// display it in bold
					if( $user->data['user_id'] == $row['poster_id'] )
					{
						$event_output['DISPLAY_BOLD'] = true;
					}
					else
					{
						$event_output['DISPLAY_BOLD'] = false;
					}
	
					$event_output['ETYPE_DISPLAY_NAME'] = $this->available_etype_display_names[$row['etype_id']];
	
					$event_output['FULL_SUBJECT'] = censor_text($row['event_subject']);
					$event_output['EVENT_SUBJECT'] = $event_output['FULL_SUBJECT'];
					if( $subject_limit > 0 )
					{
						if(utf8_strlen($event_output['EVENT_SUBJECT']) > $subject_limit)
						{
							$event_output['EVENT_SUBJECT'] = truncate_string($event_output['EVENT_SUBJECT'], $subject_limit) . '...';
						}
					}
					$template->assign_block_vars('calendar_days.events', $event_output);
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
			/* get current weekday so we show this upcoming week's events */
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
	
	
		// Is the user able to view ANY events?
		$user_can_view_events = false;
		if ( $auth->acl_get('u_raidplanner_view_events') )
		{
			$user_can_view_events = true;
	
			/* find the group options here so we do not have to look them up again for each day */
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
		}
	
		$disp_events_only_on_start = $config['rp_disp_events_only_on_start'];
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
			//if( $auth->acl_get('u_raidplanner_create_events') )
			if ( $auth->acl_gets('u_raidplanner_create_public_events', 'u_raidplanner_create_group_events', 'u_raidplanner_create_private_events') )
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
			if ( $user_can_view_events && $auth->acl_get('u_viewprofile') )
			{
				// find birthdays
				$calendar_days['BIRTHDAYS'] = $this->generate_birthday_list( $true_j, $true_m, $true_y );
			}
	
			$template->assign_block_vars('calendar_days', $calendar_days);
	
			if ( $user_can_view_events )
			{
				//find any events on this day
				$start_temp_date = gmmktime(0,0,0,$true_m, $true_j, $true_y)  - $user->timezone - $user->dst;
	
				$end_temp_date = $start_temp_date + 86399;
	
				if( $disp_events_only_on_start == 0 )
				{
					$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
							WHERE ( (event_access_level = 2) OR
									(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
									(event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
								((( event_start_time >= '.$db->sql_escape($start_temp_date).' AND event_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
								 ( event_end_time > '.$db->sql_escape($start_temp_date).' AND event_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
								 ( event_start_time < '.$db->sql_escape($start_temp_date).' AND event_end_time > '.$db->sql_escape($end_temp_date)." )) OR
								 ((event_all_day = 1) AND (event_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $true_j, $true_m, $true_y)) . "'))) ORDER BY event_start_time ASC";
				}
				else
				{
	
					$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
							WHERE ( (event_access_level = 2) OR
									(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
									(event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
								 (( event_start_time >= '.$db->sql_escape($start_temp_date).' AND event_start_time <= '.$db->sql_escape($end_temp_date)." ) OR
								 ((event_all_day = 1) AND (event_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $true_j, $true_m, $true_y)) . "'))) ORDER BY event_start_time ASC";
	
				}
	
	
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$event_output['COLOR'] = $this->available_etype_colors[$row['etype_id']];
					$event_output['IMAGE'] = $this->available_etype_images[$row['etype_id']];
					$event_output['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=event&amp;calEid=".$row['event_id'].$this->etype_url_opts);
	
					// if the event was created by this user
					// display it in bold
					if( $user->data['user_id'] == $row['poster_id'] )
					{
						$event_output['DISPLAY_BOLD'] = true;
					}
					else
					{
						$event_output['DISPLAY_BOLD'] = false;
					}
					$event_output['ETYPE_DISPLAY_NAME'] = $this->available_etype_display_names[$row['etype_id']];
	
					$event_output['FULL_SUBJECT'] = censor_text($row['event_subject']);
					$event_output['EVENT_SUBJECT'] = $event_output['FULL_SUBJECT'];
					if( $subject_limit > 0 )
					{
						if(utf8_strlen($event_output['EVENT_SUBJECT']) > $subject_limit)
						{
							$event_output['EVENT_SUBJECT'] = truncate_string($event_output['EVENT_SUBJECT'], $subject_limit) . '...';
						}
					}
	
					$event_output['SHOW_TIME'] = true;
					if( $row['event_all_day'] == 1 )
					{
						$event_output['ALL_DAY'] = true;
					}
					else
					{
						$event_output['ALL_DAY'] = false;
						$correct_format = $disp_time_format;
						if( $row['event_end_time'] - $row['event_start_time'] > 86400 )
						{
							$correct_format = $disp_date_time_format;
						}
						$event_output['START_TIME'] = $user->format_date($row['event_start_time'], $correct_format, true);
						$event_output['END_TIME'] = $user->format_date($row['event_end_time'], $correct_format, true);
					}
	
					$template->assign_block_vars('calendar_days.events', $event_output);
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
	
		//$disp_events_only_on_start = $config['rp_disp_events_only_on_start", 0);
		// the day view is a graphical layout... we probably want to ignore the "display only on start rule" here
		$disp_events_only_on_start = 0;
		$disp_time_format = $config['rp_time_format']; 
		$disp_date_time_format = $config['rp_date_time_format'];
	
	    $event_counter = 0;
		// Is the user able to view ANY events?
		if ( $auth->acl_get('u_raidplanner_view_events') )
		{
			// find birthdays
			if( $auth->acl_get('u_viewprofile') )
			{
				$birthday_list = $this->generate_birthday_list( $this->date['day'], $this->date['month_no'], $this->date['year'] );
				if( $birthday_list != "" )
				{
					$events['PRE_PADDING'] = "";
					$events['PADDING'] = "96";
					$events['DATA'] = $birthday_list;
					$events['POST_PADDING'] = "";
					$template->assign_block_vars('events', $events);
					$event_counter++;
				}
			}
	
	
			//find any events on this day
			$start_temp_date = gmmktime(0,0,0,$this->date['month_no'], $this->date['day'], $this->date['year'])  - $user->timezone - $user->dst;
			$end_temp_date = $start_temp_date + 86399;
	
	
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
			if( $disp_events_only_on_start == 0 )
			{
				$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
						WHERE ( (event_access_level = 2) OR
								(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
								(event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
						((( event_start_time >= '.$db->sql_escape($start_temp_date).' AND event_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
						 ( event_end_time > '.$db->sql_escape($start_temp_date).' AND event_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
						 ( event_start_time < '.$db->sql_escape($start_temp_date).' AND event_end_time > '.$db->sql_escape($end_temp_date)." )) OR
						 ((event_all_day = 1) AND (event_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $this->date['day'], $this->date['month_no'], $this->date['year'])) . "'))) ORDER BY event_start_time ASC";
			}
			else
			{
				$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
						WHERE ( (event_access_level = 2) OR
								(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
								(event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
						(( event_start_time >= '.$db->sql_escape($start_temp_date).' AND event_start_time <= '.$db->sql_escape($end_temp_date)." ) OR
						 ((event_all_day = 1) AND (event_day LIKE '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $this->date['day'], $this->date['month_no'], $this->date['year'])) . "'))) ORDER BY event_start_time ASC";
	
			}
	
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$pre_padding = 0;
				$padding = 0;
				$post_padding = 0;
				$events['PRE_PADDING'] = "";
				$events['PADDING'] = "";
				$events['POST_PADDING'] = "";
				$events['COLOR'] = $this->available_etype_colors[$row['etype_id']];
				$events['IMAGE'] = $this->available_etype_images[$row['etype_id']];
				$events['EVENT_URL'] = append_sid("{$phpbb_root_path}planner.$phpEx", "view=event&amp;calEid=".$row['event_id']. $etype_url_opts);
				// if the event was created by this user
				// display it in bold
				if( $user->data['user_id'] == $row['poster_id'] )
				{
					$events['DISPLAY_BOLD'] = true;
				}
				else
				{
					$events['DISPLAY_BOLD'] = false;
				}
	
				$events['ETYPE_DISPLAY_NAME'] = $this->available_etype_display_names[$row['etype_id']];
	
				$events['FULL_SUBJECT'] = censor_text($row['event_subject']);
				$events['EVENT_SUBJECT'] = $events['FULL_SUBJECT'];
				if( $subject_limit > 0 )
				{
					if(utf8_strlen($events['EVENT_SUBJECT']) > $subject_limit)
					{
						$events['EVENT_SUBJECT'] = truncate_string($events['EVENT_SUBJECT'], $subject_limit) . '...';
					}
				}
	
				if( $row['event_all_day'] == 1 )
				{
					$events['ALL_DAY'] = true;
					$events['PADDING'] = "96";
				}
				else
				{
					$events['ALL_DAY'] = false;
					$correct_format = $disp_time_format;
					if( $row['event_end_time'] - $row['event_start_time'] > 86400 )
					{
						$correct_format = $this->disp_date_time_format;
					}
					$events['START_TIME'] = $user->format_date($row['event_start_time'], $correct_format, true);
					$events['END_TIME'] = $user->format_date($row['event_end_time'], $correct_format, true);
	
	
					if( $row['event_start_time'] > $start_temp_date )
					{
						// find pre-padding value...
						$start_diff = $row['event_start_time'] - $start_temp_date;
						$pre_padding = round($start_diff/900);
						if( $pre_padding > 0 )
						{
							$events['PRE_PADDING'] = $pre_padding;
						}
					}
					if( $row['event_end_time'] < $end_temp_date )
					{
						// find pre-padding value...
						$end_diff = $end_temp_date - $row['event_end_time'];
						$post_padding = round($end_diff/900);
						if( $post_padding > 0 )
						{
							$events['POST_PADDING'] = $post_padding;
						}
					}
					$events['PADDING'] = 96 - $pre_padding - $post_padding;
	
				}
				$template->assign_block_vars('events', $events);
				$event_counter++;
			}
			$db->sql_freeresult($result);
		}
	
		$week_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		$month_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=month&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		$add_event_url = "";
	
		if ( $auth->acl_gets('u_raidplanner_create_public_events', 'u_raidplanner_create_group_events', 'u_raidplanner_create_private_events') )
		{
			$add_event_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=post&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		}
	
		// A typical usage for sending your variables to your template.
		$template->assign_vars(array(
			'CALENDAR_HEADER'	=> $calendar_header_txt,
			'WEEK_IMG'			=> $user->img('button_calendar_week', 'WEEK'),
			'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
			'ADD_LINK'			=> $add_event_url,
			'WEEK_VIEW_URL'		=> $week_view_url,
			'MONTH_VIEW_URL'	=> $month_view_url,
			'CALENDAR_PREV'		=> $prev_link,
			'CALENDAR_NEXT'		=> $next_link,
			'CALENDAR_VIEW_OPTIONS' => $this->mode_sel_code.' '.$this->month_sel_code.' '.$this->day_sel_code.' '.$this->year_sel_code,
			'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}planner.$phpEx", $this->get_etype_post_opts() ),
			'EVENT_COUNT'		=> $event_counter,
		));

	}
	
	public function display_event()
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
	
		$this->_init_calendar_data();
		$etype_url_opts = $this->get_etype_url_opts();

		$event_id = request_var('calEid', 0);
		$event_display_name = "";
		$event_color = "";
		$event_image = "";
		$event_details = "";
		$all_day = 1;
		$start_date_txt = "";
		$end_date_txt = "";
		$subject="";
		$message="";
		$back_url = append_sid("{$phpbb_root_path}planner.$phpEx", "calD=".$this->date['day']."&amp;calM=".
				$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts );
		
		if( $event_id > 0 )
		{
			$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
					WHERE event_id = '.$db->sql_escape($event_id);
			$result = $db->sql_query($sql);
			$event_data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if( !$event_data )
			{
				trigger_error( 'INVALID_EVENT' );
			}
	
			// Is the user able to view ANY events?
			if ( !$auth->acl_get('u_raidplanner_view_events') )
			{
				trigger_error( 'USER_CANNOT_VIEW_EVENT' );
			}
			
			// Is user authorized to view THIS event?
			$user_auth_for_event = $this->is_user_authorized_to_view_event( $user->data['user_id'], $event_data);
			if( $user_auth_for_event == 0 )
			{
				trigger_error( 'PRIVATE_EVENT' );
			}
			if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
			{
				$calWatchE = request_var( 'calWatchE', 2 );
				$watchclass = new calendar_watch();
				
				if( $calWatchE < 2 )
				{
					$watchclass->calendar_watch_event( $event_id, $calWatchE );
				}
				else
				{
					$watchclass->calendar_mark_user_read_event( $event_id, $user->data['user_id'] );
				}
			}
	
		    $disp_date_format = $config['rp_date_format'];
		    $disp_date_time_format = $config['rp_date_time_format'];
	
			$start_date_txt = $user->format_date($event_data['event_start_time'], $disp_date_time_format, true);
			$end_date_txt = $user->format_date($event_data['event_end_time'], $disp_date_time_format, true);
	
			// translate event start and end time into user's timezone
			$event_start = $event_data['event_start_time'] + $user->timezone + $user->dst;
			$event_end = $event_data['event_end_time'] + $user->timezone + $user->dst;
	
			if( $event_data['event_all_day'] == 1 )
			{
				// All day event - find the string for the event day
				if ($event_data['event_day'])
				{
					list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $event_data['event_day']);
	
					$event_days_time = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
					$start_date_txt = $user->format_date($event_days_time, $disp_date_format, true);
					$this->date['day'] = $eday['eday_day'];
					$this->date['month_no'] = $eday['eday_month'];
					$this->date['year'] = $eday['eday_year'];
				}
				else
				{
					// We should never get here
					// (this would be an all day event with no specified day for the event)
					$start_date_txt = "";
				}
			}
			else
			{
				$all_day = 0;
				$this->date['day'] = gmdate("d", $event_start);
				$this->date['month_no'] = gmdate("n", $event_start);
				$this->date['year']	=	gmdate('Y', $event_start);
			}
			$back_url = append_sid("{$phpbb_root_path}planner.$phpEx", "calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts );
	
			$event_body = $event_data['event_body'];
			$event_data['bbcode_options'] = (($event_data['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +   
			 (($event_data['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +     (($event_data['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
	
			$message = generate_text_for_display($event_body, $event_data['bbcode_uid'], $event_data['bbcode_bitfield'], $event_data['bbcode_options']);
			$event_display_name = $this->available_etype_display_names[$event_data['etype_id']];
			$event_color = $this->available_etype_colors[$event_data['etype_id']];
			$event_image = $this->available_etype_images[$event_data['etype_id']];
	
			$subject = censor_text($event_data['event_subject']);
	
			$poster_url = '';
			$invite_list = '';
			
			$raidevents = new raidevents();
			$raidevents->get_event_invite_list_and_poster_url($event_data, $poster_url, $invite_list );
	
			$edit_url = "";
			$edit_all_url = "";
			if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_events') &&
			    (($user->data['user_id'] == $event_data['poster_id'])|| $auth->acl_get('m_raidplanner_edit_other_users_events') ))
			{
				$edit_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=edit&amp;calEid=".$event_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
				if( $event_data['recurr_id'] > 0 )
				{
					$edit_all_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=edit&amp;calEditAll=1&amp;calEid=".$event_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
				}
			}
			$delete_url = "";
			$delete_all_url = "";
			if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_events') &&
			    (($user->data['user_id'] == $event_data['poster_id'])|| $auth->acl_get('m_raidplanner_delete_other_users_events') ))
			{
				$delete_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=delete&amp;calEid=".$event_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
				if( $event_data['recurr_id'] > 0 )
				{
					$delete_all_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=delete&amp;calDelAll=1&amp;calEid=".$event_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
				}
			}

			// does this event have attendance tracking turned on?
			if( $event_data['track_rsvps'] == 1 )
			{
				$rsvp_id	= request_var('rsvp_id', 0);
				$submit		= (isset($_POST['post'])) ? true : false;
				$rsvp_data = array();
				if( $rsvp_id !== 0 )
				{
					$this->get_rsvp_data( $rsvp_id, $rsvp_data );
					if( $rsvp_data['event_id'] != $event_id )
					{
						trigger_error('NO_RSVP');
					}
				}
				else
				{
					$rsvp_data['rsvp_id'] = 0;
					$rsvp_data['event_id'] = $event_id;
					$rsvp_data['poster_id'] = $user->data['user_id'];
					$rsvp_data['poster_name'] = $user->data['username'];
					$rsvp_data['poster_colour'] = $user->data['user_colour'];
					$rsvp_data['poster_ip'] = $user->ip;
					$rsvp_data['post_time'] = time();
					$rsvp_data['rsvp_val'] = 2;
					$rsvp_data['rsvp_count'] = 1;
					$rsvp_data['rsvp_detail'] = "";
					$rsvp_data['rsvp_detail_edit'] = "";
				}
	
	
				// Can we edit this reply ... if we're a moderator with rights then always yes
				// else it depends on editing times, lock status and if we're the correct user
				if ( $rsvp_id !== 0 && !$auth->acl_get('m_raidplanner_edit_other_users_rsvps'))
				{
					if ($user->data['user_id'] != $rsvp_data['poster_id'])
					{
						trigger_error('USER_CANNOT_EDIT_RSVP');
					}
				}
	
				if( $submit )
				{
					// what were the old event_data head counts?
					$old_yes_count = $event_data['rsvp_yes'];
					$old_no_count = $event_data['rsvp_no'];
					$old_maybe_count = $event_data['rsvp_maybe'];
	
					$old_user_yes_count = 0;
					$old_user_maybe_count = 0;
					$old_user_no_count = 0;
	
					$new_rsvp_val	= request_var('rsvp_val', 2);
					$new_rsvp_count	= request_var('rsvp_count', 1);
					$new_rsvp_detail = utf8_normalize_nfc( request_var('rsvp_detail', '', true) );
	
					$uid = $bitfield = $options = '';
					$allow_bbcode = $allow_urls = $allow_smilies = true;
					generate_text_for_storage($new_rsvp_detail, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
	
					$new_user_yes_count = 0;
					$new_user_maybe_count = 0;
					$new_user_no_count = 0;
	
					if( $rsvp_id !== 0 )
					{
						if( $rsvp_data['rsvp_val'] == 0 )
						{
							$old_user_yes_count = $rsvp_data['rsvp_count'];
						}
						else if( $rsvp_data['rsvp_val'] == 1 )
						{
							$old_user_no_count = $rsvp_data['rsvp_count'];
						}
						else
						{
							$old_user_maybe_count = $rsvp_data['rsvp_count'];
						}
					}
					// don't allow guests, unless the event organizer gave the OK
					if( $event_data['allow_guests'] != 1 && $new_rsvp_count > 1 )
					{
					    $new_rsvp_count = 1;
					}
					if( $new_rsvp_val == 0 )
					{
						$new_user_yes_count = $new_rsvp_count;
					}
					else if( $new_rsvp_val == 1 )
					{
						$new_user_no_count = $new_rsvp_count;
					}
					else
					{
						$new_user_maybe_count = $new_rsvp_count;
					}
	
					$new_yes_count = $old_yes_count - $old_user_yes_count + $new_user_yes_count;
					$new_no_count = $old_no_count - $old_user_no_count + $new_user_no_count;
					$new_maybe_count = $old_maybe_count - $old_user_maybe_count + $new_user_maybe_count;
	
	
					// save the user's rsvp data...
	
					// update the ip address and time
					$rsvp_data['poster_ip'] = $user->ip;
					$rsvp_data['post_time'] = time();
					$rsvp_data['rsvp_val'] = $new_rsvp_val;
					$rsvp_data['rsvp_count'] = $new_rsvp_count;
					$rsvp_data['rsvp_detail'] = $new_rsvp_detail;
					if( $rsvp_id > 0 )
					{
						$sql = 'UPDATE ' . RP_RSVP_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', array(
								'poster_id'			=> (int) $rsvp_data['poster_id'],
								'poster_name'		=> (string) $rsvp_data['poster_name'],
								'poster_colour'		=> (string) $rsvp_data['poster_colour'],
								'poster_ip'			=> (string) $rsvp_data['poster_ip'],
								'post_time'			=> (int) $rsvp_data['post_time'],
								'rsvp_val'				=> (int) $rsvp_data['rsvp_val'],
								'rsvp_count'			=> (int) $rsvp_data['rsvp_count'],
								'rsvp_detail'			=> (string) $rsvp_data['rsvp_detail'],
								'bbcode_bitfield'	=> $bitfield,
								'bbcode_uid'		=> $uid,
								'bbcode_options'	=> $options,
								)) . "
							WHERE rsvp_id = $rsvp_id";
						$db->sql_query($sql);
					}
					else
					{
						$sql = 'INSERT INTO ' . RP_RSVP_TABLE . ' ' . $db->sql_build_array('INSERT', array(
								'event_id'			=> (int) $rsvp_data['event_id'],
								'poster_id'			=> (int) $rsvp_data['poster_id'],
								'poster_name'		=> (string) $rsvp_data['poster_name'],
								'poster_colour'		=> (string) $rsvp_data['poster_colour'],
								'poster_ip'			=> (string) $rsvp_data['poster_ip'],
								'post_time'			=> (int) $rsvp_data['post_time'],
								'rsvp_val'				=> (int) $rsvp_data['rsvp_val'],
								'rsvp_count'			=> (int) $rsvp_data['rsvp_count'],
								'rsvp_detail'			=> (string) $rsvp_data['rsvp_detail'],
								'bbcode_bitfield'	=> $bitfield,
								'bbcode_uid'		=> $uid,
								'bbcode_options'	=> $options,
								)
							);
						$db->sql_query($sql);
						//$rsvp_id = $db->sql_nextid();
					}
					// update the event id's rsvp stats
						$sql = 'UPDATE ' . RP_EVENTS_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', array(
								'rsvp_yes'		=> (int) $new_yes_count,
								'rsvp_no'		=> (int) $new_no_count,
								'rsvp_maybe'	=> (int) $new_maybe_count,
								)) . "
							WHERE event_id = $event_id";
						$db->sql_query($sql);
					$event_data['rsvp_yes'] = $new_yes_count;
					$event_data['rsvp_no'] = $new_no_count;
					$event_data['rsvp_maybe'] = $new_maybe_count;
					
						
					$this->calendar_add_or_update_reply( $event_id );
				
				}
	
				$sql = 'SELECT * FROM ' . RP_RSVP_TABLE . '
						WHERE event_id = '.$db->sql_escape($event_id). ' ORDER BY rsvp_val ASC';
				$result = $db->sql_query($sql);
	
				$edit_rsvps = 0;
				if( $auth->acl_get('m_raidplanner_edit_other_users_rsvps') )
				{
					$edit_rsvps = 1;
					$edit_rsvp_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=event&amp;calEid=".$event_id.$etype_url_opts );
					$edit_rsvp_url .="&amp;rsvp_id=";
				}
	
				while ($rsvp_row = $db->sql_fetchrow($result) )
				{
					if( ($rsvp_id == 0 && $rsvp_data['poster_id'] == $rsvp_row['poster_id']) ||
					    ($rsvp_id != 0 && $rsvp_id == $rsvp_row['rsvp_id']) )
					{
						$rsvp_data['rsvp_id'] = $rsvp_row['rsvp_id'];
						$rsvp_data['post_time'] = $rsvp_row['post_time'];
						$rsvp_data['rsvp_val'] = $rsvp_row['rsvp_val'];
						$rsvp_data['rsvp_count'] = $rsvp_row['rsvp_count'];
						$edit_text_array = generate_text_for_edit( $rsvp_row['rsvp_detail'], $rsvp_row['bbcode_uid'], $rsvp_row['bbcode_options']);
						$rsvp_data['rsvp_detail_edit'] = $edit_text_array['text'];
					}
					$rsvp_out['POSTER'] = $rsvp_row['poster_name'];
					$rsvp_out['POSTER_URL'] = get_username_string( 'full', $rsvp_row['poster_id'], $rsvp_row['poster_name'], $rsvp_row['poster_colour'] );
					$rsvp_out['VALUE'] = $rsvp_row['rsvp_val'];
					if( $rsvp_row['rsvp_val'] == 0 )
					{
						$rsvp_out['COLOR'] = '#00ff00';
						$rsvp_out['VALUE_TXT'] = $user->lang['YES'];
					}
					else if( $rsvp_row['rsvp_val'] == 1 )
					{
						$rsvp_out['COLOR'] = '#ff0000';
						$rsvp_out['VALUE_TXT'] = $user->lang['NO'];
					}
					else
					{
						$rsvp_out['COLOR'] = '#0000ff';
						$rsvp_out['VALUE_TXT'] = $user->lang['MAYBE'];
					}
					$rsvp_out['U_EDIT'] = "";
					if( $edit_rsvps === 1 )
					{
						$rsvp_out['U_EDIT'] = $edit_rsvp_url . $rsvp_row['rsvp_id'];
					}
					$rsvp_out['HEADCOUNT'] = $rsvp_row['rsvp_count'];
					$rsvp_out['DETAILS'] = generate_text_for_display($rsvp_row['rsvp_detail'], $rsvp_row['bbcode_uid'], $rsvp_row['bbcode_bitfield'], $rsvp_row['bbcode_options']);
					$rsvp_out['POST_TIMESTAMP'] = $rsvp_row['post_time'];
					$rsvp_out['POST_TIME'] = $user->format_date($rsvp_row['post_time']);
					$template->assign_block_vars('rsvps', $rsvp_out);
	
				}
				$db->sql_freeresult($result);
				$show_current_response = 0;
				if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
				{
					$show_current_response = 1;
					$sel_attend_code  = "<select name='rsvp_val' id='rsvp_val''>\n";
					$sel_attend_code .= "<option value='0'>".$user->lang['YES']."</option>\n";
					$sel_attend_code .= "<option value='1'>".$user->lang['NO']."</option>\n";
					$sel_attend_code .= "<option value='2'>".$user->lang['MAYBE']."</option>\n";
					$sel_attend_code .= "</select>\n";
	
					$temp_find_str = "value='".$rsvp_data['rsvp_val']."'";
					$temp_replace_str = "value='".$rsvp_data['rsvp_val']."' selected='selected'";
					$sel_attend_code = str_replace( $temp_find_str, $temp_replace_str, $sel_attend_code );
	
					$template->assign_vars( array(
						'S_RSVP_MODE_ACTION'=> append_sid("{$phpbb_root_path}planner.$phpEx", "view=event&amp;calEid=".$event_id.$etype_url_opts ),
						'S_CURRENT_RSVP'	=> $show_current_response,
						'S_EDIT_RSVP'		=> $edit_rsvps,
						'CURR_RSVP_ID'		=> $rsvp_data['rsvp_id'],
						'CURR_POSTER_URL'	=> get_username_string( 'full', $rsvp_data['poster_id'], $rsvp_data['poster_name'], $rsvp_data['poster_colour'] ),
						'CURR_RSVP_COUNT'	=> $rsvp_data['rsvp_count'],
						'CURR_RSVP_DETAIL'	=> $rsvp_data['rsvp_detail_edit'],
						'SEL_ATTEND'		=> $sel_attend_code,
						)
					);
	
				}
				$template->assign_vars( array(
					'CURR_YES_COUNT'	=> $event_data['rsvp_yes'],
					'CURR_NO_COUNT'		=> $event_data['rsvp_no'],
					'CURR_MAYBE_COUNT'	=> $event_data['rsvp_maybe'],
					)
				);
			}
		
			$add_event_url = "";
			//if( $auth->acl_get('u_raidplanner_create_events') )
			if ( $auth->acl_gets('u_raidplanner_create_public_events', 'u_raidplanner_create_group_events', 'u_raidplanner_create_private_events') )
			{
				$add_event_url = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=post&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			}
			$day_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=day&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			$week_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			$month_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=month&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
	
			$s_rsvp_headcount = false;
			if( ($user->data['user_id'] == $event_data['poster_id'])|| $auth->acl_get('u_raidplanner_view_headcount') )
			{
				$s_rsvp_headcount = true;
			}
			$s_rsvp_details = false;
			if( ($user->data['user_id'] == $event_data['poster_id'])|| $auth->acl_get('u_raidplanner_view_detailed_rsvps') )
			{
				$s_rsvp_details = true;
			}
			$s_watching_event = array();
			$this->calendar_init_s_watching_event_data( $event_id, $s_watching_event );
	
			$template->assign_vars(array(
				'U_CALENDAR'		=> $back_url,
				'ETYPE_DISPLAY_NAME'=> $event_display_name,
				'EVENT_COLOR'		=> $event_color,
				'EVENT_IMAGE'		=> $event_image,
				'SUBJECT'			=> $subject,
				'MESSAGE'			=> $message,
				'START_DATE'		=> $start_date_txt,
				'END_DATE'			=> $end_date_txt,
				'IS_RECURRING'		=> $event_data['recurr_id'],
				'RECURRING_TXT'		=> $this->get_recurring_event_string_via_id( $event_data['recurr_id'] ),
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
				'ADD_LINK'			=> $add_event_url,
				'DAY_VIEW_URL'		=> $day_view_url,
				'WEEK_VIEW_URL'		=> $week_view_url,
				'MONTH_VIEW_URL'	=> $month_view_url,
				'S_CALENDAR_RSVPS'	=> $event_data['track_rsvps'],
				'S_RSVP_HEADCOUNT'	=> $s_rsvp_headcount,
				'S_RSVP_DETAILS'	=> $s_rsvp_details,
				'S_ALLOW_GUESTS'	=> $event_data['allow_guests'],
	
	
				'U_WATCH_EVENT' 		=> $s_watching_event['link'],
				'L_WATCH_EVENT' 		=> $s_watching_event['title'],
				'S_WATCHING_EVENT'		=> $s_watching_event['is_watching'],
	
				)
			);
		}
		
		
	}
	
	
	
		
	/* calendar_init_s_watching_event_data()
	**
	** Determines if the current user is watching the specified event, and
	** generates the data required for the overall_footer to display
	** the watch/unwatch link.
	**
	** INPUT
	**   $event_id - event currently being displayed
	**
	** OUTPUT
	**   $s_watching_event - filled with data for the overall_footer template
	*/
	function calendar_init_s_watching_event_data( $event_id, &$s_watching_event )
	{
		global $db, $user;
		global $phpEx, $phpbb_root_path;
	
		$s_watching_event['link'] = "";
		$s_watching_event['title'] = "";
		$s_watching_event['is_watching'] = false;
		if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
		{
			$sql = 'SELECT * FROM ' . RP_EVENTS_WATCH . '
				WHERE user_id = '.$user->data['user_id'].' AND event_id = ' .$event_id;
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$s_watching_event['is_watching'] = true;
			}
			$db->sql_freeresult($result);
			if( $s_watching_event['is_watching'] )
			{
				$s_watching_event['link'] = append_sid( "{$phpbb_root_path}planner.$phpEx", "view=event&amp;calEid=".$event_id."&amp;calWatchE=0" );
				$s_watching_event['title'] = $user->lang['WATCH_EVENT_TURN_OFF'];
			}
			else
			{
				$s_watching_event['link'] = append_sid( "{$phpbb_root_path}planner.$phpEx", "view=event&amp;calEid=".$event_id."&amp;calWatchE=1" );
				$s_watching_event['title'] = $user->lang['WATCH_EVENT_TURN_ON'];
			}
		}
	}
		
	
	/* get_rsvp_data()
	**
	** Gets the rsvp data for the selected rsvp id
	*/
	function get_rsvp_data( $id, &$rsvp_data )
	{
		global $auth, $db, $user;
		if( $id < 1 )
		{
			trigger_error('NO_RSVP');
		}
		$sql = 'SELECT * FROM ' . RP_RSVP_TABLE . '
				WHERE rsvp_id = '.$db->sql_escape($id);
		$result = $db->sql_query($sql);
		$rsvp_data = $db->sql_fetchrow($result);
		if( !$rsvp_data )
		{
			trigger_error('NO_RSVP');
		}
	
	    $db->sql_freeresult($result);
	    $rsvp_data['rsvp_detail_edit'] = "";
	}
		
	
	
	
		
	/* get_recurring_event_string_via_id()
	**
	** Gets the displayable string that describes the frequency of a
	** recurring event
	**
	** INPUT
	**   $recurr_id - the recurring event id.
	*/
	private function get_recurring_event_string_via_id( $recurr_id )
	{
		global $db, $user;
	
		$string = "";
	
		if( $recurr_id == 0 )
		{
			return $string;
		}
	
		$sql = 'SELECT * FROM ' . RP_RECURRING_EVENTS_TABLE ."
				WHERE recurr_id ='".$db->sql_escape($recurr_id)."'";
		$result = $db->sql_query($sql);
		if($row = $db->sql_fetchrow($result))
		{
			$string = $this->get_recurring_event_string( $row );
		}
		$db->sql_freeresult($result);
	
		return $string;
	}
	
	/* get_recurring_event_string()
	**
	** Gets the displayable string that describes the frequency of a
	** recurring event
	**
	** INPUT
	**   $row - the row of data from the RP_RECURRING_EVENTS_TABLE
	**          describing this recurring event.
	*/
	private function get_recurring_event_string( $row )
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
	
		if( $row['event_all_day'] == 0 )
		{
			$timestamp = $timestamp + (($row['poster_timezone'] + $row['poster_dst'])*3600);
	
			// we only need to display a timezone reference if it's different from the viewer
			// and it's a timed (not all day) event
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
		
	
	
	
	/* displays the upcoming events for the next x number of days */
	public function display_next_events_for_x_days( $x )
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
		$etype_url_opts = $this->get_etype_url_opts();
	
		// Is the user able to view ANY events?
		$user_can_view_events = false;
		if ( $auth->acl_get('u_raidplanner_view_events') )
		{
	
			$this->_init_calendar_data();
			$subject_limit = $config['rp_display_truncated_name'];
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
	
			$start_temp_date = time();
			//$end_temp_date = $start_temp_date + 31536000;
			$end_temp_date = $start_temp_date + ( $x * 86400 );
			// find all day events that are still taking place
			$sort_timestamp_cutoff = $start_temp_date - 86400+1;
	
		    $disp_date_format = $config['rp_date_format'];
		    $disp_date_time_format = $config['rp_date_time_format'];
	
			// don't list events that are more than 1 year in the future
			$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
					WHERE ( (event_access_level = 2) OR
						(poster_id = '.$db->sql_escape($user->data['user_id']).' ) 
						OR (event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' 
					AND ((( event_start_time >= '.$db->sql_escape($start_temp_date).' 
					AND event_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
					 	(event_end_time > '.$db->sql_escape($start_temp_date).' 
					AND event_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
					 	(event_start_time < '.$db->sql_escape($start_temp_date).' 
					AND event_end_time > '.$db->sql_escape($end_temp_date)." )) OR (sort_timestamp > ".$db->sql_escape($sort_timestamp_cutoff)." 
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
				//$events['START_TIME'] = $user->format_date($row['event_start_time']);
				//$events['END_TIME'] = $user->format_date($row['event_end_time']);
				$template->assign_block_vars('events', $events);
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
	
		// what day of the week are we starting on?
		if (phpversion() < '5.1')
		{
			switch(gmdate("l",gmmktime(0,0,0, $month, $day, $year)))
			{
				case "Monday":
					$fday = 1;
					break;
				case "Tuesday":
					$fday = 2;
					break;
				case "Wednesday":
					$fday = 3;
					break;
				case "Thursday":
					$fday = 4;
					break;
				case "Friday":
					$fday = 5;
					break;
				case "Saturday":
					$fday = 6;
					break;
				case "Sunday":
					$fday = 7;
					break;
			}
		}
		else
		{
			$fday = gmdate("N",gmmktime(0,0,0, $month, $day, $year));
		}
		$fday = $fday - $first_day_of_week;
		if( $fday < 0 )
		{
			$fday = $fday + 7;
		}
		return $fday;
	}
		
				
	/* we need to find out what group this user is a member of,
	   and create a list of or options for an sql command so we can
	   find events for all of the groups this user is a member of.
	*/
	private function get_sql_group_options($user_id)
	{
		global $auth, $db;
	
		// What groups is this user a member of?
	
		/* don't check for hidden group setting -
		  if the event was made by the admin for a hidden group -
		  members of the hidden group need to be able to see the event in the calendar */
	
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
	
	/*------------------------------------------------------
	  Begin helper functions for filtering the calendar
	  display based on a specified event type.
	------------------------------------------------------*/
	private function get_etype_filter()
	{
		global $db;
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			return "";
		}
		return " AND etype_id = ".$db->sql_escape($calEType)." ";
	}
	
	private function get_etype_url_opts()
	{
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			return "";
		}
		return "&amp;calEType=".$calEType;
	}
	
	private function get_etype_post_opts()
	{
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			return "";
		}
		return "calEType=".$calEType;
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
		
	
	
	/* is_user_authorized_to_view_event()
	**
	** Is the specified user allowed to view the event defined
	** by the given event_data?
	*/
	private function is_user_authorized_to_view_event($user_id, $event_data)
	{
		global $auth, $db;
		$user_auth_for_event = 0;
	
		// no matter what the author can always see their own events
		if( $user_id === $event_data['poster_id'] )
		{
			$user_auth_for_event = 1;
		}
		else
		{
			switch( $event_data['event_access_level'] )
			{
				case 0:
					// personal event... only event creator is invited
					break;
				case 1:
					// group event... only members of specified group are invited
					// is this user a member of the group?
					if( $event_data['group_id'] != 0 )
					{
						$sql = 'SELECT g.group_id
								FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
								WHERE ug.user_id = '.$db->sql_escape($user_id).'
									AND g.group_id = ug.group_id
									AND g.group_id = '.$db->sql_escape($event_data['group_id']).'
									AND ug.user_pending = 0';
						$result = $db->sql_query($sql);
						if( $result )
						{
							$group_data = $db->sql_fetchrow($result);
							if( $group_data['group_id'] == $event_data['group_id'] )
							{
								$user_auth_for_event = 1;
							}
						}
						$db->sql_freeresult($result);
					}
					else
					{
						$group_list = explode( ',', $event_data['group_id_list'] );
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
							//if( $group_data['group_id'] == $event_data['group_id'] )
							{
								$user_auth_for_event = 1;
							}
						}
						$db->sql_freeresult($result);
					}
					break;
				case 2:
					// public event... everyone is invited
					$user_auth_for_event = 1;
					break;
			}
		}
		return $user_auth_for_event;
	}
	

	/* calendar_add_or_update_reply()
	**
	** Notifies users who are watching the event of the new reply
	** or update.  Note if the user doesn't have permission to view
	** detailed replies - we don't notify them about new/updated replies,
	** we will only notify them when the event information itself is updated.
	**
	** INPUT
	**   $event_id - the id of the event with updated info/replies.
	**   $is_reply - is this a reply, or update to the event information itself?
	*/
	public function calendar_add_or_update_reply( $event_id, $is_reply = true )
	{
		global $auth, $db, $user, $config, $phpEx, $phpbb_root_path;
	
		$user_id = $user->data['user_id'];
		$user_notify = $user->data['user_notify'];
	
		$event_data = array();
		
		$raidevents = new raidevents();
		$raidevents->get_event_data( $event_id, $event_data );
	
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
			WHERE w.user_id = u.user_id '. $sql_track_replies .' AND w.event_id = ' .$event_id.' AND u.user_id <> '.$user_id;
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
					$messenger->template('calendar_updated_event', $row['user_lang']);
				}
				$messenger->to($row['user_email'], $row['username']);
				$messenger->im($row['user_jabber'], $row['username']);
	
				$messenger->assign_vars(array(
								'USERNAME'			=> htmlspecialchars_decode($row['username']),
								'EVENT_SUBJECT'		=> $event_data['event_subject'],
								'U_UNWATCH_EVENT'=> generate_board_url() . "/planner.$phpEx?view=event&calEid=$event_id&calWatchE=0",
								'U_EVENT'			=> generate_board_url() . "/planner.$phpEx?view=event&calEid=$event_id", )
							);
	
				$messenger->send($row['user_notify_type']);
	
				$sql = 'UPDATE ' . RP_EVENTS_WATCH . '
					SET ' . $db->sql_build_array('UPDATE', array(
					'notify_status'		=> (int) 1,
										)) . "
					WHERE event_id = $event_id AND user_id = " . $row['user_id'];
				$db->sql_query($sql);
			}
	
		}
		$db->sql_freeresult($result);
		$messenger->save_queue();
	
		if( $user_notify == 1 )
		{
			calendar_watch_event( $event_id, 1);
		}
	}
	

}



/*
 * collects functions for watching certain event types
 * 
 */
class calendar_watch extends raidplanner_base 
{
	/* calendar_watch_event()
	**
	** Adds/removes the current user into the RP_EVENTS_WATCH table
	** so that they can start/stop recieving notifications about updates
	** and replies to the specified event.
	**
	** INPUT
	**    $event_id - the event the want to start/stop watching
	**    $turn_on = 1 - the user wants to START watching the event
	**    $turn_on = 0 - the user wants to STOP watching the event
	*/
	public function calendar_watch_event( $event_id, $turn_on = 1 )
	{
		global $db, $user, $auth;
		global $phpEx, $phpbb_root_path;
	
		$user_id = $user->data['user_id'];
	
		if( $turn_on == 1 )
		{
			$is_watching_event = false;
			$sql = 'SELECT * FROM ' . RP_EVENTS_WATCH . '
				WHERE user_id = '.$user_id.' AND event_id = ' .$event_id;
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$is_watching_event = true;
			}
			$db->sql_freeresult($result);
			if( $is_watching_event )
			{
				$this->calendar_mark_user_read_event( $event_id, $user_id );
			}
			else
			{
				$track_replies = 0;
				if( $auth->acl_get('u_raidplanner_view_detailed_rsvps') )
				{
					$track_replies = 1;
				}
				else
				{
					$event_data = array();
					$raidevents = new raidevents();
					$raidevents->get_event_data( $event_id, $event_data );
					if( $user->data['user_id'] == $event_data['poster_id'] )
					{
						$track_replies = 1;
					}
				}
	
				$sql = 'INSERT INTO ' . RP_EVENTS_WATCH . ' ' . $db->sql_build_array('INSERT', array(
						'event_id'		=> (int) $event_id,
						'user_id'		=> (int) $user_id,
						'notify_status'	=> (int) 0,
						'track_replies' => (int) $track_replies,
						)
					);
				$db->sql_query($sql);
			}
		}
		else if( $turn_on == 0 )
		{
			$sql = 'DELETE FROM ' . RP_EVENTS_WATCH . '
					WHERE event_id = ' .$db->sql_escape($event_id). '
					AND user_id = '.$db->sql_escape($user_id);
			$db->sql_query($sql);
		}
	}
	
	/* calendar_watch_calendar()
	**
	** Adds/removes the current user into the RP_WATCH table
	** so that they can start/stop recieving notifications about new events
	**
	** INPUT
	**    $turn_on = 1 - the user wants to START watching the calendar
	**    $turn_on = 0 - the user wants to STOP watching the calendar
	*/
	public function calendar_watch_calendar( $turn_on = 1 )
	{
		global $db, $user, $auth;
		global $phpEx, $phpbb_root_path;
	
		$user_id = $user->data['user_id'];
	
		if( $turn_on == 1 )
		{
			$is_watching_calendar = false;
			$sql = 'SELECT * FROM ' . RP_WATCH . '
				WHERE user_id = '.$user_id;
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$is_watching_calendar = true;
			}
			$db->sql_freeresult($result);
			if( $is_watching_calendar )
			{
				$this->calendar_mark_user_read_calendar( $user_id );
			}
			else
			{
				$sql = 'INSERT INTO ' . RP_WATCH . ' ' . $db->sql_build_array('INSERT', array(
						'user_id'		=> (int) $user_id,
						'notify_status'	=> (int) 0,
						)
					);
				$db->sql_query($sql);
			}
		}
		else if( $turn_on == 0 )
		{
			$sql = 'DELETE FROM ' . RP_WATCH . '
					WHERE user_id = '.$db->sql_escape($user_id);
			$db->sql_query($sql);
		}
	}
	
	
	
	/* calendar_mark_user_read_event()
	**
	** Changes the user's notify_status in the RP_EVENTS_WATCH table
	** This indicates that the user has re-visited the event, and
	** they will recieve a notification the next time there is
	** an update/reply posted to this event.
	**
	** INPUT
	**   $user_id - the user who just viewed a event.
	*/
	public function calendar_mark_user_read_event( $event_id, $user_id )
	{
		global $db;
	
		$sql = 'UPDATE ' . RP_EVENTS_WATCH . '
			SET ' . $db->sql_build_array('UPDATE', array(
			'notify_status'		=> (int) 0,
								)) . "
			WHERE event_id = $event_id AND user_id = $user_id";
		$db->sql_query($sql);
	}
	
	/* calendar_mark_user_read_calendar()
	**
	** Changes the user's notify_status in the RP_WATCH table
	** This indicates that the user has re-visited the page, and
	** they will recieve a notification the next time there is
	** a new event posted.
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
				$s_watching_calendar['link'] = append_sid( "{$phpbb_root_path}calendar.$phpEx", "calWatch=0" );
				$s_watching_calendar['title'] = $user->lang['WATCH_CALENDAR_TURN_OFF'];
			}
			else
			{
				$s_watching_calendar['link'] = append_sid( "{$phpbb_root_path}calendar.$phpEx", "calWatch=1" );
				$s_watching_calendar['title'] = $user->lang['WATCH_CALENDAR_TURN_ON'];
			}
		}
	}
		
	
	
}

/*
 * event functions
 */
class raidevents extends raidplanner_base 
{
	
	/* get_event_data()
	**
	** Given an event id, find all the data associated with the event
	*/
	public function get_event_data( $id, &$event_data )
	{
		global $auth, $db, $user, $config;
		if( $id < 1 )
		{
			trigger_error('NO_EVENT');
		}
		$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
				WHERE event_id = '.$db->sql_escape($id);
		$result = $db->sql_query($sql);
		$event_data = $db->sql_fetchrow($result);
		if( !$event_data )
		{
			trigger_error('NO_EVENT');
		}
	
	    $db->sql_freeresult($result);
	
	
		if( $event_data['recurr_id'] > 0 )
		{
		    $event_data['is_recurr'] = 1;
	
			$sql = 'SELECT * FROM ' . RP_RECURRING_EVENTS_TABLE . '
						WHERE recurr_id = '.$db->sql_escape( $event_data['recurr_id'] );
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
	    	$db->sql_freeresult($result);
	
		    $event_data['frequency_type'] = $row['frequency_type'];
		    $event_data['frequency'] = $row['frequency'];
		    $event_data['final_occ_time'] = $row['final_occ_time'];
		    $event_data['week_index'] = $row['week_index'];
		    $event_data['first_day_of_week'] = $row['first_day_of_week'];
		}
		else
		{
			$event_data['is_recurr'] = 0;
		    $event_data['frequency_type'] = 0;
		    $event_data['frequency'] = 0;
		    $event_data['final_occ_time'] = 0;
		    $event_data['week_index'] = 0;
		    $event_data['first_day_of_week'] = $config["rp_first_day_of_week"];
		}
	}
	
	
	/* get the the invite list for an event and the poster url
	*/
	public function get_event_invite_list_and_poster_url($event_data, &$poster_url, &$invite_list )
	{
		global $auth, $db, $user, $config;
		global $phpEx, $phpbb_root_path;
	
		$sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . '
				WHERE user_id = '.$db->sql_escape($event_data['poster_id']);
		$result = $db->sql_query($sql);
		$poster_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
	
		$poster_url = get_username_string( 'full', $event_data['poster_id'], $poster_data['username'], $poster_data['user_colour'] );
	
		$invite_list = "";
	
		switch( $event_data['event_access_level'] )
		{
			case 0:
				// personal event... only event creator is invited
				$invite_list = $poster_url;
				break;
			case 1:
				if( $event_data['group_id'] != 0 )
				{
					// group event... only members of specified group are invited
					$sql = 'SELECT group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
							WHERE group_id = '.$db->sql_escape($event_data['group_id']);
					$result = $db->sql_query($sql);
					$group_data = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
					$temp_list = (($group_data['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $group_data['group_name']] : $group_data['group_name']);
					$temp_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=group&amp;g=".$event_data['group_id']);
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
					$group_list = explode( ',', $event_data['group_id_list'] );
					$num_groups = sizeof( $group_list );
					for( $i = 0; $i < $num_groups; $i++ )
					{
						if( $group_list[$i] == "")
						{
							continue;
						}
						// group event... only members of specified group are invited
						$sql = 'SELECT group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
								WHERE group_id = '.$db->sql_escape($group_list[$i]);
						$result = $db->sql_query($sql);
						$group_data = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);
						$temp_list = (($group_data['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $group_data['group_name']] : $group_data['group_name']);
						$temp_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=group&amp;g=".$event_data['group_id']);
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
				// public event... everyone is invited
				$invite_list = $user->lang['EVERYONE'];
				break;
		}
	
	}
		
	

		
}


?>
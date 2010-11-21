<?php
/**
*
* @author alightner
*
* @package phpBB Calendar
* @version CVS/SVN: $Id$
* @copyright (c) 2009 alightner
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




/* main function to display an individual event */
function calendar_display_event()
{
}




/* displays the calendar - either week view or upcoming event list
   as specified in the ACP on the index */
function calendar_display_calendar_on_index()
{
	global $auth, $db, $user, $config, $template;

	$user->setup('calendar');

	//find the first day of the week
	$index_display_week = $config['index_display_week']; 
	if( $index_display_week === "1" )
	{
		$template->assign_vars(array(
			'S_CALENDAR_WEEK'	=> true,
		));
		calendar_display_week( 1 );
	}
	else
	{
		//see if we should display X number of upcoming events
		$index_display_next_events = $config['index_display_next_events'];  
		$s_next_events = false;
		if( $index_display_next_events > 0 )
		{
			$s_next_events = true;
		}

		$template->assign_vars(array(
			'S_CALENDAR_WEEK'	=> false,
			'S_CALENDAR_NEXT_EVENTS'	=> $s_next_events,
		));
		display_next_events( $index_display_next_events );
	}
}

/* displays the next x number of upcoming events */
function display_next_events( $x )
{
	global $auth, $db, $user, $config, $template, $date, $available_etype_colors, $available_etype_images, $available_etype_display_names, $month_sel_code, $day_sel_code, $year_sel_code, $mode_sel_code;

	global $phpEx, $phpbb_root_path;

	$etype_url_opts = get_etype_url_opts();

	// Is the user able to view ANY events?
	$user_can_view_events = false;
	if ( $auth->acl_get('u_raidplanner_view_events') )
	{

		init_calendar_data();
		$subject_limit = $config['rp_display_truncated_name'];
		$group_options = get_sql_group_options($user->data['user_id']);
		$etype_options = get_etype_filter();

		$start_temp_date = time();
		$end_temp_date = $start_temp_date + 31536000;
		// find all day events that are still taking place
		$sort_timestamp_cutoff = $start_temp_date - 86400+1;

	    $disp_date_format = $config['rp_date_format'];
	    $disp_date_time_format = $config['rp_date_time_format'];

		// don't list events that are more than 1 year in the future
		$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
				WHERE ( (event_access_level = 2) OR
					(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
					(event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
				((( event_start_time >= '.$db->sql_escape($start_temp_date).' AND event_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
				 ( event_end_time > '.$db->sql_escape($start_temp_date).' AND event_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
				 ( event_start_time < '.$db->sql_escape($start_temp_date).' AND event_end_time > '.$db->sql_escape($end_temp_date)." )) OR (sort_timestamp > ".$db->sql_escape($sort_timestamp_cutoff)." AND event_all_day = 1) ) ORDER BY sort_timestamp ASC";
		$result = $db->sql_query_limit($sql, $x, 0);
		while ($row = $db->sql_fetchrow($result))
		{
			$events['EVENT_URL'] = append_sid("{$phpbb_root_path}calendar.$phpEx", "view=event&amp;calEid=".$row['event_id'].$etype_url_opts);
			$events['IMAGE'] = $available_etype_images[$row['etype_id']];
			$events['COLOR'] = $available_etype_colors[$row['etype_id']];
			$events['ETYPE_DISPLAY_NAME'] = $available_etype_display_names[$row['etype_id']];

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
			get_event_invite_list_and_poster_url($row, $poster_url, $invite_list );
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

/* displays the upcoming events for the next x number of days */
function display_next_events_for_x_days( $x )
{
	global $auth, $db, $user, $config, $template, $date, $available_etype_colors, $available_etype_images, $available_etype_display_names, $month_sel_code, $day_sel_code, $year_sel_code, $mode_sel_code;

	global $phpEx, $phpbb_root_path;

	$etype_url_opts = get_etype_url_opts();

	// Is the user able to view ANY events?
	$user_can_view_events = false;
	if ( $auth->acl_get('u_raidplanner_view_events') )
	{

		init_calendar_data();
		$subject_limit = $config['rp_display_truncated_name'];
		$group_options = get_sql_group_options($user->data['user_id']);
		$etype_options = get_etype_filter();

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
					(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
					(event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
				((( event_start_time >= '.$db->sql_escape($start_temp_date).' AND event_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
				 ( event_end_time > '.$db->sql_escape($start_temp_date).' AND event_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
				 ( event_start_time < '.$db->sql_escape($start_temp_date).' AND event_end_time > '.$db->sql_escape($end_temp_date)." )) OR (sort_timestamp > ".$db->sql_escape($sort_timestamp_cutoff)." AND sort_timestamp <= ".$db->sql_escape($end_temp_date)." AND event_all_day = 1) ) ORDER BY sort_timestamp ASC";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$events['EVENT_URL'] = append_sid("{$phpbb_root_path}calendar.$phpEx", "view=event&amp;calEid=".$row['event_id'].$etype_url_opts);
			$events['IMAGE'] = $available_etype_images[$row['etype_id']];
			$events['COLOR'] = $available_etype_colors[$row['etype_id']];
			$events['ETYPE_DISPLAY_NAME'] = $available_etype_display_names[$row['etype_id']];

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
			get_event_invite_list_and_poster_url($row, $poster_url, $invite_list );
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

/* used to generate the UCP "manage my events" module */
function display_posters_next_events_for_x_days( $x, $user_id )
{
	global $auth, $db, $user, $config, $template, $date, $available_etype_colors, $available_etype_images, $available_etype_display_names, $month_sel_code, $day_sel_code, $year_sel_code, $mode_sel_code;

	global $phpEx, $phpbb_root_path;

	$etype_url_opts = get_etype_url_opts();

	// Is the user able to view ANY events?
	$user_can_view_events = false;
	if ( $auth->acl_get('u_raidplanner_view_events') )
	{

		init_calendar_data();
		$subject_limit = $config['rp_display_truncated_name'];
		$group_options = get_sql_group_options($user->data['user_id']);
		$etype_options = get_etype_filter();

		$start_temp_date = time();
		//$end_temp_date = $start_temp_date + 31536000;
		$end_temp_date = $start_temp_date + ( $x * 86400 );
		// find all day events that are still taking place
		$sort_timestamp_cutoff = $start_temp_date - 86400+1;

	    $disp_date_format = $config['rp_date_format'];
	    $disp_date_time_format = $config['rp_date_time_format'];

		// don't list events that are more than 1 year in the future
		$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
				WHERE poster_id = '.$user_id.' AND( (event_access_level = 2) OR
					(poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
					(event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
				((( event_start_time >= '.$db->sql_escape($start_temp_date).' AND event_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
				 ( event_end_time > '.$db->sql_escape($start_temp_date).' AND event_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
				 ( event_start_time < '.$db->sql_escape($start_temp_date).' AND event_end_time > '.$db->sql_escape($end_temp_date)." )) OR (sort_timestamp > ".$db->sql_escape($sort_timestamp_cutoff)." AND sort_timestamp <= ".$db->sql_escape($end_temp_date)." AND event_all_day = 1) ) ORDER BY sort_timestamp ASC";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$events['EVENT_URL'] = append_sid("{$phpbb_root_path}calendar.$phpEx", "view=event&amp;calEid=".$row['event_id'].$etype_url_opts);
			$events['IMAGE'] = $available_etype_images[$row['etype_id']];
			$events['COLOR'] = $available_etype_colors[$row['etype_id']];
			$events['ETYPE_DISPLAY_NAME'] = $available_etype_display_names[$row['etype_id']];

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
			$events['RECURRING_TXT'] = get_recurring_event_string_via_id( $row['recurr_id'] );

			$poster_url = '';
			$invite_list = '';
			get_event_invite_list_and_poster_url($row, $poster_url, $invite_list );
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



			$edit_url = "";
			$edit_all_url = "";
			if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_events') &&
				(($user->data['user_id'] == $row['poster_id'])|| $auth->acl_get('m_raidplanner_edit_other_users_events') ))
			{
				$edit_url = append_sid("{$phpbb_root_path}calendarpost.$phpEx", "mode=edit&amp;calEid=".$row['event_id']."&amp;calD=".$date['day']."&amp;calM=".$date['month_no']."&amp;calY=".$date['year']);
				if( $row['recurr_id'] > 0 )
				{
					$edit_all_url = append_sid("{$phpbb_root_path}calendarpost.$phpEx", "mode=edit&amp;calEditAll=1&amp;calEid=".$row['event_id']."&amp;calD=".$date['day']."&amp;calM=".$date['month_no']."&amp;calY=".$date['year']);
				}
			}
			$delete_url = "";
			$delete_all_url = "";
			if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_events') &&
				(($user->data['user_id'] == $row['poster_id'])|| $auth->acl_get('m_raidplanner_delete_other_users_events') ))

			{
				$delete_url = append_sid("{$phpbb_root_path}calendarpost.$phpEx", "mode=delete&amp;calEid=".$row['event_id']."&amp;calD=".$date['day']."&amp;calM=".$date['month_no']."&amp;calY=".$date['year'].$etype_url_opts);
				if( $row['recurr_id'] > 0 )
				{
					$delete_all_url = append_sid("{$phpbb_root_path}calendarpost.$phpEx", "mode=delete&amp;calDelAll=1&amp;calEid=".$row['event_id']."&amp;calD=".$date['day']."&amp;calM=".$date['month_no']."&amp;calY=".$date['year'].$etype_url_opts);
				}
			}
			$events['U_EDIT'] = $edit_url;
			$events['U_EDIT_ALL'] = $edit_all_url;
			$events['U_DELETE'] = $delete_url;
			$events['U_DELETE_ALL'] = $delete_all_url;


			$template->assign_block_vars('myevents', $events);
		}
		$db->sql_freeresult($result);
	}
}

/* used to generate the UCP "manage event registration" module */
function display_users_next_events_for_x_days( $x, $user_id )
{
	global $auth, $db, $user, $config, $template, $date, $available_etype_colors, $available_etype_images, $available_etype_display_names, $month_sel_code, $day_sel_code, $year_sel_code, $mode_sel_code;

	global $phpEx, $phpbb_root_path;

	$etype_url_opts = get_etype_url_opts();

	$template->assign_vars(array(
			'S_RSVP_COLUMN'	=> true ));

	// Is the user able to view ANY events?
	$user_can_view_events = false;
	if ( $auth->acl_get('u_raidplanner_view_events') )
	{

		init_calendar_data();
		$subject_limit = $config['rp_display_truncated_name'];
		$group_options = get_sql_group_options($user->data['user_id']);
		$temp_find_str = "group_id";
		$temp_replace_str = "e.group_id";
		$group_options = str_replace( $temp_find_str, $temp_replace_str, $group_options );

		$etype_options = get_etype_filter();
		$temp_find_str = "etype";
		$temp_replace_str = "e.etype";
		$etype_options = str_replace( $temp_find_str, $temp_replace_str, $etype_options );

		$start_temp_date = time();
		//$end_temp_date = $start_temp_date + 31536000;
		$end_temp_date = $start_temp_date + ( $x * 86400 );
		// find all day events that are still taking place
		$sort_timestamp_cutoff = $start_temp_date - 86400+1;

	    $disp_date_format = $config['rp_date_format'];
	    $disp_date_time_format = $config['rp_date_time_format'];

		// don't list events that are more than 1 year in the future
		$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . ' e, '.CALENDAR_RSVP_TABLE.' r
				WHERE e.event_id = r.event_id AND r.poster_id = '.$user_id.' AND
					( (e.event_access_level = 2) OR
					(e.poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR
					(e.event_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' AND
				((( e.event_start_time >= '.$db->sql_escape($start_temp_date).' AND e.event_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
				 ( e.event_end_time > '.$db->sql_escape($start_temp_date).' AND e.event_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
				 ( e.event_start_time < '.$db->sql_escape($start_temp_date).' AND e.event_end_time > '.$db->sql_escape($end_temp_date)." )) OR (e.sort_timestamp > ".$db->sql_escape($sort_timestamp_cutoff)." AND e.sort_timestamp <= ".$db->sql_escape($end_temp_date)." AND e.event_all_day = 1) ) ORDER BY e.sort_timestamp ASC";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$events['EVENT_URL'] = append_sid("{$phpbb_root_path}calendar.$phpEx", "view=event&amp;calEid=".$row['event_id'].$etype_url_opts);
			$events['IMAGE'] = $available_etype_images[$row['etype_id']];
			$events['COLOR'] = $available_etype_colors[$row['etype_id']];
			$events['ETYPE_DISPLAY_NAME'] = $available_etype_display_names[$row['etype_id']];

			$events['FULL_SUBJECT'] = censor_text($row['event_subject']);
			$events['SUBJECT'] = $events['FULL_SUBJECT'];
			if( $subject_limit > 0 )
			{
				if(utf8_strlen($events['SUBJECT']) > $subject_limit)
				{
					$events['SUBJECT'] = truncate_string($events['SUBJECT'], $subject_limit) . '...';
				}
			}
				$events['POSTER'] = $row['poster_name'];
				$events['POSTER_URL'] = get_username_string( 'full', $row['poster_id'], $row['poster_name'], $row['poster_colour'] );
				$events['VALUE'] = $row['rsvp_val'];
				if( $row['rsvp_val'] == 0 )
				{
					$events['COLOR'] = '#00ff00';
					$events['VALUE_TXT'] = $user->lang['YES'];
				}
				else if( $row['rsvp_val'] == 1 )
				{
					$events['COLOR'] = '#ff0000';
					$events['VALUE_TXT'] = $user->lang['NO'];
				}
				else
				{
					$events['COLOR'] = '#0000ff';
					$events['VALUE_TXT'] = $user->lang['MAYBE'];
				}
				$events['U_EDIT'] = "";
				if( $edit_rsvps === 1 )
				{
					$events['U_EDIT'] = $edit_rsvp_url . $row['rsvp_id'];
				}
				$events['HEADCOUNT'] = $row['rsvp_count'];
				$events['DETAILS'] = generate_text_for_display($row['rsvp_detail'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
				$events['POST_TIMESTAMP'] = $row['post_time'];
				$events['POST_TIME'] = $user->format_date($row['post_time']);



			$poster_url = '';
			$invite_list = '';
			get_event_invite_list_and_poster_url($row, $poster_url, $invite_list );
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
function init_view_selection_code( $view_mode )
{
	global $auth, $db, $user, $config, $date, $month_names, 
	$month_sel_code, $day_sel_code, $year_sel_code, $mode_sel_code;

	// create RP_VIEW_OPTIONS
	$month_sel_code  = "<select name='calM' id='calM'>\n";
	for( $i = 1; $i <= 12; $i++ )
	{
		$month_sel_code .= "<option value='".$i."'>".$user->lang['datetime'][$month_names[$i]]."</option>\n";
	}
	$month_sel_code .= "</select>\n";

	$day_sel_code  = "<select name='calD' id='calD'>\n";
	for( $i = 1; $i <= 31; $i++ )
	{
		$day_sel_code .= "<option value='".$i."'>".$i."</option>\n";
	}
	$day_sel_code .= "</select>\n";


	$temp_year	=	gmdate('Y');

	$year_sel_code  = "<select name='calY' id='calY'>\n";
	for( $i = $temp_year-1; $i < ($temp_year+5); $i++ )
	{
		$year_sel_code .= "<option value='".$i."'>".$i."</option>\n";
	}
	$year_sel_code .= "</select>\n";

	$temp_find_str = "value='".$date['month_no']."'>";
	$temp_replace_str = "value='".$date['month_no']."' selected='selected'>";
	$month_sel_code = str_replace( $temp_find_str, $temp_replace_str, $month_sel_code );
	$temp_find_str = "value='".(int)$date['day']."'>";
	$temp_replace_str = "value='".(int)$date['day']."' selected='selected'>";
	$day_sel_code = str_replace( $temp_find_str, $temp_replace_str, $day_sel_code );
	$temp_find_str = "value='".$date['year']."'>";
	$temp_replace_str = "value='".$date['year']."' selected='selected'>";
	$year_sel_code = str_replace( $temp_find_str, $temp_replace_str, $year_sel_code );

	$mode_sel_code = "<select name='view' id='view'>\n";
	$mode_sel_code .= "<option value='month'>".$user->lang['MONTH']."</option>\n";
	$mode_sel_code .= "<option value='week'>".$user->lang['WEEK']."</option>\n";
	$mode_sel_code .= "<option value='day'>".$user->lang['DAY']."</option>\n";
	$mode_sel_code .= "</select>\n";
	$temp_find_str = "value='".$view_mode."'>";
	$temp_replace_str = "value='".$view_mode."' selected='selected'>";
	$mode_sel_code = str_replace( $temp_find_str, $temp_replace_str, $mode_sel_code );
}


/* get_event_data()
**
** Given an event id, find all the data associated with the event
*/
function get_event_data( $id, &$event_data )
{
	global $auth, $db, $user;
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


/**
* Do the various checks required for removing event as well as removing it
* Note the caller of this function must make sure that the user has
* permission to delete the event before calling this function
*/
function handle_event_delete($event_id, &$event_data)
{
	global $user, $db, $auth, $date;
	global $phpbb_root_path, $phpEx;

	$s_hidden_fields = build_hidden_fields(array(
			'calEid'=> $event_id,
			'mode'	=> 'delete',
			'calEType' => request_var('calEType', 0),
			)
	);


	if (confirm_box(true))
	{
		// delete all the rsvps for this event before deleting the event
		$sql = 'DELETE FROM ' . RP_RSVP_TABLE . ' WHERE event_id = ' .$db->sql_escape($event_id);
		$db->sql_query($sql);

		$sql = 'DELETE FROM ' . RP_EVENTS_WATCH . ' WHERE event_id = ' .$db->sql_escape($event_id);
		$db->sql_query($sql);

		// Delete event
		$sql = 'DELETE FROM ' . RP_EVENTS_TABLE . '
				WHERE event_id = '.$db->sql_escape($event_id);
		$db->sql_query($sql);

		$etype_url_opts = get_etype_url_opts();
		$meta_info = append_sid("{$phpbb_root_path}calendar.$phpEx", "calM=".$date['month_no']."&amp;calY=".$date['year'].$etype_url_opts);
		$message = $user->lang['EVENT_DELETED'];

		meta_refresh(3, $meta_info);
		$message .= '<br /><br />' . sprintf($user->lang['RETURN_CALENDAR'], '<a href="' . $meta_info . '">', '</a>');
		trigger_error($message);
	}
	else
	{
		confirm_box(false, $user->lang['DELETE_EVENT'], $s_hidden_fields);
	}
}

/**
* Do the various checks required for removing event as well as removing it
* Note the caller of this function must make sure that the user has
* permission to delete the event before calling this function
*/
function handle_event_delete_all($event_id, &$event_data)
{
	global $user, $db, $auth, $date;
	global $phpbb_root_path, $phpEx;


	if( $event_data['recurr_id'] == 0 )
	{
		handle_event_delete($event_id, $event_data);
	}
	else
	{
		$s_hidden_fields = build_hidden_fields(array(
				'calEid'	=> $event_id,
				'mode'		=> 'delete',
				'calDelAll'	=> 1,
				'calEType' => request_var('calEType', 0),
				)
		);

		if (confirm_box(true))
		{
			// find all of the events in this recurring event string so we can delete their rsvps
			$sql = 'SELECT event_id FROM ' . RP_EVENTS_TABLE . '
						WHERE recurr_id = '. $event_data['recurr_id'];
			$result = $db->sql_query($sql);

			// delete all the rsvps for this event before deleting the event
			while ($row = $db->sql_fetchrow($result))
			{
				$sql = 'DELETE FROM ' . RP_RSVP_TABLE . ' WHERE event_id = ' .$db->sql_escape($row['event_id']);
				$db->sql_query($sql);

				$sql = 'DELETE FROM ' . RP_EVENTS_WATCH . ' WHERE event_id = ' .$db->sql_escape($row['event_id']);
				$db->sql_query($sql);
			}
			$db->sql_freeresult($result);

			// delete the recurring event
			$sql = 'DELETE FROM ' . RP_RECURRING_EVENTS_TABLE . '
					WHERE recurr_id = '.$db->sql_escape($event_data['recurr_id']);
			$db->sql_query($sql);

			// finally delete all of the events
			$sql = 'DELETE FROM ' . RP_EVENTS_TABLE . '
					WHERE recurr_id = '.$db->sql_escape($event_data['recurr_id']);
			$db->sql_query($sql);

			$etype_url_opts = get_etype_url_opts();
			$meta_info = append_sid("{$phpbb_root_path}calendar.$phpEx", "calM=".$date['month_no']."&amp;calY=".$date['year'].$etype_url_opts);
			$message = $user->lang['EVENT_DELETED'];

			meta_refresh(3, $meta_info);
			$message .= '<br /><br />' . sprintf($user->lang['RETURN_CALENDAR'], '<a href="' . $meta_info . '">', '</a>');
			trigger_error($message);
		}
		else
		{
			confirm_box(false, $user->lang['DELETE_ALL_EVENTS'], $s_hidden_fields);
		}
	}
}


/* generates the selection code necessary for group selection when making new calendar posts
   by default no group is selected and the entire form item is disabled
*/
function posting_generate_group_selection_code( $user_id )
{
	global $auth, $db, $user, $config;

	$disp_hidden_groups = $config['rp_display_hidden_groups'];

	if ( $auth->acl_get('u_raidplanner_nonmember_groups') )
	{
		if( $disp_hidden_groups == 1 )
		{
			$sql = 'SELECT g.group_id, g.group_name, g.group_type
					FROM ' . GROUPS_TABLE . ' g
					ORDER BY g.group_type, g.group_name';
		}
		else
		{
			$sql = 'SELECT g.group_id, g.group_name, g.group_type
					FROM ' . GROUPS_TABLE . ' g
					' . ((!$auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) ? ' 	WHERE g.group_type <> ' . GROUP_HIDDEN : '') . '
					ORDER BY g.group_type, g.group_name';
		}
	}
	else
	{
		if( $disp_hidden_groups == 1 )
		{
			$sql = 'SELECT g.group_id, g.group_name, g.group_type
					FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug
					WHERE ug.user_id = ". $db->sql_escape($user_id).'
						AND g.group_id = ug.group_id
						AND ug.user_pending = 0
					ORDER BY g.group_type, g.group_name';
		}
		else
		{
			$sql = 'SELECT g.group_id, g.group_name, g.group_type
					FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug
					WHERE ug.user_id = ". $db->sql_escape($user_id)."
						AND g.group_id = ug.group_id" . ((!$auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) ? ' 	AND g.group_type <> ' . GROUP_HIDDEN : '') . '
						AND ug.user_pending = 0
					ORDER BY g.group_type, g.group_name';
		}
	}

	$result = $db->sql_query($sql);

	$group_sel_code = "<select name='calGroupId[]' id='calGroupId[]' disabled='disabled' multiple='multiple' size='6' >\n";
	while ($row = $db->sql_fetchrow($result))
	{
		$group_sel_code .= "<option value='" . $row['group_id'] . "'>" . (($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name']) . "</option>\n";
	}
	$db->sql_freeresult($result);
	$group_sel_code .= "</select>\n";
	return $group_sel_code;
}

/* prune_calendar()
**
** Cron job used to delete old events (and all of their related data:
** rsvps, recurring event data, etc) after they've expired.
**
** The expiration date of an event = when the event ends + the prune_limit
** specified in the calendar ACP.
*/
function prune_calendar()
{
	global $auth, $db, $user, $config, $phpEx, $phpbb_root_path;
	$prune_limit = $config['rp_prune_limit'];

	set_config  ( 'last_prune',  time(),0);
	
	// delete events that have been over for $prune_limit seconds.
	$end_temp_date = time() - $prune_limit;

	// find all day events that finished before the prune limit
	$sort_timestamp_cutoff = $end_temp_date - 86400;
	$sql = 'SELECT event_id FROM ' . RP_EVENTS_TABLE . '
				WHERE ( (event_all_day = 1 AND sort_timestamp < '.$db->sql_escape($sort_timestamp_cutoff).')
				OR (event_all_day = 0 AND event_end_time < '.$db->sql_escape($end_temp_date).') )';
	$result = $db->sql_query($sql);

	// delete all the rsvps for this event before deleting the event
	while ($row = $db->sql_fetchrow($result))
	{
		$sql = 'DELETE FROM ' . RP_RSVP_TABLE . ' WHERE event_id = ' .$row['event_id'];
		$db->sql_query($sql);

		$sql = 'DELETE FROM ' . RP_EVENTS_WATCH . ' WHERE event_id = ' .$row['event_id'];
		$db->sql_query($sql);

	}
	$db->sql_freeresult($result);

	// now delete the old events
	$sql = 'DELETE FROM ' . RP_EVENTS_TABLE . '
				WHERE ( (event_all_day = 1 AND sort_timestamp < '.$db->sql_escape($sort_timestamp_cutoff).')
				OR (event_all_day = 0 AND event_end_time < '.$db->sql_escape($end_temp_date).') )';
	$db->sql_query($sql);

	// delete any recurring events that are permanently over
	$sql = 'DELETE FROM ' . RP_RECURRING_EVENTS_TABLE . '
				WHERE (final_occ_time > 0) AND
				      (final_occ_time < '. $end_temp_date .')';
	$db->sql_query($sql);

}


/**
* Fill smiley templates (or just the variables) with smilies, either in a window or inline
*/
function generate_calendar_smilies($mode)
{
	global $auth, $db, $user, $config, $template;
	global $phpEx, $phpbb_root_path;

	if ($mode == 'window')
	{
		page_header($user->lang['SMILIES']);

		$template->set_filenames(array(
			'body' => 'posting_smilies.html')
		);
	}

	$display_link = false;
	if ($mode == 'inline')
	{
		$sql = 'SELECT smiley_id
			FROM ' . SMILIES_TABLE . '
			WHERE display_on_posting = 0';
		$result = $db->sql_query_limit($sql, 1, 0, 3600);

		if ($row = $db->sql_fetchrow($result))
		{
			$display_link = true;
		}
		$db->sql_freeresult($result);
	}

	$last_url = '';

	$sql = 'SELECT *
		FROM ' . SMILIES_TABLE .
		(($mode == 'inline') ? ' WHERE display_on_posting = 1 ' : '') . '
		ORDER BY smiley_order';
	$result = $db->sql_query($sql, 3600);

	$smilies = array();
	while ($row = $db->sql_fetchrow($result))
	{
		if (empty($smilies[$row['smiley_url']]))
		{
			$smilies[$row['smiley_url']] = $row;
		}
	}
	$db->sql_freeresult($result);

	if (sizeof($smilies))
	{
		foreach ($smilies as $row)
		{
			$template->assign_block_vars('smiley', array(
				'SMILEY_CODE'	=> $row['code'],
				'A_SMILEY_CODE'	=> addslashes($row['code']),
				'SMILEY_IMG'	=> $phpbb_root_path . $config['smilies_path'] . '/' . $row['smiley_url'],
				'SMILEY_WIDTH'	=> $row['smiley_width'],
				'SMILEY_HEIGHT'	=> $row['smiley_height'],
				'SMILEY_DESC'	=> $row['emotion'])
			);
		}
	}

	if ($mode == 'inline' && $display_link)
	{
		$template->assign_vars(array(
			'S_SHOW_SMILEY_LINK' 	=> true,
			'U_MORE_SMILIES' 		=> append_sid("{$phpbb_root_path}calendarpost.$phpEx", 'mode=smilies'))
		);
	}

	if ($mode == 'window')
	{
		page_footer();
	}
}


/* populate_calendar()
**
** Populates occurrences of recurring events in the calendar
**
** INPUT
**   $recurr_id_to_pop - if this is 0, then we are running a
**       cron job, and need to populate occurrences of all
**       recurring events - up till the end population limit
**
**       If this is non-zero, then it is the id of a newly
**       created recurring event, and we need to populate
**       all of the instances of this event immediately up to
**       the end population limit, and if its first occurrence
**       is way into the future (past the population limit)
**       populate at least one occurrence anyway, so the
**       user has at least one event to view now.
**
** RETURNS
**   the first populated event_id (if $recurr_id_to_pop was > 0 )
*/
function populate_calendar( $recurr_id_to_pop = 0 )
{
	global $auth, $db, $user, $config, $phpEx, $phpbb_root_path, $cache;
	$populate_limit = $config['rp_populate_limit'];

    if( $recurr_id_to_pop > 0 )
    {
    	set_config ('last_populate', time() ,0);
    	$cache->destroy('config');
	}

	// create events that occur between now and $populate_limit seconds.
	$end_populate_limit = time() + $populate_limit;

	$first_pop = 0;
	$first_pop_event_id = 0;
	if( $recurr_id_to_pop > 0 )
	{
		$sql = 'SELECT * FROM ' . RP_RECURRING_EVENTS_TABLE . '
					WHERE recurr_id = '.$recurr_id_to_pop;
	}
	else
	{
		// find all day events that need new events occurrences
		$sql = 'SELECT * FROM ' . RP_RECURRING_EVENTS_TABLE . '
					WHERE ( (last_calc_time = 0) OR
							((next_calc_time < '. $end_populate_limit .') AND
							((next_calc_time < final_occ_time) OR (final_occ_time = 0)) ))';
	}
	$result = $db->sql_query($sql);
	
	while ($row = $db->sql_fetchrow($result))
	{
		if( $row['final_occ_time'] == 0 )
		{
			$row['final_occ_time'] = $end_populate_limit;
		}


		switch( $row['frequency_type'] )
		{
			case 1:
				//01) A: Day [X] of [Month Name] every [Y] Year(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $start_day = gmdate('j',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, ($start_year+$row['frequency']));
				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];

				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 2:
				//02) A: [Xth] [Weekday Name] of [Month Name] every [Y] Year(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $week_day = gmdate('w',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_day = 0;
				    // make sure the day exists
				    while( $start_day == 0 )
				    {
				    	$start_year = $start_year + $row['frequency'];
						$start_day = find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, true, false, $row['first_day_of_week'] );
				    }
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);

				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 3:
				//03) A: [Xth] [Weekday Name] of full weeks in [Month Name] every [Y] Year(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $week_day = gmdate('w',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_day = 0;
				    // make sure the day exists
				    while( $start_day == 0 )
				    {
				    	$start_year = $start_year + $row['frequency'];
				    	$start_day = find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, true, true, $row['first_day_of_week'] );
				    }
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);

				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 4:
				//04) A: [Xth from last] [Weekday Name] of [Month Name] every [Y] Year(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $week_day = gmdate('w',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_day = 0;
				    // make sure the day exists
				    while( $start_day == 0 )
				    {
				    	$start_year = $start_year + $row['frequency'];
				    	$start_day = find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, false, false, $row['first_day_of_week'] );
				    }
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);

				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 5:
				//05) A: [Xth from last] [Weekday Name] of full weeks in [Month Name] every [Y] Year(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $week_day = gmdate('w',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_day = 0;
				    // make sure the day exists
				    while( $start_day == 0 )
				    {
				    	$start_year = $start_year + $row['frequency'];
				    	$start_day = find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, false, true, $row['first_day_of_week'] );
				    }
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);

				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 6:
				//06) M: Day [X] of month every [Y] Month(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $start_day = gmdate('j',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $month_frequency = $row['frequency'];
				    $year_frequency = 0;
				    if( $row['frequency'] > 11 )
				    {
				    	$year_frequency = (int) floor($row['frequency']/12);
				    	$month_frequency = $row['frequency'] - (12 * $year_frequency);
				    }
				    $start_month = $start_month + $month_frequency;
				    $start_year = $start_year + $year_frequency;
				    if($start_month > 12)
				    {
				    	$start_month = $start_month - 12;
				    	$start_year = $start_year + 1;
				    }
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 7:
				//07) M: [Xth] [Weekday Name] of month every [Y] Month(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $week_day = gmdate('w',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_day = 0;
				    // make sure the day exists
				    while( $start_day == 0 )
				    {
				    	$start_month = $start_month + $row['frequency'];
				    	if( $start_month > 12 )
				    	{
				    		$start_month_mod = $start_month %12;
				    		$add_year = ($start_month - $start_month_mod) / 12;
				    		$start_month = $start_month_mod;
				    		if( $start_month == 0 )
				    		{
				    			$start_month = 12;
				    			$add_year--;
				    		}
				    		$start_year = $start_year + $add_year;
				    	}
				    	$start_day = find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, true, false, $row['first_day_of_week'] );
				    }
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);

				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 8:
				//08) M: [Xth] [Weekday Name] of full weeks in month every [Y] Month(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $week_day = gmdate('w',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_day = 0;
				    // make sure the day exists
				    while( $start_day == 0 )
				    {
				    	$start_month = $start_month + $row['frequency'];
				    	if( $start_month > 12 )
				    	{
				    		$start_month_mod = $start_month %12;
				    		$add_year = ($start_month - $start_month_mod) / 12;
				    		$start_month = $start_month_mod;
				    		if( $start_month == 0 )
				    		{
				    			$start_month = 12;
				    			$add_year--;
				    		}
				    		$start_year = $start_year + $add_year;
				    	}
				    	$start_day = find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, true, true, $row['first_day_of_week'] );
				    }
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);

				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 9:
				//09) M: [Xth from last] [Weekday Name] of month every [Y] Month(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $week_day = gmdate('w',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_day = 0;
				    // make sure the day exists
				    while( $start_day == 0 )
				    {
				    	$start_month = $start_month + $row['frequency'];
				    	if( $start_month > 12 )
				    	{
				    		$start_month_mod = $start_month %12;
				    		$add_year = ($start_month - $start_month_mod) / 12;
				    		$start_month = $start_month_mod;
				    		if( $start_month == 0 )
				    		{
				    			$start_month = 12;
				    			$add_year--;
				    		}
				    		$start_year = $start_year + $add_year;
				    	}
				    	$start_day = find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, false, false, $row['first_day_of_week'] );
				    }
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);

				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 10:
				//10) M: [Xth from last] [Weekday Name] of full weeks in month every [Y] Month(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];

				    // convert to poster's time - if not all day event
				    $poster_start_time = $row['next_calc_time'];
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_start_time = $row['next_calc_time'] + (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }
				    $week_day = gmdate('w',$poster_start_time);
				    $start_month = gmdate('n',$poster_start_time);
				    $start_year = gmdate('Y',$poster_start_time);
				    $start_day = 0;
				    // make sure the day exists
				    while( $start_day == 0 )
				    {
				    	$start_month = $start_month + $row['frequency'];
				    	if( $start_month > 12 )
				    	{
				    		$start_month_mod = $start_month %12;
				    		$add_year = ($start_month - $start_month_mod) / 12;
				    		$start_month = $start_month_mod;
				    		if( $start_month == 0 )
				    		{
				    			$start_month = 12;
				    			$add_year--;
				    		}
				    		$start_year = $start_year + $add_year;
				    	}
				    	$start_day = find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, false, true, $row['first_day_of_week'] );
				    }
				    $start_hour = gmdate('G',$poster_start_time);
				    $start_minute = gmdate('i',$poster_start_time);
				    $poster_new_start_time = gmmktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);

				    // convert back to poster's time - if not all day event
				    if( $row['event_all_day'] == 0 )
				    {
				    	$poster_new_start_time = $poster_new_start_time - (($row['poster_timezone'] + $row['poster_dst'])*3600);
				    }

				    $row['next_calc_time'] = $poster_new_start_time;

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 11:
				//11) W: [Weekday Name] every [Y] Week(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];
				    $row['next_calc_time'] = $row['next_calc_time'] + ($row['frequency'] * 7 * 86400);

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			case 12:
				//12) D: Every [Y] Day(s)
				while( must_find_next_occ( $row, $end_populate_limit ))
				{
				    $first_pop = ($row['last_calc_time'] == 0) ?  1: 0;
				    $row['last_calc_time'] = $row['next_calc_time'];
				    $row['next_calc_time'] = $row['next_calc_time'] + ($row['frequency'] * 86400);

				    $r_event_all_day = $row['event_all_day'];
				    $r_event_day = "";
				    $r_sort_timestamp = $row['last_calc_time'];
				    $r_event_start = $row['last_calc_time'];
				    $r_event_end = $row['last_calc_time'] + $row['event_duration'];
				    if( $r_event_all_day == 1 )
				    {
				    	$r_event_start = 0;
				    	$r_event_end = 0;
						$r_event_day = sprintf('%2d-%2d-%4d', gmdate('j',$r_sort_timestamp), gmdate('n',$r_sort_timestamp), gmdate('Y',$r_sort_timestamp));
				    }

				    $sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
							'etype_id'				=> (int) $row['etype_id'],
							'sort_timestamp'		=> (int) $r_sort_timestamp,
							'event_start_time'		=> (int) $r_event_start,
							'event_end_time'		=> (int) $r_event_end,
							'event_all_day'			=> (int) $r_event_all_day,
							'event_day'				=> (string) $r_event_day,
							'event_subject'			=> (string) $row['event_subject'],
							'event_body'			=> (string) $row['event_body'],
							'poster_id'				=> (int) $row['poster_id'],
							'event_access_level'	=> (int) $row['event_access_level'],
							'group_id'				=> (int) $row['group_id'],
							'bbcode_uid'			=> (string) $row['bbcode_uid'],
							'bbcode_bitfield'		=> (string) $row['bbcode_bitfield'],
							'enable_bbcode'			=> (int) $row['enable_bbcode'],
							'enable_magic_url'		=> (int) $row['enable_magic_url'],
							'enable_smilies'		=> (int) $row['enable_smilies'],
							'track_rsvps'			=> (int) $row['track_rsvps'],
							'allow_guests'			=> (int) $row['allow_guests'],
							'recurr_id'				=> (int) $row['recurr_id']
							)
						);
					$db->sql_query($sql);
					
					if( $first_pop == 1 )
					{
						$first_pop_event_id = $db->sql_nextid();
					}
				}
				break;
			default:
				break;
		}
		$sql = 'UPDATE ' . RP_RECURRING_EVENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', array(
					'last_calc_time'		=> (int) $row['last_calc_time'],
					'next_calc_time'		=> (int) $row['next_calc_time'],
						)) . "
					WHERE recurr_id = ".$row['recurr_id'];
		$db->sql_query($sql);
	}
	$db->sql_freeresult($result);
	return $first_pop_event_id;
}

/* must_find_next_occ()
**
** Given the current recurring event row_data, and the current
** populate limit date, do we still need to create the next
** occurrence of this event in the calendar?
**
** INPUT
**   $row_data - the current recurring event data
**   $end_populate_limit - how far into the future are we
**                         supposed to generate occurrences?
**
** RETURNS
**   true - we need to find the next occurence
**   false - we have generated all that we need at this time
*/
function must_find_next_occ( $row_data, $end_populate_limit )
{
	if( $row_data['last_calc_time'] == 0 )
	{
		/* no matter how far into the future this event
		may be, we must create at least the first occurrence
		so the user will have an event to look at to make sure everything
		looks ok after creating this string of recurring events */
		return true;
	}
	if( $row_data['next_calc_time'] < $end_populate_limit )
	{
	    /* if we are under the populate limit check the final occ time */
	    if( $row_data['final_occ_time'] == 0 )
	    {
	    	// this recurring event has no end date
	    	return true;
	    }
	    if( $row_data['next_calc_time'] < $row_data['final_occ_time'] )
	    {
	    	// this recurring event has not yet reached its end date
	    	return true;
	    }
	}
	return false;
}


/* find_week_index()
**
** Given a GMT date, determine what week (index) of the month this day occurs in.
**
** INPUT
**   $date - the GMT date in question
**   $from_start - is this looking for the index from the
**                 start of the month, or the end of the month?
**   $full_week - is index of full weeks in the month, or is it just the
**                index of weeks from the first/last day of the month?
**   $first_day_of_week - used to determine the start and end of a "full week"
**
** OUTPUT
**   the index of the week containing the given date.
*/
function find_week_index( $date, $from_start, $full_week, $first_day_of_week = -1 )
{
	$number_of_days_in_month = gmdate('t', $date);
	$day = gmdate('j',$date);
	$month = gmdate('n',$date);
	$year = gmdate('Y',$date);
	if( $first_day_of_week < 0 )
	{
		$first_day_of_week = $config['rp_first_day_of_week'];
	}

	if( $from_start )
	{
		$first_date = gmmktime(0, 0, 0, $month, 1, $year);
		$month_first_weekday = gmdate('w',$first_date);
		$first_day_of_first_full_week = 1;
		if( $full_week && $first_day_of_week != $month_first_weekday )
		{
			$diff = $month_first_weekday - $first_day_of_week;
			if( $diff > 0 )
			{
				$first_day_of_first_full_week = 8 - $diff;
			}
			else
			{
				$first_day_of_first_full_week = 1 - $diff;
			}
		}
		if( $day < $first_day_of_first_full_week )
		{
			return 0;
		}
		if( $day < ($first_day_of_first_full_week + 7) )
		{
			return 1;
		}
		if( $day < ($first_day_of_first_full_week + 14) )
		{
			return 2;
		}
		if( $day < ($first_day_of_first_full_week + 21) )
		{
			return 3;
		}
		if( $day < ($first_day_of_first_full_week + 28) )
		{
			return 4;
		}
		if( $day < ($first_day_of_first_full_week + 35) )
		{
			return 5;
		}

	}
	else
	{
		$last_day_of_week = $first_day_of_week-1;
		if( $last_day_of_week < 0 )
		{
			$last_day_of_week = 6;
		}
		$last_date = gmmktime(0, 0, 0, $month, $number_of_days_in_month, $year);
		$month_last_weekday = gmdate('w',$last_date);
		$last_day_of_last_full_week = $number_of_days_in_month;
		if( $full_week && $last_day_of_week != $month_last_weekday )
		{
			$diff = $last_day_of_week - $month_last_weekday;
			if( $diff > 0 )
			{
				$last_day_of_last_full_week = $number_of_days_in_month - 7 + $diff;
			}
			else
			{
				$last_day_of_last_full_week = $number_of_days_in_month + $diff;
			}
		}
		if( $day > $last_day_of_last_full_week )
		{
			return 0;
		}
		if( $day > ($last_day_of_last_full_week - 7) )
		{
			return 1;
		}
		if( $day > ($last_day_of_last_full_week - 14) )
		{
			return 2;
		}
		if( $day > ($last_day_of_last_full_week - 21) )
		{
			return 3;
		}
		if( $day > ($last_day_of_last_full_week - 28) )
		{
			return 4;
		}
		if( $day > ($last_day_of_last_full_week - 35) )
		{
			return 5;
		}
	}
}

/* find_day_via_week_index()
**
** Given a weekday (monday, tuesday, wednesday...) and and index (n)
** Find the day number of the nth weekday and return it.
**
** INPUT
**   $weekday - number 0-6, 0=Sunday, 6=Saturday,
**              this is the weekday we're searching for
**   $index - what week are we looking for this weeday in?
**   $month - the month we're searching
**   $year - the year we're searching
**   $from_start - is this looking for the nth weekday from the
**                 start of the month, or the end of the month?
**   $full_week - is it the nth weekday of full weeks in the month,
**                or is it just the nth weekday of the month?
**   $first_day_of_week - used to determine the start and end of a "full week"
**
** OUTPUT
**   the number of the day we were searching for.
*/
function find_day_via_week_index( $weekday, $index, $month, $year, $from_start, $full_week, $first_day_of_week = -1 )
{

	$first_date = gmmktime(0, 0, 0, $month, 1, $year);
	$number_of_days_in_month = gmdate('t', $first_date);
	if( $first_day_of_week < 0 )
	{
		$first_day_of_week = $config['rp_first_day_of_week'];
	}
	if( $from_start )
	{
		$month_first_weekday = gmdate('w',$first_date);
		$first_day_of_first_full_week = 1;
		if( !$full_week )
		{
			$first_day_of_week = $month_first_weekday;
		}
		if( $full_week && $first_day_of_week != $month_first_weekday )
		{
			$diff = $month_first_weekday - $first_day_of_week;
			if( $diff > 0 )
			{
				$first_day_of_first_full_week = 8 - $diff;
			}
			else
			{
				$first_day_of_first_full_week = 1 - $diff;
			}
		}
		$diff = $weekday - $first_day_of_week;
		if( $diff >= 0 )
		{
			$day = $first_day_of_first_full_week + (($index-1) * 7) + $diff;
		}
		else
		{
			$day = $first_day_of_first_full_week + ($index * 7) + $diff;
		}
	}
	else
	{
		$last_day_of_week = $first_day_of_week-1;
		if( $last_day_of_week < 0 )
		{
			$last_day_of_week = 6;
		}
		$last_date = gmmktime(0, 0, 0, $month, $number_of_days_in_month, $year);
		$month_last_weekday = gmdate('w',$last_date);
		$last_day_of_last_full_week = $number_of_days_in_month;

		if( !$full_week )
		{
			$last_day_of_week = $month_last_weekday;
		}
		if( $full_week && $last_day_of_week != $month_last_weekday )
		{
			$diff = $last_day_of_week - $month_last_weekday;
			if( $diff > 0 )
			{
				$last_day_of_last_full_week = $number_of_days_in_month - 7 + $diff;
			}
			else
			{
				$last_day_of_last_full_week = $number_of_days_in_month + $diff;
			}
		}
		$diff = $weekday - $last_day_of_week;
		if( $diff > 0 )
		{
			$day = $last_day_of_last_full_week - ($index * 7) + $diff;
		}
		else
		{
			$day = $last_day_of_last_full_week - (($index-1) * 7) + $diff;
		}
	}
	if( $day < 1 || $day > $number_of_days_in_month )
	{
		$day = 0;
	}
	return $day;
}


/*------------------------------------------------------
  End helper functions for filtering the calendar
  display based on a specified event type.
------------------------------------------------------*/


/* get_recurring_event_string_via_id()
**
** Gets the displayable string that describes the frequency of a
** recurring event
**
** INPUT
**   $recurr_id - the recurring event id.
*/
function get_recurring_event_string_via_id( $recurr_id )
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
		$string = get_recurring_event_string( $row );
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
function get_recurring_event_string( $row )
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


/* calendar_generate_group_sql_for_notify_new_event()
**
** Given the data for a "group" event, find the sql
** options we need to search for users with permission
** to view the event.
**
** INPUT
**   $event_data - the data of the newly created event
**
** OUTPUT
**   sql group related options used in query
*/
function calendar_generate_group_sql_for_notify_new_event( $event_data )
{
		/* find the groups we need to notify */
		$group_sql = "AND u.user_id = ug.user_id AND g.group_id = ug.group_id AND (";
		$group_options = "";
		if( $event_data['group_id'] != 0 )
		{
			$group_options = " g.group_id = ".$event_data['group_id']." ";
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
				if( $group_options == "" )
				{
					$group_options = " g.group_id = ".$group_list[$i]." ";
				}
				else
				{
					$group_options = $group_options . "OR g.group_id = ".$group_list[$i]." ";
				}
			}
		}
		$group_sql = $group_sql . $group_options . ") ";
		return $group_sql;
}

/* calendar_notify_new_event()
**
** Notifies users who are watching the calendar of the new event
** (if the event is one the user has permission to see).
**
** INPUT
**   $event_id - the id of the newly created event
*/
function calendar_notify_new_event( $event_id )
{
	global $auth, $db, $user, $config;
	global $phpEx, $phpbb_root_path;

	$user_id = $user->data['user_id'];
	$user_notify = $user->data['user_notify'];

	$event_data = array();
	get_event_data( $event_id, $event_data );

	$sql = "";
	if( $event_data['event_access_level'] > 0 )
	{
		/* don't worry about notifications for private events
		   (ie event_data['event_access_level'] == 0) */
		if( $event_data['event_access_level'] == 1 )
		{
			$group_sql = calendar_generate_group_sql_for_notify_new_event( $event_data );
			$sql = 'SELECT w.*, u.username, u.username_clean, u.user_email, u.user_notify_type,
				u.user_jabber, u.user_lang FROM ' . RP_WATCH . ' w, ' . USERS_TABLE . ' u,
				'. GROUPS_TABLE. ' g, '.USER_GROUP_TABLE.' ug
				WHERE w.user_id = u.user_id '. $group_sql .' AND u.user_id <> '.$user_id;


		}
		else /* this is a public event */
		{
			$sql = 'SELECT w.*, u.username, u.username_clean, u.user_email, u.user_notify_type,
				u.user_jabber, u.user_lang FROM ' . RP_WATCH . ' w, ' . USERS_TABLE . ' u
				WHERE w.user_id = u.user_id AND u.user_id <> '.$user_id;
		}
		include_once($phpbb_root_path . 'includes/functions.' . $phpEx);
		include_once($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);
		$messenger = new messenger();
		$db->sql_query($sql);
		$result = $db->sql_query($sql);
		$notified_users = array();
		$notify_user_index = 0;
		while ($row = $db->sql_fetchrow($result))
		{
			if( $row['notify_status'] == 0 && !in_array($row['user_id'], $notified_users) )
			{
				// track the list of users we've notified, so we only send the email once
				// this should only be an issue if the user is a member of multiple groups
				// that were all invited to the same event, but still it should be avoided.
				$notified_users[$notify_user_index] = $row['user_id'];
				$notify_user_index++;

				$messenger->template('calendar_new_event', $row['user_lang']);
				$messenger->to($row['user_email'], $row['username']);
				$messenger->im($row['user_jabber'], $row['username']);

				$messenger->assign_vars(array(
								'USERNAME'			=> htmlspecialchars_decode($row['username']),
								'EVENT_SUBJECT'		=> $event_data['event_subject'],
								'U_CALENDAR'		=> generate_board_url() . "/calendar.$phpEx",
								'U_UNWATCH_CALENDAR'=> generate_board_url() . "/calendar.$phpEx?calWatch=0",
								'U_EVENT'			=> generate_board_url() . "/calendar.$phpEx?view=event&calEid=$event_id", )
							);

				$messenger->send($row['user_notify_type']);

				$sql = 'UPDATE ' . RP_WATCH . '
					SET ' . $db->sql_build_array('UPDATE', array(
					'notify_status'		=> (int) 1,
										)) . "
					WHERE user_id = " . $row['user_id'];
				$db->sql_query($sql);
			}

		}
		$db->sql_freeresult($result);
		$messenger->save_queue();

	}

	if( $user_notify == 1 )
	{
		calendar_watch_calendar( 1 );
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
			$s_watching_event['link'] = append_sid( "{$phpbb_root_path}calendar.$phpEx", "view=event&amp;calEid=".$event_id."&amp;calWatchE=0" );
			$s_watching_event['title'] = $user->lang['WATCH_EVENT_TURN_OFF'];
		}
		else
		{
			$s_watching_event['link'] = append_sid( "{$phpbb_root_path}calendar.$phpEx", "view=event&amp;calEid=".$event_id."&amp;calWatchE=1" );
			$s_watching_event['title'] = $user->lang['WATCH_EVENT_TURN_ON'];
		}
	}
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
function calendar_init_s_watching_calendar( &$s_watching_calendar )
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




?>

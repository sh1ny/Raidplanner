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


class raidplanner_population
{
	
	/**
	 * 
	 */
	function __construct()
	{
		
	//TODO - Insert your code here
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
					while( $this->must_find_next_occ( $row, $end_populate_limit ))
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
	private function must_find_next_occ( $row_data, $end_populate_limit )
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
		



}

?>
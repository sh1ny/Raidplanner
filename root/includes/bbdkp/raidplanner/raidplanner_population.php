<?php
/**
*
* @author alightner, Sajaki
* @package bbDKP Raidplanner
* @version CVS/SVN: $Id$
* @copyright (c) 2009 alightner
* @copyright (c) 2010 Sajaki : refactoring code into classes, adapting to bbdkp
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

// Include the base class
if (!class_exists('raidplanner_base'))
{
	require($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_base.' . $phpEx);
}

/*
 * new raid population class
 * no constructor, we follow base
 * 
 */
class raidplanner_population extends raidplanner_base
{
	
	/***
	 * function to build event array used for posting new event. called from planneradd
	 * error checking is done
	 * 
	 * @param byref : $event_data, $newraid
 	 * @param byval : $s_date_time_opts 
	 * @returns : $event_data or trigger_error
	 * 
	 */
	public function gather_raiddata( &$event_data, &$newraid, $s_date_time_opts)
	{
		global $user, $config; 
		$error = array();
		
		$event_data['event_subject']= utf8_normalize_nfc(request_var('subject', '', true));
		$event_data['event_body']	= utf8_normalize_nfc(request_var('message', '', true));
		$event_data['etype_id']		= request_var('calEType', 0);
		$event_data['group_id'] = 0;
		$event_data['group_id_list'] = ",";
		
		// get raidsize from form
		$event_data['roles_needed'] = request_var('role_needed', array(0=> 0));
		
		// get member group id
		$group_id_array = request_var('calGroupId', array(0));
		$num_group_ids = sizeof( $group_id_array );
	    if( $num_group_ids == 1 )
	    {
	    	// if only one group pass the groupid
			$event_data['group_id'] = $group_id_array[0];
	
	    }
		elseif( $num_group_ids > 1 )
		{
			// if we want multiple groups then pass the array 
			$group_index = 0;
			for( $group_index = 0; $group_index < $num_group_ids; $group_index++ )
			{
			    if( $group_id_array[$group_index] == "" )
			    {
			    	continue;
			    }
			    $event_data['group_id_list'] .= $group_id_array[$group_index] . ",";
			}
		}
		
		$event_data['event_access_level']	= request_var('calELevel', 0);
		
		// if we selected group but didn't actually a group then throw error
		if( $event_data['event_access_level'] == 1 && $num_group_ids < 1 )
		{
			$error[] = $user->lang['NO_GROUP_SELECTED'];
		}
		
		//do we track signups ?
		$event_data['track_signups'] = request_var('calTrackRsvps', 0);
	    

		/*------------------------------------------------------------------
	      Begin to find start/end times
	      NOTE: if s_date_time_opts is false we are editing all events and
	            there will not be any data to get
	    -------------------------------------------------------------------*/
		if( $s_date_time_opts )
		{
			$start_hr = request_var('calHr', 0);
			$start_mn = request_var('calMn', 0);
			$event_data['event_start_time'] = gmmktime($start_hr, $start_mn, 0, $newraid->date['month_no'], $newraid->date['day'], $newraid->date['year'] ) - $user->timezone - $user->dst;
		}
	
		// DNSBL check
		if ($config['check_dnsbl'] )
		{
			if (($dnsbl = $user->check_dnsbl('post')) !== false)
			{
				$error[] = sprintf($user->lang['IP_BLACKLISTED'], $user->ip, $dnsbl[1]);
			}
		}
		
	    /*------------------------------------------------------------------
	      Check options for recurring events
	    -------------------------------------------------------------------*/
		if( request_var('calIsRecurr', '') == "ON" )
		{
		    $event_data['is_recurr'] = 1;
			$event_data['frequency_type'] = request_var('calRFrqT', 1);
			switch ($event_data['frequency_type'])
			{
				case 2:
				case 4:
					$event_data['week_index'] = $newraid->find_week_index( $event_data['event_start_time'], true, false, $event_data['first_day_of_week'] );
					break;
				default:
					$event_data['week_index'] = 0;
					break;
			}
	
	
			$event_data['frequency'] = request_var('calRFrq', 1);
			if( $event_data['frequency'] < 1 )
			{
				$error[] = $user->lang['FREQUENCEY_LESS_THAN_1'];
			}
			$final_occ_month = request_var('calRMEnd', 0);
			$final_occ_day = request_var('calRDEnd', 0);
			$final_occ_year = request_var('calRYEnd', 0);
			if( $final_occ_month == 0 || $final_occ_month == 0 || $final_occ_month == 0 )
			{
				$event_data['final_occ_time'] = 0;
			}
			else
			{
				// we want to use the last minute of their selected day so we will populate events
				// on their last selected day, but not any day after
				$event_data['final_occ_time'] = gmmktime(23, 59, 0, $final_occ_month, $final_occ_day, $final_occ_year ) - $user->timezone - $user->dst;
				
				if( $event_data['final_occ_time'] < $event_data['event_start_time'] )
				{
					$error[] = $user->lang['NEGATIVE_LENGTH_EVENT'];
				}
				else if( $event_data['final_occ_time'] == $event_data['event_start_time'] )
				{
					$error[] = $user->lang['ZERO_LENGTH_EVENT'];
				}
			}
	
		}
		else
		{
		    $event_data['is_recurr'] = 0;
		}
		
		if (!sizeof($error))
		{
			//return the parsed event data
			return $event_data;
		}
		
	}
		
	/*
	 * inserts new planned raid in DB
	 * @param byref : $event_data, $newraid, $event_id
	 * 
	 */
	public function create_event(&$event_data, &$newraid, &$event_id)
	{
		global $db;
		
		$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
		$allow_bbcode = $allow_urls = $allow_smilies = true;
		generate_text_for_storage($event_data['event_body'], $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
		
		$recurr_id = 0;
		/*----------------------------------------------------
		   RECURRING EVENT: add it to the recurring
		   event table and begin populating the future raids
		----------------------------------------------------*/
		if( $event_data['is_recurr'] == 1 )
		{
			$event_frequency_type = $event_data['frequency_type'];
			$event_frequency = $event_data['frequency'];
			$event_week_index = $event_data['week_index'];
			$event_final_occ_time = $event_data['final_occ_time'];
			$event_duration = 0;
			
			$poster_timezone = $event_data['poster_timezone'];
			$poster_dst = $event_data['poster_dst'];
	
			$sql = 'INSERT INTO ' . RP_RECURRING . ' ' . $db->sql_build_array('INSERT', array(
					'etype_id'				=> (int) $event_data['etype_id'],
					'frequency'				=> (int) $event_frequency,
					'frequency_type'		=> (int) $event_frequency_type,
					'first_occ_time'		=> (int) $event_data['event_start_time'],
					'final_occ_time'		=> (int) $event_final_occ_time,
					'event_duration'		=> (int) $event_duration,
					'week_index'			=> (int) $event_week_index,
					'first_day_of_week'		=> (int) $event_data['first_day_of_week'],
					'last_calc_time'		=> (int) 0,
					'next_calc_time'		=> (int) $event_data['event_start_time'],
					'event_subject'			=> (string) $event_data['event_subject'],
					'event_body'			=> (string) $event_data['event_body'],
					'poster_id'				=> (int) $event_data['poster_id'],
					'poster_timezone'		=> (int) $poster_timezone,
					'poster_dst'			=> (int) $poster_dst,
					'event_access_level'	=> (int) $event_data['event_access_level'],
					'group_id'				=> (int) $event_data['group_id'],
					'group_id_list'			=> (string) $event_data['group_id_list'],
					'bbcode_uid'			=> (string) $uid,
					'bbcode_bitfield'		=> (string) $bitfield,
					'enable_bbcode'			=> (int) $allow_bbcode,
					'enable_magic_url'		=> (int) $allow_urls,
					'enable_smilies'		=> (int) $allow_smilies,
					'track_signups'			=> (int) $event_data['track_signups'],
					
					)
				);
			$db->sql_query($sql);
			$recurr_id = $db->sql_nextid();
	
			$event_id = $newraid->populate_calendar( $recurr_id );
	
		}
		/*----------------------------------------------------
		   NON-RECURRING EVENT: add it to the raids table
		----------------------------------------------------*/
		else
		{

			// insert raid
			$data = array(
					'etype_id'				=> (int) $event_data['etype_id'],
					'sort_timestamp'		=> (int) $event_data['event_start_time'],
					'event_start_time'		=> (int) $event_data['event_start_time'],
					'event_day'				=> (string) $event_data['event_day'],
					'event_subject'			=> (string) $event_data['event_subject'],
					'event_body'			=> (string) $event_data['event_body'],
					'poster_id'				=> (int) $event_data['poster_id'],
					'event_access_level'	=> (int) $event_data['event_access_level'],
					'group_id'				=> (int) $event_data['group_id'],
					'group_id_list'			=> (string) $event_data['group_id_list'],
					'bbcode_uid'			=> (string) $uid,
					'bbcode_bitfield'		=> (string) $bitfield,
					'enable_bbcode'			=> (int) $allow_bbcode,
					'enable_magic_url'		=> (int) $allow_urls,
					'enable_smilies'		=> (int) $allow_smilies,
					'track_signups'			=> (int) $event_data['track_signups'],
					'recurr_id'				=> (int) $recurr_id,
			);
			
			
			$sql = 'INSERT INTO ' . RP_RAIDS_TABLE . ' ' . $db->sql_build_array('INSERT', $data  );
			$db->sql_query($sql);
			$event_id = $db->sql_nextid();
			
			unset($data);
			$data = array();
			// populate roles needed for this raid
			foreach($event_data['roles_needed'] as $key => $slots)
			{
				$data[] = array(
					'event_id' 		=> $event_id,
					'role_id'		=> $key,
					'role_needed'	=> $slots,
				);
			}
			$db->sql_multi_insert(RP_EVENTROLES, $data);
			
		}
		
		// notify
		$this->calendar_notify_new_event( $event_id );
	
		
		
	}
	

	
	/*
	 * Edits an event
	 * 
	 * @param byref : &event_data 
	 * @param byval : $newraid, $event_id
	 */
	public function edit_event(&$event_data, $newraid, $event_id, $s_date_time_opts)
	{
		global $db;
		
		$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
		$allow_bbcode = $allow_urls = $allow_smilies = true;
		generate_text_for_storage($event_data['event_body'], $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
		
		/*---------------------------------------------
		   EDIT
		---------------------------------------------*/
		if( $event_id > 0 )
		{
			// we are only editing the one event
			if($s_date_time_opts )
			{
				// update schedule table
				$sql = 'UPDATE ' . RP_RAIDS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', array(
						'etype_id'				=> (int) $event_data['etype_id'],
						'sort_timestamp'		=> (int) $event_data['event_start_time'],
						'event_start_time'		=> (int) $event_data['event_start_time'],
						'event_day'				=> (string) $event_data['event_day'],
						'event_subject'			=> (string) $event_data['event_subject'],
						'event_body'			=> (string) $event_data['event_body'],
						'poster_id'				=> (int) $event_data['poster_id'],
						'event_access_level'	=> (int) $event_data['event_access_level'],
						'group_id'				=> (int) $event_data['group_id'],
						'group_id_list'			=> (string) $event_data['group_id_list'],
						'bbcode_uid'			=> (string) $uid,
						'bbcode_bitfield'		=> (string) $bitfield,
						'enable_bbcode'			=> $allow_bbcode,
						'enable_magic_url'		=> (int) $allow_urls,
						'enable_smilies'		=> (int) $allow_smilies,
						
						
						)) . "
					WHERE event_id = $event_id";
				$db->sql_query($sql);
				
				// delete old roles
				$sql = 'delete from ' . RP_EVENTROLES . ' where event_id  = ' .  $event_id ; 
				$db->sql_query($sql);
				
				unset($data);
				$data = array();
				// populate new roles
				foreach($event_data['roles_needed'] as $key => $slots)
				{
					$data[] = array(
						'event_id' 		=> $event_id,
						'role_id'		=> $key,
						'role_needed'	=> $slots,
					);
				}
				$db->sql_multi_insert(RP_EVENTROLES, $data);
			}
			// we are editing all occurrences of this event...
			else
			{
				$recurr_id = $event_data['recurr_id'];
				//start by updating the recurring events table
				$sql = 'UPDATE ' . RP_RECURRING . '
					SET ' . $db->sql_build_array('UPDATE', array(
						'etype_id'				=> (int) $event_data['etype_id'],
						'event_subject'			=> (string) $event_data['event_subject'],
						'event_body'			=> (string) $event_data['event_body'],
						'event_access_level'	=> (int) $event_data['event_access_level'],
						'group_id'				=> (int) $event_data['group_id'],
						'group_id_list'			=> (string) $event_data['group_id_list'],
						'enable_bbcode'			=> (int) $allow_bbcode,
						'enable_smilies'		=> (int) $allow_smilies,
						'enable_magic_url'		=> (int) $allow_urls,
						'bbcode_bitfield'		=> (string) $bitfield,
						'bbcode_uid'			=> (string) $uid,
						
						
						)) . "
					WHERE recurr_id = $recurr_id";
				$db->sql_query($sql);
		
				// now update all events of this occurence id
				$sql = 'UPDATE ' . RP_RAIDS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', array(
						'etype_id'				=> (int) $event_data['etype_id'],
						'event_subject'			=> (string) $event_data['event_subject'],
						'event_body'			=> (string) $event_data['event_body'],
						'event_access_level'	=> (int) $event_data['event_access_level'],
						'group_id'				=> (int) $event_data['group_id'],
						'group_id_list'			=> (string) $event_data['group_id_list'],
						'bbcode_uid'			=> (string) $uid,
						'bbcode_bitfield'		=> (string) $bitfield,
						'enable_bbcode'			=> (int) $allow_bbcode,
						'enable_magic_url'		=> (int) $allow_urls,
						'enable_smilies'		=> (int) $allow_smilies,
						
						
						)) . "
					WHERE recurr_id = $recurr_id";
				$db->sql_query($sql);
			}
			
			$raidplanner= new displayplanner;
			$raidplanner->calendar_add_or_update_reply($event_id, false );
			
		}
		
	}
	

/* calendar_notify_new_event()
	**
	** send email to users who are watching the calendar of the new event
	** (if the event is one the user has permission to see).
	**
	** INPUT
	**   $event_id - the id of the newly created event
	** OUTPUT
	* 
	**   
	*/
	public function calendar_notify_new_event( $event_id )
	{
		global $auth, $db, $user, $config;
		global $phpEx, $phpbb_root_path;
	
		include_once($phpbb_root_path . 'includes/functions.' . $phpEx);
		
		$user_id = $user->data['user_id'];
		$user_notify = $user->data['user_notify'];
	
		$event_data = array();
		$raidevents = new raidevents;
		$raidevents->get_event_data( $event_id, $event_data );

		switch ($event_data['event_access_level']) 
		{
		    case 0:
			   /* don't worry about notifications for private events
			   (ie event_data['event_access_level'] == 0) */
				return;
		        break;
		    case 1:
				// group event
				$sql_array = array(
				    'SELECT'    => 'w.*, u.username, u.username_clean, u.user_email, u.user_notify_type, u.user_jabber, u.user_lang  ', 
				 
				    'FROM'      => array(
				        RP_WATCH  		  => 'w',
				        USERS_TABLE       => 'u',
				        GROUPS_TABLE      => 'g',
				        USER_GROUP_TABLE  => 'ug', 
				    ),
				 
				    'WHERE'     => ' w.user_id = u.user_id AND u.user_id <> ' . $user->data['user_id'] . ' 
				    				AND u.user_id = ug.user_id AND g.group_id = ug.group_id ',
				);
				
				if( $event_data['group_id'] != 0)
				{
					$sql_array['WHERE'] .= " AND ( g.group_id = ". $event_data['group_id'].") ";
				}
				
				elseif ($event_data['group_id_list'] )
				{
					$group_list = explode( ',', $event_data['group_id_list'] );
					$sql_array['WHERE'] .= ' AND ' . $db->sql_in_set('g.group_id', $group_list);
				}
		        
		        break;
		    case 2:
				// public event
				$sql_array = array(
				    'SELECT'    => 'w.*, u.username, u.username_clean, u.user_email, u.user_notify_type, u.user_jabber, u.user_lang  ', 
				 
				    'FROM'      => array(
				        RP_WATCH  		  => 'w',
				        USERS_TABLE       => 'u',
				    ),
				 
				    'WHERE'     => 'w.user_id = u.user_id AND u.user_id <> ' . $user->data['user_id'] ,
				);
		        
		        break;
		}
		
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$db->sql_query($sql);
		
		$result = $db->sql_query($sql);
		$notified_users = array();
		$notify_user_index = 0;
		
		// Include the messenger class
		if (!class_exists('messenger'))
		{
			require($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);
		}
		$messenger = new messenger();
		
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
					'U_CALENDAR'		=> generate_board_url() . "/planner.$phpEx",
					'U_UNWATCH_CALENDAR'=> generate_board_url() . "/planner.$phpEx?calWatch=0",
					'U_EVENT'			=> generate_board_url() . "/planner.$phpEx?view=event&calEid=$event_id", )
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
	
		if( $user_notify == 1 )
		{
			$this->calendar_watch_calendar( 1 );
		}
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
	public function find_week_index( $date, $from_start, $full_week, $first_day_of_week = -1 )
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
		
	
	/**
	* Do the various checks required for removing event as well as removing it
	* Note the caller of this function must make sure that the user has
	* permission to delete the event before calling this function
	*/
	public function handle_event_delete($event_id, &$event_data)
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
			// delete all the signups for this event before deleting the event
			$sql = 'DELETE FROM ' . RP_SIGNUPS . ' WHERE event_id = ' .$db->sql_escape($event_id);
			$db->sql_query($sql);
	
			$sql = 'DELETE FROM ' . RP_EVENTS_WATCH . ' WHERE event_id = ' .$db->sql_escape($event_id);
			$db->sql_query($sql);
	
			// Delete event
			$sql = 'DELETE FROM ' . RP_RAIDS_TABLE . '
					WHERE event_id = '.$db->sql_escape($event_id);
			$db->sql_query($sql);
	
			$etype_url_opts = $this->get_etype_url_opts();
			$meta_info = append_sid("{$phpbb_root_path}planner.$phpEx", "calM=".$date['month_no']."&amp;calY=".$date['year'].$etype_url_opts);
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
				// find all of the events in this recurring event string so we can delete their signups
				$sql = 'SELECT event_id FROM ' . RP_RAIDS_TABLE . '
							WHERE recurr_id = '. $event_data['recurr_id'];
				$result = $db->sql_query($sql);
	
				// delete all the signups for this event before deleting the event
				while ($row = $db->sql_fetchrow($result))
				{
					$sql = 'DELETE FROM ' . RP_SIGNUPS . ' WHERE event_id = ' .$db->sql_escape($row['event_id']);
					$db->sql_query($sql);
	
					$sql = 'DELETE FROM ' . RP_EVENTS_WATCH . ' WHERE event_id = ' .$db->sql_escape($row['event_id']);
					$db->sql_query($sql);
				}
				$db->sql_freeresult($result);
	
				// delete the recurring event
				$sql = 'DELETE FROM ' . RP_RECURRING . '
						WHERE recurr_id = '.$db->sql_escape($event_data['recurr_id']);
				$db->sql_query($sql);
	
				// finally delete all of the events
				$sql = 'DELETE FROM ' . RP_RAIDS_TABLE . '
						WHERE recurr_id = '.$db->sql_escape($event_data['recurr_id']);
				$db->sql_query($sql);
	
				$etype_url_opts = $this->get_etype_url_opts();
				$meta_info = append_sid("{$phpbb_root_path}planner.$phpEx", "calM=".$date['month_no']."&amp;calY=".$date['year'].$etype_url_opts);
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
	
	
	/**
	* Fill smiley templates (or just the variables) with smilies, either in a window or inline
	* 
	*/
	public function generate_calendar_smilies($mode)
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
	
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
			$sql = 'SELECT * FROM ' . RP_RECURRING . '
					WHERE recurr_id = '.$recurr_id_to_pop;
		}
		else
		{
			// find all day events that need new events occurrences
			$sql = 'SELECT * FROM ' . RP_RECURRING . '
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
	
					    $sql = 'INSERT INTO ' . RP_RAIDS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
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
								'track_signups'			=> (int) $row['track_signups'],
								
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
							$start_day = $this->find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, true, false, $row['first_day_of_week'] );
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
	
					    $sql = 'INSERT INTO ' . RP_RAIDS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
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
								'track_signups'			=> (int) $row['track_signups'],
								
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
	
					    $sql = 'INSERT INTO ' . RP_RAIDS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
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
								'track_signups'			=> (int) $row['track_signups'],
								
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
					    	$start_day = $this->find_day_via_week_index( $week_day, $row['week_index'], $start_month, $start_year, true, false, $row['first_day_of_week'] );
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
	
					    $sql = 'INSERT INTO ' . RP_RAIDS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
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
								'track_signups'			=> (int) $row['track_signups'],
								
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
	
					    $sql = 'INSERT INTO ' . RP_RAIDS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
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
								'track_signups'			=> (int) $row['track_signups'],
								
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
	
					    $sql = 'INSERT INTO ' . RP_RAIDS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
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
								'track_signups'			=> (int) $row['track_signups'],
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
			$sql = 'UPDATE ' . RP_RECURRING . '
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
	
		
	/* generates the selection code necessary for group selection when making new calendar posts
	   by default no group is selected and the entire form item is disabled
	*/
	public function posting_generate_group_selection_code( $user_id )
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
	private function find_day_via_week_index( $weekday, $index, $month, $year, $from_start, $full_week, $first_day_of_week = -1 )
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
	
	/* prune_calendar()
	**
	** Cron job used to delete old events (and all of their related data:
	** signups, recurring event data, etc) after they've expired.
	**
	** The expiration date of an event = when the event ends + the prune_limit
	** specified in the calendar ACP.
	*/
	function prune_calendar()
	{
		global $auth, $db, $user, $config, $phpEx, $phpbb_root_path;
		
		$prune_limit = $config['rp_prune_limit']; 
		
		set_config ('rp_last_prune', time() ,0);
	    $cache->destroy('config');
	    	
		// delete events that have been over for $prune_limit seconds.
		$end_temp_date = time() - $prune_limit;
	
		// find all day events that finished before the prune limit
		$sort_timestamp_cutoff = $end_temp_date - 86400;
		$sql = 'SELECT event_id FROM ' . RP_RAIDS_TABLE . '
					WHERE ( (event_all_day = 1 AND sort_timestamp < '.$db->sql_escape($sort_timestamp_cutoff).')
					OR (event_all_day = 0 AND event_end_time < '.$db->sql_escape($end_temp_date).') )';
		$result = $db->sql_query($sql);
	
		// delete all the signups for this event before deleting the event
		while ($row = $db->sql_fetchrow($result))
		{
			$sql = 'DELETE FROM ' . RP_SIGNUPS . ' WHERE event_id = ' .$row['event_id'];
			$db->sql_query($sql);
	
			$sql = 'DELETE FROM ' . RP_EVENTS_WATCH . ' WHERE event_id = ' .$row['event_id'];
			$db->sql_query($sql);
	
		}
		$db->sql_freeresult($result);
	
		// now delete the old events
		$sql = 'DELETE FROM ' . RP_RAIDS_TABLE . '
					WHERE ( (event_all_day = 1 AND sort_timestamp < '.$db->sql_escape($sort_timestamp_cutoff).')
					OR (event_all_day = 0 AND event_end_time < '.$db->sql_escape($end_temp_date).') )';
		$db->sql_query($sql);
	
		// delete any recurring events that are permanently over
		$sql = 'DELETE FROM ' . RP_RECURRING . '
					WHERE (final_occ_time > 0) AND
					      (final_occ_time < '. $end_temp_date .')';
		$db->sql_query($sql);
	
	}
		






}

?>
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

class raidplanner_base
{
	
	/**
	 * 
	 */
	function __construct()
	{
		$this->_init_calendar_data();
	}
	
	public $date = array();
	public $month_names = array();
	public $available_etype_count = 0;
	public $available_etype_ids = array();
	public $available_etype_full_names = array();
	public $available_etype_display_names = array();
	public $available_etype_colors = array();
	public $available_etype_images = array();
	public $month_sel_code = "";
	public $day_sel_code = "";
	public $year_sel_code = "";
	public $mode_sel_code = "";
	
	
	
	/*
	 * checks if a user has right to post a new raid
	 * 
	 */
	public function authcheck($mode, $submit, $event_data)
	{
		global $user, $auth; 
		$is_authed = false;
		
		// Bots can't post events in the calendar
		if ($user->data['is_bot'])
		{
			redirect(append_sid("{$phpbb_root_path}index.$phpEx"));
		}
	
		// Is the user able to view events?
		if ( !$auth->acl_get('u_raidplanner_view_events') )
		{
			if ($user->data['user_id'] != ANONYMOUS)
			{
				trigger_error('USER_CANNOT_VIEW_EVENT');
			}
			trigger_error('LOGIN_EXPLAIN_POST_EVENT');
		}
	
		// Permission to do the action asked?
		switch ($mode)
		{
			case 'post':
				if ( $auth->acl_gets(
					'u_raidplanner_create_public_events', 
					'u_raidplanner_create_group_events', 
					'u_raidplanner_create_private_events'))
					{
						$is_authed = true;
						if( $submit )
						{	
							// on submit we need to double check that they have permission to create the selected type of event
							$is_authed = false;
							$test_event_level = request_var('calELevel', 0);
							switch ($test_event_level)
							{
								case 2:
									if ( $auth->acl_get('u_raidplanner_create_public_events') )
									{
										$is_authed = true;
									}
								break;
			
								case 1:
									if ( $auth->acl_get('u_raidplanner_create_group_events') )
									{
										$is_authed = true;
									}
								break;
			
								case 0:
								default:
									if ( $auth->acl_get('u_raidplanner_create_private_events') )
									{
										$is_authed = true;
									}
								break;
							}
						}
				}
			break;
		
			case 'edit':
				if ($user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_events') )
				{
					$is_authed = true;
				}
			break;
		
			case 'delete':
				if ($user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_events') )
				{
					$is_authed = true;
				}
			break;
		}
		
		if (!$is_authed)
		{
			if ($user->data['is_registered'])
			{
				if( strtoupper($mode) == "" )
				{
					$error_string = 'USER_CANNOT_POST_EVENT';
				}
				else
				{
					$error_string = 'USER_CANNOT_' . strtoupper($mode) . '_EVENT';
				}
				trigger_error($error_string);
			}
		
			login_box('', $user->lang['LOGIN_EXPLAIN_POST_EVENT']);
		}
		
		// Can we edit this post ... if we're a moderator with rights then always yes
		// else it depends on editing times, lock status and if we're the correct user
		if ($mode == 'edit' && !$auth->acl_get('m_raidplanner_edit_other_users_events'))
		{
			if ($user->data['user_id'] != $event_data['poster_id'])
			{
				trigger_error('USER_CANNOT_EDIT_EVENT');
			}
		}
		if ($mode == 'delete' && !$auth->acl_get('m_raidplanner_delete_other_users_events'))
		{
			if ($user->data['user_id'] != $event_data['poster_id'])
			{
				trigger_error('USER_CANNOT_DELETE_EVENT');
			}
		}
		
		/*-------------------------------------------
		  Does the user have permission for
		  rsvps allowing guests, & recurring events?
		---------------------------------------------*/
		$event_data['s_track_rsvps'] = false;
		if( $auth->acl_get('u_raidplanner_track_rsvps'))
		{
			$event_data['s_track_rsvps'] = true;
		}
		
		
		$event_data['s_allow_guests'] = false;
		if( $auth->acl_get('u_raidplanner_allow_guests'))
		{
			$event_data['s_allow_guests'] = true;
		}

		$event_data['s_recurring_opts'] = false;
		if( $event_id == 0 )
		{
			if( $auth->acl_get('u_raidplanner_create_recurring_events') )
			{
				$event_data['s_recurring_opts'] = true;
			}
		}
			
		$event_data['s_update_recurring_options'] = false;
		if( $user->data['user_lang'] == 'en' )
		{
			$event_data['s_update_recurring_options'] = true;
		}
		
		return $event_data;
	}
	
	
	
	/* initialize global variables used throughout
	   all of the calendar functions
	*/
	public function _init_calendar_data()
	{
		global $auth, $db, $user, $config; 
		
		/* check to see if we have already initialized things */
		if( count($this->month_names) == 0 )
		{
			$this->month_names[1] = "January";
			$this->month_names[2] = "February";
			$this->month_names[3] = "March";
			$this->month_names[4] = "April";
			$this->month_names[5] = "May";
			$this->month_names[6] = "June";
			$this->month_names[7] = "July";
			$this->month_names[8] = "August";
			$this->month_names[9] = "September";
			$this->month_names[10] = "October";
			$this->month_names[11] = "November";
			$this->month_names[12] = "December";
	
			//find the available event types:
			$sql = 'SELECT * FROM ' . EVENTS_TABLE . ' ORDER BY event_id';
			$result = $db->sql_query($sql);
			$this->available_etype_count = 0;
			while ($row = $db->sql_fetchrow($result))
			{
				$this->available_etype_ids[$this->available_etype_count] = $row['event_id'];
				$this->available_etype_full_names[$this->available_etype_count] = $row['event_name'];
				$this->available_etype_colors[$row['event_id']] = $row['event_color'];
				$this->available_etype_images[$row['event_id']] = $row['event_imagename'];
				$this->available_etype_display_names[$row['event_id']] = $row['event_name'];
				$this->available_etype_count++;
			}
			$db->sql_freeresult($result);
		}
	
		// always refresh the date...
	
		//get the current date and set it into an array
		$this->date['day'] = request_var('calD', '');
		$this->date['month'] = request_var('calM', '');
		$this->date['month_no'] = request_var('calM', '');
		$this->date['year'] = request_var('calY', '');
	
		$temp_now_time = time() + $user->timezone + $user->dst;
	
		if( $this->date['day'] == "" )
		{
			$this->date['day'] = gmdate("d", $temp_now_time);
		}
	
		if( $this->date['month'] == "" )
		{
			$this->date['month'] = gmdate("F", $temp_now_time);
			$this->date['month_no'] = gmdate("n", $temp_now_time);
			$this->date['prev_month'] = gmdate("n", $temp_now_time) - 1;
			$this->date['next_month'] = gmdate("n", $temp_now_time) + 1;
	
		}
		else
		{
			$this->date['month'] = $this->month_names[$this->date['month']];
			$this->date['prev_month'] = $this->date['month'] - 1;
			$this->date['next_month'] = $this->date['month'] + 1;
		}
	
		if( $this->date['year'] == "" )
		{
			$this->date['year']	= gmdate('Y', $temp_now_time);
		}
		
		// make sure this day exists - ie there is no February 31st.
		$number_days = gmdate("t", gmmktime( 0,0,0,$this->date['month_no'], 1, $this->date['year']));
		if( $number_days < $this->date['day'] )
		{
		    $this->date['day'] = $number_days;
		}
	}
	
	/*------------------------------------------------------
	  Begin helper functions for filtering the calendar
	  display based on a specified event type.
	------------------------------------------------------*/
	public function get_etype_filter()
	{
		global $db;
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			return "";
		}
		return " AND etype_id = ".$db->sql_escape($calEType)." ";
	}
	
	public function get_etype_url_opts()
	{
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			return "";
		}
		return "&amp;calEType=".$calEType;
	}
	
	public function get_etype_post_opts()
	{
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			return "";
		}
		return "calEType=".$calEType;
	}
	
	
}

?>
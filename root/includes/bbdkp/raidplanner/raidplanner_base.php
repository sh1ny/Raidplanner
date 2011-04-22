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
	public $raid_plan_count = 0;
	public $raid_plan_ids = array();
	public $raid_plan_names = array();
	public $raid_plan_displaynames = array();
	public $raid_plan_colors = array();
	public $raid_plan_images = array();
	public $month_sel_code = "";
	public $day_sel_code = "";
	public $year_sel_code = "";
	public $mode_sel_code = "";
	
	
	
	/*
	 * checks if a user has right to post a new raid
	 * @param byref : $raidplan_data
	 */
	public function authcheck($mode, $submit, &$raidplan_data, $raidplan_id)
	{
		global $user, $auth; 
		$is_authed = false;
		
		// Bots can't post raidplans in the calendar
		if ($user->data['is_bot'])
		{
			redirect(append_sid("{$phpbb_root_path}index.$phpEx"));
		}
	
		// Is the user able to view raidplans?
		if ( !$auth->acl_get('u_raidplanner_view_raidplans') )
		{
			if ($user->data['user_id'] != ANONYMOUS)
			{
				trigger_error('USER_CANNOT_VIEW_RAIDPLAN');
			}
			trigger_error('LOGIN_EXPLAIN_POST_RAIDPLAN');
		}
	
		// Permission to do the action asked?
		switch ($mode)
		{
			case 'post':
				if ( $auth->acl_gets(
					'u_raidplanner_create_public_raidplans', 
					'u_raidplanner_create_group_raidplans', 
					'u_raidplanner_create_private_raidplans'))
					{
						$is_authed = true;
						if( $submit )
						{	
							// on submit we need to double check that they have permission to create the selected type of raidplan
							$is_authed = false;
							$test_raidplan_level = request_var('calELevel', 0);
							switch ($test_raidplan_level)
							{
								case 2:
									if ( $auth->acl_get('u_raidplanner_create_public_raidplans') )
									{
										$is_authed = true;
									}
								break;
			
								case 1:
									if ( $auth->acl_get('u_raidplanner_create_group_raidplans') )
									{
										$is_authed = true;
									}
								break;
			
								case 0:
								default:
									if ( $auth->acl_get('u_raidplanner_create_private_raidplans') )
									{
										$is_authed = true;
									}
								break;
							}
						}
				}
			break;
		
			case 'edit':
				if ($user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_raidplans') )
				{
					$is_authed = true;
				}
			break;
		
			case 'delete':
				if ($user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_raidplans') )
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
					$error_string = 'USER_CANNOT_POST_RAIDPLAN';
				}
				else
				{
					$error_string = 'USER_CANNOT_' . strtoupper($mode) . '_RAIDPLAN';
				}
				trigger_error($error_string);
			}
		
			login_box('', $user->lang['LOGIN_EXPLAIN_POST_RAIDPLAN']);
		}
		
		// Can we edit this post ... if we're a moderator with rights then always yes
		// else it depends on editing times, lock status and if we're the correct user
		if ($mode == 'edit' && !$auth->acl_get('m_raidplanner_edit_other_users_raidplans'))
		{
			if ($user->data['user_id'] != $raidplan_data['poster_id'])
			{
				trigger_error('USER_CANNOT_EDIT_RAIDPLAN');
			}
		}
		if ($mode == 'delete' && !$auth->acl_get('m_raidplanner_delete_other_users_raidplans'))
		{
			if ($user->data['user_id'] != $raidplan_data['poster_id'])
			{
				trigger_error('USER_CANNOT_DELETE_RAIDPLAN');
			}
		}
		
		/*-------------------------------------------
		  Does the user have permission for
		  signups allowing guests, & recurring raidplans?
		---------------------------------------------*/
		$raidplan_data['s_track_signups'] = false;
		if( $auth->acl_get('u_raidplanner_track_signups'))
		{
			$raidplan_data['s_track_signups'] = true;
		}
		
		$raidplan_data['s_recurring_opts'] = false;
		if( $raidplan_id == 0 )
		{
			if( $auth->acl_get('u_raidplanner_create_recurring_raidplans') )
			{
				$raidplan_data['s_recurring_opts'] = true;
			}
		}
			
		$raidplan_data['s_update_recurring_options'] = false;
		if( $user->data['user_lang'] == 'en' )
		{
			$raidplan_data['s_update_recurring_options'] = true;
		}
		
		return $raidplan_data;
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
	
			//find the available raidplan types:
			$sql = 'SELECT * FROM ' . EVENTS_TABLE . ' ORDER BY raidplan_id';
			$result = $db->sql_query($sql);
			$this->raid_plan_count = 0;
			while ($row = $db->sql_fetchrow($result))
			{
				$this->raid_plan_ids[$this->raid_plan_count] = $row['raidplan_id'];
				$this->raid_plan_names[$this->raid_plan_count] = $row['raidplan_name'];
				$this->raid_plan_colors[$row['raidplan_id']] = $row['raidplan_color'];
				$this->raid_plan_images[$row['raidplan_id']] = $row['raidplan_imagename'];
				$this->raid_plan_displaynames[$row['raidplan_id']] = $row['raidplan_name'];
				$this->raid_plan_count++;
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
	  display based on a specified raidplan type.
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
	
	
	/* calendar_watch_calendar()
	**
	** Adds/removes the current user into the RP_WATCH table
	** so that they can start/stop recieving notifications about new raidplans
	**
	** INPUT
	**    $turn_on = 1 - the user wants to START watching the calendar
	**    $turn_on = 0 - the user wants to STOP watching the calendar
	*/
	public function calendar_watch_calendar($turn_on = 1)
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
	
	
	
}

?>
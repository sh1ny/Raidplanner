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

class raidplanner_base
{
	
	public $date = array();
	public $month_names = array();
	public $raid_plan_ids = array();
	public $raid_plan_names = array();
	public $raid_plan_displaynames = array();
	public $raid_plan_colors = array();
	public $raid_plan_images = array();
	public $raid_plan_count = 0;
	public $month_sel_code = "";
	public $day_sel_code = "";
	public $year_sel_code = "";
	public $mode_sel_code = "";

	function __construct()
	{
		global $auth, $db, $user, $config; 

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

		//find the available events from bbDKP, store them in a global array
		$sql = 'SELECT * FROM ' . EVENTS_TABLE . ' ORDER BY event_id';
		$result = $db->sql_query($sql, 600000);
		$this->raid_plan_count = 0;
		while ($row = $db->sql_fetchrow($result))
		{
			$this->raid_plan_ids[$this->raid_plan_count] = $row['event_id'];
			$this->raid_plan_names[$this->raid_plan_count] = $row['event_name'];
			$this->raid_plan_displaynames[$row['event_id']] = $row['event_name'];
			$this->raid_plan_colors[$row['event_id']] = $row['event_color'];
			$this->raid_plan_images[$row['event_id']] = $row['event_imagename'];
			$this->raid_plan_count++;
		}
		$db->sql_freeresult($result);
	
		// always refresh the date...
		$temp_now_time = time() + $user->timezone + $user->dst;
		
		//get the selected date and set it into an array
		$this->date['day'] = request_var('calD', '');
		$this->date['month'] = request_var('calM', '');
		$this->date['month_no'] = request_var('calM', '');
		$this->date['year'] = request_var('calY', '');
	
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
	

	
	
	/**
	* Adds/removes the current user into the RP_WATCH table
	* so that they can start/stop recieving notifications about new raidplans
	*
 	* INPUT
	*    $turn_on = 1 - the user wants to START watching the calendar
	*    $turn_on = 0 - the user wants to STOP watching the calendar
	*
	* @param int $turn_on
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
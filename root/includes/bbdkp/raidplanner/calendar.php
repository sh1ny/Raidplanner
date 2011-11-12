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
*/

/**
 * @ignore
 */
if ( !defined('IN_PHPBB') OR !defined('IN_BBDKP') )
{
	exit;
}

/**
 * the base class
 *
 */
abstract class calendar
{
	/**
	 * core date object. 
	 *
	 * @var array
	 */
	public $date = array();
	
	/**
	 * month names
	 *
	 * @var array
	 */
	public $month_names = array();

	/**
	 * names of days. depends on acp setting
	 *
	 * @var array
	 */
	public $daynames = array();
	
	
	/**
	 * number of days in month
	 *
	 * @var int
	 */public $days_in_month = 0;
	
	/**
	 * selectors
	 *
	 */
	public $month_sel_code = "";
	public $day_sel_code = "";
	public $year_sel_code = "";
	public $mode_sel_code = "";
	
	/**
	 * 
	 *
	 * @var unknown_type
	 */
	public $group_options;
	public $period_start;
	public $period_end;
	public $timestamp;
	
	/**
	 * 
	 */
	function __construct($arg)
	{
		global $config; 
		
		//set month names (common.php lang entry)
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
		
		//get the selected date and set it into an array
		$this->date['day'] = request_var('calD', date("d", time()));
		$this->date['month'] = $this->month_names[ request_var('calM', date("n", time()))] ;
		$this->date['month_no'] = request_var('calM', date("n", time()) );
		$this->date['year'] = request_var('calY', date("Y", time()) );
		
		$this->date['prev_month'] = $this->date['month'] - 1;
		$this->date['next_month'] = $this->date['month'] + 1;
		
		$this->days_in_month = cal_days_in_month(CAL_GREGORIAN, $this->date['month_no'], $this->date['year']);
		
		//set day names
		$this->get_weekday_names();
		
		//get utc date
		$this->timestamp = 	gmmktime(0, 0, 0, $this->date['month_no'], $this->date['day'], $this->date['year']);
		
		$this->group_options = $this->get_sql_group_options();
	}
	
	/**
	 * get gmt timestamp for first day for current (gmt) timestamp
	 *
	 * @param int $inDate
	 * @return int
	 */
	protected function Get1DoM($inDate) 
	{
		//in  1321056000
		//GMT: Sat, 12 Nov 2011 00:00:00 GMT
		//Your time zone: Sat Nov 12 01:00:00 2011 GMT+1
		global $user;
		$xdate = gmmktime(0,0,0, gmdate('m',$inDate), 01, gmdate('Y',$inDate)) ;
		//GMT: Tue, 01 Nov 2011 00:00:00 GMT
		// Your time zone: Tue Nov 1 01:00:00 2011 GMT+1
		return $xdate;
	}
	
	/**
	 * get gmt timestamp for last day for current gmt timestamp
	 *
	 * @param int $inDate
	 * @return int
	 */
	protected function GetLDoM($inDate) 
	{
		//in  1321056000
		//GMT: Sat, 12 Nov 2011 00:00:00 GMT
		//Your time zone: Sat Nov 12 01:00:00 2011 GMT+1		
		global $user;
		$month = gmdate('m', $inDate);
		$year = gmdate('Y', $inDate);
		date_default_timezone_set('UTC');
		$result = strtotime("{$year}-{$month}-01");
		//go back 1 second
		$dateEnd = strtotime('-1 second',strtotime('+1 month', $result ));
		//GMT: Wed, 30 Nov 2011 23:59:59 GMT
		//Your time zone: Thu Dec 1 00:59:59 2011 GMT+1
		return $dateEnd;
	}
	
	/**
	 * Displays header, week, month, or day (see implementations)
	 * 
	 */
	public abstract function display();
	
	
	/**
	 * fday is used to determine in what day we are starting with in week view
	 *
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * @param int $first_day_of_week
	 * @return int
	 */
	protected function get_fday($day, $month, $year)
	{
		global $config;
		
		/**
		 * 0=mon
		 * 1=tue
		 * 2=wed
		 * 3=thu
		 * 4=fri
		 * 5=sat
		 * 6=sun
		 */
		$fday = gmdate("N",gmmktime(0,0,0, $month, $day, $year)) - 1;
		
		// first day 0 being monday in acp, 
		$fday = $fday -  (int) $config['rp_first_day_of_week'];
		if( $fday < 0 )
		{
			$fday = $fday + 7;
		}
		return $fday;
	}
	
	/**
	 * Generates array of birthdays for the given UTC range for users/founders
	 *
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * @return string
	 */
	protected function generate_birthday_list($from, $end)
	{
		global $db, $user, $config;
		
		$birthday_list = "";
		if ($config['load_birthdays'] && $config['allow_birthdays'])
		{
			
			$day1= gmdate("j", $from);
			$day2= gmdate("j", $end);
			$month= gmdate("n", $from);
			$year= gmdate("Y", $from);
			
			$sql = 'SELECT user_id, username, user_colour, user_birthday
					FROM ' . USERS_TABLE . "
					WHERE (( user_birthday >= '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $day1, $month,$year )) . "'
					AND user_birthday <= '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $day2, $month,$year )) . "')
					OR user_birthday " . $db->sql_like_expression($db->any_char . '-' . sprintf( '%s', $month)  .'-' . $db->any_char) . ' ) 
					AND user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')
					ORDER BY user_birthday ASC';
			$result = $db->sql_query($sql);
			$oldday= $newday = "";
			while ($row = $db->sql_fetchrow($result))
			{
				$birthday_str = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
				$age = (int) substr($row['user_birthday'], -4);
				$birthday_str .= ' (' . ($year - $age) . ')';
				
				$newday = trim(substr($row['user_birthday'],0, 2));
				
				if($oldday != $newday)
				{
					// new birthday found, make new string
					$daystr = $birthday_str;
					$birthday_list[$newday] = array(
						'day' => $row['user_birthday'],
						'bdays' =>  $user->lang['BIRTHDAYS'].": ". $daystr,
					);
					
					
				}
				else 
				{
					// other bday on same day, add it
					$daystr = $birthday_list[$oldday]['bdays'] .", ". $birthday_str;
					// modify array entry
					$birthday_list[$oldday] = array(
						'day' => $row['user_birthday'],
						'bdays' =>  $daystr,
					);
					
				}
				$oldday = $newday;
				
			}
			$db->sql_freeresult($result);
		}
	
		return $birthday_list;
	}
	
	/*
	 * return group list 
	 */
	private function get_sql_group_options()
	{
		global $user, $auth, $db;
	
		// What groups is this user a member of?
	
		/* don't check for hidden group setting -
		  if the raidplan was made by the admin for a hidden group -
		  members of the hidden group need to be able to see the raidplan in the calendar */
	
		$sql = 'SELECT g.group_id, g.group_name, g.group_type
				FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
				WHERE ug.user_id = '.$db->sql_escape($user->data['user_id']).'
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
	
	
	/* 
	 * "shift" names of weekdays depending on which day we want to display as the first day of the week
	*/
	private function get_weekday_names()
	{
		global $config, $user;
		switch((int) $config['rp_first_day_of_week'])
		{
			case 0:
				//monday
				$this->daynames[6] = $user->lang['datetime']['Sunday'];
				$this->daynames[0] = $user->lang['datetime']['Monday'];
				$this->daynames[1] = $user->lang['datetime']['Tuesday'];
				$this->daynames[2] = $user->lang['datetime']['Wednesday'];
				$this->daynames[3] = $user->lang['datetime']['Thursday'];
				$this->daynames[4] = $user->lang['datetime']['Friday'];
				$this->daynames[5] = $user->lang['datetime']['Saturday'];
				break;
			case 1:
				//tue
				$this->daynames[5] = $user->lang['datetime']['Sunday'];
				$this->daynames[6] = $user->lang['datetime']['Monday'];
				$this->daynames[0] = $user->lang['datetime']['Tuesday'];
				$this->daynames[1] = $user->lang['datetime']['Wednesday'];
				$this->daynames[2] = $user->lang['datetime']['Thursday'];
				$this->daynames[3] = $user->lang['datetime']['Friday'];
				$this->daynames[4] = $user->lang['datetime']['Saturday'];
				break;
			case 2:
				//wed
				$this->daynames[4] = $user->lang['datetime']['Sunday'];
				$this->daynames[5] = $user->lang['datetime']['Monday'];
				$this->daynames[6] = $user->lang['datetime']['Tuesday'];
				$this->daynames[0] = $user->lang['datetime']['Wednesday'];
				$this->daynames[1] = $user->lang['datetime']['Thursday'];
				$this->daynames[2] = $user->lang['datetime']['Friday'];
				$this->daynames[3] = $user->lang['datetime']['Saturday'];
				break;
			case 3:
				//thu
				$this->daynames[3] = $user->lang['datetime']['Sunday'];
				$this->daynames[4] = $user->lang['datetime']['Monday'];
				$this->daynames[5] = $user->lang['datetime']['Tuesday'];
				$this->daynames[6] = $user->lang['datetime']['Wednesday'];
				$this->daynames[0] = $user->lang['datetime']['Thursday'];
				$this->daynames[1] = $user->lang['datetime']['Friday'];
				$this->daynames[2] = $user->lang['datetime']['Saturday'];
				break;
			case 4:
				//fri
				$this->daynames[2] = $user->lang['datetime']['Sunday'];
				$this->daynames[3] = $user->lang['datetime']['Monday'];
				$this->daynames[4] = $user->lang['datetime']['Tuesday'];
				$this->daynames[5] = $user->lang['datetime']['Wednesday'];
				$this->daynames[6] = $user->lang['datetime']['Thursday'];
				$this->daynames[0] = $user->lang['datetime']['Friday'];
				$this->daynames[1] = $user->lang['datetime']['Saturday'];
				break;
			case 5:
				//sat
				$this->daynames[1] = $user->lang['datetime']['Sunday'];
				$this->daynames[2] = $user->lang['datetime']['Monday'];
				$this->daynames[3] = $user->lang['datetime']['Tuesday'];
				$this->daynames[4] = $user->lang['datetime']['Wednesday'];
				$this->daynames[5] = $user->lang['datetime']['Thursday'];
				$this->daynames[6] = $user->lang['datetime']['Friday'];
				$this->daynames[0] = $user->lang['datetime']['Saturday'];
				break;
			case 6:
				//sun
				$this->daynames[0] = $user->lang['datetime']['Sunday'];
				$this->daynames[1] = $user->lang['datetime']['Monday'];
				$this->daynames[2] = $user->lang['datetime']['Tuesday'];
				$this->daynames[3] = $user->lang['datetime']['Wednesday'];
				$this->daynames[4] = $user->lang['datetime']['Thursday'];
				$this->daynames[5] = $user->lang['datetime']['Friday'];
				$this->daynames[6] = $user->lang['datetime']['Saturday'];
				break;
		}
	}
	
}

?>
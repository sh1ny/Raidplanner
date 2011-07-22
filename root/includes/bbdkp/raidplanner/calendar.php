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
	
	public $date = array();
	public $month_names = array();
	public $month_sel_code = "";
	public $day_sel_code = "";
	public $year_sel_code = "";
	public $mode_sel_code = "";
	public $group_options;
	public $period_start;
	public $period_end;
	
	/**
	 * 
	 */
	function __construct($arg)
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
		
		$this->_init_view_selection_code( $arg );
		$this->_set_date_prev_next( $arg );
		
		$first_day_of_week = $config['rp_first_day_of_week'];
		$sunday= $monday= $tuesday= $wednesday= $thursday= $friday= $saturday='';
		
		$this->group_options = $this->get_sql_group_options();
		
		
		
	}
	
	private function Get1stdateofMonth($dateThis) 
	{
		$retVal = NULL;
		if (is_numeric($dateThis)) 
		{
			$dateSoM = strtotime(date('Y',$dateThis) . '-' . date('m',$dateThis) . '-01');
			if (is_numeric($dateSoM)) 
			{
				$retVal = $dateSoM;
			}
		}
		return $retVal;
	}
	
	function GetLDoM($dateThis) 
	{
		$retVal = NULL;
		if (is_numeric($dateThis)) 
		{
			$dateSoM = strtotime(date('Y',$dateThis) . '-' . date('m',$dateThis) . '-01');
			$dateCog = strtotime('+1 month',$dateSoM);
			$dateEoM = strtotime('-1 day',$dateCog );
			if (is_numeric($dateEoM)) 
			{
				$retVal = $dateEoM;
			}
		}
		return $retVal;
	}
	
	/**
	 * Displays common Calendar elements, header message
	 * 
	 */
	public function displayCalframe()
	{
		
		global $config, $template, $db;
		
		$sql = 'SELECT announcement_msg, bbcode_uid, bbcode_bitfield, bbcode_options FROM ' . RP_RAIDPLAN_ANNOUNCEMENT;
		$db->sql_query($sql);
		$result = $db->sql_query($sql);
		while ( $row = $db->sql_fetchrow($result) )
		{
			$text = $row['announcement_msg'];
			$bbcode_uid = $row['bbcode_uid'];
			$bbcode_bitfield = $row['bbcode_bitfield'];
			$bbcode_options = $row['bbcode_options'];
		}
		
		$message = generate_text_for_display($text, $bbcode_uid, $bbcode_bitfield, $bbcode_options);
		
		$template->assign_vars(array(
			'S_SHOW_WELCOME_MSG'	=> ($config ['rp_show_welcomemsg'] == 1) ? true : false,
			'WELCOME_MSG'		=> $message,
		));
	
	
	}
	
	
	/**
	 * Displays week, month, day or raidplan, see implementations
	 * 
	 */
	public abstract function display();
	
	protected function get_etype_filter()
	{
		global $db;
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			return "";
		}
		return " AND etype_id = ".$db->sql_escape($calEType)." ";
	}
	
	protected function get_etype_url_opts()
	{
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			return "";
		}
		return "&amp;calEType=".$calEType;
	}
	
	protected function get_etype_post_opts()
	{
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			return "";
		}
		return "calEType=".$calEType;
	}
	
	
	/**
	 * Initialize the pulldown menus that allow the user
	 *  to jump from one calendar display mode/time to another
	 *
	 * @param string $view_mode
	 */
	protected function _init_view_selection_code( $view_mode )
	{
		global $auth, $db, $user, $config; 
	
		// create RP_VIEW_OPTIONS
		$this->month_sel_code  = "<select name='calM' id='calM'>\n";
		for( $i = 1; $i <= 12; $i++ )
		{
			$selected = ($this->date['month_no'] == $i ) ? ' selected="selected"' : '';
			$this->month_sel_code .= '<option value="'.$i.'"'.$selected.'>'.$user->lang['datetime'][$this->month_names[$i]].'</option>';
		}
		$this->month_sel_code .= "</select>";
	
		$this->day_sel_code  = "<select name='calD' id='calD'>";
		for( $i = 1; $i <= 31; $i++ )
		{
			$selected = ( (int) $this->date['day'] == $i ) ? ' selected="selected"' : '';
			$this->day_sel_code .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
		}
		$this->day_sel_code .= "</select>";
	
		$temp_year	=	gmdate('Y');
		$this->year_sel_code  = "<select name='calY' id='calY'>";
		for( $i = $temp_year-1; $i < ($temp_year+5); $i++ )
		{
			$selected = ( (int) $this->date['year'] == $i ) ? ' selected="selected"' : '';
			$this->year_sel_code .= "<option value='".$i."'>".$i."</option>";
		}
		$this->year_sel_code .= "</select>";
		
		$this->mode_sel_code = "<select name='view' id='view'>";
		$this->mode_sel_code .= "<option value='month'>".$user->lang['MONTH']."</option>";
		$this->mode_sel_code .= "<option value='week'>".$user->lang['WEEK']."</option>";
		$this->mode_sel_code .= "<option value='day'>".$user->lang['DAY']."</option>";
		$this->mode_sel_code .= "</select>";
		
		$temp_find_str = "value='".$view_mode."'>";
		$temp_replace_str = "value='".$view_mode."' selected='selected'>";
		$this->mode_sel_code = str_replace( $temp_find_str, $temp_replace_str, $this->mode_sel_code );
		
	}
	
	
	/**
	 * used to find info about the previous and next [day, week, or month]
	 *
	 * @param string $view_mode
	 */
	protected function _set_date_prev_next( $view_mode )
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
	protected function get_weekday_names( $first_day_of_week, &$sunday, &$monday, &$tuesday, &$wednesday, &$thursday, &$friday, &$saturday )
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
	protected function get_fday($day, $month, $year, $first_day_of_week)
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
	
	/**
	 * Generates the list of birthdays for the given date
	 *
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * @return string
	 */
	public function generate_birthday_list( $day, $month, $year )
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
				// @todo TRANSLATION ISSUE HERE!!!
				$birthday_list .= (($birthday_list != '') ? ', ' : '') . get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
				if ($age = (int) substr($row['user_birthday'], -4))
				{
					// @todo TRANSLATION ISSUE HERE!!!
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
	
}

?>
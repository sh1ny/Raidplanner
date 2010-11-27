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
	
	
	
}

?>
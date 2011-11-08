<?php
/**
*
* @author Sajaki
* @package bbDKP Raidplanner
* @copyright (c) 2011 Sajaki
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

// Include the base class
if (!class_exists('calendar'))
{
	require($phpbb_root_path . 'includes/bbdkp/raidplanner/calendar.' . $phpEx);
}

/**
 * implements a calendar frame
 *
 */
class rpframe extends calendar
{
	private $mode = '';
	private $message = '';
	
	/**
	 * 
	 */
	function __construct()
	{
		$this->mode="frame";
		parent::__construct($this->mode);
	}
	
	/**
	 * @see calendar::display()
	 * implements abstract method
	 */
	public function display()
	{	
		/* shows frame oject. 
		 * 
		 * "$this" = Object of: rpframe	
			rpframe::mode = (string:5) frame	
			date = Array [10]	
				day = (string:2) 19	
				month = (string:6) August	
				month_no = (string:1) 8	
				year = (string:4) 2011	
				prev_month = (string:1) 8	
				next_month = (string:1) 8	
				prev_day = (string:2) 18	
				next_day = (string:2) 20	
				prev_year = (string:4) 2011	
				next_year = (string:4) 2011	
			month_names = Array [12]	
				1 = (string:7) January	
				2 = (string:8) February	
				3 = (string:5) March	
				4 = (string:5) April	
				5 = (string:3) May	
				6 = (string:4) June	
				7 = (string:4) July	
				8 = (string:6) August	
				9 = (string:9) September	
				10 = (string:7) October	
				11 = (string:8) November	
				12 = (string:8) December	
			daynames = Array [7]	
				6 = (string:6) Sunday	
				0 = (string:6) Monday	
				1 = (string:7) Tuesday	
				2 = (string:9) Wednesday	
				3 = (string:8) Thursday	
				4 = (string:6) Friday	
				5 = (string:8) Saturday	
			month_sel_code = (string:461) <select name='calM' id='calM'>\n<option value="1">January</option><option value="2">February</option><option value="3">March</option><option value="4">April</option><option value="5">May</option><option value="6">June</option><option value="7">July</option><option value="8" selected="selected">August</option><option value="9">September</option><option value="10">October</option><option value="11">November</option><option value="12">December</option></select>	
			day_sel_code = (string:971) <select name='calD' id='calD'><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19" selected="selected">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option></select>	
			year_sel_code = (string:263) <select name='calY' id='calY'><option value="2010">2010</option><option value="2011" selected="selected">2011</option><option value="2012">2012</option><option value="2013">2013</option><option value="2014">2014</option><option value="2015">2015</option></select>	
			mode_sel_code = (string:141) <select name='view' id='view'><option value='month'>Month</option><option value='week'>Week</option><option value='day'>Day</option></select>	
			group_options = (string:134) group_id = 5 OR group_id_list LIKE '%,5,%' OR group_id = 4 OR group_id_list LIKE '%,4,%' OR group_id = 2 OR group_id_list LIKE '%,2,%'	
			period_start = null	
			period_end = null	
			timestamp = (int) 1313704800	
		 * 
		 */
		$this->displayCalframe();
	}
	
	/**
	 * Displays common Calendar elements, header message
	 * 
	 */
	private function displayCalframe()
	{
		global $config, $user, $template, $db, $phpEx, $phpbb_root_path;
		
		// set WELCOME_MSG
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
		
		$this->message = generate_text_for_display($text, $bbcode_uid, $bbcode_bitfield, $bbcode_options);
		
		// create RP_VIEW_OPTIONS
		$view_mode=request_var('view', 'month');
		$this->mode_sel_code = "<select name='view' id='view'>";
		$this->mode_sel_code .= "<option value='month'>".$user->lang['MONTH']."</option>";
		$this->mode_sel_code .= "<option value='week'>".$user->lang['WEEK']."</option>";
		$this->mode_sel_code .= "<option value='day'>".$user->lang['DAY']."</option>";
		$this->mode_sel_code .= "</select>";
				
		$temp_find_str = "value='".$view_mode."'>";
		$temp_replace_str = "value='".$view_mode."' selected='selected'>";
		$this->mode_sel_code = str_replace( $temp_find_str, $temp_replace_str, $this->mode_sel_code );
		
		$this->month_sel_code  = "<select name='calM' id='calM'>\n";
		for( $i = 1; $i <= 12; $i++ )
		{
			$selected = ($this->date['month_no'] == $i ) ? ' selected="selected"' : '';
			$this->month_sel_code .= '<option value="'.$i.'"'.$selected.'>'.$user->lang['datetime'][$this->month_names[$i]].'</option>';
		}
		$this->month_sel_code .= "</select>";
	
		$this->day_sel_code  = "<select name='calD' id='calD'>";
		
		//if in raidplan mode let pulldown begin at today
		$begin = 1;
		for( $i = $begin; $i <= $this->days_in_month; $i++ )
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
			$this->year_sel_code .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
		}
		$this->year_sel_code .= "</select>";

		// make url of buttons
		if($view_mode === "week")
		{
			$this->date['prev_day'] = gmdate("d", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']-7, $this->date['year'] ));
			$this->date['next_day'] = gmdate("d", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']+7, $this->date['year'] ));
			$this->date['prev_month'] = gmdate("n", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']-7, $this->date['year']));
			$this->date['next_month'] = gmdate("n", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']+7, $this->date['year']));
			$this->date['prev_year']  = gmdate("Y", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']-7, $this->date['year']));
			$this->date['next_year']  = gmdate("Y", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']+7, $this->date['year']));
			
			// set previous & next links
			$prev_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['prev_day']."&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year']);
			$next_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['next_day']."&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year']);
		}
		elseif($view_mode === "day")
		{
		
			$this->date['prev_day'] = gmdate("d", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']-1, $this->date['year'] ));
			$this->date['next_day'] = gmdate("d", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']+1, $this->date['year'] ));
			$this->date['prev_month'] = gmdate("n", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']-1, $this->date['year']));
			$this->date['next_month'] = gmdate("n", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']+1, $this->date['year']));
			$this->date['prev_year']  = gmdate("Y", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']-1, $this->date['year']));
			$this->date['next_year']  = gmdate("Y", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']+1, $this->date['year']));
			
			$prev_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$this->date['prev_day']."&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year']);
			$next_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$this->date['next_day']."&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year']);
		}
		elseif($view_mode === "raidplan" )
		{
		
			$this->date['prev_day'] = gmdate("d", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']-1, $this->date['year'] ));
			$this->date['next_day'] = gmdate("d", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']+1, $this->date['year'] ));
			$this->date['prev_month'] = gmdate("n", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']-1, $this->date['year']));
			$this->date['next_month'] = gmdate("n", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']+1, $this->date['year']));
			$this->date['prev_year']  = gmdate("Y", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']-1, $this->date['year']));
			$this->date['next_year']  = gmdate("Y", gmmktime(0,0,0, $this->date['month_no'], $this->date['day']+1, $this->date['year']));
			
			$prev_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=showadd&amp;calD=".$this->date['prev_day']."&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year']);
			$next_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=showadd&amp;calD=".$this->date['next_day']."&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year']);
		}		
		elseif($view_mode === "month")
		{
			$this->date['prev_day'] = gmdate("d", gmmktime(0,0,0, $this->date['month_no']-1, $this->date['day'], $this->date['year'] ));
			$this->date['next_day'] = gmdate("d", gmmktime(0,0,0, $this->date['month_no']+1, $this->date['day'], $this->date['year'] ));
			$this->date['prev_month'] = gmdate("n", gmmktime(0,0,0, $this->date['month_no'] -1, $this->date['day'], $this->date['year']));
			$this->date['next_month'] = gmdate("n", gmmktime(0,0,0, $this->date['month_no'] +1, $this->date['day'], $this->date['year']));
			$this->date['prev_year']  = gmdate("Y", gmmktime(0,0,0, $this->date['month_no'] -1, $this->date['day'], $this->date['year']));
			$this->date['next_year']  = gmdate("Y", gmmktime(0,0,0, $this->date['month_no'] +1, $this->date['day'], $this->date['year']));
			
			$prev_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year']);
			$next_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year']);
		}
		
		$template->assign_vars(array(
			'S_PLANNER_RAIDFRAME'	=> true,
			'S_SHOW_WELCOME_MSG'	=> ($config ['rp_show_welcomemsg'] == 1) ? true : false,
			'MODE_VIEW_OPTIONS' 	=> $this->mode_sel_code, 
			'CALENDAR_VIEW_OPTIONS' => $this->month_sel_code.' '.$this->day_sel_code.' '.$this->year_sel_code,
			'CALENDAR_PREV'			=> $prev_link,
			'CALENDAR_NEXT'			=> $next_link,
			'WELCOME_MSG'			=> $this->message,
		));
		
		
	
	}
	
}

?>
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

// Include the base class
if (!class_exists('calendar'))
{
	require($phpbb_root_path . 'includes/bbdkp/raidplanner/calendar.' . $phpEx);
}

/**
 * implements a week view
 *
 */
class rpweek extends calendar
{
	private $mode = '';
	
	/**
	 * 
	 */
	function __construct()
	{
		$this->mode = "week";
		parent::__construct($this->mode);
	}
	
	/**
	 * @see calendar::display()
	 *
	 */
	public function display()
	{
		global $auth, $user, $config, $template, $phpEx, $phpbb_root_path;
	
		$etype_url_opts = $this->get_etype_url_opts();
		$this->_init_view_selection_code("week");
		
		// create next and prev links
		$index_display = request_var('indexWk', 0);
		$this->_set_date_prev_next( "week" );
		$prev_link = "";
		$next_link = "";
	
		//find the first day of the week
		if( $index_display == 0)
		{
			$first_day_of_week = $config['rp_first_day_of_week'];
			$prev_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['prev_day']."&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year'].$etype_url_opts);
			$next_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['next_day']."&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year'].$etype_url_opts);
		}
		else
		{
			// display on index !!
			/* get current weekday so we show this upcoming week's raidplans */
			$temp_date = time() + $user->timezone + $user->dst;
			$first_day_of_week = gmdate("w", $temp_date);
	
			$prev_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['prev_day']."&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year']."&amp;indexWk=1".$etype_url_opts);
			$next_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['next_day']."&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year']."&amp;indexWk=1".$etype_url_opts);
		}
		$this->get_weekday_names( $first_day_of_week, $sunday, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday );
	
		$this->date['fday'] = $this->get_fday($this->date['day'], $this->date['month_no'], $this->date['year'], $first_day_of_week);
	
		$number_days = 7;
		$calendar_header_txt = $user->lang['WEEK_OF'] . sprintf($user->lang['LOCAL_DATE_FORMAT'], $user->lang['datetime'][$this->date['month']], $this->date['day'], $this->date['year'] );
	
		$counter = 0;
		$j_start = $this->date['day'];
		if( $this->date['fday'] < $number_days )
		{
			$j_start = $this->date['day']-$this->date['fday'];
		}
		$prev_month_no = $this->date['month_no'] - 1;
		$prev_year_no = $this->date['year'];
		if( $prev_month_no == 0 )
		{
			$prev_month_no = 12;
			$prev_year_no--;
		}
		$prev_month_day_count = date("t",mktime( 0,0,0,$prev_month_no, 25, $prev_year_no));
		
		// how many days are in this month?
		$month_day_count = date("t",mktime(0,0,0,$this->date['month_no'], 25, $this->date['year']));
		$next_month_no = $this->date['month_no'] + 1;
		$next_year_no = $this->date['year'];
		if( $next_month_no == 13 )
		{
			$next_month_no = 1;
			$next_year_no++;
		}
		
		// get raid info
		if (!class_exists('rpraid'))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpraid.' . $phpEx);
		}
		$rpraid = new rpraid();
		
		// array of raid days
		$raiddays = $rpraid->GetRaiddaylist($this->Get1DoM($this->timestamp), $this->GetLDoM($this->timestamp) );
		// array of bdays
		$birthdays = $this->generate_birthday_list( $this->Get1DoM($this->timestamp), $this->GetLDoM($this->timestamp));
		
		
		for ($j = $j_start; $j < $j_start+7; $j++, $counter++)
		{
			if( $j < 1 )
			{
				$true_j = $prev_month_day_count + $j;
				$true_m = $prev_month_no;
				$true_y = $prev_year_no;
			}
			else if ($j > $month_day_count )
			{
				$true_j = $j - $month_day_count;
				$true_m = $next_month_no;
				$true_y = $next_year_no;
			}
			else
			{
				$true_j = $j;
				$true_m = $this->date['month_no'];
				$true_y = $this->date['year'];
			}
	
			// start creating the data for the real days
			$calendar_days['START_WEEK'] = false;
			$calendar_days['END_WEEK'] = false;
			$calendar_days['DUMMY_DAY'] = false;
			$calendar_days['HEADER_CLASS'] = '';
			$calendar_days['DAY_CLASS'] = '';
			$calendar_days['NUMBER'] = 0;
			$calendar_days['ADD_LINK'] = '';
			$calendar_days['BIRTHDAYS'] = '';
	
			if($counter % 7 == 0)
			{
				$calendar_days['START_WEEK'] = true;
			}
			if($counter % 7 == 6 )
			{
				$calendar_days['END_WEEK'] = true;
			}
			$calendar_days['NUMBER'] = $true_j;
			
			if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans') )
			{
				$calendar_days['ADD_LINK'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=showadd&amp;calD=".$true_j."&amp;calM=".$true_m."&amp;calY=".$true_y.$etype_url_opts);
			}
			$calendar_days['DAY_VIEW_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$true_j."&amp;calM=".$true_m."&amp;calY=".$true_y.$etype_url_opts);
			$calendar_days['MONTH_VIEW_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$true_j."&amp;calM=".$true_m."&amp;calY=".$true_y.$etype_url_opts);
	
			if( ($true_j == $this->date['day']) &&
			    ($true_m == $this->date['month_no']) &&
			    ($true_y == $this->date['year']) )
			{
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
	
			//highlight current day
			$test_start_hi_time = mktime( 0,0,0,$true_m, $true_j, $true_y) + date('Z');
			$test_end_hi_time = $test_start_hi_time + 86399;
			$test_hi_time = time() + $user->timezone + $user->dst;
	
			if( ($test_start_hi_time <= $test_hi_time) &&
			    ($test_end_hi_time >= $test_hi_time))
			{
				$calendar_days['HEADER_CLASS'] = 'highlight';
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
			
			// user cannot add raid/appointments in the past
			$calendar_days['ADD_RAID_ICON'] = false;
			if( $j >= date('d') || $this->date['month_no'] > date('m') )
			{
				$calendar_days['ADD_RAID_ICON'] = true;
			}
			
			$calendar_days['BIRTHDAYS']="";
			if ( $auth->acl_get('u_raidplanner_view_raidplans') && $auth->acl_get('u_viewprofile') )
			{
				// find birthdays
				if(is_array($birthdays))
				{
					//loop the bdays
					foreach ($birthdays as $birthday)
					{
						if($birthday['day'] == $j)
						{
							$calendar_days['BIRTHDAYS'] = $birthday['bdays'];
						}
					}
				}
				
			}
			
	
			$template->assign_block_vars('calendar_days', $calendar_days);

			// if can see raids
			if ( $auth->acl_get('u_raidplanner_view_raidplans') )
			{
				$hit= false;
				if(isset($raiddays) && is_array($raiddays))
				{
					// loop all days having raids			
					foreach ($raiddays as $raidday)
					{
						if($raidday['day'] == $j)
						{
							$raidplan_output = $rpraid->GetRaidinfo($true_m, $true_j, $true_y, $this->group_options, $this->mode);
							foreach($raidplan_output as $raid )
							{
								$template->assign_block_vars('calendar_days.raidplans', $raid);
							}
							$hit= true;
						}
					}
					
					// remove hit
					if ($hit) 
					{
						$raiddays = array_shift($raiddays);
					}
				}
				
			}
			
			
	
		}
	
		$template->assign_vars(array(
				'CALENDAR_HEADER'	=> $calendar_header_txt,
				'DAY_IMG'			=> $user->img('button_calendar_day', 'DAY'),
				'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
				'CALENDAR_PREV'		=> $prev_link,
				'CALENDAR_NEXT'		=> $next_link,
				'CALENDAR_VIEW_OPTIONS' => $this->mode_sel_code.' '.$this->month_sel_code.' '.$this->day_sel_code.' '.$this->year_sel_code,
				'S_PLANNER_WEEK'	=> true,
				'SUNDAY'			=> $sunday,
				'MONDAY'			=> $monday,
				'TUESDAY'			=> $tuesday,
				'WEDNESDAY'			=> $wednesday,
				'THURSDAY'			=> $thursday,
				'FRIDAY'			=> $friday,
				'SATURDAY'			=> $saturday,
				'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;". $this->get_etype_post_opts() ),
		));
	}
}

?>
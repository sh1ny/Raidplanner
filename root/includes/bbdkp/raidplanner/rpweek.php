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
	
		// create next and prev links
		$first_day_of_week = $config['rp_first_day_of_week'];
		
		// get date number 
		$this->date['fday'] = $this->get_fday($this->date['day'], $this->date['month_no'], $this->date['year'], $first_day_of_week);
	
		$number_days = 7;
		$calendar_header_txt = $user->lang['WEEK_OF'] . sprintf($user->lang['LOCAL_DATE_FORMAT'], 
			$user->lang['datetime'][$this->date['month']], $this->date['day'], $this->date['year'] );
	
		$counter = 0;
		$j_start = $this->date['day']-$this->date['fday'];
		
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
		
				
		if ($j_start < 0)
		{
			$fdaystamp = gmmktime(0,0,0, $this->date['month_no']-1, $j_start + $prev_month_day_count, $prev_year_no);
		}
		else 
		{
			$fdaystamp = gmmktime(0,0,0, $this->date['month_no'], $j_start, $this->date['year']);
		}
		
		if ($j_start + $number_days > $month_day_count)
		{
			$ldaystamp = gmmktime(0,0,0, $next_month_no, $j_start + $number_days - $month_day_count, $next_year_no);
		}
		else 
		{
			$ldaystamp = gmmktime(0,0,0, $this->date['month_no'], $j_start + $number_days, $this->date['year']);
		}
		
		// array of raid days
		if (!class_exists('rpraid'))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpraid.' . $phpEx);
		}
		$rpraid = new rpraid();
		$raiddays = $rpraid->GetRaiddaylist($fdaystamp, $ldaystamp);
		// array of bdays
		$birthdays = $this->generate_birthday_list( $fdaystamp, $ldaystamp);
		
		
		for ($j = $j_start; $j < $j_start+7; $j++, $counter++)
		{
			if( $j < 1 )
			{
				//past month
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
				// 1 <= $j <= $month_day_count 
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
				$calendar_days['ADD_LINK'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=showadd&amp;calD=".$true_j."&amp;calM=".$true_m."&amp;calY=".$true_y);
			}
			$calendar_days['DAY_VIEW_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$true_j."&amp;calM=".$true_m."&amp;calY=".$true_y);
			$calendar_days['MONTH_VIEW_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$true_j."&amp;calM=".$true_m."&amp;calY=".$true_y);
	
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
			if( $true_m >= date('m') || ($true_m == date('m')  && $true_j >= date('d') ) )
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
						if($raidday['day'] == $true_j)
						{
							//raid(s) found get detail
							$raidplan_output = $rpraid->GetRaidinfo($true_m, $true_j, $true_y, $this->group_options, $this->mode);
							foreach($raidplan_output as $raid )
							{
								$template->assign_block_vars('calendar_days.raidplans', $raid['raidinfo']);
								foreach($raid['userchars'] as $key => $char)
								{
									$template->assign_block_vars('calendar_days.raidplans.userchars', $char);
								}
								unset($char);
								unset($key);
								foreach($raid['raidroles'] as $key => $raidrole)
								{
									$template->assign_block_vars('calendar_days.raidplans.raidroles', $raidrole);
								}
								unset($raidrole);
								unset($key);
							
								
							}
							$hit= true;
						}
					}
					
					// remove hit
					if ($hit) 
					{
						$shifted = array_shift($raiddays);
					}
				}
				
			}
			
			
	
		}
	
		$template->assign_vars(array(
				'CALENDAR_HEADER'	=> $calendar_header_txt,
				'DAY_IMG'			=> $user->img('button_calendar_day', 'DAY'),
				'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
				'S_PLANNER_WEEK'	=> true,
				'D0'				=> $this->daynames[0],
				'D1'				=> $this->daynames[1],
				'D2'				=> $this->daynames[2],
				'D3'				=> $this->daynames[3],
				'D4'				=> $this->daynames[4],
				'D5'				=> $this->daynames[5],
				'D6'				=> $this->daynames[6],
				'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week" ),
		));
	}
}

?>
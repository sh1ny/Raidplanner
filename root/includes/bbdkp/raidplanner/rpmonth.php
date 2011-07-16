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
 * implements a month view
 *
 */
class rpmonth extends calendar
{
	private $mode = '';
	
	/**
	 * 
	 */
	function __construct()
	{
		$this->mode="month";
		parent::__construct($this->mode);
	}
	
	/**
	 * @see calendar::display()
	 *
	 */
	public function display()
	{
		global $db, $auth, $user, $config, $template, $phpEx, $phpbb_root_path;
		
		$etype_url_opts = $this->get_etype_url_opts();
		$this->_init_view_selection_code("month");
		
		//create next and prev links
		$this->_set_date_prev_next( "month" );
		$prev_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year'].$etype_url_opts);
		$next_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year'].$etype_url_opts);
	
		//find the first day of the week
		$first_day_of_week = $config['rp_first_day_of_week'];
		$this->get_weekday_names( $first_day_of_week, $sunday, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday );
	
		//get the first day of the month
		$this->date['num'] = "01";
		$this->date['fday'] = $this->get_fday( $this->date['num'], $this->date['month_no'], $this->date['year'], $first_day_of_week );
	
		$number_days = gmdate("t", gmmktime( 0,0,0,$this->date['month_no'], $this->date['day'], $this->date['year']));
	
		$calendar_header_txt = $user->lang['MONTH_OF'] . sprintf($user->lang['LOCAL_DATE_FORMAT'], $user->lang['datetime'][$this->date['month']], $this->date['day'], $this->date['year'] );
		
		$counter = 0;
		// get raid info
		if (!class_exists('raidplans'))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplans.' . $phpEx);
		}
		$raidplans = new raidplans();
		
		
		for ($j = 1; $j < $number_days+1; $j++, $counter++)
		{
			// if it is the first week
			if ($j == 1)
			{
				// find how many place holders we need before day 1
				if ($this->date['fday'] < 7)
				{
					$this->date['fday'] = $this->date['fday']+1;
					for ($i = 1; $i < $this->date['fday']; $i++, $counter++)
					{
						// create dummy days (place holders)
						if( $i == 1 )
						{
							$calendar_days['START_WEEK'] = true;
						}
						else
						{
							$calendar_days['START_WEEK'] = false;
						}
						$calendar_days['END_WEEK'] = false;
						$calendar_days['HEADER_CLASS'] = '';
						$calendar_days['DAY_CLASS'] = '';
						$calendar_days['NUMBER'] = 0;
						$calendar_days['DUMMY_DAY'] = true;
						$calendar_days['ADD_LINK'] = '';
						$calendar_days['BIRTHDAYS'] = '';
						$template->assign_block_vars('calendar_days', $calendar_days);
					}
				}
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
			$calendar_days['NUMBER'] = $j;
			
			if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans') )
			{
				$calendar_days['ADD_LINK'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;calD=".$j."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']. $etype_url_opts);
			}
			$calendar_days['DAY_VIEW_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$j."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			$calendar_days['WEEK_VIEW_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$j."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
	
			//highlight selected day
			if( $j == $this->date['day'] )
			{
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
	
			//highlight current day
			$test_start_hi_time = mktime( 0,0,0,$this->date['month_no'], $j, $this->date['year']) + date('Z');
			$test_end_hi_time = $test_start_hi_time + 86399;
			$test_hi_time = time() + $user->timezone + $user->dst;
	
			if( ($test_start_hi_time <= $test_hi_time) &&
			    ($test_end_hi_time >= $test_hi_time))
			{
				$calendar_days['HEADER_CLASS'] = 'highlight';
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
	
			if ( $auth->acl_get('u_raidplanner_view_raidplans') && $auth->acl_get('u_viewprofile') )
			{
				// find birthdays
				$calendar_days['BIRTHDAYS'] = $this->generate_birthday_list( $j, $this->date['month_no'], $this->date['year'] );
			}
	
			$template->assign_block_vars('calendar_days', $calendar_days);
			
			if ( $auth->acl_get('u_raidplanner_view_raidplans') )
			{
				$raidplan_output = $raidplans->GetRaidinfo($this->date['month_no'], $j, $this->date['year'], $this->group_options, "month");
				
				foreach($raidplan_output as $raid )
				{
					$template->assign_block_vars('calendar_days.raidplans', $raid);
				}
			}
	
		}
		$counter--;
		$dummy_end_day_count = 6 - ($counter % 7);
		for ($i = 1; $i <= $dummy_end_day_count; $i++)
		{
			// create dummy days (place holders)
			$calendar_days['START_WEEK'] = false;
			if( $i == $dummy_end_day_count )
			{
				$calendar_days['END_WEEK'] = true;
			}
			else
			{
				$calendar_days['END_WEEK'] = false;
			}
			$calendar_days['HEADER_CLASS'] = '';
			$calendar_days['DAY_CLASS'] = '';
			$calendar_days['NUMBER'] = 0;
			$calendar_days['DUMMY_DAY'] = true;
			$calendar_days['ADD_LINK'] = '';
			$calendar_days['BIRTHDAYS'] = '';
			$template->assign_block_vars('calendar_days', $calendar_days);
		}
	
		$template->assign_vars(array(
			'CALENDAR_HEADER'	=> $calendar_header_txt,
			'DAY_IMG'			=> $user->img('button_calendar_day', 'DAY'),
			'WEEK_IMG'			=> $user->img('button_calendar_week', 'WEEK'),
			'CALENDAR_PREV'		=> $prev_link,
			'CALENDAR_NEXT'		=> $next_link,
			'CALENDAR_VIEW_OPTIONS' => $this->mode_sel_code.' '.$this->month_sel_code.' '.$this->day_sel_code.' '.$this->year_sel_code,
			'S_PLANNER_MONTH'	=> true,
			'SUNDAY'			=> $sunday,
			'MONDAY'			=> $monday,
			'TUESDAY'			=> $tuesday,
			'WEDNESDAY'			=> $wednesday,
			'THURSDAY'			=> $thursday,
			'FRIDAY'			=> $friday,
			'SATURDAY'			=> $saturday,
			'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;". $this->get_etype_post_opts() ),
		));
	
	}
}

?>
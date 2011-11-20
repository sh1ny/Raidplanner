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
		global $auth, $user, $config, $template, $phpEx, $phpbb_root_path;
	
		$this->date['num'] = "01";
		$this->date['fday'] = $this->get_fday( $this->date['num'], $this->date['month_no'], $this->date['year']);
	
		$number_days = date("t", mktime( 0,0,0,$this->date['month_no'], $this->date['day'], $this->date['year']));
	
		$calendar_header_txt = $user->lang['MONTH_OF'] . sprintf($user->lang['LOCAL_DATE_FORMAT'], $user->lang['datetime'][$this->date['month']], $this->date['day'], $this->date['year'] );
		
		$counter = 0;
		// include raid class
		if (!class_exists('rpraid'))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpraid.' . $phpEx);
		}
		$rpraid = new rpraid();
		
		// array of raid days
		$firstday = $this->Get1DoM($this->timestamp);
		$lastday =  $this->GetLDoM($this->timestamp);
		
		$raiddays = $rpraid->GetRaiddaylist( $firstday, $lastday );
		$birthdays = $this->generate_birthday_list( $firstday,$lastday);
		
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
				$calendar_days['ADD_LINK'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=showadd&amp;calD=".$j."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
			}
			
			$calendar_days['DAY_VIEW_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$j."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
			$calendar_days['WEEK_VIEW_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$j."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
	
			//highlight selected day
			if( $j == $this->date['day'] )
			{
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
			
			//highlight today
			$start_hi_time = mktime( 0,0,0,$this->date['month_no'], $j, $this->date['year']) + date('Z');
			$end_hi_time = $start_hi_time + 86399;
			$hi_time = time() + $user->timezone + $user->dst;
	
			if( ($start_hi_time <= $hi_time) && ($end_hi_time >= $hi_time))
			{
				$calendar_days['HEADER_CLASS'] = 'highlight';
				$calendar_days['DAY_CLASS'] = 'highlight';
			}
			
			// user cannot add raid/appointments in the past
			$calendar_days['ADD_RAID_ICON'] = false;
			if( (int) $this->date['month_no'] > (int) date('m') || 
				( (int) $this->date['month_no']  == (int) date('m') && $j >= (int) date('d') )  || 
				(int) $this->date['year'] > (int) date('Y') )
			{
				$calendar_days['ADD_RAID_ICON'] = true;
			}
			
			// add birthdays
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
			
			if ( $auth->acl_get('u_raidplanner_view_raidplans') )
			{
				$hit= false;
				if(isset($raiddays) && is_array($raiddays))
				{
					foreach ($raiddays as $raidday)
					{
						if($raidday['day'] == $j)
						{
							if (isset($rpraid))
							{
								$raidplan_output = $rpraid->GetRaidinfo($this->date['month_no'], $j, $this->date['year'], $this->group_options, "month");
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
					}
					
					// remove hit
					if ($hit) 
					{
						$shifted = array_shift($raiddays);
					}
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
			'S_PLANNER_MONTH'	=> true,
			'S_DISPLAY_NAME'	=> ($config['rp_show_name'] ==1 ? true : false) ,  
			'D0'				=> $this->daynames[0],
			'D1'				=> $this->daynames[1],
			'D2'				=> $this->daynames[2],
			'D3'				=> $this->daynames[3],
			'D4'				=> $this->daynames[4],
			'D5'				=> $this->daynames[5],
			'D6'				=> $this->daynames[6],
			'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month"),
		));
	
	}
}

?>
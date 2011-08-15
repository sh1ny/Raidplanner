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
 * implements raidplan day view
 *
 */
class rpday extends calendar
{
	private $mode = '';
	

	function __construct()
	{
		$this->mode="day";
		parent::__construct($this->mode);
	}
	
	/**
	 * @see calendar::display()
	 *
	 */
	public function display()
	{
		global $db, $auth, $user, $config, $template, $phpEx, $phpbb_root_path;
		
		$calendar_header_txt = $user->lang['DAY_OF'] . sprintf($user->lang['LOCAL_DATE_FORMAT'], $user->lang['datetime'][$this->date['month']], $this->date['day'], $this->date['year'] );

		$hour_mode = $config['rp_hour_mode'];
		if( $hour_mode == 12 )
		{
			for( $i = 0; $i < 24; $i++ )
			{
				$time_header['TIME'] = $i % 12;
				if( $time_header['TIME'] == 0 )
				{
					$time_header['TIME'] = 12;
				}
				$time_header['AM_PM'] = $user->lang['PM'];
				if( $i < 12 )
				{
					$time_header['AM_PM'] = $user->lang['AM'];
				}
				$template->assign_block_vars('time_headers', $time_header);
			}
		}
		else
		{
			for( $i = 0; $i < 24; $i++ )
			{
				$o = "";
				if($i < 10 )
				{
					$o="0";
				}
				$time_header['TIME'] = $o . $i;
				$time_header['AM_PM'] = "";
				$template->assign_block_vars('time_headers', $time_header);
			}
		}
		
		$week_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
		$month_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
		$add_raidplan_url = "";
	
		if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans') )
		{
			$add_raidplan_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=showadd&amp;mode=newraid&amp;calD=".
				$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
		}
		$calendar_days['BIRTHDAYS'] = "";
		$birthdays = $this->generate_birthday_list( $this->Get1DoM($this->timestamp), $this->GetLDoM($this->timestamp));
		if ( $auth->acl_get('u_raidplanner_view_raidplans') && $auth->acl_get('u_viewprofile') )
		{
			if(isset($birthdays[$this->date['day']]))
			{
				$calendar_days['BIRTHDAYS'] = $birthdays[$this->date['day']]['bdays'];
			}
		}
		
		// get raid info
		if (!class_exists('rpraid'))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpraid.' . $phpEx);
		}
		$rpraid = new rpraid();
		
		$raidplan_output = array();
		// Is the user able to view ANY raidplans?
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			$raidplan_output = $rpraid->GetRaidinfo($this->date['month_no'], $this->date['day'], $this->date['year'], $this->group_options, "day");
			foreach($raidplan_output as $raid )
			{
				$template->assign_block_vars('raidplans', $raid);
			}
		}
		
		$template->assign_vars(array(
			'BIRTHDAYS'			=> $calendar_days['BIRTHDAYS'],
			'CALENDAR_HEADER'	=> $calendar_header_txt,
			'WEEK_IMG'			=> $user->img('button_calendar_week', 'WEEK'),
			'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
			'ADD_LINK'			=> $add_raidplan_url,
			'WEEK_VIEW_URL'		=> $week_view_url,
			'MONTH_VIEW_URL'	=> $month_view_url,
			'S_PLANNER_DAY'		=> true,		
			'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;" ),
			'EVENT_COUNT'		=> sizeof($raidplan_output),
		));
		
		
	}
}

?>
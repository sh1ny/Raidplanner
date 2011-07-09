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
 * implements upcoming
 *
 */
class rpup extends calendar
{
	
	/**
	 * 
	 */
	function __construct()
	{
		parent::__construct("day");
	}
	
	/**
	 * 
	 * @see calendar::display()
	 */
	public function display()
	{
		global $auth, $user, $config, $template, $phpEx, $phpbb_root_path;
		
		$this->_init_view_selection_code("day");
		$etype_url_opts = $this->get_etype_url_opts();
	
		// create next and prev links
		$this->set_date_prev_next( "day" );
		$prev_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$this->date['prev_day']."&amp;calM=".$this->date['prev_month']."&amp;calY=".$this->date['prev_year'].$etype_url_opts);
		$next_link = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$this->date['next_day']."&amp;calM=".$this->date['next_month']."&amp;calY=".$this->date['next_year'].$etype_url_opts);
	
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
	
		$raidplan_counter = 0;
		// Is the user able to view ANY raidplans?
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			// get raid info
			if (!class_exists('raidplans'))
			{
				include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplans.' . $phpEx);
			}
			$raidplans = new raidplans();
			$raidplan_counter = $raidplans->showraidinfo($this->date['month_no'], $this->date['day'], $this->date['year'], 'day');
		}
	
		$week_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		$month_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		$add_raidplan_url = "";
	
		if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans') )
		{
			$add_raidplan_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=newraid&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		}
	
		$template->assign_vars(array(
			'CALENDAR_HEADER'	=> $calendar_header_txt,
			'WEEK_IMG'			=> $user->img('button_calendar_week', 'WEEK'),
			'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
			'ADD_LINK'			=> $add_raidplan_url,
			'WEEK_VIEW_URL'		=> $week_view_url,
			'MONTH_VIEW_URL'	=> $month_view_url,
			'CALENDAR_PREV'		=> $prev_link,
			'CALENDAR_NEXT'		=> $next_link,
			'S_PLANNER_DAY'		=> true,		
			'CALENDAR_VIEW_OPTIONS' => $this->mode_sel_code.' '.$this->month_sel_code.' '.$this->day_sel_code.' '.$this->year_sel_code,
			'S_POST_ACTION'		=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;". $this->get_etype_post_opts() ),
			'EVENT_COUNT'		=> $raidplan_counter,
		));
	}
}

?>
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
 * implements upcoming raids view
 *
 */
class rpup extends calendar
{
	private $mode = '';
	/*
	/**
	 * 
	 */
	function __construct()
	{
		$this->mode = "up";
		parent::__construct($this->mode);
	}
	
	/**
	 * @see calendar::display($x)
	 *
	 * @param int $x
	 */
	public function display()
	{
		$this->_display_next_raidplans();
	}
	
	/**
	 * displays the next x number of upcoming raidplans 
	 *
	 * @param string $mode (up or next)
	 */
	private function _display_next_raidplans()
	{
		global $user, $db, $auth, $template, $phpEx, $phpbb_root_path;
	
		// if can see raids
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			
			// build sql 
			$sql_array = array(
	   			'SELECT'    => 'r.raidplan_id ',   
				'FROM'		=> array(RP_RAIDS_TABLE => 'r'), 
				'WHERE'		=>  '(raidplan_access_level = 2 
						   OR (r.poster_id = '. $db->sql_escape($user->data['user_id']).' ) OR (r.raidplan_access_level = 1 AND ('. $group_options.')) )  
						  AND (r.raidplan_start_time >= '. $db->sql_escape($start_temp_date).' AND r.raidplan_start_time <= '. $db->sql_escape($end_temp_date). " )",
				'ORDER_BY'	=> 'r.raidplan_start_time ASC'
			);
			
			$sql = $db->sql_build_query('SELECT', $sql_array);
			$result = $db->sql_query_limit($sql, $x, 0);
	
			while ($row = $db->sql_fetchrow($result))
			{
				unset($this);
				$this->id= $row['raidplan_id'];
				$this->make_obj();
				
				$fsubj = $subj = censor_text($this->subject);
				if( $config['rp_display_truncated_name'] > 0 )
				{
					if(utf8_strlen($subj) > $config['rp_display_truncated_name'])
					{
						$subj = truncate_string($subj, $config['rp_display_truncated_name']) . '...';
					}
				}
				
				$correct_format = $config['rp_time_format'];
				if( $this->end_time - $this->start_time > 86400 )
				{
					$correct_format = $config['rp_date_time_format'];
				}
				
				$raidinfo = array(
					'RAID_ID'				=> $this->id,
					'ETYPE_DISPLAY_NAME' 	=> $this->eventlist->events[$this->event_type]['event_name'], 
					'COLOR' 				=> $this->eventlist->events[$this->event_type]['color'],
					'IMAGE' 				=> $phpbb_root_path . "images/event_images/" . $this->eventlist->events[$this->event_type]['imagename'] . ".png", 
					'S_EVENT_IMAGE_EXISTS'  => (strlen( $this->eventlist->events[$this->event_type]['imagename'] ) > 1) ? true : false,
					'EVENT_URL'  			=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$this->id), 
					'EVENT_ID'  			=> $this->id,
					 // for popup
					'S_SIGNUP_MODE_ACTION' 	=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$this->id. "&amp;mode=signup"), 
					'INVITE_TIME'  			=> $user->format_date($this->invite_time, $correct_format, true), 
					'START_TIME'			=> $user->format_date($this->start_time, $correct_format, true),
					'END_TIME' 				=> $user->format_date($this->end_time, $correct_format, true),
					
					'DISPLAY_BOLD'			=> ($user->data['user_id'] == $this->poster) ? true : false,
					'ALL_DAY'				=> ($this->all_day == 1  ) ? true : false,
					'SHOW_TIME'				=> ($mode == "day" || $mode == "week" ) ? true : false, 
					'COUNTER'				=> $raidplan_counter++, 
					'S_CANSIGNUP'			=> $this->signups_allowed, 
					'S_LEGITUSER'			=> ($user->data['is_bot'] || $user->data['user_id'] == ANONYMOUS) ? false : true, 
				
					'RAID_TOTAL'			=> $total_needed,
				
					'CURR_CONFIRMED_COUNT'	 => $this->signups['confirmed'],
					'S_CURR_CONFIRMED_COUNT' => ($this->signups['confirmed'] > 0) ? true: false,
					'CURR_CONFIRMEDPCT'		=> sprintf( "%.0f%%", ($total_needed > 0 ? round(($this->signups['confirmed']) /  $total_needed, 2) *100 : 0)),
					
					'CURR_YES_COUNT'		=> $this->signups['yes'],
					'S_CURR_YES_COUNT'		=> ($this->signups['yes'] + $this->signups['maybe'] > 0) ? true: false,
					'CURR_YESPCT'			=> sprintf( "%.0f%%", ($total_needed > 0 ? round(($this->signups['yes']) /  $total_needed, 2) *100 : 0)),
				
					'CURR_MAYBE_COUNT'		=> $this->signups['maybe'],
					'S_CURR_MAYBE_COUNT' 	=> ($this->signups['maybe'] > 0) ? true: false,
					'CURR_MAYBEPCT'			=> sprintf( "%.0f%%", ($total_needed > 0 ? round(($this->signups['maybe']) /  $total_needed, 2) *100 : 0)), 
					
					'CURR_NO_COUNT'			=> $this->signups['no'],
					'S_CURR_NO_COUNT'		=> ($this->signups['no'] > 0) ? true: false,
					'CURR_NOPCT'			=> sprintf( "%.0f%%", ($total_needed > 0 ? round(($this->signups['no']) /  $total_needed, 2) *100 : 0)),
				
					'CURR_TOTAL_COUNT'  	=> $this->signups['yes'] + $this->signups['maybe'],
				);
			}
			$db->sql_freeresult($result);
				
			}
			
			
			$template->assign_vars(array(
				'S_PLANNER_UPCOMING'		=> true,
				'EVENT_COUNT'				=> sizeof($raidplan_output),
			));
			
		}
			
}
	
	
	


?>
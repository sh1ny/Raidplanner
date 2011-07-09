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
 * implements a dayview
 *
 */
class rpday extends calendar
{
	
	/**
	 * 
	 */
	function __construct()
	{
		parent::__construct("up");
	}
	
	/**
	 * 
	 * @see calendar::display()
	 */
	public function display()
	{
		
	}
	
	
	/* displays the next x number of upcoming raidplans */
	private function _display_next_raidplans( $x )
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
	
		$etype_url_opts = $this->get_etype_url_opts(); 
		$raidplan_data = array();
		// Is the user able to view ANY raidplans?
		$user_can_view_raidplans = false;
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			$subject_limit = $config['rp_display_truncated_name']; 
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
	
			$start_temp_date = time();
			$end_temp_date = $start_temp_date + 31536000;
			// find all day raidplans that are still taking place
			$sort_timestamp_cutoff = $start_temp_date - 86400+1;
	
		    $disp_date_format = $config['rp_date_format'];
		    $disp_date_time_format = $config['rp_date_time_format']; 
	
			// don't list raidplans more than 2 months in the future
			$sql_array = array(
	   			'SELECT'    => 'r.*', 
				'FROM'		=> array(RP_RAIDS_TABLE => 'r'), 
				'WHERE'		=>  ' ( r.raidplan_access_level = 2 
								  OR r.poster_id = '. $db->sql_escape($user->data['user_id']) . ' 
								  OR (r.raidplan_access_level = 1 AND (' . $group_options . ' )) )
								  AND r.raidplan_start_time >= ' . $db->sql_escape($start_temp_date). ' 
								  AND r.raidplan_start_time <= ' . $db->sql_escape($end_temp_date) ,
				'ORDER_BY'	=> 'r.raidplan_start_time ASC');
			$sql = $db->sql_build_query('SELECT', $sql_array);
			$result = $db->sql_query($sql);
		
			$result = $db->sql_query_limit($sql, $x, 0);
			while ($row = $db->sql_fetchrow($result))
			{
				$raidplan_data['EVENT_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts);
				$raidplan_data['COLOR'] = $this->raid_plan_colors[$row['etype_id']];
				$raidplan_data['ETYPE_DISPLAY_NAME'] = $this->raid_plan_displaynames[$row['etype_id']];
				$raidplan_data['IMAGE'] = $phpbb_root_path . "images/event_images/" . $this->raid_plan_images[$row['etype_id']] . ".png";
				$raidplan_data['S_EVENT_IMAGE_EXISTS'] = (strlen( $this->raid_plan_images[$row['etype_id']] ) > 1) ? true : false;
				$raidplan_data['FULL_SUBJECT'] = censor_text($row['raidplan_subject']);
				$raidplan_data['SUBJECT'] = $raidplan_data['FULL_SUBJECT'];
				if( $subject_limit > 0 )
				{
					if(utf8_strlen($raidplan_data['SUBJECT']) > $subject_limit)
					{
						$raidplans['SUBJECT'] = truncate_string($raidplan_data['SUBJECT'], $subject_limit) . '...';
					}
				}
	
				$poster_url = '';
				$invite_list = '';
				
				if (!class_exists('raidplans'))
				{
					include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplans.' . $phpEx);
				}
		
				$raidplans = new raidplans();
				$raidplans->get_raidplan_invites($row, $poster_url, $invite_list );

				$raidplan_data['POSTER'] = $poster_url;
				$raidplan_data['INVITED'] = $invite_list;
				$raidplan_data['ALL_DAY'] = 0;
				if( $row['raidplan_all_day'] == 1 )
				{
					list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $row['raidplan_day']);
					$row['raidplan_start_time'] = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
					$row['raidplan_end_time'] = $row['raidplan_start_time']+86399;
					$raidplan_data['ALL_DAY'] = 1;
					$raidplan_data['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_format, true);
					$raidplan_data['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_format, true);
				}
				else
				{
					$raidplan_data['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_time_format, true);
					$raidplan_data['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_time_format, true);
				}
	
				$template->assign_block_vars('raidplans', $raidplan_data);
			}
			$db->sql_freeresult($result);
		}
	}
	
	/* displays the upcoming raidplans for the next x number of days */
	public function display_next_raidplans_for_x_days( $x )
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
		$etype_url_opts = $this->get_etype_url_opts();
		$raidplan_data = array();
		// Is the user able to view ANY raidplans?
		$user_can_view_raidplans = false;
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
	
			$subject_limit = $config['rp_display_truncated_name'];
			$group_options = $this->get_sql_group_options($user->data['user_id']);
			$etype_options = $this->get_etype_filter();
	
			$start_temp_date = time();
			//$end_temp_date = $start_temp_date + 31536000;
			$end_temp_date = $start_temp_date + ( $x * 86400 );
			// find all day raidplans that are still taking place
			$sort_timestamp_cutoff = $start_temp_date - 86400+1;
	
		    $disp_date_format = $config['rp_date_format'];
		    $disp_date_time_format = $config['rp_date_time_format'];
	
			// don't list raidplans that are more than 1 year in the future
			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
					WHERE ( (raidplan_access_level = 2) OR
						(poster_id = '.$db->sql_escape($user->data['user_id']).' ) 
						OR (raidplan_access_level = 1 AND ('.$group_options.') ) ) '.$etype_options.' 
					AND ((( raidplan_start_time >= '.$db->sql_escape($start_temp_date).' 
					AND raidplan_start_time <= '.$db->sql_escape($end_temp_date).' ) OR
					 	(raidplan_end_time > '.$db->sql_escape($start_temp_date).' 
					AND raidplan_end_time <= '.$db->sql_escape($end_temp_date).' ) OR
					 	(raidplan_start_time < '.$db->sql_escape($start_temp_date).' 
					AND raidplan_end_time > '.$db->sql_escape($end_temp_date)." )) OR (sort_timestamp > ".$db->sql_escape($sort_timestamp_cutoff)." 
					AND sort_timestamp <= ".$db->sql_escape($end_temp_date)." 
					AND raidplan_all_day = 1) ) ORDER BY sort_timestamp ASC";
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$raidplans['EVENT_URL'] = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts);
				$raidplans['IMAGE'] = $this->raid_plan_images[$row['etype_id']];
				$raidplans['COLOR'] = $this->raid_plan_colors[$row['etype_id']];
				$raidplans['ETYPE_DISPLAY_NAME'] = $this->raid_plan_displaynames[$row['etype_id']];
	
				$raidplans['FULL_SUBJECT'] = censor_text($row['raidplan_subject']);
				$raidplans['SUBJECT'] = $raidplans['FULL_SUBJECT'];
				if( $subject_limit > 0 )
				{
					if(utf8_strlen($raidplans['SUBJECT']) > $subject_limit)
					{
						$raidplans['SUBJECT'] = truncate_string($raidplans['SUBJECT'], $subject_limit) . '...';
					}
				}
	
				$poster_url = '';
				$invite_list = '';
				
				if (!class_exists('raidplans'))
				{
					include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplans.' . $phpEx);
				}
		
				$raidplans = new raidplans();
				$raidplans->get_raidplan_invites($row, $poster_url, $invite_list );
				
				$rraidplans = new raidplans; 
				$rraidplans->get_raidplan_invites($row, $poster_url, $invite_list );
				$raidplans['POSTER'] = $poster_url;
				$raidplans['INVITED'] = $invite_list;
				$raidplans['ALL_DAY'] = 0;
				if( $row['raidplan_all_day'] == 1 )
				{
					list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $row['raidplan_day']);
					$row['raidplan_start_time'] = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
					$row['raidplan_end_time'] = $row['raidplan_start_time']+86399;
					$raidplans['ALL_DAY'] = 1;
					$raidplans['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_format, true);
					$raidplans['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_format, true);
				}
				else
				{
					$raidplans['START_TIME'] = $user->format_date($row['raidplan_start_time'], $disp_date_time_format, true);
					$raidplans['END_TIME'] = $user->format_date($row['raidplan_end_time'], $disp_date_time_format, true);
				}

				$template->assign_block_vars('raidplans', $raidplans);
			}
			$db->sql_freeresult($result);
		}
	}
	
	
	
	
}

?>
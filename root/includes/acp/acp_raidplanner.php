<?php
/**
* This class manages the Raidplanner settings
*
* @author Sajaki@bbdkp.com
* @package bbDkp.acp
* @copyright (c) 2010 bbdkp http://code.google.com/p/bbdkp/
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* $Id$
*  
**/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}
/**
 * This class manages the Raidplanner settings
 *
 */
class acp_raidplanner 
{
	public $u_action;
	public function main($id, $mode) 
	{
		global $db, $user, $auth, $template, $sid, $cache;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		$user->add_lang ( array ('mods/raidplanner', 'mods/dkp_common' ));
		if (!$auth->acl_get('a_raid_config'))
		{
			trigger_error($user->lang['USER_CANNOT_MANAGE_RAIDPLANNER'] );
		}
		
		$this->u_action = append_sid("{$phpbb_root_path}adm/index.$phpEx", "i=raidplanner&amp;mode=".$mode );
		$action	= request_var('action', '');
		
		
        switch ($mode) 
		{
			case 'rp_settings' :

				$updateroles = (isset($_POST['roleupdate'])) ? true : false;
				$deleterole = (request_var('roledelete', '') != '') ? true : false;
				$addrole = (isset($_POST['roleadd'])) ? true : false;
				
				$update	= (isset($_POST['update_rp_settings'])) ? true : false;
				$updateadv	= (isset($_POST['update_rp_settings_adv'])) ? true : false;

				// check the form key
				if ($updateroles || $addrole || $update || $updateadv)
				{
					if (!check_form_key('acp_raidplanner'))
					{
						trigger_error('FORM_INVALID');
					}
				}
				
				//user pressed edit button
				if( $updateroles)
				{
					$rolenames = utf8_normalize_nfc(request_var('rolename', array( 0 => ''), true));
					$roleneed1 = request_var('role_need1', array( 0 => 0));
					$roleneed2 = request_var('role_need2', array( 0 => 0));
					$rolecolor = request_var('role_color', array( 0 => ''));
					$roleicon = request_var('role_icon', array( 0 => ''));
					foreach ( $rolenames as $role_id => $role_name )
					{
						$data = array(
						    'role_name'     	=> $role_name,
						    'role_needed1'     	=> $roleneed1[$role_id],
						 	'role_needed2'     	=> $roleneed2[$role_id],
							'role_color'     	=> $rolecolor[$role_id],
							'role_icon'     	=> $roleicon[$role_id],
						);

						 $sql = 'UPDATE ' . RP_ROLES . ' SET ' . $db->sql_build_array('UPDATE', $data) . '
					   	     WHERE role_id=' . (int) $role_id; 
   						 $db->sql_query($sql); 		
						
					}
				}
				
				//user pressed add
				if($addrole)
				{
					$data = array(
					    'role_name'     => utf8_normalize_nfc(request_var('newrole', 'New role', true)),
						'role_needed1'   => request_var('newrole_need1', 0),
						'role_needed2'   => request_var('newrole_need2', 0),
						'role_color'     => request_var('newrole_color', ''),
						'role_icon'     => request_var('newrole_icon', ''),
					);

					$sql = 'INSERT INTO ' . RP_ROLES . $db->sql_build_array('INSERT', $data);
   					$db->sql_query($sql); 			
				}		

				
				//used pressed red cross to delete role
				if ($deleterole) 
				{
						// ask for permission
						if (confirm_box(true))
						{
							// @todo check if there are scheduled raids with this role, ask permission
							$sql = 'delete from ' . RP_ROLES . ' where role_id = ' . request_var('hiddenroleid', 0) ;
							$db->sql_query($sql);
							
							trigger_error(sprintf($user->lang['ROLE_DELETE_SUCCESS'], request_var('hiddenroleid', 0)), E_USER_WARNING);
						}
						else
						{
							// get field content
							$s_hidden_fields = build_hidden_fields(array(
								// set roledelete to 1. so when this gets in the $_POST output, the $deleterole becomes true
								'roledelete'	=> 1,
								'hiddenroleid'	=> request_var('delrole_id', 0),
								)
							);
							confirm_box(false, sprintf($user->lang['CONFIRM_DELETE_ROLE'], request_var('delrole_id', 0)), $s_hidden_fields);
						}
						
						
				}
				
				if($update)
				{
					
					$first_day	= request_var('first_day', 0);
					set_config  ( 'rp_first_day_of_week',  $first_day,0);  
					
					$invitehour	= request_var('event_invite_hh', 0) * 60 + request_var('event_invite_mm', 0);
					set_config  ( 'rp_default_invite_time',  $invitehour,0);
					$starthour =  request_var('event_start_hh', 0) * 60 + request_var('event_start_mm', 0);
					set_config  ( 'rp_default_start_time',  $starthour,0);
										
					$message="";
					$message .= '<br />' . sprintf( $user->lang['RPSETTINGS_UPDATED'], E_USER_NOTICE);
					trigger_error($message);
					
					
				}
				
				// move the calendar for daylight savings
				if( request_var('calPlusHour', 0) == 1)
				{
					$this->move_all_raidplans_by_one_hour( request_var('plusVal', 1));
					exit;
				}
				
				// update all advanced settings
				if( $updateadv )
				{
					
					$disp_week	= request_var('disp_week', 0);
					set_config  ( 'rp_index_display_week',  $disp_week,0);  
					
					$hour_mode = request_var('hour_mode', 12);
					set_config  ( 'rp_hour_mode',  $hour_mode,0);
					
					$disp_trunc = request_var('disp_trunc', 0);
					set_config  ( 'rp_display_truncated_name',  $disp_trunc,0);
					
					$disp_hidden_groups	= request_var('disp_hidden_groups', 0);
					set_config  ( 'rp_display_hidden_groups',  $disp_hidden_groups,0);
					
					$disp_raidplans_1_day = request_var('disp_raidplans_1_day', 0);
					set_config  ( 'rp_disp_raidplans_only_on_start',  $disp_raidplans_1_day,0);

					$date_format = request_var('date_format', 'M d, Y');
					set_config  ( 'rp_date_format',  $date_format,0);
					
					$date_time_format = request_var('date_time_format', 'M d, Y h:i a');
					set_config  ( 'rp_date_time_format',  $date_time_format,0);
					
					$time_format = request_var('time_format', 'h:i a');
					set_config  ( 'rp_time_format',  $time_format,0);

					// prune_freq is entered in days by user, but stored in seconds
					$prune_freq = request_var('prune_freq', 0);
					$prune_freq = 86400 * $prune_freq;
					set_config  ( 'rp_prune_frequency',  $prune_freq,0);
					
					// prune_limit is entered in days by user, but stored in seconds
					$prune_limit = request_var('prune_limit', 0);
					$prune_limit = 86400 * $prune_limit;
					set_config  ( 'rp_prune_limit',  $prune_limit,0);

					// auto populate recurring raidplan settings
					// populate_freq is entered in days by user, but stored in seconds
					$populate_freq = request_var('populate_freq', 0);
					$populate_freq = 86400 * $populate_freq;
					set_config  ( 'rp_populate_frequency',  $populate_freq,0);
					
					// populate_limit is entered in days by user, but stored in seconds
					$populate_limit = request_var('populate_limit', 0);
					$populate_limit = 86400 * $populate_limit;
					set_config  ( 'rp_populate_limit',  $populate_limit,0);

					$cache->destroy('config');
					
					$message="";
					$message .= '<br />' . sprintf( $user->lang['ADVRPSETTINGS_UPDATED'], E_USER_NOTICE);
					trigger_error($message);
					
					
				}

				$sel_monday = "";
				$sel_tuesday = "";
				$sel_wednesday = "";
				$sel_thursday = "";
				$sel_friday = "";
				$sel_saturday = "";
				$sel_sunday = "";
				switch($config['rp_first_day_of_week']  )
				{
					case 0:
						$sel_monday = "selected='selected'";
						break;
					case 1:
						$sel_tuesday = "selected='selected'";
						break;
					case 2:
						$sel_wednesday = "selected='selected'";
						break;
					case 3:
						$sel_thursday = "selected='selected'";
						break;
					case 4:
						$sel_friday = "selected='selected'";
						break;
					case 5:
						$sel_saturday = "selected='selected'";
						break;
					case 6:
						$sel_sunday = "selected='selected'";
						break;
				}
				
				// build presets for invite hour pulldown
				$s_event_invite_hh_options = '<option value="0"' . (isset($config['rp_default_invite_time']) ? '': ' selected="selected"' ) . '>--</option>';
				$invhour = intval($config['rp_default_invite_time'] / 60);
				for ($i = 0; $i <= 23; $i++)
				{
					$selected = ($i == $invhour ) ? ' selected="selected"' : '';
					$s_event_invite_hh_options .= "<option value=\"$i\"$selected>$i</option>";
				}
				// build presets for invite minute pulldown
				$s_event_invite_mm_options = '<option value="0"' . (isset($config['rp_default_invite_time']) ? '': ' selected="selected"' ) . '>--</option>';
				$invmin = $config['rp_default_invite_time'] - ($invhour*60); 
				for ($i = 0; $i <= 59; $i++)
				{
					$selected = ($i == $invmin ) ? ' selected="selected"' : '';
					$s_event_invite_mm_options .= "<option value=\"$i\"$selected>$i</option>";
				}
				// build presets for start hour pulldown
				$s_event_start_hh_options = '<option value="0"' . (isset($config['rp_default_start_time']) ? '': ' selected="selected"' ) . '>--</option>';
				$starthour = intval($config['rp_default_start_time'] / 60);
				for ($i = 0; $i <= 23; $i++)
				{
					$selected = ($i == $starthour ) ? ' selected="selected"' : '';
					$s_event_start_hh_options .= "<option value=\"$i\"$selected>$i</option>";
				}
				// build presets for start minute pulldown
				$s_event_start_mm_options = '<option value="0"' . (isset($config['rp_default_start_time']) ? '': ' selected="selected"' ) . '>--</option>';
				$startmin =  $config['rp_default_start_time'] - ($starthour* 60); 
				for ($i = 0; $i <= 59; $i++)
				{
					$selected = '';
					if($i == $startmin)
					{
						$selected = ' selected="selected"';
					}
					
					$s_event_start_mm_options .= "<option value=\"$i\"$selected>$i</option>";
				}				
					
				// select raid roles
				$sql = 'SELECT * FROM ' . RP_ROLES . '
						ORDER BY role_id';
				$db->sql_query($sql);
				$result = $db->sql_query($sql);
				$total_roles = 0;
				while ( $row = $db->sql_fetchrow($result) )
                {
                	$total_roles++;
                    $template->assign_block_vars('role_row', array(
                    	'ROLE_ID' 		=> $row['role_id'],
                        'ROLENAME' 		=> $row['role_name'],
	                    'ROLENEED1' 	=> $row['role_needed1'],
	                    'ROLENEED2' 	=> $row['role_needed2'],
                    	'ROLECOLOR' 	=> $row['role_color'],
                    	'ROLEICON' 		=> $row['role_icon'],
                    	'S_ROLE_ICON_EXISTS'	=>  (strlen($row['role_icon']) > 1) ? true : false,
                    	'ROLE_ICON' 	=> (strlen($row['role_icon']) > 1) ? $phpbb_root_path . "images/raidrole_images/" . $row['role_icon'] . ".png" : '',
                    	'U_DELETE' 		=> $this->u_action. '&roledelete=1&delrole_id=' . $row['role_id'],
                    	));
                }
                $db->sql_freeresult($result);
			
				$template->assign_vars(array(
					'S_RAID_INVITE_HOUR_OPTIONS'		=> $s_event_invite_hh_options,
					'S_RAID_INVITE_MINUTE_OPTIONS'		=> $s_event_invite_mm_options, 
					'S_RAID_START_HOUR_OPTIONS'			=> $s_event_start_hh_options,
					'S_RAID_START_MINUTE_OPTIONS'		=> $s_event_start_mm_options,
					'SEL_MONDAY'		=> $sel_monday,
					'SEL_TUESDAY'		=> $sel_tuesday,
					'SEL_WEDNESDAY'		=> $sel_wednesday,
					'SEL_THURSDAY'		=> $sel_thursday,
					'SEL_FRIDAY'		=> $sel_friday,
					'SEL_SATURDAY'		=> $sel_saturday,
					'SEL_SUNDAY'		=> $sel_sunday,
					'DISP_WEEK_CHECKED'	=> ( $config['rp_index_display_week'] == 1 ) ? "checked='checked'" : '',
					'DISP_NEXT_EVENTS_DISABLED'	=> ( $config['rp_index_display_week'] == 1 ) ? "disabled='disabled'" : '',
					'DISP_NEXT_EVENTS'	=> $config['rp_index_display_next_raidplans'],
					'SEL_12_HOURS'		=> ($config['rp_hour_mode'] == 12) ? "selected='selected'" :'',
					'SEL_24_HOURS'		=> ($config['rp_hour_mode'] != 12) ? "selected='selected'" :'' ,
					'DISP_TRUNCATED'	=> $config['rp_display_truncated_name'],
					'DISP_HIDDEN_GROUPS_CHECKED'	=> ($config['rp_display_hidden_groups'] == '1' ) ? "checked='checked'" : '',
					'DISP_EVENTS_1_DAY_CHECKED'	=> ( $config['rp_disp_raidplans_only_on_start'] == '1' ) ? "checked='checked'" : '',
					'DATE_FORMAT'		=> $config['rp_date_format'],
					'DATE_TIME_FORMAT'	=> $config['rp_date_time_format'],
					'TIME_FORMAT'		=> $config['rp_time_format'],
					'PRUNE_FREQ'		=> (int) $config['rp_prune_frequency'] / 86400,
					'PRUNE_LIMIT'		=> (int) $config['rp_prune_limit'] / 86400,
					'POPULATE_FREQ'		=> (int) $config['rp_populate_frequency'] / 86400,
					'POPULATE_LIMIT'	=> (int) $config['rp_populate_limit'] / 86400,
					'U_PLUS_HOUR'		=> $this->u_action."&calPlusHour=1&plusVal=1",
					'U_MINUS_HOUR'		=> $this->u_action."&calPlusHour=1&plusVal=0",
					'U_ACTION'			=> $this->u_action,
					));

				$this->tpl_name = 'dkp/acp_' . $mode;
				$this->page_title = $user->lang ['ACP_RAIDPLANNER_SETTINGS'];
				
				$form_key = 'acp_raidplanner';
				add_form_key($form_key);
		
				break;
			
		}
	}

	/**
	 * moves all raids in the calendar +/- one hour 
	 * helps when you reset the boards daylight savings time setting. 
	 * 
	 */
	private function move_all_raidplans_by_one_hour( $plusVal )
	{
		global $auth, $db, $user, $config, $phpEx, $phpbb_root_path;
	
		$s_hidden_fields = build_hidden_fields(array(
				'mode'		=> 'rp_settings',
				'i'			=> 'raidplanner',
				'plusVal'	=> $plusVal,
				'calPlusHour' => 1,
				)
		);
	
		$factor = 1;
		if( $plusVal == 0 )
		{
			$factor = -1;
		}
		
		if (confirm_box(true))
		{
	
			/* first populate all recurring raidplans to make sure
			   the cron job does not run again while we are working. */
			if (!class_exists('raidplanner_population'))
			{
				require($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_population.' . $phpEx);
			}
			$rp = new raidplanner_population;
			$rp->populate_calendar(0);
	
			/* next move all recurring raidplans by one hour
			   (note we will also edit the poster_timezone by one hour
			   so as not to change the calculation method) */
			
			// delete any recurring raidplans that are permanently over
			$sql = 'SELECT * FROM ' . RP_RECURRING . '
						ORDER BY recurr_id';
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$first_occ_time = $row['first_occ_time'] + ($factor * 3600);
				$final_occ_time = 0;
				if( $row['final_occ_time'] != 0 )
				{
					$final_occ_time = $row['final_occ_time'] + ($factor * 3600);
				}
				$last_calc_time = $row['last_calc_time'] + ($factor * 3600);
				$next_calc_time = $row['next_calc_time'] + ($factor * 3600);
				$poster_timezone = $row['poster_timezone'] + $factor;
				$recurr_id = (int) $row['recurr_id'];
				$sql = 'UPDATE ' . RP_RECURRING . '
					SET ' . $db->sql_build_array('UPDATE', array(
						'first_occ_time'	=> (int) $first_occ_time,
						'final_occ_time'	=> (int) $final_occ_time,
						'last_calc_time'	=> (int) $last_calc_time,
						'next_calc_time'	=> (int) $next_calc_time,
						'poster_timezone'	=> (float) $poster_timezone,
						)) . "
					WHERE recurr_id = $recurr_id";
				$db->sql_query($sql);
			}
			$db->sql_freeresult($result);
	
			/* finally move each individual raidplan by one hour */
			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
						ORDER BY raidplan_id';
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$sort_timestamp = max(0,$row['sort_timestamp'] + ($factor * 3600));
				$raidplan_invite_time = max(0,$row['raidplan_invite_time'] + ($factor * 3600));
				$raidplan_start_time = max($row['raidplan_start_time'] + ($factor * 3600),0);
				$raidplan_end_time = max(0,$row['raidplan_end_time'] + ($factor * 3600));
				$raidplan_id = $row['raidplan_id'];
				$sql = 'UPDATE ' . RP_RAIDS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', array(
						'sort_timestamp'	=> (int) $sort_timestamp,
						'raidplan_invite_time'	=> (int) $raidplan_invite_time,
						'raidplan_start_time'	=> (int) $raidplan_start_time,
						'raidplan_end_time'		=> (int) $raidplan_end_time,				
						)) . "
					WHERE raidplan_id = $raidplan_id";
				$db->sql_query($sql);
			}
			$db->sql_freeresult($result);
						 
			$meta_info = append_sid("{$phpbb_root_path}adm/index.$phpEx", "i=raidplanner&amp;mode=rp_settings" );
			meta_refresh(3, $meta_info);
			$message="";
			$message .= '<br /><br />' . sprintf( $user->lang['PLUS_HOUR_SUCCESS'],(string)$factor);
			trigger_error($message);
	
		}
		else
		{
			confirm_box(false, sprintf( $user->lang['PLUS_HOUR_CONFIRM'],(string)$factor), $s_hidden_fields);
		}
	}

}

?>

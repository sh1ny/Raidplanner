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

class acp_raidplanner 
{
	public $u_action;
	public function main($id, $mode) 
	{
		global $db, $user, $auth, $template, $sid, $cache;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		$user->add_lang ( array ('mods/raidplanner' ));
		if (!$auth->acl_get('a_raid_config'))
		{
			trigger_error($user->lang['USER_CANNOT_MANAGE_RAIDPLANNER'] );
		}
		
		$form_key = 'acp_raidplanner';
		add_form_key($form_key);

		$this->u_action = append_sid("{$phpbb_root_path}adm/index.$phpEx", "i=raidplanner&amp;mode=".$mode );
		$action	= request_var('action', '');
		
		$etype_id = 0;
		$etype_index = request_var('etype_index', 0);
		if( $etype_index > 0 )
		{
			// find the event_id for this item
			$sql = 'SELECT etype_id, etype_index FROM ' . RP_EVENT_TYPES_TABLE .'
			WHERE etype_index = ' . $db->sql_escape($etype_index);
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if( $row )
			{
				$etype_id = $row['etype_id'];
			}
		}
		
        switch ($mode) 
		{
			case 'rp_settings' :

				if( request_var('calPlusHour', 0) == 1)
				{
					$this->move_all_events_by_one_hour( request_var('plusVal', 1) );
					exit;
				}
				
				$update	= (isset($_POST['update_rp_settings'])) ? true : false;
				if( $update )
				{
					$first_day	= request_var('first_day', 0);
					set_config  ( 'rp_first_day_of_week',  $first_day,0);  
					
					$disp_week	= request_var('disp_week', 0);
					set_config  ( 'rp_index_display_week',  $disp_week,0);  
					
					$disp_next_events	= request_var('disp_next_events', 0);
					set_config  ( 'rp_index_display_next_events',  $disp_next_events,0);
					
					$hour_mode = request_var('hour_mode', 12);
					set_config  ( 'rp_hour_mode',  $hour_mode,0);
					
					$disp_trunc = request_var('disp_trunc', 0);
					set_config  ( 'rp_display_truncated_name',  $disp_trunc,0);
					
					$disp_hidden_groups	= request_var('disp_hidden_groups', 0);
					set_config  ( 'rp_display_hidden_groups',  $disp_hidden_groups,0);
					
					$disp_events_1_day = request_var('disp_events_1_day', 0);
					set_config  ( 'rp_disp_events_only_on_start',  $disp_events_1_day,0);

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

					// auto populate recurring event settings
					// populate_freq is entered in days by user, but stored in seconds
					$populate_freq = request_var('populate_freq', '0');
					$populate_freq = 86400 * $populate_freq;
					set_config  ( 'rp_populate_frequency',  $populate_freq,0);
					
					// populate_limit is entered in days by user, but stored in seconds
					$populate_limit = request_var('populate_limit', '0');
					$populate_limit = 86400 * $populate_limit;
					set_config  ( 'rp_populate_limit',  $populate_limit,0);

					$cache->destroy('config');
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

				$template->assign_vars(array(
					'SEL_MONDAY'		=> $sel_monday,
					'SEL_TUESDAY'		=> $sel_tuesday,
					'SEL_WEDNESDAY'		=> $sel_wednesday,
					'SEL_THURSDAY'		=> $sel_thursday,
					'SEL_FRIDAY'		=> $sel_friday,
					'SEL_SATURDAY'		=> $sel_saturday,
					'SEL_SUNDAY'		=> $sel_sunday,
					'DISP_WEEK_CHECKED'	=> ( $config['rp_index_display_week'] == '1' ) ? "checked='checked'" : '',
					'DISP_NEXT_EVENTS_DISABLED'	=> ( $config['rp_index_display_week'] == '1' ) ? "disabled='disabled'" : '',
					'DISP_NEXT_EVENTS'	=> $config['rp_index_display_next_events'],
					'SEL_12_HOURS'		=> ($config['rp_hour_mode'] == 12) ? "selected='selected'" :'',
					'SEL_24_HOURS'		=> ($config['rp_hour_mode'] != 12) ? "selected='selected'" :'' ,
					'DISP_TRUNCATED'	=> $config['rp_display_truncated_name'],
					'DISP_HIDDEN_GROUPS_CHECKED'	=> ($config['rp_display_hidden_groups'] == '1' ) ? "checked='checked'" : '',
					'DISP_EVENTS_1_DAY_CHECKED'	=> ( $config['rp_disp_events_only_on_start'] == '1' ) ? "checked='checked'" : '',
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
				
				break;
			case 'rp_eventsettings' :
				$this->u_action = $this->u_action . "&amp;mode=rp_eventsettings";
				$this->tpl_name = 'dkp/acp_' . $mode;
				
				if( $update )
				{
					switch ($action)
					{
						case 'delete':
							
							// determine if we're deleting all existing
							// events of this type or converting them
							$delete_existing_events	= request_var('delete_ee', '');
							if( $delete_existing_events === 'delete' )
							{
								$sql = 'DELETE FROM ' . RP_EVENTS_TABLE . '
								WHERE etype_id = ' . $db->sql_escape($etype_id);
								$db->sql_query($sql);
							}
							else
							{
								$new_etype_id	= request_var('new_id', -1);
								if( $new_etype_id >= 0 )
								{
									$sql = 'UPDATE ' . RP_EVENTS_TABLE . '
											SET etype_id = ' . $db->sql_escape($new_etype_id) . '
											WHERE etype_id = ' . $db->sql_escape($etype_id);
									$db->sql_query($sql);
								}
							}

							$sql = 'DELETE FROM ' . RP_EVENT_TYPES_TABLE . '
							WHERE etype_index = ' . $db->sql_escape($etype_index);
							$db->sql_query($sql);

							$sql = 'UPDATE ' . RP_EVENT_TYPES_TABLE . '
									SET etype_index = etype_index - 1
									WHERE etype_index > ' . $db->sql_escape($etype_index);
							$db->sql_query($sql);
							$auth->acl_clear_prefetch();
							$cache->destroy('sql', RP_EVENTS_TABLE);
							$cache->destroy('sql', RP_EVENT_TYPES_TABLE);
							
							break;
						case 'add':
							$sql = 'SELECT COUNT(etype_id) as num_etypes
									FROM ' . RP_EVENT_TYPES_TABLE;
							$result = $db->sql_query($sql);
							$num_etypes = $db->sql_fetchfield('num_etypes');
							$db->sql_freeresult($result);
							$num_etypes++;
							$etype_full_name = request_var('etype_full_name', '', true);
							$null_string = "";
							/* add the new event type and set etype_index using $num_etypes */
							$sql = 'INSERT INTO ' . RP_EVENT_TYPES_TABLE . ' ' . $db->sql_build_array('INSERT', array(
									'etype_index'		=> (int) $num_etypes,
									'etype_full_name'	=> (string) $etype_full_name,
									'etype_display_name'=> (string) $etype_full_name,
									'etype_color'		=> (string) $null_string,
									'etype_image'		=> (string) $null_string,
								));
							$db->sql_query($sql);
							$cache->destroy('sql', RP_EVENTS_TABLE);
							$cache->destroy('sql', RP_EVENT_TYPES_TABLE);							
							break;
						case 'edit':
							$etype_id = request_var('etype_id', 0);
							$etype_full_name = request_var('etype_full_name', '', true);
							$etype_color = request_var('etype_color', '');
							$etype_image = request_var('etype_image', '');
							$etype_display_name = request_var('etype_display_name', '', true);

							$sql = 'UPDATE ' . RP_EVENT_TYPES_TABLE . '
									SET ' . $db->sql_build_array('UPDATE', array(
									'etype_full_name'	=> $etype_full_name,
									'etype_display_name'=> $etype_display_name,
									'etype_color'		=> $etype_color,
									'etype_image'		=> $etype_image,
									)) . '
									WHERE etype_id = ' . $etype_id;
							$db->sql_query($sql);
							break;
								
								
					}
				}
				else
				{
					switch ($action)
					{
						case 'delete':
							$sql = 'SELECT COUNT(etype_id) as num_events
									FROM ' . RP_EVENTS_TABLE . '
									WHERE etype_id = ' . $db->sql_escape($etype_id);
							$result = $db->sql_query($sql);
							$num_events = $db->sql_fetchfield('num_events');
							$db->sql_freeresult($result);

							// get current event type NAME
							$etype_full_name = "";
							$sql = 'SELECT etype_full_name, etype_id FROM ' . RP_EVENT_TYPES_TABLE .'
									WHERE etype_id = ' . $db->sql_escape($etype_id);
							$result = $db->sql_query($sql);
							if( $row = $db->sql_fetchrow($result))
							{
								$etype_full_name = $row['etype_full_name'];
							}
							$db->sql_freeresult($result);

							if( $num_events > 0 )
							{
								/* there are existing events of this type -
								   find out what we should do with them */
								$sql = 'SELECT etype_full_name, etype_id FROM ' . RP_EVENT_TYPES_TABLE .'
										WHERE etype_id <> ' . $db->sql_escape($etype_id) . '
										ORDER BY etype_index';
								$result = $db->sql_query($sql);
								while ($row = $db->sql_fetchrow($result))
								{
									$url = $this->u_action . "&amp;etype_index={$row['etype_index']}";
									$template->assign_block_vars('alt_event_types',
										array(
												'FULL_NAME'	=> $row['etype_full_name'],
												'ID'		=> $row['etype_id'],
									));
									$row_counter++;
								}
								$db->sql_freeresult($result);
							}

							$this->page_title = 'ACP_CALENDAR_DELETE_EVENT_TYPE';
							$template->assign_vars(array(
								'S_DELETE_ETYPE'	=> true,
								'ETYPE_FULL_NAME'	=> $etype_full_name,
								'U_BACK'			=> $this->u_action,
								'U_ACTION'			=> $this->u_action."&amp;action=delete&amp;etype_index=".$etype_index,
								'NUM_EXISTING_EVENTS'=> $num_events,
								));
							return;							
							break;
							
						case 'edit':
							
							$sql = 'SELECT * FROM ' . RP_EVENT_TYPES_TABLE .'
									WHERE etype_index = ' . $db->sql_escape($etype_index);
							$result = $db->sql_query($sql);
							$row = $db->sql_fetchrow($result);
							$db->sql_freeresult($result);
							if( $row )
							{
								$template->assign_vars(array(
									'S_EDIT_ETYPE'		=> true,
									'ETYPE_FULL_NAME'	=> $row['etype_full_name'],
									'ETYPE_DISPLAY_NAME'=> $row['etype_display_name'],
									'ETYPE_ID'			=> $row['etype_id'],
									'ETYPE_COLOR'		=> $row['etype_color'],
									'ETYPE_IMAGE'		=> $row['etype_image'],
									'U_SWATCH'			=> append_sid("{$phpbb_admin_path}swatch.$phpEx", 'form=acp_etype&amp;name=etype_color'),
									'U_BACK'			=> $this->u_action,
									'U_ACTION'			=> $this->u_action."&amp;action=edit",
								));
							}
							return;
							break;
									
								
					}
				}
				
				switch ($action)
				{
					case 'move_up':
					case 'move_down':
						if ($etype_index == 0)
						{
							trigger_error($user->lang['NO_EVENT_TYPE_ERROR']);
						}

						if( $action === 'move_up' )
						{
							$etype_index = $etype_index - 1;
							// find the event_id for this item
							$sql = 'SELECT etype_id, etype_index FROM ' . RP_EVENT_TYPES_TABLE .'
							WHERE etype_index = '. $db->sql_escape($etype_index);
							$result = $db->sql_query($sql);
							$row = $db->sql_fetchrow($result);
							$db->sql_freeresult($result);
							if( !$row )
							{
								trigger_error($user->lang['NO_EVENT_TYPE_ERROR']);
							}
							$etype_id = $row['etype_id'];
						}
						
						$etype_index_plus_1 = $etype_index + 1;
						$sql = 'UPDATE ' . RP_EVENT_TYPES_TABLE . '
								SET etype_index = '.$db->sql_escape($etype_index_plus_1). '
								WHERE etype_index = ' . $db->sql_escape($etype_index);
						$db->sql_query($sql);

						$sql = 'UPDATE ' . RP_EVENT_TYPES_TABLE . '
								SET etype_index = '.$db->sql_escape($etype_index).'
								WHERE etype_index = ' . $db->sql_escape($etype_index_plus_1) . '
								AND etype_id <> '.$db->sql_escape($etype_id);
						
						$db->sql_query($sql);
						
						$cache->destroy('sql', RP_EVENTS_TABLE);
						$cache->destroy('sql', RP_EVENT_TYPES_TABLE);
						
					break;
				}
				
				//find the available event types:
				$sql = 'SELECT * FROM ' . RP_EVENT_TYPES_TABLE .' ORDER BY etype_index';
				$result = $db->sql_query($sql);
				$row_counter = 0;
				while ($row = $db->sql_fetchrow($result))
				{
					$url = $this->u_action . "&amp;etype_index={$row['etype_index']}";
					$template->assign_block_vars('event_types',
						array(
								'COUNT'			=> $row_counter,
								'FULL_NAME'			=> $row['etype_full_name'],
								'DISPLAY_NAME'	=> $row['etype_display_name'],
								'ID'			=> $row['etype_id'],
								'COLOR'			=> $row['etype_color'],
								'IMAGE'			=> $row['etype_image'],
								'U_MOVE_UP'			=> $url . '&amp;action=move_up',
								'U_MOVE_DOWN'		=> $url . '&amp;action=move_down',
								'U_EDIT'			=> $url . '&amp;action=edit',
								'U_DELETE'			=> $url . '&amp;action=delete',
					));
					$row_counter++;
				}
				$db->sql_freeresult($result);
				
				$template->assign_vars(array(
						'S_MANAGE_ETYPES'	=> 1,
						'U_ACTION'			=> $this->u_action,
						));
				
				
   				
				$this->page_title = $user->lang ['ACP_RAIDPLANNER_EVENTSETTINGS'];
				break;
				
		}
	}

	public function move_all_events_by_one_hour( $plusVal )
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
	
			/* first populate all recurring events to make sure
			   the cron job does not run again while we are working. */
			include_once($phpbb_root_path . 'includes/bbdkp/raidplanner/functions_rp.' . $phpEx);
			populate_calendar(0);
	
			/* next move all recurring events by one hour
			   (note we will also edit the poster_timezone by one hour
			   so as not to change the calculation method) */
			
			// delete any recurring events that are permanently over
			$sql = 'SELECT * FROM ' . RP_RECURRING_EVENTS_TABLE . '
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
				$sql = 'UPDATE ' . RP_RECURRING_EVENTS_TABLE . '
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
	
			/* finally move each individual event by one hour */
			$sql = 'SELECT * FROM ' . RP_EVENTS_TABLE . '
						ORDER BY event_id';
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$sort_timestamp = $row['sort_timestamp'] + ($factor * 3600);
				$event_start_time = $row['event_start_time'] + ($factor * 3600);
				$event_end_time = $row['event_end_time'] + ($factor * 3600);
				$event_id = $row['event_id'];
				$sql = 'UPDATE ' . RP_EVENTS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', array(
						'sort_timestamp'	=> (int) $sort_timestamp,
						'event_start_time'	=> (int) $event_start_time,
						'event_end_time'	=> (int) $event_end_time,
						)) . "
					WHERE event_id = $event_id";
				$db->sql_query($sql);
			}
			$db->sql_freeresult($result);
						 
			$meta_info = append_sid("{$phpbb_root_path}adm/index.$phpEx", "i=raidplanner&amp;mode=rp_settings" );
			meta_refresh(3, $meta_info);
	
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

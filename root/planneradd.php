<?php
/**
*
* @author Sajaki
* @package  Raidplanner
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_display.' . $phpEx);
$raidevents = new raidevents();
include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_population.' . $phpEx);
$newraid= new raidplanner_population();

// Start session management
$user->session_begin();
$auth->acl($user->data);

// Language files 
$user->setup('posting');
$user->add_lang ( array ('posting', 'mods/dkp_admin','mods/raidplanner'  ));
// Grab only parameters needed here
//----------------------------
$event_id	= request_var('calEid', 0);
$lastclick	= request_var('lastclick', 0);  // time of last click
$submit		= (isset($_POST['post'])) ? true : false;
$preview	= (isset($_POST['preview'])) ? true : false;
$delete		= (isset($_POST['delete'])) ? true : false;
$cancel		= (isset($_POST['cancel'])) ? true : false;

// mode: post, edit, delete, or smilies
$mode = ($delete && !$preview && $submit) ? 'delete' : request_var('mode', '');
$error = array();
// get the number of events : of none defined throw error..
if( $newraid->available_etype_count < 1 )
{
	trigger_error('NO_EVENT_TYPES');
}

$current_time = $user->time_now;

/*-----------------------------------
  begin event_data initialization
-------------------------------------*/
$event_data = array();
if( $event_id !== 0 )
{
	$raidevents->get_event_data( $event_id, $event_data );
}
else
{
	if( $auth->acl_get('u_raidplanner_create_recurring_events') )
	{
		$event_data['s_recurring_opts'] = true;
	}
	$event_data['event_id'] = 0;
	$event_data['event_start_time'] = 0;
	
	$event_data['etype_id'] = 0;
	$event_data['event_subject'] = "";
	$event_data['event_body'] = "";
	$event_data['poster_id'] = $user->data['user_id'];
	$event_data['poster_timezone'] = $user->data['user_timezone'];
	$event_data['poster_dst'] = $user->data['user_dst'];
	// set event tracking to 1 by default
	$event_data['track_signups'] = 1;
	$event_data['event_day'] = "00-00-0000";
	$event_data['signup_yes'] = 0;
	$event_data['signup_no'] = 0;
	$event_data['signup_maybe'] = 0;
	$event_data['recurr_id'] = 0;
	$event_data['is_recurr'] = 0;
	$event_data['frequency_type'] = 0;
	$event_data['frequency'] = 0;
	$event_data['final_occ_time'] = 0;
	$event_data['week_index'] = 0;
	$event_data['first_day_of_week'] = $config["rp_first_day_of_week"];

}

// take care of smilies popup
if( $mode == 'smilies' )
{
	$newraid->generate_calendar_smilies('window');
	trigger_error('NO_POST_EVENT_MODE');
}

/*-------------------------------------
  begin permission checking
-------------------------------------*/
$newraid->authcheck($mode, $submit, $event_data, $event_id);
		
/*-------------------------------------
  Handle delete mode...
-------------------------------------*/
if ($mode == 'delete')
{
    $delete_all = request_var('calDelAll', 0);
    if( $delete_all == 0 )
    {
		$newraid->handle_event_delete($event_id, $event_data);
	}
	else
	{
		$newraid->handle_event_delete_all($event_id, $event_data);
	}
	exit;
}

/*---------------------------------------------------------
  If in edit mode, we need to find out if we are editing
  one event, or all recurring events... BEFORE we start querying
  all the submitted data!
---------------------------------------------------------*/
$s_date_time_opts = true;
if( $event_id != 0 )
{
    $edit_all = request_var('calEditAll', 0);
    if( $edit_all != 0 )
    {
		$s_date_time_opts = false;
    }
}

// in submit or preview we need to gather the posted data...
if ($submit || $preview)
{
	//complete the event array by calling the gather function
	$newraid->gather_raiddata($event_data, $newraid, $s_date_time_opts);
	
	if( $event_id > 0 )
	{
		$newraid->edit_event($event_data, $newraid, $event_id );
	}
	else 
	{
		// we have all data, now go create the raid
		//pass zero event_id by reference to get it updated
		$newraid->create_event($event_data, $newraid, $event_id);
	}
	
	$main_calendar_url = append_sid("{$phpbb_root_path}planner.$phpEx", "calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
	$view_event_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=event&amp;calEid=".$event_id."&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
	
	// by default you should show the user the newly created event - not the main calendar page
	meta_refresh(3, $view_event_url);
	
	if( $mode == 'edit' )
	{
		$message = $user->lang['EVENT_EDITED'] . '<br /><br />' . sprintf($user->lang['VIEW_EVENT'], '<a href="' . $view_event_url . '">', '</a>');
	}
	else
	{
		$message = $user->lang['EVENT_STORED'] . '<br /><br />' . sprintf($user->lang['VIEW_EVENT'], '<a href="' . $view_event_url . '">', '</a>');
	}
	
	$message .= '<br /><br />' . sprintf($user->lang['RETURN_CALENDAR'], '<a href="' . $main_calendar_url . '">', '</a>');
	trigger_error($message);
	
}

// Preview
if ($preview)
{
	// translate event start and end time into user's timezone
	$user_event_start = $event_data['event_start_time'] + $user->timezone + $user->dst;
	

	// Convert event comment into preview version with bbcode and all
	$event_body = $event_data['event_body'];
	$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
	$allow_bbcode = $allow_urls = $allow_smilies = true;
	generate_text_for_storage($event_body, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
	$preview_message = generate_text_for_display($event_body, $uid, $bitfield, $options);

	$preview_etype_display_name = $newraid->available_etype_display_names[$event_data['etype_id']];
	$preview_event_color = $newraid->available_etype_colors[$event_data['etype_id']];
	$preview_event_image = $newraid->available_etype_images[$event_data['etype_id']];
	$preview_subject = censor_text($event_data['event_subject']);

	$poster_url = '';
	$invite_list = '';
	
	$revents = new raidevents;
	$revents->get_event_invite_list_and_poster_url($event_data, $poster_url, $invite_list );
	

	if (!sizeof($error))
	{
		$template->assign_vars(array(
			'PREVIEW_SUBJECT'		=> $preview_subject,
			'PREVIEW_ETYPE_DISPLAY_NAME'=> $preview_etype_display_name,
			'PREVIEW_EVENT_COLOR'	=> $preview_event_color,
			'PREVIEW_EVENT_IMAGE'	=> $preview_event_image,
			'PREVIEW_MESSAGE'		=> $preview_message,
			'PREVIEW_START_DATE'	=> $user->format_date($event_data['event_start_time']),
		
			'PREVIEW_POSTER'		=> $poster_url,
			'PREVIEW_INVITED'		=> $invite_list,
			
			'S_DISPLAY_PREVIEW'		=> true,
			'PREVIEW_TRACK_SIGNUPS'	=> $event_data['track_signups'],
			  )
		);
	}
}

/******************************************************************************************************
 * 
 * build new Raid posting form
 * 
 ******************************************************************************************************/

// action URL, include session_id for security purpose
$s_action = append_sid("{$phpbb_root_path}planneradd.$phpEx", "mode=$mode", true, $user->session_id);

// Page title
switch ($mode)
{
	case 'post':
		$page_title = $user->lang['CALENDAR_POST_EVENT'];
	break;

	case 'delete':
	case 'edit':
		$page_title = $user->lang['CALENDAR_EDIT_EVENT'];
		// Decode text for message editing
		decode_message($event_data['event_body'], $event_data['bbcode_uid']);
	break;
}

$temp_find_str = "<br />";
$temp_replace_str = "\n";
$event_data['event_body'] = str_replace( $temp_find_str, $temp_replace_str, $event_data['event_body'] );

//-----------------------------------------
// populate form options...
//-----------------------------------------

//count events from bbdkp, put them in a pulldown...
$e_type_sel_code  = "";
for( $i = 0; $i < $newraid->available_etype_count; $i++ )
{
	$e_type_sel_code .= "<option value='".$newraid->available_etype_ids[$i]."'>".$newraid->available_etype_full_names[$i]."</option>\n";
}

// event acces level
$level_sel_code ="";	
// Find what groups this user is a member of and add them to the list of groups to invite
$group_sel_code = $newraid->posting_generate_group_selection_code( $event_data['poster_id'] );
if( $auth->acl_get('u_raidplanner_create_public_events') )
{
	$level_sel_code .= "<option value='2'>".$user->lang['EVENT_ACCESS_LEVEL_PUBLIC']."</option>\n";
}
if( $auth->acl_get('u_raidplanner_create_group_events') )
{
	$level_sel_code .= "<option value='1'>".$user->lang['EVENT_ACCESS_LEVEL_GROUP']."</option>\n";
}
if( $auth->acl_get('u_raidplanner_create_private_events') )
{
	$level_sel_code .= "<option value='0'>".$user->lang['EVENT_ACCESS_LEVEL_PERSONAL']."</option>\n";
}



// Raid start date
$month_sel_code  = " ";
for( $i = 1; $i <= 12; $i++ )
{
	$month_sel_code .= "<option value='".$i."'>".$user->lang['datetime'][$newraid->month_names[$i]]."</option>\n";
}

$day_sel_code= "";
for( $i = 1; $i <= 31; $i++ )
{
	$day_sel_code .= "<option value='".$i."'>".$i."</option>\n";
}

$year_sel_code  = " ";
for( $i = $newraid->date['year']; $i < ($newraid->date['year']+5); $i++ )
{
	$year_sel_code .= "<option value='".$i."'>".$i."</option>\n";
}

// Raid start time
$hour_sel_code = "";
$hour_mode = $config['rp_hour_mode'];
if( $hour_mode == 12 )
{
	for( $i = 0; $i < 24; $i++ )
	{
		$mod_12 = $i % 12;
		if( $mod_12 == 0 )
		{
			$mod_12 = 12;
		}
		$am_pm = $user->lang['PM'];
		if( $i < 12 )
		{
			$am_pm = $user->lang['AM'];
		}
		$hour_sel_code .= "<option value='".$i."'>".$am_pm." ".$mod_12."</option>\n";
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
		$hour_sel_code .= "<option value='".$i."'>".$o.$i."</option>\n";
	}
}

$min_sel_code = "";
for( $i = 0; $i < 12; $i++ )
{
	$t = $i * 5;
	$o = "";
	if($t < 10 )
	{
		$o="0";
	}
	$min_sel_code .= "<option value='".$t."'>".$o.$t."</option>\n";
}

/**
 * Event recurrance
 */
$recurr_event_check = "";
$recurr_event_freq_sel_code = "";
$recurr_event_freq_val_code = "";
$end_recurr_month_sel_code = "";
$end_recurr_day_sel_code = "";
$end_recurr_year_sel_code = "";

if( $event_data['s_recurring_opts'] )
{
	$recurr_event_check = "<input type='checkbox' name='calIsRecurr' value='ON' onclick='update_recurr_state();update_recurring_options();' />";
	$recurr_event_freq_sel_code  = "<select name='calRFrqT' id='calRFrqT' disabled='disabled'>\n";
	$recurr_event_freq_sel_code .= "<option value='1'>".$user->lang['RECURRING_EVENT_CASE_1']."</option>\n";
	$recurr_event_freq_sel_code .= "<option value='2'>".$user->lang['RECURRING_EVENT_CASE_2']."</option>\n";
	$recurr_event_freq_sel_code .= "<option value='3'>".$user->lang['RECURRING_EVENT_CASE_3']."</option>\n";
	$recurr_event_freq_sel_code .= "<option value='4'>".$user->lang['RECURRING_EVENT_CASE_4']."</option>\n";
	$recurr_event_freq_sel_code .= "<option value='5'>".$user->lang['RECURRING_EVENT_CASE_5']."</option>\n";
	$recurr_event_freq_sel_code .= "<option value='6'>".$user->lang['RECURRING_EVENT_CASE_6']."</option>\n";
	$recurr_event_freq_sel_code .= "</select>\n";
	$recurr_event_freq_val_code = "<input type='number' name='calRFrq' id='calRFrq' value='1' onchange='update_recurring_options()' disabled='disabled' />";

	$temp_find_str = "id='calM'";
	$temp_replace_str = "id='calM' onchange='update_recurring_options()'";
	$month_sel_code = str_replace( $temp_find_str, $temp_replace_str, $month_sel_code );

	$temp_find_str = "id='calD'";
	$temp_replace_str = "id='calD' onchange='update_recurring_options()'";
	$day_sel_code = str_replace( $temp_find_str, $temp_replace_str, $day_sel_code );

	$temp_find_str = "id='calY'";
	$temp_replace_str = "id='calY' onchange='update_recurring_options()'";
	$year_sel_code = str_replace( $temp_find_str, $temp_replace_str, $year_sel_code );

	$end_recurr_month_sel_code  = "<select name='calRMEnd' id='calRMEnd' onchange='update_recurring_end_date_opts(this.value)' disabled='disabled'>\n";
	$end_recurr_month_sel_code .= "<option value='0'>".$user->lang['NEVER']."</option>\n";
	for( $i = 1; $i <= 12; $i++ )
	{
		$end_recurr_month_sel_code .= "<option value='".$i."'>".$user->lang['datetime'][$newraid->month_names[$i]]."</option>\n";
	}
	$end_recurr_month_sel_code .= "</select>\n";

	$end_recurr_day_sel_code  = "<select name='calRDEnd' id='calRDEnd' onchange='update_recurring_end_date_opts(this.value)' disabled='disabled'>\n";
	$end_recurr_day_sel_code .= "<option value='0'>".$user->lang['NEVER']."</option>\n";
	for( $i = 1; $i <= 31; $i++ )
	{
		$end_recurr_day_sel_code .= "<option value='".$i."'>".$i."</option>\n";
	}
	$end_recurr_day_sel_code .= "</select>\n";

	$end_recurr_year_sel_code  = "<select name='calRYEnd' id='calRYEnd' onchange='update_recurring_end_date_opts(this.value)' disabled='disabled'>\n";
	$end_recurr_year_sel_code .= "<option value='0'>".$user->lang['NEVER']."</option>\n";
	for( $i = $newraid->date['year']; $i < ($newraid->date['year']+5); $i++ )
	{
		$end_recurr_year_sel_code .= "<option value='".$i."'>".$i."</option>\n";
	}
	$end_recurr_year_sel_code .= "</select>\n";

}

$cancel_url = append_sid("{$phpbb_root_path}planner.$phpEx", "m=".$newraid->date['month_no']."&amp;y=".$newraid->date['year']);

// check to see if we're editing an existing event
if( sizeof($error) || $preview || $event_id > 0 )
{
	
	// get profile for this raid
	$sql_array = array(
		    'SELECT'    => 'r.role_id, r.role_name, er.role_needed ', 
		    'FROM'      => array(
		        RP_ROLES   => 'r'
		    ),
		    'LEFT_JOIN' => array(
		        array(
		            'FROM'  => array( RP_EVENTROLES  => 'er'),
		            'ON'    => 'r.role_id = er.role_id AND er.event_id = ' . $event_id  
		        )
		    ),
		    'ORDER_BY'  => 'r.role_id'
	);
	
	$sql = $db->sql_build_query('SELECT', $sql_array);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
	    $template->assign_block_vars('raidroles', array(
	        'ROLE_ID'        => $row['role_id'],
		    'ROLE_NAME'      => $row['role_name'],
	    	'ROLE_NEEDED2'    => $row['role_needed'],
	    ));
	}
	$db->sql_freeresult($result);
	

	// translate event start and end time into user's timezone
	$event_start = $event_data['event_start_time'] + $user->timezone + $user->dst;
	$cancel_url = append_sid("{$phpbb_root_path}planner.$phpEx", "m=".gmdate('n', $event_start)."&amp;y=".gmdate('Y', $event_start) );

	//-----------------------------------------
	// month selection data
	//-----------------------------------------

	$end_month_sel_code = str_replace( $temp_find_str, $temp_replace_str, $month_sel_code );
	$temp_find_str = "name='calM' id='calM'";
	$temp_replace_str = "name='calMEnd' id='calMEnd'";
	$end_month_sel_code = str_replace( $temp_find_str, $temp_replace_str, $end_month_sel_code );
	$temp_find_str = "value='".gmdate('n', $event_start)."'";
	$temp_replace_str = "value='".gmdate('n', $event_start)."' selected='selected'";
	$month_sel_code = str_replace( $temp_find_str, $temp_replace_str, $month_sel_code );

	//-----------------------------------------
	// day selection data
	//-----------------------------------------
	
	$end_day_sel_code = str_replace( $temp_find_str, $temp_replace_str, $day_sel_code );
	$temp_find_str = "name='calD' id='calD'";
	$temp_replace_str = "name='calDEnd' id='calDEnd'";
	$end_day_sel_code = str_replace( $temp_find_str, $temp_replace_str, $end_day_sel_code );
	$temp_find_str = "value='".gmdate('j', $event_start)."'";
	$temp_replace_str = "value='".gmdate('j', $event_start)."' selected='selected'";
	$day_sel_code = str_replace( $temp_find_str, $temp_replace_str, $day_sel_code );

	//-----------------------------------------
	// year selection data
	//-----------------------------------------
	
	$end_year_sel_code = str_replace( $temp_find_str, $temp_replace_str, $year_sel_code );
	$temp_find_str = "name='calY' id='calY'";
	$temp_replace_str = "name='calYEnd' id='calYEnd'";
	$end_year_sel_code = str_replace( $temp_find_str, $temp_replace_str, $end_year_sel_code );
	$temp_find_str = "value='".gmdate('Y', $event_start)."'";
	$temp_replace_str = "value='".gmdate('Y', $event_start)."' selected='selected'";
	$year_sel_code = str_replace( $temp_find_str, $temp_replace_str, $year_sel_code );

	//-----------------------------------------
	// hour selection data
	//-----------------------------------------
	
	$end_hour_code = str_replace( $temp_find_str, $temp_replace_str, $hour_sel_code );
	$temp_find_str = "name='calHr' id='calHr'";
	$temp_replace_str = "name='calHrEnd' id='calHrEnd'";
	$end_hour_code = str_replace( $temp_find_str, $temp_replace_str, $end_hour_code );
	$temp_find_str = "value='".gmdate('G', $event_start)."'";
	$temp_replace_str = "value='".gmdate('G', $event_start)."' selected='selected'";
	$start_hour_code = str_replace( $temp_find_str, $temp_replace_str, $hour_sel_code );

	//-----------------------------------------
	// minute selection data
	//-----------------------------------------
	
	$end_min_code = str_replace( $temp_find_str, $temp_replace_str, $min_sel_code );
	$temp_find_str = "name='calMn' id='calMn'";
	$temp_replace_str = "name='calMnEnd' id='calMnEnd'";
	$end_min_code = str_replace( $temp_find_str, $temp_replace_str, $end_min_code );
	$temp_find_str = "value='".gmdate('i', $event_start)."'";
	$temp_replace_str = "value='".gmdate('i', $event_start)."' selected='selected'";
	$start_min_code = str_replace( $temp_find_str, $temp_replace_str, $min_sel_code );

	//-----------------------------------------
	// event type data
	//-----------------------------------------
	$temp_find_str = "value='".$event_data['etype_id']."'";
	$temp_replace_str = "value='".$event_data['etype_id']."' selected='selected'";
	$e_type_sel_code = str_replace( $temp_find_str, $temp_replace_str, $e_type_sel_code );

	//-----------------------------------------
	// event levels
	//-----------------------------------------
	$temp_find_str = "value='".$event_data['event_access_level']."'";
	$temp_replace_str = "value='".$event_data['event_access_level']."' selected='selected'";
	$level_sel_code = str_replace( $temp_find_str, $temp_replace_str, $level_sel_code );

	if( $event_data['group_id'] != 0 )
	{
		$temp_find_str = "value='".$event_data['group_id']."'";
		$temp_replace_str = "value='".$event_data['group_id']."' selected='selected'";
		$group_sel_code = str_replace( $temp_find_str, $temp_replace_str, $group_sel_code );
	}
	else
	{
		$group_list = explode( ',', $event_data['group_id_list'] );
		$num_groups = sizeof( $group_list );
		for( $group_index = 0; $group_index < $num_groups; $group_index++ )
		{
			if( $group_list[$group_index] == "" )
			{
				continue;
			}
			$temp_find_str = "value='".$group_list[$group_index]."'";
			$temp_replace_str = "value='".$group_list[$group_index]."' selected='selected'";
			$group_sel_code = str_replace( $temp_find_str, $temp_replace_str, $group_sel_code );
		}
	}

	if( $event_data['event_access_level'] == 1 )
	{
		$temp_find_str = "disabled='disabled'";
		$temp_replace_str = "";
		$group_sel_code = str_replace( $temp_find_str, $temp_replace_str, $group_sel_code );
	}

	//-----------------------------------------
	// recurring events
	//-----------------------------------------
	if( $event_data['is_recurr'] == 1 )
	{
		$temp_find_str = "value='ON'";
		$temp_replace_str = "value='ON' checked='checked'";
		$recurr_event_check = str_replace( $temp_find_str, $temp_replace_str, $recurr_event_check );

		$temp_find_str = "disabled='disabled'";
		$temp_replace_str = "";
		$recurr_event_freq_sel_code = str_replace( $temp_find_str, $temp_replace_str, $recurr_event_freq_sel_code );
		$recurr_event_freq_val_code = str_replace( $temp_find_str, $temp_replace_str, $recurr_event_freq_val_code );
		$end_recurr_month_sel_code  = str_replace( $temp_find_str, $temp_replace_str, $end_recurr_month_sel_code );
		$end_recurr_day_sel_code    = str_replace( $temp_find_str, $temp_replace_str, $end_recurr_day_sel_code );
		$end_recurr_year_sel_code   = str_replace( $temp_find_str, $temp_replace_str, $end_recurr_year_sel_code );

		$temp_find_str = "value='".$event_data['frequency_type']."'";
		$temp_replace_str = "value='".$event_data['frequency_type']."' selected='selected'";
		$recurr_event_freq_sel_code = str_replace( $temp_find_str, $temp_replace_str, $recurr_event_freq_sel_code );

		$temp_find_str = "value='1'";
		$temp_replace_str = "value='".$event_data['frequency']."'";
		$recurr_event_freq_val_code = str_replace( $temp_find_str, $temp_replace_str, $recurr_event_freq_val_code );

		if( $event_data['final_occ_time'] > 0 )
		{
			$temp_final_time = $event_data['final_occ_time'] + $user->timezone + $user->dst;
			$temp_find_str = "value='".gmdate('n', $temp_final_time )."'";
			$temp_replace_str = "value='".gmdate('n', $temp_final_time)."' selected='selected'";
			$end_recurr_month_sel_code = str_replace( $temp_find_str, $temp_replace_str, $end_recurr_month_sel_code );

			$temp_find_str = "value='".gmdate('j', $temp_final_time)."'";
			$temp_replace_str = "value='".gmdate('j', $temp_final_time)."' selected='selected'";
			$end_recurr_day_sel_code = str_replace( $temp_find_str, $temp_replace_str, $end_recurr_day_sel_code );

			$temp_find_str = "value='".gmdate('Y', $temp_final_time)."'";
			$temp_replace_str = "value='".gmdate('Y', $temp_final_time)."' selected='selected'";
			$end_recurr_year_sel_code = str_replace( $temp_find_str, $temp_replace_str, $end_recurr_year_sel_code );
		}
	}
}
else // we are creating a new event
{
	
	// make raid composition proposal
	$sql_array = array(
	    'SELECT'    => 'r.role_id, r.role_name, role_needed1, role_needed2 ', 
	    'FROM'      => array(
	        RP_ROLES   => 'r'
	    ),
	    'ORDER_BY'  => 'r.role_id'
	);
	$sql = $db->sql_build_query('SELECT', $sql_array);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
	    $template->assign_block_vars('raidroles', array(
	        'ROLE_ID'        => $row['role_id'],
		    'ROLE_NAME'      => $row['role_name'],
		    'ROLE_NEEDED1'    => $row['role_needed1'],
	    	'ROLE_NEEDED2'    => $row['role_needed2'],
	    ));
	}
	$db->sql_freeresult($result);
	
	//-----------------------------------------
	// month selection data
	//-----------------------------------------
	$temp_find_str = "value='".$newraid->date['month_no']."'>";
	$temp_replace_str = "value='".$newraid->date['month_no']."' selected='selected'>";
	$month_sel_code = str_replace( $temp_find_str, $temp_replace_str, $month_sel_code );

	$temp_find_str = "name='calM' id='calM'";
	$temp_replace_str = "name='calMEnd' id='calMEnd' disabled='disabled'";
	$end_month_sel_code = str_replace( $temp_find_str, $temp_replace_str, $month_sel_code );

	//-----------------------------------------
	// day selection data
	//-----------------------------------------
	$temp_find_str = "value='".$newraid->date['day']."'>";
	$temp_replace_str = "value='".$newraid->date['day']."' selected='selected'>";
	$day_sel_code = str_replace( $temp_find_str, $temp_replace_str, $day_sel_code );

	$temp_find_str = "name='calD' id='calD'";
	$temp_replace_str = "name='calDEnd' id='calDEnd' disabled='disabled'";
	$end_day_sel_code = str_replace( $temp_find_str, $temp_replace_str, $day_sel_code );


	//-----------------------------------------
	// year selection data
	//-----------------------------------------
	$temp_find_str = "value='".$newraid->date['year']."'>";
	$temp_replace_str = "value='".$newraid->date['year']."' selected='selected'>";
	$year_sel_code = str_replace( $temp_find_str, $temp_replace_str, $year_sel_code );

	$temp_find_str = "name='calY' id='calY'";
	$temp_replace_str = "name='calYEnd' id='calYEnd' disabled='disabled'";
	$end_year_sel_code = str_replace( $temp_find_str, $temp_replace_str, $year_sel_code );

	//-----------------------------------------
	// hour selection data
	//-----------------------------------------
	$temp_find_str = "id='calHr'";
	$temp_replace_str = "id='calHr' disabled='disabled'";
	$hour_sel_code = str_replace( $temp_find_str, $temp_replace_str, $hour_sel_code );

	$start_hour_code = $hour_sel_code;
	$end_hour_code = $hour_sel_code;

	$temp_find_str = "name='calHr' id='calHr'";
	$temp_replace_str = "name='calHrEnd' id='calHrEnd'";
	$end_hour_code = str_replace( $temp_find_str, $temp_replace_str, $end_hour_code );

	//-----------------------------------------
	// minute selection data
	//-----------------------------------------
	$temp_find_str = "id='calMn'";
	$temp_replace_str = "id='calMn' disabled='disabled'";
	$min_sel_code = str_replace( $temp_find_str, $temp_replace_str, $min_sel_code );

	$start_min_code = $min_sel_code;
	$end_min_code = $min_sel_code;

	$temp_find_str = "name='calMn' id='calMn'";
	$temp_replace_str = "name='calMnEnd' id='calMnEnd'";
	$end_min_code = str_replace( $temp_find_str, $temp_replace_str, $end_min_code );

}

// Build Navigation Links
$newraid->generate_forum_nav($post_data);

$s_hidden_fields = '<input type="hidden" name="calEid" value="' . $event_data['event_id'] . '" />';
$s_hidden_fields .= '<input type="hidden" name="lastclick" value="' . $current_time . '" />';

$day_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=day&amp;calD=".$newraid->date['day']."&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
$week_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=week&amp;calD=".$newraid->date['day']."&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
$month_view_url = append_sid("{$phpbb_root_path}planner.$phpEx", "view=month&amp;calD=".$newraid->date['day']."&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);

$allow_delete = false;
if( ($mode == 'edit') &&
	( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_events')) &&
	(($user->data['user_id'] == $event_data['poster_id'])|| $auth->acl_get('m_raidplanner_delete_other_users_events') ))
{
	$allow_delete = true;
}


$template->assign_vars(array(
	'L_POST_A'					=> $page_title,
	'L_MESSAGE_BODY_EXPLAIN'	=> (intval($config['max_post_chars'])) ? sprintf($user->lang['MESSAGE_BODY_EXPLAIN'], intval($config['max_post_chars'])) : '',
	'SUBJECT'					=> $event_data['event_subject'],
	'MESSAGE'					=> $event_data['event_body'],
	'MINI_POST_IMG'				=> $user->img('icon_post_target', $user->lang['POST']),
	'ERROR'						=> (sizeof($error)) ? implode('<br />', $error) : '',
	'U_CALENDAR'				=> append_sid("{$phpbb_root_path}planner.$phpEx"),
	'S_DATE_TIME_OPTS'			=> $s_date_time_opts,
	'MONTH_SEL'					=> $month_sel_code,
	'DAY_SEL'					=> $day_sel_code,
	'YEAR_SEL'					=> $year_sel_code,
	'START_HOUR_SEL'			=> $start_hour_code,
	'START_MIN_SEL'				=> $start_min_code,

	'EVENT_TYPE_SEL'			=> $e_type_sel_code,
	'EVENT_ACCESS_LEVEL_SEL'	=> $level_sel_code,
	'EVENT_GROUP_SEL'			=> $group_sel_code,

	'DAY_VIEW_URL'				=> $day_view_url,
	'WEEK_VIEW_URL'				=> $week_view_url,
	'MONTH_VIEW_URL'			=> $month_view_url,
 	'TRACK_RSVP_CHECK'			=> ($event_data['track_signups'] == 1) ? ' checked="checked"' : '',
	'S_RECURRING_OPTS'			=> $event_data['s_recurring_opts'],
	'S_UPDATE_RECURRING_OPTIONS'=> $event_data['s_update_recurring_options'],
	'RECURRING_EVENT_CHECK'		=> $recurr_event_check,
	'RECURRING_EVENT_TYPE_SEL'	=> $recurr_event_freq_sel_code,
	'RECURRING_EVENT_FREQ_IN'	=> $recurr_event_freq_val_code,
	'END_RECURR_MONTH_SEL'		=> $end_recurr_month_sel_code,
	'END_RECURR_DAY_SEL'		=> $end_recurr_day_sel_code,
	'END_RECURR_YEAR_SEL'		=> $end_recurr_year_sel_code,
	

	'S_POST_ACTION'				=> $s_action,
	'S_HIDDEN_FIELDS'			=> $s_hidden_fields, 

	//javascript alerts
	'LA_ALERT_OLDBROWSER' 		=> $user->lang['ALERT_OLDBROWSER'],
	//'UA_AJAXHANDLER1'		  	=> append_sid($phpbb_root_path . 'styles/' . $user->theme['template_path'] . '/template/planner/plannerajax.'. $phpEx),
	'UA_AJAXHANDLER1'		  	=> 'plannerajax.'. $phpEx
)
);

// HTML, BBCode, Smilies, Images and Flash status
$bbcode_status	= ($config['allow_bbcode']) ? true : false;
$img_status		= ($bbcode_status) ? true : false;
$flash_status	= ($bbcode_status && $config['allow_post_flash']) ? true : false;
$url_status		= ($config['allow_post_links']) ? true : false;
$smilies_status	= ($bbcode_status && $config['allow_smilies']) ? true : false;

if ($smilies_status)
{
	// Generate smiley listing
	$newraid->generate_calendar_smilies('inline');
}
$quote_status	= false;

$template->assign_vars(array(
	'BBCODE_STATUS'				=> ($bbcode_status) ? 
		sprintf($user->lang['BBCODE_IS_ON'], '<a href="' . append_sid("{$phpbb_root_path}faq.$phpEx", 'mode=bbcode') . '">', '</a>') : 
		sprintf($user->lang['BBCODE_IS_OFF'], '<a href="' . append_sid("{$phpbb_root_path}faq.$phpEx", 'mode=bbcode') . '">', '</a>'),
	'IMG_STATUS'				=> ($img_status) ? $user->lang['IMAGES_ARE_ON'] : $user->lang['IMAGES_ARE_OFF'],
	'FLASH_STATUS'				=> ($flash_status) ? $user->lang['FLASH_IS_ON'] : $user->lang['FLASH_IS_OFF'],
	'SMILIES_STATUS'			=> ($smilies_status) ? $user->lang['SMILIES_ARE_ON'] : $user->lang['SMILIES_ARE_OFF'],
	'URL_STATUS'				=> ($bbcode_status && $url_status) ? $user->lang['URL_IS_ON'] : $user->lang['URL_IS_OFF'],

	'S_DELETE_ALLOWED'			=> $allow_delete,
	'S_BBCODE_ALLOWED'			=> $bbcode_status,
	'S_SMILIES_ALLOWED'			=> $smilies_status,
	'S_LINKS_ALLOWED'			=> $url_status,
	'S_BBCODE_IMG'				=> $img_status,
	'S_BBCODE_URL'				=> $url_status,
	'S_BBCODE_FLASH'			=> $flash_status,
	'S_BBCODE_QUOTE'			=> $quote_status,
)
);

// Build custom bbcodes array
display_custom_bbcodes();

// Output page ...
page_header($page_title);

$template->set_filenames(array(
	'body' => 'planner/planner_post_body.html')
);

make_jumpbox(append_sid("{$phpbb_root_path}viewforum.$phpEx"));

page_footer();





?>
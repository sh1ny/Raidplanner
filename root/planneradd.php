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

// Language file (see documentation related to language files)
$user->setup('posting');
$user->add_lang ( array ('posting', 'mods/dkp_common','mods/raidplanner'  ));
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
	$event_data['event_end_time'] = 0;
	$event_data['etype_id'] = 0;
	$event_data['event_subject'] = "";
	$event_data['event_body'] = "";
	$event_data['poster_id'] = $user->data['user_id'];
	$event_data['poster_timezone'] = $user->data['user_timezone'];
	$event_data['poster_dst'] = $user->data['user_dst'];
	
	$event_data['event_day'] = "00-00-0000";
	$event_data['track_rsvps'] = 0;
	$event_data['allow_guests'] = 0;
	$event_data['rsvp_yes'] = 0;
	$event_data['rsvp_no'] = 0;
	$event_data['rsvp_maybe'] = 0;
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
$newraid->authcheck($mode, $submit, &$event_data, $event_id);
		
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
  one event, or all events... BEFORE we start querying
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
	
	gather_eventdata(&$event_data, &$newraid, $s_date_time_opts);
	
	if( $event_id > 0 )
	{
		edit_event(&$event_data, $newraid, $event_id );
	}
	else 
	{
		//pass zero event_id by reference to get it updated
		create_event(&$event_data, &$newraid, &$event_id);
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
	$user_event_end = $event_data['event_end_time'] + $user->timezone + $user->dst;

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
	$preview_track_rsvps = $event_data['track_rsvps'];
	$preview_allow_guests = $event_data['allow_guests'];

	if (!sizeof($error))
	{
		$template->assign_vars(array(
			'PREVIEW_SUBJECT'		=> $preview_subject,
			'PREVIEW_ETYPE_DISPLAY_NAME'=> $preview_etype_display_name,
			'PREVIEW_EVENT_COLOR'	=> $preview_event_color,
			'PREVIEW_EVENT_IMAGE'	=> $preview_event_image,
			'PREVIEW_MESSAGE'		=> $preview_message,
			'PREVIEW_START_DATE'	=> $user->format_date($event_data['event_start_time']),
			'PREVIEW_END_DATE'		=> $user->format_date($event_data['event_end_time']),
			'PREVIEW_POSTER'		=> $poster_url,
			'PREVIEW_INVITED'		=> $invite_list,
			
			'S_DISPLAY_PREVIEW'		=> true,
			'PREVIEW_TRACK_RSVPS'	=> $preview_track_rsvps,
			'PREVIEW_ALLOW_GUESTS'	=> $preview_allow_guests )
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

//event types 
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

	// translate event start and end time into user's timezone
	$event_start = $event_data['event_start_time'] + $user->timezone + $user->dst;
	$cancel_url = append_sid("{$phpbb_root_path}planner.$phpEx", "m=".gmdate('n', $event_start)."&amp;y=".gmdate('Y', $event_start) );

	//-----------------------------------------
	// month selection data
	//-----------------------------------------
	$temp_find_str = "value='".gmdate('n', $event_end)."'";
	$temp_replace_str = "value='".gmdate('n', $event_end)."' selected='selected'";
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
	$temp_find_str = "value='".gmdate('j', $event_end)."'";
	$temp_replace_str = "value='".gmdate('j', $event_end)."' selected='selected'";
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
	$temp_find_str = "value='".gmdate('Y', $event_end)."'";
	$temp_replace_str = "value='".gmdate('Y', $event_end)."' selected='selected'";
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
	$temp_find_str = "value='".gmdate('G', $event_end)."'";
	$temp_replace_str = "value='".gmdate('G', $event_end)."' selected='selected'";
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
	$temp_find_str = "value='".gmdate('i', $event_end)."'";
	$temp_replace_str = "value='".gmdate('i', $event_end)."' selected='selected'";
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

	'S_TRACK_ATTENDANCE'		=> $event_data['s_track_attendance'],
	'TRACK_ATTENDANCE_CHECK'		=> $track_attendance_check,
	'TRACK_ATTENDANCE_CHECK_HIDDEN'	=> $track_attendance_check_hidden,


	'S_RECURRING_OPTS'			=> $event_data['s_recurring_opts'],
	'S_UPDATE_RECURRING_OPTIONS'=> $event_data['s_update_recurring_options'],
	'RECURRING_EVENT_CHECK'		=> $recurr_event_check,
	'RECURRING_EVENT_TYPE_SEL'	=> $recurr_event_freq_sel_code,
	'RECURRING_EVENT_FREQ_IN'	=> $recurr_event_freq_val_code,
	'END_RECURR_MONTH_SEL'		=> $end_recurr_month_sel_code,
	'END_RECURR_DAY_SEL'		=> $end_recurr_day_sel_code,
	'END_RECURR_YEAR_SEL'		=> $end_recurr_year_sel_code,

	'S_POST_ACTION'			=> $s_action,
	'S_HIDDEN_FIELDS'		=> $s_hidden_fields)
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






/***
 * function to build event array used for posting new event.
 * error checking is done
 * 
 * @param : &event_data byref
 * @returns : $event_data or trigger_error
 * 
 */
function gather_eventdata( $event_data, $newraid, $s_date_time_opts)
{
	global $user, $config; 
	$error = array();
	
	$event_data['event_subject']= utf8_normalize_nfc(request_var('subject', '', true));
	$event_data['event_body']	= utf8_normalize_nfc(request_var('message', '', true));
	$event_data['etype_id']		= request_var('calEType', 0);
	$event_data['group_id'] = 0;
	$event_data['group_id_list'] = ",";
	$group_id_array = request_var('calGroupId', array(0));
	$num_group_ids = sizeof( $group_id_array );
    if( $num_group_ids == 1 )
    {
		$event_data['group_id'] = $group_id_array[0];

    }
	else if( $num_group_ids > 1 )
	{
		$group_index = 0;
		for( $group_index = 0; $group_index < $num_group_ids; $group_index++ )
		{
		    if( $group_id_array[$group_index] == "" )
		    {
		    	continue;
		    }
		    $event_data['group_id_list'] .= $group_id_array[$group_index] . ",";
		}
	}
	$event_data['event_access_level']	= request_var('calELevel', 0);
	if( $event_data['event_access_level'] == 1 && $num_group_ids < 1 )
	{
		$error[] = $user->lang['NO_GROUP_SELECTED'];
	}

    /*------------------------------------------------------------------
      Begin to find start/end times
      NOTE: if s_date_time_opts is false we are editing all events and
            there will not be any data to get
    -------------------------------------------------------------------*/
	
	if( $s_date_time_opts )
	{
		$start_hr = request_var('calHr', 0);
		$start_mn = request_var('calMn', 0);
		$event_data['event_start_time'] = gmmktime($start_hr, $start_mn, 0, $newraid->date['month_no'], $newraid->date['day'], $newraid->date['year'] ) - $user->timezone - $user->dst;
	}

	// DNSBL check
	if ($config['check_dnsbl'] )
	{
		if (($dnsbl = $user->check_dnsbl('post')) !== false)
		{
			$error[] = sprintf($user->lang['IP_BLACKLISTED'], $user->ip, $dnsbl[1]);
		}
	}

    /*------------------------------------------------------------------
      Check options for recurring events
    -------------------------------------------------------------------*/
	if( request_var('calIsRecurr', '') == "ON" )
	{
	    $event_data['is_recurr'] = 1;
		$event_data['frequency_type'] = request_var('calRFrqT', 1);
		switch ($event_data['frequency_type'])
		{
			case 2:
			case 4:
				$event_data['week_index'] = $newraid->find_week_index( $event_data['event_start_time'], true, false, $event_data['first_day_of_week'] );
				break;
			default:
				$event_data['week_index'] = 0;
				break;
		}


		$event_data['frequency'] = request_var('calRFrq', 1);
		if( $event_data['frequency'] < 1 )
		{
			$error[] = $user->lang['FREQUENCEY_LESS_THAN_1'];
		}
		$final_occ_month = request_var('calRMEnd', 0);
		$final_occ_day = request_var('calRDEnd', 0);
		$final_occ_year = request_var('calRYEnd', 0);
		if( $final_occ_month == 0 || $final_occ_month == 0 || $final_occ_month == 0 )
		{
			$event_data['final_occ_time'] = 0;
		}
		else
		{
			// we want to use the last minute of their selected day so we will populate events
			// on their last selected day, but not any day after
			$event_data['final_occ_time'] = gmmktime(23, 59, 0, $final_occ_month, $final_occ_day, $final_occ_year ) - $user->timezone - $user->dst;
			
			if( $event_data['final_occ_time'] < $event_data['event_start_time'] )
			{
				$error[] = $user->lang['NEGATIVE_LENGTH_EVENT'];
			}
			else if( $event_data['final_occ_time'] == $event_data['event_start_time'] )
			{
				$error[] = $user->lang['ZERO_LENGTH_EVENT'];
			}
		}

	}
	else
	{
	    $event_data['is_recurr'] = 0;
	}
	
	if (!sizeof($error))
	{
		return $event_data;
	}
	
}


function edit_event($event_data, $newraid, $event_id)
{
	global $db;
	
	$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
	$allow_bbcode = $allow_urls = $allow_smilies = true;
	generate_text_for_storage($event_data['event_body'], $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
	
	/*---------------------------------------------
	   EDIT
	---------------------------------------------*/
	if( $event_id > 0 )
	{
		// we are only editing the one event
		if( $s_date_time_opts )
		{
			$sql = 'UPDATE ' . RP_EVENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', array(
					'etype_id'				=> (int) $event_data['etype_id'],
					'sort_timestamp'		=> (int) $event_data['event_start_time'],
					'event_start_time'		=> (int) $event_data['event_start_time'],
					'event_day'				=> (string) $event_data['event_day'],
					'event_subject'			=> (string) $event_data['event_subject'],
					'event_body'			=> (string) $event_data['event_body'],
					'poster_id'				=> (int) $event_data['poster_id'],
					'event_access_level'	=> (int) $event_data['event_access_level'],
					'group_id'				=> (int) $event_data['group_id'],
					'group_id_list'			=> (string) $event_data['group_id_list'],
					'bbcode_uid'			=> (string) $uid,
					'bbcode_bitfield'		=> (string) $bitfield,
					'enable_bbcode'			=> $allow_bbcode,
					'enable_magic_url'		=> (int) $allow_urls,
					'enable_smilies'		=> (int) $allow_smilies,
					
					
					)) . "
				WHERE event_id = $event_id";
			$db->sql_query($sql);
		}
		// we are editing all occurrences of this event...
		else
		{
			$recurr_id = $event_data['recurr_id'];
			//start by updating the recurring events table
			$sql = 'UPDATE ' . RP_RECURRING_EVENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', array(
					'etype_id'				=> (int) $event_data['etype_id'],
					'event_subject'			=> (string) $event_data['event_subject'],
					'event_body'			=> (string) $event_data['event_body'],
					'event_access_level'	=> (int) $event_data['event_access_level'],
					'group_id'				=> (int) $event_data['group_id'],
					'group_id_list'			=> (string) $event_data['group_id_list'],
					'enable_bbcode'			=> (int) $allow_bbcode,
					'enable_smilies'		=> (int) $allow_smilies,
					'enable_magic_url'		=> (int) $allow_urls,
					'bbcode_bitfield'		=> (string) $bitfield,
					'bbcode_uid'			=> (string) $uid,
					
					
					)) . "
				WHERE recurr_id = $recurr_id";
			$db->sql_query($sql);
	
			// now update all events of this occurence id
			$sql = 'UPDATE ' . RP_EVENTS_TABLE . '
				SET ' . $db->sql_build_array('UPDATE', array(
					'etype_id'				=> (int) $event_data['etype_id'],
					'event_subject'			=> (string) $event_data['event_subject'],
					'event_body'			=> (string) $event_data['event_body'],
					'event_access_level'	=> (int) $event_data['event_access_level'],
					'group_id'				=> (int) $event_data['group_id'],
					'group_id_list'			=> (string) $event_data['group_id_list'],
					'bbcode_uid'			=> (string) $uid,
					'bbcode_bitfield'		=> (string) $bitfield,
					'enable_bbcode'			=> (int) $allow_bbcode,
					'enable_magic_url'		=> (int) $allow_urls,
					'enable_smilies'		=> (int) $allow_smilies,
					
					
					)) . "
				WHERE recurr_id = $recurr_id";
			$db->sql_query($sql);
		}
		
		$raidplanner= new displayplanner;
		$raidplanner->calendar_add_or_update_reply($event_id, false );
		
	}
	
}


function create_event($event_data, $newraid, $event_id)
{
	global $db;
	
	$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
	$allow_bbcode = $allow_urls = $allow_smilies = true;
	generate_text_for_storage($event_data['event_body'], $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
	$event_track_attendance = 0;
	$recurr_id = 0;
	/*----------------------------------------------------
	   RECURRING EVENT: add it to the recurring
	   event table and begin populating the events
	----------------------------------------------------*/
	if( $event_data['is_recurr'] == 1 )
	{
		$event_frequency_type = $event_data['frequency_type'];
		$event_frequency = $event_data['frequency'];
		$event_week_index = $event_data['week_index'];
		$event_final_occ_time = $event_data['final_occ_time'];
		$event_duration = 0;
		$event_duration = $event_end_date - $event_data['event_start_time'];
		$poster_timezone = $event_data['poster_timezone'];
		$poster_dst = $event_data['poster_dst'];

		$sql = 'INSERT INTO ' . RP_RECURRING_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'etype_id'				=> (int) $event_data['etype_id'],
				'frequency'				=> (int) $event_frequency,
				'frequency_type'		=> (int) $event_frequency_type,
				'first_occ_time'		=> (int) $event_data['event_start_time'],
				'final_occ_time'		=> (int) $event_final_occ_time,
				'event_duration'		=> (int) $event_duration,
				'week_index'			=> (int) $event_week_index,
				'first_day_of_week'		=> (int) $event_data['first_day_of_week'],
				'last_calc_time'		=> (int) 0,
				'next_calc_time'		=> (int) $event_data['event_start_time'],
				'event_subject'			=> (string) $event_data['event_subject'],
				'event_body'			=> (string) $event_data['event_body'],
				'poster_id'				=> (int) $event_data['poster_id'],
				'poster_timezone'		=> (int) $poster_timezone,
				'poster_dst'			=> (int) $poster_dst,
				'event_access_level'	=> (int) $event_data['event_access_level'],
				'group_id'				=> (int) $event_data['group_id'],
				'group_id_list'			=> (string) $event_data['group_id_list'],
				'bbcode_uid'			=> (string) $uid,
				'bbcode_bitfield'		=> (string) $bitfield,
				'enable_bbcode'			=> (int) $allow_bbcode,
				'enable_magic_url'		=> (int) $allow_urls,
				'enable_smilies'		=> (int) $allow_smilies,
				'track_rsvps'			=> (int) $event_track_attendance,
				
				)
			);
		$db->sql_query($sql);
		$recurr_id = $db->sql_nextid();

		$event_id = $newraid->populate_calendar( $recurr_id );

	}
	/*----------------------------------------------------
	   NON-RECURRING EVENT: add it to the event table
	----------------------------------------------------*/
	else
	{
		$sql = 'INSERT INTO ' . RP_EVENTS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'etype_id'				=> (int) $event_data['etype_id'],
				'sort_timestamp'		=> (int) $event_data['event_start_time'],
				'event_start_time'		=> (int) $event_data['event_start_time'],
				/*'event_end_time'		=> (int) $event_end_date,*/
				'event_day'				=> (string) $event_data['event_day'],
				'event_subject'			=> (string) $event_data['event_subject'],
				'event_body'			=> (string) $event_data['event_body'],
				'poster_id'				=> (int) $event_data['poster_id'],
				'event_access_level'	=> (int) $event_data['event_access_level'],
				'group_id'				=> (int) $event_data['group_id'],
				'group_id_list'			=> (string) $event_data['group_id_list'],
				'bbcode_uid'			=> (string) $uid,
				'bbcode_bitfield'		=> (string) $bitfield,
				'enable_bbcode'			=> (int) $allow_bbcode,
				'enable_magic_url'		=> (int) $allow_urls,
				'enable_smilies'		=> (int) $allow_smilies,
				'track_rsvps'			=> (int) $event_track_attendance,
				'recurr_id'				=> (int) $recurr_id,
				)
			);
		$db->sql_query($sql);
		$event_id = $db->sql_nextid();
	}
	$newraid->calendar_notify_new_event( $event_id );

	
	
}


?>
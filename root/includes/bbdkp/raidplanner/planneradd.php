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
if ( !defined('IN_PHPBB') OR !defined('IN_BBDKP') )
{
	exit;
}

$user->add_lang ( array ('posting','mods/raidplanner'  ));
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);

$user->setup('posting');
$current_time = $user->time_now;

if (!class_exists('raidplanner_population'))
{
	include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_population.' . $phpEx);
}
$newraid= new raidplanner_population();

$error = array();

// get the number of events from bbDKP.. if none defined then throw error
if( $newraid->raid_plan_count < 1 )
{
	trigger_error('NO_EVENT_TYPES');
}

/*-----------------------------------
  begin raidplan_data initialization
-------------------------------------*/
$raidplan_id	= request_var('calEid', 0);
$raidplan_data = array();
if( $raidplan_id !== 0 )
{
	if (!class_exists('raidplans'))
	{
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplans.' . $phpEx);
	}
	$raidplans = new raidplans();
	$raidplans->get_raidplan_data( $raidplan_id, $raidplan_data );
}
else
{
	if( $auth->acl_get('u_raidplanner_create_recurring_raidplans') )
	{
		$raidplan_data['s_recurring_opts'] = false;
	}
	$raidplan_data['raidplan_id'] = 0;

	// new field
	$raidplan_data['raidplan_invite_time'] = request_var('calM',0);
	$raidplan_data['raidplan_start_time'] = 0;
	$raidplan_data['etype_id'] = 0;
	$raidplan_data['raidplan_subject'] = "";
	$raidplan_data['raidplan_body'] = "";
	$raidplan_data['poster_id'] = $user->data['user_id'];
	$raidplan_data['poster_timezone'] = $user->data['user_timezone'];
	$raidplan_data['poster_dst'] = $user->data['user_dst'];
	
	// set raidplan tracking to 1 by default
	$raidplan_data['track_signups'] = 1;
	$raidplan_data['raidplan_day'] = "00-00-0000";
	$raidplan_data['signup_yes'] = 0;
	$raidplan_data['signup_no'] = 0;
	$raidplan_data['signup_maybe'] = 0;
	$raidplan_data['recurr_id'] = 0;
	$raidplan_data['is_recurr'] = 0;
	$raidplan_data['frequency_type'] = 0;
	$raidplan_data['frequency'] = 0;
	$raidplan_data['final_occ_time'] = 0;
	$raidplan_data['week_index'] = 0;
	$raidplan_data['first_day_of_week'] = $config["rp_first_day_of_week"];

}

// mode: addraid, edit, delete, or smilies
$submit		= (isset($_POST['addraid'])) ? true : false;
$cancel		= (isset($_POST['cancel'])) ? true : false;
$delete		= (isset($_POST['delete'])) ? true : false;

$mode		= (isset($_GET['mode'])) ? true : false;
$mode 		= ($mode == true) ? request_var('mode', '') : '';

$s_date_time_opts = true;
$page_title = $user->lang['CALENDAR_POST_RAIDPLAN'];

if($submit && $mode !='edit' && $mode != 'delete')
{
		$newraid->authcheck('addraid', $submit, $raidplan_data, $raidplan_id);
		$page_title = $user->lang['CALENDAR_POST_RAIDPLAN'];
		//complete the raidplan array by calling the gather function
		$newraid->gather_raiddata($raidplan_data, $newraid, $s_date_time_opts);
		
		// we have all data, now go create the raidplan
		// pass zero raidplan_id by reference to get it updated
		$newraid->create_raidplan($raidplan_data, $newraid, $raidplan_id);
		
		$main_calendar_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
		$view_raidplan_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$raidplan_id."&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
		
		// now redirect to the new newly created raidplan
		meta_refresh(3, $view_raidplan_url);
		$message = $user->lang['EVENT_STORED'] . '<br /><br />' . sprintf($user->lang['VIEW_RAIDPLAN'], '<a href="' . $view_raidplan_url . '">', '</a>');
	
		$message .= '<br /><br />' . sprintf($user->lang['RETURN_CALENDAR'], '<a href="' . $main_calendar_url . '">', '</a>');
		trigger_error($message, E_USER_NOTICE);
	
}

if(($delete || $mode =='delete') && $raidplan_id > 0)
{
		$newraid->authcheck('delete', $submit, $raidplan_data, $raidplan_id);
		$page_title = $user->lang['CALENDAR_EDIT_RAIDPLAN'];
	    $delete_all = request_var('calDelAll', 0);
	    if( $delete_all == 0 )
	    {
			$newraid->handle_raidplan_delete($raidplan_id, $raidplan_data);
		}
		else
		{
			$newraid->handle_raidplan_delete_all($raidplan_id, $raidplan_data);
		}
		exit;
}

if($mode =='edit' && ($raidplan_id > 0))
{		
		
		$newraid->authcheck($mode, $submit, $raidplan_data, $raidplan_id);
		$page_title = $user->lang['CALENDAR_EDIT_RAIDPLAN'];
	    
	    $edit_all = request_var('calEditAll', 0);
	    //if editing recurring plans then don't add raid times
	    if( $edit_all != 0)
	    {
			$s_date_time_opts = false;
	    }
				
		// Decode bbcodes text for message editing
		decode_message($raidplan_data['raidplan_body'], $raidplan_data['bbcode_uid']);
	
		$main_calendar_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
		$view_raidplan_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$raidplan_id."&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
		
		if ($submit)
		{
			//assemble request_var input
			$newraid->gather_raiddata($raidplan_data, $newraid, $s_date_time_opts);
			$newraid->edit_raidplan($raidplan_data, $newraid, $raidplan_id, $s_date_time_opts );
			
			$message = $user->lang['EVENT_EDITED'] . '<br /><br />' . sprintf($user->lang['VIEW_RAIDPLAN'], '<a href="' . $view_raidplan_url . '">', '</a>');
			$message .= '<br /><br />' . sprintf($user->lang['RETURN_CALENDAR'], '<a href="' . $main_calendar_url . '">', '</a>');
			trigger_error($message, E_USER_NOTICE);
			
		}
}

 /** 
 * build Raid posting form
 * 
 **/

// action URL, include session_id for security purpose
$s_action = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=$mode", true, $user->session_id);

$temp_find_str = "<br />";
$temp_replace_str = "\n";
$raidplan_data['raidplan_body'] = str_replace( $temp_find_str, $temp_replace_str, $raidplan_data['raidplan_body'] );

//-----------------------------------------
// populate form options...
//-----------------------------------------

//count events from bbDKP, put them in a pulldown...
$e_type_sel_code  = "";
for( $i = 0; $i < $newraid->raid_plan_count; $i++)
{
	$e_type_sel_code .= '<option value="' . $newraid->raid_plan_ids[$i] . '">'. $newraid->raid_plan_names[$i].'</option>';
}

// raidplan acces level
$level_sel_code ="";

// Find what groups this user is a member of and add them to the list of groups to invite
$group_sel_code = $newraid->posting_generate_group_selection_code( $raidplan_data['poster_id'] );
if( $auth->acl_get('u_raidplanner_create_public_raidplans') )
{
	$level_sel_code .= '<option value="2">'.$user->lang['EVENT_ACCESS_LEVEL_PUBLIC'].'</option>';
}
if( $auth->acl_get('u_raidplanner_create_group_raidplans') )
{
	$level_sel_code .= '<option value="1">'.$user->lang['EVENT_ACCESS_LEVEL_GROUP'].'</option>';
}
if( $auth->acl_get('u_raidplanner_create_private_raidplans') )
{
	$level_sel_code .= '<option value="0">'.$user->lang['EVENT_ACCESS_LEVEL_PERSONAL'].'</option>';
}

/**
 * Event recurrance
 */
$recurr_raidplan_check = "";
$recurr_raidplan_freq_sel_code = "";
$recurr_raidplan_freq_val_code = "";
$end_recurr_month_sel_code = "";
$end_recurr_day_sel_code = "";
$end_recurr_year_sel_code = "";

/*
if( $raidplan_data['s_recurring_opts'] )
{
	$recurr_raidplan_check = "<input type='checkbox' name='calIsRecurr' value='ON' onclick='update_recurr_state();update_recurring_options();' />";
	$recurr_raidplan_freq_sel_code  = "<select name='calRFrqT' id='calRFrqT' disabled='disabled'>\n";
	$recurr_raidplan_freq_sel_code .= "<option value='1'>".$user->lang['RECURRING_EVENT_CASE_1']."</option>\n";
	$recurr_raidplan_freq_sel_code .= "<option value='2'>".$user->lang['RECURRING_EVENT_CASE_2']."</option>\n";
	$recurr_raidplan_freq_sel_code .= "<option value='3'>".$user->lang['RECURRING_EVENT_CASE_3']."</option>\n";
	$recurr_raidplan_freq_sel_code .= "<option value='4'>".$user->lang['RECURRING_EVENT_CASE_4']."</option>\n";
	$recurr_raidplan_freq_sel_code .= "<option value='5'>".$user->lang['RECURRING_EVENT_CASE_5']."</option>\n";
	$recurr_raidplan_freq_sel_code .= "<option value='6'>".$user->lang['RECURRING_EVENT_CASE_6']."</option>\n";
	$recurr_raidplan_freq_sel_code .= "</select>\n";
	$recurr_raidplan_freq_val_code = "<input type='number' name='calRFrq' id='calRFrq' value='1' onchange='update_recurring_options()' disabled='disabled' />";

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
 */ 
$cancel_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;m=".$newraid->date['month_no']."&amp;y=".$newraid->date['year']);


// Raid date
$month_sel_code  = " ";
for( $i = 1; $i <= 12; $i++ )
{
	$selected = ($newraid->date['month_no']==$i) ?   ' selected="selected"': '';
	$month_sel_code .= '<option value="'.$i.'"' . $selected . '>'.$user->lang['datetime'][$newraid->month_names[$i]].'</option>';
}

$day_sel_code= "";
for( $i = 1; $i <= 31; $i++ )
{
	$selected = ($newraid->date['day']==$i) ?  ' selected="selected"': '';
	$day_sel_code .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
}

$year_sel_code  = " ";
for( $i = $newraid->date['year']; $i < ($newraid->date['year']+5); $i++ )
{
	$selected = ($newraid->date['year'] == $i) ?  ' selected="selected"': '';
	$year_sel_code .= '<option value="'.$i.'"'.$selected. '>'.$i.'</option>';
}

// Raid invite time
$hour_mode = $config['rp_hour_mode'];
$presetinvhour = intval($config['rp_default_invite_time'] / 60);
$hour_invite_selcode = "";
if( $hour_mode == 12 )
{
	for( $i = 0; $i < 24; $i++ )
	{
		$selected = ($i == $presetinvhour ) ? ' selected="selected"' : '';
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
		$hour_invite_selcode .= '<option value="'.$i.'"'.$selected.'>'.$mod_12.' '.$am_pm.'</option>';
	}
}
else
{
	for( $i = 0; $i < 24; $i++ )
	{
		$selected = ($i == $presetinvhour) ? ' selected="selected"' : '';
		$hour_invite_selcode .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
	}
}
$min_invite_sel_code = "";
$presetinvmin = $config['rp_default_invite_time'] - ($presetinvhour * 60) ;
for( $i = 0; $i < 59; $i++ )
{
	$selected = ($i == $presetinvmin ) ? ' selected="selected"' : '';
	$min_invite_sel_code .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
}

// Raid start time
$hour_start_selcode = "";
$presetstarthour = intval($config['rp_default_start_time'] / 60);
if( $hour_mode == 12 )
{
	for( $i = 0; $i < 24; $i++ )
	{
		$selected = ($i == $presetstarthour) ? ' selected="selected"' : '';
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
		$hour_start_selcode .= '<option value="'.$i.'"'.$selected.'>'.$mod_12.' '.$am_pm.'</option>';
	}
}
else
{
	for( $i = 0; $i < 24; $i++ )
	{
		$selected = ($i == $presetstarthour) ? ' selected="selected"' : '';
		$hour_start_selcode .= '<option value="'.$i.'">'.$i.'</option>';
	}
}

$min_start_sel_code = "";
$presetstartmin = $config['rp_default_start_time'] - ($presetstarthour * 60) ;
for( $i = 0; $i < 59; $i++ )
{
	$selected = ($i == $presetstartmin ) ? ' selected="selected"' : '';
	$min_start_sel_code .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
}

// check to see if we're viewing an existing raidplan
if( sizeof($error) || $raidplan_id > 0)
{
	
	// get profile for this raid
	$sql_array = array(
		    'SELECT'    => 'r.role_id, r.role_name, er.role_needed ', 
		    'FROM'      => array(
		        RP_ROLES   => 'r'
		    ),
		    'LEFT_JOIN' => array(
		        array(
		            'FROM'  => array( RP_RAIDPLAN_ROLES  => 'er'),
		            'ON'    => 'r.role_id = er.role_id AND er.raidplan_id = ' . $raidplan_id  
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
	    	'ROLE_NEEDED'    => $row['role_needed'],
	    ));
	}
	$db->sql_freeresult($result);
	
	// translate raidplan start and end time into user's timezone
	$raidplan_start = $raidplan_data['raidplan_start_time'] + $user->timezone + $user->dst;
	$cancel_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;m=".gmdate('n', $raidplan_start)."&amp;y=".gmdate('Y', $raidplan_start) );

	if( $raidplan_data['group_id'] != 0 )
	{
		$temp_find_str = "value='".$raidplan_data['group_id']."'";
		$temp_replace_str = "value='".$raidplan_data['group_id']."' selected='selected'";
		$group_sel_code = str_replace( $temp_find_str, $temp_replace_str, $group_sel_code );
	}
	else
	{
		$group_list = explode( ',', $raidplan_data['group_id_list'] );
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

	if( $raidplan_data['raidplan_access_level'] == 1 )
	{
		$temp_find_str = "disabled='disabled'";
		$temp_replace_str = "";
		$group_sel_code = str_replace( $temp_find_str, $temp_replace_str, $group_sel_code );
	}

	//-----------------------------------------
	// recurring raidplans
	//-----------------------------------------
	/*
	if( $raidplan_data['is_recurr'] == 1 )
	{
		$temp_find_str = "value='ON'";
		$temp_replace_str = "value='ON' checked='checked'";
		$recurr_raidplan_check = str_replace( $temp_find_str, $temp_replace_str, $recurr_raidplan_check );

		$temp_find_str = "disabled='disabled'";
		$temp_replace_str = "";
		$recurr_raidplan_freq_sel_code = str_replace( $temp_find_str, $temp_replace_str, $recurr_raidplan_freq_sel_code );
		$recurr_raidplan_freq_val_code = str_replace( $temp_find_str, $temp_replace_str, $recurr_raidplan_freq_val_code );
		$end_recurr_month_sel_code  = str_replace( $temp_find_str, $temp_replace_str, $end_recurr_month_sel_code );
		$end_recurr_day_sel_code    = str_replace( $temp_find_str, $temp_replace_str, $end_recurr_day_sel_code );
		$end_recurr_year_sel_code   = str_replace( $temp_find_str, $temp_replace_str, $end_recurr_year_sel_code );

		$temp_find_str = "value='".$raidplan_data['frequency_type']."'";
		$temp_replace_str = "value='".$raidplan_data['frequency_type']."' selected='selected'";
		$recurr_raidplan_freq_sel_code = str_replace( $temp_find_str, $temp_replace_str, $recurr_raidplan_freq_sel_code );

		$temp_find_str = "value='1'";
		$temp_replace_str = "value='".$raidplan_data['frequency']."'";
		$recurr_raidplan_freq_val_code = str_replace( $temp_find_str, $temp_replace_str, $recurr_raidplan_freq_val_code );

		if( $raidplan_data['final_occ_time'] > 0 )
		{
			$temp_final_time = $raidplan_data['final_occ_time'] + $user->timezone + $user->dst;
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
	*/
}
else //  new raid
{
	
	// make raid composition proposal, always choose primary role first
	$sql_array = array(
	    'SELECT'    => 'r.role_id, r.role_name, role_needed1 ', 
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
	    	'ROLE_NEEDED'    => $row['role_needed1'],
	    ));
	}
	$db->sql_freeresult($result);
	
}


$s_hidden_fields = '<input type="hidden" name="calEid" value="' . $raidplan_data['raidplan_id'] . '" />';
$s_hidden_fields .= '<input type="hidden" name="lastclick" value="' . $current_time . '" />';

$day_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$newraid->date['day']."&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
$week_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$newraid->date['day']."&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);
$month_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$newraid->date['day']."&amp;calM=".$newraid->date['month_no']."&amp;calY=".$newraid->date['year']);

$allow_delete = false;
if( ($mode == 'edit') &&
	( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_raidplans')) &&
	(($user->data['user_id'] == $raidplan_data['poster_id'])|| $auth->acl_get('m_raidplanner_delete_other_users_raidplans') ))
{
	$allow_delete = true;
}


$template->assign_vars(array(
	'L_POST_A'					=> $page_title,
	'L_MESSAGE_BODY_EXPLAIN'	=> (intval($config['max_post_chars'])) ? sprintf($user->lang['MESSAGE_BODY_EXPLAIN'], intval($config['max_post_chars'])) : '',
	'SUBJECT'					=> $raidplan_data['raidplan_subject'],
	'MESSAGE'					=> $raidplan_data['raidplan_body'],
	'MINI_POST_IMG'				=> $user->img('icon_post_target', $user->lang['POST']),
	'ERROR'						=> (sizeof($error)) ? implode('<br />', $error) : '',
	'U_CALENDAR'				=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner"),
	'S_DATE_TIME_OPTS'			=> $s_date_time_opts,
	'MONTH_SEL'					=> $month_sel_code,
	'DAY_SEL'					=> $day_sel_code,
	'YEAR_SEL'					=> $year_sel_code,

	'INVITE_HOUR_SEL'			=> $hour_invite_selcode, 
	'INVITE_MIN_SEL'			=> $min_invite_sel_code, 

	'START_HOUR_SEL'			=> $hour_start_selcode,
	'START_MIN_SEL'				=> $min_start_sel_code,

	'EVENT_TYPE_SEL'			=> $e_type_sel_code,
	'EVENT_ACCESS_LEVEL_SEL'	=> $level_sel_code,
	'EVENT_GROUP_SEL'			=> $group_sel_code,

	'DAY_VIEW_URL'				=> $day_view_url,
	'WEEK_VIEW_URL'				=> $week_view_url,
	'MONTH_VIEW_URL'			=> $month_view_url,
 	'TRACK_RSVP_CHECK'			=> ($raidplan_data['track_signups'] == 1) ? ' checked="checked"' : '',
	//'S_RECURRING_OPTS'			=> $raidplan_data['s_recurring_opts'],
	//'S_UPDATE_RECURRING_OPTIONS'=> $raidplan_data['s_update_recurring_options'],
	'RECURRING_EVENT_CHECK'		=> $recurr_raidplan_check,
	'RECURRING_EVENT_TYPE_SEL'	=> $recurr_raidplan_freq_sel_code,
	'RECURRING_EVENT_FREQ_IN'	=> $recurr_raidplan_freq_val_code,
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
	'S_PLANNER_ADD'				=> true,
)
);

// Build custom bbcodes array
display_custom_bbcodes();

// Output page ...
page_header($page_title);




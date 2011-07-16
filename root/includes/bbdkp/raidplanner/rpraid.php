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
 * implements a raid view
 *
 */
class rpraid extends calendar
{
	
	/**
	 * 
	 */
	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * @see calendar::display()
	 *
	 */
	public function display()
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
		
		$raidplan_id = request_var('calEid', 0); 
		
		if( $raidplan_id == 0)
		{
			return;
		}
		
		// @todo provide different layout depending on event type
		$calEType = request_var('calEType', 0);
		if( $calEType == 0 )
		{
			$etype_url_opts=  "";
		}
		else 
		{
			$etype_url_opts =  "&amp;calEType=".$calEType;
		}
		
		$raidplan_display_name = "";
		$raidplan_color = "";
		$raidplan_image = "";
		$all_day = 1;
		$start_date_txt = "";
		$end_date_txt = "";
		$subject="";
		$message="";

		$back_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;calD=".$this->date['day']."&amp;calM=".
				$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts );
		
		$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . ' WHERE raidplan_id = '. (int) $raidplan_id;
		$result = $db->sql_query($sql);
		
		// get raiddata into one recordset 
		$raidplan_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if( !$raidplan_data )
		{
			trigger_error( 'INVALID_RAIDPLAN' );
		}

		// check if it is a private appointment
		if( !$this->_is_user_authorized_to_view_raidplan($user->data['user_id'], $raidplan_data))
		{
			trigger_error( 'PRIVATE_RAIDPLAN' );
		}
		
		if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
		{
			$calWatchE = request_var( 'calWatchE', 2 );
			if (!class_exists('calendar_watch'))
			{
				include($phpbb_root_path . 'includes/bbdkp/raidplanner/calendar_watch.' . $phpEx);
			}
	
			$watchclass = new calendar_watch();
			if( $calWatchE < 2 )
			{
				// starts watching
				$watchclass->calendar_watch_raidplan( $raidplan_id, $calWatchE );
			}
			else
			{
				//mark that user re-visited raidplan
				$watchclass->calendar_mark_user_read_raidplan( $raidplan_id, $user->data['user_id'] );
			}
		}

		$invite_date_txt = $user->format_date($raidplan_data['raidplan_invite_time'], $config['rp_date_time_format'], true);
		$start_date_txt = $user->format_date($raidplan_data['raidplan_start_time'], $config['rp_date_time_format'], true);
		$end_date_txt = $user->format_date($raidplan_data['raidplan_end_time'], $config['rp_date_time_format'], true);
		
		$raidplan_display_name = $this->raid_plan_displaynames[$raidplan_data['etype_id']];
		$raidplan_color = $this->raid_plan_colors[$raidplan_data['etype_id']];
		$raidplan_image = $this->raid_plan_images[$raidplan_data['etype_id']];
		
		$raidplan_body = $raidplan_data['raidplan_body'];
		$subject = censor_text($raidplan_data['raidplan_subject']);
		
		$raidplan_data['bbcode_options'] = (($raidplan_data['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +   
		 (($raidplan_data['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) +     (($raidplan_data['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
		
		$message = generate_text_for_display($raidplan_body, $raidplan_data['bbcode_uid'], $raidplan_data['bbcode_bitfield'], $raidplan_data['bbcode_options']);
		
		// translate raidplan start and end time into user's timezone
		$raidplan_invite = $raidplan_data['raidplan_invite_time'] + $user->timezone + $user->dst;
		$raidplan_start = $raidplan_data['raidplan_start_time'] + $user->timezone + $user->dst;
		$all_day = 0;
		$this->date['day'] = gmdate("d", $raidplan_start);
		$this->date['month_no'] = gmdate("n", $raidplan_start);
		$this->date['year']	=	gmdate('Y', $raidplan_start);
			
		$back_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;calD=".$this->date['day'].
			"&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts );

		$poster_url = '';
		$invite_list = '';
		
		/**
		* get invited groups
		*  
		**/
		if (!class_exists('raidplans'))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplans.' . $phpEx);
		}

		$raidplans = new raidplans();
		$raidplans->get_raidplan_invites($raidplan_data, $poster_url, $invite_list );

		$edit_url = "";
		$edit_all_url = "";
		if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_raidplans') &&
		    (($user->data['user_id'] == $raidplan_data['poster_id'])|| $auth->acl_get('m_raidplanner_edit_other_users_raidplans')))
		{
			$edit_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=edit&amp;calEid=".$raidplan_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
			if( $raidplan_data['recurr_id'] > 0 )
			{
				$edit_all_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=edit&amp;calEditAll=1&amp;calEid=".$raidplan_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
			}
		}
		
		$delete_url = "";
		$delete_all_url = "";
		if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_raidplans') &&
		    (($user->data['user_id'] == $raidplan_data['poster_id'])|| $auth->acl_get('m_raidplanner_delete_other_users_raidplans') ))
		{
			$delete_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=delete&amp;calEid=".$raidplan_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			if( $raidplan_data['recurr_id'] > 0 )
			{
				$delete_all_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;mode=delete&amp;calDelAll=1&amp;calEid=".$raidplan_id."&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
			}
		}

		// does this raidplan have attendance tracking turned on and not personal ?
		if( $raidplan_data['track_signups'] == 1 && $raidplan_data['raidplan_access_level'] != 0)
		{
			
			$signup_data = array();
			$signup_data['signup_id'] = 0;
			$signup_data['raidplan_id'] = $raidplan_id;
			$signup_data['poster_id'] = $user->data['user_id'];
			$signup_data['poster_name'] = $user->data['username'];
			$signup_data['poster_colour'] = $user->data['user_colour'];
			$signup_data['poster_ip'] = $user->ip;
			$signup_data['post_time'] = time();
			$signup_data['dkpmember_id'] = request_var('signupchar', 0);
			$signup_data['signup_val'] = 2;
			$signup_data['signup_count'] = 1;
			$signup_data['signup_detail'] = "";
			$signup_data['signup_detail_edit'] = "";
				
			
			// show signed up
			$signup_id	= request_var('hidden_signup_id', 0);
			
			if ($signup_id ==0)
			{
				//doublecheck in database in case of repost
				$signup_id = $this->check_if_subscribed($signup_data['poster_id'],$signup_data['dkpmember_id'], $signup_data['raidplan_id']);
			}
	
			if( $signup_id !== 0 )
			{
				$this->get_signup_data( $signup_id, $signup_data );
				if( $signup_data['raidplan_id'] != $raidplan_id )
				{
					trigger_error('NO_SIGNUP');
				}
			}

			// Can we edit this reply ... if we're a moderator with rights then always yes
			// else it depends on editing times, lock status and if we're the correct user
			if ( $signup_id !== 0 && !$auth->acl_get('m_raidplanner_edit_other_users_signups'))
			{
				if ($user->data['user_id'] != $signup_data['poster_id'])
				{
					trigger_error('USER_CANNOT_EDIT_SIGNUP');
				}
			}

			// sign up
			$signmeup = (isset($_POST['signmeup'])) ? true : false;
			if( $signmeup )
			{
				$raidplans->signup($raidplan_data, $signup_data);
			}
			
			$edit_signups = 0;
			if( $auth->acl_get('m_raidplanner_edit_other_users_signups') )
			{
				$edit_signups = 1;
				$edit_signup_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$raidplan_id.$etype_url_opts );
				$edit_signup_url .="&amp;signup_id=";
			}
			
			
			// list the available signups per role_id
			// first get profiles needed for this raid
			$sql_array = array(
			    	'SELECT'    => 'r.role_id, r.role_name, r.role_color, r.role_icon, er.role_needed, er.role_signedup, er.role_confirmed ', 
			    	'FROM'      => array(
						RP_ROLES   => 'r'
			    	),
			    
			    	'LEFT_JOIN' => array(
			        	array(
			            	'FROM'  => array( RP_RAIDPLAN_ROLES  => 'er'),
			            	'ON'    => 'r.role_id = er.role_id AND er.raidplan_id = ' . $raidplan_id)
			    			),
			    	'WHERE' => ' er.role_needed > 0' ,  
			    	'ORDER_BY'  => 'r.role_id'
			);
			$sql = $db->sql_build_query('SELECT', $sql_array);
			$result0 = $db->sql_query($sql);
			$roles = array ();
			$total_needed = 0;
			while ( $row = $db->sql_fetchrow ( $result0 ) )
			{

				$role_id = $row['role_id'];
				$role_name = $row['role_name'];
				$role_needed = $row['role_needed'];
				$role_signedup = $row['role_signedup'];
				$total_needed += $role_needed;
				
				// build divs with signups 
				$template->assign_block_vars('raidroles', array(
				        'ROLE_ID'        => $role_id,
					    'ROLE_NAME'      => $role_name,
				    	'ROLE_NEEDED'    => $role_needed,
						'ROLE_COLOR'	 => $row['role_color'],
						'S_ROLE_ICON_EXISTS' => (strlen($row['role_icon']) > 1) ? true : false,
				       	'ROLE_ICON' 	 => (strlen($row['role_icon']) > 1) ? $phpbb_root_path . "images/raidrole_images/" . $row['role_icon'] . ".png" : '',
				    	'ROLE_SIGNEDUP'  => $role_signedup,
				 ));
			 
				 
				// list the signups for each raid role
				$sql_array = array(
			    	'SELECT'    => ' s.*, m.member_id, m.member_name, m.member_level,  
				    				 m.member_gender_id, a.image_female_small, a.image_male_small, 
				    				 l.name as member_class , c.imagename, c.colorcode ', 
			    	'FROM'      => array(
				        RP_SIGNUPS	 		=> 's', 
				        MEMBER_LIST_TABLE 	=> 'm',
				        CLASS_TABLE  		=> 'c',
				        RACE_TABLE  		=> 'a',
				        BB_LANGUAGE			=> 'l', 
				        
			    	),
			    
				    'WHERE'     =>  " l.attribute_id = c.class_id 
				    				  AND l.language = '" . $config['bbdkp_lang'] . "' 
			    					  AND l.attribute = 'class'
									  AND (m.member_class_id = c.class_id)
									  AND m.member_race_id =  a.race_id  
									  AND s.role_id = " . (int) $role_id . ' 
									  AND s.raidplan_id = ' . $raidplan_id . '
									  AND s.poster_id = m.phpbb_user_id
									  AND s.dkpmember_id = m.member_id
									  AND m.game_id = c.game_id and m.game_id = a.game_id and m.game_id = l.game_id' , 
			    	'ORDER_BY'  => 's.signup_val DESC'
				);
				$sql = $db->sql_build_query('SELECT', $sql_array);
					
				$result = $db->sql_query($sql);
				
				// loop signups
				while ($signup_row = $db->sql_fetchrow($result) )
				{
					
					if( ($signup_id == 0 && $signup_data['poster_id'] == $signup_row['poster_id']) ||
					    ($signup_id != 0 && $signup_id == $signup_row['signup_id']) )
					{
						
						$signup_data['signup_id'] = $signup_row['signup_id'];
						$signup_data['post_time'] = $signup_row['post_time'];
						$signup_data['signup_val'] = $signup_row['signup_val'];
						$signup_data['signup_count'] = $signup_row['signup_count'];
						$edit_text_array = generate_text_for_edit( $signup_row['signup_detail'], $signup_row['bbcode_uid'], $signup_row['bbcode_options']);
						$signup_data['signup_detail_edit'] = $edit_text_array['text'];
					}

					if( $signup_row['signup_val'] == 0 )
					{
						$signupcolor = '#00FF00';
						$signuptext = $user->lang['YES'];
					}
					else if( $signup_row['signup_val'] == 1 )
					{
						$signupcolor = '#FF0000';
						$signuptext = $user->lang['NO'];
					}
					else
					{
						$signupcolor = '#FFCC33';
						$signuptext = $user->lang['MAYBE'];
					}
					
					$signup_editlink = "";
					if( $edit_signups === 1 )
					{
						$signup_editlink = $edit_signup_url . $signup_row['signup_id'];
					}
					
					$raceimage = (string) (($signup_row['member_gender_id']==0) ? $signup_row['image_male_small'] : $signup_row['image_female_small']);
					
					$template->assign_block_vars('raidroles.signups', array(
        				'POST_TIME' => $user->format_date($signup_row['post_time']),
						'POST_TIMESTAMP' => $signup_row['post_time'],
						'DETAILS' => generate_text_for_display($signup_row['signup_detail'], $signup_row['bbcode_uid'], $signup_row['bbcode_bitfield'], $signup_row['bbcode_options']),
						'HEADCOUNT' => $signup_row['signup_count'],
						'U_EDIT' => $signup_editlink,
						'POSTER' => $signup_row['poster_name'], 
						'POSTER_URL' => get_username_string( 'full', $signup_row['poster_id'], $signup_row['poster_name'], $signup_row['poster_colour'] ),
						'VALUE' => $signup_row['signup_val'], 
						'POST_TIME' => $user->format_date($signup_row['post_time']),
						'COLOR' => $signupcolor, 
						'VALUE_TXT' => $signuptext, 
						'CHARNAME'      => $signup_row['member_name'],
						'LEVEL'         => $signup_row['member_level'],
						'CLASS'         => $signup_row['member_class'],
						'COLORCODE'  	=> ($signup_row['colorcode'] == '') ? '#123456' : $signup_row['colorcode'],
				        'CLASS_IMAGE' 	=> (strlen($signup_row['imagename']) > 1) ? $phpbb_root_path . "images/class_images/" . $signup_row['imagename'] . ".png" : '',  
						'S_CLASS_IMAGE_EXISTS' => (strlen($signup_row['imagename']) > 1) ? true : false,
				       	'RACE_IMAGE' 	=> (strlen($raceimage) > 1) ? $phpbb_root_path . "images/race_images/" . $raceimage . ".png" : '',  
						'S_RACE_IMAGE_EXISTS' => (strlen($raceimage) > 1) ? true : false, 			 				
					
					
					));
    
				}
				
			}
			
			
			$db->sql_freeresult($result);
			$db->sql_freeresult($result0);
			
			$show_current_response = 0;
			
			/* Build the signup form */
			/* if its not a bot and not anon show form */
			if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
			{
				$show_current_response = 1;
				
				// will you attend ?
				$sel_attend_code  = "<select name='signup_val' id='signup_val''>\n";
				$sel_attend_code .= "<option value='0'>".$user->lang['SIGN_UP']."</option>\n";
				$sel_attend_code .= "<option value='1'>".$user->lang['DECLINE']."</option>\n";
				$sel_attend_code .= "<option value='2'>".$user->lang['TENTATIVE']."</option>\n";
				$sel_attend_code .= "</select>\n";
				
				// get profiles still not confirmed for this raid for the pulldown
				// ex. needed 5
				// available signups 7
				// confirmed 3
				// --> list this role because 5-3 > 0
				$sql_array = array(
			    	'SELECT'    => 'r.role_id, r.role_name, er.role_needed, er.role_confirmed, er.role_needed', 
			    	'FROM'      => array(
						RP_ROLES   => 'r'
			    	),
			    
			    	'LEFT_JOIN' => array(
			        	array(
			            	'FROM'  => array( RP_RAIDPLAN_ROLES  => 'er'),
			            	'ON'    => 'r.role_id = er.role_id AND er.raidplan_id = ' . $raidplan_id)
			    			),
			    	'WHERE' => '(er.role_needed - er.role_confirmed) > 0' ,  
			    	'ORDER_BY'  => 'r.role_id'
				);
				$sql = $db->sql_build_query('SELECT', $sql_array);
				$result = $db->sql_query($sql);
				$s_role_options = '';
				while ($row = $db->sql_fetchrow($result))
				{
					//build the role pulldown
					$s_role_options .= '<option value="' . $row['role_id'] . '" > ' . $row['role_name'] . ' ('.$row['role_confirmed'] .'/'.$row['role_needed'] .')' . '</option>';     
				}
				$db->sql_freeresult($result);
				
				//build the dkpmember pulldown, only those that are assigned to this user.
				$sql_array = array(
				    'SELECT'    => 	'm.member_id, m.member_name  ', 
				    'FROM'      => array(
				        MEMBER_LIST_TABLE 	=> 'm',
				        USERS_TABLE 		=> 'u', 
				    	),
				    'WHERE'     =>  " m.member_rank_id != 90 AND u.user_id = m.phpbb_user_id AND u.user_id = " . $user->data['user_id']  ,
					'ORDER_BY'	=> " m.member_name ",
				    );

			    $sql = $db->sql_build_query('SELECT', $sql_array);
			    $result = $db->sql_query($sql);
				$s_member_options = '';
				$hasdkpchar= false;
				while ( $row = $db->sql_fetchrow($result) )
                   {
                   	$hasdkpchar = true;
					$s_member_options .= '<option value="' . $row['member_id'] . '" > ' . $row['member_name'] . '</option>';
                   }
                   $db->sql_freeresult($result);
				$template->assign_vars(array(
					'S_CANSIGNUP' => $hasdkpchar,
					'S_RAIDMEMBER_OPTIONS'	=> $s_member_options,
					)
				);

		
				$temp_find_str = "value='".$signup_data['signup_val']."'";
				$temp_replace_str = "value='".$signup_data['signup_val']."' selected='selected'";
				$sel_attend_code = str_replace( $temp_find_str, $temp_replace_str, $sel_attend_code );

				$template->assign_vars( array(
					'S_SIGNUP_MODE_ACTION'=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$raidplan_id.$etype_url_opts ),
					'S_CURRENT_SIGNUP'	=> $show_current_response,
					'S_EDIT_SIGNUP'		=> $edit_signups,
					'S_ROLE_OPTIONS'	=> $s_role_options, 
					'CURR_SIGNUP_ID'	=> $signup_data['signup_id'],
					'CURR_POSTER_URL'	=> get_username_string( 'full', $signup_data['poster_id'], $signup_data['poster_name'], $signup_data['poster_colour'] ),
					'CURR_SIGNUP_COUNT'	=> $signup_data['signup_count'],
					'CURR_SIGNUP_DETAIL'	=> $signup_data['signup_detail_edit'],
					'SEL_ATTEND'		=> $sel_attend_code,
										)
				);

			}
			
			// display raid attendance statistics
			
			$template->assign_vars( array(
				'RAID_TOTAL'		=> $total_needed,
			
				'CURR_INVITED_COUNT' => 0, 
				'S_CURR_INVITED_COUNT'	=> false,
			
				'CURR_YES_COUNT'	=> $raidplan_data['signup_yes'],
				'S_CURR_YES_COUNT'	=> ($raidplan_data['signup_yes'] + $raidplan_data['signup_maybe'] > 0) ? true: false,
				
				'CURR_MAYBE_COUNT'	=> $raidplan_data['signup_maybe'],
				'S_CURR_MAYBE_COUNT' => ($raidplan_data['signup_maybe'] > 0) ? true: false,

				'CURR_NO_COUNT'		=> $raidplan_data['signup_no'],
				'S_CURR_NO_COUNT'	=> ($raidplan_data['signup_no'] > 0) ? true: false,
			
				)
			);
		}
	
		$add_raidplan_url = "";
		if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans') )
		{
			$add_raidplan_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		}
		$day_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		$week_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);
		$month_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year'].$etype_url_opts);

		$s_signup_headcount = false;
		if( ($user->data['user_id'] == $raidplan_data['poster_id'])|| $auth->acl_get('u_raidplanner_view_headcount') )
		{
			$s_signup_headcount = true;
		}
		
		$s_watching_raidplan = array();
		$this->calendar_init_s_watching_raidplan_data( $raidplan_id, $s_watching_raidplan );

		$template->assign_vars(array(
			'U_CALENDAR'		=> $back_url,
			'ETYPE_DISPLAY_NAME'=> $raidplan_display_name,
			'EVENT_COLOR'		=> $raidplan_color,
			'EVENT_IMAGE' 		=> $phpbb_root_path . "images/event_images/" . $raidplan_image . ".png", 
           	'S_EVENT_IMAGE_EXISTS' 	=> (strlen($raidplan_image) > 1) ? true : false, 
			'SUBJECT'			=> $subject,
			'MESSAGE'			=> $message,
		
			'INVITE_TIME'		=> $invite_date_txt,
			'START_TIME'		=> $start_date_txt,
			'END_DATE'			=> $end_date_txt,
			'S_PLANNER_RAIDPLAN'	=> true,
			'IS_RECURRING'		=> $raidplan_data['recurr_id'],
			'RECURRING_TXT'		=> $this->get_recurring_raidplan_string_via_id( $raidplan_data['recurr_id'] ),
			'POSTER'			=> $poster_url,
			'ALL_DAY'			=> $all_day,
			'INVITED'			=> $invite_list,
			'U_EDIT'			=> $edit_url,
			'U_EDIT_ALL'		=> $edit_all_url,
			'U_DELETE'			=> $delete_url,
			'U_DELETE_ALL'		=> $delete_all_url,
			'DAY_IMG'			=> $user->img('button_calendar_day', 'DAY'),
			'WEEK_IMG'			=> $user->img('button_calendar_week', 'WEEK'),
			'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
			'ADD_LINK'			=> $add_raidplan_url,
			'DAY_VIEW_URL'		=> $day_view_url,
			'WEEK_VIEW_URL'		=> $week_view_url,
			'MONTH_VIEW_URL'	=> $month_view_url,
			'S_CALENDAR_SIGNUPS'	=> $raidplan_data['track_signups'],
			'S_SIGNUP_HEADCOUNT'	=> $s_signup_headcount,
			'U_WATCH_RAIDPLAN' 		=> $s_watching_raidplan['link'],
			'L_WATCH_RAIDPLAN' 		=> $s_watching_raidplan['title'],
			'S_WATCHING_RAIDPLAN'	=> $s_watching_raidplan['is_watching'],
			
			)
		);
		
	}
}

?>
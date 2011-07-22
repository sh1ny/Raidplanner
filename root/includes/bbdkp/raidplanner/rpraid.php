<?php
/**
*
* @author Sajaki
* @package bbDKP Raidplanner
* @copyright (c) 2011 Sajaki
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

/**
 * implements a raid plan
 *
 */
class rpraid
{
	/**
	 * raidplan pk
	 *
	 * @var int
	 */
	private $id; 
	
	/**
	 * raidplan event type
	 *
	 * @var int
	 */
	public $event_type; 
	
	private $invite_time;
	private $start_time;
	private $end_time;
	private $all_day;
	private $day;

	private $subject;
	private $body;
	private $bbcode = array();
	
	private $poster;

	/**
	 * access level 2 =group 
	 * @var unknown_type
	 */
	private $accesslevel;
	private $group_id;
	private $group_id_list;
	
	/**
	 * aray of signups
	 *
	 * @var unknown_type
	 */
	private $signups =array();
	
	/**
	 * can user see raidplan ?
	 *
	 * @var boolean
	 */
	private $auth_cansee = false;
	
	// if raidplan is recurring then id > 0
	private $recurr_id = 0;
	
	
	/**
	 * url of the poster
	 *
	 * @var string
	 */
	private $poster_url = '';
	
	/**
	 * string representing invited groups
	 *
	 * @var string
	 */
	private $invite_list = '';
		
	/**
	 * signups allowed ?
	 *
	 * @var boolean
	 */
	private $signups_allowed;
	
	/**
	 * 
	 */
	function __construct($id)
	{
		global $db, $user, $config, $phpEx, $phpbb_root_path, $db;
		
		//if $id != 0 then make object from db
		$this->id=$id;
		
		if($id !=0)
		{
			// fabricate the raidplan object
			
			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . ' WHERE raidplan_id = '. (int) $id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if( !$row )
			{
				trigger_error( 'INVALID_RAIDPLAN' );
			}
			
			$this->event_type= $row['etype_id'];
			$this->invite_time=$row['raidplan_invite_time'];
			$this->start_time=$row['raidplan_start_time'];
			$this->end_time=$row['raidplan_end_time'];
			
			$this->all_day=$row['raidplan_all_day'];
			$this->day=$row['raidplan_day'];

			$this->subject=$row['raidplan_subject'];
			$this->body=$row['raidplan_body'];
			
			$this->bbcode['bitfield']= $row['bbcode_bitfield'];
			$this->bbcode['uid']= $row['bbcode_uid'];
			//enable_bbcode & enable_smilies & enable_magic_url always 1
			
			
			$this->poster=$row['poster_id'];
			$sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . ' WHERE user_id = '.$db->sql_escape($this->poster);
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$this->poster_url = get_username_string( 'full', $this->poster, $row['username'], $row['user_colour'] );
			
			$this->group_id=$row['group_id'];
			$this->group_id_list=$row['group_id_list'];
			$this->accesslevel=$row['raidplan_access_level'];
			
			//depending on access level invite different groups.
			switch( $this->accesslevel )
			{
				case 0:
					// personal raidplan... only raidplan creator is invited
					$this->invite_list = $this->poster_url;
					break;
				case 1:
					if( $this->group_id != 0 )
					{
						// this is a group raidplan... only phpbb accounts of this group are invited
						$sql = 'SELECT group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
								WHERE group_id = '.$db->sql_escape($this->group_id);
						
						$result = $db->sql_query($sql);
						$group_data = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);
						
						$temp_list = (($group_data['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $group_data['group_name']] : $group_data['group_name']);
						$temp_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=group&amp;g=".$this->group_id);
						$temp_color_start = "";
						$temp_color_end = "";
						if( $group_data['group_colour'] !== "" )
						{
							$temp_color_start = "<span style='color:#".$group_data['group_colour']."'>";
							$temp_color_end = "</span>";
						}
						$this->invite_list = "<a href='".$temp_url."'>".$temp_color_start.$temp_list.$temp_color_end."</a>";
					}
					else 
					{
						// multiple groups invited	
						$group_list = explode( ',', $this->group_id_list );
						$num_groups = sizeof( $group_list );
						for( $i = 0; $i < $num_groups; $i++ )
						{
							if( $group_list[$i] == "")
							{
								continue;
							}
							
							// group raidplan... only phpbb accounts  of specified group are invited
							$sql = 'SELECT group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
									WHERE group_id = '.$db->sql_escape($group_list[$i]);
							$result = $db->sql_query($sql);
							$group_data = $db->sql_fetchrow($result);
							$db->sql_freeresult($result);
							$temp_list = (($group_data['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $group_data['group_name']] : $group_data['group_name']);
							$temp_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=group&amp;g=".$this->group_id);
							$temp_color_start = "";
							$temp_color_end = "";
							if( $group_data['group_colour'] !== "" )
							{
								$temp_color_start = "<span style='color:#".$group_data['group_colour']."'>";
								$temp_color_end = "</span>";
							}
							
							if( $this->invite_list == "" )
							{
								$this->invite_list = "<a href='".$temp_url."'>".$temp_color_start.$temp_list.$temp_color_end."</a>";
							}
							else
							{
								$this->invite_list .=  ", " . "<a href='".$temp_url."'>".$temp_color_start.$temp_list.$temp_color_end."</a>";
							}
						}
					}
					break;
				case 2:
					// public raidplan... everyone is invited
					$this->invite_list = $user->lang['EVERYONE'];
					break;
			}
			
			//false or true
			$this->auth_cansee = $this->checkauth();
			
			$this->recurr_id = $row['recurr_id'];
			
			if ($row['track_signups'] == 1)
			{
				$this->signups['track'] = true;
				$this->signups['yes'] = $row['signup_yes'];
				$this->signups['no'] = $row['signup_no'];
				$this->signups['maybe'] = $row['signup_maybe'];
			}
			else 
			{
				$this->signups['track'] = false;
				$this->signups['yes'] = 0;
				$this->signups['no'] = 0;
				$this->signups['maybe'] = 0;
			}
			
			
			
			
			
		}
		else 
		{
			$this->color = "";
			$this->image = "";
			$this->display_name= "";
			$this->all_day=0;
			
			$this->invite_time=0;
			$this->start_time=0;
			$this->end_time=0;
			$this->subject="";
			$this->poster=0;
			$this->body="";
			
			$this->signups['track'] = false;
			$this->signups['yes'] = 0;
			$this->signups['no'] = 0;
			$this->signups['maybe'] = 0;
			
		}
		
	}
	

	/**
	 * @see calendar::display()
	 *
	 */
	public function display(rpevents $eventlist, rpsignups $signups)
	{
		global $db, $user, $config, $template, $phpEx, $phpbb_root_path;
		
		// check if it is a private appointment
		if( !$this->auth_cansee)
		{
			trigger_error( 'PRIVATE_RAIDPLAN' );
		}
		
		// record every watch of this raidplan
		/*
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
		*/
		
		// format the dates
		$raidplan_display_name = $eventlist->events[$this->event_type]['event_name'];
		$raidplan_color = $eventlist->events[$this->event_type]['color'];
		$raidplan_image = $eventlist->events[$this->event_type]['imagename'];
		
		// 7
		$bbcode_options = OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES + OPTION_FLAG_LINKS;
		$message = generate_text_for_display($this->body, $this->bbcode['uid'], $this->bbcode['bitfield'], $bbcode_options);
		
		// translate raidplan start and end time into user's timezone
		$raidplan_invite = $this->invite_time + $user->timezone + $user->dst;
		$raidplan_start = $this->start_time + $user->timezone + $user->dst;
		$day = gmdate("d", $raidplan_start);
		$month_no = gmdate("n", $raidplan_start);
		$year =	gmdate('Y', $raidplan_start);
		$back_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;calD=".$day."&amp;calM=".$month_no."&amp;calY=".$year);
		$raidplan_end = $this->end_time + $user->timezone + $user->dst;

		// format
		$invite_date_txt = $user->format_date($raidplan_invite, $config['rp_date_time_format'], true);
		$start_date_txt = $user->format_date($raidplan_start, $config['rp_date_time_format'], true);
		$end_date_txt = $user->format_date($raidplan_end, $config['rp_date_time_format'], true);

		/* make the url for the edit button */
		$edit_url = "";
		$edit_all_url = "";
		if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_raidplans') &&
		    (($user->data['user_id'] == $this->poster )|| $auth->acl_get('m_raidplanner_edit_other_users_raidplans')))
		{
			$edit_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=edit&amp;calEid=".
				$this->id."&amp;calD=".$day."&amp;calM=".$month."&amp;calY=".$year);
			if( $this->recurr_id > 0 )
			{
				$edit_all_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=editall=1&amp;calEid=".
				$this->id."&amp;calD=".$day."&amp;calM=".$month."&amp;calY=".$year);
			}
		}
		/* make the url for the delete button */
		$delete_url = "";
		$delete_all_url = "";
		if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_raidplans') &&
		    (($user->data['user_id'] == $this->poster )|| $auth->acl_get('m_raidplanner_delete_other_users_raidplans') ))
		{
			$delete_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=delete&amp;calEid=".
				$this->id."&amp;calD=".$day."&amp;calM=".$month."&amp;calY=".$year);
			if( $raidplan_data['recurr_id'] > 0 )
			{
				$delete_all_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=deleteall&amp;calEid=".
				$this->id."&amp;calD=".$day."&amp;calM=".$month."&amp;calY=".$year);
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
				$edit_signup_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$raidplan_id );
				$edit_signup_url .="&amp;signup_id=";
			}
			
			
			
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
					'S_SIGNUP_MODE_ACTION'=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$raidplan_id ),
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
			$add_raidplan_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planneradd&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
		}
		$day_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
		$week_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);
		$month_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$this->date['day']."&amp;calM=".$this->date['month_no']."&amp;calY=".$this->date['year']);

		$s_signup_headcount = false;
		if( ($user->data['user_id'] == $raidplan_data['poster_id'])|| $auth->acl_get('u_raidplanner_view_headcount') )
		{
			$s_signup_headcount = true;
		}
		
		$s_watching_raidplan = array();
		$this->calendar_init_s_watching_raidplan_data( $raidplan_id, $s_watching_raidplan );

		$template->assign_vars(array(
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
	
	/**
	 * checks if user is allowed to see raid
	 *
	 * @return boolean
	 */
	private function checkauth()
	{
		global $user, $auth, $db;
		$user_auth_for_raidplan= false;
		
		if ($this->poster == $user->data['user_id'])
		{
			return true;
		}
		
		switch($this->accesslevel)
		{
			case 0:
				// personal raidplan... only raidplan creator is invited
				$user_auth_for_raidplan = false;
				break;
			case 1:
				// group raidplan... only members of specified phpbb usergroup are invited
				// is this user a member of the group?
				if($this->group_id !=0)
				{
					$sql = 'SELECT g.group_id
							FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
							WHERE ug.user_id = '.$db->sql_escape($user->data['user_id']).'
								AND g.group_id = ug.group_id
								AND g.group_id = '.$db->sql_escape($this->group_id).'
								AND ug.user_pending = 0';
					$result = $db->sql_query($sql);
					if($result)
					{
						$row = $db->sql_fetchrow($result);
						if( $row['group_id'] == $this->group_id )
						{
							$user_auth_for_raidplan = true;
						}
					}
					$db->sql_freeresult($result);
				}
				else 
				{
					$group_list = explode( ',', $this->group_id_list);
					$num_groups = sizeof( $group_list );
					$group_options = '';
					for( $i = 0; $i < $num_groups; $i++ )
					{
					    if( $group_list[$i] == "" )
					    {
					    	continue;
					    }
						if( $group_options != "" )
						{
							$group_options = $group_options . " OR ";
						}
						$group_options = $group_options . "g.group_id = ".$group_list[$i];
					}
					$sql = 'SELECT g.group_id
							FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
							WHERE ug.user_id = '.$db->sql_escape($user->data['user_id']).'
								AND g.group_id = ug.group_id
								AND ('.$group_options.')
								AND ug.user_pending = 0';
					$result = $db->sql_query($sql);
					if( $result )
					{
						$user_auth_for_raidplan = true;
					}
					$db->sql_freeresult($result);
				}
				break;
			case 2:
				// public raidplan... everyone is invited
				$user_auth_for_raidplan = true;
				break;
			
		}
		return $user_auth_for_raidplan;
		
	}
	
	

	public function showadd()
	{
		
	}
	
}

?>
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
	
	private $eventlist;
	
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
	 * access level 0 = personal, 1 = groups, 3 = all 
	 * @var int
	 */
	private $accesslevel;
	
	
	private $group_id;
	private $group_id_list;
	
	/**
	 * array of possible roles
	 *
	 * @var array
	 */
	private $roles= array();

	/**
	 * array of signoffs
	 *
	 * @var array
	 */
	private $signoffs= array();

	/**
	 * array of raid roles, subarray of signups per role
	 *
	 * @var array
	 */
	private $raidroles= array();

	/**
	 * aray of signups
	 *
	 * @var array
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
	 * constructor
	 *
	 * @param int $id
	 */
	function __construct($id=0)
	{
		global $phpEx, $phpbb_root_path;
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpevents.' . $phpEx);
		$this->eventlist= new rpevents();
		
		if($id !=0)
		{
			$this->id=$id;
			// fetch raid object from db
			$this->make_obj();
			//can user see it ?
			
		}
		
	}

	/**
	 * make raid object like example
	 * 
	 "$this" = Object of: rpraid	
		rpraid::id = (int) 1	
		event_type = (string:2) 39	
		rpraid::invite_time = (string:10) 1309896000	
		rpraid::start_time = (string:10) 1309897800	
		rpraid::end_time = (string:1) 0	
		rpraid::all_day = (string:1) 0	
		rpraid::day = (string:10) 00-00-0000	
		rpraid::subject = (string:2) qs	
		rpraid::body = (string:29) [b:1zcpogce]test[/b:1zcpogce]	
		rpraid::bbcode = Array [2]	
			bitfield = (string:4) QA==	
			uid = (string:8) 1zcpogce	
		rpraid::poster = (string:1) 2	
		rpraid::accesslevel = (string:1) 2	
		rpraid::group_id = (string:1) 0	
		rpraid::group_id_list = (string:1) ,	
		rpraid::roles = Array [0]	
		rpraid::raidroles = Array [6]	
			1 = Array [7]	
				role_name = (string:10) Ranged DPS	
				role_color = (string:7) #BB00AA	
				role_icon = (string:5) range	
				role_needed = (string:1) 3	
				role_signedup = (string:1) 0	
				role_confirmed = (string:1) 0	
				role_signups = Array [0]	
			2 = Array [7]	
				role_name = (string:9) Melee DPS	
				role_color = (string:7) #FFCC66	
				role_icon = (string:5) melee	
				role_needed = (string:1) 1	
				role_signedup = (string:1) 0	
				role_confirmed = (string:1) 0	
				role_signups = Array [0]	
			3 = Array [7]	
				role_name = (string:4) Tank	
				role_color = (string:7) #777777	
				role_icon = (string:4) tank	
				role_needed = (string:1) 1	
				role_signedup = (string:1) 0	
				role_confirmed = (string:1) 0	
				role_signups = Array [0]	
			4 = Array [7]	
				role_name = (string:8) Off Tank	
				role_color = (string:7) #AAAAAA	
				role_icon = (string:4) tank	
				role_needed = (string:1) 1	
				role_signedup = (string:1) 0	
				role_confirmed = (string:1) 0	
				role_signups = Array [0]	
			5 = Array [7]	
				role_name = (string:6) Healer	
				role_color = (string:7) #00EECC	
				role_icon = (string:6) healer	
				role_needed = (string:1) 2	
				role_signedup = (string:1) 0	
				role_confirmed = (string:1) 0	
				role_signups = Array [1]	
					0 = Array [24]	
						signup_id = (string:1) 1	
						raidplan_id = (string:1) 1	
						poster_id = (string:1) 2	
						poster_name = (string:5) admin	
						poster_colour = (string:0) 	
						poster_ip = (string:0) 	
						signup_val = (string:1) 1	
						signup_time = (string:10) 1309896000	
						signup_count = (string:1) 0	
						dkpmemberid = (string:2) 16	
						dkpmembername = (string:5) Xeeni	
						classname = (string:6) Priest	
						imagename = (string:42) ./images/class_images/wow_Priest_small.png	
						colorcode = (string:7) #FFFFFF	
						raceimg = (string:47) ./images/race_images/wow_human_female_small.png	
						genderid = (string:1) 1	
						level = (string:2) 80	
						dkp_current = (string:6) -30.00	
						priority_ratio = (string:4) 0.24	
						lastraid = (string:10) 1286391317	
						attendanceP1 = (double) 0	
						comment = (string:16) qdfgsdfgsdfgsfdg	
						roleid = (string:1) 5	
						confirm = (string:1) 0	
			6 = Array [7]	
				role_name = (string:6) Hybrid	
				role_color = (string:7) #9999FF	
				role_icon = (string:7) unknown	
				role_needed = (string:1) 2	
				role_signedup = (string:1) 0	
				role_confirmed = (string:1) 0	
				role_signups = Array [0]	
		rpraid::signups = Array [3]	
		rpraid::auth_cansee = (boolean) true	
		rpraid::recurr_id = (string:1) 0	
		rpraid::poster_url = (string:111) <a href="./memberlist.php?mode=viewprofile&amp;u=2" style="color: #AA0000;" class="username-coloured">admin</a>	
		rpraid::invite_list = (string:8) Everyone	
		rpraid::signups_allowed = (boolean) true	
	 */
	private function make_obj()
	{
			global $db, $user, $config, $phpEx, $phpbb_root_path, $db;
			
			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . ' WHERE raidplan_id = '. (int) $this->id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if( !$row )
			{
				trigger_error( 'INVALID_RAIDPLAN' );
			}
			
			// check access
			$this->accesslevel=$row['raidplan_access_level'];
			$this->poster=$row['poster_id'];
			$this->group_id=$row['group_id'];
			$this->group_id_list=$row['group_id_list'];

			$this->auth_cansee = $this->checkauth();
			if(!$this->auth_cansee)
			{
				trigger_error( 'NOT_AUTHORISED' );
			}
			
			// now go add raid properties
			$this->event_type= $row['etype_id'];
			$this->invite_time=$row['raidplan_invite_time'];
			$this->start_time=$row['raidplan_start_time'];
			$this->end_time=$row['raidplan_end_time'];
			
			$this->all_day=$row['raidplan_all_day'];
			$this->day=$row['raidplan_day'];
			
			// is raid recurring ? 
			// @todo not implemented yet !
			$this->recurr_id = $row['recurr_id'];
			//$this->get_recurring_raidplan_string_via_id( $raidplan_data['recurr_id'] )

			$this->subject=$row['raidplan_subject'];
			$this->body=$row['raidplan_body'];
			
			$this->bbcode['bitfield']= $row['bbcode_bitfield'];
			$this->bbcode['uid']= $row['bbcode_uid'];
			//enable_bbcode & enable_smilies & enable_magic_url always 1
			
			//get number of signups if they are tracked
			if ($row['track_signups'] == 1)
			{
				//track
				$this->signups_allowed = true;
				$this->signups['yes'] = $row['signup_yes'];
				$this->signups['no'] = $row['signup_no'];
				$this->signups['maybe'] = $row['signup_maybe'];
				
				// get array of raid roles with signups per role
				$this->get_raid_roles();
				
				// attach signups to roles
				$this->getSignups();
				
				//get all that signed unavailable 
				$this->get_unavailable();
			}
			else 
			{
				$this->signups_allowed = false;
				$this->signups['yes'] = 0;
				$this->signups['no'] = 0;
				$this->signups['maybe'] = 0;
			}
			unset ($row);
			
			$sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . ' WHERE user_id = '.$db->sql_escape($this->poster);
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$this->poster_url = get_username_string( 'full', $this->poster, $row['username'], $row['user_colour'] );
			
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
	
	
	/**
	 * builds raid roles property, needed sor displaying signups
	 *
	 */
	private function get_raid_roles()
	{
		global $db;
		
		$sql_array = array(
	    	'SELECT'    => 'rr.raidplandet_id, rr.role_needed, rr.role_signedup, rr.role_confirmed, 
	    					r.role_id, r.role_name, r.role_color, r.role_icon ', 
	    	'FROM'      => array(
				RP_ROLES   => 'r',
				RP_RAIDPLAN_ROLES   => 'rr'
	    	),
	    	'WHERE'		=>  'r.role_id = rr.role_id and rr.raidplan_id = ' . $this->id, 
	    	'ORDER_BY'  => 'r.role_id'
			);
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		$signups = array();
		while ( $row = $db->sql_fetchrow ( $result ) )
		{
			$this->raidroles[$row['role_id']]['role_name'] = $row['role_name'];
			$this->raidroles[$row['role_id']]['role_color'] = $row['role_color'];
			$this->raidroles[$row['role_id']]['role_icon'] = $row['role_icon']; 
			$this->raidroles[$row['role_id']]['role_needed'] = $row['role_needed']; 
			$this->raidroles[$row['role_id']]['role_signedup'] = $row['role_signedup']; 
			$this->raidroles[$row['role_id']]['role_confirmed'] = $row['role_confirmed']; 
			$this->raidroles[$row['role_id']]['role_signups'] =  $signups;
		}
		$db->sql_freeresult($result);
	}
	
	
	/**
	 * builds roles property, needed when you make new raid
	 *
	 */
	private function get_roles()
	{
		global $db;
		
		$sql_array = array(
	    	'SELECT'    => 'r.role_id, r.role_name, r.role_color, r.role_icon ', 
	    	'FROM'      => array(
				RP_ROLES   => 'r'
	    	),
	    	'ORDER_BY'  => 'r.role_id'
			);
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		while ( $row = $db->sql_fetchrow ( $result ) )
		{
			$this->roles[$row['role_id']]['role_name'] = $row['role_name'];
			$this->roles[$row['role_id']]['role_color'] = $row['role_color'];
			$this->roles[$row['role_id']]['role_icon'] = $row['role_icon']; 
		}
		$db->sql_freeresult($result);
	}
	
	/**
	 * selects all signups that have a role, then makes signup objects, returns array of objects to role code
	 *
	 * @param int $raidplan_id
	 */
	private function getSignups()
	{
		global $db, $phpEx, $phpbb_root_path, $db;

		if (!class_exists('rpsignup'))
		{
			require("{$phpbb_root_path}includes/bbdkp/raidplanner/rpsignups.$phpEx");
		}
		$rpsignup = new rpsignup();
		
		foreach ($this->raidroles as $id => $role)
		{
			$sql = "select * from " . RP_SIGNUPS . " where raidplan_id = " . $this->id . " and signup_val > 0 and role_id  = " . $id;
			$result = $db->sql_query($sql);
			$signups = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$rpsignup->getSignup($row['signup_id']);
				//get all public object vars to signup array and bind to role
				$this->raidroles[$id]['role_signups'][] = get_object_vars($rpsignup);
			}
			$db->sql_freeresult($result);
			
		}

	}
	
	/**
	 * get all those that signed unavailable
	 *
	 * @param int $raidplan_id
	 */
	public function get_unavailable()
	{
		global $db, $config, $phpbb_root_path, $db;
		
		if (!class_exists('rpsignup'))
		{
			require("{$phpbb_root_path}includes/bbdkp/raidplanner/rpsignups.$phpEx");
		}
		$rpsignup = new rpsignup();
		
		$sql = "select * from " . RP_SIGNUPS . " where raidplan_id = " . $this->id . " and signup_val = 0";
		$result = $db->sql_query($sql);
		$signups = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$rpsignup->getSignup($row['signup_id']);
			//get all public object vars to signup array and bind to role
			$this->signoffs[] = get_object_vars($rpsignup);
		}
		$db->sql_freeresult($result);
	}
	
	
	/**
	 * displays a raid object
	 *
	 * @param rpevents $eventlist
	 */
	public function display()
	{
		// raid object does not need to know the events list so it‘s passed byval
		
		global $db, $auth, $user, $config, $template, $phpEx, $phpbb_root_path;
		
		// check if it is a private appointment
		if( !$this->auth_cansee)
		{
			trigger_error( 'PRIVATE_RAIDPLAN' );
		}
		
		// format the raidplan message
		$bbcode_options = OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES + OPTION_FLAG_LINKS;
		$message = generate_text_for_display($this->body, $this->bbcode['uid'], $this->bbcode['bitfield'], $bbcode_options);

		// translate raidplan start and end time into user's timezone
		$raidplan_invite = $this->invite_time + $user->timezone + $user->dst;
		$raidplan_start = $this->start_time + $user->timezone + $user->dst;
		$day = gmdate("d", $raidplan_start);
		$month = gmdate("n", $raidplan_start);
		$year =	gmdate('Y', $raidplan_start);
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
			if( $this->recurr_id > 0 )
			{
				$delete_all_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=deleteall&amp;calEid=".
				$this->id."&amp;calD=".$day."&amp;calM=".$month."&amp;calY=".$year);
			}
		}
		
		// url to add raid
		$add_raidplan_url = "";
		if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans'))
		{
			$add_raidplan_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=add&amp;calD=".$day."&amp;calM=". $month. "&amp;calY=".$year);
		}
		$day_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$day ."&amp;calM=".$month."&amp;calY=".$year);
		$week_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$day ."&amp;calM=".$month."&amp;calY=".$year);
		$month_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$day."&amp;calM=".$month."&amp;calY=".$year);

		$s_signup_headcount = false;
		if($user->data['user_id'] == $this->poster || $auth->acl_get('u_raidplanner_view_headcount') )
		{
			$s_signup_headcount = true;
		}
		
		$total_needed = 0;		
		// check if we have signups and that the appointment is not a personal 
		if($this->signups_allowed == true && $this->accesslevel != 0)
		{
			//loop all roles
			// @ : role 0 is declined
			foreach($this->raidroles as $key => $role)
			{
				$total_needed += $role['role_needed'];
				$template->assign_block_vars('raidroles', array(
				        'ROLE_ID'        => $key,
					    'ROLE_NAME'      => $role['role_name'],
				    	'ROLE_NEEDED'    => $role['role_needed'],
				    	'ROLE_CONFIRMED' => $role['role_confirmed'],
				    	'ROLE_SIGNEDUP'  => $role['role_signedup'],
						'ROLE_COLOR'	 => $role['role_color'],
						'S_ROLE_ICON_EXISTS' => (strlen($role['role_icon']) > 1) ? true : false,
				       	'ROLE_ICON' 	 => (strlen($role['role_icon']) > 1) ? $phpbb_root_path . "images/raidrole_images/" . $role['role_icon'] . ".png" : '',
				 ));
				 
				 // loop signups per role
				 foreach($role['role_signups'] as $signup)
				 {
				 	
					$edit_text_array = generate_text_for_edit( $signup['comment'], $signup['bbcode']['uid'], 7);
					$editcomment = $edit_text_array['text'];
					if( $signup['signup_val'] == 1 )
					{
						$signupcolor = '#FFCC33';
						$signuptext = $user->lang['MAYBE'];
					}
					elseif( $signup['signup_val'] == 1 )
					{
						$signupcolor = '#00FF00';
						$signuptext = $user->lang['YES'];
					}
					elseif( $signup['signup_val'] == 2 )
					{
						$signupcolor = '#000000';
						$signuptext = $user->lang['CONFIRMED'];
					}
					
					// can user edit signup ?
					// depends on freeze time and who you are
				 	$edit_signups = 0;
					if( $auth->acl_get('m_raidplanner_edit_other_users_signups') )
					{
						$edit_signups = 1;
						$edit_signup_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=". $this->id );
						$edit_signup_url .="&amp;signup_id=" . $signup['signup_id'];
					}
			
					$template->assign_block_vars('raidroles.signups', array(
	       				'POST_TIME' => $user->format_date($signup['signup_time']),
						'POST_TIMESTAMP' => $signup['signup_time'],
						'DETAILS' => generate_text_for_display($signup['comment'], $signup['bbcode']['uid'], $signup['bbcode']['bitfield'], 7),
						'HEADCOUNT' => $signup['signup_count'],
						'U_EDIT' => '',
						'POSTER' => $signup['poster_name'], 
						'POSTER_URL' => get_username_string( 'full', $signup['poster_id'], $signup['poster_name'], $signup['poster_colour'] ),
						'VALUE' => $signup['signup_val'], 
						'POST_TIME' => $user->format_date($signup['signup_time']),
						'COLOR' => $signupcolor, 
						'VALUE_TXT' => $signuptext, 
						'CHARNAME'      => $signup['dkpmembername'],
						'LEVEL'         => $signup['level'],
						'CLASS'         => $signup['classname'],
						'COLORCODE'  	=> ($signup['colorcode'] == '') ? '#123456' : $signup['colorcode'],
				        'CLASS_IMAGE' 	=> (strlen($signup['imagename']) > 1) ? $phpbb_root_path . "images/class_images/" . $signup['imagename'] . ".png" : '',  
						'S_CLASS_IMAGE_EXISTS' => (strlen($signup['imagename']) > 1) ? true : false,
				       	'RACE_IMAGE' 	=> (strlen($signup['raceimg']) > 1) ? $phpbb_root_path . "images/race_images/" . $signup['raceimg'] . ".png" : '',  
						'S_RACE_IMAGE_EXISTS' => (strlen($signup['raceimg']) > 1) ? true : false, 			 				
					));
						
				 }
			 
			}
		}
		
		// display signoffs
		foreach($this->signoffs as $key => $signoff)
		{
			$template->assign_block_vars('raidroles.signups', array(
    			'POST_TIME' => $user->format_date($signoff['signup_time']),
				'POST_TIMESTAMP' => $signoff['signup_time'],
				'DETAILS' => generate_text_for_display($signup['comment'], $signoff['bbcode']['uid'], $signoff['bbcode']['bitfield'], 7),
				'POSTER' => $signoff['poster_name'], 
				'POSTER_URL' => get_username_string( 'full', $signoff['poster_id'], $signoff['poster_name'], $signoff['poster_colour'] ),
				'VALUE' => $signoff['signup_val'], 
				'POST_TIME' => $user->format_date($signoff['signup_time']),
				'COLOR' => '#FF0000', 
				'VALUE_TXT' => $user->lang['NO'], 
				'CHARNAME'      => $signoff['dkpmembername'],
				'LEVEL'         => $signoff['level'],
				'CLASS'         => $signoff['classname'],
				'COLORCODE'  	=> ($signoff['colorcode'] == '') ? '#123456' : $signoff['colorcode'],
		        'CLASS_IMAGE' 	=> (strlen($signoff['imagename']) > 1) ? $phpbb_root_path . "images/class_images/" . $signoff['imagename'] . ".png" : '',  
				'S_CLASS_IMAGE_EXISTS' => (strlen($signoff['imagename']) > 1) ? true : false,
		       	'RACE_IMAGE' 	=> (strlen($signoff['raceimg']) > 1) ? $phpbb_root_path . "images/race_images/" . $signoff['raceimg'] . ".png" : '',  
				'S_RACE_IMAGE_EXISTS' => (strlen($signoff['raceimg']) > 1) ? true : false, 			 				
			));
		}

		// fixed content
		
		$poster_url = ''; 
		
		$template->assign_vars( array(
			'RAID_TOTAL'		=> $total_needed,
		
			'CURR_INVITED_COUNT' => 0,
			'S_CURR_INVITED_COUNT'	=> false,
		
			'CURR_YES_COUNT'	=> $this->signups['yes'],
			'S_CURR_YES_COUNT'	=> ($this->signups['yes'] + $this->signups['maybe'] > 0) ? true: false,
			
			'CURR_MAYBE_COUNT'	=> $this->signups['maybe'],
			'S_CURR_MAYBE_COUNT' => ($this->signups['maybe'] > 0) ? true: false,

			'CURR_NO_COUNT'		=> $this->signups['no'],
			'S_CURR_NO_COUNT'	=> ($this->signups['no'] > 0) ? true: false,
		
			'ETYPE_DISPLAY_NAME'=> $this->eventlist->events[$this->event_type]['event_name'],
			'EVENT_COLOR'		=> $this->eventlist->events[$this->event_type]['color'],
			'EVENT_IMAGE' 		=> $phpbb_root_path . "images/event_images/" . $this->eventlist->events[$this->event_type]['imagename'] . ".png", 
           	'S_EVENT_IMAGE_EXISTS' 	=> (strlen($this->eventlist->events[$this->event_type]['imagename']) > 1) ? true : false, 
			'SUBJECT'			=> $this->subject,
			'MESSAGE'			=> $message,
		
			'INVITE_TIME'		=> $invite_date_txt,
			'START_TIME'		=> $start_date_txt,
			'END_DATE'			=> $end_date_txt,

			'S_PLANNER_RAIDPLAN'=> true,
		
			'IS_RECURRING'		=> $this->recurr_id,
			'POSTER'			=> $poster_url,
			'INVITED'			=> $this->invite_list,
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
			'S_CALENDAR_SIGNUPS'	=> $this->signups_allowed,
			'S_SIGNUP_HEADCOUNT'	=> $s_signup_headcount,
				
			)
		);
		
	}

	
	
	/**
	 * gets array with raid days 
	 *
	 * @param int $from
	 * @param int $end
	 * 
	 * @return array
	 */
	public function GetRaiddaylist($from, $end)
	{
		global $user, $db;
		
		// build sql 
		$sql_array = array(
   			'SELECT'    => 'r.raidplan_start_time ', 
			'FROM'		=> array(RP_RAIDS_TABLE => 'r' ), 
			'WHERE'		=>  '  r.raidplan_start_time >= '.$db->sql_escape($from).' 
							 AND r.raidplan_start_time <= '. $db->sql_escape($end) ,
			'ORDER_BY'	=> 'r.raidplan_start_time ASC');
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		$raiddaylist = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$raiddaylist [] = array(
				'month' => date("m", $row['raidplan_start_time']),
				'day' => date("d", $row['raidplan_start_time']),
				'year' => date("Y", $row['raidplan_start_time'])
			); 
		}
		
		$db->sql_freeresult($result);
		return array_unique($raiddaylist);
		
	}
	
	/**
	 * return raid plan info array to display on day/week/month/upcoming calendar
	 * 
	 * @param int $day		today
	 * @param int $month	this month
	 * @param int $year		this year
	 * @param string	$group_options 
	 * @param string 	$mode
	 * @param int 		$x		  	
	 * @return array
	 */
	public function GetRaidinfo($month, $day, $year, $group_options, $mode)
	{
		global $db, $user, $template, $config, $phpbb_root_path, $auth, $phpEx;
		
		$raidplan_output = array();
		
		//find any raidplans on this day
		$start_temp_date = gmmktime(0,0,0,$month, $day, $year)  - $user->timezone - $user->dst;
		
		switch($mode)
		{
			case "up":
				// get next x upcoming raids  
				// find all day raidplans since 1 days ago
				$start_temp_date = $start_temp_date - 30*86400+1;
				// don't list raidplans more than 2 months in the future
				$end_temp_date = $start_temp_date + 31536000;
				// show only this number of raids
				$x = $config['rp_index_display_next_raidplans'];
				break;
			case "next":
				// display the upcoming raidplans for the next x number of days
				$end_temp_date = $start_temp_date + ( $config['rp_index_display_next_raidplans'] * 86400 );
				$x = 0;
				break;
			default:
				$end_temp_date = $start_temp_date + 86399;
				//return all rows
				$x = 0;
		}
		
		$etype_url_opts = "";
		$raidplan_counter = 0;

		// build sql 
		$sql_array = array(
   			'SELECT'    => 'r.*', 
			'FROM'		=> array(RP_RAIDS_TABLE => 'r'), 
			'WHERE'		=>  ' ( (raidplan_access_level = 2)
							   OR (poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR (raidplan_access_level = 1 AND ('.$group_options.')) )  
							  AND (raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date). " )",
			'ORDER_BY'	=> 'r.raidplan_start_time ASC');
		
		// filter on event type ?
		$calEType = request_var('calEType', 0);
		if( $calEType != 0)
		{
			$sql_array['WHERE'] .= " AND etype_id = ".$db->sql_escape($calEType)." ";
			$etype_url_opts = "&amp;calEType=".$calEType;
		}
		
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query_limit($sql, $x, 0);

		while ($row = $db->sql_fetchrow($result))
		{

			$fsubj = $subj = censor_text($row['raidplan_subject']);
			if( $config['rp_display_truncated_name'] > 0 )
			{
				if(utf8_strlen($subj) > $config['rp_display_truncated_name'])
				{
					$subj = truncate_string($subj, $config['rp_display_truncated_name']) . '...';
				}
			}
			
			$correct_format = $config['rp_time_format'];
			if( $row['raidplan_end_time'] - $row['raidplan_start_time'] > 86400 )
			{
				$correct_format = $config['rp_date_time_format'];
			}
			
			$pre_padding = 0;
			$post_padding = 0;
			/* if in dayview we need to shift the raid to its time */
			if($mode =="day")
			{
				/* sets the colspan width */
		        if( $row['raidplan_start_time'] > $start_temp_date )
		        {
		          // find pre-padding value...
		          $start_diff = $row['raidplan_start_time'] - $start_temp_date;
		          $pre_padding = round($start_diff/900);
		        }
		
		        if( $row['raidplan_end_time'] < $end_temp_date )
		        {
		          // find pre-padding value...
		          $end_diff = $end_temp_date - $row['raidplan_end_time'];
		          $post_padding = round($end_diff/900);
		        }
			}

			/*
			 * $poster_url = '';
			$invite_list = '';
			$raidplans->get_raidplan_invites($row, $poster_url, $invite_list );
			$raidplan_data['POSTER'] = $poster_url;
			$raidplan_data['INVITED'] = $invite_list;
			$raidplan_data['ALL_DAY'] = 0;
			$row['raidplan_all_day'] == 1 
			list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $row['raidplan_day']);
			$row['raidplan_start_time'] = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
			$row['raidplan_end_time'] = $row['raidplan_start_time']+86399;
			*/
			
			$raidplan_output[] = array(
				'RAID_ID'				=> $row['raidplan_id'],
				'PRE_PADDING'			=> $pre_padding,
				'POST_PADDING'			=> $post_padding,
				'PADDING'				=> 96 - $pre_padding - $post_padding, 
				'ETYPE_DISPLAY_NAME' 	=> $this->eventlist->events[$row['etype_id']]['event_name'], 
				'FULL_SUBJECT' 			=> $fsubj,
				'EVENT_SUBJECT' 		=> $subj, 
				'COLOR' 				=> $this->eventlist->events[$row['etype_id']]['color'],
				'IMAGE' 				=> $phpbb_root_path . "images/event_images/" . $this->eventlist->events[$row['etype_id']]['imagename'] . ".png", 
				'S_EVENT_IMAGE_EXISTS'  => (strlen( $this->eventlist->events[$row['etype_id']]['imagename'] ) > 1) ? true : false,
				'EVENT_URL'  			=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts), 
				'EVENT_ID'  			=> $row['raidplan_id'],
				 // for popup
				'INVITE_TIME'  			=> $user->format_date($row['raidplan_invite_time'], $correct_format, true), 
				'START_TIME'			=> $user->format_date($row['raidplan_start_time'], $correct_format, true),
				'END_TIME' 				=> $user->format_date($row['raidplan_end_time'], $correct_format, true),
				
				'DISPLAY_BOLD'			=> ($user->data['user_id'] == $row['poster_id'] ) ? true : false,
				'ALL_DAY'				=> ($row['raidplan_all_day'] == 1  ) ? true : false,
				'SHOW_TIME'				=> ($mode == "day" ) ? true : false, 
				'COUNTER'				=> $raidplan_counter++, 
			);

		}
		$db->sql_freeresult($result);
		
		return $raidplan_output;
	}
	
}

?>
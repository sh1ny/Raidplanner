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

// Include the base class
if (!class_exists('raidplanner_base'))
{
	require($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplanner_base.' . $phpEx);
}

class raidplanner_signup extends raidplanner_base
{
	
	/**
	 * handles signing up to a raid (called from display_plannedraid)
	 *
	 * @param array $raidplan_data
	 * @param array $signup_data
	 */
	private function signup(&$raidplan_data, $signup_data)
	{
		global $user, $db, $config;
			

		// get the chosen raidrole 1-6, this changes the signup value
		$newrole_id = request_var('signuprole', 0);
		// get the attendance value
		$new_signup_val	= request_var('signup_val', 2);
		
		$uid = $bitfield = $options = '';
		$allow_bbcode = $allow_urls = $allow_smilies = true;
		generate_text_for_storage($new_signup_detail, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
		
		// get the chosen raidchar
		$signup_data['dkpmember_id'] = request_var('signupchar', 0);
		// update the ip address and time
		$signup_data['poster_ip'] = $user->ip;
		$signup_data['post_time'] = time();
		$signup_data['signup_count'] =  request_var('signup_count', 1);
		$signup_data['signup_detail'] = utf8_normalize_nfc( request_var('signup_detail', '', true) );
		
		$delta_yes_count = 0;
		$delta_no_count = 0;
		$delta_maybe_count = 0;
		
		// identify the signup. if user returns to signup screen he can change
		$signup_id = request_var('hidden_signup_id', 0);
		
		if ($signup_id ==0)
		{
			//doublecheck in database
			$signup_id = $this->check_if_subscribed($signup_data['poster_id'],$signup_data['dkpmember_id'], $signup_data['raidplan_id']);
		}
			
		// save the user's signup data...
		if( $signup_id > 0)
		{
			
			//get old role
			$old_role_id = (int) $signup_data['role_id'];
			$signup_data['role_id'] = $newrole_id;
			$sql = " select role_signedup from " . RP_RAIDPLAN_ROLES . " where role_id = " . 
			$old_role_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$result = $db->sql_query($sql);
			$db->sql_query($sql);
			$role_signedup = (int) $db->sql_fetchfield('role_signedup',0,$result);  
			$role_signedup = max(0, $role_signedup - 1);
			$db->sql_freeresult ( $result );
			// decrease old role
			$sql = " update " . RP_RAIDPLAN_ROLES . ' set role_signedup = ' . $role_signedup . ' where role_id = ' . 
			$old_role_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$db->sql_query($sql);
			
			// increase new role
			$sql = " update " . RP_RAIDPLAN_ROLES . " set role_signedup = (role_signedup  + 1) where role_id = " . 
			$newrole_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$db->sql_query($sql);
			
			// fetch existing signup value
			if ($signup_data['signup_val'] != $new_signup_val)
			{
				// new role selected 
				
				// decrease the current yes-no-maybe stat
				$old_signup_val = $signup_data['signup_val'];
				$signup_data['signup_val'] = $new_signup_val;
				
				switch($old_signup_val)
				{
					case 0:
						$delta_yes_count -= 1;
						break;
					case 1:
						$delta_no_count -= 1;
						break;
					case 2:
						$delta_maybe_count -= 1;
						break;
				}
				
				// NEW Signup
				switch($new_signup_val)
				{
					case 0:
						$delta_yes_count += 1;
						break;
					case 1:
						$delta_no_count += 1;
						break;
					case 2:
						$delta_maybe_count += 1;
						break;
				}

			}
			
			$sql = 'UPDATE ' . RP_SIGNUPS . '
				SET ' . $db->sql_build_array('UPDATE', array(
					'poster_id'			=> (int) $signup_data['poster_id'],
					'poster_name'		=> (string) $signup_data['poster_name'],
					'poster_colour'		=> (string) $signup_data['poster_colour'],
					'poster_ip'			=> (string) $signup_data['poster_ip'],
					'post_time'			=> (int) $signup_data['post_time'],
					'signup_val'		=> (int) $signup_data['signup_val'],
					'signup_count'		=> (int) $signup_data['signup_count'],
					'signup_detail'		=> (string) $signup_data['signup_detail'],
					'dkpmember_id'		=> $signup_data['dkpmember_id'], 
					'role_id'			=> (int) $newrole_id,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_uid'		=> $uid,
					'bbcode_options'	=> $options,
					)) . "
				WHERE signup_id = $signup_id";
			$db->sql_query($sql);
		}
		else
		{
			//NEW SIGNUP
			$sql = " update " . RP_RAIDPLAN_ROLES . " set role_signedup = (role_signedup  + 1) where role_id = " . 
			$newrole_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$db->sql_query($sql);
				
			switch($new_signup_val)
			{
				case 0:
					$delta_yes_count += 1;
					break;
				case 1:
					$delta_no_count += 1;
					break;
				case 2:
					$delta_maybe_count += 1;
					break;
			}
			
			$signup_data['signup_val'] = $new_signup_val;
			$signup_data['role_id'] = $newrole_id;
			
			$sql = 'INSERT INTO ' . RP_SIGNUPS . ' ' . $db->sql_build_array('INSERT', array(
					'raidplan_id'		=> (int) $signup_data['raidplan_id'],
					'poster_id'			=> (int) $signup_data['poster_id'],
					'poster_name'		=> (string) $signup_data['poster_name'],
					'poster_colour'		=> (string) $signup_data['poster_colour'],
					'poster_ip'			=> (string) $signup_data['poster_ip'],
					'post_time'			=> (int) $signup_data['post_time'],
					'signup_val'		=> (int) $signup_data['signup_val'],
					'signup_count'		=> (int) $signup_data['signup_count'],
					'signup_detail'		=> (string) $signup_data['signup_detail'],
					'dkpmember_id'		=> $signup_data['dkpmember_id'], 
					'role_id'			=> $newrole_id,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_uid'		=> $uid,
					'bbcode_options'	=> $options,
					)
				);
			$db->sql_query($sql);
			
			$signup_id = $db->sql_nextid();
			$signup_data['signup_id'] = $signup_id;
		}
		
		// update the raidplan id's signup stats
		$sql = 'UPDATE ' . RP_RAIDS_TABLE . ' SET signup_yes = signup_yes + ' . (int) $delta_yes_count . ', signup_no = signup_no + ' . 
			(int) $delta_no_count . ', signup_maybe = signup_maybe + ' . (int) $delta_maybe_count . '
		WHERE raidplan_id = ' . (int) $signup_data['raidplan_id'];
		$db->sql_query($sql);
		
		$raidplan_data['signup_yes'] = $raidplan_data['signup_yes'] + $delta_yes_count;
		$raidplan_data['signup_no'] = $raidplan_data['signup_no'] + $delta_no_count;
		$raidplan_data['signup_maybe'] = $raidplan_data['signup_maybe'] + $delta_maybe_count;
		
		$this->calendar_add_or_update_reply( $signup_data['raidplan_id'] );
		
	}
	
	
	
	
}

?>
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
* 
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

/*
 * collects functions for watching certain raidplan types
 * 
 */
class calendar_watch extends raidplanner_base 
{
	/* calendar_watch_raidplan()
	**
	** Adds/removes the current user into the RP_RAIDPLAN_WATCH table
	** so that they can start/stop recieving notifications about updates
	** and replies to the specified raidplan.
	**
	** INPUT
	**    $raidplan_id - the raidplan the want to start/stop watching
	**    $turn_on = 1 - the user wants to START watching the raidplan
	**    $turn_on = 0 - the user wants to STOP watching the raidplan
	*/
	public function calendar_watch_raidplan( $raidplan_id, $turn_on = 1 )
	{
		global $db, $user, $auth;
		global $phpEx, $phpbb_root_path;
	
		$user_id = $user->data['user_id'];
	
		if( $turn_on == 1 )
		{
			$is_watching_raidplan = false;
			$sql = 'SELECT * FROM ' . RP_RAIDPLAN_WATCH . '
				WHERE user_id = '.$user_id.' AND raidplan_id = ' .$raidplan_id;
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$is_watching_raidplan = true;
			}
			$db->sql_freeresult($result);
			if( $is_watching_raidplan )
			{
				$this->calendar_mark_user_read_raidplan( $raidplan_id, $user_id );
			}
			else
			{
				$sql = 'INSERT INTO ' . RP_RAIDPLAN_WATCH . ' ' . 
				$db->sql_build_array('INSERT', array(
						'raidplan_id'		=> (int) $raidplan_id,
						'user_id'		=> (int) $user_id,
						'notify_status'	=> (int) 0,
						'track_replies' => (int) 1,
						)
					);
				$db->sql_query($sql);
			}
		}
		else if( $turn_on == 0 )
		{
			$sql = 'DELETE FROM ' . RP_RAIDPLAN_WATCH . '
					WHERE raidplan_id = ' .$db->sql_escape($raidplan_id). '
					AND user_id = '.$db->sql_escape($user_id);
			$db->sql_query($sql);
		}
	}
	
	/* calendar_mark_user_read_raidplan()
	**
	** Changes the user's notify_status in the RP_RAIDPLAN_WATCH table
	** This indicates that the user has re-visited the raidplan, and
	** they will recieve a notification the next time there is
	** an update/reply posted to this raidplan.
	**
	** INPUT
	**   $user_id - the user who just viewed a raidplan.
	*/
	public function calendar_mark_user_read_raidplan( $raidplan_id, $user_id )
	{
		global $db;
	
		$sql = 'UPDATE ' . RP_RAIDPLAN_WATCH . '
			SET ' . $db->sql_build_array('UPDATE', array(
			'notify_status'		=> (int) 0,
								)) . "
			WHERE raidplan_id = $raidplan_id AND user_id = $user_id";
		$db->sql_query($sql);
	}
	
	/* calendar_mark_user_read_calendar()
	**
	** Changes the user's notify_status in the RP_WATCH table
	** This indicates that the user has re-visited the page, and
	** they will recieve a notification the next time there is
	** a new raidplan posted.
	**
	** INPUT
	**   $user_id - the user who just viewed a calendar page.
	*/
	public function calendar_mark_user_read_calendar( $user_id )
	{
		global $db;

		$sql = 'UPDATE ' . RP_WATCH . '
			SET ' . $db->sql_build_array('UPDATE', array(
			'notify_status'		=> (int) 0,
								)) . "
			WHERE user_id = $user_id";
		$db->sql_query($sql);
	}
	
	/* calendar_init_s_watching_calendar()
	**
	** Determines if the current user is watching the calendar, and
	** generates the data required for the overall_footer to display
	** the watch/unwatch link.
	**
	** OUTPUT
	**   $s_watching_calendar - filled with data for the overall_footer template
	*/
	public function calendar_init_s_watching_calendar( &$s_watching_calendar )
	{
		global $db, $user;
		global $phpEx, $phpbb_root_path;
	
		$s_watching_calendar['link'] = "";
		$s_watching_calendar['title'] = "";
		$s_watching_calendar['is_watching'] = false;
		if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
		{
			$sql = 'SELECT * FROM ' . RP_WATCH . '
				WHERE user_id = '.$user->data['user_id'];
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$s_watching_calendar['is_watching'] = true;
			}
			$db->sql_freeresult($result);
			if( $s_watching_calendar['is_watching'] )
			{
				$s_watching_calendar['link'] = append_sid( "{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;calWatch=0" );
				$s_watching_calendar['title'] = $user->lang['WATCH_CALENDAR_TURN_OFF'];
			}
			else
			{
				$s_watching_calendar['link'] = append_sid( "{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;calWatch=1" );
				$s_watching_calendar['title'] = $user->lang['WATCH_CALENDAR_TURN_ON'];
			}
		}
	}
		
	
	
}


?>
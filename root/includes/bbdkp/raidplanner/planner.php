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

$user->add_lang ( array ('mods/raidplanner'));

//get permissions
if ( !$auth->acl_get('u_raidplanner_view_raidplans') )
{
	trigger_error( 'USER_CANNOT_VIEW_RAIDPLAN' );
}
/*	
if (!class_exists('calendar_watch'))
{
	include($phpbb_root_path . 'includes/bbdkp/raidplanner/calendar_watch.' . $phpEx);
}

if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
{
	$calWatch = request_var( 'calWatch', 2 );

	$watchclass = new calendar_watch();
				
	if( $calWatch < 2 )
	{
		$watchclass->calendar_watch_calendar( $calWatch );
	}
	else
	{
		$watchclass->calendar_mark_user_read_calendar( $user->data['user_id'] );
	}
}
*/
$view_mode = request_var('view', 'month');

switch( $view_mode )
{

   case "next":
      // display next raidplans for specified number of days
      $template_body = "calendar_next_raidplans_for_x_days.html";
      $daycount = request_var('daycount', 60 );
      $user_id = request_var('u', 0);
      if( $user_id == 0 )
      {
      	// display all raids
      	$cal->display_next_raidplans_for_x_days( $daycount );
      }
      else
      {
      	// display signed up raids
      	$cal->display_users_next_raidplans_for_x_days($daycount, $user_id);
      }
      $template->assign_vars(array(
		'S_PLANNER_UPCOMING'	=> true,
		));
      break;
	case "raidplan":
		// display a single raidplan
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpraid.' . $phpEx);
		$cal = new rpraid();
		$cal->display();
		break;
	case "day":
		// display all of the raidplans on this day
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpday.' . $phpEx);
		$cal = new rpday();
		$cal->display();
		break;
	case "week":
		// display the entire week
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpweek.' . $phpEx);
		$cal = new rpweek();
		$cal->display();
		break;
	case "month":
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpmonth.' . $phpEx);
		$cal = new rpmonth();
		$cal->display();
		break;
}

$watcher = new calendar_watch(); 

$s_watching_calendar = array();
$watcher->calendar_init_s_watching_calendar( $s_watching_calendar );

$template->assign_vars(array(
		'U_WATCH_CALENDAR' 		=> $s_watching_calendar['link'],
		'L_WATCH_CALENDAR' 		=> $s_watching_calendar['title'],
		'S_WATCHING_CALENDAR'	=> $s_watching_calendar['is_watching'],
		)
	);

// Output the page
page_header($user->lang['PAGE_TITLE']); 


?>
<?php
/**
*
* @author alightner
*
* @package phpBB Calendar
* @version $Id $
* @copyright (c) 2009 alightner
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
include($phpbb_root_path . 'includes/bbdkp/raidplanner/functions_rp.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data); 
$user->setup('viewforum');
$user->add_lang ( array ('mods/dkp_common','mods/raidplanner'  ));

//get permissions
if ( !$auth->acl_get('u_raidplanner_view_events') )
{
	trigger_error( 'NO_AUTH_OPERATION' );
}

if( !$user->data['is_bot'] && $user->data['user_id'] != ANONYMOUS )
{
	$calWatch = request_var( 'calWatch', 2 );
	if( $calWatch < 2 )
	{
		calendar_watch_calendar( $calWatch );
	}
	else
	{
		calendar_mark_user_read_calendar( $user->data['user_id'] );
	}
}

$view_mode = request_var('view', 'month');

switch( $view_mode )
{
	case "event":
		// display a single event
		$template_body = "calendar_view_event.html";
		calendar_display_event();
		break;

   case "next":
      // display next events for specified number of days
      $template_body = "calendar_next_events_for_x_days.html";
      $daycount = request_var('daycount', 60 );
      $user_id = request_var('u', 0);
      if( $user_id == 0 )
      {
      	display_next_events_for_x_days( $daycount );
      }
      else
      {
      	display_users_next_events_for_x_days($daycount, $user_id);
      }
      break;

	case "day":
		// display all of the events on this day
		$template_body = "calendar_view_day.html";
		calendar_display_day();
		break;

	case "week":
		// display the entire week
		// viewing the week is a lot like viewing the month...
		$template_body = "calendar.html";
		calendar_display_week( 0 );
		break;

	case "month":
		// display the entire month
		$template_body = "calendar.html";
		calendar_display_month();
		break;
}

$s_watching_calendar = array();
calendar_init_s_watching_calendar( $s_watching_calendar );

$template->assign_vars(array(
		'U_WATCH_CALENDAR' 		=> $s_watching_calendar['link'],
		'L_WATCH_CALENDAR' 		=> $s_watching_calendar['title'],
		'S_WATCHING_CALENDAR'	=> $s_watching_calendar['is_watching'],
		)
	);

// Output the page
page_header($user->lang['PAGE_TITLE']); 


// Set the filename of the template you want to use for this file.
$template->set_filenames(array(
	'body' => $template_body)
);

page_footer();


?>

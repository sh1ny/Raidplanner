<?php
/**
*
* @author alightner
*
* @package phpBB Calendar
* @version CVS/SVN: $Id$
* @copyright (c) 2009 alightner
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true); // we tell the page that it is going to be using phpBB, this is important.
$phpbb_root_path = './'; // See phpbb_root_path documentation
$phpEx = substr(strrchr(__FILE__, '.'), 1); // Set the File extension for page-wide usage.
include($phpbb_root_path . 'common.' . $phpEx); // include the common.php file, this is important, especially for database connects.
include($phpbb_root_path . 'includes/functions_calendar.' . $phpEx); // contains the functions that "do the work".

// Start session management -- This will begin the session for the user browsing this page.
$user->session_begin();
$auth->acl($user->data);

// Language file (see documentation related to language files)
$user->setup('calendar');

// If users such as bots don't have permission to view any events
// you don't want them wasting time in the calendar...
// Is the user able to view ANY events?
if ( !$auth->acl_get('u_calendar_view_events') )
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

/**
* All of your coding will be here, setting up vars, database selects, inserts, etc...
*/
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
page_header($user->lang['PAGE_TITLE']); // Page title, this language variable should be defined in the language file you setup at the top of this page.


// Set the filename of the template you want to use for this file.
$template->set_filenames(array(
	'body' => $template_body) // template file name -- See Templates Documentation
);

// Finish the script, display the page
page_footer();


?>

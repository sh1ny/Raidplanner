<?php
/**
 * bbdkp acp language file for raidplanner module
 * 
 * @package bbDkp
 * @copyright 2010 bbdkp <http://code.google.com/p/bbdkp/>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * 
 */

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

// Merge the following language entries into the lang array
$lang = array_merge($lang, array(

	//settings 
    '12_HOURS'								=> '12 hours',
    '24_HOURS'								=> '24 hours',
    'AUTO_POPULATE_EVENT_FREQUENCY'			=> 'Auto Populate Recurring Events',
    'AUTO_POPULATE_EVENT_FREQUENCY_EXPLAIN'	=> 'How often (in days) should recurring events be populated in the calendar?  Note if you select 0, recurring events will never get added to the calendar.',
    'AUTO_POPULATE_EVENT_LIMIT'				=> 'Auto Populate Limits',
    'AUTO_POPULATE_EVENT_LIMIT_EXPLAIN'		=> 'How many days in advance do you want to populated with recurring events?  In other words, do you want to only see recurring events in the calendar for 30, 45, or more days before the event?',
    'AUTO_PRUNE_EVENT_FREQUENCY'			=> 'Auto Prune Past Events',
    'AUTO_PRUNE_EVENT_FREQUENCY_EXPLAIN'	=> 'How often (in days) should past events be pruned from the calendar?  Note if you select 0, past events will never be auto-pruned, you will have to delete them by hand.',
    'AUTO_PRUNE_EVENT_LIMIT'				=> 'Auto Prune Limits',
    'AUTO_PRUNE_EVENT_LIMIT_EXPLAIN'		=> 'How many days after an event do you want to add the event to the next auto prune\'s delete list?  In other words, do you want all events to remain in the calendar for 0, 30, or 45 days after the event?',
    'RP_ETYPE_NAME'							=> 'Event Type Name',
    'RP_ETYPE_COLOR'						=> 'Event Type Color',
    'RP_ETYPE_ICON'							=> 'Event Type Icon URL',
    'CHANGE_EVENTS_TO'						=> 'Change all events of this type to',
    'CLICK_PLUS_HOUR'						=> 'Move ALL events by one hour.',
    'CLICK_PLUS_HOUR_EXPLAIN'				=> 'Being able to move all events in the calendar +/- one hour helps when you reset the boards daylight savings time setting.  Note clicking on the links to move the events will loose any changes you have made above.  Please submit the form to save your work before moving the events +/- one hour.',
    'COLOR'									=> 'Color',
    'CREATE_EVENT_TYPE'						=> 'Create new event type',
    'DATE_FORMAT'							=> 'Date Format',
    'DATE_FORMAT_EXPLAIN'					=> 'Try &quot;M d, Y&quot;',
    'DATE_TIME_FORMAT'						=> 'Date and Time Format',
    'DATE_TIME_FORMAT_EXPLAIN'				=> 'Try &quot;M d, Y h:i a&quot; or &quot;M d, Y H:i&quot;',
    'DELETE'								=> 'Delete',
    'DELETE_ALL_EVENTS'						=> 'Delete any existing events of this type',
    'DELETE_ETYPE'							=> 'Delete Event Type',
    'DELETE_ETYPE_EXPLAIN'					=> 'Are you sure you want to delete this event type?',
    'DELETE_LAST_EVENT_TYPE'				=> 'Warning: this is the last event type.',
    'DELETE_LAST_EVENT_TYPE_EXPLAIN'		=> 'Deleting this event type will delete all events from the calendar.  New event creation will be disabled until new event types are created.',
    'DISPLAY_12_OR_24_HOURS'				=> 'Display Time Format',
    'DISPLAY_12_OR_24_HOURS_EXPLAIN'		=> 'Do you want to display the times in 12 hour mode with AM/PM or 24 hour mode?  This does not effect what format the times are displayed to the user - that is set in their profile.  This only effects the pulldown menu for time selection when creating/editing events and the timed headings on the view day calendar.',
    'DISPLAY_HIDDEN_GROUPS'					=> 'Display Hidden Groups',
    'DISPLAY_HIDDEN_GROUPS_EXPLAIN'			=> 'Do you want users to be able to see and invite members of hidden groups?  If this setting is disabled, only group administrators will be able to see and invite members of the hidden group.',
    'DISPLAY_NAME'							=> 'Disply Name (may be NULL)',
    'DISPLAY_EVENTS_ONLY_1_DAY'				=> 'Display Events 1 Day',
    'DISPLAY_EVENTS_ONLY_1_DAY_EXPLAIN'		=> 'Display events only on the day they begin (ignore their end date/time).',
    'DISPLAY_FIRST_WEEK'					=> 'Display Current Week',
    'DISPLAY_FIRST_WEEK_EXPLAIN'			=> 'Would you like to have the current week displayed on the forum index?',
    'DISPLAY_NEXT_EVENTS'					=> 'Display Next Events',
    'DISPLAY_NEXT_EVENTS_EXPLAIN'			=> 'Specify the number of current events you want listed on the index page.  Note this option is ignored if you have turned on the option to display the current week.',
    'DISPLAY_TRUNCATED_SUBJECT'				=> 'Truncate Subject',
    'DISPLAY_TRUNCATED_SUBJECT_EXPLAIN'		=> 'Long names in the subject can take up a lot of space on the calendar.  How many characters do you want displayed in the subject on the calendar? (enter 0 if you do not want to truncate the subject)',
    'EDIT'									=> 'Edit',
    'EDIT_ETYPE'							=> 'Edit Event Type',
    'EDIT_ETYPE_EXPLAIN'					=> 'Specify the way you want this event type to display.',
    'FIRST_DAY'								=> 'First Day',
    'FIRST_DAY_EXPLAIN'						=> 'Which day should be displayed as the first day of the week?',
    'FULL_NAME'								=> 'Full Name',
    'FRIDAY'								=> 'Friday',
    'ICON_URL'								=> 'URL for icon',
    'MANAGE_ETYPES'							=> 'Manage Event Types',
    'MANAGE_ETYPES_EXPLAIN'					=> 'Event types are used to help organize the calendar, you may add, edit, delete or reorder the event types here.',
    'MINUS_HOUR'							=> 'Move all events minus (-) one hour',
    'MONDAY'								=> 'Monday',
    'NO_EVENT_TYPE_ERROR'					=> 'Failed to find specified event type.',
    'PLUS_HOUR'								=> 'Move all events plus (+) one hour',
    'PLUS_HOUR_CONFIRM'						=> 'Are you sure you want to move all the events by %1$s hour?',
    'PLUS_HOUR_SUCCESS'						=> 'Successfully moved all events by %1$s hour.',
    'SATURDAY'								=> 'Saturday',
    'SUNDAY'								=> 'Sunday',
    'TIME_FORMAT'							=> 'Time Format',
    'TIME_FORMAT_EXPLAIN'					=> 'Try &quot;h:i a&quot; or &quot;H:i&quot;',
    'THURSDAY'								=> 'Thursday',
    'TUESDAY'								=> 'Tuesday',
    'USER_CANNOT_MANAGE_CALENDAR'			=> 'You do not have permission to manage the calendar settings or event types.',
    'WEDNESDAY'								=> 'Wednesday',
	'USER_CANNOT_MANAGE_RAIDPLANNER'		=> 'You are not authorised to manage the raidplanner settings', 

	'AM'					=> 'AM',

	'ALL_DAY'				 	=> 'All Day Event',
	'ALLOW_GUESTS'				=> 'Allow members to bring guests to this event',
	'ALLOW_GUESTS_ON'			=> 'Members are allowed to bring guests to this event.',
	'ALLOW_GUESTS_OFF'			=> 'Members are not allowed to bring guests to this event.',
	'AM'						=> 'AM',

	'CALENDAR_POST_EVENT'		=> 'Create New Event',
	'CALENDAR_EDIT_EVENT'		=> 'Edit Event',
	'CALENDAR_TITLE'			=> 'Planner',
	'RAIDPLANNER'				=> 'Raid Planner',
	'NEWRAID'					=> 'New Raid',

	'CALENDAR_NUMBER_ATTEND'=> 'The number of people you are bringing to this event',
	'CALENDAR_NUMBER_ATTEND_EXPLAIN'=> '(enter 1 for yourself)',
	'CALENDAR_RESPOND'		=> 'Please register here',
	'CALENDAR_WILL_ATTEND'	=> 'Will you attend this event?',
	'COL_HEADCOUNT'			=> 'Count',
	'COL_WILL_ATTEND'		=> 'Will Attend?',
	'COMMENTS'				=> 'Comments',

	'DAY'					=> 'Day',
	'DAY_OF'				=> 'Day of ',
	'DELETE_ALL_EVENTS'		=> 'Delete all occurrences of this event.',
	'DETAILS'				=> 'Details',
	'DELETE_EVENT'			=> 'Delete event',

	'EDIT'					=> 'Edit',
	'EDIT_ALL_EVENTS'		=> 'Edit all occurrences of this event.',
	
	'EMPTY_EVENT_MESSAGE'		=> 'You must enter a message when posting Events.',
	'EMPTY_EVENT_SUBJECT'		=> 'You must enter a subject when posting Events.',
	'EMPTY_EVENT_MESSAGE_RAIDS'	=> 'You must enter a message when posting Raids.',
	'EMPTY_EVENT_SUBJECT_RAIDS'	=> 'You must enter a subject when posting Raids.',


	'END_DATE'					=> 'End Date',
	'END_RECURRING_EVENT_DATE'	=> 'Last occurence:',
	'END_TIME'					=> 'End Time',
	'EVENT_ACCESS_LEVEL'			=> 'Who can see this event?',
	'EVENT_ACCESS_LEVEL_GROUP'		=> 'Group',
	'EVENT_ACCESS_LEVEL_PERSONAL'	=> 'Personal',
	'EVENT_ACCESS_LEVEL_PUBLIC'		=> 'Public',
	'EVENT_CREATED_BY'		=> 'Event Posted By',
	'EVENT_DELETED'				=> 'This event has been deleted successfully.',
	'EVENT_EDITED'				=> 'This event has been edited successfully.',
	'EVENT_GROUP'				=> 'Which group can see this event?',
	'EVENT_STORED'				=> 'This event has been created successfully.',
	'EVENT_TYPE'				=> 'Event Type',
	'EVERYONE'				=> 'Everyone',

	'FROM_TIME'				=> 'From',
	'FREQUENCEY_LESS_THAN_1'	=> 'Recurring events must have a frequency greater than or equal to 1',

	'HOW_MANY_PEOPLE'		=> 'Quick Headcount',

	'INVALID_EVENT'			=> 'The event you are trying to view does not exist.',
	'INVITE_INFO'			=> 'Invited',

	'MESSAGE_BODY_EXPLAIN'		=> 'Enter your message here, it may contain no more than <strong>%d</strong> characters.',
	'MAYBE'					=> 'Maybe',
	'MONTH'					=> 'Month',
	'MONTH_OF'				=> 'Month of ',
	'MY_EVENTS'				=> 'My Events',

	'LOCAL_DATE_FORMAT'		=> '%1$s %2$s, %3$s',
	'LOGIN_EXPLAIN_POST_EVENT'	=> 'You need to login in order to add/edit/delete events.',

	'NEGATIVE_LENGTH_EVENT'		=> 'The event cannot end before it starts.',
	'NEVER'						=> 'Never',
	'NEW_EVENT'					=> 'New Event',
	'NO_EVENT'					=> 'The requested event does not exist.',
	'NO_EVENT_TYPES'			=> 'The site administrator has not set up event types for this calendar.  Calendar event creation has been disabled.',
	'NO_GROUP_SELECTED'			=> 'There are no groups selected for this group event.',
	'NO_POST_EVENT_MODE'		=> 'No post mode specified.',
	'NO_EVENTS_TODAY'			=> 'There are no events scheduled for this day.',

	'OCCURS_EVERY'			=> 'Occurs every',
	
	'PAGE_TITLE'			=> 'Calendar',
	'PM'						=> 'PM',
	'PRIVATE_EVENT'			=> 'This event is private.  You must be invited and logged in to view this event.',

	'RECURRING_EVENT'				=> 'Recurring event',
	'RECURRING_EVENT_TYPE'			=> 'Recurrence Type: ',
	'RECURRING_EVENT_TYPE_EXPLAIN'	=> 'Tip choices begin with a letter to indicate their frequency: A - Annual, M - Monthly, W - Weekly, D - Daily',
	'RECURRING_EVENT_FREQ'		=> 'Event frequency:',
	'RECURRING_EVENT_FREQ_EXPLAIN'	=> 'This value represents [Y] in the choice above',
	
	'RECURRING_EVENT_CASE_1'    => 'A: [Xth] Day of [Month Name] every [Y] Year(s)',
	'RECURRING_EVENT_CASE_2'    => 'A: [Xth] [Weekday Name] of [Month Name] every [Y] Year(s)',
	'RECURRING_EVENT_CASE_3'    => 'M: [Xth] Day of month every [Y] Month(s)',
	'RECURRING_EVENT_CASE_4'    => 'M: [Xth] [Weekday Name] of month every [Y] Month(s)',
	'RECURRING_EVENT_CASE_5'    => 'W: [Weekday Name] every [Y] Week(s)',
	'RECURRING_EVENT_CASE_6'    => 'D: Every [Y] Day(s)',
	
	'RETURN_CALENDAR'			=> '%sReturn to the calendar%s',

	'START_DATE'				=> 'Start Date',
	'RAID_DATE'					=> 'Raid Date',
	'START_TIME'				=> 'Start Time',
	'RAID_START_TIME'			=> 'Raid Start Time',

	'TO_TIME'					=> 'To',

	'TRACK_RSVPS'				=> 'Track attendance',
	'TRACK_RSVPS_ON'			=> 'Attendance tracking is enabled.',
	'TRACK_RSVPS_OFF'			=> 'Attendance tracking is disabled.',

	'UPCOMING_EVENTS'		=> 'Upcoming Events',
	'USER_CANNOT_VIEW_EVENT'=> 'You do not have permission to view this event.',
	'USER_CANNOT_DELETE_EVENT'	=> 'You do not have permission to delete events.',
	'USER_CANNOT_EDIT_EVENT'	=> 'You do not have permission to edit events.',
	'USER_CANNOT_POST_EVENT'	=> 'You do not have permission to create events.',
	'USER_CANNOT_VIEW_EVENT'	=> 'You do not have permission to view events.',

	'VIEW_EVENT'				=> '%sView your submitted event%s',
	'WEEK'						=> 'Week',

	'WATCH_CALENDAR_TURN_ON'	=> 'Watch the calendar',
	'WATCH_CALENDAR_TURN_OFF'	=> 'Stop watching the calendar',
	'WATCH_EVENT_TURN_ON'		=> 'Watch this event',
	'WATCH_EVENT_TURN_OFF'		=> 'Stop watching this event',
	'WEEK'						=> 'Week',
	'WEEK_OF'					=> 'Week of ',
	
	'ZERO_LENGTH_EVENT'			=> 'The event cannot end at the same time it starts.',
	'ZEROTH_FROM'				=> '0th from ',
	'numbertext'			=> array(
		'0'		=> '0th',
		'1'		=> '1st',
		'2'		=> '2nd',
		'3'		=> '3rd',
		'4'		=> '4th',
		'5'		=> '5th',
		'6'		=> '6th',
		'7'		=> '7th',
		'8'		=> '8th',
		'9'		=> '9th',
		'10'	=> '10th',
		'11'	=> '11th',
		'12'	=> '12th',
		'13'	=> '13th',
		'14'	=> '14th',
		'15'	=> '15th',
		'16'	=> '16th',
		'17'	=> '17th',
		'18'	=> '18th',
		'19'	=> '19th',
		'20'	=> '20th',
		'21'	=> '21st',
		'22'	=> '22nd',
		'23'	=> '23rd',
		'24'	=> '24th',
		'25'	=> '25th',
		'26'	=> '26th',
		'27'	=> '27th',
		'28'	=> '28th',
		'29'	=> '29th',
		'30'	=> '30th',
		'31'	=> '31st',
		'n'		=> 'nth' ),

));

?>
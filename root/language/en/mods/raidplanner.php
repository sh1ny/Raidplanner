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


));

?>
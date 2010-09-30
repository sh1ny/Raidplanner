<?php
/**
*
* calendar [English]
*
* @author alightner
*
* @package phpBB Calendar
* @version CVS/SVN: $Id$
* @copyright (c) 2009 alightner
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/*** DO NOT CHANGE*/
if (empty($lang) || !is_array($lang))
{
    $lang = array();
}
// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'ALL_DAY'				=> 'All Day Event',
	'AM'					=> 'AM',
	'CALENDAR_TITLE'		=> 'Calendar',
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
	'EDIT'					=> 'Edit',
	'EDIT_ALL_EVENTS'		=> 'Edit all occurrences of this event.',
	'EVENT_CREATED_BY'		=> 'Event Posted By',
	'EVERYONE'				=> 'Everyone',
	'FROM_TIME'				=> 'From',
	'HOW_MANY_PEOPLE'		=> 'Quick Headcount',
	'INVALID_EVENT'			=> 'The event you are trying to view does not exist.',
	'INVITE_INFO'			=> 'Invited',
	'OCCURS_EVERY'			=> 'Occurs every',
	'RECURRING_EVENT_CASE_1_STR'    => '%1$s Day of %4$s - every %5$s Year(s)',
	'RECURRING_EVENT_CASE_2_STR'    => '%3$s %2$s of %4$s - every %5$s Year(s)',
	'RECURRING_EVENT_CASE_3_STR'    => '%3$s %2$s of full weeks in %4$s - every %5$s Year(s)',
	'RECURRING_EVENT_CASE_3b_STR'    => '%2$s of first partial week in %4$s - every %5$s Year(s)',
	'RECURRING_EVENT_CASE_4_STR'    => '%3$s from last %2$s of %4$s - every %5$s Year(s)',
	'RECURRING_EVENT_CASE_5_STR'    => '%3$s from last %2$s of full weeks in %4$s - every %5$s Year(s)',
	'RECURRING_EVENT_CASE_5b_STR'    => '%2$s of last partial week in %4$s - every %5$s Year(s)',
	'RECURRING_EVENT_CASE_6_STR'    => '%1$s Day of month - every %5$s Month(s)',
	'RECURRING_EVENT_CASE_7_STR'    => '%3$s %2$s of month - every %5$s Month(s)',
	'RECURRING_EVENT_CASE_8_STR'    => '%3$s %2$s of full weeks in month - every %5$s Month(s)',
	'RECURRING_EVENT_CASE_8b_STR'    => '%2$s of first partial week in month - every %5$s Month(s)',
	'RECURRING_EVENT_CASE_9_STR'    => '%3$s from last %2$s of month - every %5$s Month(s)',
	'RECURRING_EVENT_CASE_10_STR'    => '%3$s from last %2$s of full weeks in month - every %5$s Month(s)',
	'RECURRING_EVENT_CASE_10b_STR'    => '%2$s of last partial week in month - every %5$s Month(s)',
	'RECURRING_EVENT_CASE_11_STR'    => '%2$s - every %5$s Week(s)',
	'RECURRING_EVENT_CASE_12_STR'    => 'Every %5$s Day(s)',
	'LOCAL_DATE_FORMAT'		=> '%1$s %2$s, %3$s',
	'MAYBE'					=> 'Maybe',
	'MONTH'					=> 'Month',
	'MONTH_OF'				=> 'Month of ',
	'MY_EVENTS'				=> 'My Events',
	'NEW_EVENT'				=> 'New Event',
	'NO_EVENTS_TODAY'		=> 'There are no events scheduled for this day.',
	'PAGE_TITLE'			=> 'Calendar',
	'PM'					=> 'PM',
	'PRIVATE_EVENT'			=> 'This event is private.  You must be invited and logged in to view this event.',
	'TO_TIME'				=> 'To',
	'UPCOMING_EVENTS'		=> 'Upcoming Events',
	'USER_CANNOT_VIEW_EVENT'=> 'You do not have permission to view this event.',
	'WATCH_CALENDAR_TURN_ON'	=> 'Watch the calendar',
	'WATCH_CALENDAR_TURN_OFF'	=> 'Stop watching the calendar',
	'WATCH_EVENT_TURN_ON'	=> 'Watch this event',
	'WATCH_EVENT_TURN_OFF'	=> 'Stop watching this event',
	'WEEK'					=> 'Week',
	'WEEK_OF'				=> 'Week of ',
	'ZEROTH_FROM'			=> '0th from ',
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

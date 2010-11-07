<?php
/**
*
* permissions_calendar [English]
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
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

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

/**
*	MODDERS PLEASE NOTE
*
*	You are able to put your permission sets into a separate file too by
*	prefixing the new file with permissions_ and putting it into the acp
*	language folder.
*
*	An example of how the file could look like:
*
*	<code>
*
*	if (empty($lang) || !is_array($lang))
*	{
*		$lang = array();
*	}
*
*	// Adding new category
*	$lang['permission_cat']['bugs'] = 'Bugs';
*
*	// Adding new permission set
*	$lang['permission_type']['bug_'] = 'Bug Permissions';
*
*	// Adding the permissions
*	$lang = array_merge($lang, array(
*		'acl_bug_view'		=> array('lang' => 'Can view bug reports', 'cat' => 'bugs'),
*		'acl_bug_post'		=> array('lang' => 'Can post bugs', 'cat' => 'post'), // Using a phpBB category here
*	));
*
*	</code>
*/

// Adding new category
$lang['permission_cat']['raidplanner'] = 'Raidplanner';

// Adding new permission set
$lang['permission_type']['raidplanner_'] = 'Raidplanner Permissions';


// User Permissions
$lang = array_merge($lang, array(

	// view raid participation
	'acl_u_raidplanner_view_headcount'			=> array('lang' => 'Can view the headcount for events created by other users', 'cat' => 'raidplanner'),
	
	// allows viewing raids
	'acl_u_raidplanner_view_events'			=> array('lang' => 'Can view events', 'cat' => 'raidplanner'),

	// view rsvps
	'acl_u_raidplanner_view_detailed_rsvps'	=> array('lang' => 'Can view the detailed rsvps for events created by other users', 'cat' => 'raidplanner'),

	// allows creating raids
	'acl_u_raidplanner_create_events'			=> array('lang' => 'Can create events', 'cat' => 'raidplanner'),
	
	// allows public events where every member can subscribe 
	'acl_u_raidplanner_create_public_events'	=> array('lang' => 'Can create public events', 'cat' => 'raidplanner'),
	
	// allows group events where only usergroups can subscribe
	'acl_u_raidplanner_create_group_events'	=> array('lang' => 'Can create group events', 'cat' => 'raidplanner'),

	// allows private events - only for you - eg hairdresser
	'acl_u_raidplanner_create_private_events'	=> array('lang' => 'Can create private events', 'cat' => 'raidplanner'),

	// means that every member *must* say yes or no whether to attend on next login
	'acl_u_raidplanner_track_rsvps'			=> array('lang' => 'Can create events with attendance tracking', 'cat' => 'raidplanner'),

	// means that you can create a raid with non members
	'acl_u_raidplanner_allow_guests'			=> array('lang' => 'Can create events where participants are allowed to bring non-member friends', 'cat' => 'raidplanner'),

	// can create events that recur
	'acl_u_raidplanner_create_recurring_events' => array('lang' => 'Can create recurring events', 'cat' => 'raidplanner'),
	
	// allows deleting raids
	'acl_u_raidplanner_delete_events'			=> array('lang' => 'Can delete events', 'cat' => 'raidplanner'),

	// allows editing raids that you created
	'acl_u_raidplanner_edit_events'			=> array('lang' => 'Can edit events', 'cat' => 'raidplanner'),

));

// Moderator Permissions
$lang = array_merge($lang, array(
	'acl_m_raidplanner_edit_other_users_events'	=> array('lang' => 'Can edit events created by other users', 'cat' => 'raidplanner'),
	'acl_m_raidplanner_delete_other_users_events'	=> array('lang' => 'Can delete events created by other users', 'cat' => 'raidplanner'),
	'acl_m_raidplanner_edit_other_users_rsvps'	=> array('lang' => 'Can edit responses created by other users', 'cat' => 'raidplanner'),
));

// Admin Permissions
$lang = array_merge($lang, array(
	'acl_a_raid_config'		=> array('lang' => 'Can manage ACP Raidplanner settings and event types', 'cat' => 'raidplanner'),
));

?>

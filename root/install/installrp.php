<?php
/**
 * Raidplanner installer
 * @package bbDkp-installer
 * @author sajaki9@gmail.com
 * @copyright (c) 2010 bbDkp <http://code.google.com/p/bbdkp/>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * 
 */

define('UMIL_AUTO', true);
define('IN_PHPBB', true);
define('ADMIN_START', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();

// We only allow a founder install this MOD
if ($user->data['user_type'] != USER_FOUNDER)
{
    if ($user->data['user_id'] == ANONYMOUS)
    {
        login_box('', 'LOGIN');
    }

    trigger_error('NOT_AUTHORISED', E_USER_WARNING);
}

if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
    trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}

if (!file_exists($phpbb_root_path . 'install/installrp.' . $phpEx))
{
    trigger_error('Warning! Install directory has wrong name. it must be \'install\'. Please rename it and launch again.', E_USER_WARNING);
}


// The name of the mod to be displayed during installation.
$mod_name = 'Raidplanner';

/*
* The name of the config variable which will hold the currently installed version
* You do not need to set this yourself, UMIL will handle setting and updating the version itself.
*/
$version_config_name = 'bbdkp_plugin_rp_version';

/*
* The language file which will be included when installing
*/
$language_file = 'mods/raidplanner_lang';

/*
* Optionally we may specify our own logo image to show in the upper corner instead of the default logo.
* $phpbb_root_path will get prepended to the path specified
* Image height should be 50px to prevent cut-off or stretching.
*/
//$logo_img = 'images/bbdkp.png';

/*
* Run Options 
*/
$options = array(
);

/*
* The array of versions and actions within each.
* You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
*
* You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
* The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
*/

/***************************************************************
 * 
 * Welcome to the raidplanner installer
 * 
****************************************************************/
$versions = array(
    
    '0.0.1'    => array(

		'module_add' => array(
            array('acp', 0, 'ACP_CAT_RPLAN'),
            array('acp', 'ACP_CAT_RPLAN', 'ACP_RPLAN_MAINPAGE'),
            array('acp', 'ACP_RPLAN_MAINPAGE', array(
           		 'module_basename' => 'raidplanner',
            	 'modes'           => array('rp_settings') ,
        		),
        	 )), 
     	
      	// raid permission
	   'permission_add' => array(
            /* admin */
        	 array('a_raid_config', true),
			/* mod */
            array('m_calendar_edit_other_users_events', true),
            array('m_calendar_delete_other_users_events', true),
            array('m_calendar_edit_other_users_rsvps', true),
            /* user */
            array('u_raidplanner_view_headcount', true),
            array('u_u_raidplanner_view_events', true),
            array('u_raidplanner_view_detailed_rsvps', true),
            array('u_raidplanner_create_events', true),
            array('u_raidplanner_create_public_events', true),
            array('u_raidplanner_create_group_events', true),
            array('u_raidplanner_create_private_events', true),
            array('u_raidplanner_track_rsvps', true),
            array('u_raidplanner_allow_guests', true),
            array('u_raidplanner_create_recurring_events', true),
            array('u_raidplanner_edit_events', true),
            array('u_raidplanner_delete_events', true),
      	),
      	
		  // Assign default permissions 
        'permission_set' => array(
      	
			//may configure raidplanner
			array('ROLE_ADMIN_FULL', 'a_raid_config'),
			array('ROLE_ADMIN_FORUM', 'a_raid_config'),
			array('ROLE_ADMIN_STANDARD', 'a_raid_config'),
			array('ROLE_ADMIN_USERGROUP', 'a_raid_config'),
			array('ROLE_MOD_FULL', 'a_raid_config'),
			array('ROLE_MOD_QUEUE', 'a_raid_config'),
			array('ROLE_MOD_SIMPLE', 'a_raid_config'),
			array('ROLE_MOD_STANDARD', 'a_raid_config'),
			
			
			/* moderator pemissions */
			// allows editing other peoples events
			array('ROLE_ADMIN_FULL', 'm_calendar_edit_other_users_events'),
			array('ROLE_ADMIN_FORUM', 'm_calendar_edit_other_users_events'),
			array('ROLE_ADMIN_STANDARD', 'm_calendar_edit_other_users_events'),
			array('ROLE_ADMIN_USERGROUP', 'm_calendar_edit_other_users_events'),
			array('ROLE_MOD_FULL', 'm_calendar_edit_other_users_events'),
			array('ROLE_MOD_QUEUE', 'm_calendar_edit_other_users_events'),
			array('ROLE_MOD_SIMPLE', 'm_calendar_edit_other_users_events'),
			array('ROLE_MOD_STANDARD', 'm_calendar_edit_other_users_events'),
				
			
			// allows deleting other peoples events
			array('ROLE_ADMIN_FULL', 'm_calendar_delete_other_users_events'),
			array('ROLE_ADMIN_FORUM', 'm_calendar_delete_other_users_events'),
			array('ROLE_ADMIN_STANDARD', 'm_calendar_delete_other_users_events'),
			array('ROLE_ADMIN_USERGROUP', 'm_calendar_delete_other_users_events'),
			array('ROLE_MOD_FULL', 'm_calendar_delete_other_users_events'),
			array('ROLE_MOD_QUEUE', 'm_calendar_delete_other_users_events'),
			array('ROLE_MOD_SIMPLE', 'm_calendar_delete_other_users_events'),
			array('ROLE_MOD_STANDARD', 'm_calendar_delete_other_users_events'),
							
			
			
			// allows editing other peoples rsvp
			array('ROLE_ADMIN_FULL', 'm_calendar_edit_other_users_rsvps'),
			array('ROLE_ADMIN_FORUM', 'm_calendar_edit_other_users_rsvps'),
			array('ROLE_ADMIN_STANDARD', 'm_calendar_edit_other_users_rsvps'),
			array('ROLE_ADMIN_USERGROUP', 'm_calendar_edit_other_users_rsvps'),
			array('ROLE_MOD_FULL', 'm_calendar_edit_other_users_rsvps'),
			array('ROLE_MOD_QUEUE', 'm_calendar_edit_other_users_rsvps'),
			array('ROLE_MOD_SIMPLE', 'm_calendar_edit_other_users_rsvps'),
			array('ROLE_MOD_STANDARD', 'm_calendar_edit_other_users_rsvps'),			
			
			
			/*user permissions */
			// view raid participation		
            array('ROLE_ADMIN_FULL', 'u_raidplanner_view_headcount'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_view_headcount'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_view_headcount'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_view_headcount'),
			array('ROLE_MOD_FULL', 'u_raidplanner_view_headcount'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_view_headcount'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_view_headcount'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_view_headcount'),
			array('ROLE_USER_FULL', 'u_raidplanner_view_headcount'),
			
			// allows viewing raids
			array('ROLE_ADMIN_FULL', 'u_u_raidplanner_view_events'),
			array('ROLE_ADMIN_FORUM', 'u_u_raidplanner_view_events'),
			array('ROLE_ADMIN_STANDARD', 'u_u_raidplanner_view_events'),
			array('ROLE_ADMIN_USERGROUP', 'u_u_raidplanner_view_events'),
			array('ROLE_MOD_FULL', 'u_u_raidplanner_view_events'),
			array('ROLE_MOD_QUEUE', 'u_u_raidplanner_view_events'),
			array('ROLE_MOD_SIMPLE', 'u_u_raidplanner_view_events'),
			array('ROLE_MOD_STANDARD', 'u_u_raidplanner_view_events'),
			array('ROLE_USER_FULL', 'u_u_raidplanner_view_events'),
			
			// allows viewing who rsvp back
			array('ROLE_ADMIN_FULL', 'u_raidplanner_view_detailed_rsvps'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_view_detailed_rsvps'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_view_detailed_rsvps'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_view_detailed_rsvps'),
			array('ROLE_MOD_FULL', 'u_raidplanner_view_detailed_rsvps'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_view_detailed_rsvps'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_view_detailed_rsvps'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_view_detailed_rsvps'),
			array('ROLE_USER_FULL', 'u_raidplanner_view_detailed_rsvps'),
			
			// allows creating raids
			array('ROLE_ADMIN_FULL', 'u_raidplanner_create_events'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_create_events'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_create_events'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_create_events'),
			array('ROLE_MOD_FULL', 'u_raidplanner_create_events'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_create_events'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_create_events'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_create_events'),
			array('ROLE_USER_FULL', 'u_raidplanner_create_events'),	
					
			// allows public events where every member can subscribe 
			array('ROLE_ADMIN_FULL', 'u_raidplanner_create_public_events'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_create_public_events'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_create_public_events'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_create_public_events'),
			array('ROLE_MOD_FULL', 'u_raidplanner_create_public_events'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_create_public_events'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_create_public_events'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_create_public_events'),
				
			
			// allows group events where only usergroups can subscribe
			array('ROLE_ADMIN_FULL', 'u_raidplanner_create_group_events'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_create_group_events'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_create_group_events'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_create_group_events'),
			array('ROLE_MOD_FULL', 'u_raidplanner_create_group_events'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_create_group_events'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_create_group_events'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_create_group_events'),
			
			
			// allows private events - only for you - eg hairdresser
			array('ROLE_ADMIN_FULL', 'u_raidplanner_create_private_events'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_create_private_events'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_create_private_events'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_create_private_events'),
			array('ROLE_MOD_FULL', 'u_raidplanner_create_private_events'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_create_private_events'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_create_private_events'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_create_private_events'),
			array('ROLE_USER_FULL', 'u_raidplanner_create_private_events'),				
			
			// means that every member *must* say yes or no whether to attend on next login
			array('ROLE_ADMIN_FULL', 'u_raidplanner_track_rsvps'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_track_rsvps'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_track_rsvps'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_track_rsvps'),
			array('ROLE_MOD_FULL', 'u_raidplanner_track_rsvps'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_track_rsvps'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_track_rsvps'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_track_rsvps'),
								

			// means that you can create a raid with non guild members
			array('ROLE_ADMIN_FULL', 'u_raidplanner_allow_guests'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_allow_guests'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_allow_guests'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_allow_guests'),
			array('ROLE_MOD_FULL', 'u_raidplanner_allow_guests'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_allow_guests'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_allow_guests'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_allow_guests'),
							

			// can create events that recur
			array('ROLE_ADMIN_FULL', 'u_raidplanner_create_recurring_events'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_create_recurring_events'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_create_recurring_events'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_create_recurring_events'),
			array('ROLE_MOD_FULL', 'u_raidplanner_create_recurring_events'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_create_recurring_events'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_create_recurring_events'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_create_recurring_events'),
							
			
			// allows editing raids
			array('ROLE_ADMIN_FULL', 'u_raidplanner_edit_events'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_edit_events'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_edit_events'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_edit_events'),
			array('ROLE_MOD_FULL', 'u_raidplanner_edit_events'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_edit_events'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_edit_events'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_edit_events'),
				
					
			// allows deleting your own events
			array('ROLE_ADMIN_FULL', 'u_raidplanner_delete_events'),
			array('ROLE_ADMIN_FORUM', 'u_raidplanner_delete_events'),
			array('ROLE_ADMIN_STANDARD', 'u_raidplanner_delete_events'),
			array('ROLE_ADMIN_USERGROUP', 'u_raidplanner_delete_events'),
			array('ROLE_MOD_FULL', 'u_raidplanner_delete_events'),
			array('ROLE_MOD_QUEUE', 'u_raidplanner_delete_events'),
			array('ROLE_MOD_SIMPLE', 'u_raidplanner_delete_events'),
			array('ROLE_MOD_STANDARD', 'u_raidplanner_delete_events'),
			array('ROLE_USER_FULL', 'u_raidplanner_delete_events'),	

			/* end user pemissions */ 

						
			
        ),

        'custom' => array('bbdkp_caches'),
        
        ),
     
);

// Include the UMIF Auto file and everything else will be handled automatically.
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);

/**************************************
 *  
 * global function for clearing cache
 * 
 */
function bbdkp_caches($action, $version)
{
    global $db, $table_prefix, $umil, $bbdkp_table_prefix;
    
    $umil->cache_purge();
    $umil->cache_purge('imageset');
    $umil->cache_purge('template');
    $umil->cache_purge('theme');
    $umil->cache_purge('auth');
    
    return 'UMIL_CACHECLEARED';
}


?>
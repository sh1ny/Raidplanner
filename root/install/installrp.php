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
$version_config_name = 'bbdkp_raidplanner';

/*
* The language file which will be included when installing
*/
$language_file = 'mods/raidplanner';

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
			array('ROLE_ADMIN_FULL', 'm_raidplanner_edit_other_users_events'),
			array('ROLE_ADMIN_FORUM', 'm_raidplanner_edit_other_users_events'),
			array('ROLE_ADMIN_STANDARD', 'm_raidplanner_edit_other_users_events'),
			array('ROLE_ADMIN_USERGROUP', 'm_raidplanner_edit_other_users_events'),
			array('ROLE_MOD_FULL', 'm_raidplanner_edit_other_users_events'),
			array('ROLE_MOD_QUEUE', 'm_raidplanner_edit_other_users_events'),
			array('ROLE_MOD_SIMPLE', 'm_raidplanner_edit_other_users_events'),
			array('ROLE_MOD_STANDARD', 'm_raidplanner_edit_other_users_events'),
				
			
			// allows deleting other peoples events
			array('ROLE_ADMIN_FULL', 'm_raidplanner_delete_other_users_events'),
			array('ROLE_ADMIN_FORUM', 'm_raidplanner_delete_other_users_events'),
			array('ROLE_ADMIN_STANDARD', 'm_raidplanner_delete_other_users_events'),
			array('ROLE_ADMIN_USERGROUP', 'm_raidplanner_delete_other_users_events'),
			array('ROLE_MOD_FULL', 'm_raidplanner_delete_other_users_events'),
			array('ROLE_MOD_QUEUE', 'm_raidplanner_delete_other_users_events'),
			array('ROLE_MOD_SIMPLE', 'm_raidplanner_delete_other_users_events'),
			array('ROLE_MOD_STANDARD', 'm_raidplanner_delete_other_users_events'),
							
			// allows editing other peoples rsvp
			array('ROLE_ADMIN_FULL', 'm_raidplanner_edit_other_users_rsvps'),
			array('ROLE_ADMIN_FORUM', 'm_raidplanner_edit_other_users_rsvps'),
			array('ROLE_ADMIN_STANDARD', 'm_raidplanner_edit_other_users_rsvps'),
			array('ROLE_ADMIN_USERGROUP', 'm_raidplanner_edit_other_users_rsvps'),
			array('ROLE_MOD_FULL', 'm_raidplanner_edit_other_users_rsvps'),
			array('ROLE_MOD_QUEUE', 'm_raidplanner_edit_other_users_rsvps'),
			array('ROLE_MOD_SIMPLE', 'm_raidplanner_edit_other_users_rsvps'),
			array('ROLE_MOD_STANDARD', 'm_raidplanner_edit_other_users_rsvps'),			
			
			
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
        
		'module_add' => array(
            array('acp', 0, 'ACP_CAT_RAIDPLANNER'),
            
            array('acp', 'ACP_CAT_RAIDPLANNER', 'ACP_RAIDPLANNER'),
            
            array('acp', 'ACP_RAIDPLANNER', array(
           		 'module_basename' => 'raidplanner',
            	 'modes'           => array('rp_settings') ,
        		),
        	 )), 
     
        	 
	      // adding some configs
		'config_add' => array(
			array('rp_first_day_of_week', 0, true),
			array('rp_index_display_week', 0, true),
			array('rp_index_display_next_events', 5),
			array('rp_hour_mode', 12),
			array('rp_display_truncated_name', 0, true),
			array('rp_prune_frequency', 0, true),
			array('rp_last_prune', 0, true),
			array('rp_prune_limit', 2592000, true),
			array('rp_display_hidden_groups', 0, true),
			array('rp_date_format', 'M d, Y', true),
			array('rp_date_time_format', 'M d, Y h:i a', true),
			array('rp_disp_events_only_on_start', 0, true),
			array('rp_populate_frequency', 86400, true),
			array('rp_last_populate', 0, true),
			array('rp_populate_limit', 2592000, true),
			),
        	 
			//adding some tables
			'table_add' => array(
			
			array( 'phpbb_rp_event_types', array(
			            'COLUMNS'			=> array(
					   'etype_id' 		=> array('INT:8', NULL, 'auto_increment' ),
                       'etype_index'	=> array('INT:8', 0),
		  			   'etype_full_name' 	=> array('VCHAR_UNI:255', ''),
		  			   'etype_display_name' => array('VCHAR_UNI:255', 0),
					   'etype_color'   	=> array('VCHAR:8', 0),
					   'etype_image'   	=> array('VCHAR:255', ''),
					),
                    'PRIMARY_KEY'	=> array('etype_id')),
				),  
					
			array( 'phpbb_rp_events', array(
                    'COLUMNS'			=> array(
                       'event_id'			=> array('INT:8', NULL, 'auto_increment' ),
					   'etype_id' 			=> array('INT:8', 0),
		  			   'sort_timestamp' 	=> array('BINT', 0),
		  			   'event_start_time' 	=> array('BINT', 0),
			 		   'event_end_time' 	=> array('BINT', 0),
					   'event_all_day'   	=> array('UINT', 0),
					   'event_day'   		=> array('VCHAR:10', ''),
					   'event_subject'   	=> array('VCHAR_UNI:255', ''),
					   'event_body'   		=> array('MTEXT', ''),
					   'poster_id'		 	=> array('UINT', 0),
					   'event_access_level' => array('BOOL', 0),
					   'group_id' 			=> array('UINT', 0),
					   'group_id_list' 		=> array('VCHAR_UNI:255', ''),
					   'enable_bbcode' 		=> array('BOOL', 1),
					   'enable_smilies' 	=> array('BOOL', 1),
					   'enable_magic_url' 	=> array('BOOL', 1),
					   'bbcode_bitfield' 	=> array('VCHAR:255', ''),
					   'bbcode_uid' 		=> array('VCHAR:8', ''),
					   'track_rsvps' 		=> array('BOOL', 0),
					   'allow_guests' 		=> array('BOOL', 0),
					   'rsvp_yes' 			=> array('UINT', 0),
					   'rsvp_no' 			=> array('UINT', 0),
					   'rsvp_maybe' 		=> array('UINT', 0),
					   'recurr_id' 			=> array('UINT', 0),
					),
                    'PRIMARY_KEY'	=> array('event_id')), 
              ),
              
			array('phpbb_rp_recurring_events', array(
			         'COLUMNS'			=> array(
			           'recurr_id'			=> array('INT:8', NULL, 'auto_increment' ),
					   'etype_id' 			=> array('INT:8', 0),
					   'frequency' 			=> array('USINT', 1),
			 		   'frequency_type' 	=> array('USINT', 0),
					   'first_occ_time' 	=> array('BINT', 0),
					   'final_occ_time'   	=> array('BINT', 0),
					   'event_all_day'   	=> array('USINT', 0),
					   'event_duration'   	=> array('BINT', 0),
					   'week_index'   		=> array('USINT', 0),
					   'first_day_of_week'	=> array('USINT', 0),
					   'last_calc_time' 	=> array('BINT', 0),
					   'next_calc_time' 	=> array('BINT', 0),
					   'event_subject' 		=> array('VCHAR_UNI:255', ''),
					   'event_body' 		=> array('MTEXT', ''),
					   'poster_id' 			=> array('UINT', 0),
					   'poster_timezone' 	=> array('DECIMAL', 0.00),
					   'poster_dst' 		=> array('BOOL', 0),
					   'event_access_level' => array('BOOL', 0),
					   'group_id' 			=> array('UINT', 0),
					   'group_id_list' 		=> array('VCHAR_UNI:255', ''),
					   'enable_bbcode' 		=> array('BOOL', 1),
					   'enable_smilies' 	=> array('BOOL', 1),
					   'enable_magic_url' 	=> array('BOOL', 1),
					   'bbcode_bitfield' 	=> array('VCHAR:255', ''),
					   'bbcode_uid' 		=> array('VCHAR:8', ''),
					   'track_rsvps' 		=> array('BOOL', 0),
					   'allow_guests' 		=> array('BOOL', 0),
					),
			             'PRIMARY_KEY'	=> array('recurr_id')), 
			        ),
                            
			array( 'phpbb_rp_rsvps', array(
                    'COLUMNS'			=> array(
                       'rsvp_id'			=> array('INT:8', NULL, 'auto_increment' ),
					   'event_id' 			=> array('INT:8', 0),
		  			   'poster_id' 			=> array('INT:8', 0),
		  			   'poster_name' 		=> array('VCHAR:255', ''),
			 		   'poster_colour' 		=> array('VCHAR:6', ''),
					   'poster_ip'   		=> array('VCHAR:40', ''),
					   'post_time'   		=> array('TIMESTAMP', 0),
					   'rsvp_val'   		=> array('BOOL', 0),
					   'rsvp_count'   		=> array('USINT', 0),
					   'rsvp_detail'		=> array('MTEXT', ''),
					   'bbcode_bitfield' 	=> array('VCHAR:255', ''),
					   'bbcode_uid' 		=> array('VCHAR:8', ''),
					   'bbcode_options' 	=> array('UINT', 7),
					),
                    'PRIMARY_KEY'	=> array('rsvp_id'),
					 'KEYS'            => array(
    				     'event_id'   => array('INDEX', 'event_id'),
				 		 'poster_id'  => array('INDEX', 'poster_id'), 
						 'eid_post_time' => array('INDEX', array('event_id', 'post_time'))
						)
					)),
			
			array( 'phpbb_rp_events_watch', array(
                    'COLUMNS'			=> array(
					   'event_id' 			=> array('INT:8', 0),
		  			   'user_id' 			=> array('INT:8', 0),
		  			   'notify_status' 		=> array('BOOL', 0),
			 		   'track_replies' 		=> array('BOOL', 0),
					),
					 'KEYS'       => array(
    				     'event_id'     => array('INDEX', 'event_id'),
				 		 'user_id'  	=> array('INDEX', 'user_id'), 
						 'notify_stat'  => array('INDEX', 'notify_status'),
						)
					)),
					
			array( 'phpbb_rp_calendar_watch', array(
                    'COLUMNS'			=> array(
		  			   'user_id' 			=> array('INT:8', 0),
		  			   'notify_status' 		=> array('BOOL', 0),
					),
					 'KEYS'       => array(
				 		 'user_id'  	=> array('INDEX', 'user_id'), 
						 'notify_stat'  => array('INDEX', 'notify_status'),
						)
					)),
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
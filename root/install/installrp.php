<?php
/**
 * Raidplanner installer
 * @package bbDkp-installer
 * @author sajaki9@gmail.com
 * @copyright (c) 2010 bbDkp <http://code.google.com/p/bbdkp/>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: installrp.php 1758 2010-11-22 20:24:53Z sajaki9 $
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
            array('m_raidplanner_edit_other_users_events', true),
            array('m_raidplanner_delete_other_users_events', true),
            array('m_raidplanner_edit_other_users_signups', true),
            
            /* user */
            array('u_raidplanner_view_events', true),
            array('u_raidplanner_view_headcount', true),
            
            array('u_raidplanner_signup_events', true), 		            
            array('u_raidplanner_create_events', true),
            array('u_raidplanner_create_public_events', true),
            array('u_raidplanner_create_group_events', true),
            array('u_raidplanner_create_private_events', true),
            array('u_raidplanner_create_recurring_events', true),
            array('u_raidplanner_edit_events', true),
            array('u_raidplanner_delete_events', true),

      	),
      	
		  // Assign default permissions 
        'permission_set' => array(
      	
      		/*set admin permissions */
			//may configure raidplanner
			array('ADMINISTRATORS', 'a_raid_config', 'group', true),

			/*set moderator pemissions */
			// allows editing other peoples events
			array('ADMINISTRATORS', 'm_raidplanner_edit_other_users_events', 'group', true),
			array('GLOBAL_MODERATORS', 'm_raidplanner_edit_other_users_events', 'group', true),

			// allows deleting other peoples events
			array('ADMINISTRATORS', 'm_raidplanner_delete_other_users_events', 'group', true),
			array('GLOBAL_MODERATORS', 'm_raidplanner_edit_other_users_events', 'group', true),
							
			// allows editing other peoples signup
			array('ADMINISTRATORS', 'm_raidplanner_edit_other_users_signups', 'group', true),
			array('GLOBAL_MODERATORS', 'm_raidplanner_edit_other_users_signups', 'group', true),
			
			/*set user permissions */
			// allows viewing raids
			array('ADMINISTRATORS', 'u_raidplanner_view_events', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_view_events', 'group', true),
			array('REGISTERED', 'u_raidplanner_view_events', 'group', true),
			array('NEWLY_REGISTERED', 'u_raidplanner_view_events', 'group', true),
			array('GUESTS', 'u_raidplanner_view_events', 'group', true),
			
			// view raid participation		
			array('ADMINISTRATORS', 'u_raidplanner_view_headcount', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_view_headcount', 'group', true),
			array('REGISTERED', 'u_raidplanner_view_headcount', 'group', true),
			
			// allows signing up for an event or raid
			array('ADMINISTRATORS', 'u_raidplanner_signup_events', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_signup_events', 'group', true),
			array('REGISTERED', 'u_raidplanner_signup_events', 'group', true),
			array('NEWLY_REGISTERED', 'u_raidplanner_signup_events', 'group', true),
			array('GUESTS', 'u_raidplanner_signup_events', 'group', true),
			
			// allows creating raids
			array('ADMINISTRATORS', 'u_raidplanner_create_events', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_create_events', 'group', true),
			array('REGISTERED', 'u_raidplanner_create_events', 'group', true),
					
			// allows public events where every member can subscribe 
			array('ADMINISTRATORS', 'u_raidplanner_create_public_events', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_create_public_events', 'group', true),
			array('REGISTERED', 'u_raidplanner_create_public_events', 'group', true),
			
			// allows group events where only usergroups can subscribe
			array('ADMINISTRATORS', 'u_raidplanner_create_group_events', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_create_group_events', 'group', true),
			array('REGISTERED', 'u_raidplanner_create_group_events', 'group', true),
			
			// allows private events - only for you - eg hairdresser
			array('ADMINISTRATORS', 'u_raidplanner_create_private_events', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_create_private_events', 'group', true),
			array('REGISTERED', 'u_raidplanner_create_private_events', 'group', true),

			// can create events that recur
			array('ADMINISTRATORS', 'u_raidplanner_create_recurring_events', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_create_recurring_events', 'group', true),
			array('REGISTERED', 'u_raidplanner_create_recurring_events', 'group', true),
			
			// allows editing raids
			array('ADMINISTRATORS', 'u_raidplanner_edit_events', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_edit_events', 'group', true),
			array('REGISTERED', 'u_raidplanner_edit_events', 'group', true),
					
			// allows deleting your own events
			array('ADMINISTRATORS', 'u_raidplanner_delete_events', 'group', true),
			array('GLOBAL_MODERATORS', 'u_raidplanner_delete_events', 'group', true),
			array('REGISTERED', 'u_raidplanner_delete_events', 'group', true),
			

        ),
        
		'module_add' => array(
            /* hook acp module to dkp_raids */ 
        	array('acp', 'ACP_DKP_RAIDS', array(
           		 'module_basename' => 'raidplanner',
            	 'modes'           => array('rp_settings') ,
        		),
        	 ), 
        	 
            // hook ucp module to ucp_dkp
			array('ucp', 'UCP_DKP', array(
					'module_basename'   => 'planner',
					'module_mode'       => array('raidplanner_registration', 'raidplanner_myevents') ,
				),
			),
        ), 
     
        	 
	      // adding some configs
		'config_add' => array(
			array('rp_first_day_of_week', 0, true),
			array('rp_index_display_week', 0, true),
			array('rp_index_display_next_events', 5),
			array('rp_hour_mode', 24),
			array('rp_display_truncated_name', 0, true),
			array('rp_prune_frequency', 0, true),
			array('rp_last_prune', 0, true),
			array('rp_prune_limit', 31536000, true),
			array('rp_display_hidden_groups', 0, true),
			array('rp_time_format', 'H:i', true),
			array('rp_date_format', 'M d, Y', true),
			array('rp_date_time_format', 'M d, Y H:i', true),
			array('rp_disp_events_only_on_start', 0, true),
			array('rp_populate_frequency', 86400, true),
			array('rp_last_populate', 0, true),
			array('rp_populate_limit', 94608000, true),
			),
        	 
			//adding some tables
			'table_add' => array(
						
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
					   'track_signups' 		=> array('BOOL', 0),
					   'signup_yes' 		=> array('UINT', 0),
					   'signup_no' 			=> array('UINT', 0),
					   'signup_maybe' 		=> array('UINT', 0),
					   'recurr_id' 			=> array('UINT', 0),
					),
                    'PRIMARY_KEY'	=> array('event_id')), 
              ),

			array('phpbb_rp_recurring', array(
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
					   'track_signups' 		=> array('BOOL', 0),
					),
			             'PRIMARY_KEY'	=> array('recurr_id')), 
			        ),
                            
			array( 'phpbb_rp_signups', array(
                    'COLUMNS'			=> array(
                       'signup_id'			=> array('INT:8', NULL, 'auto_increment' ),
					   'event_id' 			=> array('INT:8', 0),
		  			   'poster_id' 			=> array('INT:8', 0),
		  			   'poster_name' 		=> array('VCHAR:255', ''),
			 		   'poster_colour' 		=> array('VCHAR:6', ''),
					   'poster_ip'   		=> array('VCHAR:40', ''),
					   'post_time'   		=> array('TIMESTAMP', 0),
					   'signup_val'   		=> array('BOOL', 0),
					   'signup_count'   	=> array('USINT', 0),
					   'signup_detail'		=> array('MTEXT', ''),
					   'bbcode_bitfield' 	=> array('VCHAR:255', ''),
					   'bbcode_uid' 		=> array('VCHAR:8', ''),
					   'bbcode_options' 	=> array('UINT', 7),
					),
                    'PRIMARY_KEY'	=> array('signup_id'),
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
					
			array( 'phpbb_rp_watch', array(
                    'COLUMNS'			=> array(
		  			   'user_id' 			=> array('INT:8', 0),
		  			   'notify_status' 		=> array('BOOL', 0),
					),
					 'KEYS'       => array(
				 		 'user_id'  	=> array('INDEX', 'user_id'), 
						 'notify_stat'  => array('INDEX', 'notify_status'),
						)
					)),
					
			array( 'phpbb_rp_eventroles', array(
                    'COLUMNS'			=> array(
                       'eventdet_id'		=> array('INT:8', NULL, 'auto_increment' ),
					   'event_id' 			=> array('INT:8', 0),
					   'role_id' 			=> array('INT:8', 0),
					   'role_needed' 		=> array('INT:8', 0),
					),
                    'PRIMARY_KEY'	=> array('eventdet_id')), 
              ),              

              array('phpbb_rp_roles' , array(
                    'COLUMNS'        => array(
                        'r_index'    		=> array('INT:8', NULL, 'auto_increment'),
                        'role_id'   		=> array('INT:8', 0),
                        'role_name'     	=> array('VCHAR_UNI', ''),
                    ),
                    'PRIMARY_KEY'    => 'r_index', 
                    'KEYS'         => array('role_id'    => array('UNIQUE', 'role_id')),                  
                    
                ),
            ),
		),

		 'table_row_insert'	=> array(
		// inserting roles
		array('phpbb_rp_roles',
           array(
           		  // ranged DPS
                  array(
                      'role_id' => 1,
                      'role_name' => 'Ranged DPS', 
                  		),
                  
           		  // Melee DPS                  
                  array(
                      'role_id' => 2,
                      'role_name' => 'Melee DPS', 
                  		),
                  
                  array(
                      'role_id' => 3,
                      'role_name' => 'Tank', 
                  		),
                  
                  array(
                      'role_id' => 4,
                      'role_name' => 'Healer', 
                  		),
                  
                  array(
                      'role_id' => 5,
                      'role_name' => 'Hybrid DPS/Heal', 
                  		),
                  
                  array(
                      'role_id' => 6,
                      'role_name' => 'Hybrid Tank/Heal', 
                  		),
           ))),
           
        'custom' => array('purgecaches'),
        
        ),
     
);

// Include the UMIF Auto file and everything else will be handled automatically.
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);

/**************************************
 *  
 * global function for clearing cache
 * 
 */
function purgecaches($action, $version)
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
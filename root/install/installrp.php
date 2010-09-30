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
$language_file = 'mods/dkp_rplanner';

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
$bbdkp_table_prefix = "bbeqdkp_";

/***************************************************************
 * 
 * Welcome to the bbtips installer
 * 
****************************************************************/

$versions = array(
    
    '0.0.1'    => array(

     // Lets add global config settings
	'config_add' => array(

		// source site
		array('rp_first_day_of_week', '0', true),
		array('rp_index_display_week', '0', true),		
		array('rp_index_display_next_events', '5', true),
		array('rp_hour_mode', '12', true),
		array('rp_display_truncated_name', '0', true),
		array('rp_prune_frequency', '0', true),
		array('rp_last_prune', '0', true),
		array('rp_prune_limit', '2592000', true),
		array('rp_display_hidden_groups', '0', true),
		array('rp_time_format', 'h:i a', true),
		array('rp_date_format', 'M d, Y', true),
		array('rp_date_time_format', 'M d, Y h:i a', true),
		array('rp_disp_events_only_on_start', '0', true),
		array('rp_populate_frequency', '86400', true),
		array('rp_last_populate', '0', true),
		array('rp_populate_limit', '2592000', true),		

	),

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
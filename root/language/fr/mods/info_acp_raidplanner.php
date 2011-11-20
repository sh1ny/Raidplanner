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

// Create the lang array if it does not already exist
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// Merge the following language entries into the lang array
$lang = array_merge($lang, array(
	'ACP_CAT_RAIDPLANNER' 				=> 'Raidplanner', //main tab 
	'ACP_RAIDPLANNER' 					=> 'Raidplanner', //category
	'ACP_RAIDPLANNER_SETTINGS'  		=> 'Raidplanner Settings', 	//module
	'ACP_RAIDPLANNER_SETTINGS_EXPLAIN' 	=> 'Ici vous pouvez configurer le Calendrier',
	'ACP_RAIDPLANNER_EVENTSETTINGS'		=> 'Evénements', //module
));

?>
<?php
/**
* Constants defined for raidplanner
* @package bbDkp.includes
* @version $Id$
* @copyright (c) 2009 bbDKP 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}
    
// TABLE DEFINITIONS
define('RP_EVENT_TYPES_TABLE',		$table_prefix . 'rp_event_types');
define('RP_EVENTS_TABLE',			$table_prefix . 'rp_events');
define('RP_RECURRING_EVENTS_TABLE',	$table_prefix . 'rp_recurring_events');
define('RP_RSVP_TABLE',				$table_prefix . 'rp_rsvps');
define('RP_EVENTS_WATCH',			$table_prefix . 'rp_events_watch');
define('RP_WATCH',					$table_prefix . 'rp_watch');

?>
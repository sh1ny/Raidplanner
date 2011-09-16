<?php
/**
*
* @author Sajaki
* @package bbDKP Raidplanner
* @copyright (c) 2011 Sajaki
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/


/**
 * @ignore
 */
if ( !defined('IN_PHPBB') OR !defined('IN_BBDKP') )
{
	exit;
}

class rpevents
{

	public $events = array();
	
	function __construct()
	{
		global $db; 
		
		$sql = 'SELECT * FROM ' . EVENTS_TABLE . ' ORDER BY event_id';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$this->events[$row['event_id']]['event_name'] = $row['event_name'];
			$this->events[$row['event_id']]['color'] = $row['event_color'];
			$this->events[$row['event_id']]['imagename'] = $row['event_imagename'];
			$this->events[$row['event_id']]['dkpid'] = $row['event_dkpid'];
		}
		$db->sql_freeresult($result);
		
	}
	
}

?>
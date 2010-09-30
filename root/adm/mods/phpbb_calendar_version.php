<?php
/**
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
* @package phpbb_calendar
*/
class phpbb_calendar_version
{
	function version()
	{
		return array(
			'author'	=> 'alightner',
			'title'		=> 'phpbb Calendar',
			'tag'		=> 'phpbb_calendar',
			'version'	=> '0.1.0',
			'file'		=> array('phpbbcalendarmod.com', 'updatecheck', 'mods.xml'),
		);
	}
}

?>
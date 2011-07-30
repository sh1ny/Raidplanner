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

// Include the base class
if (!class_exists('calendar'))
{
	require($phpbb_root_path . 'includes/bbdkp/raidplanner/calendar.' . $phpEx);
}

/**
 * implements a calendar frame
 *
 */
class rpframe extends calendar
{
	private $mode = '';
	
	/**
	 * 
	 */
	function __construct()
	{
		$this->mode="frame";
		parent::__construct($this->mode);
	}
	
	/**
	 * @see calendar::display()
	 * implements abstract method
	 */
	public function display()
	{	
		// 
		$this->displayCalframe();
	}
	
	/**
	 * Displays common Calendar elements, header message
	 * 
	 */
	private function displayCalframe()
	{
		
		global $config, $template, $db;
		
		$sql = 'SELECT announcement_msg, bbcode_uid, bbcode_bitfield, bbcode_options FROM ' . RP_RAIDPLAN_ANNOUNCEMENT;
		$db->sql_query($sql);
		$result = $db->sql_query($sql);
		while ( $row = $db->sql_fetchrow($result) )
		{
			$text = $row['announcement_msg'];
			$bbcode_uid = $row['bbcode_uid'];
			$bbcode_bitfield = $row['bbcode_bitfield'];
			$bbcode_options = $row['bbcode_options'];
		}
		
		$message = generate_text_for_display($text, $bbcode_uid, $bbcode_bitfield, $bbcode_options);
		
		$template->assign_vars(array(
			'S_PLANNER_RAIDFRAME'	=> true,
			'S_SHOW_WELCOME_MSG'	=> ($config ['rp_show_welcomemsg'] == 1) ? true : false,
			'WELCOME_MSG'		=> $message,
		));
	
	
	}
	
}

?>
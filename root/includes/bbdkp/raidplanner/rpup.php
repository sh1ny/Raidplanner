<?php
/**
*
* @author alightner
* @author Sajaki
* @package bbDKP Raidplanner
* @copyright (c) 2009 alightner
* @copyright (c) 2011 Sajaki : refactoring, adapting to bbdkp
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
 * implements a dayview
 *
 */
class rpup extends calendar
{
	private $mode = '';
	
	/**
	 * 
	 */
	function __construct($mode)
	{
		$this->mode = $mode;
		parent::__construct($this->mode);
	}
	
	/**
	 * @see calendar::display($x)
	 *
	 * @param int $x
	 */
	public function display()
	{
		$this->_display_next_raidplans();
	}
	
	/**
	 * displays the next x number of upcoming raidplans 
	 * 
	 *
	 * @param string $mode (up or next)
	 */
	private function _display_next_raidplans()
	{
		global $auth, $template, $phpEx, $phpbb_root_path;
	
		// get raid info
		if (!class_exists('raidplans'))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/raidplans.' . $phpEx);
		}
		
		$raidplans = new raidplans();
		$raidplan_output = array();
		
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			$raidplan_output = $raidplans->GetRaidinfo($this->date['month_no'], $this->date['day'], $this->date['year'], $this->group_options, $this->mode);
			foreach($raidplan_output as $raid )
			{
				$template->assign_block_vars('raidplans', $raid);
			}
		}
				
		$template->assign_vars(array(
			'S_PLANNER_UPCOMING'		=> true,
			'EVENT_COUNT'				=> sizeof($raidplan_output),
		));
		
		
	}
	
	
	
}

?>
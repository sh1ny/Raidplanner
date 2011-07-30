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
 * implements upcoming raids view
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
	 * to fix
	 *
	 * @param string $mode (up or next)
	 */
	private function _display_next_raidplans()
	{
		global $auth, $template, $phpEx, $phpbb_root_path;
	
		// get raid info
		if (!class_exists('rpraid'))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpraid.' . $phpEx);
		}
		$rpraid = new rpraid();
		$raiddays = $rpraid->GetRaiddaylist($this->Get1DoM($this->timestamp), $this->GetLDoM($this->timestamp) );
		
		$raidplan_output = array();
		// if can see raids
		if ( $auth->acl_get('u_raidplanner_view_raidplans') )
		{
			if(isset($raiddays) && is_array($raiddays))
			{
				// loop all days having raids			
				foreach ($raiddays as $raidday)
				{
					//raid(s) found get detail
					$raidplan_output = $rpraid->GetRaidinfo($this->date['month_no'], $this->date['day'], $this->date['year'], $this->group_options, $this->mode);
					foreach($raidplan_output as $raid )
					{
						$template->assign_block_vars('raidplans', $raid);
					}
				}
			}
			
		}

				
		$template->assign_vars(array(
			'S_PLANNER_UPCOMING'		=> true,
			'EVENT_COUNT'				=> sizeof($raidplan_output),
		));
		
		
	}
	
	
	
}

?>
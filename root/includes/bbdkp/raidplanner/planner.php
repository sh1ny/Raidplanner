<?php
/**
*
* @author alightner
* @author Sajaki
* @package bbDKP Raidplanner
* @copyright (c) 2009 alightner
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

$user->add_lang ( array ('mods/raidplanner'));

//get permissions
if ( !$auth->acl_get('u_raidplanner_view_raidplans') )
{
	trigger_error( 'USER_CANNOT_VIEW_RAIDPLAN' );
}

$view_mode = request_var('view', 'month');
$mode=request_var('mode', '');

// display header
include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpframe.' . $phpEx);
$cal = new rpframe();
$cal->display();

switch( $view_mode )
{
	case "raidplan":
		
		if (!class_exists('rpraid', false))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpraid.' . $phpEx);
		}
		$raidplan_id = request_var('hidden_raidplanid', request_var('calEid', 0));
		switch($mode)
		{
			case 'signup':
				if(isset($_POST['signmeup' . $raidplan_id]))
				{
					if (!class_exists('rpsignup', false))
					{
						include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpsignups.' . $phpEx);
					}
					$signup = new rpsignup();
					$signup->signup($raidplan_id);
					$raid = new rpraid($raidplan_id);
					$raid->display();
				}
				break;
			case 'delsign':
				if (!class_exists('rpsignup', false))
				{
					include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpsignups.' . $phpEx);
				}
				
				$signup_id = request_var('signup_id', 0);
				$signup = new rpsignup();
				$signup->deletesignup($signup_id);
				$raid = new rpraid($raidplan_id);
				$raid->display();
				break;
			case 'editsign':
				if (!class_exists('rpsignup', false))
				{
					include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpsignups.' . $phpEx);
				}
				
				$signup_id = request_var('signup_id', 0);
				$signup = new rpsignup();
				$signup->editsignupcomment($signup_id);
				$raid = new rpraid($raidplan_id);
				$raid->display();
				break;				
			case 'requeue':
				if (!class_exists('rpsignup', false))
				{
					include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpsignups.' . $phpEx);
				}
				$signup_id = request_var('signup_id', 0);
				$signup = new rpsignup();
				$signup->requeuesignup($signup_id);
				$raid = new rpraid($raidplan_id);
				$raid->display();
				break;		
			case 'confirm':
				if (!class_exists('rpsignup', false))
				{
					include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpsignups.' . $phpEx);
				}
				$signup_id = request_var('signup_id', 0);
				$signup = new rpsignup();
				$signup->confirmsignup($signup_id);
				$raid = new rpraid($raidplan_id);
				$raid->display();
				break;	
			case 'showadd':
				$raid = new rpraid($raidplan_id);
				$raid->showadd($cal, $raidplan_id);
				break;	
			case 'delete':
				$raid = new rpraid($raidplan_id);
				$raid->delete();
				break;			
			default:
				$raid = new rpraid($raidplan_id);
				$raid->display();
				break;
		}
		break;
   case "next":		
      // display upcoming raidplans
      $template_body = "calendar_next_raidplans_for_x_days.html";
      $daycount = request_var('daycount', 60 );
      $user_id = request_var('u', 0);
      if( $user_id == 0 )
      {
      	// display all raids
      	$cal->display_next_raidplans_for_x_days( $daycount );
      }
      else
      {
      	// display signed up raids
      	$cal->display_users_next_raidplans_for_x_days($daycount, $user_id);
      }
      $template->assign_vars(array(
		'S_PLANNER_UPCOMING'	=> true,
		));
      break;
	case "day":
		// display all of the raidplans on this day
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpday.' . $phpEx);
		$cal = new rpday();
		// display calendar
		$cal->display();		
		break;
	case "week":
		// display the entire week
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpweek.' . $phpEx);
		$cal = new rpweek();
		// display calendar
		$cal->display();
		break;
	case "month":
	default:	
		//display the entire month
		include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpmonth.' . $phpEx);
		$cal = new rpmonth();
		// display calendar
		$cal->display();
		break;
}

// Output the page
page_header($user->lang['PAGE_TITLE']); 

?>
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

/**
 * implements Raid signups
 *
 */
class rpsignup
{
	
	public $signup_id=0;
	public $raidplan_id;
	public $poster_id;
	public $poster_name;
	public $poster_colour;
	public $poster_ip;
	
	/**
	 * 0=yes, 1=no, 2=maybe 
	 *
	 * @var unknown_type
	 */
	public $signup_val;
	public $signup_time;
	public $signup_count;
	
	public $dkpmemberid;
	public $dkpmembername;
	public $classname;
	public $imagename;
	public $colorcode;
	public $raceimg;
	public $genderid;
	public $level;
	
	public $dkp_current;
	public $priority_ratio;
	public $lastraid;
	public $attendanceP1;
	
	public $comment;
	private $bbcode = array();
	
	public $roleid;
	public $confirm;
	

	
	/**
	 * makes a rpsignup object
	 *
	 * @param int $signup_id  
	 */
	public function getSignup($signup_id)
	{
		
		global $db, $config, $phpbb_root_path, $db;
		
		$this->signup_id=$signup_id;
		$sql = "select * from " . RP_SIGNUPS . " where signup_id = " . $this->signup_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		if( !$row )
		{
			trigger_error( 'INVALID_SIGNUP' );
		}
		$db->sql_freeresult($result);
		
		$this->raidplan_id = $row['raidplan_id'];
		$this->poster_id = $row['poster_id'];
		$this->poster_name = $row['poster_name'];
		$this->poster_colour = $row['poster_colour'];
		$this->poster_ip = $row['poster_ip'];
		$this->signup_time = $row['post_time'];
		$this->signup_val = $row['signup_val'];
		$this->signup_count = $row['signup_count'];
		$this->comment = $row['signup_detail'];
		$this->bbcode['bitfield']= $row['bbcode_bitfield'];
		$this->bbcode['uid']= $row['bbcode_uid'];
		//enable_bbcode & enable_smilies & enable_magic_url always 1
		$this->confirm = $row['role_confirm'];
		$this->dkpmemberid = $row['dkpmember_id'];
		$this->roleid = $row['role_id'];
		// get memberinfo
		$sql_array = array(
	    	'SELECT'    => ' s.*, m.member_id, m.member_name, m.member_level,  
		    				 m.member_gender_id, a.image_female_small, a.image_male_small, 
		    				 l.name as member_class , c.imagename, c.colorcode ', 
	    	'FROM'      => array(
		        RP_SIGNUPS	 		=> 's',
		        MEMBER_LIST_TABLE 	=> 'm',
		        CLASS_TABLE  		=> 'c',
		        RACE_TABLE  		=> 'a',
		        BB_LANGUAGE			=> 'l', 
		        
	    	),
	    
		    'WHERE'     =>  " l.attribute_id = c.class_id 
		    				  AND l.language = '" . $config['bbdkp_lang'] . "' 
	    					  AND l.attribute = 'class'
							  AND (m.member_class_id = c.class_id)
							  AND m.member_race_id =  a.race_id  
							  AND s.dkpmember_id = " . (int) $this->dkpmemberid . ' 
							  AND s.raidplan_id = ' . (int) $this->raidplan_id . '
							  AND s.dkpmember_id = m.member_id
							  AND m.game_id = c.game_id and m.game_id = a.game_id and m.game_id = l.game_id' 		    	
		);
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		
		$this->dkpmembername = $row['member_name'];
		$this->classname = $row['member_class'];
		$this->imagename = (strlen($row['imagename']) > 1) ? $phpbb_root_path . "images/class_images/" . $row['imagename'] . ".png" : '';
		$this->colorcode = $row['colorcode'];
		$race_image = (string) (($row['member_gender_id']==0) ? $row['image_male_small'] : $row['image_female_small']);
		$this->raceimg = (strlen($race_image) > 1) ? $phpbb_root_path . "images/race_images/" . $race_image . ".png" : '';
		$this->level =  $row['member_level'];
		$this->genderid = $row['member_gender_id'];
		
		/* get member dkp for the dkp pool to which the raid plan event belongs. */
		$sql_array = array(
		    'SELECT'    => 	'm.member_dkpid, m.member_status, m.member_lastraid, 
							sum(m.member_earned + m.member_adjustment - m.member_spent - ( ' . max(0, $config['bbdkp_basegp']) . ') ) AS member_current ',
		 
		    'FROM'      => array(
		        MEMBER_DKP_TABLE 	=> 'm',
		        EVENTS_TABLE 		=> 'e',
		        RP_RAIDS_TABLE 		=> 'rp',
		        RP_SIGNUPS 			=> 'rs',
		    	),
		 
		    'WHERE'     =>  ' rs.raidplan_id = rp.raidplan_id 
		    				  and rp.etype_id = e.event_id 
		    				  and e.event_dkpid = m.member_dkpid  
		    				  and rs.raidplan_id = ' . $this->raidplan_id . ' 
		    				  and m.member_id = rs.dkpmember_id and m.member_id = ' . $this->dkpmemberid ,  
		    'GROUP_BY' => 'm.member_dkpid, m.member_status, m.member_lastraid '
		);
		
		if($config['bbdkp_epgp'] == 1)
		{
			$sql_array[ 'SELECT'] .= ', sum(m.member_earned - m.member_raid_decay + m.member_adjustment) AS ep, sum(m.member_spent - m.member_item_decay ) AS gp, 
			CASE WHEN SUM(m.member_spent - m.member_item_decay) = 0 THEN ROUND((m.member_earned - m.member_raid_decay + m.member_adjustment) / ' . max(0, $config['bbdkp_basegp']) .', 2) 
			ELSE ROUND(SUM(m.member_earned - m.member_raid_decay + m.member_adjustment) / SUM(' . max(0, $config['bbdkp_basegp']) .' + m.member_spent - m.member_item_decay),2) END AS pr ' ;
		}
					
		$sql = $db->sql_build_query('SELECT_DISTINCT', $sql_array);
		
		if (($result = $db->sql_query ($sql)))
		{
			while ($row = $db->sql_fetchrow($result))
			{
				$this->dkp_current = $row ['member_current'];
				if($config['bbdkp_epgp'] == 1)
				{
					$this->priority_ratio = $row ['pr'];
				}
				$this->lastraid = $row ['member_lastraid'];
				// fetch the 30 day 
				$this->attendanceP1 = raidcount ( true, $row ['member_dkpid'], $config['bbdkp_list_p1'], $this->dkpmemberid ,2,false );
			}
		}
			
	}
	
	
		
	/**
	 * puts signups on template
	 *
	 */
	private function _pushtemplate()
	{
		
	}

	public function signup()
	{
		$signup_data['poster_id'] = $user->data['user_id'];
		$signup_data['poster_name'] = $user->data['username'];
		$signup_data['poster_colour'] = $user->data['user_colour'];
		$signup_data['poster_ip'] = $user->ip;
		$signup_data['post_time'] = time();
		$signup_data['dkpmember_id'] = request_var('signupchar', 0);
		$signup_data['signup_val'] = 2;
		$signup_data['signup_count'] = 1;
		$signup_data['signup_detail'] = "";
		$signup_data['signup_detail_edit'] = "";
		
	}
	

	
	
}

?>
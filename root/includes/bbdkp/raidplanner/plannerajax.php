<?php
/**
 * returns profile xml based on ajax call 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 */
define('IN_PHPBB', true);
define('IN_BBDKP', true);
define('ADMIN_START', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
$role_id = request_var('role', 0);
$sql_array = array(
	    'SELECT'    => 'r.role_id, r.role_name, ' ,  
	    'FROM'      => array(
	        RP_ROLES   => 'r'
	    ),
	    'ORDER_BY'  => 'r.role_id'
	);
if($role_id==1)
{
	$sql_array['SELECT'] .= 'r.role_needed1 as role_needed';	
}
else 
{
	$sql_array['SELECT'] .= 'r.role_needed2 as role_needed';
}

$sql = $db->sql_build_query('SELECT', $sql_array);
$result = $db->sql_query($sql);
header('Content-type: text/xml');
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<rolelist>';
while ($row = $db->sql_fetchrow($result))
// preparing xml
{
	 $xml .= '<role>'; 
	 $xml .= "<role_id>" . $row['role_id'] . "</role_id>";
	 $xml .= "<role_name>" . $row['role_name'] . "</role_name>";
	 $xml .= "<role_needed>" . $row['role_needed'] . "</role_needed>";
	 $xml .= '</role>';
}
$db->sql_freeresult($result);
$xml .= '</rolelist>';
//return xml to ajax
echo($xml); 
?>

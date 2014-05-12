<?php
/**
 * MyBB 1.6
 * Copyright 2013 Leefish, All Rights Reserved
 *
 *
 * $Id: fishstats.php Fish $
 */

define("IN_MYBB", 1);

define('THIS_SCRIPT', 'fishstats.php');

$change_dir = "./";

if(!@chdir($forumdir) && !empty($forumdir))
{
	if(@is_dir($forumdir))
	{
		$change_dir = $forumdir;
	}
	else
	{
		die("\$forumdir is invalid!");
	}
}

$templatelist = "fishstats";

require_once $change_dir."/global.php";
require_once MYBB_ROOT."inc/functions_post.php";
require_once MYBB_ROOT."inc/functions_user.php";
require_once MYBB_ROOT."inc/functions.php";

// Load global language phrases
$lang->load("portal");

// Fetch the current URL
$fishstats_url = get_current_location();
add_breadcrumb("PostStats", $mybb->settings['bburl']."/fishstats.php");

$mybb->settings['myfishstats_group'] = '4,2';
$plugins->run_hooks("fishstats_start");

$access = false;

// The allowed groups
$groups = explode(',',$mybb->settings['myfishstats_group']);

// Additional Groups
$additionalgroups = explode(",",$mybb->user['additionalgroups']);

if(in_array($mybb->user['usergroup'], $groups))
{
    $access=true;
}
foreach($additionalgroups as $additionalgroup)
{
    if(in_array($additionalgroup, $groups))
    {
        $access=true;
    }
} 
if(!$access)
{
    // permission denied
    error_no_permission();
}
if($access)
{
if(!($mybb->input['mydate'] || $mybb->input['enddate']))
{	
    $mydate = (TIME_NOW-(60*60*24*7));
	$enddate = date(TIME_NOW);
	$prettystart = date("jS F, Y", (TIME_NOW-(60*60*24*7)));
	$prettyend = date("jS F, Y", (TIME_NOW));
}
else
{
    $prettystart = date("jS F, Y", strtotime($db->escape_string($mybb->input['mydate'])));
	$prettyend = date("jS F, Y", strtotime($db->escape_string($mybb->input['enddate'])));
    $mydate = $db->escape_string (strtotime($mybb->input['mydate'])); // Always escape string data from a user!
	$finddate = $db->escape_string (strtotime($mybb->input['enddate'])); // Always escape string data from a user!
	$enddate = date($finddate+(60*60*24*1));
}
$statsquery = $db->query("
			SELECT p.uid AS userid ,p.username AS username, p.dateline,p.pid, COUNT(p.pid) AS total
			FROM ".TABLE_PREFIX."posts p
			WHERE p.dateline > $mydate AND p.dateline < $enddate AND p.visible = '1'
			GROUP BY p.uid
			ORDER BY p.uid ASC
		");

if ($db->num_rows($statsquery) > 0)
{
	$poststablerows = "";
	$poststablefooter = "";
	while ($mystats = $db->fetch_array($statsquery))
	{
		$count = my_number_format($mystats['total']);
		$total_posts += $mystats['total'];
		
		$poststablerows .= '<tr>
		<td class="trow1">'. build_profile_link($mystats['username'], $mystats['userid']). '</td>
		<td class="trow1">'. $count .'</td>
		</tr>';		
	}
	$poststablefooter .= '<tr>
		<td class="trow1">Totals</td>
		<td class="trow1">'. $total_posts .'</td>
		</tr>';
}
else
{
	$poststablerows = '<tr><td class="trow1" colspan="2" align="center">no posts</td></tr>';
}

$template='<html>
<head>
<title>PostStats</title>
{$headerinclude}
<script src="//ajax.googleapis.com/ajax/libs/scriptaculous/1.8.2/scriptaculous.js"></script>
<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/datepicker.js"></script>
<link type="text/css" rel="stylesheet" href="{$mybb->settings[\'bburl\']}/jscripts/datepicker.css" />
</head>
<body>
{$header}
<form id="myform" method="get" action="fishstats.php">
<table border="0" cellspacing="1" cellpadding="4" class="tborder">
<tr>
<td class="trow1" colspan="3">Select the start and end dates you want to check - if you do not select both dates it defaults to 7 days</td>
</tr>
<tr>
<td class="trow1">
<input type="text" name="mydate" id="mydate" value="mydate"/><br />
<label>Selected date : {$prettystart}</label>
</td>
<td class="trow1">
<input type="text" name="enddate" id="enddate" value="enddate"/><br />
<label>Selected date : {$prettyend}</label>
</td>
<td class="trow1">
<input type="submit" class="button" value="Run Query" />
</td>
</tr>
</table>
</form>
<table border="0" cellspacing="1" cellpadding="4" class="tborder">
<tr>
<td class="tcat"><span><strong>Name</strong></span></td>
<td class="tcat" colspan ="2"><span><strong># of Posts</strong></span></td>
</tr>
{$poststablerows}
{$poststablefooter}
<tr><td class="tfoot smalltext" colspan ="2" align="center">A <a href ="http://www.leefish.nl" target="blank" >leefish</a> Plugin</td></tr>
</table>
{$footer}
<script type="text/javascript">
var dpicker = new DatePicker({
 relative : \'mydate\',
 keepFieldEmpty: true
});
var dpicker = new DatePicker({
 relative : \'enddate\',
 keepFieldEmpty: true
});
</script>
</body>
</html>';

$template=str_replace("\'", "'", addslashes($template));

$plugins->run_hooks("fishstats_end");

eval("\$fishstats=\"".$template."\";");
output_page($fishstats);
}
?>
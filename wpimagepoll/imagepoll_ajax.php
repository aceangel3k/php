<?php
/*
 	Image Poll Widget AJAX Proxy
	http://www.holytaco.com
    A widget that allows users to vote on images 
    Angel Espiritu
    1.0
    http://www.holytaco.com
*/
/** wordpress config/database classes **/
include_once('../../../wp-config.php');
include_once('../../../wp-includes/wp-db.php');

// read submitted information

$vote = $_POST['vote'];
$results_id = $_POST['results_div_id'];
$imgdesc1 = $_POST['imgdesc1'];
$imgdesc2 = $_POST['imgdesc2'];
$img1 = $_POST['img1'];
$img2 = $_POST['img2'];
$title =  $_POST['title'];
$oldInner = $_POST['oldInner'];
global $wpdb;
$table_name = $wpdb->prefix . "image_poll";

if ($vote <= 1)
{
	if (!isset($_COOKIE["visits"])) {
		setcookie("visits", 0);
		if ($vote == 1)
		{
		$update = "UPDATE " . $table_name .
		" SET img2count = img2count + 1 ORDER BY ID DESC LIMIT 1";
		} else {
		$update = "UPDATE " . $table_name .
		" SET img1count = img1count + 1 ORDER BY ID DESC LIMIT 1";
		}
	
	} else {
		 setcookie("visits", ++$_COOKIE["visits"]);
	}
	dbDelta($update);
}

//get image counts from wpdb
$img1count = $wpdb->get_var("select img1count from wp_image_poll order by id DESC limit 1;");
$img2count = $wpdb->get_var("select img2count from wp_image_poll order by id DESC limit 1;");
$total = $img1count + $img2count;
//calculate percentages
if ($total != 0)
{
	$img1percent = round($img1count/($total),3) . "%";
	$img2percent = round($img2count/($total),3) . "%";
	$img1percentwidth = round($img1count/($total),3)*164 . "px";
	$img2percentwidth = round($img2count/($total),3)*164 . "px";
	$img1percent100 = round($img1count/($total),2)*100 . "%";
	$img2percent100 = round($img2count/($total),2)*100 . "%";
} else {
	$img1percent =  "0%";
	$img2percent = "0%";
	$img1percentwidth = "0px";
	$img2percentwidth = "0px";
	$img1percent100 = "0%";
	$img2percent100 =  "0%";
}
// Compose JavaScript for return
$imgsrc1 = "<img src=\'$img1\' height=\'85\' style=\'float:left;padding-right:10px\'/>";
$imgsrc2 = "<img src=\'$img2\' height=\'85\' style=\'float:left;padding-right:10px\'/>";



$results = <<<SRESULT
<style type="text/css">

.pollvotebutton {font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#e34400;background-color:#e2e2d6; }
a.pollvotebutton:hover {color:#e34400;}
</style>

<div style="margin-bottom:10px"><div style="float:left"><img src="/wp-content/themes/holytaco_theme/images/cloudbul.gif" /></div><div class="chicktext" style="font-family: \'Lucida Grande\', Verdana, Sans-Serif;font-size:12px;font-weight:bold;color:#e34400;text-transform:uppercase;padding-top:16px">&nbsp;&nbsp;$title</div></div>
<div style="margin-left:40px;width:250px;">

<div class="pollinfo" style="height:85px;padding-top:10px;">$imgsrc1
	<span class="chicktext" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#e34400;">$imgdesc1:</span><br/>
	<span class="percentage" style="font-family:Arial, Helvetica, sans-serif;font-size:30px;font-weight:bold;color:#568b81;margin-right:30px;">$img1percent100</span><span class="pollvotes" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#568b81;">$img1count votes</span>
		<div class="pollbar">
		<div style="float:left"><img src="/wp-content/plugins/imagepoll/images/lft-bar.jpg" /></div><div class="barstyle" style="float:left;height:17px;border-top:1px solid:#d1d1d1;border-bottom:1px solid:#d1d1d1;background-image:url(/wp-content/plugins/imagepoll/images/bar-bg.jpg); background-repeat:repeat-x;width:$img1percentwidth;"></div><div style="float:left"><img src="/wp-content/plugins/imagepoll/images/rgt-bar.jpg" /></div>
		</div><br/><div align="center" style="border-bottom:1px solid #d1d1d1;padding-bottom:8px;margin-bottom:10px;margin-top:20px;"><a class="pollvotebutton" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#e34400;background-color:#e2e2d6;" href="javascript:imagepoll_cast_vote(0,\'polldiv\');void(0);">&nbsp;Vote&nbsp;</a></div>
</div>
<div class="pollinfo" style="margin-top:10px;height:85px;padding-top:20px;">$imgsrc2
	<span class="chicktext" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#e34400;">$imgdesc2:</span><br/>
	<span class="percentage" style="font-family:Arial, Helvetica, sans-serif;font-size:30px;font-weight:bold;color:#568b81;margin-right:30px;">$img2percent100</span><span class="pollvotes" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#568b81;">$img2count votes</span>
		<div class="pollbar">
		<div style="float:left"><img src="/wp-content/plugins/imagepoll/images/lft-bar.jpg" /></div><div class="barstyle" style="float:left;height:17px;border-top:1px solid:#d1d1d1;border-bottom:1px solid:#d1d1d1;background-image:url(/wp-content/plugins/imagepoll/images/bar-bg.jpg); background-repeat:repeat-x;width:$img2percentwidth;"></div><div style="float:left"><img src="/wp-content/plugins/imagepoll/images/rgt-bar.jpg" /></div>
		</div><br/><div align="center" style="border-bottom:1px solid #d1d1d1;padding-bottom:8px;margin-bottom:20px;margin-top:20px;"><a class="pollvotebutton" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#e34400;background-color:#e2e2d6;" href="javascript:imagepoll_cast_vote(1,\'polldiv\');void(0);">&nbsp;Vote&nbsp;</a></div>
</div>
</div>
SRESULT;


//replace returns - javascript do not want!
$results = str_replace("\r","",$results);
$results = str_replace("\n","",$results);

die( "document.getElementById('$results_id').innerHTML = '$results';" );

?>
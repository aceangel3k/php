<?php
/*
Plugin Name: 	Image Poll Widget
Plugin URI:    	http://www.holytaco.com
Description:    A widget that allows users to vote on images 
Author:         Angel Espiritu
Version:        1.0
Author URI:     http://www.holytaco.com
*/
function image_poll_install_table(){
//create wordpress table

	global $wpdb;

	$table_name = $wpdb->prefix . "image_poll";
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name ) {
	$sql = "CREATE TABLE " . $table_name . " (
	     id mediumint(9) NOT NULL AUTO_INCREMENT,
		 title text NOT NULL,
		 img1 VARCHAR(255) NOT NULL,
		 imgdesc1 text NOT NULL,
		 img2 VARCHAR(255) NOT NULL,
		 imgdesc2 text NOT NULL,
		 img1count mediumint(9) default '0' NOT NULL,
		 img2count mediumint(9) default '0' NOT NULL,
		 UNIQUE KEY id (id)
	);";
	
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
    //echo "ran sql";
	dbDelta($sql);
	}
}


function imagepoll_js_header() {
// use JavaScript SACK library for AJAX 
wp_print_scripts( array( 'sack' )); 
// Define custom JavaScript function 
$options = get_option('widget_image_poll');
?> 
<script type="text/javascript"> 
//<![CDATA[ 

function imagepoll_cast_vote( vote_field, results_div ) { 
// function body defined below 
   var mysack = new sack( 
       "<?php bloginfo( 'wpurl' ); ?>/wp-content/plugins/imagepoll/imagepoll_ajax.php" );    
function whenLoading(){
	var e = document.getElementById(results_div); 
	e.innerHTML = "<img src='/wp-content/plugins/imagepoll/images/indicator_remembermilk_orange.gif'>";
}

 if (vote_field == 5)
 {
 var e = document.getElementById(results_div); 
 e.innerHTML = polldivmainmenu;
 return true;
 }
 
  mysack.execute = 1;
  mysack.method = 'POST';
  mysack.setVar( "vote", vote_field );
  mysack.setVar( "results_div_id", results_div );
  mysack.setVar ("imgdesc1", "<?php echo $options['imgdesc1']; ?>");
  mysack.setVar ("imgdesc2", "<?php echo $options['imgdesc2']; ?>");
  mysack.setVar ("img1", "<?php echo $options['img1']; ?>");
  mysack.setVar ("img2", "<?php echo $options['img2']; ?>");
  mysack.setVar ("title", "<?php echo $options['title']; ?>");  
  mysack.onLoading = whenLoading;
  mysack.onError = function() { alert('AJAX error in voting' )};
  mysack.runAJAX();
  


  return true;
} // end of JavaScript function myplugin_cast_vote 

//]]> 
</script> 
<?php } // end of PHP function myplugin_js_header 



image_poll_install_table();
function widget_image_poll_control(){
		// Get options
		$options = get_option('widget_image_poll');
		// options exist? if not set defaults
		if ( !is_array($options) )
			$options = array('title'=>'Who would you rather do?', 'image_poll-img1'=>'', 'image_poll-img2'=>'', 'imgdesc1' => '', 'imgdesc2' => '');
		
		// form posted?
		if ( $_POST['image_poll-submit'] ) {

			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['image_poll-title']));
			$options['img1'] = strip_tags(stripslashes($_POST['image_poll-img1']));
			$options['img2'] = strip_tags(stripslashes($_POST['image_poll-img2']));
			$options['imgdesc1'] = strip_tags(stripslashes($_POST['image_poll-imgdesc1']));
			$options['imgdesc2'] = strip_tags(stripslashes($_POST['image_poll-imgdesc2']));		
			
			//Is it a new entry?
			if ($_POST['image_poll-newpollentry']){
				global $wpdb;
				$table_name = $wpdb->prefix . "image_poll";
				$insert = "INSERT INTO " . $table_name .
            		" (title, img1, imgdesc1, img2, imgdesc2) " .
            		"VALUES ('" . $options['title'] . "','" . $options['img1']   . "','" . $options['imgdesc1']  ."','" . $options['img2']   					. "','" . $options['imgdesc2']. "')";
				$results = $wpdb->query( $insert );
			}
			//Reset the counter?
			if ($_POST['image_poll-resetcounter']){
				global $wpdb;
				$table_name = $wpdb->prefix . "image_poll";
				$insert = "UPDATE " . $table_name .
           		 " SET img1count = 0, img2count = 0 ORDER BY ID DESC LIMIT 1";
				$results = $wpdb->query( $insert );
			}
			//$options['exclude'] = strip_tags(stripslashes($_POST['myRecentPosts-exclude']));
			update_option('widget_image_poll', $options);
		}
		//Get options for form fields in control
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$img1 =  htmlspecialchars($options['img1'], ENT_QUOTES);
		$img2 = htmlspecialchars($options['img2'], ENT_QUOTES);
		$imgdesc1 =  htmlspecialchars($options['imgdesc1'], ENT_QUOTES);
		$imgdesc2 = htmlspecialchars($options['imgdesc2'], ENT_QUOTES);		
		// The form fields
		echo '<p style="text-align:right;">
				<label for="image_poll-newpollentry">' . __('New Poll Entry:') . ' 
				<input type="checkbox" name="image_poll-newpollentry" value="1" />
				</label></p>';			
		echo '<p style="text-align:right;">
				<label for="image_poll-resetcounter">' . __('Reset Counter:') . ' 
				<input type="checkbox" name="image_poll-resetcounter" value="1" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="image_poll-title">' . __('Poll Name:') . ' 
				<input style="width: 370px;" id="image_poll-title" name="image_poll-title" type="text" value="'.$title.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="image_poll-img1">' . __('Image URL 1:') . ' 
				<input style="width: 350px;" id="image_poll-img1" name="image_poll-img1" type="text" value="'.$img1.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="image_poll-imgdesc1">' . __('Image 1 Desc:') . ' 
				<input style="width: 350px;" id="image_poll-imgdesc1" name="image_poll-imgdesc1" type="text" value="'.$imgdesc1.'" />
				</label></p>';				
		echo '<p style="text-align:right;">
				<label for="image_poll-img2">' . __('Image URL 2:') . ' 
				<input style="width: 350px;" id="image_poll-img2" name="image_poll-img2" type="text" value="'.$img2.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="image_poll-imgdesc2">' . __('Image 2 Desc:') . ' 
				<input style="width: 350px;" id="image_poll-imgdesc2" name="image_poll-imgdesc2" type="text" value="'.$imgdesc2.'" />
				</label></p>';	
																	
		echo '<input type="hidden" id="image_poll-submit" name="image_poll-submit" value="1" />';
}

function widget_image_poll($args) {
//actual display of image poll
$options = get_option('widget_image_poll');
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$img1 =  htmlspecialchars($options['img1'], ENT_QUOTES);
		$img2 = htmlspecialchars($options['img2'], ENT_QUOTES);
		$imgdesc1 =  htmlspecialchars($options['imgdesc1'], ENT_QUOTES);
		$imgdesc2 = htmlspecialchars($options['imgdesc2'], ENT_QUOTES);
    extract($args);
?>

        <?php echo $before_widget; ?>
		<div style="vertical-align:middle;padding-left:10px;height:270px;margin-top:20px;" id="polldiv">
	
            <?php echo $before_title
               . '<div style="float:left"><img src="http://media1.holytaco.com/web/holytaco/perm/theme_images/cloudbul.gif" /></div><div style="font-family: \'Lucida Grande\', Verdana, Sans-Serif;font-size:12px;font-weight:bold;color:#e34400;text-transform:uppercase;padding-top:16px">&nbsp;&nbsp;' . $title 
			. '</div>'
                . $after_title; ?>
				<br />
<style>label img {
  behavior: url('/wp-content/plugins/imagepoll/label_img.htc');
}</style>
<br /><div style="float:left;font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#e34400;width:280px;" align="center">Click on Photo to Vote</div>
<div style="float:left;font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#e34400;margin-top:8px;padding-left:0px;" align="center"><label for="imageChosen1"><img src="<?php echo $img1; ?>"  onClick="imagepoll_cast_vote(0,'polldiv');"  height="142" /><br /><div align="center" onClick="imagepoll_cast_vote(0,'polldiv');"><?php echo $imgdesc1; ?></div></label> </div> <div style="float:left;padding-top:50px;;margin-top:28px;padding-left:5px;padding-right:15px" align="center"><strong style="font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#629791">Vs.</strong></div> <div style="float:left;font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#e34400;margin-top:8px" align="center"><label for="imageChosen2"><img src="<?php echo $img2; ?>"  onClick="imagepoll_cast_vote(1,'polldiv');" height="142"/><br /><div align="center" onClick="imagepoll_cast_vote(1,'polldiv');"><?php echo $imgdesc2; ?></div></div></label> <div style="float:left;padding-top:50px;"><br /></div>
<div style="margin-top:8px;width:288px;float:left;padding-left:2px;padding-top:8px;border-top:1px solid #e2e2d6"><div align="center"><strong><a href="javascript:imagepoll_cast_vote(2,'polldiv');void(0);" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;font-weight:bold;color:#e34400;background-color:#e2e2d6">&nbsp;Results&nbsp;</a></strong></div></div>
</div>
 
        <?php echo $after_widget; ?>
<script> var polldivmainmenu = document.getElementById('polldiv').innerHTML;</script>
<?php
}

function widget_image_poll_init()
{
register_sidebar_widget(__('Image Poll Widget'), 'widget_image_poll');
register_widget_control('Image Poll Widget', 'widget_image_poll_control', 460, 250); 
add_action('wp_head', 'imagepoll_js_header' );
}

//sidebar setup
add_action('plugins_loaded', 'widget_image_poll_init'); 
?>
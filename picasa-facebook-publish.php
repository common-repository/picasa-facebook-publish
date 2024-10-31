<?php


/*
Plugin Name: Picasa Facebook Publish
Plugin Script: picasa-facebook-publish.php
Plugin URI: http://blog.wie-gand.de/?p=889
Description: Wordpress plugin to push a Picasa imagegallery into a Facebook stream.
Version: 0.1
License: GPL
Author: Kay Wiegand
Author URI: http://www.wie-gand.de

=== RELEASE NOTES ===
2011-03-11 - v1.0 - first version
*/


/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
Online: http://www.gnu.org/licenses/gpl.txt
*/

add_filter('the_content', 'pfbp_filter');

function get_between($input, $start, $end){
	$substr = substr($input, strlen($start)+strpos($input, $start), (strlen($input) - strpos($input, $end))*(-1));
	return $substr;
} 

// attach stylesheet
add_action('wp_head','pfbp_addStyleSheet');

function pfbp_addStyleSheet() {

	$cssurl = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'picasa-facebook-publish.css';
	
	// add stylesheet
	echo '<link href="' . $cssurl . '" rel="stylesheet" type="text/css" />';
}



// pfbp_filter will generate the (HTML) output
function pfbp_filter($content) {

	global $wpdb;
	
    $options = get_option('pfbp_sample');

	//album name
	$tagstart = '<!--picasa-album:::';
	$tagend = ':::-->';
	$albumname = get_between($content, $tagstart, $tagend);
	$tag = $tagstart . $albumname . $tagend;

	//facebook publish dialog ant stream attachment
	$imagepath = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	$flashurl = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'picasa-facebook-publish.swf'.'?un='. $options['picasaAccount'] .'&an='. $albumname . '&ip=' . $imagepath ;
	$imgurl = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'attachment-preview.jpg';
	
	$message = 'Wohooo, hier sind coole Bilder!';
	$caption = 'Foto-Slideshow vom Album '. $albumname .'.';

	$pfbp_html = '<div id="fb-root"></div><script src="http://connect.facebook.net/de_DE/all.js"></script>
		<script type="text/javascript">
		
		function publishAttachment(){
			FB.init({ appId: "'. $options['facebookAppId'] .'", status: true, cookie: true, xfbml: true });
	
			var attachment = {
				name: "'. get_the_title($post->ID) .' &raquo;&raquo; '. get_bloginfo('name') .'",
				caption: "'. $options['dialogCaption'] .'",
				description: "'. get_bloginfo('description') .'",
				href: "'. get_permalink() .'",
				media: [
					{
			    	type: "flash", 
			   		swfsrc: "'. $flashurl .'",
			    	imgsrc: "'. $imgurl .'", 
			    	width: "100", 
			    	height: "75",
			    	expanded_width: "430", 
			    	expanded_height: "430"
					}
				]
			}

			var dialog = {
				method: "stream.publish",
				display: "popup",
				message: "'. $options['dialogMessage'] .'",
				attachment: attachment,
				user_message_prompt: ""
			};
	
			FB.ui( dialog, function(result) {} );		

		}
		</script><div class="facebookPublishLink"><a href="javascript:publishAttachment();">'. $options['linkText'] .'</a></div>';
	$content = eregi_replace( $tag, $pfbp_html, $content );
	return $content;
}

add_action('admin_init', 'pfbp_sampleoptions_init' );
add_action('admin_menu', 'pfbp_sampleoptions_add_page');

// Init plugin options to white list our options
function pfbp_sampleoptions_init(){
    register_setting( 'pfbp_sampleoptions_options', 'pfbp_sample', 'pfbp_sampleoptions_validate' );
}

// Add menu page
function pfbp_sampleoptions_add_page() {
    add_options_page('Picasa Facebook Publish Options', 'Picasa Facebook Publish', 'manage_options', 'pfbp_sampleoptions', 'pfbp_sampleoptions_do_page');
}

// Draw the menu page itself
function pfbp_sampleoptions_do_page() {
    ?>
    <div class="wrap">
        <h2>Picasa Facebook Publish Options</h2>
        <form method="post" action="options.php">
            <?php settings_fields('pfbp_sampleoptions_options'); ?>
            <?php $options = get_option('pfbp_sample'); ?>
			<br><br>
			<label for="facebookappid">Your Facebook app id:
			<br>	
			<input type="text" name="pfbp_sample[facebookAppId]" value="<?php echo $options['facebookAppId']; ?>" size="50" /> 
			</label>
			<br><br>
			<label for="picasaaccount">Your Picasa account name:
			<br>	
			<input type="text" name="pfbp_sample[picasaAccount]" value="<?php echo $options['picasaAccount']; ?>" size="50" /> 
			</label>
			<br><br>
			<label for="linktext">Your text for the link:
			<br>	
			<input type="text" name="pfbp_sample[linkText]" value="<?php echo $options['linkText']; ?>" size="50" /> 
			</label>
			<br><br>
			<label for="linktext">Your text for the Facebook dialog message:
			<br>	
			<input type="text" name="pfbp_sample[dialogMessage]" value="<?php echo $options['dialogMessage']; ?>" size="50" /> 
			</label>
			<br><br>
			<label for="linktext">Your text for the Facebook dialog caption:
			<br>	
			<input type="text" name="pfbp_sample[dialogCaption]" value="<?php echo $options['dialogCaption']; ?>" size="50" /> 
			</label>
			<br><br>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </form>
    </div>
    <?php    
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function pfbp_sampleoptions_validate($input) {
    // Say our option must be safe text with no HTML tags
    $input['facebookAppId'] =  wp_filter_nohtml_kses($input['facebookAppId']);
    $input['picasaAccount'] =  wp_filter_nohtml_kses($input['picasaAccount']);    
    $input['linkText'] =  wp_filter_nohtml_kses($input['linkText']);
    $input['dialogMessage'] =  wp_filter_nohtml_kses($input['dialogMessage']);
    $input['dialogCaption'] =  wp_filter_nohtml_kses($input['dialogCaption']);
    return $input;
}

?>
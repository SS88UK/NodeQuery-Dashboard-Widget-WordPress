<?php
/*
Plugin Name: Dashboard Widget NodeQuery
Plugin URI: https://ss88.uk/
Description: Adds a widget to the Dashboard showing your NodeQuery monitored server details. Requires an API key from NodeQuery.
Version: 1.0
Author: Steven Sullivan Ltd
Author URI: https://blog.ss88.uk/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Add the Widget to WordPress
function ss88_add_NodeQuery_widget() { add_action( 'admin_footer', 'ss88_show_NodeQuery_widget' );	}

// Output Widget 
function ss88_show_NodeQuery_widget() {
	
	// Bail if not viewing the main dashboard page
	if ( get_current_screen()->base !== 'dashboard' ) { return;	}
	
	// Ouput the style and scripts
	wp_enqueue_style('ss88_NodeQuery_widget_css', plugins_url('css.css', __FILE__));
	wp_enqueue_script('ss88_NodeQuery_widget_js', plugins_url('js.js', __FILE__));
	
	echo '<div id="ss88_NodeQuery" class="welcome-panel" style="display: none;"><div class="welcome-panel-content"><h2><img src="'.plugins_url('NQ-Logo.png', __FILE__).'" alt="NodeQuery Servers" /></h2>';
	
	$NQ = get_option('ss88_NodeQuery_widget_key');
	
	if($NQ != '')
	{
		$Data = ss88_NodeQuery_widget_api($NQ, 3);
		
		if(is_array($Data))
		{
			foreach($Data as $Server)
				echo ss88_NodeQuery_widget_showbars($Server);
				
			echo '<script> jQuery("#ss88_NodeQuery.welcome-panel").addClass("loaded"); </script>';
		}
		else
			echo '<p style="color:red;">There seems to be a problem contacting NodeQuery. Please refresh.</p>';
	}
	else
	{
		echo '<p>You have not yet set your NodeQuery API key. Please set this first.</p>';
		echo ss88_NodeQuery_widget_showform();
	}
	
	// Show Steven Sullivan Ltd Copy
	echo '<ul class="ss88_vw_keys k2"><li class="red"><a href="https://paypal.me/SS88/3" target="_blank">Beers Please!</a></li><li class="violet"><a href="https://blog.ss88.uk/" target="_blank">Steven Sullivan Ltd</a></li></ul><div class="ss88_spinner"><div class="double-bounce1"></div><div class="double-bounce2"></div></div>';
	
	echo '</div></div>';
}


// Call NodeQuery
function ss88_NodeQuery_widget_api($NQ, $Timeout=10)
{
	$answer = wp_remote_get('https://nodequery.com/api/servers?api_key=' . $NQ, array('timeout'=> $Timeout));
	
	// Return Error
	if (is_wp_error($answer))
		return 'error';

	// Parse JSON output
	$data = json_decode($answer['body'], true);
	return $data['data'][0];
}

add_action('wp_dashboard_setup', 'ss88_add_NodeQuery_widget');







function ss88_NodeQuery_widget_ajax()
{
	if(! wp_verify_nonce($_POST['nonce'], 'ss88_NodeQuery_widget_ajax' )) die ('busted');
	
	$NQ = sanitize_text_field($_POST['apikey']);
	
	$Data = ss88_NodeQuery_widget_api($NQ);
	if(is_array($Data))
	{
		// Set Options
		update_option('ss88_NodeQuery_widget_key', $NQ);

		foreach($Data as $Server)
			echo ss88_NodeQuery_widget_showbars($Server, $NQ);
	}
	else
	{
		echo '<p style="color:red;">Sorry, either the login is incorrect, or we could not connect to NodeQuery. Please try again.</p>';
		echo ss88_NodeQuery_widget_showform();
	}
	
	wp_die();
}

add_action('wp_ajax_ss88_NodeQuery_widget_ajax', 'ss88_NodeQuery_widget_ajax');



function ss88_NodeQuery_widget_showbars($Data)
{
	$DPercent = (($Data['disk_usage'] / $Data['disk_total']) * 100);
	$DPercent = ($DPercent>100) ? 100 : $DPercent;
	
	$RPercent = (($Data['ram_usage'] / $Data['ram_total']) * 100);
	$RPercent = ($RPercent>100) ? 100 : $RPercent;
	
	$IPV6 = ($Data['ipv6']!= '') ? '<li class="yellow" tooltip="IPV6 Address">'.$Data['ipv6'].'</li>' : '';
	
	$Active = ($Data['status']!='active') ? 'inactive' : '';
	
	return '<div class="ss88_nq_server"><h5>'.$Data['name'].'</h5><ul class="ss88_vw_keys">
		<li class="emerald" tooltip="Availability">'.$Data['availability'].'</li>
		<li class="violet" tooltip="IPV4 Address">'.$Data['ipv4'].'</li>
		'.$IPV6.'
		<li class="clear"></li>
		</ul>

	<div class="bar-main-container azure" tooltip="Load: '.$Data['load_average'].'"><div class="bar-wrap"><div class="nq-bar-percentage" data-percentage="'.$Data['load_percent'].'"></div><div class="bar-container"><div class="bar"></div></div></div></div>
	<div class="bar-main-container azure" tooltip="RAM: '.ss88_NodeQuery_widget_formatBytes($Data['ram_usage']).' / '.ss88_NodeQuery_widget_formatBytes($Data['ram_total']).'"><div class="bar-wrap"><div class="nq-bar-percentage" data-percentage="'.$RPercent.'"></div><div class="bar-container"><div class="bar"></div></div></div></div>
	<div class="bar-main-container azure" tooltip="Disk: '.ss88_NodeQuery_widget_formatBytes($Data['disk_usage']).' / '.ss88_NodeQuery_widget_formatBytes($Data['disk_total']).'"><div class="bar-wrap"><div class="nq-bar-percentage" data-percentage="'.$DPercent.'"></div><div class="bar-container"><div class="bar"></div></div></div></div>
	
	<div class="ss88_nq_offline '.$Active.'">Server<br />Not<br />Responding</div></div>';
}

function ss88_NodeQuery_widget_showform()
{
	$nonce = wp_create_nonce('ss88_NodeQuery_widget_ajax');
	
	return '<form autocomplete="off" action="'.admin_url( 'admin-ajax.php') . '" method="post" class="ss88_vw_form_nq">
	
	<input type="text" name="ss88_NodeQuery_widget_key" autocomplete="off" placeholder="API key" required />
	<p style="font-size:11px;">Click <a href="https://nodequery.com/settings/api" target="_blank">here</a> to find or generate an API key from NodeQuery.</p>
	<input type="submit" value="Save Details" class="btn" />
	<input type="hidden" name="ss88_NodeQuery_widget_nonce" value="'.$nonce.'" />
	</form></div>
	
	<script> jQuery(document).ready(function(){ NQ_hookSubmitform(); }); </script>';
}



























function ss88_NodeQuery_widget_formatBytes($bytes, $precision =2) { 
	
    $base = log($bytes, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');   

    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
} 

function ss88_NodeQuery_widget_remove()
{
	delete_option('ss88_NodeQuery_widget_key');
}

register_uninstall_hook( __FILE__, 'ss88_NodeQuery_widget_remove' );

?>
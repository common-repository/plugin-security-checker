<?php
/*
Plugin Name: Plugin Security Checker
Plugin URI: https://www.pluginvulnerabilities.com/plugin-security-checker/
Description: Check if plugins have any security issues that can be detected by our Plugin Security Checker tool.
Version: 1.0
Author: White Fir Design
Author URI: https://www.pluginvulnerabilities.com/
License: GPLv2
Text Domain: plugin-security-checker
Domain Path: /languages

Copyright 2017-2018 White Fir Design

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; only version 2 of the License is applicable.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Block direct access to the file
if ( !defined( 'ABSPATH' ) ) { 
	exit; 
}

class PluginSecurityChecker {
	
	public function __construct()
    {
		add_filter( 'plugin_row_meta', array($this,'add_links_to_installed_plugins_page'), 10, 2 );
		add_filter( 'plugins_api_result', array($this,'plugin_details_page_tab'), 10, 3);
		add_action( 'admin_head', array( $this, 'add_javascript_to_add_new_plugin_page' ) );
		add_action( 'wp_ajax_plugin_security_checker_page', array($this, 'installed_plugins_ajax_page') );
		add_action( 'wp_ajax_plugin_security_checker_results', array($this, 'get_psc_results') );
    }
	
	//Add link to Installed Plugins Page
	function add_links_to_installed_plugins_page( $links, $file ) {
		//Check if plugin is not a single file
		if ( strpos( $file, '/' ) !== false ) {
			//Check if plugin in Plugin Directory
			foreach ( $links as $entry ) {
				if ( strpos( $entry, "View details") !== FALSE ) {
					$plugin_path =  strstr( $file, '/', true );
					$links['psc'] = '<a href="'.esc_url ( admin_url( 'admin-ajax.php' ).'?action=plugin_security_checker_page&nonce='.wp_create_nonce( 'psc_page_nonce' ).'&type=directory&path='.$plugin_path.'&TB_iframe=true&width=600&height=550' ).'" class="thickbox open-plugin-details-modal">Check plugin security</a>' ;	
					return $links;
				}
			}
			$links['psc'] = '<a href="'.esc_url ( admin_url( 'admin-ajax.php' ).'?action=plugin_security_checker_page&nonce='.wp_create_nonce( 'psc_page_nonce' ).'&type=upload&path='.$file.'&TB_iframe=true&width=600&height=550' ).'" class="thickbox open-plugin-details-modal">Check plugin security</a>' ;		
			return $links;	
		}
		return $links;
	}
	
	//Handles popup page shown when clicking "Check plugin security" links in on Installed Plugins page
	public function installed_plugins_ajax_page () {
		if ( current_user_can( 'activate_plugins' ) && isset ( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'psc_page_nonce' ) ) {
			if ( isset ( $_GET['type'] ) && $_GET['type'] == "directory" && isset( $_GET['path'] ) && preg_match( '/([a-z0-9\-]+)/i', $_GET['path'], $match ) ) {
				$type = "directory";
				$path = $match[1];
			}
			else if ( isset ( $_GET['type'] ) && $_GET['type'] == "upload" && !$this->is_pv_subscriber() ) {
				exit('You can check plugins not in the Plugin Directory if are a customer of our <a href="https://www.pluginvulnerabilities.com/">Plugin Vulnerabilities service</a> and have <a href="https://www.pluginvulnerabilities.com/set-up/">set up the companion plugin for that on your website</a>.');
			}
			else if ( isset ( $_GET['type'] ) && $_GET['type'] == "upload" && isset($_GET['path']) && preg_match( '/([a-z0-9\-\_\.\/]+)/i', $_GET['path'], $match ) ) {
				$type = "upload";
				$path = $match[1];
			}
			else
				exit('Incomplete request.');
			echo '<html><head>';
			echo '<script type="text/javascript" src="'.includes_url( '/js/jquery/jquery.js' ).'"></script>';
			echo '<script>
				jQuery( document ).ready(function() {
					jQuery( "#plugin_security_checker" ).prepend(\'<img id="theImg" src="'.includes_url("/images/spinner.gif" ).'" style=" display: block; margin-left: auto; margin-right: auto; margin-top: 450px;" />\');
					jQuery( "#plugin_security_checker" ).load("'.admin_url( 'admin-ajax.php' ).'?action=plugin_security_checker_results", {"type":"'.$type.'","path":"'.$path.'","nonce":"'.wp_create_nonce( 'psc_results_nonce' ).'"} );
				});
			</script>';
			echo '</head><body>';
			echo '<div id="plugin_security_checker"></div>';
			echo '<body></html>';
			exit();
		}
		else
			exit('You are not allowed to access this page.');
	}
	
	//Add Plugin Security Checker tab to details pages on Add New plugins page
	public function plugin_details_page_tab ( $res, $action, $args ) {
		if ( $action = "plugin_information" && isset ($res->sections['description']) ) {
					$res->sections['plugin_security_checker'] = "";
				}
		return $res;
	}
	
	//Adds JavaScript code to Add New plugin page to handle showing results on Plugin Security Checker tab on plugin details pages
	public function add_javascript_to_add_new_plugin_page() {
        if(get_current_screen()->id == 'plugin-install')
			echo '<script>
				var psc_results_nonce="'.wp_create_nonce( 'psc_results_nonce' ).'";
				jQuery(document).on("click","a[name=\'plugin_security_checker\']", function() {
					if ( jQuery( "#section-plugin_security_checker" ).children().length == 0 ) {
						jQuery( "#section-plugin_security_checker" ).prepend(\'<img id="theImg" src="'.includes_url("/images/spinner.gif" ).'" style=" display: block; margin-left: auto; margin-right: auto; margin-top: 450px;" />\');
						var plugin_path = jQuery(" a[name=\'plugin_security_checker\'] " ).attr("href").match(/plugin=([a-z0-9\-]+)/i)[1];
						
						jQuery( "#section-plugin_security_checker" ).load("'.admin_url( 'admin-ajax.php' ).'?action=plugin_security_checker_results", {"type":"directory","path":plugin_path,"nonce":psc_results_nonce} );
					}
				});
				</script>';
		else
			return;
	}

	//Handles request to Plugin Security Checker tool and displaying results
	public function get_psc_results() {
		if ( current_user_can( 'activate_plugins' ) && isset ( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'psc_results_nonce' ) ) {
			//Get results for plugin in Plugin Directory
			if ( isset ( $_POST['type'] ) && $_POST['type'] == "directory" && isset ( $_POST['path'] ) && preg_match( '/([a-z0-9\-]+)/i', $_POST['path'], $match ) ) {
				$path = $match[1];
				$response = wp_remote_post( "https://www.pluginvulnerabilities.com/api/plugin-security-checker/", array(
					'timeout' => 60,
					'body' => array( 'type' => 'directory', 'plugin' => $path ),
					)
				);
			}
			//Get results for plugin not in Plugin Directory
			else if ( isset ( $_POST['type'] ) && $_POST['type'] == "upload" && isset ( $_POST['path'] )  && preg_match( '/([a-z0-9\-\_\.\/]+)/i', $_POST['path'], $match ) && get_option('plugin_vulnerabilities_api_license_key') && get_option('plugin_vulnerabilities_api_license_email') && get_option('plugin_vulnerabilities_api_license_instance') ) {
				$plugins_directory = WP_PLUGIN_DIR;	
				$plugin_file = $plugins_directory.'/'.$match[1];
				$plugin_data = get_plugin_data( $plugin_file );
				
				//Make sure plugin file specified is actually one
				if ( empty( $plugin_data['Name'] ) )
					exit('Invalid plugin specified.');

				//Generate zip file of plugin
				preg_match( '/([a-z0-9\-\_]+)\//i', $_POST['path'], $match );
				$plugin_directory = $match[1];
				$directory = $plugins_directory.'/'.$plugin_directory;
				$zip = new ZipArchive();
				$filename = tempnam(sys_get_temp_dir(), 'prefix');
				if ($zip->open($filename, ZipArchive::CREATE)==TRUE) {
				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($directory),
					RecursiveIteratorIterator::SELF_FIRST
				);
				foreach ( $files as $file ) {
					if (!$file->isDir()) {
						$filePath = $file->getRealPath();
						$relativePath = substr($filePath, strlen($plugins_directory) + 1);
						$zip->addFile($filePath, $relativePath);
					}
				}
				$zip->close();

				}
				//Limit file uploads to 20 MB
				if ( filesize ($filename) <= 20971520 ) {
					$post_fields = array(
						'type' => 'upload',
						'site-url' => get_site_url(),
						'api-license-key' => get_option('plugin_vulnerabilities_api_license_key'),
						'api-license-email'  => get_option('plugin_vulnerabilities_api_license_email'),
						'api-license-instance' => get_option('plugin_vulnerabilities_api_license_instance'),
					);
					$boundary = wp_generate_password( 24 );
					$headers  = array( 'content-type' => 'multipart/form-data; boundary=' . $boundary, );
					$payload = '';
					foreach ( $post_fields as $name => $value ) {
						$payload .= '--' . $boundary;
						$payload .= "\r\n";
						$payload .= 'Content-Disposition: form-data; name="' . $name .
							'"' . "\r\n\r\n";
						$payload .= $value;
						$payload .= "\r\n";
					}
					if ( $filename ) {
						$payload .= '--' . $boundary;
						$payload .= "\r\n";
						$payload .= 'Content-Disposition: form-data; name="' . 'plugin' .
							'"; filename="' . basename( $filename ) . '"' . "\r\n";
						$payload .= "\r\n";
						$payload .= file_get_contents( $filename );
						$payload .= "\r\n";
					}
					$payload .= '--' . $boundary . '--';
					$response = wp_remote_post( "https://www.pluginvulnerabilities.com/api/plugin-security-checker/",
						array(
							'timeout' => 60,
							'headers'    => $headers,
							'body'       => $payload,
						)
					);
				}
				else
						exit('The plugin is too big to be uploaded.');
			}
			else
				exit('Incomplete request.');

			if ( is_wp_error( $response ) ) {
				$error = $response->get_error_message();
				exit('Something went wrong:'.$error);
			} 
			else {
				$data = json_decode ( $response['body'] );
				
				if ( !empty($data->Status) ) {
					if ( $data->Status == "Your usage for the day has been exhausted.")
						exit("Your usage of the tool for the day has been exhausted.");
					else if ( $data->Status == "API license key is not active.")
						exit("Your Plugin Vulnerabilities API license key is not active.");
				}
				if ( !isset( $data->Issues ) ) {
					exit('Something went wrong.');
				}
				
				//Fill in data for uploaded plugin
				if ( isset( $plugin_data ) ) {
					$data->Name = $plugin_data['Name'];
					$data->Version = $plugin_data['Version'];
				}
			   	
				if  ($data->KnownVulnerable) {
						echo '<h3>Plugin Contains Vulnerability:</h3>';
						echo "<p>The <span style='color:red'>current version, ".esc_html($data->Version).", of the plugin ".esc_html($data->Name)." contains a publicly disclosed vulnerability</span>. If you were using our Plugin Vulnerabilities service and using the plugin checked, you would have already been warned of the vulnerability. If you sign up for the service, the details of vulnerability will be <a href='
						https://www.pluginvulnerabilities.com/where-to-find-our-data-on-wordpress-plugin-vulnerabilities-when-using-the-service/'>presented in the WordPress admin area</a>.</p>";
				}
				
				if ( !empty( $data->Issues ) ) {
					if (count($data->Issues) > 1 )
						echo '<h3>Possible Issues Detected:</h3><ul>';
					else
						echo '<h3>Possible Issue Detected:</h3><ul>';
					foreach ( $data->Issues as $issue ) {
							echo '<br><li>'.esc_html($issue->Details->Text);
							echo '</li>';
						}
					echo '</ul>';
					echo '<p>You should <b>not</b> be contacting the developer of the plugin with these results as they only indicate a possible issues. Instead someone with the proper expertise should review the plugin to determine if there is in fact an issue before contacting the developer about a confirmed issue, so their time is not taken up unnecessarily.</p>';
				}
				else {
					if ($data->KnownVulnerable)
						echo "<p>No additional issues detected.</p>";
					else {
						echo '<h3>No Issues Detected</h3>';
						if ( $_POST['type'] == "directory" )
							echo "<p>The current version, ".esc_html($data->Version).", of the plugin ".esc_html($data->Name)." isn't listed as containing any vulnerabilities in our data set and we didn't detect any security issues with the checks performed by this tool. The plugin may contain security issues that cannot be found by this tool.</p>";
						else
							echo "<p>In the uploaded version of the plugin ".esc_html($data->Name)." we didn't detect any security issues with the checks performed by this tool. The plugin may contain security issues that cannot be found by this tool.</p>";
					}
				}
				echo "<br><h3>Get a Professional Security Review</h3>";
				echo '<p>Our price to do a <a href="https://www.pluginvulnerabilities.com/wordpress-plugin-security-review-service/">security review</a> of version '.esc_html($data->Version).' of '.esc_html($data->Name).' is '.esc_html($data->Price).' USD.</p>';
				if ( $_POST['type'] == "directory")
					echo '<p>If you were a paying customer of our service you also could <a href="https://www.pluginvulnerabilities.com/wordpress-plugin-security-reviews/">suggest/vote</a> for the plugin to receive a review from us for no additional cost.</p>';
				if ( isset ( $data->PreviousReview ) )
					echo '<p>We previously did a <a href="'.esc_url($data->PreviousReview->URL).'">review of version '.esc_html($data->PreviousReview->Version).' of the plugin</a>.</p>';
				exit();
			}
		}
		else
			exit('You are not allowed to access this page.');
	}
	
	//Check if the website has been setup to access Plugin Vulnerabilities service through the Plugin Vulnerabilities plugin
	private function is_pv_subscriber () {
		if (get_option('plugin_vulnerabilities_api_license_key'))
			return true;
		else
			return false;
	}

}

$plugin_security_checker = new PluginSecurityChecker();
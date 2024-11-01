<?php

/**

Plugin Name: TTFGen for Wordpress
Plugin URI: http://www.ttfgen.com/wordpress
Description: TTFGen will allow you to upload your own custom fonts and embed them directly into your wordpress page.
Version: 1.0.5
Author: Mike Dabbs, Vinny Troia
Author URI: http://www.ttfgen.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

**/

define ( 'TTF_OPTIONS','ttfgen_options' );
define ( 'TTF_FONTS', 'ttfgen_fonts' ); 
define ( 'TTF_CSS', 'ttfgen_css' ); 
define ( 'TTF_VERSION','1.0.5' ); 

$nonce_base = 'wp-ttfgen-nonce'; // nonce for security of links/forms, try to prevent "CSRF"

session_start();  
 
		
// Folder definitions as constants	
define ( 'TTF_WP_CONTENT_DIR', ABSPATH . 'wp-content/' );	// ex: /var/www/ttfgen/httpdocs/wp-content/ 	
define ( 'TTF_WP_CONTENT_URL', get_option('siteurl') . '/wp-content/' );	// ex: http://www.ttfgen.com/wp-content 	 	
define ( 'TTF_WP_PLUGIN_URL', TTF_WP_CONTENT_URL . 'plugins/' );	// ex: http://www.ttfgen.com/wp-content/plugins/
define ( 'TTF_WP_PLUGIN_DIR', TTF_WP_CONTENT_DIR . 'plugins/');	// ex: /var/www/ttfgen/httpdocs/wp-content/plugins/
define ( 'TTF_CONTENT_PATH', TTF_WP_CONTENT_DIR . 'uploads/');	// ex: /var/www/ttfgen/httpdocs/wp-content/uploads/
define ( 'TTF_PATH_FULL', TTF_WP_PLUGIN_DIR . basename( dirname ( __FILE__ ) ) . '/' );	// ex: /var/www/ttfgen/httpdocs/wp-content/plugins/wp-ttfgen/ 	
define ( 'TTF_PATH_SHORT', TTF_WP_PLUGIN_URL . 'wp-ttfgen/');	// ex: /wp-content/plugins/wp-ttfgen/ 	
define ( 'WP_TTFGEN_URL', TTF_WP_PLUGIN_URL . basename( dirname ( __FILE__ ) ) . '/' );	// ex: http://www.ttfgen.com/wp-content/plugins/wp-ttfgen/ 
define ( 'WP_TTFGEN_BASENAME', plugin_basename( __FILE__ ) );
 
 
$fontList = array(); 
$tgStyles = array();  
$loadDefaults = false; 
 
// Init table names   
global $table_prefix;
$wp_ttf_fonts = $table_prefix."ttfgen_fonts"; 
$wp_ttf_css = $table_prefix."ttfgen_css";


define ( 'WP_DB_FONTS', $table_prefix."ttfgen_fonts" ); 
define ( 'WP_DB_CSS', $table_prefix."ttfgen_css" ); 

//options settings
add_action('admin_menu', 'ttfAdminMenu');
add_action('init', 'initOptions');

// multi language support
load_plugin_textdomain('wp-ttfgen', TTF_PATH_FULL.'languages', 'languages/');

// set up defaults when plugin is activated
register_activation_hook(__FILE__, 'install_plugin');

 
//add TTFGen to settings page
function ttfAdminMenu() 
{
	if (function_exists('add_options_page')) {
		add_options_page('TTFGen - Embed Custom Fonts', 'TTFGen', 9, basename(__FILE__), 'ttfOptionsPage');
	}
}




/**
 * INIT OPTIONS 
 * Checks and Add/Update the default options. 
 * 
 */
 
function initOptions() 
{
	
	global $wpdb, $ttfOptions, $loadDefaults;
	
	$ttfOptions = get_option(TTF_OPTIONS);
	
	if ($ttfOptions['version'] != TTF_VERSION || $loadDefaults == true) 
	{
		$ttfOptions = array( 
			'version' => TTF_VERSION,
			'ttfPath' => TTF_PATH_FULL,
			'jqueryPath' => TTF_PATH_FULL.'scripts/jquery-min.js',
			'configFile' => TTF_PATH_FULL.'scripts/config.php',
			'useTTFjQuery' => true,
			'useTTFMeta' => true,
			'fontFolder' => TTF_CONTENT_PATH.'fonts/',
			'fontCache' => TTF_CONTENT_PATH.'fontcache/',
			'loadLocation' => 'footer',
			'version' => TTF_VERSION,
			'fontDBCols'=> '3',
			'cssDBCols' => '5',	
		);
		
		
		//add or update the db
		update_option(TTF_OPTIONS, $ttfOptions);
		
		$_SESSION['ttfMessage'] .= "Database Update Complete.";
	 
	} 
	
	// check to see if we are re-loading the default options
	if ($loadDefaults == true) {
		$loadDefaults = false; 
		refresh(); 
	}

		 
	//reload
	$ttfOptions = get_option(TTF_OPTIONS);
	
	initDB(); 
			
}

/**
 * INIT PLUGIN 
 * Creates database table (if not found). 
 * If post found, redirect accordingly. 
 * 
 */

function initDB() 
{
		
	global $wpdb, $ttfOptions;
	
   
	$fontDb = "CREATE TABLE IF NOT EXISTS ".WP_DB_FONTS." (				
			`id`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
			`name`   	LONGTEXT  NOT NULL ,
			`font`  	LONGTEXT  NOT NULL ,
			PRIMARY KEY ( `id` ));";
	$result = $wpdb->query($fontDb);
	 		   		   
   	$cssDb = "CREATE TABLE IF NOT EXISTS ".WP_DB_CSS." (				
			`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,                                                                 
			`css_type` VARCHAR(100) NOT NULL ,                                                                          
			`css_name` VARCHAR(200) NOT NULL ,                                                                         
			`css_font_id` INT(10) NOT NULL ,  
			`enable_hover` TINYINT(1) NOT NULL ,  
			`hover_color` VARCHAR(10) ,  								 
			PRIMARY KEY ( `id` ));";

	$cssResult = $wpdb->query($cssDb);

	
	// catch for beta users
	add_column_if_not_exist(WP_DB_CSS, "enable_hover", "TINYINT(1) NULL"); 
	add_column_if_not_exist(WP_DB_CSS, "hover_color", "VARCHAR(10) NULL");	
	
	checkFolders(); 
	
}

function add_column_if_not_exist($db, $column, $column_attr = "VARCHAR( 255 ) NULL" ){
	$exists = false;
	
	$columns = mysql_query("show columns from $db");
	while($c = mysql_fetch_assoc($columns))
	{
		if($c['Field'] == $column) {
			$exists = true;
			break;
		}
	}
	if(!$exists) {
		mysql_query("ALTER TABLE `$db` ADD `$column`  $column_attr");
	}
}



/**
 * CHECK FOLDERS
 * Checks and Creates the folders based on the TTF Options 
 * 
 */ 

function checkFolders()  
{

	global $wpdb, $ttfOptions;
	 
 	$fontDir = $ttfOptions['fontFolder']; 
 	
 	// check to see if the Uploads folder exists
 	
 	if (!is_dir(TTF_CONTENT_PATH)) 
 	{	
 		mkdir(TTF_CONTENT_PATH); 
 		chmod(TTF_CONTENT_PATH, 0755);
 		
 	}
		
 	// Create Font Folder 
	if(!is_dir($ttfOptions['fontFolder'])) 
	{	
		mkdir($ttfOptions['fontFolder']); 
		chmod($ttfOptions['fontFolder'], 0750);
			
		if(!is_dir($ttfOptions['fontFolder']))  
			$_SESSION['ttfMessage'] .= "Unable to create folder ".$ttfOptions['fontFolder'].". Please check your folder permissions. <br />"; 
	}


 	// Create Font Folder
	if(!is_dir($ttfOptions['fontCache'])) 
	{
		mkdir($ttfOptions['fontCache']);
		chmod($ttfOptions['fontCache'], 0750);
			
		if(!is_dir($ttfOptions['fontCache']))  
			$_SESSION['ttfMessage'] .= "Unable to create folder ".$ttfOptions['fontCache'].". Please check your folder permissions. <br />"; 
	}	

		
	checkConfig();
		  
}

function loadDefaults() 
{	
	global $loadDefaults;
	$loadDefaults = true; 
	checkOptions(); 	
	
}

/**
 * CHECK CONFIG 
 * The external config.php file is needed by the TTFGen PHP file during font rendering. 
 * If the file does not exist, this function will create it. 
 * checkConfig will also check the urls in the config to make sure that they match the
 * paths in the database. In the event of a user path change, the config file will auto update.   
 */

function checkConfig() 
{
	global $wpdb, $ttfOptions;	
	$config = $ttfOptions['configFile'];  

	// if Config File does not exist, create it..  
	if (!file_exists($config)) 
	{ 
	
		$fh = fopen($config, 'w');
		
		if (!$fh) {
			$_SESSION['ttfMessage'] .= "Can't Create the TTF Config File. Please check your directory permissions";
		}
		
		$string = "<?php \n\n";
		$string .= "// Config file needed for TTFGen PHP file during font rendering.\n";
		$string .= "$"."cache_prefix = '".$ttfOptions['fontCache']."'; // folder must be writable, images will get cached here\n";  
		$string .= "$"."font_prefix = '".$ttfOptions['fontFolder']."'; // points to folder containg .ttf font files\n\n";
		$string .= "?>\n"; 
		
		fwrite($fh, $string);
		
		fclose($fh); 
	} 
	else // if file does exist, check the settings to make sure they match the database 
	{
		// include the config file and check to see if the vars match the database info
		include_once ($config); 
		if ($cache_prefix != $ttfOptions['fontCache'] || $font_prefix != $ttfOptions['fontFolder']) 
		{
			
			$fh = fopen($config, 'w');
			
			if (!$fh) {
				$_SESSION['ttfMessage'] .= "Can't Create the TTF Config File. Please check your directory permissions";
			}
			
			$string = "<?php \n\n";
			$string .= "// Config file needed for TTFGen PHP file during font rendering.\n";
			$string .= "$"."cache_prefix = '".$ttfOptions['fontCache']."'; // folder must be writable, images will get cached here\n";  
			$string .= "$"."font_prefix = '".$ttfOptions['fontFolder']."'; // points to folder containg .ttf font files\n\n";
			$string .= "?>\n"; 
			 
			fwrite($fh, $string);
			
			fclose($fh); 			
			
		} 
	} 
		  
	checkFonts(); 
}



/**
 * CHECK FONTS 
 * Get the list of fonts in the database and compare them to what's 
 * stored on the server. If the files are not there, remove them from 
 * the database.    
 */

function checkFonts() 
{
	global $wpdb, $ttfOptions;
	
	$fontList = getFonts(); 

	for ($i=0; $i < count($fontList); $i++) 
	{
		$fontID = $fontList[$i]->id; 
		$curFont = $fontList[$i]->font; 
		$font = $ttfOptions['fontFolder'].$curFont; 
		if (!file_exists($font)) {
			$_SESSION['ttfMessage'] .= "The font ".$curFont." is missing. <br />";
			  
		}
	}

	checkPost(); 
}

/**
 * CHECKPOST
 * Check the post data to determine where to send the results.    
 */

function checkPost() 
{
	global $wpdb, $ttfOptions;
				
	// If page is loading from POST, determine where it is going 			 
	if ($_POST)
	{		
		switch ($_GET['action'])
		{
			case "addFont":
				addFont();
				break; 
			case "deleteFont":
				deleteFont(); 
				break; 
			case "deleteCSS":
				deleteCSS(); 
				break; 
			case "updateGeneral":
				updateGeneral(); 
				break; 
			case "assignCSS":
				assignCSS(); 
				break; 
			case "updateCSS":
				updateCSS(); 
				break;
			case "loadDefaults":
				loadDefaults(); 
				break;
		}					
	}	
}


/**
 * addFont
 * Adds a new font to the database
 */

function addFont() 
{
	global $wpdb, $ttfOptions;	
	

	// Valid file types for upload.
	$allowedExtensions = array("ttf"); 

	if (empty( $_POST['fontFileName'] ) && empty($_FILES['uploadedFile']['tmp_name'])) 
		$_SESSION['ttfMessage'] .= "Required field: <strong>File URL</strong> omitted. <br />";	

	if (empty( $_POST['fontName'] )) 
		$_SESSION['ttfMessage'] .= "Required field: <strong>Title</strong> omitted <br />";	

	$fontName = trim($_POST['fontName']); 

	$file = basename($_FILES['uploadedFile']['name']);
	$fileExt = strtolower(substr($file, strripos($file, '.'))); // file extension
	$fileBase = strtolower(substr($file, 0, strripos($file, '.'))); // file name
	
	// strip spaces from file name
	$fileBase = str_replace (" ", "_", $fileBase);

	if ($fileExt == ".ttf") 
	{ 
		$fileBase = trim($fileBase);
		$fileName = $fileBase.".ttf"; 
		$target_path = $ttfOptions['fontFolder'] . $fileName; 
		
		// Copy uploaded file.
		if (move_uploaded_file($_FILES['uploadedFile']['tmp_name'], $target_path)) 
		{
			chmod ($target_path, 0644);	 

			// once successful, add to database												
			$query = "INSERT INTO ".WP_DB_FONTS." (name, font) VALUES ('".$fontName."', '".$fileName."')";
			$result = $wpdb->query($query);
			
			// display success message
			$_SESSION['ttfMessage'] .= "The file ". basename( $fontName ). " has been uploaded <br />";
			
		} 
		else 
		{
			$_SESSION['ttfMessage'] .= "File upload failed. <br />";
		}

	} 
	else 
	{ 
		$_SESSION['ttfMessage'] .= "Font extension invalid. <br />";
	}

}	


/**
 * assignCSS
 * Assigns a font to a CSS element
 */

function assignCSS()
{
	global $wpdb, $ttfOptions;
 
	// get post data
	$cssType = $_POST['cssType'];
	$fontName = $_POST['fontName'];
	$cssName = $_POST['cssName'];
	$enableHover = $_POST['enableHover']; 
	$hoverColor = checkColor($_POST['hoverColor']); 


	// check the hover post and convert it to a number
	if ($enableHover == "true") 
		$hoverBool = "1"; 
	else
		$hoverBool = "0"; 
			
	// check the css name for element indicators '.' or '#', and remove them
	if ($cssName[0] == "#" || $cssName[0] == ".")
		$cssName = substr($cssName, 1);
	
	if ($cssType == "" || $cssType == null || $cssName == "" || $cssName == null) 
	{
		$_SESSION['ttfMessage'] .= "Please fill out all required fields and try again. <br />";
	} 
	else 
	{
		// find the font ID 
		$fontID = $wpdb->get_var("SELECT id FROM ".WP_DB_FONTS." WHERE name = \"".$fontName."\"");
		
		// insert the new data to the db
		$query = "INSERT INTO ".WP_DB_CSS." (css_name, css_type, css_font_id, enable_hover, hover_color) VALUES ('".$cssName."', '".$cssType."', '".$fontID."', '".$hoverBool."', '".$hoverColor."')";
		
		if ($wpdb->query($query)) 
			$_SESSION['ttfMessage'] .= "CSS assignment successful. <br />";
		else 
			$_SESSION['ttfMessage'] .= "CSS assignment failed. Please try again. <br />";
		
	}

}


/**
 * updateCSS
 * Updates the CSS options
 */

function updateCSS() 
{
	global $wpdb, $ttfOptions;

	// get post data
	$cssID = $_POST['cssID'];
	$fontID = $_POST['fontUpdate']; 
	$enableHover = $_POST['enableHover'.$cssID];
	$hoverColor = checkColor($_POST['hoverColor']); 
	
	// check the hover post and convert it to a number
	if ($enableHover == "true")
		$hoverBool = "1"; 
	else
		$hoverBool = "0"; 
	
	$query = "UPDATE ".WP_DB_CSS." SET css_font_id = '".$fontID."', enable_hover = '".$hoverBool."', hover_color = '".$hoverColor."' WHERE id = ".$cssID."";
	
	if ($wpdb->query($query)) 
		$_SESSION['ttfMessage'] .= "CSS update successful. <br />";
	else 
		$_SESSION['ttfMessage'] .= "CSS update failed. Please try again. <br />";

}

 

/**
 * refresh
 * Redirects to wordpress plugin page for clean URLs
 */

function refresh() 
{
	//used to clean the url and refresh the page
	$deleteGoTo = "?page=ttfgen.php";
	header ("Location: $deleteGoTo"); 
}


// ************* Delete a Font 

function deleteFont() 
{
	global $wpdb, $ttfOptions;
	$fontID = $_POST['fontID'];
	
	// get the font file name
	$fileName = $wpdb->get_var("SELECT font FROM ".WP_DB_FONTS." WHERE id=\"".$fontID."\"");
	
	// remove the font file
	$target = $ttfOptions['fontFolder'] . $fileName;
	unlink($target);

	// remove the font entry from the database
	$fontQ = "DELETE FROM ".WP_DB_FONTS." WHERE id=\"".$fontID."\""; 
	$dff = $wpdb->query($fontQ);	
	
	if ($dff)
		$_SESSION['ttfMessage'] .= "Font Deleted Successfully. <br />";
	else 
		$_SESSION['ttfMessage'] .= "Font Deletion Unsuccessful. Please try again. <br />";
}

// ************* Delete a CSS Assignment

function deleteCSS() 
{
	
	global $wpdb, $ttfOptions; 
	$cssID = $_POST['cssID']; 

	// remove the font entry from the database
	$fontQ = "DELETE FROM ".WP_DB_CSS." WHERE id=\"".$cssID."\""; 
	$wpdb->query($fontQ);

	$_SESSION['ttfMessage'] .= "CSS Assignment Deleted Successfully. <br />";
			
}

function getFonts() 
{
   	global $wpdb, $ttfOptions;
	$result = $wpdb->get_results("SELECT * FROM ".WP_DB_FONTS);
	return $result; 
}


function getCSS() 
{
   	global $wpdb, $ttfOptions;
	$result = $wpdb->get_results("SELECT * FROM ".WP_DB_CSS);
	return $result; 	
}

function getFontName($fontID) 
{
   	global $wpdb, $ttfOptions;
	$fontName = $wpdb->get_var("SELECT font FROM ".WP_DB_FONTS." WHERE id=\"".$fontID."\"");
	return $fontName; 	
}

function displayMessage() 
{
	global $wpdb, $ttfOptions;
	echo '<div id="ttfMessage">'.$_SESSION['ttfMessage'].'</div>';  
	$_SESSION['ttfMessage'] = ""; 
}
 

/**
 * Update General
 * Updates the general settings fields
 */ 

function updateGeneral() 
{
	global $wpdb, $ttfOptions; 
 
	$options = get_option(TTF_OPTIONS);

	$options['loadLocation'] = $_POST['loadLocation'];
	$options['fontFolder'] = $_POST['fontFolder'];
	$options['fontCache'] = $_POST['fontCache'];
	$options['useTTFjQuery'] = $_POST['useTTFjQuery'];
	$options['useTTFMeta'] = $_POST['useTTFMeta'];

	update_option(TTF_OPTIONS, $options);

	$_SESSION['ttfMessage'] .= "Database Options Updated <br />";
	
	//reload
	$ttfOptions = get_option(TTF_OPTIONS);
	

	
}

/**
 * Check Color
 * Updates the general settings fields
 */ 

function checkColor($color) 
{
	if ($color[0] != "#" && $color != null)
		$color = "#".$color; 
	
	return $color; 
	
}

 

/**
 * TTFGen Admin Options Page 
 */
	
function ttfOptionsPage() 
{
	global $ttfOptions, $wpdb;	
	
	// break PHP to start javascript and HTML	
	?>
	
	<script type="text/javascript">

		function confirmDelete(delString) { 
			confirm.value = confirm(delString); 
		 	return confirm.value;  
		} 
					
	</script>
	 
	<style type="text/css">
		
		h3 {
			color: #21759B; 
			font-size: 15px;  
			margin: 0px; 
			padding-top: 5px; 
			padding-bottom: 10px; 
		}
		
		.rowOdd { 
			background-color:#e9e9e9; 
		}
		
		#fontTable, #cssListTable  { 
			width:100%; 
		}	
		
		#fontTable, #cssAddTable, #cssListTable  {
			text-align:left;
			padding-bottom: 10px; 
		}
		
		#fontTable th, #cssListTable th {	
			padding: 5px; 
			border-bottom: 1px solid #ccc; 
			font-size: 13px;
			color: #555; 
			font-weight: bold;  
						 
		}
		
		#fontTable td, #cssListTable td { 
			padding: 5px; 
			border-bottom: 1px solid #CCC;  
		}
		
		.sectionWrapper {
			padding: 10px; 
			width: 99%; 			
		}
		.colorWrapper {
			background-color: #f0f0f0; 
			padding: 10px; 
			width: 99%; 
			border-bottom: 1px solid #ccc; 
			border-top: 1px solid #ccc;   
		
		}
		#ttfMessage {
			color: #FF0000; 
			font-size: 14px; 
			padding: 5px;
			padding-bottom: 17px;  
		} 
		
	</style>
	 
	 <div class="wrap">
	 	<h2><?php _e('TTFGen Options','ttfText'); ?></h2>
			<?php wp_nonce_field($nonce_base) ?>
		<p><?php _e("<a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9942549' target='_blank'>If you find this plugin useful, please consider making a PayPal donation of $2</a>. 
					<br /> 50% of every donation will go towards the education and care of a teenage autistic girl. 
					<br /> The other 50% will go towards the blossoming Hockey career of a young grade-school boy.
					<br /> <br /> <a href='http://www.ttfgen.com/guide/' target='_blank'>Found a bug? Please report it!</a><br />", 'wp_ttfgen'); ?></p>
		<?php displayMessage(); ?>
	 
		<div class="colorWrapper">
			<h3>General Settings</h3>
			<form action="?page=ttfgen.php&action=updateGeneral" method="post" id="ttfgen_assign" name="ttfgen_assign" class="form-table" style="padding-bottom: 10px"> 
				<label style="font-weight:bold;"><?php _e("Where to Load TTFGen:",'wp-ttfgen'); ?> </label>
				
				<input type="radio" name="loadLocation" value="header" <?php if ($ttfOptions['loadLocation'] == "header") echo 'CHECKED'; ?>><?php _e(" Header",'wp-ttfgen'); ?>
				 
				<input type="radio" name="loadLocation" value="footer" <?php if ($ttfOptions['loadLocation'] == "footer") echo 'CHECKED'; ?>> <?php _e(" Footer",'wp-ttfgen'); ?>
				 
				 <p><label>Font Location</label><input type="text" name="fontFolder" id="fontFolder" value="<?php echo $ttfOptions['fontFolder']; ?>" size="60" /> </p>
				 
				 <p><label>Image Cache Location</label><input type="text" name="fontCache" id="fontCache" value="<?php echo $ttfOptions['fontCache']; ?>" size="60" /></p>
				 
				<p><INPUT TYPE="checkbox" NAME="useTTFjQuery" width="250" id="useTTFjQuery"   
						<?php if ($ttfOptions['useTTFjQuery']) echo " checked"; ?>	> 
						<?php _e("Use included jQuery file (Do not uncheck unless you are manually loading jQuery elsewhere)",'wp-ttfgen'); ?> 	
				</p>
				<p>
				<INPUT TYPE="checkbox" NAME="useTTFMeta" width="250" id="useTTFMeta"   
						<?php if ($ttfOptions['useTTFMeta']) echo " checked"; ?>	> 
						<?php _e("Use included jQuery metadata plugin (Do not uncheck unless you are manually loading the metadata plugin elsewhere)",'wp-ttfgen'); ?> 					
				</p>
				<input name="Update" type="submit" value="Update" style="margin-top: 10px; margin-right:10px; float:left; " />
				</form>
				<form action="?page=ttfgen.php&action=loadDefaults" method="post" id="ttf_load_defaults" name="ttf_load_defaults" style="padding-bottom: 10px"> 
				 <input type="submit" name="Load Defaults" value="Load Defaults" style="margin-top: 8px;" />
				 </form>
				
				<?php //$_SESSION['ttfMessage'] = ""; ?>
						
		</div> 

		
		<!-- Display the Font List -->
		
		<div class="sectionWrapper">
			<h3>Available Fonts</h3>
			
			<?php  
				$fontList = getFonts(); 
				if ($fontList == null) 
				{ 
					_e("You have not uploaded any fonts.", 'wp-ttfgen'); 
				} 
				else 
				{
					// start the table
					echo '<table id="fontTable" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<th>Name</th>
								<th>Font</th>
								<th>Action</th>
							</tr> 
						';  
						
					for ($i=0; $i < count($fontList); $i++) 
					{
						$curID = $fontList[$i]->id; 
						$curName = $fontList[$i]->name; 
						$curFont = $fontList[$i]->font; 
						$delString = "Are you sure you want to delete \'".$curFont."\'? This will also delete any CSS associations.";
						
						if ($i & 1)
							echo '<tr class="rowOdd">';
						else 
							echo '<tr class="rowEven">';
							 
						echo '
								<td>'.$curName.'</td>
								<td>'.$curFont.'</td>
								<td>
	
								<form action="?page=ttfgen.php&action=deleteFont" method="post" id="ttfgen_delete" name="ttfgen_delete">  
									<input type="hidden" name="fontID" id="fontID" value="'.$curID.'" />
									<input name="Delete" type="submit" value="Delete" onclick="return confirmDelete(\''.$delString.'\')"/>
								</form> 
								
								</td>
							</tr>
						';
					}
					
				// close table
				echo '</table>'; 
					
				}
			?>
		</div>
	
	<div class="colorWrapper">

		<h3>Add a New Font</h3>
		
		<?php 
	
		$max_upload_size = 1000000; 
		$max_upload_size_text = '';					
			
		?>
			<form enctype="multipart/form-data" action="?page=ttfgen.php&action=addFont&method=upload" method="post" id="ttfgen_add" name="ttfgen_add" class="form-table"> 
	            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_upload_size; ?>" />
	            <table class="optiontable niceblue" cellpadding="0" cellspacing="0"> 
	                <tr>
	                    <th scope="row" style="width:100px"><strong><?php _e('Font Name',"wp-ttfgen"); ?></strong></th> 
	                    <td>
	                        <input type="text" style="width:320px;" class="cleardefault" name="fontName" id="fontName" maxlength="200" />												
	                    </td> 
	                </tr>
					<tr valign="top">
							<th scope="row" style="width:100px"><strong><?php _e('Select a font',"wp-ttfgen"); ?></strong></th> 
							<td><input type="file" name="uploadedFile" style="width:320px;" /><br />
								<span class="setting-description">
									<?php _e('Max. filesize',"wp-ttfgen"); echo $max_upload_size_text; ?> = <?php echo $max_upload_size; ?> <?php _e('bytes',"wp-ttfgen"); ?>.
									<br /> <?php _e('If a font with the same name already exists it will be renamed automatically.',"wp-ttfgen"); ?>
								</span>
							</td>
	                </tr> 
					<tr>
						<td>
						<input type="submit" value="Add Font" name="submit" /></td>
					</tr>
	
	            </table>
			 
			</form>

	</div>
	
	<div class="sectionWrapper">
		
		<h3>CSS Font Assignments</h3>
			
            <table id="cssAddTable" class="optiontable niceblue" cellpadding="0" cellspacing="0"> 

			</table>
			
				
			<table id="cssListTable" cellpadding="0" cellspacing="0"> 
			  <tr>
                    <th><strong><?php _e('CSS Type',"wp-ttfgen"); ?></strong></th> 
					<th><strong><?php _e('CSS Name',"wp-ttfgen"); ?></strong></th> 
					<th><strong><?php _e('Current Font',"wp-ttfgen"); ?></strong></th>
					<th><strong><?php _e('Enable Rollover Color',"wp-ttfgen"); ?></strong></th> 
					<th><strong><?php _e('Rollover Color',"wp-ttfgen"); ?></strong></th> 
					<th><strong><?php _e('Assign Font',"wp-ttfgen"); ?></strong></th>
					<th><strong><?php _e('Action',"wp-ttfgen"); ?></strong></th>
                </tr>

				
				<tr>
					<form action="?page=ttfgen.php&action=assignCSS" method="post" id="ttfgen_assign" name="ttfgen_assign" class="form-table"> 
						
						<!-- CSS Type -->
						<td>
							<input type="radio" name="cssType" value="ID" > ID
							<input type="radio" name="cssType" value="Class" checked="checked" > Class
							<input type="radio" name="cssType" value="Element" > Element
						</td>
						
						<!-- CSS Name -->
						
						<td><input type="text" name="cssName" id="cssName" maxlength="100" /></td>
						
						<!-- Existing Font -->
						<td></td>
						
						<!-- Enable Rollover -->
						<td>
							<input type="radio" name="enableHover" value="true" > Yes
							<input type="radio" name="enableHover" value="false" checked="checked" > No
						</td>
						
						<!-- Rollover Color -->
						<td><input type="text" name="hoverColor" id="hoverColor" maxlength="7" size="7" /></td>
						
						
						<!-- Select Font -->
						<td>
							<select id="fontName" name="fontName">		
								<?php 
									for ($i=0; $i < count($fontList); $i++) {
										echo '<option value="'.$fontList[$i]->name.'">'.$fontList[$i]->name.'</option>';
									}
								?>
							</select>
							
						</td>

						<td><input type="submit" value="Assign Font" name="Assign Font"></td>	
					</form>		
				</tr>		
				 
				<?php  
					$cssList = getCSS(); 
					$fontList = getFonts(); 
					
					if (count($cssList) > 0)
					{			
						// Display the existing CSS List						
						for ($i=0; $i < count($cssList); $i++) 
						{
							$cssID = $cssList[$i]->id;
							$type = $cssList[$i]->css_type; 
							$name = $cssList[$i]->css_name; 
							$fontID = $cssList[$i]->css_font_id; 
							$enableHover = $cssList[$i]->enable_hover; 
							$hoverColor = $cssList[$i]->hover_color; 
							$fontName = getFontName($fontID); // get the the font name from the ID
							$cssDelString = "Are you sure you want to delete the CSS assignment for \'".$name."\'?";

							if ($i & 1)
								echo '<tr class="rowEven">';
							else 
								echo '<tr class="rowOdd">';
													
							echo '
									<td>'.$type.'</td> 
									<td>'.$name.'</td>
									<td>'.$fontName.'</td>
									
									<td>
										<form action="?page=ttfgen.php&action=updateCSS" method="post" id="ttfgen_update_css" name="ttfgen_update_css"> 
																			
										<input type="radio" name="enableHover'.$cssID.'" value="true" ';
										if ( $enableHover == true )  
											echo 'checked="checked" ';
											echo '> Yes
											
										<input type="radio" name="enableHover'.$cssID.'" value="false" ';
										
										if ( $enableHover != true )
											echo 'checked="checked" ';
											echo '> No										
									</td>
									
									<td><input type="text" name="hoverColor" id="hoverColor" maxlength="7" value="'.$hoverColor.'" size="7" /></td>
						
									<td>

										<select id="fontUpdate" name="fontUpdate">		
										'; 
										 
								for ($f=0; $f < count($fontList); $f++) 
								{

									echo '<option name="'.$fontList[$f]->name.'" value="'.$fontList[$f]->id.'"';
										if ($fontID == $fontList[$f]->id)
											echo ' selected="yes" '; 
									
									echo '>'.$fontList[$f]->name.'</option>';
								} 
					
			 				echo '</select></td>
									<td>
										 
											<input type="hidden" name="cssID" id="cssID" value="'.$cssID.'" />
											<input name="Update" type="submit" value="Update" style="float:left; margin-right: 10px" />
										</form>
										
										<form action="?page=ttfgen.php&action=deleteCSS" method="post" id="ttfgen_delete_css" name="ttfgen_delete_css">  
											<input type="hidden" name="cssID" id="cssID" value="'.$cssID.'" />
											<input name="Delete" type="submit" value="Delete" onclick="return confirmDelete(\''.$cssDelString.'\')" style="float:left; margin-right: 10px"/>
										</form>
									</td> 
								</tr>
							';
						}
						
					}
				?>
            </table>	
					
		</div>

	<div class="footerSpacer" style="padding-top: 20px; clear:both;"></div>
		
</div>
	

		
	
<?php
	}

// admin Options Page End


/**
 * 
 * PAGE LOAD FUNCTION 
 * This function will take the parameters specified in the admin panel 
 * and load the jQuery options within the wp_footer() section of the site
 * 
 */

function ttfLoad() 
{
	global $wpdb, $ttfOptions;

	$fontList = getFonts(); 
	$cssList = getCSS();
	
	$fontPath = $ttfOptions['fontFolder']; 
	$fontCache = $ttfOptions['fontCache'];
	
	// if no fonts are found or no CSS assignments are set, end the program. 
	if ($fontList == null || $cssList == null) 
	{
		die();
	}
	else 
	{
		echo "\n\n<!-- Start TTFGen Code -->\n\n"; 
				
		// if useJquery option is true, load jQuery
		if ($ttfOptions['useTTFjQuery'])
			echo "<script type='text/javascript' src='".TTF_PATH_SHORT."scripts/jquery-min.js'></script>\n"; 

		// if useMeta option is true, load jQuery metadata plugin
		if ($ttfOptions['useTTFMeta'])
			echo "<script type='text/javascript' src='".TTF_PATH_SHORT."scripts/jquery.metadata.js'></script>\n"; 		
		
		// load the jQuery TTFGen file 
		echo "<script type='text/javascript' src='".TTF_PATH_SHORT."scripts/jquery.ttfgen.js'></script>\n\n"; 

		// Begin main script load
		echo "<script type='text/javascript'>\n";
		echo "   $(document).ready(function() {\n";  
		
		// Begin the cycle through the cssList to create the calls to ttfgen
		for ($i=0; $i < count($cssList); $i++) 
		{
			$type = $cssList[$i]->css_type; 
			$name = $cssList[$i]->css_name; 
			$fontID = $cssList[$i]->css_font_id; 
			$fontName = getFontName($fontID); // get the the font name from the ID 
	
			$enableHover = $cssList[$i]->enable_hover; 
			$hoverColor = $cssList[$i]->hover_color;
			
			
			// class or ID?
			if ($type == "Class")
				$ident = "."; 
			else if ($type == "ID")
				$ident = "#";
			else if ($type == "Element")
				$ident = ""; 
				 
			// echo the final jQuery string 	
			
			if ($enableHover)
				echo "      $('".$ident.$name."').ttfgen( { font: '".$fontName."', hiFgColor: '".$hoverColor."', asBackground: true } );\n"; 
			else
				echo "      $('".$ident.$name."').ttfgen( { font: '".$fontName."', asBackground: true } );\n";

		}
		
		// end script  
		echo "   });\n"; 
		echo "</script>\n\n"; 	 
		
		echo "<!-- End TTFGen Code -->\n\n"; 
		
	}
}

 
 
// load TTFGen in the header or footer
if ($ttfOptions['loadLocation'] == "header")
	add_action('wp_header', 'ttfLoad');
else 
	add_action('wp_footer', 'ttfLoad');



?>
<?php
/**
 * @package Wordibbit
 * @author Robert Lowe
 * @version 0.1
 */
/*
Plugin Name: Wordibbit - Ribbit Click-to-Call
Plugin URI: http://www.iblargz.com/wordpress-plugins/wordibbit-click-to-call-for-wordpress
Description: Add 2-legged click-to-call functionality
Version: 0.1
Author URI: http://www.iblargz.com/
*/
require_once('Ribbit.php');



/* Add our function to the widgets_init hook. */
add_action( 'widgets_init', 'wordibbit_load_widgets' );

/* Function that registers our widget. */
function wordibbit_load_widgets() {
	register_widget( 'Worddibit_Widget' );
}


class Worddibit_Widget extends WP_Widget {

  function Worddibit_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'Worddibit', 'description' => 'Worddibit Widget displays an form to connect calls to you via a widget position on the frontend.' );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300,  'id_base' => 'wordibbit-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'wordibbit-widget', 'Wordibbit', $widget_ops, $control_ops );
	}
	
	
	function widget( $args, $instance ) {
    extract( $args );
    
    $wordibbit_on = get_option('wordibbit_on');
    if (empty($wordibbit_on)){ return false; }
      
    echo "<a name='calling'></a>";

		/* User-selected settings. */
    $title = apply_filters('widget_title', $instance['title'] );
    $dtext = $instance['dtext'];

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Title of widget (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;

    // This just echoes the chosen line, we'll position it later
  	echo '<p id="wordibbit">' .$dtext. '</p>';


    if(!empty($_POST['destination_phone_number']) && ereg("^[0-9]{3}-?[0-9]{3}-?[0-9]{4}$", $_POST['destination_phone_number'])){
      
      try {
        $ribbit = Ribbit::getInstance();

        $wordibbit_domain           = stripslashes(base64_decode(get_option('wordibbit_domain')));
        $wordibbit_password         = stripslashes(base64_decode(get_option('wordibbit_password')));

        $wordibbit_consumer_key     = stripslashes(base64_decode(get_option('wordibbit_consumer_key')));
        $wordibbit_secret_key       = stripslashes(base64_decode(get_option('wordibbit_secret_key')));
        
        $wordibbit_application_id   = stripslashes(base64_decode(get_option('wordibbit_application_id')));
        $wordibbit_account_id       = stripslashes(base64_decode(get_option('wordibbit_account_id')));

        $wordibbit_phone            = stripslashes(base64_decode(get_option('wordibbit_phone')));
      
        //Check if tel:1 forces North American calls 
        $source = "tel:1" . str_replace("-", "", $wordibbit_phone); 
        $dest   = "tel:1" . str_replace("-", "", $_POST['destination_phone_number']);

        $ribbit->setApplicationCredentials($wordibbit_consumer_key, $wordibbit_secret_key, $wordibbit_application_id, $wordibbit_domain, $wordibbit_account_id);
        
        $ribbit->Login($wordibbit_domain, $wordibbit_password);
        
        $application_id = $ribbit->getCurrentApplicationId();
        $ribbit->Applications()->updateApplication(null, true, $wordibbit_domain, $application_id);
        
        
        $call = $ribbit->Calls()->createThirdPartyCall($source, array($dest) );
      
        echo "<p>Thank you, will call you in seconds...if not just call us at: " . $wordibbit_phone . "</p>";
      
      } catch (RibbitException $e){
        //echo "A ". $e->getStatus() ." error occured - " . $e->getMessage();
        echo "<p>An error occured, please try calling us at: " . $wordibbit_phone . "</p>";
			}
			
    } else {
  
      if(!empty($_POST['destination_phone_number'])){
        $strWarningMessage = "<p>Sorry, we couldn't validate your number, enter it like 647-123-1234 or 6471231234</p>";
      }
      
      ?>
      <form name="wordibbit" action="#calling" method="POST" class="wordibbit">
        <?php echo $strWarningMessage; ?>
        <div> Ex: 6471234321 </div>
        <input name="destination_phone_number" value="<?php echo $_POST['destination_phone_number']; ?>" size="10">
        <input name="wordibbit_submit" type="submit" value="Call Me Now" onclick="this.disabled=true;">
      </form>
      <?php
      
      		/* After widget (defined by themes). */
      		echo $after_widget;      
    }
    
		
	}
	
	
		function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

    /* Strip tags (if needed) and update the widget settings. */
    $instance['title'] = strip_tags( $new_instance['title'] );
    $instance['dtext'] = strip_tags( $new_instance['dtext'] );
    

		return $instance;
	}
	
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => 'Call Us', 'dtext' => "Contact us by entering your phone number below and we'll call you, no fuss.");
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'dtext' ); ?>">Text (each row will be displayed randomly, use Notepad to copy and paste):</label>
<textarea rows="4" cols="34" id="<?php echo $this->get_field_id( 'dtext' ); ?>" name="<?php echo $this->get_field_name( 'dtext' ); ?>" value="<?php echo $instance['dtext']; ?>"><?php echo $instance['dtext']; ?></textarea>
</p>
<?php
	}
}


/*
 * Admin User Interface
 */

function wordibbit_options_setup() {
	$wordibbit=get_plugin_data(__FILE__);
  add_options_page($wordibbit['Name'], 'Wordibbit', 7, basename(__FILE__), 'wordibbit_main_page');
}
add_action('admin_menu', 'wordibbit_options_setup');

function wordibbit_main_page() {
  if ( function_exists('current_user_can') && !current_user_can( 7 ) )  wp_die( __('You do not have sufficient permissions to access this page.') );
	
	$mymess=' ';
	$myerr=' ';
  
  $wordibbit_domain           = stripslashes(base64_decode(get_option('wordibbit_domain')));
  $wordibbit_password         = stripslashes(base64_decode(get_option('wordibbit_password')));
  
  $wordibbit_consumer_key     = stripslashes(base64_decode(get_option('wordibbit_consumer_key')));
  $wordibbit_secret_key       = stripslashes(base64_decode(get_option('wordibbit_secret_key')));
  
  $wordibbit_application_id = stripslashes(base64_decode(get_option('wordibbit_application_id')));
  $wordibbit_account_id       = stripslashes(base64_decode(get_option('wordibbit_account_id')));
    
  $wordibbit_phone            = stripslashes(base64_decode(get_option('wordibbit_phone')));
  
  if($_SERVER['REQUEST_METHOD']==='POST'){

		if(isset($_POST['wordibbit_domain']))	{
			update_option('wordibbit_domain',base64_encode($_POST['wordibbit_domain']));
			$wordibbit_domain = stripslashes(base64_decode(get_option('wordibbit_domain')));
		}

		if(isset($_POST['wordibbit_password']))	{
			update_option('wordibbit_password',base64_encode($_POST['wordibbit_password']));
			$wordibbit_password = stripslashes(base64_decode(get_option('wordibbit_password')));
		}

		if(isset($_POST['wordibbit_consumer_key']))	{
			update_option('wordibbit_consumer_key',base64_encode($_POST['wordibbit_consumer_key']));
			$wordibbit_consumer_key = stripslashes(base64_decode(get_option('wordibbit_consumer_key')));
		}

		if(isset($_POST['wordibbit_secret_key']))	{
			update_option('wordibbit_secret_key',base64_encode($_POST['wordibbit_secret_key']));
			$wordibbit_secret_key = stripslashes(base64_decode(get_option('wordibbit_secret_key')));
		}

		if(isset($_POST['wordibbit_application_id']))	{
			update_option('wordibbit_application_id',base64_encode($_POST['wordibbit_application_id']));
			$wordibbit_application_id = stripslashes(base64_decode(get_option('wordibbit_application_id')));
		}

		if(isset($_POST['wordibbit_account_id']))	{
			update_option('wordibbit_account_id',base64_encode($_POST['wordibbit_account_id']));
			$wordibbit_account_id = stripslashes(base64_decode(get_option('wordibbit_account_id')));
		}

		if(isset($_POST['wordibbit_phone']))	{
			update_option('wordibbit_phone',base64_encode($_POST['wordibbit_phone']));
			$wordibbit_phone = stripslashes(base64_decode(get_option('wordibbit_phone')));
		}

		if(isset($_POST['wordibbit_on'])){
		  if(!empty($wordibbit_domain)         && !empty($wordibbit_password)   && 
		     !empty($wordibbit_consumer_key)   && !empty($wordibbit_secret_key) && 
		     !empty($wordibbit_application_id) && !empty($wordibbit_account_id) && 
		     !empty($wordibbit_phone)) {
  			update_option('wordibbit_on', '1'); 
		  } else {
		    $myerr = "Unable to enable plugin, please fill in all fields accurately";
		  }
		}	else {
		  update_option('wordibbit_on','0');
    }

		if(strlen($mymess)>6)$mymess='<br /><div id="message" class="updated fade">'.$mymess.'</div>';
		if(strlen($myerr)>6)$myerr='<br /><div id="message" class="error fade"'.$myerr.'</div>';
	} 


  ?>

<div class="wrap">
  <h2>
    <?php $wordibbit=get_plugin_data(__FILE__); echo $wordibbit['Name']; ?>
  </h2>
  
  <?php echo $mymess.$myerr; ?>
  
  <div class="wrap">
  	<h3>First you must: <a href="http://www.ribbit.com/">Get a Ribbit Account</a> &raquo;&raquo;</h3>
  </div>
  
  
  <form method="post" action="<?php echo attribute_escape($_SERVER["REQUEST_URI"]); ?>">
    
    <?php wp_nonce_field('wordibbit-update_modify'); ?>
    
    <table class="form-table">
    
      <tr valign="top">
        <th scope="row"><?php _e('Ribbit App ID') ?></th>
        <td>
          <input name="wordibbit_application_id" type="text" id="wordibbit_application_id" value="<?php echo htmlentities($wordibbit_application_id); ?>" size="10" /> <strong><em>* required</em></strong>
        </td>
      </tr>

      <tr valign="top">
        <th scope="row"><?php _e('Ribbit Account ID') ?></th>
        <td>
          <input name="wordibbit_account_id" type="text" id="wordibbit_account_id" value="<?php echo htmlentities($wordibbit_account_id); ?>" size="10" /> <strong><em>* required</em></strong>
        </td>
      </tr>

      <tr valign="top">
        <td colspan="2">&nbsp;</td>
      </tr>

      <tr valign="top">
        <th scope="row"><?php _e('Ribbit Consumer Key') ?></th>
        <td>
          <input name="wordibbit_consumer_key" type="text" id="wordibbit_consumer_key" value="<?php echo htmlentities($wordibbit_consumer_key); ?>" size="50" /> <strong><em>* required</em></strong>
        </td>
      </tr>
      
      <tr valign="top">
        <th scope="row"><?php _e('Ribbit Secret Key') ?></th>
        <td>
          <input name="wordibbit_secret_key" type="text" id="wordibbit_secret_key" value="<?php echo htmlentities($wordibbit_secret_key); ?>" size="50" /> <strong><em>* required</em></strong>
        </td>
      </tr>
      
      <tr valign="top">
        <td colspan="2">&nbsp;</td>
      </tr>
      
      <tr valign="top">
        <th scope="row"><?php _e('Ribbit Domain') ?></th>
        <td>
          <input name="wordibbit_domain" type="text" id="wordibbit_domain" value="<?php echo htmlentities($wordibbit_domain); ?>" size="50" /> <strong><em>* required</em></strong>
        </td>
      </tr>
      
      <tr valign="top">
        <td colspan="2">&nbsp;</td>
      </tr>
      
      <tr valign="top">
        <th scope="row"><?php _e('Ribbit Password') ?></th>
        <td>
          <input name="wordibbit_password" type="text" id="wordibbit_password" value="<?php echo htmlentities($wordibbit_password); ?>" size="50" /> <strong><em>* required</em></strong>
        </td>
      </tr>

      <tr valign="top">
        <td colspan="2">&nbsp;</td>
      </tr>
      
      <tr valign="top">
        <th scope="row"><?php _e('Phone') ?></th>
        <td>
          <input name="wordibbit_phone" type="text" id="wordibbit_phone" value="<?php echo htmlentities($wordibbit_phone); ?>" size="10" /> <strong><em>* required</em></strong><br />
          * the phone number you would like to receive calls on
        </td>
      </tr>
      
      
      <tr valign="top">
        <th scope="row"><?php _e('Enable Wordibbit Widget') ?></th>
        <td>
          <label for="wordibbit_on">
            <input type="checkbox" name="wordibbit_on" id="wordibbit_on" value="1" <?php if(get_option('wordibbit_on')=='1')echo 'checked="checked" ';?>/>
            <?php _e('Enabled') ?>
          </label>
        </td>
      </tr>
    </table>
    
    <p class="submit">
      <input name="wordibbitsubmitconfiguration" id="wordibbitsubmitconfiguration" value="<?php _e('Save Settings &raquo;'); ?>" type="submit" class="button valinp" />
    </p>
  </form>
  
</div>

<hr style="visibility:hidden;clear:both;" />

<div class="wrap">
	<p><a href="http://www.iblargz.com/">Robert Lowe, Toronto Web Developer</a> &raquo;&raquo;</p>
	<hr style="visibility:hidden;" />
  <p><a href="http://www.iblargz.com/wordpress-plugins/wordibbit-ribbit-click-to-call-for-wordpress">Wordibbit: Ribbit Click-to-Call For WordPress</a> &raquo;&raquo;</p>
  <p><br /><strong>Like this plugin? Support post-college freelance web developers below:</strong> <br />
    <!-- Feed hungry post-college developers below -->
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
      <input type="hidden" name="cmd" value="_s-xclick">
      <input type="hidden" name="hosted_button_id" value="9121760">
      <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
      <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
    </form>
  </p>
</div>

  
  
<?php
  }  
?>
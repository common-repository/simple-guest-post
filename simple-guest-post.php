<?php
/*
Plugin Name: Guest Post Plugin
Plugin URI: #
Description: Guest Post Plugin allows your visitors to submit posts without registration from anywhere on your site.
Version: 1.1
Author: Debabrat Sharma
Author URI: https://www.facebook.com/debabrat.sharma.31
*/

add_action( 'wp_enqueue_scripts', 'gpp_stylesheet' );

function gpp_stylesheet() {
    wp_register_style( 'prefix-style', plugins_url('style.css', __FILE__) );
    wp_enqueue_style( 'prefix-style' );
    wp_register_style( 'boot-style', plugins_url('bootstrap.css', __FILE__) );
    wp_enqueue_style( 'boot-style' );
}

function gpp_activate() {
 
    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/gpp_pdf';
    if (! is_dir($upload_dir)) {
       wp_mkdir_p( $upload_dir, 0700 );
    }
}
register_activation_hook(__FILE__, 'gpp_activate');

    
function gpp_notice() {
    $check= get_option('gpp_postAuthor');
    if(!$check){
      ?>
      <div class="notice notice-info is-dismissible">
          <p><?php _e( 'Please select a user with contributor role under plugin settings for plugin shortcode to work!', 'sample-text-domain' ); ?></p>
      </div>
      <?php
  }
}
add_action( 'admin_notices', 'gpp_notice' );
 


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'gpp_links' );

function gpp_links( $links ) {
   $links[] = '<a href="'. esc_url( get_admin_url(null, 'edit.php?post_type=guestpost&page=simple-guest-post.php') ) .'">Settings</a>';
   
   return $links;
}


if ( is_admin() ){

add_action('admin_menu' , 'gpp_settings'); 

 }

    function gpp_settings() {
        add_submenu_page('edit.php?post_type=guestpost', 
                        'Guest post Settings', 
                        'Settings', 
                        'administrator', 
                        basename(__FILE__), 
                        'gpp_registerSettingsPage');

      add_action( 'admin_init', 'gpp_registerSettings' );
    }

    function gpp_registerSettings() {
      register_setting( 'gpp_registerSettingsGroup', 'gpp_postAuthor' );
    } 

  function gpp_registerSettingsPage() {
     ?>
     <div class="wrap">
      <h1> Select a user from the list:</h1>
        <form method="post" action="options.php">
          <?php settings_fields( 'gpp_registerSettingsGroup' ); ?>
          <?php do_settings_sections( 'gpp_registerSettingsGroup' ); ?>
          <?php //wp_nonce_field( 'gpp_uaction', 'gpp_ufield' ); ?>
          <table class="form-table">
            
              <tr valign="top">
                <th scope="row"><label for="blogname">Users</label></th>
                <td>
                  <select name="gpp_postAuthor">
                    <?php 
                      $args = array(
                                   'role' => 'contributor',
                                   'orderby' => 'user_nicename',
                                   'order' => 'ASC'
                                  );
                                   $contributors = get_users($args);

                    foreach ($contributors as $user) {
                          echo '<option value='.$user->ID.'>'.$user->display_name.'</option>';
                        }
                    ?>
                  </select>
                </td>
              </tr>
           
          </table>
          <?php submit_button(); ?>
          </form>
    </div>

    <?php

}


function gpp_shortcode( $atts ) {


    extract ( shortcode_atts (array(
        'cat' => '1',
        'redirect' => get_bloginfo('home'),
    ), $atts ) );

    return '
    <div class="test">
    <form class="fbt-simple-guest-post" action="" method="post">
	<input type="hidden" name="gptask" value="savepost" />
<p>The (*) marked fields are mandatory.</p>

        <div class="form-group"><label class="lab">Your Name:* </label><br>
          <input type="text" class="form-control inp" name="gname" pattern="[a-zA-Z][a-zA-Z\s]*" maxlength="30" required>
        </div><br><br>
        <div class="form-group"><label class="lab">Email Address:* </label><br>
          <input type="email" class="form-control inp" name="email" required>
        </div><br><br>
        <div class="form-group"><label class="lab">Mobile No:* </label><br>
          <input type="text" class="form-control inp" name="phone" pattern="[0-9]{10}" required>
        </div><br><br>
        <div class="form-group"><label class="lab">Gender:* </label><br>
          <input type="radio" name="gender" value="male" required> Male<br>
          <input type="radio" name="gender" value="female"> Female
        </div><br><br>
        <div class="form-group"><label class="lab">Communication Address:* </label>
          '. wp_nonce_field() .'
          <textarea class="form-control inp" rows="5" cols="50" required name="commadd"></textarea>
        </div><br><br>
        <div class="form-group"><label class="lab">Permanent Address:* </label>
          <textarea class="form-control inp" rows="5" cols="50" required name="padd"></textarea>
        </div><br><br>



<br><br><br>
        <input type="hidden" value="'. $redirect .'" name="redirect">
        
<button type="submit" class="btn btn-primary">Submit</button>
<button type="reset" class="btn btn-primary">Reset</button> <br>
        </form>
        </div>
	<br>
	<br>
	<br>
	';


    }
	function gpp_save(){

  if(isset($_POST['gptask']) && $_POST['gptask'] == 'savepost' && wp_verify_nonce($_POST["_wpnonce"])){
    
               ob_start();
                        $title = sanitize_text_field( $_POST["title"] );
                        $email = sanitize_email( $_POST["email"] );
                        $phone=is_int( $_POST["phone"] );
                        $gender=sanitize_text_field( $_POST["gender"] );
                        $commadd=sanitize_text_field( $_POST["commadd"] );
                        $padd=sanitize_text_field( $_POST["padd"] );
                        $gname = sanitize_text_field( $_POST["gname"] );
                        $redirecturl = esc_url( $_POST["redirect"]);
                
                   
                   //$user_id = get_post_meta( $post->ID, 'gpp_postAuthor' );  
                        $user_id= get_option('gpp_postAuthor');
                   //Post Properties
                    $new_post = array(
                            'post_title'    => $gname,
                            'post_status'   => 'pending',     
                            'post_type'     => 'guestpost', 
                            'post_author'   => $user_id
                            
                    );
                    //save the new post
                    $pid = wp_insert_post($new_post);
                     
                    /* Insert Form data into Custom Fields */
                    add_post_meta($pid, 'guest-name', $gname, true);
                    add_post_meta($pid, 'guest-email', $email, true);
                    add_post_meta($pid, 'guest-gender', $gender, true);
                    add_post_meta($pid, 'guest-commadd', $commadd, true);
                    add_post_meta($pid, 'guest-padd', $padd, true);
                    add_post_meta($pid, 'guest-phone', $phone, true);
                    
                    

                header("Location: $redirecturl");
                ob_end_flush();
         }

}
  
add_action("wp", "gpp_save");
	
	$check= get_option('gpp_postAuthor');
    if($check) {
    add_shortcode( 'guest-post', 'gpp_shortcode' );
    }



add_action( 'init', 'gpp_create' );
function gpp_create() {
/*****************************************************/
 $labels = array(
        'name'                  => ( 'Guest Posts'),
        'singular_name'         => ( 'Guest Post'),
        'menu_name'             => ( 'Guest Post'),
        'add_new_item'          => __( 'Add New Guest Post', 'textdomain' ),
        'new_item'              => __( 'New Guest Post', 'textdomain' ),
        'edit_item'             => __( 'Edit Guest Post', 'textdomain' ),
        'view_item'             => __( 'View Guest Post', 'textdomain' ),
        'all_items'             => __( 'All Guest Posts', 'textdomain' ),
        'search_items'          => __( 'Search Guest Posts', 'textdomain' ),
        'not_found'             => __( 'No Guest Posts found.', 'textdomain' ),
        'not_found_in_trash'    => __( 'No Guest Posts found in Trash.', 'textdomain' ),
        );
 
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'menu_icon'          => 'dashicons-businessman',
        'rewrite'            => true,
        'menu_position'      => null,
        'supports'           => array( 'title', 'custom-fields' ),
    );

register_post_type( 'guestpost', $args );

/*****************************************************/
}


if( is_admin()) {
            function gpp_postNotification($post_id) {

              require_once('fpdf/fpdf.php');
              
                if( ( $_POST['post_status'] == 'publish' ) && ( $_POST['original_post_status'] != 'publish' ) ) {
               $post = get_post($post_id);
               $key = get_post_meta( $post->ID, 'guest-name' );
               $key1 = get_post_meta( $post->ID, 'guest-email' );
               $to = $key1;
               
               $key3 = get_post_meta( $post->ID, 'guest-commadd' );
               
               $key6 = get_post_meta( $post->ID, 'guest-gender' );
               $key7 = get_post_meta( $post->ID, 'guest-padd' );
               $key11 = get_post_meta( $post->ID, 'guest-phone' );

                  $name=uniqid().strtotime(current_time( 'mysql' ));


              class PDF extends FPDF
              {
                // Page header
                function Header()
                {
                    
                    // Arial bold 15
                    $this->SetFont('Arial','',10);
                    // Move to the right
                    $this->Cell(30);
                    // Title
                   $this->Multicell(0,5,"Your Submitted Information");
                   $this->Ln(20);    }

                // Page footer
                function Footer()
                { 
                    // Position at 1.5 cm from bottom
                    $this->SetY(-15);
                    // Arial italic 8
                    $this->SetFont('Arial','I',8);
                    // Page number
                    
                    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
                  
                }

              }
            $uploadpath = wp_upload_dir();
            $upload_dir = $uploadpath['basedir'];
            // Instanciation of inherited class
            $pdf = new PDF();
            $pdf->AliasNbPages();
            $pdf->AddPage('P', 'A4');
            $pdf->SetAuthor('Debabrat Sharma');
            $pdf->SetTitle('Guest post plugin');
            $pdf->SetFont('Times','',12);
            $pdf->Cell(0,10,'Name: '.$key[0],0,1);
            $pdf->Cell(0,10,'Email: '.$key1[0],0,1);
            $pdf->Cell(0,10,'Mobile Number: '.$key11[0],0,1);
            $pdf->Cell(0,10,'Gender: '.$key6[0],0,1);
            $pdf->Cell(0,10,'Communication Address: '.$key3[0],0,1);
            $pdf->Cell(0,10,'Permanent Address: '.$key7[0],0,1);
            $pdf->Output($upload_dir.'/gpp_pdf/'.$name.'.pdf','F');

               $message = '
                          <!DOCTYPE html>
                          <html>

                          <body>
                              <table>

                                  <tr>
                                      <td>
                                          <p>Hi,</p>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          <p>'.$key[0].'</p>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          <p>Thanks for registering with us.</p>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          <p>Kindly Check the attachment with the email.</p>
                                      </td>
                                  </tr>
                                  <tr>
                                      <td>
                                          <p>Take a printout of the attached file and this email.</p>
                                      </td>
                                  </tr>
                              </table>

                          </body>

                          </html>';
                   
               $headers = array('Content-Type: text/html; charset=UTF-8');
               $attachments = $upload_dir.'/gpp_pdf/'.$name.'.pdf';
               wp_mail($to, "Your document has been published.", $message, $headers,$attachments);
                }
            }
}
add_action( 'publish_guestpost', 'gpp_postNotification' );

?>
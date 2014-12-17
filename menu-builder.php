<?php
namespace WebsiteForms;
defined('ABSPATH') or die("Invalid access!");

class MenuBuilder
{
  public function __construct()
  {
    add_action( 'admin_menu', array( $this, 'create_menu_pages' ) );
    add_action( 'wp_ajax_'.plugin_constant("slug").'_wishpond_api', array( $this, 'wishpond_api' ) );
    add_action( 'send_headers', array( $this, 'add_cors_headers' ) );
  }

  /**
   * Returns an instance of this class. 
   */
  public static function get_instance() {
    if( null == self::$instance ) {
      self::$instance = new PageTemplater();
    }
    return self::$instance;
  }

  public function create_menu_pages()
  {
    add_menu_page(  
      __( 'Forms', plugin_constant("SLUG") ),          // The title to be displayed on the corresponding page for this menu  
      __( 'Forms', plugin_constant("SLUG") ),                  // The text to be displayed for this actual menu item  
      'administrator',            // Which type of users can see this menu  
      plugin_constant("SLUG") . '-website-forms',                  // The unique ID - that is, the slug - for this menu item  
      array( $this, 'display_website_forms' ),// The name of the function to call when rendering the menu for this page  
      plugins_url("assets/images/forms-plugin-icon.png", __FILE__),
      plugin_constant('MENU_INDEX')
    );
    add_submenu_page(
      plugin_constant("SLUG") . "-website-forms",
      __( "Forms", plugin_constant("SLUG") ),
      __( "Forms", plugin_constant("SLUG") ),
      "administrator",
      plugin_constant("SLUG") . "-website-forms",
      array( $this, 'display_website_forms' )
    );
    add_submenu_page(
      plugin_constant("SLUG") . "-website-forms",
      __( "Add New", plugin_constant("SLUG") ),
      __( "Add New", plugin_constant("SLUG") ),
      "administrator",
      plugin_constant("SLUG") . "-website-forms-create",
      array( $this, 'display_create_website_form' )
    );
    add_submenu_page(
      plugin_constant("SLUG") . "-website-forms",
      __( "Dashboard", plugin_constant("SLUG") ),
      __( "Dashboard", plugin_constant("SLUG") ),
      "administrator",
      plugin_constant("SLUG") . "-website-forms-dashboard",
      array( $this, 'display_dashboard' )
    );
    add_submenu_page(
      plugin_constant("SLUG") . "-website-forms",
      __( "Settings", plugin_constant("SLUG") ),
      __( "Settings", plugin_constant("SLUG") ),
      "administrator",
      plugin_constant("SLUG") . "-website-forms-settings",
      array( $this, 'display_settings_page' )
    );
  }

  public function display_website_forms()
  {
    wp_register_style(
      "website_forms_list_css",
      plugins_url("wishpond/assets/css/item-list.css", __FILE__)
    );
    wp_enqueue_style( "website_forms_list_css" );

    $wishpond_action  = preg_replace('/[^a-zA-Z\-_]+/i', "", $_GET["wishpond-action"]);
    $wishpond_id      = preg_replace('/[^0-9]+/i', "", $_GET["wishpond-id"]);

    if($wishpond_action != "") {
      if(!Storage::permalink_structure_valid()) {
        $notice = 'Invalid permalink structure. Please go to "Settings"->"Permalinks" and make sure your permalinks use the "postname"<br/> Otherwise, you have to manually paste the wishpond "Embed Code" into your homepage';
      }
      else {
        switch($wishpond_action)
        {
          case "make-homepage": {
            update_option( 'page_on_front', Form::get_by_wishpond_id($wishpond_id)->wordpress_post_id );
            update_option( 'show_on_front', 'page' );
            $notice = "Your Form is now your homepage. <a href='".Form::get_by_wishpond_id($wishpond_id)->url()."' target='_blank'>View Page</a>";
            break;
          }
          case "reset-homepage": {
            update_option( 'page_on_front', '' );
            update_option( 'show_on_front', '' );
            $notice = "Home page reset successfully";
            break;
          }
        }
      }
    }
    include_once "views/website-forms.php";
  }

  public function display_dashboard()
  {
    $wishpond_action  = preg_replace('/[^a-zA-Z]+/i', "", $_GET["wishpond-action"]);
    $wishpond_id      = preg_replace('/[^0-9]+/i', "", $_GET["wishpond-id"]);
    $wishpond_marketing_id  = preg_replace('/[^0-9]+/i', "", $_GET["wishpond-marketing-id"]);

    switch($wishpond_action)
    {
      case "edit": {
        $redirect_url = "/wizard/start?participation_type=landing_page&landing_page_type=forms&wizard=wizards%2Flanding_page&social_campaign_id=".$wishpond_id;
        break;
      }
      case "manage": {
        $redirect_url = "/central/marketing_campaigns/".$wishpond_marketing_id;
        break;
      }
      case "report": {
        $redirect_url = "/central/landing_pages/".$wishpond_id;
        break;
      }
      default: {
        $redirect_url = "/central/landing_pages";
        break;
      }
    }

    self::enqueue_scripts();
    $dashboard_page = new WishpondIframe($redirect_url);
    $dashboard_page->display_iframe(); 
  }

  public function display_create_website_form()
  {
    self::enqueue_scripts();
    $dashboard_page = new WishpondIframe( "/wizard/start?landing_page_type=forms&participation_type=landing_page&wizard=wizards%2Flanding_page", self::query_info_from_post_id() );
    $dashboard_page->display_iframe();
  }

  function display_settings_page()
  {
    self::enqueue_scripts();

    $post_error = "";
    if( $_POST["submit"] )
    {
      if( !$_POST["enable_automatic_authentication"] )
      {
        Storage::delete(plugin_constant("MASTER_TOKEN"));
        Storage::delete(plugin_constant("AUTH_TOKEN"));
      }
      else if($_POST["enable_guest_signup"])
      {
        $post_error = "Please disable Automatic Authentication to use Guest Signups";
      }
      else
      {
        $post_error = "Automatic authentication is a deprecated feature and can't be re-enabled"; 
      }

      if( !$_POST["enable_automatic_authentication"] )
      {
        if( $_POST["enable_guest_signup"] )
        {
          Storage::enable_guest_signup();
          $notice = "Guest signup enabled!";
        }
        else
        {
          Storage::disable_guest_signup();
          $notice = "Guest signup disabled!";
        }
      }
    }
    include_once 'views/settings.php';
  }

  public function query_info_from_post_id()
  {
    $post_id = intval( $_GET["post_id"] );
    $excerpt = get_excerpt_by_id( $post_id );

    $query_info = array();

    if( is_int( $post_id ) && $post_id > 0 )
    {
      $query_info = array(
        "ad_campaign[ad_creative][title]"             => substr( get_the_title( $post_id ), 0, 25 ),
        "ad_campaign[ad_creative][body]"              => $excerpt,
        "ad_campaign[ad_creative][link_url]"          => esc_url( get_permalink( $post_id ) ),
        "ad_campaign[ad_creative][destination_type]"  => "external_destination"
      );
    }
    return $query_info;
  }

  public function wishpond_api()
  {
    if (is_user_logged_in())
    {
      $nonce = $_POST['nonce'];
      $data = $_POST['data'];

      if ( ! wp_verify_nonce( $nonce, 'wishpond-api-nonce' ) ) {
        die ( 'Insufficient Access! Whoa');
      }

      /*
      * Only allow this if current user has enough access to modify plugins
      */
      if ( current_user_can( 'activate_plugins' ) )
      {
        $return_message = "";
        $path_start   = strrpos($data["options"]["wordpress_path"], "/");
        $path         = substr($data["options"]["wordpress_path"], $path_start);
        $url          = $data["options"]["wordpress_url"];

        switch($data['endpoint']) {
          case "disable_guest_signup": {
            Storage::disable_guest_signup();
            break;
          }
          case "check_path_availability": {
            $wishpond_id  = preg_replace('/[^0-9]+/i', "", $data["options"]["social_campaign_id"]);
            $existing_post = Form::get_by_wishpond_id($wishpond_id);

            // hosting as home page ?
            if($path == "") {
              $return_message = json_message('error', 'The path/slug was empty.');
            }
            else if(filter_var($url, FILTER_VALIDATE_URL) === false) {
              $return_message = json_message('error', 'URL Invalid; please ensure no invalid characters or spaces were used in the URL. Also make sure http:// or https:// are included in the URL.');
            }
            else if(!Storage::permalink_structure_valid()) {
              $return_message = json_message('error',
                'Invalid permalink structure. If you want to automatically publish to wordpress, please go in "Settings"->"Permalinks" and make sure your permalinks use the "postname"<br/> Otherwise, you have to manually create a wordpress page at this path and paste the wishpond "Embed Code"');
            }
            else {
              if( Form::page_slug_used($path, $existing_post->wordpress_post_id) ) {
                $return_message = json_message('error', 'Oops! The specified path \''.$path.'\' appears to be in use');
              }
              else
              {
                $return_message = json_message('updated', 'The specified path seems to be available!');
              }
            }
            break;
          }

          case "publish_campaign": {
            $wishpond_marketing_id = preg_replace('/[^0-9]+/i', "", $data["options"]["marketing_campaign_id"]);
            $wishpond_id      = preg_replace('/[^0-9]+/i', "", $data["options"]["social_campaign_id"]);

            $page_title       = html_entity_decode($data["options"]["social_campaign_title"], ENT_QUOTES);
            $page_title       = preg_replace('/[^a-zA-Z\-\_0-9\s\(\)\[\]\{\}\"\'\"]+/i', "", $page_title);

            $page_description = $data["options"]["social_campaign_description"];
            $page_description = preg_replace('/[^a-zA-Z\-\_0-9\s\(\)\[\]\{\}\"\'\"]+/i', "", $page_description);
            $page_image_url   = $data["options"]["social_campaign_image_url"];
            $facebook_app_id  = $data["options"]["facebook_app_id"];
            $landing_page_type = $data["options"]["landing_page_type"];

            $existing_post = Form::get_by_wishpond_id($wishpond_id);

            if(strlen($path) == 0)
            {
              $return_message = json_message('error', 'The path/slug was empty. Please use a url like "http://domain.com/path" to host your page. To set a form as your homepage, just go into "Forms" and click on "Make Homepage"');
            }
            else if( Form::page_slug_used($path, $existing_post->wordpress_post_id) ) {
              $return_message = json_message('error', 'Duplicate path/slug \'' . $path . '\'. Please try a different path ?');
            }
            else if(!Storage::permalink_structure_valid()) {
              $return_message = json_message('error',
                'Invalid permalink structure. To Automatically publish to wordpress, you need to go in "Settings"->"Permalinks" and make sure your permalinks use the "postname"<br/> Otherwise, just use the wishpond Embed code found under "Add to Website"');
            }
            else
            {
              if($existing_post)
              {
                $existing_post->update_values(array(
                  "path"      => $path,
                  "title"     => $page_title,
                  "image_url" => $page_image_url,
                  "wishpond_marketing_id" => $wishpond_marketing_id,
                  "description"           => $page_description,
                  "landing_page_type"     => $landing_page_type,
                  "facebook_app_id"       => $facebook_app_id
                ));

                $existing_post->save();
                $return_message = json_message('updated',
                  'Form Successfully updated! &nbsp;&nbsp;&nbsp; <a class="btn" target="_blank" href="'.$existing_post->url().'">View Page</a>'); 
              }
              else
              {
                $new_website_form = new Form(array(
                  "path"                  => $path,
                  "wishpond_marketing_id" => $wishpond_marketing_id,
                  "wishpond_id"           => $wishpond_id,
                  "title"                 => $page_title,
                  "description"           => $page_description,
                  "landing_page_type"     => $landing_page_type,
                  "image_url"             => $page_image_url,
                  "facebook_app_id"       => $facebook_app_id
                ));

                $new_website_form->save();

                if($new_website_form->wordpress_post_id >= 0)
                {
                  $return_message = json_message('updated', 'Form Successfully published! &nbsp;&nbsp;&nbsp; <a class="btn" target="_blank" href="'.$new_website_form->url().'">View Page</a>'); 
                }
                else if (get_page_by_title($page_title) != NULL) {
                  $return_message = json_message('error','Duplicate title! Wordpress needs page titles to be unique, so please change the Form title, and publish the page again');
                }
                else
                {
                  $return_message = json_message('error','Unknown error occurred! Your Form could not be created. Maybe try a different slug ? Contact us at 1-800-921-0167 if the problem persists');
                }
              }
            }
            break;
          }
          case "delete_campaign": {
            $wishpond_id  = preg_replace('/[^0-9]+/i', "", $data["options"]["social_campaign_id"]);
            wp_delete_post(Form::get_by_wishpond_id($wishpond_id)->wordpress_post_id);
            $return_message = json_message('updated', 'Form deleted successfully.'); 
            break;
          }
        }
        echo $return_message;
      }
    }
    exit();
  }

  public function add_cors_headers()
  {
    header("Access-Control-Allow-Origin: ".wishpond_constant("site_url").", ".wishpond_constant("secure_site_url"));
    header("Access-Control-Allow-Headers: "."origin, x-requested-with, content-type");
    header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
    header("Origin: ".wishpond_constant("site_url").", ".wishpond_constant("secure_site_url"));
  }

  public function enqueue_scripts()
  {
    wp_register_style(
      "website_forms_main_css",
      plugins_url("wishpond/assets/css/iframe.css", __FILE__)
    );

    wp_enqueue_style( "website_forms_main_css" );
    wp_enqueue_script( 'json2' );

    $plugin_scripts = array();

    $plugin_scripts["website_forms_cross_domain_js"] = array(
      "url"           => plugins_url("wishpond/assets/javascripts/xd.js", __FILE__),
      "dependencies"  => array( 'jquery' ),
      "in_footer"     => true
    );

    $plugin_scripts["website_forms_wishpond_api_script_js"] = array(
      "url"           => plugins_url("wishpond/assets/javascripts/wishpond-api.js", __FILE__),
      "dependencies"  => array( 'jquery' ),
      "in_footer"     => true,
      "localize"      => true,
      "localize_variable" => "JS",
      "localize_options"  => 
        array(
          // use wp-admin/admin-ajax.php to process the request
          'ajaxurl'      => admin_url( 'admin-ajax.php' ),
          'global_nonce' => wp_create_nonce( 'wishpond-api-nonce' ),
          'plugin_slug'  => plugin_constant("slug"),
          'WISHPOND_SITE_URL' => WISHPOND_SITE_URL,
          'WISHPOND_SECURE_SITE_URL' => WISHPOND_SECURE_SITE_URL,
          'is_guest_signup_enabled' => Storage::is_guest_signup_enabled()
        )
    );

    foreach( $plugin_scripts as $name => $options)
    {
      wp_register_script(
        $name,
        $options["url"],
        $options["dependencies"],
        $options["in_footer"]
      );
      wp_enqueue_script( $name );
      if( $options["localize"] )
      {
        wp_localize_script(
          $name,
          $options["localize_variable"],
          $options["localize_options"]
        );
      }
    }
  }
  //------------------------------------------------
}

$menu_builder = new MenuBuilder();
?>
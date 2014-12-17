<?php
  namespace WebsiteForms;
  if( ! current_user_can( 'activate_plugins' ) )
  {
    wp_die( __( 'Not enough permissions' ) );
  }
  defined('ABSPATH') or die("Invalid access!");
?>
  <div class="wrap">
  <h2>Forms
  <?php
    echo '<a href="' . admin_url("admin.php")."?page=" . plugin_constant("SLUG") . "-website-forms-create" . 
          '" class="add-new-h2">Add New</a>';
  ?>
  </h2>
  <br/>
  </div>
<?php
if(isset($notice) && $notice != "")
{
  echo "<div class='updated'><p>" . $notice . "</p></div>";
}

$forms = Form::get_all_posts();

if(count($forms) == 0)
{
  echo "<p>You haven't yet published any forms on this wordpress site.</p>";
}
else
{
  ?>
  <table class="lp-wishpond-list">
    <thead>
      <tr>
        <th><!-- image --></th>
        <th>Title</th>
        <th>Author</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>

      <?php
      $alternate = false;

      foreach($forms as $post):
        $form = Form::load($post);
      ?>
        <tr class="<?php if($alternate) echo 'alternate';?>">
          <td class="image">
            <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">
              <img src="<?php echo get_post_meta($post->ID, Form::POST_META_WISHPOND_IMAGE_KEY, true); ?>"/>
            </a>
          </td>
          <td>
            <a href="<?php echo get_permalink($post->ID); ?>" title="View Form" target="_blank">
              <b>
                <?php
                  $title = $post->post_title;
                  if($title == "") {
                    $title = "(no title)";
                  }
                  echo $title;
                ?>
              </b>
            </a><br/>
            <div class="row-actions">
              <span class="edit">
                <a href=<?php
                    echo "'".admin_url("admin.php")."?page=".
                        plugin_constant("SLUG").
                        "-website-forms-dashboard&wishpond-action=edit".
                        "&wishpond-id=".$form->wishpond_id.
                        "'"
                  ?> title="Edit this item">
                  Edit
                </a> | 
              </span>
              <span class="edit">
                <a href="<?php echo get_permalink($post->ID); ?>" title="View Form" target="_blank">
                  View
                </a> | 
              </span>
              <span class="edit">
                <a href=<?php
                    echo "'".
                      admin_url("admin.php").
                        "?page=".
                        plugin_constant("SLUG").
                        "-website-forms-dashboard&wishpond-action=manage&wishpond-marketing-id=".$form->wishpond_marketing_id.
                        "&wishpond-id=".$form->wishpond_id.
                        "'"
                  ?> title="Manage Form">
                  Manage
                </a> | 
              </span>
              <span class="edit">
                <a href="<?php echo get_edit_post_link($post->ID); ?>" title="View the Wordpress Page for this item" target="_blank">
                  Wordpress Page
                </a> | 
              </span>
              <span>
                 <a href=<?php
                    echo "'".
                      admin_url("admin.php").
                        "?page=".
                        plugin_constant("SLUG").
                        "-website-forms-dashboard&wishpond-action=report&wishpond-id=".$form->wishpond_id.
                        "'"
                  ?> title="View Report">
                  View Report
                </a> |
              </span>
              <?php if(get_option('page_on_front') != $post->ID): ?>
                <span>
                   <a href=<?php
                      echo "'".
                        admin_url("admin.php").
                          "?page=".
                          plugin_constant("SLUG").
                          "-website-forms&wishpond-action=make-homepage&wishpond-id=".$form->wishpond_id.
                          "'"
                    ?> title="View Report">
                    Make Homepage
                  </a>
                </span>
              <?php else: ?>
                <span>
                   <a href=<?php
                      echo "'".
                        admin_url("admin.php").
                          "?page=".
                          plugin_constant("SLUG").
                          "-website-forms&wishpond-action=reset-homepage'"
                    ?> title="View Report">
                    Reset Homepage
                  </a>
                </span>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <!-- Author -->
            <?php echo the_author_meta( 'user_nicename' , $post->post_author ); ?>
          </td>
          <td>
            <!-- Date -->
            <?php
              echo mysql2date( 'Y/m/d', $post->post_date );
              echo "<br/>";
              if( 'publish' == $post->post_status )
              {
                echo "Published";
              }
              else
              {
                echo "Last Modified"; 
              }
            ?>
          </td>
        </tr>
      <?php $alternate = !$alternate; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php
}

?>

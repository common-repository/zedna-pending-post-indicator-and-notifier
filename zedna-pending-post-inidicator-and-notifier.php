<?php
/*
 * Plugin Name: Zedna pending post indicator and notifier
 * Plugin URI: https://profiles.wordpress.org/zedna#content-plugins
 * Text Domain: zedna-ppian
 * Domain Path: /languages
 * Description: Show the number of pending posts waiting for approval in the admin menu, if any. Also automatically supports custom post types. Sends email notification of posts pending review.
 * Version: 1.0
 * Author: Radek Mezulanik
 * Author URI: http://mezulanik.cz
 * License: GPL3
*/

// Direct access to this file is not allowed
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Show the number of pending posts waiting for approval in the admin menu, if any
 * @param array $menu 
 * @return array
 */
function zedna_pending_posts_indicator( $menu ) {

	$post_types = get_post_types();
	if ( empty( $post_types ) ) {
		return;
	}
	
	foreach ( $post_types as $type ) {

		$status        = 'pending';
	    $num_posts     = wp_count_posts( $type, 'readable' );
	    $pending_count = 0;

	    if ( ! empty( $num_posts->$status ) ) {
			$pending_count = $num_posts->$status;
		}

	    // Build string to match in $menu array
		if ( $type == 'post' ) {
			$menu_str = 'edit.php';
	    } else {
			$menu_str = 'edit.php?post_type='.$type;
		}

	    // Loop through $menu items, find match, add indicator
	    foreach ( $menu as $menu_key => $menu_data ) {
			if ( $menu_str != $menu_data[ 2 ] ) {
				continue;
			} else {
				// NOTE: Using the same CSS classes as the plugin updates count, it will match your admin color theme just fine.
				$menu[ $menu_key ][0] .= " <span class='update-plugins count-$pending_count'><span class='plugin-count'>" . number_format_i18n( $pending_count ) . '</span></span>';
			}
		}
	}
	return $menu;
}
add_filter( 'add_menu_classes', 'zedna_pending_posts_indicator' );


// set default configuration
register_activation_hook(__FILE__,'zedna_status_notifier_defaults');

function zedna_status_notifier_defaults() {
    add_option('notificationemails',get_option('admin_email'));
    add_option('approvednotification','yes');
    add_option('declinednotification','yes');
}


// add to admin menu
add_action('admin_menu', 'zedna_statusnotify_add_option_page');

function zedna_statusnotify_add_option_page() {
    // Add a new submenu in options:
    add_options_page(__('Pending post indicator and notifier','zedna-ppian'), __('Pending post indicator and notifier','zedna-ppian'), 'edit_themes', 'status_notifier', 'zedna_statusnotify_options_page');
}

function zedna_statusnotify_options_page() {
	if(isset($_POST['save']) && check_admin_referer( 'zednappian-settings-update', 'zednappian-nonce' ) && wp_verify_nonce( $_REQUEST['zednappian-nonce'], 'zednappian-settings-update' )) {
      update_option('notificationemails',sanitize_text_field($_POST['notificationemails']));
      update_option('approvednotification',sanitize_text_field($_POST['approvednotification']));
      update_option('declinednotification',sanitize_text_field($_POST['declinednotification']));
	  	echo "<div id='message' class='updated fade'><p>".__('Notification settings saved.','zedna-ppian')."</p></div>";
    }
    ?>
<div class="wrap">
  <h2><?php echo __('Post Status Notifications','zedna-ppian');?></h2>
  <form name="site" action="" method="post" id="notifier">

    <div id="review">
      <fieldset id="pendingdiv">
        <legend><b><?php echo __('Pending Review Notifications','zedna-ppian'); ?></b></legend>
        <div><input type="text" size="50" name="notificationemails" tabindex="1" id="notificationemails"
            value="<?php echo esc_attr(get_option('notificationemails')); ?>"><br />
          <?php echo __('Enter email addresses which should be notified of posts pending review (comma separated).','zedna-ppian');?>
        </div>
      </fieldset>
      <br />

      <fieldset id="reviewdiv">
        <legend><b><?php echo __('Post Review Notifications','zedna-ppian'); ?></b></legend>
        <div>
          <label for="approvednotification" class="selectit"><input type="checkbox" tabindex="2"
              id="approvednotification" name="approvednotification" value="yes"
              <?php if(get_option('approvednotification')=='yes') echo 'checked="checked"'; ?> />
            <?php echo __('Notify contributor when their post is approved','zedna-ppian'); ?></label><br />
          <label for="declinednotification" class="selectit"><input type="checkbox" tabindex="3"
              id="declinednotification" name="declinednotification" value="yes"
              <?php if(get_option('declinednotification')=='yes') echo 'checked="checked"'; ?> />
            <?php echo __('Notify contributor when their post is declined (sent back to drafts)','zedna-ppian'); ?>
          </label>
        </div>
      </fieldset>
      <br />
      <p class="submit">
        <input name="save" type="submit" id="savenotifier" tabindex="6" style="font-weight: bold;"
          value="<?php echo __('Save Settings','zedna-ppian'); ?>" />
      </p>
    </div>
    <?php wp_nonce_field( 'zednappian-settings-update', 'zednappian-nonce' );?>
  </form>
  <p><?php echo __('If you like this plugin, please donate us for faster upgrade','zedna-ppian'); ?></p>
  <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
    <input type="hidden" name="cmd" value="_s-xclick">
    <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit"
      alt="PayPal - The safer, easier way to pay online!">
    <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
  </form>
</div>
<?php
}

// Hook for post status changes
add_filter('transition_post_status', 'zedna_notify_status',10,3);
function zedna_notify_status($new_status, $old_status, $post) {
    global $current_user;
	$contributor = get_userdata($post->post_author);
    if ($old_status != 'pending' && $new_status == 'pending') {
      $emails=get_option('notificationemails');
      if(strlen($emails)) {
        $subject='['.get_option('blogname').'] "'.$post->post_title.'" '.__('is waiting for approval','zedna-ppian');
        $message=__('New post from','zedna-ppian')." {$contributor->display_name} ".__('is waiting for approval','zedna-ppian').".\n\n";
        $message.=__('Author','zedna-ppian').": {$contributor->user_login} <{$contributor->user_email}> (IP: {$_SERVER['REMOTE_ADDR']})\n";
        $message.=__('Title','zedna-ppian').": {$post->post_title}\n";
		$category = get_the_category($post->ID);
		if(isset($category[0])) 
			$message.=__('Category','zedna-ppian').": {$category[0]->name}\n";;
				$message.=__('Check the post on','zedna-ppian').": ".get_option('siteurl')."/wp-admin/post.php?action=edit&post={$post->ID}\n\n";
				$message.="To approve, just PUBLISH the post. To reject, change posts status to DRAFT.\n\n\n";
        $message.=get_bloginfo()."\n".get_option('siteurl');
        wp_mail( $emails, $subject, $message);
      }
	} elseif ($old_status == 'pending' && $new_status == 'publish' && $current_user->ID!=$contributor->ID) {
      if(get_option('approvednotification')=='yes') {
        $subject='['.get_option('blogname').'] "'.$post->post_title.'" '.__('approved','zedna-ppian');
        $message="{$contributor->display_name},\n\n".__('Your post has been published on','zedna-ppian')." ".get_permalink($post->ID)." .\n\n";
        $message.=__('By editor','zedna-ppian')." {$current_user->display_name} <{$current_user->user_email}>\n\n\n";
        $message.=get_bloginfo()."\n".get_option('siteurl');
        wp_mail( $contributor->user_email, $subject, $message);
      }
	} elseif ($old_status == 'pending' && $new_status == 'draft' && $current_user->ID!=$contributor->ID) {
      if(get_option('declinednotification')=='yes') {
        $subject='['.get_option('blogname').'] "'.$post->post_title.'" '.__('rejected','zedna-ppian');
        $message="{$contributor->display_name},\n\n".__('Your post has been rejected. You can fix it on','zedna-ppian')." ".get_option('siteurl')."/wp-admin/post.php?action=edit&post={$post->ID} .\n\n";
        $message.=__('By editor','zedna-ppian')." {$current_user->display_name} <{$current_user->user_email}>\n\n\n";
        $message.=get_bloginfo()."\n".get_option('siteurl');
        wp_mail( $contributor->user_email, $subject, $message);
      }
	}
}
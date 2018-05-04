<?php
/*
Plugin Name: BSB Woocommerce Contact Preferences
Plugin URI: http://www.shaungill.co.uk/wordpress
Description: Enables users to specifically opt-in or out of contact preferences
Version: 0.1.0
Author: Shaun Gill
Author URI: http://www.shaungill.co.uk/
License: GPLv2 or later
Text Domain: bsb
*/

/**
 * Get additional account fields
 * @return array
 */
function bsb_get_account_fields() {
  return apply_filters( 'bsb_account_fields', array(
    'user_url' => array(
      'type'        => 'text',
      'label'       => __( 'Website', 'bsb' ),
      'placeholder' => __( 'E.g. http://www.shaungill.co.uk', 'bsb' ),
      'required'    => true,
      'hide_in_admin'        => true,
    ),
  ) );
}

/**
 * Add fields to registration form and account area.
 */
function bsb_print_user_frontend_fields() {
  $fields = bsb_get_account_fields();
  $is_user_logged_in = is_user_logged_in();

  foreach ( $fields as $key => $field_args ) {
    if ( $is_user_logged_in && ! empty( $field_args['hide_in_account'] ) ) {
      continue;
    }

    if ( ! $is_user_logged_in && ! empty( $field_args['hide_in_registration'] ) ) {
      continue;
    }
    woocommerce_form_field( $key, $field_args );
  }
}

add_action( 'woocommerce_register_form', 'bsb_print_user_frontend_fields', 10 ); // register form
add_action( 'woocommerce_edit_account_form', 'bsb_print_user_frontend_fields', 10 ); // my account

/**
 * Show fields at checkout.
 */
function bsb_checkout_fields( $checkout_fields ) {
  $fields = bsb_get_account_fields();

  foreach ( $fields as $key => $field_args ) {
    if ( ! empty( $field_args['hide_in_checkout'] ) ) {
      continue;
    }
    $checkout_fields['account'][ $key ] = $field_args;
  }

  return $checkout_fields;
}

add_filter( 'woocommerce_checkout_fields', 'bsb_checkout_fields', 10, 1 );

/**
 * Add fields to admin area.
*/
function bsb_print_user_admin_fields() {
  $fields = bsb_get_account_fields();
  ?>
  <h2><?php _e( 'Additional Information', 'bsb' ); ?></h2>
  <table class="form-table" id="bsb-additional-information">
    <tbody>
    <?php foreach ( $fields as $key => $field_args ) { ?>
      <?php
        if ( ! empty( $field_args['hide_in_admin'] ) ) {
          continue;
        }
      ?>
      <tr>
        <th>
          <label for="<?php echo $key; ?>"><?php echo $field_args['label']; ?></label>
        </th>
        <td>
          <?php $field_args['label'] = false; ?>
          <?php woocommerce_form_field( $key, $field_args ); ?>
        </td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
  <?php
}

add_action( 'show_user_profile', 'bsb_print_user_admin_fields', 30 ); // admin: edit profile
add_action( 'edit_user_profile', 'bsb_print_user_admin_fields', 30 ); // admin: edit other users

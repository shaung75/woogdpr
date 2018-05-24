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
    'bsb-contact-preferences'      => array(
    	'type'    => 'radio',
        'label'   => __( 'Contact preferences', 'bsb' ),
        'gdpr-cp' => true,
        'options' => array(
        	1 => __( 'Yes please', 'bsb' ),
        	2 => __( 'No thanks', 'bsb' ),
    	),
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
  	$value = null;

    if ( $is_user_logged_in && ! empty( $field_args['hide_in_account'] ) ) {
      continue;
    }

    if ( ! $is_user_logged_in && ! empty( $field_args['hide_in_registration'] ) ) {
      continue;
    }

    if ( $is_user_logged_in ) {
	    $user_id = bsb_get_edit_user_id();
	    $value   = bsb_get_userdata( $user_id, $key );
    }

    $value = isset( $field_args['value'] ) ? $field_args['value'] : $value;

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

        $user_id = bsb_get_edit_user_id();
        $value   = bsb_get_userdata( $user_id, $key );
      ?>
      <tr>
        <th>
          <label for="<?php echo $key; ?>"><?php echo $field_args['label']; ?></label>
        </th>
        <td>
          <?php $field_args['label'] = false; ?>
          <?php woocommerce_form_field( $key, $field_args, $value ); ?>
        </td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
  <?php
}

add_action( 'show_user_profile', 'bsb_print_user_admin_fields', 30 ); // admin: edit profile
add_action( 'edit_user_profile', 'bsb_print_user_admin_fields', 30 ); // admin: edit other users


/**
 * Modify checkboxes/radio fields.
 *
 * @param string $field
 * @param string $key
 * @param array  $args
 * @param string $value
 *
 * @return string
 */
function bsb_form_field_modify( $field, $key, $args, $value ) {
    ob_start();
    bsb_print_list_field( $key, $args, $value );
    $field = ob_get_clean();
 
    if ( $args['return'] ) {
        return $field;
    } else {
        echo $field;
    }
}
 
add_filter( 'woocommerce_form_field_checkboxes', 'bsb_form_field_modify', 10, 4 );
add_filter( 'woocommerce_form_field_radio', 'bsb_form_field_modify', 10, 4 );

/**
 * Print a list field (checkboxes|radio).
 *
 * @param string $key
 * @param array  $field_args
 * @param mixed  $value
 *
 */
function bsb_print_list_field( $key, $field_args, $value = null ) {
	$value = empty( $value ) && $field_args['type'] === 'checkboxes' ? array() : $value;
	?>
	<div class="form-row">
		<?php if ( $field_args['gdpr-cp'] ) { ?>
			<div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 20px;">
		<?php } ?>
		<?php if ( ! empty( $field_args['label'] ) && ! $field_args['gdpr-cp'] ) { ?>
			<label>
				<?php echo $field_args['label']; ?>
				<?php if ( ! empty( $field_args['required'] ) ) { ?>
					<abbr class="required" title="<?php echo esc_attr__( 'required', 'woocommerce' ); ?>">*</abbr>
				<?php } ?>
			</label>
		<?php } ?>

		<?php if ( $field_args['gdpr-cp'] ) { ?>
			<?php
				/* TO DO */
				/* Make customisable from backend */
			?>
			<h3>Contact preferences</h3>
			<p>We'd love to send you money-off coupons, offers and the latest info from <?php bloginfo('name');?> by email, post, SMS, phone and other electronic means. We'll always treat your personal details with the utmost care and will never sell them to other businesses for marketing purposes.</p>

			<p style="color: #a00;">Please let us know if you would like us to contact you or not by selecting one of the options below</p>
		<?php } ?>
		<ul style="list-style-type: none; margin-left: 20px;">
			<?php foreach ( $field_args['options'] as $option_value => $option_label ) {
				$id         = sprintf( '%s_%s', $key, sanitize_title_with_dashes( $option_label ) );
				$option_key = $field_args['type'] === 'checkboxes' ? sprintf( '%s[%s]', $key, $option_value ) : $key;
				$type       = $field_args['type'] === 'checkboxes' ? 'checkbox' : $field_args['type'];
				$checked    = $field_args['type'] === 'checkboxes' ? in_array( $option_value, $value ) : $option_value == $value;
				?>
				<li>
					<label for="<?php echo esc_attr( $id ); ?>">
						<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $option_key ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( $checked ); ?>>
						<?php echo $option_label; ?>
					</label>
				</li>
			<?php } ?>
		</ul>
		<?php if ( $field_args['gdpr-cp'] ) { ?>
			</div>
		<?php } ?>
	</div>
	<?php
}


/**
 * Save registration fields.
 *
 * @param int $customer_id
 */
function bsb_save_account_fields( $customer_id ) {
	$fields = bsb_get_account_fields();
	$sanitized_data = array();

	foreach ( $fields as $key => $field_args ) {
		if ( ! bsb_is_field_visible( $field_args ) ) {
			continue;
		}

		$sanitize = isset( $field_args['sanitize'] ) ? $field_args['sanitize'] : 'wc_clean';
		$value    = isset( $_POST[ $key ] ) ? call_user_func( $sanitize, $_POST[ $key ] ) : '';

		if ( bsb_is_userdata( $key ) ) {
			$sanitized_data[ $key ] = $value;
			continue;
		}		

		update_user_meta( $customer_id, $key, $value );
	}

	if ( ! empty( $sanitized_data ) ) {
		$sanitized_data['ID'] = $customer_id;
		wp_update_user( $sanitized_data );
	}
}

add_action( 'woocommerce_created_customer', 'bsb_save_account_fields' ); // register/checkout
add_action( 'personal_options_update', 'bsb_save_account_fields' ); // edit own account admin
add_action( 'edit_user_profile_update', 'bsb_save_account_fields' ); // edit other account admin
add_action( 'woocommerce_save_account_details', 'bsb_save_account_fields' ); // edit WC account


/**
 * Is field visible.
 *
 * @param $field_args
 *
 * @return bool
 */
function bsb_is_field_visible( $field_args ) {
	$visible = true;
	$action = filter_input( INPUT_POST, 'action' );

	if ( is_admin() && ! empty( $field_args['hide_in_admin'] ) ) {
		$visible = false;
	} elseif ( ( is_account_page() || $action === 'save_account_details' ) && is_user_logged_in() && ! empty( $field_args['hide_in_account'] ) ) {
		$visible = false;
	} elseif ( ( is_account_page() || $action === 'save_account_details' ) && ! is_user_logged_in() && ! empty( $field_args['hide_in_registration'] ) ) {
		$visible = false;
	} elseif ( is_checkout() && ! empty( $field_args['hide_in_checkout'] ) ) {
		$visible = false;
	}

	return $visible;
}

/**
 * Is this field core user data.
 *
 * @param $key
 *
 * @return bool
 */
function bsb_is_userdata( $key ) {
	$userdata = array(
		'user_pass',
		'user_login',
		'user_nicename',
		'user_url',
		'user_email',
		'display_name',
		'nickname',
		'first_name',
		'last_name',
		'description',
		'rich_editing',
		'user_registered',
		'role',
		'jabber',
		'aim',
		'yim',
		'show_admin_bar_front',
	);

	return in_array( $key, $userdata );
}

/**
 * Validate fields on frontend.
 *
 * @param WP_Error $errors
 *
 * @return WP_Error
 *
 */
function bsb_validate_user_frontend_fields( $errors ) {
	$fields = bsb_get_account_fields();

	foreach ( $fields as $key => $field_args ) {
		if ( empty( $field_args['required'] ) ) {
			continue;
		}

		if ( ! isset( $_POST['register'] ) && ! empty( $field_args['hide_in_account'] ) ) {
			continue;
		}

		if ( isset( $_POST['register'] ) && ! empty( $field_args['hide_in_registration'] ) ) {
			continue;
		}

		if ( empty( $_POST[ $key ] ) ) {
			$message = sprintf( __( '%s is a required field.', 'bsb' ), '<strong>' . $field_args['label'] . '</strong>' );
			$errors->add( $key, $message );
		}
	}

	return $errors;
}

add_filter( 'woocommerce_registration_errors', 'bsb_validate_user_frontend_fields', 10 );
add_filter( 'woocommerce_save_account_details_errors', 'bsb_validate_user_frontend_fields', 10 );

/**
 * Get currently editing user ID (frontend account/edit profile/edit other user).
 *
 * @return int
 */
function bsb_get_edit_user_id() {
    return isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : get_current_user_id();
}

/**
 * Add post values to account fields if set.
 * 
 * @param array $fields
 *
 * @return array
 */
function bsb_add_post_data_to_account_fields( $fields ) {
	if ( empty( $_POST ) ) {
		return $fields;
	}

	foreach ( $fields as $key => $field_args ) {
		if ( empty( $_POST[ $key ] ) ) {
			$fields[ $key ]['value'] = '';
			continue;
		}

		$fields[ $key ]['value'] = $_POST[ $key ];
	}

	return $fields;
}

add_filter( 'bsb_account_fields', 'bsb_add_post_data_to_account_fields', 10, 1 );


/**
 * Get user data.
 *
 * @param $user_id
 * @param $key
 *
 * @return mixed|string
 */
function bsb_get_userdata( $user_id, $key ) {
	if ( ! bsb_is_userdata( $key ) ) {
		return get_user_meta( $user_id, $key );
	}

	$userdata = get_userdata( $user_id );

	if ( ! $userdata || ! isset( $userdata->{$key} ) ) {
		return '';
	}

	return $userdata->{$key};
}
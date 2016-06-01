<?php

function wppb_signup_password_random_password_filter( $password ) {
	global $wpdb;

	$key = ( !empty( $_GET['key'] ) ? $_GET['key'] : null );
	$key = ( !empty( $_POST['key'] ) ? $_POST['key'] : $key );

	if ( !empty( $_POST['user_pass'] ) )
		$password = $_POST['user_pass'];
		
	elseif ( !is_null( $key ) ) {
		$signup = ( is_multisite() ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->signups . " WHERE activation_key = %s", $key ) ) : $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "signups WHERE activation_key = %s", $key ) ) );
		
		if ( empty( $signup ) || $signup->active ) {
			//bad key or already active
		} else {
			//check for password in signup meta
			$meta = unserialize( $signup->meta );
			
			if ( !empty($meta['user_pass']) )
                $password = $meta['user_pass'];
		}
	}
	
	return apply_filters( 'wppb_generated_random_password', $password, $key );
}
add_filter( 'random_password', 'wppb_signup_password_random_password_filter' );


function wppb_activate_signup( $key ) {
	global $wpdb;
	$bloginfo = get_bloginfo( 'name' );
	$wppb_general_settings = get_option( 'wppb_general_settings' );

	$signup = ( is_multisite() ? $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->signups WHERE activation_key = %s", $key) ) : $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->base_prefix."signups WHERE activation_key = %s", $key ) ) );
	
	if ( empty( $signup ) )
		return apply_filters( 'wppb_register_activate_user_error_message1', '<p class="error">'.__( 'Nederīga aktivizēšanas atslēgu!', 'profile-builder' ).'</p>');

	if ( $signup->active )
		if ( empty( $signup->domain ) )
			return apply_filters( 'wppb_register_activate_user_error_message2', '<p class="error">'.__( 'Šis lietotājvārds tagad ir aktīvs!', 'profile-builder' ).'</p>' );

	$meta = unserialize( $signup->meta );
	
	$user_login = ( ( isset( $wppb_general_settings['loginWith'] ) && ( $wppb_general_settings['loginWith'] == 'email' ) ) ? trim( $signup->user_email ) : trim( $signup->user_login ) );
		
	$user_email = esc_sql( $signup->user_email );
    
	$password = NULL;

	$user_id = username_exists( $user_login );

	if ( !$user_id )
		$user_id = wppb_create_user( $user_login, $password, $user_email );
	else
		$user_already_exists = true;

	if ( ! $user_id )
		return apply_filters( 'wppb_register_activate_user_error_message4', '<p class="error">'.__('Nevar izveidot lietotāju!', 'profile-builder').'</p>' );
		
	elseif ( isset( $user_already_exists ) && ( $user_already_exists == true ) )
		return apply_filters( 'wppb_register_activate_user_error_message5', '<p class="error">'.__( 'Šis lietotājvārds jau ir aktivizēts!', 'profile-builder' ).'</p>' );
	
	else{
		$inserted_user = ( is_multisite() ? $wpdb->update( $wpdb->signups, array( 'active' => 1, 'activated' => current_time( 'mysql', true ) ), array( 'activation_key' => $key ) ) : $wpdb->update( $wpdb->base_prefix.'signups', array( 'active' => 1, 'activated' => current_time( 'mysql', true ) ), array( 'activation_key' => $key ) ) );

		wppb_add_meta_to_user_on_activation( $user_id, '', $meta );
		
		// if admin approval is activated, then block the user untill he gets approved
		$wppb_generalSettings = get_option('wppb_general_settings');
		if( isset( $wppb_generalSettings['adminApproval'] ) && ( $wppb_generalSettings['adminApproval'] == 'yes' ) ){
			$user_data = get_userdata( $user_id );

			if( $wppb_generalSettings != 'not_found' && ! empty( $wppb_generalSettings['adminApprovalOnUserRole'] ) ) {
				foreach( $user_data->roles as $role ) {
					if( in_array( $role, $wppb_generalSettings['adminApprovalOnUserRole'] ) ) {
						wp_set_object_terms( $user_id, array( 'unapproved' ), 'user_status', false);
						clean_object_term_cache( $user_id, 'user_status' );
					} else {
						add_filter( 'wppb_register_success_message', 'wppb_noAdminApproval_successMessage' );
					}
				}
			} else {
				wp_set_object_terms( $user_id, array( 'unapproved' ), 'user_status', false);
				clean_object_term_cache( $user_id, 'user_status' );
			}
		}

        if ( !isset( $wppb_generalSettings['adminApproval'] ) )
            $wppb_generalSettings['adminApproval'] = 'no';

        /* copy the hashed password from signup meta to wp user table */
        if( !empty( $meta['user_pass'] ) ){
            /* we might still have the base64 encoded password in signups and not the hash */
            if( base64_encode(base64_decode($meta['user_pass'], true)) === $meta['user_pass'] )
                $meta['user_pass'] = wp_hash_password( $meta['user_pass'] );

            $wpdb->update( $wpdb->users, array('user_pass' => $meta['user_pass'] ), array('ID' => $user_id) );
        }

		wppb_notify_user_registration_email($bloginfo, $user_login, $user_email, 'sending', $password, $wppb_generalSettings['adminApproval']);

		do_action( 'wppb_activate_user', $user_id, $password, $meta );

		if ( $inserted_user ){
			$redirect_url = NULL;
			if( PROFILE_BUILDER == 'Profile Builder Pro' ) {
				$wppb_module_settings = get_option( 'wppb_module_settings' );

				if( isset( $wppb_module_settings['wppb_customRedirect'] ) && $wppb_module_settings['wppb_customRedirect'] == 'show' && function_exists( 'wppb_custom_redirect_url' ) ) {
					$redirect_url = wppb_custom_redirect_url( 'after_success_email_confirmation', '', $user_login );
				}
			}
			$redirect_url = apply_filters( 'wppb_success_email_confirmation_redirect_url', $redirect_url );
			$wppb_cr_delay = apply_filters( 'wppb_success_email_confirmation_redirect_delay', $wppb_cr_delay = 5 );

			$success_message = apply_filters( 'wppb_success_email_confirmation', '<p class="wppb-success">' . __( 'Your email was successfully confirmed.', 'profile-builder' ) . '</p><!-- .success -->' );
            $admin_approval_message = apply_filters( 'wppb_email_confirmation_with_admin_approval', '<p class="alert">' . __('Pirms jūs varat piekļūt savam kontam, administrators ir nepieciešams to apstiprināt. Jums tiks paziņots pa e-pastu.', 'profile-builder' ) . '</p>' );

			if( ! empty( $redirect_url ) ) {
				$wppb_cr_success_message = apply_filters( 'wppb_success_email_confirmation_redirect_message', '<p class="wppb-success">' . __( 'Jūs drīz tiks novirzīts automātiski.', 'profile-builder' ) . '</p>' ) . '<meta http-equiv="Refresh" content="'.$wppb_cr_delay.';url='.$redirect_url.'" />';
			}

            $wppb_general_settings = get_option( 'wppb_general_settings', 'false' );

            if ( !empty( $wppb_general_settings['adminApproval'] ) && $wppb_general_settings['adminApproval'] == 'yes' ){
				$user_data = get_userdata( $user_id );

				if( $wppb_general_settings != 'not_found' && ! empty( $wppb_general_settings['adminApprovalOnUserRole'] ) ) {
					foreach( $user_data->roles as $role ) {
						if( in_array( $role, $wppb_general_settings['adminApprovalOnUserRole'] ) ) {
							return $success_message . $admin_approval_message. ( ! empty ( $wppb_cr_success_message ) ? $wppb_cr_success_message : '' );
						} else {
							wp_set_object_terms( $user_id, NULL, 'user_status' );
							clean_object_term_cache( $user_id, 'user_status' );

							return $success_message. ( ! empty ( $wppb_cr_success_message ) ? $wppb_cr_success_message : '' );
						}
					}
				} else {
					return $success_message . $admin_approval_message. ( ! empty ( $wppb_cr_success_message ) ? $wppb_cr_success_message : '' );
				}
            } else {
				wp_set_object_terms( $user_id, NULL, 'user_status' );
				clean_object_term_cache( $user_id, 'user_status' );

                return $success_message. ( ! empty ( $wppb_cr_success_message ) ? $wppb_cr_success_message : '' );
            }
        } else {
			return apply_filters('wppb_register_failed_user_activation', '<p class="error">'. __('Radās kļūda, mēģinot aktivizēt lietotājam.', 'profile-builder') .'</p><!-- .error -->');
        }
	}		
}

//function to display the registration page
function wppb_front_end_register( $atts ){
	extract( shortcode_atts( array( 'role' => get_option( 'default_role' ), 'form_name' => 'unspecified', 'redirect_url' => '', 'redirect_priority' => 'normal' ), $atts, 'wppb-register' ) );
	
	global $$form_name;

    $$form_name = new Profile_Builder_Form_Creator( array( 'form_type' => 'register', 'form_name' => $form_name, 'role' => ( is_object( get_role( $role ) ) ? $role : get_option( 'default_role' ) ) , 'redirect_url' => $redirect_url, 'redirect_priority' => $redirect_priority ) );

    return $$form_name;
}

// function to choose whether to display the registration page or the validation message
function wppb_front_end_register_handler( $atts ){

	return ( isset( $_GET['activation_key'] ) ? wppb_activate_signup ( $_GET['activation_key'] ) : wppb_front_end_register( $atts ) );
}

add_action( 'user_register', 'wppbc_disable_admin_approval_for_user_role', 99, 1 );
function wppbc_disable_admin_approval_for_user_role( $user_id ) {
	if ( current_user_can( 'delete_users' ) ) {
		wp_set_object_terms( $user_id, NULL, 'user_status' );
		clean_object_term_cache( $user_id, 'user_status' );
	}
}

/* authors and contributors shouldn't be allowed to create pages with the register shortcode in them */
add_filter( 'the_content', 'wppb_maybe_remove_register_shortcode' );
function wppb_maybe_remove_register_shortcode( $content ){
    if ( has_shortcode( $content, 'wppb-register' ) ){
        $author_id = get_the_author_meta( 'ID' );
        if( !empty( $author_id ) ){
            if( !user_can( $author_id, 'edit_others_posts' ) ) {
                remove_shortcode('wppb-register');
            }
        }
    }

    return $content;
}
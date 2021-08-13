<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Echo conversation update logic -> DT to ECHO
 */

add_action( 'dt_post_updated', 'dt_echo_push_dt_updates', 10, 5 );
function dt_echo_push_dt_updates( $post_type, $post_id, $initial_fields, $existing_post, $post ) {

    // Ensure to execute outside of cron run window
    if ( ! wp_doing_cron() ) {
        // Ensure dt -> echo pushes are enabled
        if ( is_dt_echo_sync_enabled( 'dt_echo_push_dt_sync' ) ) {
            // Ensure contacts record has valid echo convo ids to process
            if ( $post_type === 'contacts' && isset( $post['dt_echo_convo_ids'] ) && is_array( $post['dt_echo_convo_ids'] ) && count( $post['dt_echo_convo_ids'] ) > 0 ) {
                // Ensure seeker path option has changed
                if ( isset( $existing_post['seeker_path'] ) && isset( $post['seeker_path'] ) && $existing_post['seeker_path']['key'] !== $post['seeker_path']['key'] ) {
                    dt_echo_logging_add( "Attempting to push recent contacts dt record [" . $post_id . "] seeker path updates..." );

                    // Determine if there is a mapping for recently selected seeker path option
                    $supported_seeker_path_option = isset( $post['seeker_path'] ) ? extract_supported_seeker_path_option_by_dt_id( fetch_supported_seeker_path_options(), $post['seeker_path']['key'] ) : null;
                    if ( ! empty( $supported_seeker_path_option ) ) {
                        dt_echo_logging_add( "DT seeker path option [" . $supported_seeker_path_option->dt_name . "] mapped to Echo outcome [" . $supported_seeker_path_option->echo_name . "]" );

                        // Iterate over echo convo id list and update respective outcome status within Echo!
                        foreach ( $post['dt_echo_convo_ids'] as $echo_convo_id ) {
                            try {
                                if ( Disciple_Tools_Echo_API::update_convo_outcome( $echo_convo_id, $supported_seeker_path_option->echo_id ) ) {
                                    update_convo_outcome_value( $supported_seeker_path_option->echo_name, 'dt_to_echo_last_sync_run', time() );
                                    update_convo_outcome_value( $supported_seeker_path_option->echo_name, 'log', '' );
                                    dt_echo_logging_add( 'Successfully updated echo conversation [' . $echo_convo_id . '] to new outcome [' . $supported_seeker_path_option->echo_name . ']' );

                                } else {
                                    dt_echo_logging_add( 'Unable to update echo conversation [' . $echo_convo_id . '] to new outcome [' . $supported_seeker_path_option->echo_name . ']' );

                                }
                            } catch ( Exception $exception ) {
                                dt_echo_logging_add( 'Exception: ' . $exception->getMessage() );
                            }
                        }
                    } else {
                        dt_echo_logging_add( "Unable to locate any supported seeker path options" );
                    }
                }
            }
            // Update global sync run timestamp
            update_global_dt_echo_last_run( 'dt_echo_sync_last_run_ts_dt_to_echo', time() );
        }
    }
}

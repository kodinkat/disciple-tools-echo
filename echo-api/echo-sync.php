<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Core synchronisation logic.
 */

add_action( Disciple_Tools_Echo_API::$schedule_cron_event_hook, 'dt_echo_sync_run' );
function dt_echo_sync_run() {

    // DT <- Echo Sync
    sync_echo_to_dt();

    // Age Stale Logs
    dt_echo_logging_aged();

}

function sync_echo_to_dt() {
    if ( is_dt_echo_sync_enabled( 'dt_echo_fetch_echo_sync' ) ) {

        // Load logs
        $logs   = dt_echo_logging_load();
        $logs[] = dt_echo_logging_create( '[STARTED] - ECHO -> DT' );

        // Determine search date range based on last run timestamp
        $date_range = fetch_date_range_by_global_dt_echo_last_run( 'dt_echo_sync_last_run_ts_echo_to_dt' );
        $logs[]     = dt_echo_logging_create( 'Echo conversation search date range start[' . dt_format_date( $date_range['start'], 'long' ) . '] - end[' . dt_format_date( $date_range['end'], 'long' ) . ']' );

        // Obtain latest Echo conversations for given date range
        $latest_echo_conversations = fetch_latest_echo_convos( $date_range['start'], $date_range['end'] );
        $logs[]                    = dt_echo_logging_create( 'Latest Echo conversations count: ' . count( $latest_echo_conversations ) );

        if ( ! empty( $latest_echo_conversations ) && count( $latest_echo_conversations ) > 0 ) {

            // Fetch supported echo conversation outcome options & referrers
            $supported_echo_convo_outcome_option_names = extract_supported_echo_convo_outcome_option_names( fetch_supported_echo_convo_outcome_options() );
            $supported_echo_convo_referrer_names       = extract_supported_echo_convo_referrer_names( fetch_supported_echo_convo_referrers() );

            if ( ! empty( $supported_echo_convo_outcome_option_names ) && ( count( $supported_echo_convo_outcome_option_names ) > 0 ) &&
                 ! empty( $supported_echo_convo_referrer_names ) && ( count( $supported_echo_convo_referrer_names ) > 0 ) ) {

                // Iterate through and process latest matching echo
                $logs[] = dt_echo_logging_create( 'Supported Echo conversation outcome options count [' . count( $supported_echo_convo_outcome_option_names ) . '] and referrers count [' . count( $supported_echo_convo_referrer_names ) . ']' );
                foreach ( $latest_echo_conversations as $echo_convo ) {

                    try {

                        // Identify conversations with supported outcomes & referrers
                        if ( ( isset( $echo_convo->outcome ) && ! empty( $echo_convo->outcome ) && in_array( strtolower( trim( $echo_convo->outcome ) ), $supported_echo_convo_outcome_option_names ) ) &&
                             ( isset( $echo_convo->referrer ) && ! empty( $echo_convo->referrer ) && in_array( strtolower( trim( $echo_convo->referrer ) ), $supported_echo_convo_referrer_names ) ) ) {

                            // Process incoming echo conversation
                            if ( process_echo_convo( $echo_convo, $logs ) ) {
                                update_convo_outcome_value( $echo_convo->outcome, 'echo_to_dt_last_sync_run', time() );
                                update_convo_outcome_value( $echo_convo->outcome, 'log', '' );
                                $logs[] = dt_echo_logging_create( 'Successfully processed echo conversation [' . $echo_convo->chat_id . ']' );

                            } else {
                                $logs[] = dt_echo_logging_create( 'Unable to fully process echo conversation [' . $echo_convo->chat_id . ']' );

                            }
                        }
                    } catch ( Exception $exception ) {
                        $logs[] = dt_echo_logging_create( 'Exception: ' . $exception->getMessage() );
                    }
                }
            } else {
                $logs[] = dt_echo_logging_create( 'No further ECHO -> DT processing, due to no supported conversation outcome options & referrers detected' );
            }
        } else {
            $logs[] = dt_echo_logging_create( 'No further ECHO -> DT processing, due to no Echo conversations detected' );
        }

        // Update global sync run timestamp and logs
        update_global_dt_echo_last_run( 'dt_echo_sync_last_run_ts_echo_to_dt', $date_range['end'] );
        $logs[] = dt_echo_logging_create( '[FINISHED] - ECHO -> DT' );
        dt_echo_logging_update( $logs );
    }
}

function dt_echo_logging_load(): array {
    return ! empty( get_option( 'dt_echo_logging' ) ) ? json_decode( get_option( 'dt_echo_logging' ) ) : [];
}

function dt_echo_logging_create( $msg ) {
    return (object) [
        'timestamp' => time(),
        'log'       => $msg
    ];
}

function dt_echo_logging_update( $logs ) {
    update_option( 'dt_echo_logging', json_encode( $logs ) );
}

function dt_echo_logging_add( $log ) {
    $logs   = dt_echo_logging_load();
    $logs[] = dt_echo_logging_create( $log );
    dt_echo_logging_update( $logs );
}

function dt_echo_logging_aged() {
    // Remove entries older than specified aged period!
    $logs = dt_echo_logging_load();
    if ( ! empty( $logs ) ) {
        $cut_off_point_ts  = time() - ( 3600 * 1 ); // 1 hr ago!
        $cut_off_point_idx = 0;

        $count = count( $logs );
        for ( $x = 0; $x < $count; $x ++ ) {

            // Stale logs will typically be found at the start! Therefore, capture transition point!
            if ( $logs[ $x ]->timestamp > $cut_off_point_ts ) {
                $cut_off_point_idx = $x;
                $x                 = $count;
            }
        }

        // Age off any stale logs
        if ( $cut_off_point_idx > 0 ) {
            $stale_logs = array_splice( $logs, 0, $cut_off_point_idx );
            dt_echo_logging_update( $logs );
        }
    }
}

function is_dt_echo_sync_enabled( $option_name ): bool {
    return boolval( get_option( $option_name ) );
}

function fetch_date_range_by_global_dt_echo_last_run( $option_name ): array {
    // Cater for first run states; which should force the initial linking of records across both platforms;
    // ...From a really long time ago...! Currently defaults to a week's worth of data, similar to Echo api defaults! ;)

    $start = get_option( $option_name );
    $end   = time();

    if ( empty( $start ) ) {
        $start = strtotime( '-1 week', $end );
    } else {
        // Ensure to push out by some additional mins, so as to give Echo api sufficient time to propagate any recently closed outcome updates!
        $start = strtotime( '-5 minutes', $start );
    }

    return [
        'start' => $start,
        'end'   => $end
    ];
}

function update_global_dt_echo_last_run( $option_name, $timestamp ) {
    update_option( $option_name, $timestamp );
}

function update_convo_outcome_value( $outcome_name, $param, $value ) {

    // Only update if we have an existing entry
    $supported_outcomes = fetch_supported_echo_convo_outcome_options();
    $outcome            = fetch_supported_echo_convo_outcome_option_by_name( $outcome_name );
    if ( ! empty( $supported_outcomes ) && ! empty( $outcome ) ) {

        if ( isset( $supported_outcomes->{$outcome->id} ) ) {
            $supported_outcomes->{$outcome->id}->{$param} = $value;

            // Save updated list last run
            update_option( 'dt_echo_supported_convo_options', json_encode( $supported_outcomes ) );
        }
    }
}

function fetch_latest_echo_convos( $start_ts, $end_ts ): array {
    return Disciple_Tools_Echo_API::get_convos_by_date_range( $start_ts, $end_ts );
}

function fetch_supported_echo_convo_outcome_option_by_name( $outcome_name ) {
    $supported_outcomes = fetch_supported_echo_convo_outcome_options();
    foreach ( $supported_outcomes as $outcome ) {
        if ( strtolower( trim( $outcome->name ) ) === strtolower( trim( $outcome_name ) ) ) {
            return $outcome;
        }
    }

    return null;
}

function fetch_supported_echo_convo_outcome_options() {
    return ! empty( get_option( 'dt_echo_supported_convo_options' ) ) ? json_decode( get_option( 'dt_echo_supported_convo_options' ) ) : (object) [];
}

function extract_supported_echo_convo_outcome_option_names( $options ): array {
    $option_names = [];
    if ( ! empty( $options ) ) {
        foreach ( $options as $key => $option ) {
            if ( isset( $option->name ) && ! empty( $option->name ) ) {
                $option_names[] = strtolower( trim( $option->name ) );
            }
        }
    }

    return $option_names;
}

function fetch_supported_echo_convo_referrers() {
    return ! empty( get_option( 'dt_echo_supported_convo_referrers' ) ) ? json_decode( get_option( 'dt_echo_supported_convo_referrers' ) ) : [];
}

function extract_supported_echo_convo_referrer_names( $referrers ): array {
    $referrer_names = [];
    if ( ! empty( $referrers ) ) {
        foreach ( $referrers as $key => $referrer ) {
            if ( isset( $referrer->name ) && ! empty( $referrer->name ) ) {
                $referrer_names[] = strtolower( trim( $referrer->name ) );
            }
        }
    }

    return $referrer_names;
}

function fetch_supported_seeker_path_options() {
    return ! empty( get_option( 'dt_echo_dt_supported_seeker_path_options' ) ) ? json_decode( get_option( 'dt_echo_dt_supported_seeker_path_options' ) ) : [];
}

function extract_supported_seeker_path_option_by_echo_name( $options, $echo_outcome ) {
    if ( ! empty( $options ) ) {
        foreach ( $options as $key => $option ) {
            if ( strtolower( trim( $option->echo_name ) ) === strtolower( trim( $echo_outcome ) ) ) {
                return $option;
            }
        }
    }

    return null;
}

function extract_supported_seeker_path_option_by_dt_id( $options, $dt_id ) {
    if ( ! empty( $options ) ) {
        foreach ( $options as $key => $option ) {
            if ( strtolower( trim( $option->dt_id ) ) === strtolower( trim( $dt_id ) ) ) {
                return $option;
            }
        }
    }

    return null;
}

function process_echo_convo( $echo_convo, &$logs ): bool {

    $logs[] = dt_echo_logging_create( 'Processing Echo conversation ID [' . $echo_convo->chat_id . '] with Name [' . $echo_convo->name . '], Outcome [' . $echo_convo->outcome . '] and Referrer [' . $echo_convo->referrer . ']' );

    // As searches are based on name/id associated with incoming conversations, ensure valid entries have been specified
    if ( isset( $echo_convo->name ) && ! empty( $echo_convo->name ) && isset( $echo_convo->chat_id ) && ! empty( $echo_convo->chat_id ) ) {

        // Initially search for any linked dt records, based on echo conversation id
        $logs[]  = dt_echo_logging_create( 'Searching for linked dt records based on echo convo-id [' . $echo_convo->chat_id . ']' );
        $results = dt_records_search_by_convo_id( $echo_convo->chat_id );

        // Attempt to filter out actual dt record
        $dt_record = null;
        if ( ! empty( $results ) && count( $results ) === 1 ) { // Single Hit
            $logs[]    = dt_echo_logging_create( 'Single contacts dt record [' . $results[0]->ID . '] hit detected!' );
            $dt_record = handle_singles( $results[0]->ID, $logs );

        } elseif ( ! empty( $results ) && count( $results ) > 1 ) { // - Multiple Hits
            $logs[]    = dt_echo_logging_create( 'Multiple [' . count( $results ) . '] contacts dt record hits detected!' );
            $dt_record = handle_multiples( $results, $echo_convo, $logs );

        } else {
            // If no id based hits, then attempt to search by convo name
            $logs[]  = dt_echo_logging_create( 'Searching for linked dt records based on echo convo-name [' . $echo_convo->name . ']' );
            $results = dt_records_search_by_convo_name( $echo_convo->name );

            // Only select if we have a single hit; so as to avoid updating the wrong dt record with the same name!
            if ( ! empty( $results ) && count( $results ) === 1 ) { // Single Hit
                $logs[]    = dt_echo_logging_create( 'Single contacts dt record [' . $results[0]->ID . '] hit detected!' );
                $dt_record = handle_singles( $results[0]->ID, $logs );
            }
        }

        // No Hits - If we are still unable to locate a matching dt record, then attempt to create one!
        if ( empty( $dt_record ) ) {
            $logs[]    = dt_echo_logging_create( 'Attempting to create new contacts dt record based on echo convo-name [' . $echo_convo->name . ']' );
            $dt_record = handle_no_hits( $echo_convo, $logs );
        }

        // Hopefully, we should now have a valid dt record to work with!
        if ( ! empty( $dt_record ) && isset( $dt_record['ID'] ) ) {
            $logs[] = dt_echo_logging_create( 'Echo conversation [' . $echo_convo->chat_id . '] linked with contacts dt record [' . $dt_record['ID'] . ']' );

            return ( ! empty( handle_updates( $echo_convo, $dt_record, $logs ) ) );
        } else {
            $logs[] = dt_echo_logging_create( 'Unable to process conversation [' . $echo_convo->chat_id . '], due to null dt record!' );
        }
    } else {
        $logs[] = dt_echo_logging_create( 'Unable to process conversation [' . $echo_convo->chat_id . '], due to no name/id being detected!' );
    }

    return false;
}

function dt_records_search_by_convo_id( $convo_id ): array {
    global $wpdb;

    return $wpdb->get_results( $wpdb->prepare( "
    SELECT post.ID
    FROM $wpdb->posts post
    LEFT JOIN $wpdb->postmeta meta ON (post.ID = meta.post_id)
    WHERE (post.post_type = 'contacts')
    AND (meta.meta_key = 'dt_echo_convo_ids')
    AND (meta.meta_value = %s)
    GROUP BY post.ID", $convo_id ) );
}

function dt_records_search_by_convo_name( $name ): array {
    global $wpdb;

    return $wpdb->get_results( $wpdb->prepare( "
    SELECT post.ID
    FROM $wpdb->posts post
    LEFT JOIN $wpdb->postmeta meta ON (post.ID = meta.post_id)
    WHERE (post.post_type = 'contacts')
    AND (LOWER(TRIM(post.post_title)) = %s)
    AND (meta.meta_key = 'dt_echo_convo_ids')
    GROUP BY post.ID", strtolower( trim( $name ) ) ) );
}

function handle_singles( $post_id, &$logs ) {

    // Fetch corresponding post record
    $dt_post = DT_Posts::get_post( 'contacts', $post_id, false, false );

    // Ensure returned post is not an error!
    if ( is_wp_error( $dt_post ) ) {
        $logs[] = dt_echo_logging_create( 'DT Post Get Error: ' . $dt_post->get_error_message() );

    } else {
        return $dt_post;
    }

    return null;
}

function handle_multiples( $results, $echo_convo, &$logs ) {

    // Iterate results in search of the first record matching echo convo id
    foreach ( $results as $post_id ) {

        // Shortcut! ;)
        $hit = handle_singles( $post_id->ID, $logs );

        // If hit contains echo convo id; then we have a winner!
        if ( ! empty( $hit ) && isset( $hit['dt_echo_convo_ids'] ) && is_array( $hit['dt_echo_convo_ids'] ) && in_array( $echo_convo->chat_id, $hit['dt_echo_convo_ids'] ) ) {
            return $hit;
        }
    }

    // If this point is reached, then return the first valid record within result set
    foreach ( $results as $post_id ) {
        $hit = handle_singles( $post_id->ID, $logs );
        if ( ! empty( $hit ) ) {
            return $hit;
        }
    }

    return null;
}

function handle_no_hits( $echo_convo, &$logs ) {

    // In the event of no hits, then attempt to create a corresponding contacts dt record
    $dt_fields = [];

    $dt_fields['type']                = 'access';
    $dt_fields['sources']['values'][] = [
        'value' => 'echo'
    ];

    $dt_fields['title']                          = $echo_convo->name;
    $dt_fields['name']                           = $echo_convo->name;
    $dt_fields['dt_echo_convo_ids']['values'][0] = [
        'value' => $echo_convo->chat_id
    ];

    // Create new dt post
    $dt_post = DT_Posts::create_post( 'contacts', $dt_fields, false, false );

    // Ensure returned post is not an error!
    if ( is_wp_error( $dt_post ) ) {
        $logs[] = dt_echo_logging_create( 'DT Post Create Error: ' . $dt_post->get_error_message() );

    } else {
        return $dt_post;
    }

    return null;
}

function handle_updates( $echo_convo, $dt_record, &$logs ) {

    // Ensure dt record is updated accordingly, based on echo convo shape
    $updated_fields = [];

    // Assign echo conversation id
    if ( ( isset( $dt_record['dt_echo_convo_ids'] ) && is_array( $dt_record['dt_echo_convo_ids'] ) && ! in_array( $echo_convo->chat_id, $dt_record['dt_echo_convo_ids'] ) ) ||
         ! isset( $dt_record['dt_echo_convo_ids'] ) ) {
        $updated_fields['dt_echo_convo_ids']['values'][] = [
            'value' => $echo_convo->chat_id
        ];
    }

    // Subject to mapping, update seeker path option; if not already set!
    $seeker_path_mapping = extract_supported_seeker_path_option_by_echo_name( fetch_supported_seeker_path_options(), $echo_convo->outcome );
    if ( ! empty( $seeker_path_mapping ) && ! ( isset( $dt_record['seeker_path'] ) && $dt_record['seeker_path']['key'] === $seeker_path_mapping->dt_id ) ) {
        $updated_fields['seeker_path'] = $seeker_path_mapping->dt_id;
    }

    // Fetch echo convo text and update dt record activity stream, if not already set!
    $dt_record_comments_updated = false;
    $fetched_echo_convo         = Disciple_Tools_Echo_API::get_convo_by_id( $echo_convo->chat_id );
    if ( ! empty( $fetched_echo_convo ) && isset( $fetched_echo_convo->messages ) && ! empty( $fetched_echo_convo->messages ) && ( count( $fetched_echo_convo->messages ) > 0 ) ) {
        // Ensure incoming messages have not already been captured
        if ( ! dt_record_already_contains_messages( $dt_record, $fetched_echo_convo->messages ) ) {
            $dt_record_comments_updated = true;
            // Iterate through messages and create corresponding dt comment accordingly
            foreach ( $fetched_echo_convo->messages as $echo_message ) {
                DT_Posts::add_post_comment( $dt_record['post_type'], $dt_record['ID'], ! empty( $echo_message->body ) ? $echo_message->body : '', "comment", [
                    "user_id"        => 0,
                    "comment_author" => $echo_message->name,
                    "comment_date"   => gmdate( 'Y-m-d H:i:s', $echo_message->created_at_secs )
                ], false );
            }
        }
    }

    // If required, update dt record
    if ( count( $updated_fields ) > 0 ) {
        $updated = DT_Posts::update_post( $dt_record['post_type'], $dt_record['ID'], $updated_fields, false, false );

        if ( is_wp_error( $updated ) ) {
            $logs[] = dt_echo_logging_create( 'DT Post Update Error: ' . $updated->get_error_message() );

            return null;
        } else {
            $logs[] = dt_echo_logging_create( 'DT contacts record [' . $updated['ID'] . '] - updated!' );

            return $updated;
        }
    } elseif ( $dt_record_comments_updated ) {
        $logs[] = dt_echo_logging_create( 'DT contacts record [' . $dt_record['ID'] . '] comments - updated!' );

    } else {
        $logs[] = dt_echo_logging_create( 'DT contacts record [' . $dt_record['ID'] . '] - no updates made!' );
    }

    return $dt_record;
}

function dt_record_already_contains_messages( $dt_record, $echo_convo_msgs ): bool {
    // First, fetch associated dt record comments
    $dt_record_comments = DT_Posts::get_post_comments( $dt_record['post_type'], $dt_record['ID'], false );

    // If we have a single hit, then it is assumed messages have already been previously set, so no need to duplicate!
    if ( ! empty( $dt_record_comments ) && ! is_wp_error( $dt_record_comments ) ) {
        foreach ( $dt_record_comments['comments'] as $dt_comment ) {
            foreach ( $echo_convo_msgs as $echo_message ) {
                if ( ! empty( $dt_comment['comment_content'] ) && ! empty( $echo_message->body ) &&
                     strtolower( trim( $dt_comment['comment_content'] ) ) === strtolower( trim( $echo_message->body ) ) ) {
                    return true;
                }
            }
        }
    }

    return false;
}



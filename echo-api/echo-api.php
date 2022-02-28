<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Echo_API
 */
class Disciple_Tools_Echo_API {

    public static $schedule_cron_event_hook = 'dt_echo_sync';

    public static function schedule_cron_event() {
        if ( self::has_api_assets() ) {
            if ( ! wp_next_scheduled( self::$schedule_cron_event_hook ) ) {
                wp_schedule_event( time(), '5min', self::$schedule_cron_event_hook );
            }
        }
    }

    private static function has_api_assets(): bool {
        return self::has_api_token() && self::has_api_host();
    }

    private static function has_api_token(): bool {
        return ! empty( get_option( 'dt_echo_api_token' ) );
    }

    private static function get_api_token(): string {
        return get_option( 'dt_echo_api_token' );
    }

    private static function has_api_host(): bool {
        return ! empty( get_option( 'dt_echo_api_host' ) );
    }

    private static function get_api_host(): string {
        return get_option( 'dt_echo_api_host' );
    }

    private static function get_api_headers(): array {
        return array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . self::get_api_token()
        );
    }

    public static function get_convo_options(): array {
        if ( ! self::has_api_assets() ) {
            return [];
        }

        // Build dispositions api endpoint request
        $api_endpoint = 'https://' . self::get_api_host() . 'report/conversations/dispositions.json';
        $args         = array(
            'headers' => self::get_api_headers()
        );

        // Ensure response is not an error before extracting option ids and labels
        $response = wp_remote_get( $api_endpoint, $args );
        if ( ! is_wp_error( $response ) && ( $response['response']['code'] === 200 ) && isset( $response['body'] ) ) {
            $body = json_decode( $response['body'] );
            if ( isset( $body->labels ) ) {
                $options = [];
                foreach ( $body->labels as $label ) {
                    if ( is_int( $label->id ) ) { // Int based ids seem to have valid option labels
                        $options[] = (object) [
                            'id'   => $label->id,
                            'name' => $label->name
                        ];
                    }
                }

                return $options;
            }
        }

        return [];
    }

    public static function get_convo_referrers(): array {
        if ( ! self::has_api_assets() ) {
            return [];
        }

        // Build referrers api endpoint request
        $api_endpoint = 'https://' . self::get_api_host() . 'report/referrers.json';
        $args         = array(
            'headers' => self::get_api_headers()
        );

        // Ensure response is not an error before extracting option ids and labels
        $response = wp_remote_get( $api_endpoint, $args );
        if ( ! is_wp_error( $response ) && ( $response['response']['code'] === 200 ) && isset( $response['body'] ) ) {
            $body = json_decode( $response['body'] );
            if ( is_array( $body ) && count( $body ) > 0 ) {
                $referrers = [];
                foreach ( $body as $referrer ) {
                    $referrers[] = (object) [
                        'id'   => $referrer->id,
                        'name' => $referrer->name
                    ];
                }

                return $referrers;
            }
        }

        return [];
    }

    public static function get_convos_by_date_range( $start_secs, $end_secs, $by_closed_at_date = true, $timezone = 'UTC' ): array {
        if ( ! self::has_api_assets() ) {
            return [];
        }

        // Build paginated conversations api endpoint request
        $api_endpoint = 'https://' . self::get_api_host() . 'report/conversations/paginated_conversations.json';
        $args         = array(
            'headers' => self::get_api_headers(),
            'timeout' => 30, // 30 secs
            'body'    => array(
                'startDate' => ( $start_secs * 1000 ), // Convert to milliseconds since epoch
                'endDate'   => ( $end_secs * 1000 ), // Convert to milliseconds since epoch
                'dateField' => $by_closed_at_date ? 'true' : 'false',
                'timezone'  => $timezone
            )
        );

        // Ensure response is not an error before extracting data
        $response = wp_remote_get( $api_endpoint, $args );

        if ( ! is_wp_error( $response ) && ( $response['response']['code'] === 200 ) && isset( $response['body'] ) ) {
            $body = json_decode( $response['body'] );
            if ( isset( $body->dataset ) ) {
                $convos = [];
                foreach ( $body->dataset as $convo ) {
                    $convos[] = (object) [
                        'id'             => $convo->id,
                        'chat_id'        => $convo->chat_id,
                        'client_id'      => $convo->client_id,
                        'client_contact' => $convo->client_contact,
                        'type'           => $convo->type,
                        'name'           => $convo->name,
                        'user'           => $convo->user,
                        'created_at'     => $convo->created_at,
                        'closed_at'      => $convo->closed_at,
                        'expiration'     => $convo->expiration,
                        'referrer'       => $convo->referrer,
                        'outcome'        => $convo->outcome,
                        'status'         => $convo->status,
                        'link'           => $convo->link,
                        'pdf'            => $convo->pdf
                    ];
                }

                return $convos;
            }
        }

        return [];
    }

    public static function get_convo_by_id( $convo_id ) {
        if ( ! self::has_api_assets() ) {
            return null;
        }

        // Build fetch conversation api endpoint request
        $api_endpoint = 'https://' . self::get_api_host() . 'api/conversations/' . $convo_id;
        $args         = array(
            'headers' => self::get_api_headers(),
            'timeout' => 30, // 30 secs
            'body'    => array(
                'eager' => '1', // Include everything
            )
        );

        // Ensure response is not an error before extracting data
        $response = wp_remote_get( $api_endpoint, $args );

        if ( ! is_wp_error( $response ) && ( $response['response']['code'] === 200 ) && isset( $response['body'] ) ) {
            $body = json_decode( $response['body'] );
            if ( isset( $body->conversation ) && isset( $body->messages ) ) {
                // First, reformat messages
                $messages = [];
                foreach ( $body->messages as $message ) {
                    $messages[] = (object) [
                        'id'              => $message->id,
                        'name'            => $message->name,
                        'body'            => $message->body,
                        'created_at_secs' => $message->created_at_seconds
                    ];
                }

                // Populate and return conversation object
                $convo = $body->conversation;

                return (object) [
                    'id'            => $convo->id,
                    'uuid'          => $convo->uuid,
                    'name'          => $convo->name,
                    'topic'         => $convo->topic,
                    'type'          => $convo->type,
                    'status'        => $convo->status,
                    'user_id'       => $convo->user->id,
                    'user_name'     => $convo->user->name,
                    'client_id'     => $convo->client->id,
                    'client_name'   => $convo->client->name,
                    'referrer_id'   => $convo->referrer->id,
                    'referrer_name' => $convo->referrer->name,
                    'messages'      => $messages
                ];
            }
        }

        return null;
    }

    public static function update_convo_outcome( $convo_id, $outome_id ): bool {
        if ( ! self::has_api_assets() ) {
            return false;
        }

        // Build update conversation api endpoint request
        $api_endpoint = 'https://' . self::get_api_host() . 'api/conversations/' . $convo_id . '/disposition';
        $args         = array(
            'headers' => self::get_api_headers(),
            'timeout' => 30, // 30 secs
            'method'  => 'PUT',
            'body'    => '{ "disposition": { "outcome_id": ' . $outome_id . ' } }'
        );

        // Request and response
        $response = wp_remote_request( $api_endpoint, $args );

        // Ensure response is not an error
        return ( ! is_wp_error( $response ) && ( $response['response']['code'] === 200 ) );
    }
}

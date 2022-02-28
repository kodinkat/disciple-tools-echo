<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Echo_Menu
 */
class Disciple_Tools_Echo_Menu {

    public $token = 'disciple_tools_echo';

    private static $_instance = null;

    /**
     * Disciple_Tools_Echo_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_Echo_Menu is loaded or can be loaded.
     *
     * @return Disciple_Tools_Echo_Menu instance
     * @since 0.1.0
     * @static
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        add_action( "admin_menu", array( $this, "register_menu" ) );

    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_submenu_page( 'dt_extensions', 'Echo', 'Echo', 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {
    }

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( ! current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }

        $link = 'admin.php?page=' . $this->token . '&tab=';

        ?>
        <div class="wrap">
            <h2>DISCIPLE TOOLS : ECHO</h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'general' || ! isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">General</a>
                <a href="<?php echo esc_attr( $link ) . 'logging' ?>"
                   class="nav-tab <?php echo esc_html( ( $tab == 'logging' || ! isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">Logging</a>
            </h2>

            <?php
            switch ( $tab ) {
                case "general":
                    $object = new Disciple_Tools_Echo_Tab_General();
                    $object->content();
                    break;
                case "logging":
                    $object = new Disciple_Tools_Echo_Tab_Logging();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }
}

Disciple_Tools_Echo_Menu::instance();

/**
 * Class Disciple_Tools_Echo_Tab_General
 */
class Disciple_Tools_Echo_Tab_General {
    public function content() {
        // First, handle update submissions
        $this->process_updates();

        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php /* $this->right_column() */ ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php

        // Load scripts
        $this->load_scripts();
    }

    private function load_scripts() {
        wp_enqueue_script( 'dt_echo_admin_general_scripts', plugin_dir_url( __FILE__ ) . 'js/scripts-admin-general.js', [
            'jquery',
            'lodash'
        ], 1, true );
    }

    private function process_updates() {

        // Connectivity Updates
        if ( isset( $_POST['echo_main_col_connect_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['echo_main_col_connect_nonce'] ) ), 'echo_main_col_connect_nonce' ) ) {

            update_option( 'dt_echo_fetch_echo_sync', isset( $_POST['echo_main_col_connect_echo_fetch_sync_feed'] ) ? 1 : 0 );
            update_option( 'dt_echo_push_dt_sync', isset( $_POST['echo_main_col_connect_dt_push_sync_feed'] ) ? 1 : 0 );

            update_option( 'dt_echo_api_token', isset( $_POST['echo_main_col_connect_echo_api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['echo_main_col_connect_echo_api_token'] ) ) : '' );

            $echo_api_host = '';
            if ( isset( $_POST['echo_main_col_connect_echo_api_host'] ) ) {
                $echo_api_host = sanitize_text_field( wp_unslash( $_POST['echo_main_col_connect_echo_api_host'] ) );
                if ( ! empty( $echo_api_host ) && substr( $echo_api_host, - 1 ) !== '/' ) {
                    $echo_api_host .= '/';
                }
            }
            update_option( 'dt_echo_api_host', $echo_api_host );

            // Request scheduling of cron event
            Disciple_Tools_Echo_API::schedule_cron_event();
        }

        // Available Echo Conversation Option Additions
        if ( isset( $_POST['echo_main_col_available_echo_convo_options_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['echo_main_col_available_echo_convo_options_nonce'] ) ), 'echo_main_col_available_echo_convo_options_nonce' ) ) {

            $selected_convo_option_id   = ( isset( $_POST['echo_main_col_available_echo_convo_options_selected_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['echo_main_col_available_echo_convo_options_selected_id'] ) ) : '';
            $selected_convo_option_name = ( isset( $_POST['echo_main_col_available_echo_convo_options_selected_name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['echo_main_col_available_echo_convo_options_selected_name'] ) ) : '';

            if ( ! empty( $selected_convo_option_id ) && ! empty( $selected_convo_option_name ) ) {

                // Fetch existing option of supported echo convo options.
                $supported_convo_options = json_decode( $this->fetch_echo_supported_convo_options() );

                // Add/Overwrite selected convo option entry.
                $supported_convo_options->{$selected_convo_option_id} = (object) [
                    'id'                       => $selected_convo_option_id,
                    'name'                     => $selected_convo_option_name,
                    'echo_to_dt_last_sync_run' => '',
                    'dt_to_echo_last_sync_run' => '',
                    'log'                      => ''
                ];

                // Save changes.
                update_option( 'dt_echo_supported_convo_options', json_encode( $supported_convo_options ) );

            }
        }

        // Supported Echo Conversation Options Updates
        if ( isset( $_POST['echo_main_col_supported_echo_convo_options_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['echo_main_col_supported_echo_convo_options_form_nonce'] ) ), 'echo_main_col_supported_echo_convo_options_form_nonce' ) ) {
            update_option( 'dt_echo_supported_convo_options', isset( $_POST['echo_main_col_supported_echo_convo_options_hidden_current_convo_options'] ) ? sanitize_text_field( wp_unslash( $_POST['echo_main_col_supported_echo_convo_options_hidden_current_convo_options'] ) ) : '{}' );
        }

        // Available Echo Conversation Referrer Additions
        if ( isset( $_POST['echo_main_col_available_echo_convo_referrers_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['echo_main_col_available_echo_convo_referrers_nonce'] ) ), 'echo_main_col_available_echo_convo_referrers_nonce' ) ) {

            $selected_convo_referrer_id   = ( isset( $_POST['echo_main_col_available_echo_convo_referrers_selected_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['echo_main_col_available_echo_convo_referrers_selected_id'] ) ) : '';
            $selected_convo_referrer_name = ( isset( $_POST['echo_main_col_available_echo_convo_referrers_selected_name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['echo_main_col_available_echo_convo_referrers_selected_name'] ) ) : '';

            if ( ! empty( $selected_convo_referrer_id ) && ! empty( $selected_convo_referrer_name ) ) {

                // Fetch existing referrer of supported echo convo referrers.
                $supported_convo_referrers = json_decode( $this->fetch_echo_supported_convo_referrers() );

                // Add selected convo referrer entry.
                $supported_convo_referrers[] = (object) [
                    'id'   => $selected_convo_referrer_id,
                    'name' => $selected_convo_referrer_name
                ];

                // Save changes.
                update_option( 'dt_echo_supported_convo_referrers', json_encode( $supported_convo_referrers ) );

            }
        }

        // Supported Echo Conversation Referrers Updates
        if ( isset( $_POST['echo_main_col_supported_echo_convo_referrers_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['echo_main_col_supported_echo_convo_referrers_form_nonce'] ) ), 'echo_main_col_supported_echo_convo_referrers_form_nonce' ) ) {
            update_option( 'dt_echo_supported_convo_referrers', isset( $_POST['echo_main_col_supported_echo_convo_referrers_hidden_current_convo_referrers'] ) ? sanitize_text_field( wp_unslash( $_POST['echo_main_col_supported_echo_convo_referrers_hidden_current_convo_referrers'] ) ) : '[]' );
        }

        // Supported DT Seeker Path Options Updates
        if ( isset( $_POST['echo_main_col_supported_dt_seeker_path_options_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['echo_main_col_supported_dt_seeker_path_options_form_nonce'] ) ), 'echo_main_col_supported_dt_seeker_path_options_form_nonce' ) ) {
            update_option( 'dt_echo_dt_supported_seeker_path_options', isset( $_POST['echo_main_col_supported_dt_seeker_path_options_hidden'] ) ? sanitize_text_field( wp_unslash( $_POST['echo_main_col_supported_dt_seeker_path_options_hidden'] ) ) : '[]' );
        }
    }

    private function fetch_echo_api_token(): string {
        Disciple_Tools_Echo_API::schedule_cron_event();

        return get_option( 'dt_echo_api_token' );
    }

    private function fetch_echo_api_host(): string {
        Disciple_Tools_Echo_API::schedule_cron_event();

        return get_option( 'dt_echo_api_host' );
    }

    private function is_fetch_echo_sync_enabled(): bool {

        // Ensure the default state for first time setups, is that of TRUE!
        $value = get_option( 'dt_echo_fetch_echo_sync' );
        if ( isset( wp_cache_get( 'notoptions', 'options' )['dt_echo_fetch_echo_sync'] ) ) {
            update_option( 'dt_echo_fetch_echo_sync', 1 );

            return true;

        } else {
            return boolval( $value );
        }
    }

    private function is_push_dt_sync_enabled(): bool {

        // Ensure the default state for first time setups, is that of TRUE!
        $value = get_option( 'dt_echo_push_dt_sync' );
        if ( isset( wp_cache_get( 'notoptions', 'options' )['dt_echo_push_dt_sync'] ) ) {
            update_option( 'dt_echo_push_dt_sync', 1 );

            return true;

        } else {
            return boolval( $value );
        }
    }

    private function fetch_echo_supported_convo_options(): string {
        $supported_convo_options = get_option( 'dt_echo_supported_convo_options' );

        return ! empty( $supported_convo_options ) ? $supported_convo_options : '{}';
    }

    private function fetch_echo_supported_convo_referrers(): string {
        $supported_convo_referrers = get_option( 'dt_echo_supported_convo_referrers' );

        return ! empty( $supported_convo_referrers ) ? $supported_convo_referrers : '[]';
    }


    private function sort_array_by_name( $array ) {
        usort( $array, function ( $a, $b ) {
            return strcmp( $a->name, $b->name );
        } );

        return $array;
    }

    private function array_contains_id( $array, $needle ): bool {
        if ( ! empty( $array ) ) {
            foreach ( $array as $item ) {
                if ( strtolower( trim( $item->id ) ) === strtolower( trim( $needle ) ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table id="echo_main_col_connect_table_section" class="widefat striped">
            <thead>
            <tr>
                <th>Connectivity</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_connectivity(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <!-- Box -->
        <table id="echo_main_col_available_echo_convo_options_table_section" class="widefat striped"
               style="display: none;">
            <thead>
            <tr>
                <th>Select Echo Conversation Options To Sync</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_available_echo_convo_options(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <!-- Box -->
        <table id="echo_main_col_supported_echo_convo_options_table_section" class="widefat striped"
               style="display: none;">
            <thead>
            <tr>
                <th>Supported Echo Conversation Options</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_supported_echo_convo_options(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <!-- Box -->
        <table id="echo_main_col_available_echo_convo_referrers_table_section" class="widefat striped"
               style="display: none;">
            <thead>
            <tr>
                <th>Select Echo Conversation Referrers To Sync</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_available_echo_convo_referrers(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <!-- Box -->
        <table id="echo_main_col_supported_echo_convo_referrers_table_section" class="widefat striped"
               style="display: none;">
            <thead>
            <tr>
                <th>Supported Echo Conversation Referrers</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_supported_echo_convo_referrers(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <!-- Box -->
        <table id="echo_main_col_supported_dt_seeker_path_options_table_section" class="widefat striped"
               style="display: none;">
            <thead>
            <tr>
                <th>Supported DT Seeker Path Options</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_supported_dt_seeker_path_options(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Information</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    private function main_column_connectivity() {
        ?>
        <form method="POST">
            <input type="hidden" id="echo_main_col_connect_nonce" name="echo_main_col_connect_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'echo_main_col_connect_nonce' ) ) ?>"/>

            <table class="widefat striped">
                <tr>
                    <td style="vertical-align: middle;">Echo API Token</td>
                    <td>
                        <input type="password" style="min-width: 100%;" id="echo_main_col_connect_echo_api_token"
                               name="echo_main_col_connect_echo_api_token"
                               value="<?php echo esc_attr( $this->fetch_echo_api_token() ) ?>"/><br>
                        <input type="checkbox" id="echo_main_col_connect_echo_api_token_show">Show API Token
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: middle;">Echo API Host</td>
                    <td style="vertical-align: middle;">
                        https:// <input type="text" style="min-width: 85%;" id="echo_main_col_connect_echo_api_host"
                                        name="echo_main_col_connect_echo_api_host"
                                        value="<?php echo esc_attr( $this->fetch_echo_api_host() ) ?>"/>
                    </td>
                </tr>
                <tr>
                    <td>Fetch Echo Updates</td>
                    <td>
                        <input type="checkbox" id="echo_main_col_connect_echo_fetch_sync_feed"
                               name="echo_main_col_connect_echo_fetch_sync_feed" <?php echo esc_attr( $this->is_fetch_echo_sync_enabled() ? 'checked' : '' ) ?> />
                    </td>
                </tr>
                <tr>
                    <td>Push DT Updates</td>
                    <td>
                        <input type="checkbox" id="echo_main_col_connect_dt_push_sync_feed"
                               name="echo_main_col_connect_dt_push_sync_feed" <?php echo esc_attr( $this->is_push_dt_sync_enabled() ? 'checked' : '' ) ?> />
                    </td>
                </tr>
            </table>

            <br>
            <span style="float:right;">
                <button type="submit"
                        class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></button>
            </span>
        </form>
        <?php
    }

    private function main_column_available_echo_convo_options() {
        ?>
        <select style="min-width: 80%;" id="echo_main_col_available_echo_convo_options_select">
            <option disabled selected value>-- available echo convo options --</option>

            <?php
            $current_backend_echo_convo_options = Disciple_Tools_Echo_API::get_convo_options();
            $supported_convo_options            = json_decode( $this->fetch_echo_supported_convo_options() );
            if ( ! empty( $current_backend_echo_convo_options ) ) {
                $current_backend_echo_convo_options = $this->sort_array_by_name( $current_backend_echo_convo_options );
                foreach ( $current_backend_echo_convo_options as $option ) {

                    // No need to display already supported option
                    if ( ! isset( $supported_convo_options->{$option->id} ) ) {
                        echo '<option value="' . esc_attr( $option->id ) . '">' . esc_attr( $option->name ) . '</option>';
                    }
                }
            }
            ?>
        </select>

        <span style="float:right;">
            <a id="echo_main_col_available_echo_convo_options_select_add"
               class="button float-right"><?php esc_html_e( "Add", 'disciple_tools' ) ?></a>
        </span>

        <form method="POST" id="echo_main_col_available_echo_convo_options_form">
            <input type="hidden" id="echo_main_col_available_echo_convo_options_nonce"
                   name="echo_main_col_available_echo_convo_options_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'echo_main_col_available_echo_convo_options_nonce' ) ) ?>"/>

            <input type="hidden" value="" id="echo_main_col_available_echo_convo_options_selected_id"
                   name="echo_main_col_available_echo_convo_options_selected_id"/>

            <input type="hidden" value="" id="echo_main_col_available_echo_convo_options_selected_name"
                   name="echo_main_col_available_echo_convo_options_selected_name"/>
        </form>
        <?php
    }

    private function main_column_supported_echo_convo_options() {
        ?>
        <table id="echo_main_col_supported_echo_convo_options_table" class="widefat striped">
            <thead>
            <tr>
                <th style="vertical-align: middle; text-align: center;">Name</th>
                <th style="vertical-align: middle; text-align: center;">Sync Status</th>
                <th style="vertical-align: middle; text-align: center;">Echo to DT Last Update</th>
                <th style="vertical-align: middle; text-align: center;">DT to Echo Last Update</th>
                <th></th>
            </tr>
            </thead>
            <?php
            $supported_convo_options = json_decode( $this->fetch_echo_supported_convo_options() );
            if ( ! empty( $supported_convo_options ) ) {
                foreach ( $supported_convo_options as $option ) {
                    echo '<tr>';
                    echo '<td style="vertical-align: middle; text-align: center;">' . esc_attr( $option->name ) . '</td>';

                    echo '<td style="vertical-align: middle; text-align: center;">' . esc_attr( $this::main_column_supported_echo_convo_options_logging() ) . '</td>';

                    $echo_to_dt_last_run = ! empty( $option->echo_to_dt_last_sync_run ) ? dt_format_date( $option->echo_to_dt_last_sync_run, 'long' ) : '---';
                    echo '<td style="vertical-align: middle; text-align: center;">' . esc_attr( $echo_to_dt_last_run ) . '</td>';

                    $dt_to_echo_last_run = ! empty( $option->dt_to_echo_last_sync_run ) ? dt_format_date( $option->dt_to_echo_last_sync_run, 'long' ) : '---';
                    echo '<td style="vertical-align: middle; text-align: center;">' . esc_attr( $dt_to_echo_last_run ) . '</td>';

                    echo '<td style="vertical-align: middle;">';
                    echo '<span style="float:right;"><a class="button float-right echo-main-col-supported-echo-convo-options-table-row-remove-but">Remove</a></span>';
                    echo '<input type="hidden" id="echo_main_col_supported_echo_convo_options_table_row_remove_hidden_id" value="' . esc_attr( $option->id ) . '">';
                    echo '</td>';
                    echo '</tr>';
                }
            }
            ?>
        </table>
        <br>

        <form method="POST" id="echo_main_col_supported_echo_convo_options_form">
            <input type="hidden" id="echo_main_col_supported_echo_convo_options_form_nonce"
                   name="echo_main_col_supported_echo_convo_options_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'echo_main_col_supported_echo_convo_options_form_nonce' ) ) ?>"/>

            <input type="hidden" id="echo_main_col_supported_echo_convo_options_hidden_current_convo_options"
                   name="echo_main_col_supported_echo_convo_options_hidden_current_convo_options"
                   value="<?php echo esc_attr( $this->fetch_echo_supported_convo_options() ) ?>"/>
        </form>
        <?php
    }

    private function main_column_supported_echo_convo_options_logging() {

        // Last Synced at X
        $global_ts_dt_to_echo = get_option( 'dt_echo_sync_last_run_ts_dt_to_echo' );
        $global_ts_echo_to_dt = get_option( 'dt_echo_sync_last_run_ts_echo_to_dt' );
        if ( ! empty( $global_ts_echo_to_dt ) && ! empty( $global_ts_dt_to_echo ) ) {
            if ( $global_ts_echo_to_dt >= $global_ts_dt_to_echo ) {
                return 'Last Synced at ' . dt_format_date( $global_ts_echo_to_dt, 'long' );
            } else {
                return 'Last Synced at ' . dt_format_date( $global_ts_dt_to_echo, 'long' );
            }
        }

        if ( ! empty( $global_ts_echo_to_dt ) ) {
            return 'Last Synced at ' . dt_format_date( $global_ts_echo_to_dt, 'long' );
        }

        if ( ! empty( $global_ts_dt_to_echo ) ) {
            return 'Last Synced at ' . dt_format_date( $global_ts_dt_to_echo, 'long' );
        }

        return '---';
    }

    private function main_column_available_echo_convo_referrers() {
        ?>
        <select style="min-width: 80%;" id="echo_main_col_available_echo_convo_referrers_select">
            <option disabled selected value>-- available echo convo referrers --</option>

            <?php
            $current_backend_echo_convo_referrers = Disciple_Tools_Echo_API::get_convo_referrers();
            $supported_convo_referrers            = json_decode( $this->fetch_echo_supported_convo_referrers() );
            if ( ! empty( $current_backend_echo_convo_referrers ) ) {
                $current_backend_echo_convo_referrers = $this->sort_array_by_name( $current_backend_echo_convo_referrers );
                foreach ( $current_backend_echo_convo_referrers as $referrer ) {

                    // No need to display already supported referrers
                    if ( ! $this->array_contains_id( $supported_convo_referrers, $referrer->id ) ) {
                        echo '<option value="' . esc_attr( $referrer->id ) . '">' . esc_attr( $referrer->name ) . '</option>';
                    }
                }
            }
            ?>
        </select>

        <span style="float:right;">
            <a id="echo_main_col_available_echo_convo_referrers_select_add"
               class="button float-right"><?php esc_html_e( "Add", 'disciple_tools' ) ?></a>
        </span>

        <form method="POST" id="echo_main_col_available_echo_convo_referrers_form">
            <input type="hidden" id="echo_main_col_available_echo_convo_referrers_nonce"
                   name="echo_main_col_available_echo_convo_referrers_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'echo_main_col_available_echo_convo_referrers_nonce' ) ) ?>"/>

            <input type="hidden" value="" id="echo_main_col_available_echo_convo_referrers_selected_id"
                   name="echo_main_col_available_echo_convo_referrers_selected_id"/>

            <input type="hidden" value="" id="echo_main_col_available_echo_convo_referrers_selected_name"
                   name="echo_main_col_available_echo_convo_referrers_selected_name"/>
        </form>
        <?php
    }

    private function main_column_supported_echo_convo_referrers() {
        ?>
        <table id="echo_main_col_supported_echo_convo_referrers_table" class="widefat striped">
            <thead>
            <tr>
                <th style="vertical-align: middle; text-align: left;">Name</th>
                <th></th>
            </tr>
            </thead>
            <?php
            $supported_convo_referrers = json_decode( $this->fetch_echo_supported_convo_referrers() );
            if ( ! empty( $supported_convo_referrers ) ) {
                foreach ( $supported_convo_referrers as $referrer ) {
                    echo '<tr>';
                    echo '<td style="vertical-align: middle; text-align: left;">' . esc_attr( $referrer->name ) . '</td>';

                    echo '<td style="vertical-align: middle;">';
                    echo '<span style="float:right;"><a class="button float-right echo-main-col-supported-echo-convo-referrers-table-row-remove-but">Remove</a></span>';
                    echo '<input type="hidden" id="echo_main_col_supported_echo_convo_referrers_table_row_remove_hidden_id" value="' . esc_attr( $referrer->id ) . '">';
                    echo '</td>';
                    echo '</tr>';
                }
            }
            ?>
        </table>
        <br>

        <form method="POST" id="echo_main_col_supported_echo_convo_referrers_form">
            <input type="hidden" id="echo_main_col_supported_echo_convo_referrers_form_nonce"
                   name="echo_main_col_supported_echo_convo_referrers_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'echo_main_col_supported_echo_convo_referrers_form_nonce' ) ) ?>"/>

            <input type="hidden" id="echo_main_col_supported_echo_convo_referrers_hidden_current_convo_referrers"
                   name="echo_main_col_supported_echo_convo_referrers_hidden_current_convo_referrers"
                   value="<?php echo esc_attr( $this->fetch_echo_supported_convo_referrers() ) ?>"/>
        </form>
        <?php
    }

    private function main_column_supported_dt_seeker_path_options() {
        $supported_echo_convo_options = Disciple_Tools_Echo_API::get_convo_options();
        $supported_echo_convo_options = $this->sort_array_by_name( $supported_echo_convo_options );
        ?>
        <input type="hidden" id="echo_main_col_supported_dt_seeker_path_options_supported_echo_convo_options_hidden"
               value="<?php echo esc_attr( json_encode( $supported_echo_convo_options ) ) ?>"/>

        <select style="min-width: 80%;" id="echo_main_col_supported_dt_seeker_path_options_select_ele">
            <option disabled selected value>-- select supported seeker options --</option>

            <?php
            // List Contacts seeker_path field options
            $contacts_settings = DT_Posts::get_post_settings( 'contacts' );
            if ( ! empty( $contacts_settings ) && isset( $contacts_settings['fields']['seeker_path'] ) ) {
                foreach ( $contacts_settings['fields']['seeker_path']['default'] as $key => $value ) {
                    echo '<option value="' . esc_attr( $key ) . '">' . esc_attr( $value['label'] ) . '</option>';
                }
            }
            ?>

        </select>

        <span style="float:right;">
            <a id="echo_main_col_supported_dt_seeker_path_options_select_ele_add"
               class="button float-right"><?php esc_html_e( "Add", 'disciple_tools' ) ?></a>
        </span>
        <br><br>

        Ensure to link DT Option with corresponding Echo Option, so as to ensure correct options are kept updated, during data exchanges.
        <br><br>

        <table id="echo_main_col_supported_dt_seeker_path_options_table" class="widefat striped">
            <thead>
            <tr>
                <th style="vertical-align: middle; text-align: center;">DT Option</th>
                <th style="vertical-align: middle; text-align: center;">Echo Option</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php $this->main_column_supported_dt_seeker_path_options_display_saved_options( 'echo_main_col_supported_dt_seeker_path_options_table', 'echo_main_col_supported_dt_seeker_path_options_form', 'echo_main_col_supported_dt_seeker_path_options_hidden', ! empty( get_option( 'dt_echo_dt_supported_seeker_path_options' ) ) ? json_decode( get_option( 'dt_echo_dt_supported_seeker_path_options' ) ) : json_decode( '[]' ) ); ?>
            </tbody>
        </table>
        <br>

        <span style="float:right;">
            <a id="echo_main_col_supported_dt_seeker_path_options_update_but"
               class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></a>
        </span>
        <br>

        <form method="POST" id="echo_main_col_supported_dt_seeker_path_options_form">
            <input type="hidden" id="echo_main_col_supported_dt_seeker_path_options_form_nonce"
                   name="echo_main_col_supported_dt_seeker_path_options_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'echo_main_col_supported_dt_seeker_path_options_form_nonce' ) ) ?>"/>

            <input type="hidden" id="echo_main_col_supported_dt_seeker_path_options_hidden"
                   name="echo_main_col_supported_dt_seeker_path_options_hidden" value="[]"/>
        </form>
        <?php
    }

    private function main_column_supported_dt_seeker_path_options_display_saved_options( $dt_option_table, $dt_option_form, $dt_option_hidden_values, $supported_options ) {
        /*
         * Proceed with displaying of supported options.
         */

        $supported_echo_convo_options = Disciple_Tools_Echo_API::get_convo_options();
        $supported_echo_convo_options = $this->sort_array_by_name( $supported_echo_convo_options );
        foreach ( $supported_options as $option ) {
            echo '<tr>';
            echo '<input type="hidden" id="echo_main_col_supported_dt_option_table_hidden" value="' . esc_attr( $dt_option_table ) . '" />';
            echo '<input type="hidden" id="echo_main_col_supported_dt_option_form_hidden" value="' . esc_attr( $dt_option_form ) . '" />';
            echo '<input type="hidden" id="echo_main_col_supported_dt_option_values_hidden" value="' . esc_attr( $dt_option_hidden_values ) . '" />';
            echo '<input type="hidden" id="echo_main_col_supported_dt_option_id_hidden" value="' . esc_attr( $option->dt_id ) . '" />';
            echo '<input type="hidden" id="echo_main_col_supported_dt_option_name_hidden" value="' . esc_attr( $option->dt_name ) . '" />';
            echo '<td style="vertical-align: middle; text-align: center;">';
            echo esc_attr( $option->dt_name );
            echo '</td>';
            echo '<td style="vertical-align: middle; text-align: center;">';

            echo '<select style="max-width: 300px;" id="echo_main_col_supported_dt_echo_option_select">';
            if ( ! empty( $supported_echo_convo_options ) ) {
                foreach ( $supported_echo_convo_options as $echo_option ) {
                    $selected = ( '' . $option->echo_id === '' . $echo_option->id ) ? 'selected' : '';
                    echo '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $echo_option->id ) . '">' . esc_attr( $echo_option->name ) . '</option>';
                }
            }
            echo '</select>';

            echo '</td>';
            echo '<td>';
            echo '<span style="float:right;"><a class="button float-right echo-main-col-supported-dt-seeker-path-options-table-row-remove-but">Remove</a></span>';
            echo '</td>';
            echo '</tr>';
        }
    }
}


/**
 * Class Disciple_Tools_Echo_Tab_Logging
 */
class Disciple_Tools_Echo_Tab_Logging {
    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php /* $this->right_column() */ ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Logging</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_display_logging(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Information</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function main_column_display_logging() {
        ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th style="vertical-align: middle; text-align: left; min-width: 150px;">Timestamp</th>
                <th style="vertical-align: middle; text-align: left;">Log</th>
            </tr>
            </thead>
            <?php
            $logs = ! empty( get_option( 'dt_echo_logging' ) ) ? json_decode( get_option( 'dt_echo_logging' ) ) : [];
            if ( ! empty( $logs ) ) {
                $counter = 0;
                $limit   = 500;
                for ( $x = count( $logs ) - 1; $x > 0; $x -- ) {
                    if ( ++ $counter <= $limit ) {
                        echo '<tr>';
                        echo '<td style="vertical-align: middle; text-align: left; min-width: 150px;">' . esc_attr( dt_format_date( $logs[ $x ]->timestamp, 'long' ) ) . '</td>';
                        echo '<td style="vertical-align: middle; text-align: left;">' . esc_attr( $logs[ $x ]->log ) . '</td>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
            }
            ?>
        </table>
        <?php
    }
}


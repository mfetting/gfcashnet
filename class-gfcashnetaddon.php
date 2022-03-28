<?php
GFForms::include_payment_addon_framework();

class GFcashnetAddOn extends GFPaymentAddOn
{
    protected $_version = '1.1';
    protected $_min_gravityforms_version = '2.5.16';
    protected $_slug = 'cashnetaddon';
    protected $_path = 'cashnetaddon/cashnetaddon.php';
    protected $_full_path = __FILE__;
    protected $_title = 'GF Cashnet';
    protected $_short_title = 'Cashnet';
    protected $_supports_callbacks = true;
    protected $_requires_credit_card = false;
    protected $redirect_url = '/?callback=cashnetaddon';
    protected $cashnet_url = 'https://commerce.cashnet.com/';
    protected $feed = array();


    private static $_instance = null;

    /**
     * Get an instance of this class.
     *
     * @return GFcashnetAddOn
     */
    public static function get_instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new GFcashnetAddOn();
        }
        return self::$_instance;
    }

    public function is_cashnet_condition_met()
    {
        return true;
    }

    /**
     * Handles hooks and loading of language files.
     */
    public function init()
    {
        parent::init();

        // Intercepting callback requests
        add_action('parse_request', array($this, 'maybe_process_callback'));

        $this->feed = array();
        add_filter('gform_submit_button', array($this, 'form_submit_button'), 10, 2);
    }
    // # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

    /**
     * Return the scripts which should be enqueued.
     *
     * @return array
     */
    public function scripts()
    {
        $scripts = array(array('handle' => 'my_script_js',
            'src' => $this->get_base_url() . '/js/my_script.js',
            'version' => $this->_version, 'deps' => array('jquery'),
            'strings' => array('first' => esc_html__('First Choice', 'cashnetaddon'),
                'second' => esc_html__('Second Choice', 'cashnetaddon'),
                'third' => esc_html__('Third Choice', 'cashnetaddon')
            ),
            'enqueue' => array(array('admin_page' => array('form_settings'), 'tab' => 'cashnetaddon'))),
        );

        return array_merge(parent::scripts(), $scripts);
    }

    /**
     * Return the stylesheets which should be enqueued.
     *
     * @return array
     */
    public function styles()
    {
        $styles = array(array('handle' => 'my_styles_css',
            'src' => $this->get_base_url() . '/css/my_styles.css',
            'version' => $this->_version,
            'enqueue' => array(array('field_types' => array('poll')))
        )
        );
        return array_merge(parent::styles(), $styles);
    }

    // # FRONTEND FUNCTIONS --------------------------------------------------------------------------------------------

    /**
     * Add the text in the plugin settings to the bottom of the form if enabled for this form.
     *
     * @param string $button The string containing the input tag to be filtered.
     * @param array $form The form currently being displayed.
     *
     * @return string
     */
    function form_submit_button($button, $form)
    {
        $settings = $this->get_form_settings($form);
        if (isset($settings['enabled']) && true == $settings['enabled']) {
            //$button = "<div>{$text}</div>" . $button;
        }
        return $button;
    }

    // # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

    /**
     * Creates a custom page for this add-on.
     */
    public function plugin_page()
    {
        echo 'This page appears in the Forms menu';
    }

    /**
     * Configures the settings which should be rendered on the add-on settings tab.
     *
     * @return array
     */
    public function plugin_settings_fields()
    {
        return array(array('title' => esc_html__('Cashnet General Settings', 'cashnetaddon'),
            'fields' => array(
                array(
                    'name' => 'name',
                    'tooltip' => esc_html__('Addon Name', 'cashnetaddon'),
                    'label' => esc_html__('Title', 'cashnetaddon'),
                    'type' => 'text',
                    'class' => 'small',
                    'feedback_callback' => array($this, 'is_valid_setting'),),
                array(
                    'name' => 'checkoutname',
                    'tooltip' => esc_html__('Enter the Checkout Name to send to CASHNet.', 'cashnetaddon'),
                    'label' => esc_html__('Checkout Name', 'cashnetaddon'),
                    'type' => 'text',
                    'class' => 'small',),
                array(
                    'name' => 'successful_url',
                    'tooltip' => esc_html__('URL for Default Successful Transaction Page', 'cashnetaddon'),
                    'label' => esc_html__('Successful URL', 'cashnetaddon'),
                    'type' => 'text',
                    'class' => 'small',),
                array(
                    'name' => 'unsuccessful_url',
                    'tooltip' => esc_html__('URL for Default Unsuccessful Transaction Page', 'cashnetaddon'),
                    'label' => esc_html__('Unsuccessful URL', 'cashnetaddon'),
                    'type' => 'text',
                    'class' => 'small',),
            )));
    }

    /**
     * Configures the settings which should be rendered on the Form Settings > cashnet Add-On tab.
     *
     * @return array
     */
    public function feed_settings_fields()
    {
        return array(array('title' => esc_html__("Cashnet Form Settings", "cashnetaddon"),
            'fields' => array(
                array("name" => "feedName",
                    'tooltip' => '<h6>' . __('Name', 'cashnetaddon') . "</h6>" . __('Enter a feed name to uniquely identify this setup.', 'cashnetaddon'),
                    'label' => __('Name', 'cashnetaddon'),
                    'type' => 'text',
                    'class' => 'medium',
                    'required' => true,
                ),
                array('name' => 'transactionType',
                    'tooltip' => '<h6>' . __('Transaction Type', 'cashnetaddon') . '</h6>' . __('Select a transaction type', 'cashnetaddon'),
                    'label' => __('Transaction Type', 'cashnetaddon'),
                    'type' => 'select',
                    'choices' => array(
                        array('label' => __('Select a transaction type', 'cashnetaddon'), 'value' => ''),
                        array('label' => __('Products and Services', 'cashnetaddon'), 'value' => 'product'),
                        array('label' => __('Subscription', 'cashnetaddon'), 'value' => 'subscription')
                    ),
                ),
                array('name' => 'glcode',
                    'tooltip' => esc_html__('Enter the GL code to send to CASHNet.', 'cashnetaddon'),
                    'label' => esc_html__('GL Code', 'cashnetaddon'),
                    'type' => 'text', 'class' => 'small',
                    'feedback_callback' => array($this, 'is_valid_setting'),),
                array('name' => 'amount_field',
                    'tooltip' => esc_html__('Use field number to reference the form field.', 'cashnetaddon'),
                    'label' => esc_html__('Payment Field', 'cashnetaddon'),
                    'type' => 'text',
                    'class' => 'small',),
                array('name' => 'successful_url',
                    'tooltip' => esc_html__('URL for Form Successful Transaction Page', 'cashnetaddon'),
                    'label' => esc_html__('Successful URL', 'cashnetaddon'),
                    'type' => 'text',
                    'class' => 'small',),
                array(
                    'name' => 'unsuccessful_url',
                    'tooltip' => esc_html__('URL for Form Unsuccessful Transaction Page', 'cashnetaddon'),
                    'label' => esc_html__('Unsuccessful URL', 'cashnetaddon'),
                    'type' => 'text',
                    'class' => 'small',),),),
        );
    }

    public function redirect_url($feed, $submission_data, $form, $entry)
    {
        $settings = $this->get_form_settings($form);
        $result = $this->is_custom_logic_met($form, $entry);
        if ($result) {
            $transactionID = $entry['id'];
            gform_update_meta($entry['id'], META_TRANSACTION_ID, $transactionID);
            $valCode = '';
            $validationChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
            while (strlen($valCode) < 32) {
                $valCode .= $validationChars[mt_rand(0, strlen($validationChars) - 1)];
            }

            $entry['valCode'] = $valCode;
            $entry[META_TRANSACTION_ID] = $transactionID;
            GFFormsModel::update_lead_property($entry['id'], 'payment_status', 'Processing');
            $entry['payment_status'] = 'Processing';
            $entry['payment_date'] = date('Y-m-d H:i:s');
            gform_update_meta($entry['id'], META_UNIQUE_ID, GFFormsModel::get_form_unique_id($form['id']));
            $body = array(
                'itemcode1' => rgar($feed['meta'], 'glcode'),
                'amount1' => GFCommon::format_number((rgar($entry, rgar($feed['meta'], 'amount_field'))), 'currency', 'USD', ''),
                'ref1type' => 'TRANSID',
                'ref1val' => $entry[META_TRANSACTION_ID],
                'ref2type' => 'VALCODE',
                'ref2val' => $entry['valCode'],
            );


            $response = wp_remote_get($this->cashnet_url . $this->get_plugin_setting('checkoutname'), array('name' => 'mainForm', 'target' => '_blank', 'body' => $body));

            if (!is_wp_error($response)
                && isset($response['http_response'])
                && $response['http_response'] instanceof WP_HTTP_Requests_Response
                && method_exists($response['http_response'], 'get_response_object')) {
                $this->redirect_url = $response['http_response']->get_response_object()->url;
                return $this->redirect_url;
            }
            fail_payment($entry, array());
            return $this->redirect_url;
        }
    }

    public function confirmation($confirmation, $form, $entry, $ajax)
    {
        if (empty($this->redirect_url)) {
            return $confirmation;
        }
        $confirmation = array('redirect' => $this->redirect_url);
        return $confirmation;
    }

    /**
     * Define the markup for the my_custom_field_type type field.
     *
     * @param array $field The field properties.
     * @param bool|true $echo Should the setting markup be echoed.
     */
    public function settings_my_custom_field_type($field, $echo = true)
    {
        echo '<div>' . esc_html__('My custom field contains a few settings:', 'cashnetaddon') . '</div>';
        // get the text field settings from the main field and then render the text field
        $text_field = $field['args']['text'];
        $this->settings_text($text_field);
        // get the checkbox field settings from the main field and then render the checkbox field
        $checkbox_field = $field['args']['checkbox'];
        $this->settings_checkbox($checkbox_field);
    }
    // # cashnet CONDITION EXAMPLE --------------------------------------------------------------------------------------

    /**
     * Define the markup for the custom_logic_type type field.
     *
     * @param array $field The field properties.
     * @param bool|true $echo Should the setting markup be echoed.
     */
    public function settings_custom_logic_type($field, $echo = true)
    {
        // Get the setting name.
        $name = $field['name'];
        // Define the properties for the checkbox to be used to enable/disable access to the cashnet condition settings.
        $checkbox_field = array('name' => $name, 'type' => 'checkbox', 'choices' => array(array('label' => esc_html__('Enabled', 'cashnetaddon'), 'name' => $name . '_enabled',),), 'onclick' => "if(this.checked){jQuery('#{$name}_condition_container').show();} else{jQuery('#{$name}_condition_container').hide();}",);
        // Determine if the checkbox is checked, if not the cashnet condition settings should be hidden.
        $is_enabled = $this->get_setting($name . '_enabled') == '1';
        $container_style = !$is_enabled ? "style='display:none;'" : '';
        // Put together the field markup.
        $str = sprintf("%s<div id='%s_condition_container' %s>%s</div>", $this->settings_checkbox($checkbox_field, false), $name, $container_style, $this->cashnet_condition($name));
        echo $str;
    }

    /**
     * Build an array of choices containing fields which are compatible with conditional logic.
     *
     * @return array
     */
    public function get_conditional_logic_fields()
    {
        $form = $this->get_current_form();
        $fields = array();
        foreach ($form['fields'] as $field) {
            if ($field->is_conditional_logic_supported()) {
                $inputs = $field->get_entry_inputs();
                if ($inputs) {
                    $choices = array();
                    foreach ($inputs as $input) {
                        if (rgar($input, 'isHidden')) {
                            continue;
                        }
                        $choices[] = array('value' => $input['id'], 'label' => GFCommon::get_label($field, $input['id'], true));
                    }
                    if (!empty($choices)) {
                        $fields[] = array('choices' => $choices, 'label' => GFCommon::get_label($field));
                    }
                } else {
                    $fields[] = array('value' => $field->id, 'label' => GFCommon::get_label($field));
                }
            }
        }
        return $fields;
    }

    /**
     * Evaluate the conditional logic.
     *
     * @param array $form The form currently being processed.
     * @param array $entry The entry currently being processed.
     *
     * @return bool
     */
    public function is_custom_logic_met($form, $entry)
    {
        if ($this->is_gravityforms_supported('2.5.16')) {
            return $this->is_cashnet_condition_met('custom_logic', $form, $entry);
        }
        return true;
    }

    // # HELPERS -------------------------------------------------------------------------------------------------------

    /**
     * The feedback callback
     *
     * @param string $value The setting value.
     *
     * @return bool
     */
    public function is_valid_setting($value)
    {
        return strlen($value) < 10;
    }

    public function is_callback_valid()
    {

        if (rgget('callback') != $this->_slug) {
            return false;
        }

        if (empty($_POST)) {
            return false;
        }

        return true;
    }

    public function maybe_process_callback()
    {

        // ignoring requests that are not this addon's callbacks
        if (!$this->is_callback_valid()) {
            return;
        }

        // returns either false or an array of data about the callback request which payment add-on will then use
        // to generically process the callback data

        $callback_action = $this->callback();

        $result = false;

        if (is_wp_error($callback_action)) {
            $this->display_callback_error($callback_action);
        } else if ($callback_action && is_array($callback_action) && rgar($callback_action, 'type') && !rgar($callback_action, 'abort_callback')) {

            $result = $this->process_callback_action($callback_action);

            if (is_wp_error($result)) {
                $this->display_callback_error($result);
            } else if (!$result) {
                status_header(200);
                $this->log_debug("maybe_process_callback(): Callback could not be processed");
            } else {
                status_header(200);
                $this->log_debug("maybe_process_callback(): Callback processed successfully");
            }
        } else {
            status_header(200);
            $this->log_debug("maybe_process_callback(): Callback bypassed");
        }

        $entryId = $callback_action['entry_id'];
        $entry = GFAPI::get_entry($entryId);

        if (is_wp_error($entry)) {
            return;
        }

        if (!class_exists('GFFormDisplay')) {
            // phpcs:ignore
            require_once GFCommon::get_base_path() . '/form_display.php';
        }

        $lead_id = rgar($entry, 'id');
        $form = GFAPI::get_form(rgar($entry, 'form_id'));
        $feeds = GFAPI::get_feeds(null, $form['id']);
        $feed = $feeds[0];

        if ($callback_action['success'] == 0) {
            $this->log_debug("maybe_process_callback(): success == 0");
            if (rgar($feed['meta'], 'successful_url') > '') {
                $this->log_debug("maybe_process_callback(): feed successful_url");
                wp_redirect(rgar($feed['meta'], 'successful_url'));
            } else {
                $this->log_debug("maybe_process_callback(): plug-in successful_url");
                wp_redirect($this->get_plugin_setting('successful_url'));
            }
        }
        elseif (rgar($feed['meta'], 'unsuccessful_url') > '') {
            $this->log_debug("maybe_process_callback(): feed unsuccessful_url");
            wp_redirect(rgar($feed['meta'], 'unsuccessful_url'));
        } elseif ($this->get_plugin_setting('unsuccessful_url') > '') {
            $this->log_debug("maybe_process_callback(): plug-in unsuccessful_url");
            wp_redirect($this->get_plugin_setting('unsuccessful_url'));
        } elseif (rgar($feed['meta'], 'successful_url') > '') {
            $this->log_debug("maybe_process_callback(): feed not unsuccessful_url");
            wp_redirect(rgar($feed['meta'], 'successful_url'));
        } else {
            $this->log_debug("maybe_process_callback(): plug-in not unsuccessful_url");
            wp_redirect($this->get_plugin_setting('successful_url'));
        }

        //$result = $this->post_callback($callback_action, $result);
        //echo $result;

        die();
    }

    public function display_callback_error($error)
    {

        $data = $error->get_error_data();
        $status = !rgempty("status_header", $data) ? $data["status_header"] : 200;

        status_header($status);
        echo $error->get_error_message();
    }

    /**
     * Processes callback based on provided data.
     *
     * $action = array(
     *     'type' => 'cancel_subscription',     // required
     *     'transaction_id' => '',              // required (if payment)
     *     'subscription_id' => '',             // required (if subscription)
     *     'amount' => '0.00',                  // required (some exceptions)
     *     'entry_id' => 1,                     // required (some exceptions)
     *     'transaction_type' => '',
     *     'payment_status' => '',
     *     'note' => ''
     * );
     *
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function process_callback_action($action)
    {
        $this->log_debug("Processing callback action");
        $action = wp_parse_args($action, array(
            'type' => false,
            'amount' => false,
            'transaction_type' => false,
            'transaction_id' => false,
            'subscription_id' => false,
            'entry_id' => false,
            'payment_status' => false,
            'note' => false
        ));

        $result = false;

        if (rgar($action, "id") && $this->is_duplicate_callback($action["id"])) {
            $this->log_debug("is duplicate " . $action["id"]);
            return new WP_Error("duplicate", sprintf(__("This webhook has already been processed (Event Id: %s)", "gravityforms"), $action["id"]));
        }

        $entry = GFAPI::get_entry($action['entry_id']);
        if (!$entry || is_wp_error($entry)) {
            $this->log_debug("is_wp_error " . $action['entry_id']);
            return $result;
        }

        //$action = do_action("gform_action_pre_payment_callback", $action, $entry);
        do_action("gform_action_pre_payment_callback", $action, $entry);

        switch ($action['transaction_type']) {
            case 'complete_payment':
                $result = $this->complete_payment($entry, $action);
                break;
            case 'refund_payment':
                $result = $this->refund_payment($entry, $action);
                break;
            case 'fail_payment':
                $result = $this->fail_payment($entry, $action);
                break;
            case 'add_pending_payment':
                $result = $this->add_pending_payment($entry, $action);
                break;
            case 'void_authorization':
                $result = $this->void_authorization($entry, $action);
                break;
            default:
                // handle custom events
                if (is_callable(array($this, rgar($action, 'callback')))) {
                    $result = call_user_func_array(array($this, $action['callback']), array($entry, $action));
                }
                break;
        }

        if (rgar($action, "id") && $result) {
            $this->register_callback($action["id"], $action['entry_id']);
        }

        do_action("gform_post_payment_callback", $entry, $action, $result);

        return $result;
    }

    public function register_callback($callback_id, $entry_id)
    {
        global $wpdb;

        $wpdb->insert("{$wpdb->prefix}gf_addon_payment_callback", array("addon_slug" => $this->_slug, "callback_id" => $callback_id, "lead_id" => $entry_id, "date_created" => gmdate("Y-m-d H:i:s")));
    }

    public function is_duplicate_callback($callback_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}gf_addon_payment_callback WHERE addon_slug=%s AND callback_id=%s", $this->_slug, $callback_id);
        if ($wpdb->get_var($sql))
            return true;

        return false;
    }

    public function callback()
    {
        $input = $_POST;
        // Prepare transaction ID and validation code variables if present
        $action = array();
        $action[(string)$input['ref1type1']] = $input['ref1val1'];
        $action[(string)$input['ref2type1']] = $input['ref2val1'];
        $action['success'] = false;

        // Ensure a transaction ID and validation code are present in the received data
        if (isset($action['TRANSID']) && isset($action['VALCODE'])) {
            // Prepare input variables
            $action['entry_id'] = $action['TRANSID'];
            $action['id'] = $action['TRANSID'];
            $action['amount'] = floatval($input['amount1']);
            $action['success'] = intval($input['result']);
            $action['tx'] = intval($input['tx'] ?? $input['failedtx']);
            $action['transaction_id'] = $input['tx'] ?? $action['VALCODE'];
            $action['valCode'] = preg_replace('/[^0-9a-z]/i', '', $action['VALCODE']);
            $action['note'] = '';
            $action['payment_status'] = '';
            $action['payment_method'] = 'cashnet';
            $action['type'] = intval($input['result']);
            $action['respmessage'] = $input['respmessage'] ?? '';
            $action['merchant'] = $input["merchant"];
            $action['custcode'] = $input['custcode'];
            $action['itemcnt'] = $input['itemcnt'];
            $action['lname'] = $input['lname'];
            $action['itemcode1'] = $input['itemcode1'];
            $action['qty1'] = $input['qty1'];
            $action['ccerrorcode'] = $input['ccerrorcode'] ?? '';
            $action['ccerrormessage'] = $input['ccerrormessage'] ?? '';
            if ($action['success'] == 0) {
                $action['transaction_type'] = 'complete_payment';
            } else {
                $action['transaction_type'] = 'fail_payment';
            }
            GFCommon::log_debug('callback: action => ' . print_r($action, true));
        }
        return $action;
    }

    public function post_callback($callback_action, $result)
    {
        /**
         * $entryId = $callback_action['transaction_id'];
         * $rawEntry = GFAPI::get_entry($entryId);
         * if (is_wp_error($rawEntry)) {
         * return;
         * }
         * $entry = new Entry($rawEntry);
         * if (! class_exists('GFFormDisplay')) {
         * // phpcs:ignore
         * require_once GFCommon::get_base_path() . '/form_display.php';
         * }
         * $lead_id = rgar($entry, 'id');
         *
         * $form = GFAPI::get_form($entry->getFormId());
         *
         * $lead_id = rgar($entry, 'id');
         * $feed = $this->getFeed($lead_id);
         *
         * $form = \GFFormsModel::get_form_meta($entry['form_id']);
         *
         *
         * $confirmation = GFFormDisplay::handle_confirmation($form, $entry->toArray(), false);
         *
         * \GFFormDisplay::$submission[$form['id']] = [
         * 'is_confirmation'        => true,
         * 'confirmation_message'    => $confirmation,
         * 'form'                    => $form,
         * 'lead'                    => $lead,
         * ];
         *
         *
         * if (! class_exists('GFFormDisplay')) {
         * // phpcs:ignore
         * require_once GFCommon::get_base_path() . '/form_display.php';
         * }
         *
         * $default_confirmation = GFFormsModel::get_default_confirmation();
         * $result               = GFFormDisplay::get_ajax_postback_html( $default_confirmation['message'] );
         *
         * GFCommon::log_debug('post_callback result => ' . $result );
         *
         * return $result;
         **/
    }


    // # PAYMENT INTERACTION FUNCTIONS

    public function add_pending_payment($entry, $action)
    {

        $action['payment_status'] = 'Pending';

        if (!$action['note']) {
            $amount_formatted = GFCommon::to_money($action['amount'], $entry['currency']);
            $action['note'] = sprintf(__("Payment is pending. Amount: %s. Transaction Id: %s.", "gravityforms"), $amount_formatted, $action['tx']);
        }

        GFAPI::update_entry_property($entry['id'], 'payment_status', $action['payment_status']);
        $this->add_note($entry['id'], $action['note']);

        return true;
    }

    public function complete_payment(&$entry, $action)
    {

        if (!rgar($action, 'payment_status')) {
            $action['payment_status'] = 'Paid';
        }

        if (!rgar($action, 'transaction_type')) {
            $action['transaction_type'] = 'payment';
        }

        if (!rgar($action, 'payment_date')) {
            $action['payment_date'] = gmdate('y-m-d H:i:s');
        }

        //set is_fulfilled in process_capture by gateways that are not url redirects
        //url redirects should not have this set yet, happens in post_callback for them
        $entry['is_fulfilled'] = "1";
        $entry['transaction_id'] = rgar($action, 'transaction_id');
        $entry['transaction_type'] = rgar($action, 'transaction_type', "1");
        $entry['payment_status'] = $action['payment_status'];
        $entry['payment_amount'] = rgar($action, 'amount', 0);
        $entry['payment_date'] = $action['payment_date'];
        $entry['payment_method'] = rgar($action, 'payment_method');
        $entry['currency'] = 'USD';

        if (!rgar($action, 'note')) {
            $amount_formatted = GFCommon::to_money($action['amount'], $entry['currency']);
            $action['note'] = sprintf(__('Payment has been completed. Amount: %s. Transaction Id: %s.', 'gravityforms'), $amount_formatted, $action['tx']);
        }


        GFAPI::update_entry($entry);

        $this->insert_transaction($entry['id'], $action['transaction_type'], $action['tx'], $action['amount']);

        $this->add_note($entry['id'], $action['note'], "success");

        do_action("gform_post_payment_completed", $entry, $action);

        return true;
    }

    public function refund_payment($entry, $action)
    {
        if (!$action['payment_status'])
            $action['payment_status'] = 'Refunded';

        if (!$action['transaction_type'])
            $action['transaction_type'] = 'refund';

        if (!$action['note']) {
            $amount_formatted = GFCommon::to_money($action['amount'], $entry['currency']);
            $action['note'] = sprintf(__('Payment has been refunded. Amount: %s. Transaction Id: %s.', 'gravityforms'), $amount_formatted, $action['transaction_id']);
        }

        GFAPI::update_entry_property($entry['id'], 'payment_status', $action['payment_status']);
        $this->insert_transaction($entry['id'], $action['transaction_type'], $action['tx'], $action['amount']);
        $this->add_note($entry['id'], $action['note']);

        do_action("gform_post_payment_refunded", $entry, $action);

        return true;
    }

    public function fail_payment($entry, $action)
    {
        if (!$action['payment_status'])
            $action['payment_status'] = 'Failed';

        if (!$action['note']) {
            $amount_formatted = GFCommon::to_money($action['amount'], $entry['currency']);
            $msg = $action['respmessage'] ?? 'Payment has failed.';
            if ($action['ccerrormessage'] > '') {
                $action['note'] = $msg . sprintf(__('ccerror: %s %s Amount: %s Reason Code: %d.', 'gravityforms'), $action['ccerrormessage'], $action['ccerrorcode'], $amount_formatted, $action['success'] );
            } else {
                $action['note'] = $msg . sprintf(__('Amount: %s Reason Code: %d.', 'gravityforms'), $amount_formatted, $action['success'] );
            }
        }

        GFAPI::update_entry_property($entry['id'], 'payment_status', $action['payment_status']);
        $this->add_note($entry['id'], $action['note']);

        return true;
    }

    public function void_authorization($entry, $action)
    {
        if (!$action['payment_status'])
            $action['payment_status'] = 'Voided';

        if (!$action['note']) {
            $action['note'] = sprintf(__('Authorization has been voided. Transaction Id: %s', "gravityforms"), $action['tx']);
        }

        GFAPI::update_entry_property($entry['id'], 'payment_status', $action['payment_status']);
        $this->add_note($entry['id'], $action['note']);

        return true;
    }
}
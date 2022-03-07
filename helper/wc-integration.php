<?php
/**
 * Woocommerce integration helper.
 *
 * @package Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	return;
}

if ( is_plugin_active( 'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php' )
	|| is_plugin_active( 'woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php' )
	|| is_plugin_active( 'ast-tracking-per-order-items/ast-tracking-per-order-items.php' )
	|| is_plugin_active( 'aftership-woocommerce-tracking/aftership.php' )
	|| is_plugin_active( 'aftership-woocommerce-tracking/aftership-woocommerce-tracking.php' )
) {
	new SAShipmentIntegration();
}
/**
 * SAShipmentIntegration class
 */
class SAShipmentIntegration {

	/**
	 * Construct function
	 */
	public function __construct() {
		if ( is_plugin_active( 'ast-tracking-per-order-items/ast-tracking-per-order-items.php' ) ) {
			add_filter( 'sAlertDefaultSettings', __CLASS__ . '::add_default_setting', 1 );
			add_action( 'sa_addTabs', array( $this, 'add_tabs' ), 100 );

			$smsalert_ast_notify = smsalert_get_option( 'ast_notify', 'smsalert_ast_general', 'on' );

			if ( 'on' === $smsalert_ast_notify ) {
				add_action( 'send_order_to_trackship', array( $this, 'trigger_order_trackship' ), 10, 1 );
			}
		}

		add_filter( 'sa_wc_order_sms_customer_before_send', array( $this, 'replace_aftership_trackingno' ), 10, 2 );
		add_filter( 'sa_wc_order_sms_customer_before_send', array( $this, 'replace_wcshipment_trackingno' ), 10, 2 );
		add_filter( 'sa_wc_order_sms_customer_before_send', array( $this, 'replace_wc_advshipment_trackingno' ), 10, 2 );
		add_filter( 'sa_wc_order_sms_admin_before_send', array( $this, 'replace_wc_advshipment_trackingno' ), 10, 2 );

		add_filter( 'sa_wc_variables', array( $this, 'add_tokens_wc_templates' ), 10, 2 );
	}

	/**
	 * Add tokens wc templates function.
	 *
	 * @param array  $variables variables.
	 * @param string $status    status.
	 *
	 * @return array
	 */
	public function add_tokens_wc_templates( $variables, $status ) {
		if ( is_plugin_active( 'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php' )
			|| is_plugin_active( 'woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php' )
		) {
			$wc_shipment_variables = array(
				'[tracking_number]'   => 'Tracking Number',
				'[tracking_provider]' => 'Tracking Provider',
				'[tracking_link]'     => 'Tracking Link',
			);
			$variables             = array_merge( $variables, $wc_shipment_variables );
		}

		if ( is_plugin_active( 'aftership-woocommerce-tracking/aftership.php' )
			|| is_plugin_active( 'aftership-woocommerce-tracking/aftership-woocommerce-tracking.php' )
		) {
			$wc_shipment_variables = array(
				'[aftership_tracking_number]'        => 'afshp tracking number',
				'[aftership_tracking_provider_name]' => 'afshp tracking provider',
				'[aftership_tracking_url]'           => 'afshp tracking link',
			);
			$variables             = array_merge( $variables, $wc_shipment_variables );

		}
		return $variables;
	}

	/**
	 * Replace wc shipment tracking number function.
	 *
	 * @param array $sms_data sms_data.
	 * @param int   $order_id order_id.
	 *
	 * @return array
	 */
	public function replace_wcshipment_trackingno( $sms_data, $order_id ) {
		if ( is_plugin_active( 'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php' ) ) {
			$content = ( ! empty( $sms_data['sms_body'] ) ) ? $sms_data['sms_body'] : '';
			if ( ( strpos( $content, '[tracking_number]' ) !== false ) || ( strpos( $content, '[tracking_provider]' ) !== false ) || ( strpos( $content, '[tracking_link]' ) !== false ) ) {

				$tracking_info = get_post_meta( $order_id, '_wc_shipment_tracking_items', true );
				if ( count( $tracking_info ) > 0 ) {
					$t_info  = array_shift( $tracking_info );
					$find    = array( '[tracking_number]', '[tracking_provider]', '[tracking_link]' );
					$replace = array(
						$t_info['tracking_number'],
						( ( '' !== $t_info['tracking_provider'] ) ? $t_info['tracking_provider'] : $t_info['custom_tracking_provider'] ),
						$t_info['custom_tracking_link'],
					);

					$sms_data['sms_body'] = str_replace( $find, $replace, $content );

				}
			}
		}
		return $sms_data;
	}

	/**
	 * Replace wc shipment advance tracking no function.
	 *
	 * @param array $sms_data sms_data.
	 * @param int   $order_id order_id.
	 *
	 * @return array
	 */
	public function replace_wc_advshipment_trackingno( $sms_data, $order_id ) {
		if ( is_plugin_active( 'woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php' ) ) {
			$content = ( ! empty( $sms_data['sms_body'] ) ) ? $sms_data['sms_body'] : '';

			$date_format 		= '';
			$date_shipped       = '[date_shipped]';
			if ( preg_match_all( '/\[date_shipped.*?\]/', $content, $matched ) ) {
				$date_format    = 'F j, Y';
				$date_shipped   = $matched[0][0];
				$date_params 	= SmsAlertUtility::parseAttributesFromTag( $date_shipped );
				$date_format 	= array_key_exists( 'format', $date_params ) ? $date_params['format'] : 'F j, Y';
			}

			if ( ( strpos( $content, '[tracking_number]' ) !== false ) || ( strpos( $content, '[tracking_provider]' ) !== false ) || ( strpos( $content, '[tracking_link]' ) !== false ) || (! empty ( $date_format )) ) {
				$ast            = new WC_Advanced_Shipment_Tracking_Actions();
				$tracking_items = $ast->get_tracking_items( $order_id, true );

				if ( count( $tracking_items ) > 0 ) {
					$t_info  = end( $tracking_items );

					$find    = array(
						'[tracking_number]',
						'[tracking_provider]',
						'[tracking_link]',
						$date_shipped,
					);
					$replace = array(
						$t_info['tracking_number'],
						$t_info['formatted_tracking_provider'],
						$t_info['formatted_tracking_link'],
						//wp_date( $date_format, $t_info['date_shipped']),
						
						date_i18n( $date_format, $t_info['date_shipped'] )
					);

					$sms_data['sms_body'] = str_replace( $find, $replace, $content );
				}
			}
		}
		return $sms_data;
	}

	/**
	 * Replace aftership tracking no function.
	 *
	 * @param array $sms_data sms_data.
	 * @param int   $order_id order_id.
	 *
	 * @return array
	 */
	public function replace_aftership_trackingno( $sms_data, $order_id ) {
		if ( is_plugin_active( 'aftership-woocommerce-tracking/aftership.php' )
			|| is_plugin_active( 'aftership-woocommerce-tracking/aftership-woocommerce-tracking.php' )
		) {
			$content = ( ! empty( $sms_data['sms_body'] ) ) ? $sms_data['sms_body'] : '';
			if ( ( strpos( $content, '[aftership_tracking_number]' ) !== false ) || ( strpos( $content, '[aftership_tracking_provider_name]' ) !== false ) || ( strpos( $content, '[aftership_tracking_url]' ) !== false ) ) {
				$find = array(
					'[aftership_tracking_number]',
					'[aftership_tracking_provider_name]',
					'[aftership_tracking_url]',
				);

				$datas        = current( get_post_meta( $order_id, '_aftership_tracking_items', true ) );
				$tracking_no  = ( ! empty( $datas['tracking_number'] ) ) ? $datas['tracking_number'] : get_post_meta( $order_id, '_aftership_tracking_number', true );
				$courier_name = ( ! empty( $datas['slug'] ) ) ? $datas['slug'] : get_post_meta( $order_id, '_aftership_tracking_provider_name', true );

				$after_ship_url = '';
				if ( ! empty( $datas ) && class_exists( 'AfterShip_Actions' ) ) {
					$after_ship_actions = new AfterShip_Actions();
					$after_ship_url     = $after_ship_actions->generate_tracking_page_link( $datas );
				}

				$replace = array(
					$tracking_no,
					$courier_name,
					$after_ship_url,
				);

				$sms_data['sms_body'] = str_replace( $find, $replace, $content );
			}
		}
		return $sms_data;
	}

	/**
	 * Add default settings to savesetting in setting-options.
	 *
	 * @param array $defaults defaults.
	 *
	 * @return array
	 */
	public static function add_default_setting( $defaults = array() ) {
		$defaults['smsalert_ast_general']['ast_notify'] = 'off';
		$defaults['smsalert_ast_message']['ast_notify'] = '';
		return $defaults;
	}

	/**
	 * Add tabs to smsalert settings at backend.
	 *
	 * @param array $tabs tabs.
	 *
	 * @return array
	 */
	public static function add_tabs( $tabs = array() ) {
		$ast_addon__param = array(
			'checkTemplateFor' => 'ast_addon',
			'templates'        => self::get_ast_addon_templates(),
		);

		$tabs['woocommerce']['inner_nav']['ast_addon']['title']       = 'AST Tracking Per Item';
		$tabs['woocommerce']['inner_nav']['ast_addon']['tab_section'] = 'astaddontemplates';
		$tabs['woocommerce']['inner_nav']['ast_addon']['tabContent']  = $ast_addon__param;
		$tabs['woocommerce']['inner_nav']['ast_addon']['filePath']    = 'views/message-template.php';
		return $tabs;
	}

	/**
	 * Get ast addon templates function.
	 *
	 * @return array
	 */
	public static function get_ast_addon_templates() {
		$current_val       = smsalert_get_option( 'ast_notify', 'smsalert_ast_general', 'on' );
		$checkbox_name_id  = 'smsalert_ast_general[ast_notify]';
		$text_area_name_id = 'smsalert_ast_message[ast_notify]';

		$text_body = smsalert_get_option( 'ast_notify', 'smsalert_ast_message', sprintf( 'Shipped: %1$s has been dispatched via %2$s with tracking number %3$s. Track here %4$s', '[item_name_qty]', '[tracking_provider]', '[tracking_number]', '[tracking_link]' ) );

		$templates = array();

		$variables = WooCommerceCheckOutForm::getvariables();

		$templates['ast-addon']['title']          = 'When Tracking Information is added';
		$templates['ast-addon']['enabled']        = $current_val;
		$templates['ast-addon']['status']         = '';
		$templates['ast-addon']['text-body']      = $text_body;
		$templates['ast-addon']['checkboxNameId'] = $checkbox_name_id;
		$templates['ast-addon']['textareaNameId'] = $text_area_name_id;
		$templates['ast-addon']['moreoption']     = 1;
		$templates['ast-addon']['token']          = $variables;

		return $templates;
	}

	/**
	 * Trigger order trackship function.
	 *
	 * @param int $order_id order_id.
	 */
	public function trigger_order_trackship( $order_id ) {

		$order       = new WC_Order( $order_id );
		$ast_message = smsalert_get_option( 'ast_notify', 'smsalert_ast_message', '' );

		$order_items = $order->get_items();
		$first_item  = current( $order_items );
		$post_id     = $first_item['order_id'];
		$buyer_no    = get_post_meta( $post_id, '_billing_phone', true );

		$buyer_sms_data['number']   = $buyer_no;
		$buyer_sms_data['sms_body'] = $ast_message;
		$buyer_sms_data             = WooCommerceCheckOutForm::pharse_sms_body( $buyer_sms_data, $order_id );
		do_action( 'sa_send_sms', $buyer_no, $this->parse_sms_body( $buyer_sms_data['sms_body'], $order, $order_id ) );
	}

	/**
	 * Parse sms body function.
	 *
	 * @param string $content  content.
	 * @param object $order    object.
	 * @param int    $order_id order_id.
	 *
	 * @return string
	 */
	public function parse_sms_body( $content, $order, $order_id ) {
		$ast            = new WC_Advanced_Shipment_Tracking_Actions();
		$tracking_items = $ast->get_tracking_items( $order_id, true );

		if ( count( $tracking_items ) > 0 ) {
			$t_info = end( $tracking_items );
			if ( array_key_exists( 'products_list', $t_info ) ) {
				$item_with_qty = array();
				$item_name     = array();
				foreach ( $t_info['products_list'] as $pdata ) {
					$item_with_qty[] = get_the_title( $pdata->product ) . ' [' . $pdata->qty . '] ';
					$item_name[]     = get_the_title( $pdata->product );
				}
				$item_with_qty = implode( ',', $item_with_qty );
				$item_name     = implode( ',', $item_name );
			}
		}

		$find = array(
			'[item_name]',
			'[item_name_qty]',
		);

		$replace = array(
			$item_name,
			$item_with_qty,
		);

		$content                    = str_replace( $find, $replace, $content );
		$buyer_sms_data['sms_body'] = $content;
		$buyer_sms_data             = $this->replace_wc_advshipment_trackingno( $buyer_sms_data, $order_id );
		$content                    = ( ( ! empty( $buyer_sms_data['sms_body'] ) ) ? $buyer_sms_data['sms_body'] : '' );
		return $content;
	}
}

/**
* SAWCInvoicePdf
*/
if ( is_plugin_active( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php' ) ) {
	new SAWCInvoicePdf();
}
/**
 * SAWCInvoicePdf class
 */
class SAWCInvoicePdf {

	/**
	 * Construct function
	 */
	public function __construct() {
		add_filter( 'sa_wc_order_sms_customer_before_send', array( $this, 'replace_token_wc_templates' ), 10, 2 );
		add_filter( 'sa_wc_variables', array( $this, 'add_tokens_wc_templates' ), 10, 2 );
	}

	/**
	 * Add tokens wc templates function.
	 *
	 * @param array  $variables variables.
	 * @param string $status    status.
	 *
	 * @return array
	 */
	public function add_tokens_wc_templates( $variables, $status ) {
		if ( is_plugin_active( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php' ) ) {
			$variables = array_merge(
				$variables,
				array(
					'[pdf_invoice_link]' => 'pdf invoice link',
				)
			);
		}
		return $variables;
	}

	/**
	 * Replace token wc templates function.
	 *
	 * @param array $sms_data sms_data.
	 * @param int   $order_id order_id.
	 *
	 * @return array
	 */
	public function replace_token_wc_templates( $sms_data, $order_id ) {
		if ( is_plugin_active( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php' ) ) {
			$order                = new WC_Order( $order_id );
			$sms_data['sms_body'] = str_replace( '[pdf_invoice_link]', admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&document_type=invoice&order_ids=' . $order_id . '&order_key=' . $order->get_order_key() ), $sms_data['sms_body'] );
		}
		return $sms_data;
	}
}

/**
* SAWCOrderDeliveryDt
*/
if ( is_plugin_active( 'order-delivery-date-for-woocommerce/order_delivery_date.php' ) ) {
	new SAWCOrderDeliveryDt();
}
/**
 * SAWCOrderDeliveryDt class
 */
class SAWCOrderDeliveryDt {

	/**
	 * Construct function
	 */
	public function __construct() {
		add_filter( 'sa_wc_order_sms_customer_before_send', array( $this, 'replace_token_wc_templates' ), 10, 2 );
		add_filter( 'sa_wc_variables', array( $this, 'add_tokens_wc_templates' ), 10, 2 );
	}

	/**
	 * Add tokens wc templates function.
	 *
	 * @param array  $variables variables.
	 * @param string $status    status.
	 *
	 * @return array
	 */
	public function add_tokens_wc_templates( $variables, $status ) {
		if ( is_plugin_active( 'order-delivery-date-for-woocommerce/order_delivery_date.php' ) ) {
			$variables = array_merge(
				$variables,
				array(
					'[orddd_lite_timestamp]' => 'Delivery Date',
				)
			);
		}
		return $variables;
	}

	/**
	 * Replace token wc templates function.
	 *
	 * @param array $sms_data sms_data.
	 * @param int   $order_id order_id.
	 *
	 * @return array
	 */
	public function replace_token_wc_templates( $sms_data, $order_id ) {
		if ( is_plugin_active( 'order-delivery-date-for-woocommerce/order_delivery_date.php' ) ) {
			$sms_data['sms_body'] = str_replace( '[orddd_lite_timestamp]', Orddd_Lite_Common::orddd_lite_get_order_delivery_date( $order_id ), $sms_data['sms_body'] );
		}
		return $sms_data;
	}
}

	/*******
* SAWCSerialNos
*/
if ( is_plugin_active( 'wc-serial-numbers/wc-serial-numbers.php' ) ) {
	new SAWCSerialNos();
}
/**
 * SAWCSerialNos class
 */
class SAWCSerialNos {

	/**
	 * Construct function
	 */
	public function __construct() {
		add_filter( 'sa_wc_order_sms_customer_before_send', array( $this, 'replace_token_wc_templates' ), 10, 2 );
		add_filter( 'sa_wc_variables', array( $this, 'add_tokens_wc_templates' ), 10, 2 );
	}

	/**
	 * Add tokens wc templates function.
	 *
	 * @param array  $variables variables.
	 * @param string $status    status.
	 *
	 * @return array
	 */
	public function add_tokens_wc_templates( $variables, $status ) {
		if ( is_plugin_active( 'wc-serial-numbers/wc-serial-numbers.php' ) ) {
			$variables = array_merge(
				$variables,
				array(
					'[wc_serial_no]' => 'WC Serial No.',
				)
			);
		}
		return $variables;
	}

	/**
	 * Replace token wc templates function.
	 *
	 * @param array $sms_data sms_data.
	 * @param int   $order_id order_id.
	 *
	 * @return array
	 */
	public function replace_token_wc_templates( $sms_data, $order_id ) {
		if ( is_plugin_active( 'wc-serial-numbers/wc-serial-numbers.php' ) ) {
			$order         = new WC_Order( $order_id );
			$wc_serial_nos = array();

			$serial_numbers = WC_Serial_Numbers_Query::init()->from( 'serial_numbers' )->where( 'order_id', intval( $order->get_id() ) )->get();
			foreach ( $serial_numbers as $serial_number ) {
				$wc_serial_nos[] = wc_serial_numbers_decrypt_key( $serial_number->serial_key );
			}

			$sms_data['sms_body'] = str_replace( '[wc_serial_no]', implode( ',', $wc_serial_nos ), $sms_data['sms_body'] );
		}
		return $sms_data;
	}
}

/**
* SAWCAuctions
*/
if ( is_plugin_active( 'woocommerce-simple-auctions/woocommerce-simple-auctions.php' ) ) {
	new SAWCAuctions();
}
/**
 * SAWCAuctions class
 */
class SAWCAuctions {

	/**
	 * Construct function
	 */
	public function __construct() {
		// add_filter('sa_wc_order_sms_customer_before_send', array( $this, 'replace_token_wc_templates' ), 10, 2);
		add_action( 'woocommerce_simple_auctions_outbid', array( $this, 'send_sms_outbidder' ), 10, 1 );
		add_action( 'woocommerce_simple_auctions_place_bid', array( $this, 'send_sms_bidder' ), 10, 1 );
		add_action( 'woocommerce_simple_auctions_place_bid', array( $this, 'send_admin_sms_on_placebid' ), 10, 1 );
		add_filter( 'sAlertDefaultSettings', __CLASS__ . '::add_default_setting', 1 );
		add_action( 'sa_addTabs', array( $this, 'add_tabs' ), 100 );
	}

	/**
	 * Add default settings to savesetting in setting-options.
	 *
	 * @param array $defaults defaults.
	 *
	 * @return array
	 */
	public function add_default_setting( $defaults = array() ) {
		$defaults['smsalert_wcauction_general']['wcauction_admin_notification_new']          = 'off';
		$defaults['smsalert_wcauction_general']['wcauction_bidder_notification_outbid']      = 'off';
		$defaults['smsalert_wcauction_general']['wcauction_bidder_notification_customerbid'] = 'off';
		$defaults['smsalert_wcauction_message']['wcauction_admin_sms_body_new']              = '';
		$defaults['smsalert_wcauction_message']['wcauction_sms_body_outbid']                 = '';
		$defaults['smsalert_wcauction_message']['wcauction_sms_body_customerbid']            = '';
		return $defaults;

	}

	/**
	 * Add tabs to smsalert settings at backend.
	 *
	 * @param array $tabs tabs.
	 *
	 * @return array
	 */
	public static function add_tabs( $tabs = array() ) {
		$customer_param = array(
			'checkTemplateFor' => 'sa_wc_auction',
			'templates'        => self::get_customer_templates(),
		);

		$admin_param = array(
			'checkTemplateFor' => 'sa_wc_auction_admin',
			'templates'        => self::get_admin_templates(),
		);

		$tabs['sa_wc_auction']['nav']  = 'Woo Product Auction';
		$tabs['sa_wc_auction']['icon'] = 'dashicons-admin-users';

		$tabs['sa_wc_auction']['inner_nav']['wc_auction_customer']['title']        = 'Customer Notifications';
		$tabs['sa_wc_auction']['inner_nav']['wc_auction_customer']['tab_section']  = 'wcauctioncsttemplates';
		$tabs['sa_wc_auction']['inner_nav']['wc_auction_customer']['first_active'] = true;
		$tabs['sa_wc_auction']['inner_nav']['wc_auction_customer']['tabContent']   = $customer_param;
		$tabs['sa_wc_auction']['inner_nav']['wc_auction_customer']['filePath']     = 'views/message-template.php';

		$tabs['sa_wc_auction']['inner_nav']['wc_auction_admin']['title']       = 'Admin Notifications';
		$tabs['sa_wc_auction']['inner_nav']['wc_auction_admin']['tab_section'] = 'wcauctionadmintemplates';
		$tabs['sa_wc_auction']['inner_nav']['wc_auction_admin']['tabContent']  = $admin_param;
		$tabs['sa_wc_auction']['inner_nav']['wc_auction_admin']['filePath']    = 'views/message-template.php';
		return $tabs;
	}

	/**
	 * Get variables function.
	 *
	 * @return array
	 */
	public static function getvariables() {
		$variables = array(
			'[auction_id]'   => 'Auction Id',
			'[store_name]'   => 'Store Name',
			'[first_name]'   => 'First Name',
			'[last_name]'    => 'Last Name',
			'[auction_name]' => 'Auction Name',
			'[auction_bid]'  => 'Auction bid',
			'[auction_link]' => 'Auction link',
		);
		return $variables;
	}

	/**
	 * Get customer templates function.
	 *
	 * @return array
	 */
	public static function get_customer_templates() {
		$templates                             = array();
		$templates['outbid']['title']          = 'Send SMS to Outbidder';
		$templates['outbid']['enabled']        = smsalert_get_option( 'wcauction_bidder_notification_outbid', 'smsalert_wcauction_general', 'on' );
		$templates['outbid']['status']         = 'outbid';
		$templates['outbid']['text-body']      = smsalert_get_option( 'wcauction_sms_body_outbid', 'smsalert_wcauction_message', sprintf( 'Hello %1$s, a new bid for auction %2$s has just been submitted. The new bid is: %3$s. Please visit the auction %4$s', '[first_name]', '[auction_name]', '[auction_bid]', '[auction_link]' ) );
		$templates['outbid']['checkboxNameId'] = 'smsalert_wcauction_general[wcauction_bidder_notification_outbid]';
		$templates['outbid']['textareaNameId'] = 'smsalert_wcauction_message[wcauction_sms_body_outbid]';
		$templates['outbid']['token']          = self::getvariables();
		/*Send SMS to Bidder*/
		$templates['customerbid']['title']   = 'Send SMS to Bidder';
		$templates['customerbid']['enabled'] = smsalert_get_option( 'wcauction_bidder_notification_customerbid', 'smsalert_wcauction_general', 'on' );
		$templates['customerbid']['status']  = 'customerbid';

		$templates['customerbid']['text-body']      = smsalert_get_option( 'wcauction_sms_body_customerbid', 'smsalert_wcauction_message', sprintf( 'Hello %1$s, Thank You for placing bid for %2$s. Your bid is %3$s. Please visit the auction %4$s', '[first_name]', '[auction_name]', '[auction_bid]', '[auction_link]' ) );
		$templates['customerbid']['checkboxNameId'] = 'smsalert_wcauction_general[wcauction_bidder_notification_customerbid]';
		$templates['customerbid']['textareaNameId'] = 'smsalert_wcauction_message[wcauction_sms_body_customerbid]';
		$templates['customerbid']['token']          = self::getvariables();

		return $templates;
	}

	/**
	 * Get admin template function.
	 *
	 * @return array
	 */
	public static function get_admin_templates() {
		$templates   = array();
		$ks          = 'new';
		$current_val = smsalert_get_option( 'wcauction_admin_notification_new', 'smsalert_wcauction_general', 'on' );

		$checkbox_name_id  = 'smsalert_wcauction_general[wcauction_admin_notification_new]';
		$text_area_name_id = 'smsalert_wcauction_message[wcauction_admin_sms_body_new]';

		$text_body = smsalert_get_option( 'wcauction_admin_sms_body_new', 'smsalert_wcauction_message', sprintf( '%1$s a new bid for auction %2$s has been submitted by %3$s. The new bid is: %4$s. Please visit the auction %5$s', '[store_name]:', '[auction_name]', '[first_name]', '[auction_bid]', '[auction_link]' ) );

		$templates[ $ks ]['title']          = 'When Auction is new';
		$templates[ $ks ]['enabled']        = $current_val;
		$templates[ $ks ]['status']         = $ks;
		$templates[ $ks ]['text-body']      = $text_body;
		$templates[ $ks ]['checkboxNameId'] = $checkbox_name_id;
		$templates[ $ks ]['textareaNameId'] = $text_area_name_id;
		$templates[ $ks ]['token']          = self::getvariables();
		return $templates;
	}

	/**
	 * Send sms function.
	 *
	 * @param array $datas data.
	 *
	 * @retunr void
	 */
	public static function send_sms_outbidder( $datas = array() ) {
		$outbid  = smsalert_get_option( 'wcauction_bidder_notification_outbid', 'smsalert_wcauction_general' );
		$message = smsalert_get_option( 'wcauction_sms_body_outbid', 'smsalert_wcauction_message' );

		if ( 'on' === $outbid && '' !== $message ) {
			$product_id        = $datas['product_id'];
			$product_data      = wc_get_product( $product_id );
			$outbiddeduser_id  = $datas['outbiddeduser_id'];
			$current_bidder_id = $product_data->get_auction_current_bider();

			if ( $outbiddeduser_id === $current_bidder_id ) {
				return;
			}

			$outbider_phone = get_user_meta( $outbiddeduser_id, 'billing_phone', true );
			do_action( 'sa_send_sms', $outbider_phone, $this->replace_token_wc_templates( $message, $product_id, $outbiddeduser_id ) );
		}
	}

	/**
	 * Send sms function.
	 *
	 * @param array $datas data.
	 *
	 * @return void
	 */
	public static function send_sms_bidder( $datas = array() ) {
		$customerbid = smsalert_get_option( 'wcauction_bidder_notification_customerbid', 'smsalert_wcauction_general' );
		$message     = smsalert_get_option( 'wcauction_sms_body_customerbid', 'smsalert_wcauction_message' );

		if ( 'on' === $customerbid && '' !== $message ) {
			$product_id        = $datas['product_id'];
			$product_data      = wc_get_product( $product_id );
			$current_bidder_id = $product_data->get_auction_current_bider();

			$cur_bidder_phone = get_user_meta( $current_bidder_id, 'billing_phone', true );
			do_action( 'sa_send_sms', $cur_bidder_phone, $this->replace_token_wc_templates( $message, $product_id, $current_bidder_id ) );
		}
	}

	/**
	 * Send admin sms function.
	 *
	 * @param array $datas data.
	 */
	public static function send_admin_sms_on_placebid( $datas = array() ) {
		$admin_outbid      = smsalert_get_option( 'wcauction_admin_notification_new', 'smsalert_wcauction_general' );
		$admin_sms_content = smsalert_get_option( 'wcauction_admin_sms_body_new', 'smsalert_wcauction_message' );

		$admin_phone_number = smsalert_get_option( 'sms_admin_phone', 'smsalert_message', '' );
		$admin_phone_number = str_replace( 'postauthor', 'post_author', $admin_phone_number );

		if ( 'on' === $admin_outbid && '' !== $admin_phone_number && '' !== $admin_sms_content ) {
			$admin_phone_number = str_replace( 'post_author', '', $admin_phone_number );
			$product_id         = $datas['product_id'];
			$product_data       = wc_get_product( $product_id );
			$current_bidder_id  = $product_data->get_auction_current_bider();
			do_action( 'sa_send_sms', $admin_phone_number, $this->replace_token_wc_templates( $admin_sms_content, $product_id, $current_bidder_id ) );
		}
	}

	/**
	 * Replace token wc templates function.
	 *
	 * @param string $message    message.
	 * @param int    $product_id product_id.
	 * @param int    $user_id    user_id.
	 *
	 * @return string
	 */
	public function replace_token_wc_templates( $message, $product_id, $user_id ) {
		$product_data = wc_get_product( $product_id );
		$first_name   = get_user_meta( $user_id, 'billing_first_name', true );
		$last_name    = get_user_meta( $user_id, 'billing_last_name', true );

		$replace = array(
			'[auction_id]'   => $product_id,
			'[first_name]'   => $first_name,
			'[last_name]'    => $last_name,
			'[auction_name]' => $product_data->get_title(),
			'[auction_bid]'  => $product_data->get_curent_bid(),
			'[auction_link]' => get_permalink( $product_id ),
		);

		$message = str_replace( array_keys( $this->getvariables() ), array_values( $replace ), $message );
		return $message;
	}
}

/**
* Dokan Plugin
*/
if ( is_plugin_active( 'dokan-lite/dokan.php' ) ) {
	new SAWCDokan();
}
/**
 * SAWCDokan class
 */
class SAWCDokan {

	/**
	 * Construct function
	 */
	public function __construct() {
		add_filter( 'sa_post_author_no', array( $this, 'set_vendor_phone_no' ), 10, 1 );
		add_action( 'dokan_vendor_enabled', array( $this, 'send_approved_sms_vendor' ), 10, 1 );
		add_action( 'dokan_vendor_disabled', array( $this, 'send_rejected_sms_vendor' ), 10, 1 );
	}

	/**
	 * Set vendor phone number function.
	 *
	 * @param int $product_id product_id.
	 *
	 * @return int
	 */
	public function set_vendor_phone_no( $product_id ) {
		$author_no = get_the_author_meta( 'billing_phone', get_post( $product_id )->post_author );
		if ( empty( $author_no ) ) {
			$dokan_profile = current( get_user_meta( get_post( $product_id )->post_author, 'dokan_profile_settings' ) );
			$author_no     = ( ! empty( $dokan_profile['phone'] ) ) ? $dokan_profile['phone'] : '';
		}
		return ( ! empty( $author_no ) ) ? $author_no : '';
	}

	/**
	 * Set approved sms vendor function.
	 *
	 * @param int $user_id user_id.
	 *
	 * @return void
	 */
	public function send_approved_sms_vendor( $user_id ) {
		$author_no = get_the_author_meta( 'billing_phone', $user_id );
		if ( empty( $author_no ) ) {
			$dokan_profile = current( get_user_meta( $user_id, 'dokan_profile_settings' ) );
			$author_no     = ( ! empty( $dokan_profile['phone'] ) ) ? $dokan_profile['phone'] : '';
		}

		$enabled = smsalert_get_option( 'multivendor_notification_approved', 'smsalert_general' );
		if ( 'on' === $enabled && ! empty( $author_no ) ) {
			$content = smsalert_get_option( 'multivendor_sms_body_approved', 'smsalert_message', SmsAlertMessages::showMessage( 'DEFAULT_NEW_USER_APPROVED' ) );
			do_action( 'sa_send_sms', $author_no, $this->parse_sms_body( $content, $user_id ) );
		}
	}

	/**
	 * Send rejected sms function.
	 *
	 * @param int $user_id user_id.
	 *
	 * @return void
	 */
	public function send_rejected_sms_vendor( $user_id ) {
		$author_no = get_the_author_meta( 'billing_phone', $user_id );
		if ( empty( $author_no ) ) {
			$dokan_profile = current( get_user_meta( $user_id, 'dokan_profile_settings' ) );
			$author_no     = ( ! empty( $dokan_profile['phone'] ) ) ? $dokan_profile['phone'] : '';
		}

		$enabled = smsalert_get_option( 'multivendor_notification_rejected', 'smsalert_general' );
		if ( 'on' === $enabled && ! empty( $author_no ) ) {
			$content = smsalert_get_option( 'multivendor_sms_body_rejected', 'smsalert_message', SmsAlertMessages::showMessage( 'DEFAULT_NEW_USER_REJECTED' ) );
			do_action( 'sa_send_sms', $author_no, $this->parse_sms_body( $content, $user_id ) );
		}
	}

	/**
	 * Parse sms body function.
	 *
	 * @param string $content content.
	 * @param int    $user_id user_id.
	 *
	 * @return string
	 */
	public function parse_sms_body( $content, $user_id ) {
		$find    = array(
			'[username]',
		);
		$replace = array(
			get_the_author_meta( 'display_name', $user_id ),
		);
		$content = str_replace( $find, $replace, $content );
		return $content;
	}
}

if ( is_plugin_active( 'dc-woocommerce-multi-vendor/dc_product_vendor.php' ) ) {
	new SAWCMP();
}
/**
 * SAWCMP class
 */
class SAWCMP {

	/**
	 * Construct function
	 */
	public function __construct() {
		add_filter( 'sa_post_author_no', array( $this, 'set_vendor_phone_no' ), 10, 1 );
		add_action( 'wp_ajax_activate_pending_vendor', array( $this, 'activate_pending_vendor' ) );
		add_action( 'wp_ajax_reject_pending_vendor', array( $this, 'reject_pending_vendor' ) );
		add_action( 'wp_ajax_wcmp_suspend_vendor', array( $this, 'wcmp_suspend_vendor' ) );
		add_action( 'wp_ajax_wcmp_activate_vendor', array( $this, 'wcmp_activate_vendor' ) );
	}

	/**
	 * Set vendor phone no function.
	 *
	 * @param int $product_id product_id.
	 *
	 * @return int
	 */
	public function set_vendor_phone_no( $product_id ) {
		$author_no = get_the_author_meta( 'billing_phone', get_post( $product_id )->post_author );
		return ( ! empty( $author_no ) ) ? $author_no : '';
	}

	/**
	 * Activate pending vendor function.
	 *
	 * @return void
	 */
	public function activate_pending_vendor() {
		$user_id   = filter_input( INPUT_POST, 'user_id' );
		$author_no = get_the_author_meta( 'billing_phone', $user_id );
		$enabled   = smsalert_get_option( 'multivendor_notification_approved', 'smsalert_general' );
		if ( 'on' === $enabled && ! empty( $author_no ) ) {
			$content = smsalert_get_option( 'multivendor_sms_body_approved', 'smsalert_message', SmsAlertMessages::showMessage( 'DEFAULT_NEW_USER_APPROVED' ) );
			do_action( 'sa_send_sms', $author_no, $this->parse_sms_body( $content, $user_id ) );
		}
	}

	/**
	 * Reject pending vendor function.
	 *
	 * @return void
	 */
	public function reject_pending_vendor() {
		$user_id   = filter_input( INPUT_POST, 'user_id' );
		$author_no = get_the_author_meta( 'billing_phone', $user_id );
		$enabled   = smsalert_get_option( 'multivendor_notification_rejected', 'smsalert_general' );
		if ( 'on' === $enabled && ! empty( $author_no ) ) {
			$content = smsalert_get_option( 'multivendor_sms_body_rejected', 'smsalert_message', SmsAlertMessages::showMessage( 'DEFAULT_NEW_USER_REJECTED' ) );
			do_action( 'sa_send_sms', $author_no, $this->parse_sms_body( $content, $user_id ) );
		}
	}

	/**
	 * Wcmp suspend vendor function.
	 *
	 * @return void
	 */
	public function wcmp_suspend_vendor() {
		$user_id   = filter_input( INPUT_POST, 'user_id' );
		$author_no = get_the_author_meta( 'billing_phone', $user_id );
		$enabled   = smsalert_get_option( 'multivendor_notification_rejected', 'smsalert_general' );
		if ( 'on' === $enabled && ! empty( $author_no ) ) {
			$content = smsalert_get_option( 'multivendor_sms_body_rejected', 'smsalert_message', SmsAlertMessages::showMessage( 'DEFAULT_NEW_USER_REJECTED' ) );
			do_action( 'sa_send_sms', $author_no, $this->parse_sms_body( $content, $user_id ) );
		}
	}

	/**
	 * Wcmp activate vendor function.
	 *
	 * @return void
	 */
	public function wcmp_activate_vendor() {
		$user_id   = filter_input( INPUT_POST, 'user_id' );
		$author_no = get_the_author_meta( 'billing_phone', $user_id );
		$username  = get_the_author_meta( 'display_name', $user_id );

		$enabled = smsalert_get_option( 'multivendor_notification_approved', 'smsalert_general' );
		if ( 'on' === $enabled && ! empty( $author_no ) ) {
			$content = smsalert_get_option( 'multivendor_sms_body_approved', 'smsalert_message', SmsAlertMessages::showMessage( 'DEFAULT_NEW_USER_APPROVED' ) );
			do_action( 'sa_send_sms', $author_no, $this->parse_sms_body( $content, $user_id ) );
		}
	}

	/**
	 * Parse sms body function.
	 *
	 * @param string $content content.
	 * @param int    $user_id user_id.
	 *
	 * @return string
	 */
	public function parse_sms_body( $content, $user_id ) {
		$find    = array(
			'[username]',
		);
		$replace = array(
			get_the_author_meta( 'display_name', $user_id ),
		);
		$content = str_replace( $find, $replace, $content );
		return $content;
	}
}

	/*******
* WFCM Plugin
*/
if ( is_plugin_active( 'wc-frontend-manager/wc_frontend_manager.php' ) ) {
	new SAWCFM();
}
/**
 * SAWCFM class
 */
class SAWCFM {

	/**
	 * Construct function
	 */
	public function __construct() {
		add_filter( 'sa_post_author_no', array( $this, 'set_vendor_phone_no' ), 10, 1 );
		add_action( 'wcfm_vendor_enable_after', array( $this, 'activate_vendor' ), 10, 1 );
		add_action( 'wcfm_vendor_disable_after', array( $this, 'reject_vendor' ), 10, 1 );
	}

	/**
	 * Set vendor no function.
	 *
	 * @param int $product_id product_id.
	 *
	 * @return int
	 */
	public function set_vendor_phone_no( $product_id ) {
		$author_no = get_the_author_meta( 'billing_phone', get_post( $product_id )->post_author );
		return ( ! empty( $author_no ) ) ? $author_no : '';
	}

	/**
	 * Activate vendor function.
	 *
	 * @param int $member_id member_id.
	 *
	 * @return void
	 */
	public function activate_vendor( $member_id ) {
		$author_no = get_the_author_meta( 'billing_phone', $member_id );

		if ( empty( $author_no ) ) {
			$user_setting = current( get_user_meta( $member_id, 'wcfmmp_profile_settings' ) );
			$author_no    = ( ! empty( $user_setting['phone'] ) ? $user_setting['phone'] : '' );
		}

		$enabled = smsalert_get_option( 'multivendor_notification_approved', 'smsalert_general' );
		if ( 'on' === $enabled && ! empty( $author_no ) ) {
			$content = smsalert_get_option( 'multivendor_sms_body_approved', 'smsalert_message', SmsAlertMessages::showMessage( 'DEFAULT_NEW_USER_APPROVED' ) );
			do_action( 'sa_send_sms', $author_no, $this->parse_sms_body( $content, $member_id ) );
		}
	}

	/**
	 * Reject vendor function.
	 *
	 * @param int $member_id member_id.
	 *
	 * @return void
	 */
	public function reject_vendor( $member_id ) {
		$author_no = get_the_author_meta( 'billing_phone', $member_id );

		if ( empty( $author_no ) ) {
			$user_setting = current( get_user_meta( $member_id, 'wcfmmp_profile_settings' ) );
			$author_no    = ( ! empty( $user_setting['phone'] ) ? $user_setting['phone'] : '' );
		}

		$enabled = smsalert_get_option( 'multivendor_notification_rejected', 'smsalert_general' );
		if ( 'on' === $enabled && ! empty( $author_no ) ) {
			$content = smsalert_get_option( 'multivendor_sms_body_rejected', 'smsalert_message', SmsAlertMessages::showMessage( 'DEFAULT_NEW_USER_REJECTED' ) );
			do_action( 'sa_send_sms', $author_no, $this->parse_sms_body( $content, $member_id ) );
		}
	}

	/**
	 * Parse sms body function.
	 *
	 * @param string $content   content.
	 * @param int    $member_id member_id.
	 *
	 * @return string
	 */
	public function parse_sms_body( $content, $member_id ) {
		$find    = array(
			'[username]',
		);
		$replace = array(
			get_the_author_meta( 'display_name', $member_id ),
		);
		$content = str_replace( $find, $replace, $content );
		return $content;
	}
}

	/*******
* Local Pickup Plus Plugin
*/
if ( is_plugin_active( 'woocommerce-shipping-local-pickup-plus/woocommerce-shipping-local-pickup-plus.php' ) ) {
	new SAWCLocalPickup();
}
/**
 * SAWCLocalPickup class
 */

class SAWCLocalPickup {

	/**
	 * Construct function
	 */
	public function __construct() {
		add_filter( 'sa_store_manager_no', array( $this, 'set_vendor_phone_no' ), 10, 1 );
	}

	/**
	 * Set vendor no function.
	 *
	 * @param object $order order.
	 *
	 * @return int
	 */
	public function set_vendor_phone_no( $order ) {
		$order_item = $order->get_items( array( 'shipping' ) );
		$item_id    = current( array_keys( $order_item ) );

		$author_no = wc_get_order_item_meta( $item_id, '_pickup_location_phone', $single = true );

		return ( ! empty( $author_no ) ) ? $author_no : '';
	}
}

/*******
* Send digit registration notification
*/
if ( is_plugin_active( 'digits/digit.php' ) ) {
	new SADigit();
}
/**
 * SADigit class
 */

class SADigit {

	/**
	 * Construct function
	 */
	public function __construct() {
		add_filter( 'sa_get_user_phone_no', array( $this, 'set_user_phone_no' ), 10, 2 );
	}

	/**
	 * Set user no function.
	 *
	 * @param string $billing_phone billing phone.
	 * @param int    $user_id user id.
	 *
	 * @return int
	 */
	public function set_user_phone_no( $billing_phone, $user_id ) {
		return ( ! empty( $billing_phone ) ) ? $billing_phone : get_user_meta( $user_id, 'digits_phone', true );
	}
}

/*******
* Send yith request quote notification
*/
if ( is_plugin_active( 'yith-woocommerce-request-a-quote-premium/init.php' ) ) {
    new SARequestQuote();
}
/**
 * SARequestQuote class
 */

class SARequestQuote
{
    /**
     * Construct function
     */
    public function __construct() {
		add_action( 'ywraq_after_create_order', array( $this, 'after_create_order' ), 10, 3 );
    }

	/**
     * Trigger after ywraq order create.
     *
     * @param int $order_id order id.
     * @param int $data data.
     * @param int $raq raq.
     *
     * @return int
     */
	public function after_create_order( $order_id, $data, $raq ) {
		WooCommerceCheckOutForm::trigger_after_order_place( $order_id, 'ywraq-new', 'ywraq-new' );
    }
}

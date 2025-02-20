<?php
/**
 * Plugin Name: Custom WC Item Notes
 * Description: Adds an item notes to WooCommerce products.
 * Version: 1.0
 * Author: Jewelry Store Marketing
 * Text Domain: custom-wc-item-notes
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure WooCommerce is active
function cwcin_check_woocommerce_active() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p><strong>Custom WC Fields Variations</strong> requires WooCommerce to be installed and active.</p></div>';
        });
        return false;
    }
    return true;
}

if (cwcin_check_woocommerce_active()) {
    // Include plugin files
    // require_once plugin_dir_path(__FILE__) . 'includes/custom-fields.php';

    // Enqueue JavaScript and CSS
    function cwcin_enqueue_scripts() {
        wp_enqueue_style('cwcin-styles', plugin_dir_url(__FILE__) . 'assets/css/custom-wc-fields.css', array(), '1.0.0');
        wp_enqueue_script('cwcin-scripts', plugin_dir_url(__FILE__) . 'assets/js/custom-wc-fields.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('dashicons');
    }
    add_action('wp_enqueue_scripts', 'cwcin_enqueue_scripts');

    // Display Item Notes field on the product page
    function cwcin_display_item_notes_field() {
        $item_notes = (isset($_GET['item_notes'])) ? $_GET['item_notes'] : '';
        echo '
            <div class="custom-item-notes">
                <h5 class="item-notes-heading">Additional Options</h5>
                <label for="item_notes">Item Notes</label>
                <textarea class="cwcin-item-notes" id="item_notes" name="item_notes" rows="3" placeholder="Enter addtional notes for this item">'.$item_notes.'</textarea>
            </div>
        ';
    }
    add_action('woocommerce_before_add_to_cart_button', 'cwcin_display_item_notes_field');

    // Save Item Notes to cart item data
    function cwcin_add_item_notes_to_cart($cart_item_data, $product_id, $variation_id) {
        if (!empty($_POST['item_notes'])) {
            $cart_item_data['item_notes'] = sanitize_text_field($_POST['item_notes']);
        }
        return $cart_item_data;
    }
    add_filter('woocommerce_add_cart_item_data', 'cwcin_add_item_notes_to_cart', 10, 3);
    

    add_filter('woocommerce_cart_item_permalink', function ($product_permalink, $cart_item, $cart_item_key) {
        if (!$product_permalink) {
            return ''; // If no link, return empty
        }
    
        // Get the product ID
        $product_id = $cart_item['product_id'];
    
        // Additional custom parameters (Modify as needed)
        $custom_params = array(
            'item_notes' => !empty($cart_item['item_notes']) ? $cart_item['item_notes'] : '',
        );
    
        // Append parameters to the URL
        $product_permalink = add_query_arg($custom_params, $product_permalink);
    
        return $product_permalink;
    }, 10, 3);
    
    function cwcin_display_item_notes_in_cart($item_data, $cart_item) {
        $dataNotes = [];
        
        if (isset($cart_item['item_notes'])) {
            $item_data[] = array(
                'key'   => __('Item Notes', 'custom-wc-item-notes'),
                'value' => ($cart_item['item_notes']) ? $cart_item['item_notes'] : '',
            );
        }else{
            $item_data[] = array(
                'key'   => __('Item Notes', 'custom-wc-item-notes'),
                'value' => '',
            );
        }
        return $item_data;
    }
    add_filter('woocommerce_get_item_data', 'cwcin_display_item_notes_in_cart', 10, 2);
    
    

// ....................................................................................................

// AJAX to update notes

    // Handle AJAX request to update item notes
    add_action('wp_ajax_cwcin_update_cart_item_notes', 'cwcin_update_cart_item_notes');
    add_action('wp_ajax_nopriv_cwcin_update_cart_item_notes', 'cwcin_update_cart_item_notes');

    function cwcin_update_cart_item_notes() {
        // Security check
        if (!isset($_POST['cwcin_cart_key']) || !isset($_POST['edit_item_notes'])) {
            wp_send_json_error(['message' => 'Invalid data.']);
            return;
        }

        $cart_key = sanitize_text_field($_POST['cwcin_cart_key']);
        $item_notes = sanitize_text_field($_POST['edit_item_notes']);
        
        // Access the WooCommerce cart object
        $cart = WC()->cart->get_cart();

        if (isset($cart[$cart_key])) {
            $cart[$cart_key]['item_notes'] = $item_notes;
            WC()->cart->cart_contents[$cart_key] = $cart[$cart_key];
            WC()->cart->set_session();
            wp_send_json_success(['message' => 'Item notes updated successfully.']);
        } else {
            wp_send_json_error(['message' => 'Cart item not found.']);
        }
    }















    // Save item notes to the order line item
    function cwcin_add_item_notes_to_order_items($item, $cart_item_key, $values, $order) {
        if (isset($values['item_notes'])) {
            $item->update_meta_data('Item Notes', sanitize_text_field($values['item_notes']));
        }
    }
    add_action('woocommerce_checkout_create_order_line_item', 'cwcin_add_item_notes_to_order_items', 20, 4);


}

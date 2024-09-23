<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WooCommerce_Brand_Manager {
    private static $instance;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        add_action('admin_post_wc_brand_manager_action', [$this, 'handle_form_submission']);
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'GMP Brand Manager',
            'GMP Brand Manager',
            'manage_woocommerce',
            'wc-brand-manager',
            [$this, 'admin_page']
        );
    }

    public function admin_page() {
        // Display messages
        if (isset($_GET['message'])) {
            if ($_GET['message'] == 'success') {
                echo '<div class="notice notice-success is-dismissible"><p>Products successfully processed.</p></div>';
            } elseif ($_GET['message'] == 'error') {
                echo '<div class="notice notice-error is-dismissible"><p>There was an error processing the products.</p></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1>WooCommerce Brand Manager</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="wc_brand_manager_action">
                <?php wp_nonce_field('wc_brand_manager_action'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">From Brand</th>
                        <td>
                            <?php $this->render_brands_dropdown('from_brand'); ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">To Brand</th>
                        <td>
                            <?php $this->render_brands_dropdown('to_brand'); ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Action</th>
                        <td>
                            <select name="wc_action">
                                <option value="move">Move</option>
                                <option value="copy">Copy</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Execute'); ?>
            </form>
        </div>
        <?php
    }

    private function render_brands_dropdown($name) {
        $terms = get_terms(['taxonomy' => 'pa_brand', 'hide_empty' => false]);
        echo '<select name="' . esc_attr($name) . '">';
        foreach ($terms as $term) {
            echo '<option value="' . esc_attr($term->term_id) . '">' . esc_html($term->name) . '</option>';
        }
        echo '</select>';
    }

    public function handle_form_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wc_brand_manager_action')) {
            wp_die('Invalid nonce');
        }

        $from_brand = intval($_POST['from_brand']);
        $to_brand = intval($_POST['to_brand']);
        $action = sanitize_text_field($_POST['wc_action']);

        if ($from_brand && $to_brand && in_array($action, ['move', 'copy'])) {
            $success = $this->process_products($from_brand, $to_brand, $action);

            if ($success) {
                wp_safe_redirect(admin_url('admin.php?page=wc-brand-manager&message=success'));
            } else {
                wp_safe_redirect(admin_url('admin.php?page=wc-brand-manager&message=error'));
            }
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=wc-brand-manager&message=error'));
        exit;
    }

    private function process_products($from_brand, $to_brand, $action) {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'pa_brand',
                    'field' => 'term_id',
                    'terms' => $from_brand,
                ],
            ],
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();

                if ($action == 'move') {
                    wp_remove_object_terms($product_id, $from_brand, 'pa_brand');
                }

                wp_set_object_terms($product_id, $to_brand, 'pa_brand', true);
            }
            wp_reset_postdata();
            return true;
        } else {
            return false;
        }
    }
}
?>

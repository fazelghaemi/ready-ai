<?php
/*
 * Plugin Name: ReadyMIND | هوش مصنوعی ردی استودیو
 * Plugin URI: https://readystudio.ir/ai/
 * Description: ReadyMIND artificial intelligence assistant for WordPress. هوش مصنوعی ردی استودیو، دستیار پرقدرت و همه جانبه در سایت شما
 * Version: 2.0.1
 * Author: Ready Studio | Fazelghaemi
 * Author URI: https://readystudio.ir/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: readystudio-ai
 * Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('HMYT_TOOLS_VERSION', '2.0.1'); 
define('HMYT_TOOLS_DIR', plugin_dir_path(__FILE__)); 
define('HMYT_TOOLS_URL', plugin_dir_url(__FILE__)); 

require_once HMYT_TOOLS_DIR . 'includes/pwa/class-hmyt-pwa.php';
require_once HMYT_TOOLS_DIR . 'includes/class-hmyt-admin.php';
require_once HMYT_TOOLS_DIR . 'core/api-client.php';
require_once HMYT_TOOLS_DIR . 'core/premium-rtl.php';
require_once HMYT_TOOLS_DIR . 'core/functions.php';


require_once HMYT_TOOLS_DIR . 'includes/ai/class-hmyt-ai-core.php';
require_once HMYT_TOOLS_DIR . 'includes/ai/admin/class-hmyt-ai-admin-settings.php';
require_once HMYT_TOOLS_DIR . 'includes/ai/admin/class-hmyt-ai-product-meta-box.php';
require_once HMYT_TOOLS_DIR . 'includes/ai/admin/class-hmyt-ai-post-meta-box.php'; 
require_once HMYT_TOOLS_DIR . 'includes/ai/admin/class-hmyt-ai-comment-meta-box.php'; 
require_once HMYT_TOOLS_DIR . 'includes/ai/admin/class-hmyt-ai-batch-processing.php';
require_once HMYT_TOOLS_DIR . 'includes/ai/frontend/class-hmyt-ai-frontend.php';
require_once HMYT_TOOLS_DIR . 'includes/ai/helpers/functions.php';
require_once HMYT_TOOLS_DIR . 'includes/ai/frontend/class-hmyt-ai-chatbot.php';
require_once HMYT_TOOLS_DIR . 'includes/ai/admin/class-hmyt-ai-chat-logs.php';


require_once HMYT_TOOLS_DIR . 'includes/affiliate/shortcodes.php';
require_once HMYT_TOOLS_DIR . 'includes/affiliate/post-types.php';
require_once HMYT_TOOLS_DIR . 'includes/affiliate/affiliate-tracking.php';
require_once HMYT_TOOLS_DIR . 'includes/affiliate/admin-request.php';
require_once HMYT_TOOLS_DIR . 'includes/affiliate/admin-balance.php';
require_once HMYT_TOOLS_DIR . 'includes/affiliate/admin-transactions.php';
require_once HMYT_TOOLS_DIR . 'includes/affiliate/admin-settings.php';
require_once HMYT_TOOLS_DIR . 'includes/affiliate/ajax-handlers.php';
require_once HMYT_TOOLS_DIR . 'includes/affiliate/telegram-notifications.php';


function hmyt_safely_include_custom_hooks() {

    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'hamyar-tools-hooks') {
        return;
    }

    if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'hmyt_save_hooks_ajax') {
        return;
    }

    $file_path = HMYT_TOOLS_DIR . 'includes/hooks/custom-hooks.php';

    if (!file_exists($file_path)) {
        return;
    }

    try {
        include_once $file_path;
    } catch (Throwable $e) {
        $error_message = sprintf(
            'Error in ReadyMIND custom hooks file: "%s" in %s on line %d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        error_log($error_message);

        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php
                    echo '<b>ReadyMIND:</b> خطایی در فایل قلاب‌های سفارشی شما شناسایی شد و برای جلوگیری از خرابی سایت، اجرای آن متوقف شد.';
                    echo '<br>لطفاً کدهای خود را در مسیر <code>/wp-content/plugins/ready-ai/includes/hooks/custom-hooks.php</code> بررسی و اصلاح کنید.';
                    echo '<br><strong style="color: #c92c2c;">جزئیات خطا:</strong> <i style="user-select: all;">' . esc_html($e->getMessage()) . ' (خط ' . $e->getLine() . ')</i>';
                ?></p>
            </div>
            <?php
        });
    }
}
add_action('plugins_loaded', 'hmyt_safely_include_custom_hooks', 15);
require_once HMYT_TOOLS_DIR . 'includes/hooks/hmyt-hooks.php';

require_once HMYT_TOOLS_DIR . 'includes/bookmark/class-hmyt-bookmark-core.php';
require_once HMYT_TOOLS_DIR . 'includes/contact-widget/class-hmyt-contact-widget-core.php';
require_once HMYT_TOOLS_DIR . 'includes/ajax-search/class-hmyt-ajax-search-core.php';

require_once HMYT_TOOLS_DIR . 'includes/comment/class-hmyt-comment-core.php';

class Hamyar_Tools {

    protected static $_instance = null;
    public $admin;
    public $pwa;
    public function get_pwa_instance() {
        return $this->pwa;
    }
    public $ai_core; 
    public $ai_admin_settings;
    public $ai_product_meta_box;
    public $ai_post_meta_box;
    public $ai_comment_meta_box; 
    public $ai_frontend;
    public $bookmark_core;
    public $ai_chatbot;
    public $ai_chat_logs;
    public $contact_widget_core;
    public $ajax_search_core;
    public $comment_core;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function __construct() {
        $this->define_constants(); 
        $this->init_hooks(); 
    }

    
    private function define_constants() {
    }

    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init_classes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('plugins_loaded', array($this, 'check_for_update'), 20);
    }


    public function check_for_update() {
        $current_version = HMYT_TOOLS_VERSION;
        $saved_version = get_option('hmyt_tools_version');

        if (version_compare($saved_version, $current_version, '<')) {

            $this->generate_custom_hooks_file();
            
            update_option('hmyt_tools_version', $current_version);
        }
    }
    
    public function init_classes() {
        $this->pwa = new Hamyar_Tools_PWA(); 
        $this->pwa->generate_pwa_files();
        
        $this->ai_core = new HMYT_AI_Review_Summarizer_Core();
        
        if (class_exists('HMYT_Bookmark_Core')) {
            $this->bookmark_core = HMYT_Bookmark();
        }
        if (class_exists('HMYT_Contact_Widget_Core')) {
            $this->contact_widget_core = HMYT_Contact_Widget();
        }
        if (class_exists('HMYT_Ajax_Search_Core')) {
            $this->ajax_search_core = HMYT_Ajax_Search();
        }
        if (class_exists('HMYT_Comment_Core')) {
            $this->comment_core = HMYT_Comment();
        }

        $this->ai_admin_settings = new HMYT_AI_Admin_Settings($this->ai_core);
        $this->ai_product_meta_box = new HMYT_AI_Product_Meta_Box($this->ai_core);
        $this->ai_post_meta_box = new HMYT_AI_Post_Meta_Box($this->ai_core);
        $this->ai_comment_meta_box = new HMYT_AI_Comment_Meta_Box($this->ai_core); 
        $this->ai_frontend = new HMYT_AI_Frontend($this->ai_core);
        $this->ai_chatbot = new HMYT_AI_Chatbot($this->ai_core);
        $this->ai_chat_logs = new HMYT_AI_Chat_Logs();

        $this->admin = new Hamyar_Tools_Admin(
            $this->pwa, 
            $this->ai_core, 
            $this->ai_admin_settings, 
            $this->ai_product_meta_box, 
            $this->ai_post_meta_box, 
            $this->ai_frontend,
            $this->ai_batch_processing
        );

        $this->ai_batch_processing = new HMYT_AI_Batch_Processing($this->ai_core);
    }

    public function enqueue_frontend_assets() {

        $this->pwa->add_pwa_head_tags(); 
        $this->pwa->add_pwa_footer_scripts(); 
    
        $this->ai_frontend->enqueue_frontend_assets(); 
    
        $pwa_enabled = (int) get_option('hmyt_enabled', 0);
        $pwa_popup_enabled = (int) get_option('hmyt_pwa_popup_enabled', 0);
        if ($pwa_enabled && $pwa_popup_enabled) {
            wp_enqueue_style('hmyt-pwa-pop-up-css', HMYT_TOOLS_URL . 'assets/css/hmyt-pwa-pop-up.min.css', [], HMYT_TOOLS_VERSION);
            wp_enqueue_script('hmyt-pwa-pop-up', HMYT_TOOLS_URL . 'assets/js/hmyt-pwa-pop-up.min.js', ['jquery'], HMYT_TOOLS_VERSION, true);
            wp_localize_script('hmyt-pwa-pop-up', 'hmytPwaPopupVars', array(
                'frequency' => (int) get_option('hmyt_pwa_popup_frequency', 7),
                'pwa_name' => get_option('hmyt_app_name', get_bloginfo('name')),
                'plugin_url' => HMYT_TOOLS_URL,
                'theme_color' => get_option('hmyt_theme_color', '#2d3748'),
                'icon_url' => get_option('hmyt_icon', get_site_icon_url()),
                'desktop_enabled' => (int) get_option('hmyt_pwa_popup_desktop_enabled', 1),
            ));
        }
    
        if (get_option('hmyt_affiliate_system_enabled', 1) == 1) {
            wp_enqueue_style('hmyt-affiliate-front', HMYT_TOOLS_URL . 'assets/css/hmyt-affiliate-front.min.css', [], HMYT_TOOLS_VERSION);

            $dark_mode_enabled = get_option('hmyt_dark_mode_enabled', 0);
            if ($dark_mode_enabled == 1) {
                wp_enqueue_style('hmyt-affiliate-front-dark', HMYT_TOOLS_URL . 'assets/css/hmyt-affiliate-front-dark.min.css', ['hmyt-affiliate-front'], HMYT_TOOLS_VERSION);
            }
        }
    }

    public function generate_custom_hooks_file() {
        $custom_code = get_option('hamyar_tools_custom_code', "<?php\n\n// کدهای سفارشی خود را اینجا وارد کنید\n");
        $file_path = HMYT_TOOLS_DIR . 'includes/hooks/custom-hooks.php';
        file_put_contents($file_path, $custom_code);
    }

    public $ai_batch_processing;
}


function HMYT_TOOLS() {
    return Hamyar_Tools::instance();
}

HMYT_TOOLS();

register_deactivation_hook(__FILE__, array('Hamyar_Tools_PWA', 'deactivate'));


function hmyt_activation_hook() {

    if (class_exists('Hamyar_Tools')) {
        $plugin_instance = Hamyar_Tools::instance();
        
        $plugin_instance->init_classes();

        if (method_exists($plugin_instance, 'generate_custom_hooks_file')) {
            $plugin_instance->generate_custom_hooks_file();
        }
        
        if (class_exists('HMYT_AI_Chat_Logs') && method_exists('HMYT_AI_Chat_Logs', 'create_table')) {
            HMYT_AI_Chat_Logs::create_table();
        }
    }
}
register_activation_hook(__FILE__, 'hmyt_activation_hook');

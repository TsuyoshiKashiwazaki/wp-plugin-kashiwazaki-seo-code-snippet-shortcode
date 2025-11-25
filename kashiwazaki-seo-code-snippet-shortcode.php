<?php
/**
 * Plugin Name: Kashiwazaki SEO Code Snippet Shortcode
 * Plugin URI: https://www.tsuyoshikashiwazaki.jp
 * Description: コードスニペットを管理し、ショートコードで呼び出すことができるプラグイン。PHP、HTML、CSS、JavaScriptに対応し、シンタックスハイライト付きエディタを搭載。
 * Version: 1.0.0
 * Author: 柏崎剛 (Tsuyoshi Kashiwazaki)
 * Author URI: https://www.tsuyoshikashiwazaki.jp/profile/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kashiwazaki-seo-code-snippet-shortcode
 * Domain Path: /languages
 */

// 直接アクセス禁止
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// プラグイン定数定義
define( 'KSCSS_VERSION', '1.0.0' );
define( 'KSCSS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KSCSS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KSCSS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * メインプラグインクラス
 */
class Kashiwazaki_SEO_Code_Snippet_Shortcode {

    /**
     * シングルトンインスタンス
     */
    private static $instance = null;

    /**
     * インスタンス取得
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * 依存ファイルの読み込み
     */
    private function load_dependencies() {
        require_once KSCSS_PLUGIN_DIR . 'includes/class-kscss-post-type.php';
        require_once KSCSS_PLUGIN_DIR . 'includes/class-kscss-admin.php';
        require_once KSCSS_PLUGIN_DIR . 'includes/class-kscss-shortcode.php';
        require_once KSCSS_PLUGIN_DIR . 'includes/class-kscss-revision.php';
        require_once KSCSS_PLUGIN_DIR . 'includes/class-kscss-usage-tracker.php';
        require_once KSCSS_PLUGIN_DIR . 'includes/class-kscss-gutenberg.php';
        require_once KSCSS_PLUGIN_DIR . 'includes/class-kscss-classic-editor.php';
        require_once KSCSS_PLUGIN_DIR . 'includes/class-kscss-auto-insert.php';
    }

    /**
     * フック初期化
     */
    private function init_hooks() {
        // アクティベーション/デアクティベーション
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // 初期化
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    /**
     * プラグイン初期化
     */
    public function init() {
        // カスタム投稿タイプ登録
        KSCSS_Post_Type::get_instance();

        // 管理画面
        if ( is_admin() ) {
            KSCSS_Admin::get_instance();
            KSCSS_Revision::get_instance();
            KSCSS_Usage_Tracker::get_instance();
        }

        // ショートコード
        KSCSS_Shortcode::get_instance();

        // Gutenbergブロック
        KSCSS_Gutenberg::get_instance();

        // クラシックエディタ
        KSCSS_Classic_Editor::get_instance();

        // 自動挿入
        KSCSS_Auto_Insert::get_instance();
    }

    /**
     * テキストドメイン読み込み
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'kashiwazaki-seo-code-snippet-shortcode',
            false,
            dirname( KSCSS_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * アクティベーション
     */
    public function activate() {
        // カスタム投稿タイプを登録
        require_once KSCSS_PLUGIN_DIR . 'includes/class-kscss-post-type.php';
        KSCSS_Post_Type::get_instance()->register_post_type();

        // リライトルールをフラッシュ
        flush_rewrite_rules();

        // デフォルトオプション設定
        $default_options = array(
            'enable_php_execution' => false,
            'auto_escape_output'   => true,
        );
        add_option( 'kscss_settings', $default_options );

        // バージョン保存
        add_option( 'kscss_version', KSCSS_VERSION );
    }

    /**
     * デアクティベーション
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

/**
 * プラグインインスタンス取得関数
 */
function kscss() {
    return Kashiwazaki_SEO_Code_Snippet_Shortcode::get_instance();
}

// プラグイン起動
kscss();

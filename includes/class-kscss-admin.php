<?php
/**
 * 管理画面クラス
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 管理画面管理クラス
 */
class KSCSS_Admin {

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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_footer', array( $this, 'add_auto_insert_script' ) );
        add_filter( 'plugin_action_links_' . KSCSS_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
    }

    /**
     * 管理メニュー追加
     */
    public function add_admin_menu() {
        // メインメニュー
        add_menu_page(
            __( 'Kashiwazaki SEO Code Snippet Shortcode', 'kashiwazaki-seo-code-snippet-shortcode' ),
            __( 'Kashiwazaki SEO Code Snippet Shortcode', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'manage_options',
            'kscss-snippets',
            array( $this, 'render_snippets_page' ),
            'dashicons-editor-code',
            81
        );

        // スニペット一覧
        add_submenu_page(
            'kscss-snippets',
            __( 'すべてのスニペット', 'kashiwazaki-seo-code-snippet-shortcode' ),
            __( 'すべてのスニペット', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'manage_options',
            'kscss-snippets',
            array( $this, 'render_snippets_page' )
        );

        // 新規追加
        add_submenu_page(
            'kscss-snippets',
            __( '新規追加', 'kashiwazaki-seo-code-snippet-shortcode' ),
            __( '新規追加', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'manage_options',
            'post-new.php?post_type=' . KSCSS_Post_Type::POST_TYPE
        );

        // 使用状況
        add_submenu_page(
            'kscss-snippets',
            __( '使用状況', 'kashiwazaki-seo-code-snippet-shortcode' ),
            __( '使用状況', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'manage_options',
            'kscss-usage',
            array( $this, 'render_usage_page' )
        );

        // 設定
        add_submenu_page(
            'kscss-snippets',
            __( '設定', 'kashiwazaki-seo-code-snippet-shortcode' ),
            __( '設定', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'manage_options',
            'kscss-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * 管理画面アセット読み込み
     */
    public function enqueue_admin_assets( $hook ) {
        global $post_type, $post;

        // 投稿タイプを確実に取得
        $current_post_type = $post_type;
        if ( empty( $current_post_type ) && isset( $_GET['post'] ) ) {
            $current_post_type = get_post_type( absint( $_GET['post'] ) );
        }
        if ( empty( $current_post_type ) && isset( $_GET['post_type'] ) ) {
            $current_post_type = sanitize_text_field( $_GET['post_type'] );
        }

        // スニペット編集画面でのみ読み込み
        $is_snippet_page = (
            ( 'post.php' === $hook || 'post-new.php' === $hook ) &&
            KSCSS_Post_Type::POST_TYPE === $current_post_type
        );

        $is_plugin_page = ( strpos( $hook, 'kscss' ) !== false ) || ( 'toplevel_page_kscss-snippets' === $hook );

        // デバッグ用
        // error_log( "KSCSS Debug: hook=$hook, post_type=$current_post_type, is_snippet=$is_snippet_page, is_plugin=$is_plugin_page" );

        if ( ! $is_snippet_page && ! $is_plugin_page ) {
            return;
        }

        // 共通スタイル
        wp_enqueue_style(
            'kscss-admin',
            KSCSS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            KSCSS_VERSION
        );

        // スニペット編集画面
        if ( $is_snippet_page ) {
            // CodeMirror
            wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
            wp_enqueue_script( 'wp-theme-plugin-editor' );
            wp_enqueue_style( 'wp-codemirror' );

            // カスタムスクリプト
            wp_enqueue_script(
                'kscss-editor',
                KSCSS_PLUGIN_URL . 'assets/js/editor.js',
                array( 'jquery', 'wp-theme-plugin-editor' ),
                KSCSS_VERSION,
                true
            );

            wp_localize_script( 'kscss-editor', 'kscssEditor', array(
                'codeTypes' => array(
                    'php'        => 'application/x-httpd-php',
                    'html'       => 'text/html',
                    'css'        => 'text/css',
                    'javascript' => 'application/javascript',
                ),
            ) );
        }

        // 管理画面共通スクリプト
        wp_enqueue_script(
            'kscss-admin',
            KSCSS_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            KSCSS_VERSION,
            true
        );

        wp_localize_script( 'kscss-admin', 'kscssAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'kscss_admin_nonce' ),
            'strings' => array(
                'copied'      => __( 'コピーしました', 'kashiwazaki-seo-code-snippet-shortcode' ),
                'copyFailed'  => __( 'コピーに失敗しました', 'kashiwazaki-seo-code-snippet-shortcode' ),
                'confirmDelete' => __( '本当に削除しますか？', 'kashiwazaki-seo-code-snippet-shortcode' ),
            ),
        ) );
    }

    /**
     * 設定登録
     */
    public function register_settings() {
        // 一般設定
        register_setting( 'kscss_settings_group', 'kscss_settings', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
        ) );

        add_settings_section(
            'kscss_general_section',
            __( '一般設定', 'kashiwazaki-seo-code-snippet-shortcode' ),
            array( $this, 'render_general_section' ),
            'kscss-settings'
        );

        add_settings_field(
            'enable_php_execution',
            __( 'PHPコード実行', 'kashiwazaki-seo-code-snippet-shortcode' ),
            array( $this, 'render_php_execution_field' ),
            'kscss-settings',
            'kscss_general_section'
        );

        add_settings_field(
            'auto_escape_output',
            __( '自動エスケープ', 'kashiwazaki-seo-code-snippet-shortcode' ),
            array( $this, 'render_auto_escape_field' ),
            'kscss-settings',
            'kscss_general_section'
        );

        // 自動挿入設定
        register_setting( 'kscss_auto_insert_group', 'kscss_auto_insert', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_auto_insert' ),
            'default'           => array(),
        ) );

        add_settings_section(
            'kscss_auto_insert_section',
            __( '自動挿入設定', 'kashiwazaki-seo-code-snippet-shortcode' ),
            array( $this, 'render_auto_insert_section' ),
            'kscss-auto-insert'
        );

        add_settings_field(
            'auto_insert_rules',
            __( '挿入ルール', 'kashiwazaki-seo-code-snippet-shortcode' ),
            array( $this, 'render_auto_insert_rules_field' ),
            'kscss-auto-insert',
            'kscss_auto_insert_section'
        );
    }

    /**
     * 設定サニタイズ
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();
        $sanitized['enable_php_execution'] = ! empty( $input['enable_php_execution'] );
        $sanitized['auto_escape_output']   = ! empty( $input['auto_escape_output'] );
        return $sanitized;
    }

    /**
     * 一般設定セクション描画
     */
    public function render_general_section() {
        echo '<p>' . esc_html__( 'プラグインの動作に関する設定を行います。', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</p>';
    }

    /**
     * PHP実行フィールド描画
     */
    public function render_php_execution_field() {
        $options = get_option( 'kscss_settings', array() );
        $enabled = ! empty( $options['enable_php_execution'] );
        ?>
        <label>
            <input type="checkbox" name="kscss_settings[enable_php_execution]" value="1" <?php checked( $enabled ); ?>>
            <?php esc_html_e( 'PHPコードの実行を許可する', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( '警告: PHPコードの実行を許可すると、セキュリティリスクが発生する可能性があります。信頼できるコードのみを使用してください。', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
        </p>
        <?php
    }

    /**
     * 自動エスケープフィールド描画
     */
    public function render_auto_escape_field() {
        $options = get_option( 'kscss_settings', array() );
        $enabled = isset( $options['auto_escape_output'] ) ? $options['auto_escape_output'] : true;
        ?>
        <label>
            <input type="checkbox" name="kscss_settings[auto_escape_output]" value="1" <?php checked( $enabled ); ?>>
            <?php esc_html_e( 'デフォルトで出力を自動エスケープする', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( '新規作成時のデフォルト設定です。各スニペットで個別に設定することもできます。', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
        </p>
        <?php
    }

    /**
     * スニペット一覧ページ描画
     */
    public function render_snippets_page() {
        // 編集画面へのリダイレクト
        if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['snippet_id'] ) ) {
            $edit_url = admin_url( 'post.php?post=' . absint( $_GET['snippet_id'] ) . '&action=edit' );
            wp_redirect( $edit_url );
            exit;
        }

        include KSCSS_PLUGIN_DIR . 'includes/views/snippets-list.php';
    }

    /**
     * 使用状況ページ描画
     */
    public function render_usage_page() {
        include KSCSS_PLUGIN_DIR . 'includes/views/usage.php';
    }

    /**
     * 設定ページ描画
     */
    public function render_settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Kashiwazaki SEO Code Snippet Shortcode 設定', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=kscss-settings&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( '一般設定', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                </a>
                <a href="?page=kscss-settings&tab=auto-insert" class="nav-tab <?php echo 'auto-insert' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( '自動挿入', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                </a>
            </nav>

            <form method="post" action="options.php">
                <?php
                if ( 'general' === $active_tab ) {
                    settings_fields( 'kscss_settings_group' );
                    do_settings_sections( 'kscss-settings' );
                } elseif ( 'auto-insert' === $active_tab ) {
                    settings_fields( 'kscss_auto_insert_group' );
                    do_settings_sections( 'kscss-auto-insert' );
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * 自動挿入セクション描画
     */
    public function render_auto_insert_section() {
        echo '<p>' . esc_html__( '特定の投稿タイプや位置にスニペットを自動挿入する設定を行います。', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</p>';
    }

    /**
     * 自動挿入ルールフィールド描画
     */
    public function render_auto_insert_rules_field() {
        $rules = get_option( 'kscss_auto_insert', array() );
        $snippets = get_posts( array(
            'post_type'      => KSCSS_Post_Type::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        ?>
        <div id="kscss-auto-insert-rules">
            <?php
            if ( ! empty( $rules ) ) {
                foreach ( $rules as $index => $rule ) {
                    $this->render_auto_insert_rule_row( $index, $rule, $snippets, $post_types );
                }
            }
            ?>
        </div>
        <p>
            <button type="button" class="button" id="kscss-add-rule">
                <?php esc_html_e( '+ ルールを追加', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
            </button>
        </p>
        <script>
        var kscssAutoInsertData = {
            ruleIndex: <?php echo count( $rules ); ?>,
            snippets: <?php echo wp_json_encode( array_values( array_map( function( $s ) {
                return array( 'id' => $s->ID, 'title' => $s->post_title );
            }, $snippets ) ) ); ?>,
            postTypes: <?php echo wp_json_encode( array_values( array_map( function( $pt ) {
                return array( 'name' => $pt->name, 'label' => $pt->label );
            }, $post_types ) ) ); ?>,
            strings: {
                rule: '<?php esc_html_e( 'ルール', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                delete: '<?php esc_html_e( '削除', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                snippet: '<?php esc_html_e( 'スニペット', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                selectSnippet: '<?php esc_html_e( '選択してください', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                postType: '<?php esc_html_e( '投稿タイプ', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                checkAll: '<?php esc_html_e( '全て選択', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                uncheckAll: '<?php esc_html_e( '全て解除', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                position: '<?php esc_html_e( '挿入位置', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                beforeContent: '<?php esc_html_e( '本文の前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                afterContent: '<?php esc_html_e( '本文の後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                afterFirstParagraph: '<?php esc_html_e( '最初の段落の後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                beforeFirstH2: '<?php esc_html_e( '最初のH2見出しの前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                afterFirstH2: '<?php esc_html_e( '最初のH2見出しの後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                beforeLastParagraph: '<?php esc_html_e( '最後の段落の前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                afterLastParagraph: '<?php esc_html_e( '最後の段落の後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                beforeLastH2: '<?php esc_html_e( '最後のH2見出しの前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                afterLastH2: '<?php esc_html_e( '最後のH2見出しの後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                beforeLastH3: '<?php esc_html_e( '最後のH3見出しの前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>',
                afterLastH3: '<?php esc_html_e( '最後のH3見出しの後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>'
            }
        };
        </script>
        <style>
            .kscss-rule-row {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 15px;
                border-radius: 4px;
            }
            .kscss-rule-row h4 {
                margin: 0 0 15px 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #ddd;
            }
            .kscss-rule-field {
                margin-bottom: 15px;
            }
            .kscss-rule-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .kscss-rule-field select {
                min-width: 300px;
            }
            .kscss-post-types-checkboxes {
                display: flex;
                flex-wrap: wrap;
                gap: 10px 20px;
                padding: 10px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                max-height: 200px;
                overflow-y: auto;
            }
            .kscss-checkbox-label {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-weight: normal !important;
                cursor: pointer;
                white-space: nowrap;
            }
            .kscss-checkbox-label input[type="checkbox"] {
                margin: 0;
            }
            .kscss-post-types-buttons {
                margin-bottom: 8px;
            }
            .kscss-post-types-buttons .button {
                margin-right: 5px;
            }
        </style>
        <?php
    }

    /**
     * 自動挿入ルール行描画
     */
    private function render_auto_insert_rule_row( $index, $rule, $snippets, $post_types ) {
        $snippet_id = isset( $rule['snippet_id'] ) ? $rule['snippet_id'] : '';
        $selected_post_types = isset( $rule['post_types'] ) ? (array) $rule['post_types'] : array( 'post' );
        $position = isset( $rule['position'] ) ? $rule['position'] : 'before_content';
        ?>
        <div class="kscss-rule-row">
            <h4>
                <?php esc_html_e( 'ルール', 'kashiwazaki-seo-code-snippet-shortcode' ); ?> #<?php echo esc_html( $index + 1 ); ?>
                <button type="button" class="button-link kscss-remove-rule" style="color: #b32d2e; float: right;">
                    <?php esc_html_e( '削除', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                </button>
            </h4>

            <div class="kscss-rule-field">
                <label><?php esc_html_e( 'スニペット', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></label>
                <select name="kscss_auto_insert[<?php echo esc_attr( $index ); ?>][snippet_id]">
                    <option value=""><?php esc_html_e( '選択してください', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <?php foreach ( $snippets as $snippet ) : ?>
                        <option value="<?php echo esc_attr( $snippet->ID ); ?>" <?php selected( $snippet_id, $snippet->ID ); ?>>
                            <?php echo esc_html( $snippet->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="kscss-rule-field">
                <label><?php esc_html_e( '投稿タイプ', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></label>
                <div class="kscss-post-types-buttons">
                    <button type="button" class="button button-small kscss-check-all"><?php esc_html_e( '全て選択', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></button>
                    <button type="button" class="button button-small kscss-uncheck-all"><?php esc_html_e( '全て解除', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></button>
                </div>
                <div class="kscss-post-types-checkboxes">
                    <?php foreach ( $post_types as $pt ) : ?>
                        <label class="kscss-checkbox-label">
                            <input type="checkbox"
                                   name="kscss_auto_insert[<?php echo esc_attr( $index ); ?>][post_types][]"
                                   value="<?php echo esc_attr( $pt->name ); ?>"
                                   <?php checked( in_array( $pt->name, $selected_post_types, true ) ); ?>>
                            <?php echo esc_html( $pt->label ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="kscss-rule-field">
                <label><?php esc_html_e( '挿入位置', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></label>
                <select name="kscss_auto_insert[<?php echo esc_attr( $index ); ?>][position]">
                    <option value="before_content" <?php selected( $position, 'before_content' ); ?>><?php esc_html_e( '本文の前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="after_content" <?php selected( $position, 'after_content' ); ?>><?php esc_html_e( '本文の後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="after_first_paragraph" <?php selected( $position, 'after_first_paragraph' ); ?>><?php esc_html_e( '最初の段落の後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="before_first_h2" <?php selected( $position, 'before_first_h2' ); ?>><?php esc_html_e( '最初のH2見出しの前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="after_first_h2" <?php selected( $position, 'after_first_h2' ); ?>><?php esc_html_e( '最初のH2見出しの後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="before_last_paragraph" <?php selected( $position, 'before_last_paragraph' ); ?>><?php esc_html_e( '最後の段落の前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="after_last_paragraph" <?php selected( $position, 'after_last_paragraph' ); ?>><?php esc_html_e( '最後の段落の後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="before_last_h2" <?php selected( $position, 'before_last_h2' ); ?>><?php esc_html_e( '最後のH2見出しの前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="after_last_h2" <?php selected( $position, 'after_last_h2' ); ?>><?php esc_html_e( '最後のH2見出しの後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="before_last_h3" <?php selected( $position, 'before_last_h3' ); ?>><?php esc_html_e( '最後のH3見出しの前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                    <option value="after_last_h3" <?php selected( $position, 'after_last_h3' ); ?>><?php esc_html_e( '最後のH3見出しの後', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></option>
                </select>
            </div>
        </div>
        <?php
    }

    /**
     * 自動挿入設定サニタイズ
     */
    public function sanitize_auto_insert( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $input as $rule ) {
            if ( empty( $rule['snippet_id'] ) ) {
                continue;
            }

            // 投稿タイプが選択されていない場合はスキップ
            if ( empty( $rule['post_types'] ) || ! is_array( $rule['post_types'] ) ) {
                continue;
            }

            $post_types = array_map( 'sanitize_text_field', $rule['post_types'] );

            $sanitized[] = array(
                'snippet_id' => absint( $rule['snippet_id'] ),
                'post_types' => $post_types,
                'position'   => sanitize_text_field( $rule['position'] ),
            );
        }

        return $sanitized;
    }

    /**
     * 自動挿入スクリプト追加
     */
    public function add_auto_insert_script() {
        $screen = get_current_screen();
        if ( ! $screen || 'kashiwazaki-seo-code-snippet-shortcode_page_kscss-settings' !== $screen->id ) {
            return;
        }

        if ( ! isset( $_GET['tab'] ) || 'auto-insert' !== $_GET['tab'] ) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            if (typeof kscssAutoInsertData === 'undefined') {
                console.error('kscssAutoInsertData is not defined');
                return;
            }

            var ruleIndex = parseInt(kscssAutoInsertData.ruleIndex);
            var snippetsData = kscssAutoInsertData.snippets;
            var postTypesData = Array.isArray(kscssAutoInsertData.postTypes)
                ? kscssAutoInsertData.postTypes
                : Object.values(kscssAutoInsertData.postTypes);
            var strings = kscssAutoInsertData.strings;

            console.log('Auto-insert initialized', { ruleIndex, snippetsCount: snippetsData.length, postTypesCount: postTypesData.length });

            $('#kscss-add-rule').on('click', function() {
                console.log('Add rule clicked, current index:', ruleIndex);
                var html = '<div class="kscss-rule-row">' +
                    '<h4>' + strings.rule + ' #' + (ruleIndex + 1) +
                    '<button type="button" class="button-link kscss-remove-rule" style="color: #b32d2e; float: right;">' + strings.delete + '</button>' +
                    '</h4>' +
                    '<div class="kscss-rule-field">' +
                    '<label>' + strings.snippet + '</label>' +
                    '<select name="kscss_auto_insert[' + ruleIndex + '][snippet_id]">' +
                    '<option value="">' + strings.selectSnippet + '</option>';

                snippetsData.forEach(function(snippet) {
                    html += '<option value="' + snippet.id + '">' + $('<div>').text(snippet.title).html() + '</option>';
                });

                html += '</select></div>' +
                    '<div class="kscss-rule-field">' +
                    '<label>' + strings.postType + '</label>' +
                    '<div class="kscss-post-types-buttons">' +
                    '<button type="button" class="button button-small kscss-check-all">' + strings.checkAll + '</button>' +
                    '<button type="button" class="button button-small kscss-uncheck-all">' + strings.uncheckAll + '</button>' +
                    '</div>' +
                    '<div class="kscss-post-types-checkboxes">';

                postTypesData.forEach(function(pt) {
                    var checked = (pt.name === 'post') ? ' checked' : '';
                    html += '<label class="kscss-checkbox-label">' +
                        '<input type="checkbox" name="kscss_auto_insert[' + ruleIndex + '][post_types][]" value="' + pt.name + '"' + checked + '>' +
                        $('<div>').text(pt.label).html() +
                        '</label>';
                });

                html += '</div></div>' +
                    '<div class="kscss-rule-field">' +
                    '<label>' + strings.position + '</label>' +
                    '<select name="kscss_auto_insert[' + ruleIndex + '][position]">' +
                    '<option value="before_content">' + strings.beforeContent + '</option>' +
                    '<option value="after_content">' + strings.afterContent + '</option>' +
                    '<option value="after_first_paragraph">' + strings.afterFirstParagraph + '</option>' +
                    '<option value="before_first_h2">' + strings.beforeFirstH2 + '</option>' +
                    '<option value="after_first_h2">' + strings.afterFirstH2 + '</option>' +
                    '<option value="before_last_paragraph">' + strings.beforeLastParagraph + '</option>' +
                    '<option value="after_last_paragraph">' + strings.afterLastParagraph + '</option>' +
                    '<option value="before_last_h2">' + strings.beforeLastH2 + '</option>' +
                    '<option value="after_last_h2">' + strings.afterLastH2 + '</option>' +
                    '<option value="before_last_h3">' + strings.beforeLastH3 + '</option>' +
                    '<option value="after_last_h3">' + strings.afterLastH3 + '</option>' +
                    '</select></div>' +
                    '</div>';

                $('#kscss-auto-insert-rules').append(html);
                ruleIndex++;
            });

            $(document).on('click', '.kscss-remove-rule', function() {
                $(this).closest('.kscss-rule-row').remove();
            });

            // 全て選択
            $(document).on('click', '.kscss-check-all', function() {
                $(this).closest('.kscss-rule-field').find('.kscss-post-types-checkboxes input[type="checkbox"]').prop('checked', true);
            });

            // 全て解除
            $(document).on('click', '.kscss-uncheck-all', function() {
                $(this).closest('.kscss-rule-field').find('.kscss-post-types-checkboxes input[type="checkbox"]').prop('checked', false);
            });
        });
        </script>
        <?php
    }

    /**
     * プラグインアクションリンク追加
     */
    public function add_plugin_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=kscss-settings' ) . '">' .
            __( '設定', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }
}

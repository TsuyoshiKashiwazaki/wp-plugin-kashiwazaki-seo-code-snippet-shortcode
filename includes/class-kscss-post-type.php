<?php
/**
 * カスタム投稿タイプクラス
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * カスタム投稿タイプ管理クラス
 */
class KSCSS_Post_Type {

    /**
     * 投稿タイプ名
     */
    const POST_TYPE = 'kscss_snippet';

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
        // initフック内で呼ばれる場合は直接登録
        if ( did_action( 'init' ) ) {
            $this->register_post_type();
        } else {
            add_action( 'init', array( $this, 'register_post_type' ) );
        }
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );

        // コードタイプがHTML以外の場合はブロックエディタを無効化
        add_filter( 'use_block_editor_for_post', array( $this, 'maybe_disable_block_editor' ), 10, 2 );
    }

    /**
     * コードタイプに応じてブロックエディタを切り替え
     *
     * @param bool    $use_block_editor ブロックエディタを使用するか
     * @param WP_Post $post             投稿オブジェクト
     * @return bool
     */
    public function maybe_disable_block_editor( $use_block_editor, $post ) {
        if ( self::POST_TYPE !== $post->post_type ) {
            return $use_block_editor;
        }

        $code_type = get_post_meta( $post->ID, '_kscss_code_type', true );

        // 新規作成時（code_typeが空）またはHTMLタイプの場合はブロックエディタを使用
        if ( empty( $code_type ) || 'html' === $code_type ) {
            return true;
        }

        // PHP/CSS/JavaScriptはCodeMirrorで編集（クラシックエディタ）
        return false;
    }

    /**
     * カスタム投稿タイプ登録
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => __( 'コードスニペット', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'singular_name'         => __( 'コードスニペット', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'menu_name'             => __( 'Kashiwazaki SEO Code Snippet Shortcode', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'all_items'             => __( 'すべてのスニペット', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'add_new'               => __( '新規追加', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'add_new_item'          => __( '新規スニペット追加', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'edit_item'             => __( 'スニペット編集', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'new_item'              => __( '新規スニペット', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'view_item'             => __( 'スニペット表示', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'search_items'          => __( 'スニペット検索', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'not_found'             => __( 'スニペットが見つかりません', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'not_found_in_trash'    => __( 'ゴミ箱にスニペットはありません', 'kashiwazaki-seo-code-snippet-shortcode' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'capabilities'        => array(
                'edit_post'          => 'manage_options',
                'read_post'          => 'manage_options',
                'delete_post'        => 'manage_options',
                'edit_posts'         => 'manage_options',
                'edit_others_posts'  => 'manage_options',
                'publish_posts'      => 'manage_options',
                'read_private_posts' => 'manage_options',
            ),
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 81,
            'menu_icon'           => 'dashicons-editor-code',
            'supports'            => array( 'title', 'editor', 'revisions' ),
            'show_in_rest'        => true,
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * メタボックス追加
     */
    public function add_meta_boxes() {
        global $post;

        // HTMLタイプの場合はコードエディタメタボックスを表示しない（ブロックエディタを使用）
        $code_type = get_post_meta( $post->ID, '_kscss_code_type', true );
        if ( 'html' !== $code_type ) {
            add_meta_box(
                'kscss_code_editor',
                __( 'コードエディタ', 'kashiwazaki-seo-code-snippet-shortcode' ),
                array( $this, 'render_code_editor_meta_box' ),
                self::POST_TYPE,
                'normal',
                'high'
            );
        }

        add_meta_box(
            'kscss_snippet_settings',
            __( 'スニペット設定', 'kashiwazaki-seo-code-snippet-shortcode' ),
            array( $this, 'render_settings_meta_box' ),
            self::POST_TYPE,
            'side',
            'default'
        );

        add_meta_box(
            'kscss_shortcode_display',
            __( 'ショートコード', 'kashiwazaki-seo-code-snippet-shortcode' ),
            array( $this, 'render_shortcode_meta_box' ),
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    /**
     * コードエディタメタボックス描画
     */
    public function render_code_editor_meta_box( $post ) {
        wp_nonce_field( 'kscss_save_meta', 'kscss_meta_nonce' );

        $code        = get_post_meta( $post->ID, '_kscss_code', true );
        $description = get_post_meta( $post->ID, '_kscss_description', true );
        ?>
        <div class="kscss-meta-box">
            <p>
                <label for="kscss_description"><strong><?php esc_html_e( '説明', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></strong></label>
                <textarea id="kscss_description" name="kscss_description" rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
            </p>
            <p>
                <label for="kscss_code"><strong><?php esc_html_e( 'コード', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></strong></label>
            </p>
            <textarea id="kscss_code" name="kscss_code" rows="20" class="large-text code"><?php echo esc_textarea( $code ); ?></textarea>
        </div>
        <?php
    }

    /**
     * 設定メタボックス描画
     */
    public function render_settings_meta_box( $post ) {
        $code_type      = get_post_meta( $post->ID, '_kscss_code_type', true );
        $execute_php    = get_post_meta( $post->ID, '_kscss_execute_php', true );
        $auto_escape    = get_post_meta( $post->ID, '_kscss_auto_escape', true );

        if ( empty( $code_type ) ) {
            $code_type = 'html';
        }
        if ( '' === $auto_escape ) {
            $auto_escape = '1';
        }

        $code_types = array(
            'php'        => 'PHP',
            'html'       => 'HTML',
            'css'        => 'CSS',
            'javascript' => 'JavaScript',
        );
        ?>
        <div class="kscss-meta-box">
            <p>
                <label for="kscss_code_type"><strong><?php esc_html_e( 'コードタイプ', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></strong></label>
                <select id="kscss_code_type" name="kscss_code_type" class="widefat">
                    <?php foreach ( $code_types as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $code_type, $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="kscss_execute_php" value="1" <?php checked( $execute_php, '1' ); ?>>
                    <?php esc_html_e( 'PHPコードを実行する', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                </label>
                <br>
                <span class="description"><?php esc_html_e( '※ セキュリティリスクがあります', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></span>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="kscss_auto_escape" value="1" <?php checked( $auto_escape, '1' ); ?>>
                    <?php esc_html_e( '出力時に自動エスケープ', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                </label>
            </p>
        </div>
        <?php
    }

    /**
     * ショートコードメタボックス描画
     */
    public function render_shortcode_meta_box( $post ) {
        if ( 'auto-draft' === $post->post_status ) {
            echo '<p>' . esc_html__( '保存後にショートコードが表示されます', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</p>';
            return;
        }

        $shortcode_id   = '[kscss_snippet id="' . $post->ID . '"]';
        $shortcode_name = '';

        if ( ! empty( $post->post_name ) ) {
            $shortcode_name = '[kscss_snippet name="' . $post->post_name . '"]';
        }
        ?>
        <div class="kscss-shortcode-box">
            <p>
                <label><strong><?php esc_html_e( 'ID指定', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></strong></label>
                <input type="text" value="<?php echo esc_attr( $shortcode_id ); ?>" class="large-text code kscss-shortcode-input" readonly>
                <button type="button" class="button kscss-copy-shortcode" data-shortcode="<?php echo esc_attr( $shortcode_id ); ?>">
                    <?php esc_html_e( 'コピー', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                </button>
            </p>
            <?php if ( $shortcode_name ) : ?>
            <p>
                <label><strong><?php esc_html_e( 'スラッグ指定', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></strong></label>
                <input type="text" value="<?php echo esc_attr( $shortcode_name ); ?>" class="large-text code kscss-shortcode-input" readonly>
                <button type="button" class="button kscss-copy-shortcode" data-shortcode="<?php echo esc_attr( $shortcode_name ); ?>">
                    <?php esc_html_e( 'コピー', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                </button>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * メタボックス保存
     */
    public function save_meta_boxes( $post_id, $post ) {
        // Nonceチェック
        if ( ! isset( $_POST['kscss_meta_nonce'] ) || ! wp_verify_nonce( $_POST['kscss_meta_nonce'], 'kscss_save_meta' ) ) {
            return;
        }

        // 自動保存時はスキップ
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // 権限チェック
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // コード保存
        if ( isset( $_POST['kscss_code'] ) ) {
            update_post_meta( $post_id, '_kscss_code', wp_unslash( $_POST['kscss_code'] ) );
        }

        // 説明保存
        if ( isset( $_POST['kscss_description'] ) ) {
            update_post_meta( $post_id, '_kscss_description', sanitize_textarea_field( $_POST['kscss_description'] ) );
        }

        // コードタイプ保存
        if ( isset( $_POST['kscss_code_type'] ) ) {
            $allowed_types = array( 'php', 'html', 'css', 'javascript' );
            $code_type = sanitize_text_field( $_POST['kscss_code_type'] );
            if ( in_array( $code_type, $allowed_types, true ) ) {
                update_post_meta( $post_id, '_kscss_code_type', $code_type );
            }
        }

        // PHP実行設定保存
        $execute_php = isset( $_POST['kscss_execute_php'] ) ? '1' : '0';
        update_post_meta( $post_id, '_kscss_execute_php', $execute_php );

        // 自動エスケープ設定保存
        $auto_escape = isset( $_POST['kscss_auto_escape'] ) ? '1' : '0';
        update_post_meta( $post_id, '_kscss_auto_escape', $auto_escape );
    }

    /**
     * スニペット取得（ID指定）
     */
    public static function get_snippet_by_id( $id ) {
        $post = get_post( absint( $id ) );
        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return null;
        }
        return $post;
    }

    /**
     * スニペット取得（スラッグ指定）
     */
    public static function get_snippet_by_name( $name ) {
        $posts = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'name'           => sanitize_title( $name ),
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ) );

        return ! empty( $posts ) ? $posts[0] : null;
    }
}

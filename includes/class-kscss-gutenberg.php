<?php
/**
 * Gutenbergブロッククラス
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gutenbergブロック管理クラス
 */
class KSCSS_Gutenberg {

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
        add_action( 'init', array( $this, 'register_block' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
    }

    /**
     * ブロック登録
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        register_block_type( 'kscss/code-snippet', array(
            'editor_script'   => 'kscss-block-editor',
            'render_callback' => array( $this, 'render_block' ),
            'attributes'      => array(
                'snippetId' => array(
                    'type'    => 'number',
                    'default' => 0,
                ),
            ),
        ) );
    }

    /**
     * エディターアセット読み込み
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'kscss-block-editor',
            KSCSS_PLUGIN_URL . 'assets/js/block-editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-i18n', 'wp-data' ),
            KSCSS_VERSION,
            true
        );

        wp_enqueue_style(
            'kscss-block-editor',
            KSCSS_PLUGIN_URL . 'assets/css/block-editor.css',
            array(),
            KSCSS_VERSION
        );

        // スニペット一覧を取得してスクリプトに渡す
        $snippets = get_posts( array(
            'post_type'      => KSCSS_Post_Type::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $snippet_options = array(
            array(
                'value' => 0,
                'label' => __( 'スニペットを選択...', 'kashiwazaki-seo-code-snippet-shortcode' ),
            ),
        );

        foreach ( $snippets as $snippet ) {
            $code_type = get_post_meta( $snippet->ID, '_kscss_code_type', true );
            $code      = get_post_meta( $snippet->ID, '_kscss_code', true );

            $snippet_data = array(
                'value'    => $snippet->ID,
                'label'    => $snippet->post_title,
                'codeType' => $code_type,
            );

            // HTMLタイプの場合はコード内容も渡す
            if ( 'html' === $code_type ) {
                $snippet_data['code'] = $code;
            }

            $snippet_options[] = $snippet_data;
        }

        wp_localize_script( 'kscss-block-editor', 'kscssBlock', array(
            'snippets' => $snippet_options,
            'strings'  => array(
                'blockTitle'       => __( 'Kashiwazaki SEO Code Snippet Shortcode', 'kashiwazaki-seo-code-snippet-shortcode' ),
                'blockDescription' => __( 'コードスニペットを挿入します', 'kashiwazaki-seo-code-snippet-shortcode' ),
                'selectSnippet'    => __( 'スニペットを選択', 'kashiwazaki-seo-code-snippet-shortcode' ),
                'noSnippets'       => __( 'スニペットがありません', 'kashiwazaki-seo-code-snippet-shortcode' ),
                'previewText'      => __( 'プレビュー', 'kashiwazaki-seo-code-snippet-shortcode' ),
                'showPreview'      => __( 'HTMLプレビューを表示', 'kashiwazaki-seo-code-snippet-shortcode' ),
            ),
        ) );
    }

    /**
     * ブロック描画
     */
    public function render_block( $attributes ) {
        if ( empty( $attributes['snippetId'] ) ) {
            return '';
        }

        return do_shortcode( '[kscss_snippet id="' . absint( $attributes['snippetId'] ) . '"]' );
    }
}

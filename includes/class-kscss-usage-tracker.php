<?php
/**
 * 使用状況トラッカークラス
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 使用状況トラッカークラス
 */
class KSCSS_Usage_Tracker {

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
        add_action( 'wp_ajax_kscss_scan_usage', array( $this, 'ajax_scan_usage' ) );
        add_action( 'wp_ajax_kscss_clear_usage', array( $this, 'ajax_clear_usage' ) );
    }

    /**
     * 使用状況スキャン（AJAX）
     */
    public function ajax_scan_usage() {
        check_ajax_referer( 'kscss_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '権限がありません', 'kashiwazaki-seo-code-snippet-shortcode' ) );
        }

        $usage_data = $this->scan_all_content();
        update_option( 'kscss_usage_data', $usage_data );

        wp_send_json_success( array(
            'message' => __( 'スキャンが完了しました', 'kashiwazaki-seo-code-snippet-shortcode' ),
            'data'    => $usage_data,
        ) );
    }

    /**
     * 使用状況クリア（AJAX）
     */
    public function ajax_clear_usage() {
        check_ajax_referer( 'kscss_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( '権限がありません', 'kashiwazaki-seo-code-snippet-shortcode' ) );
        }

        delete_option( 'kscss_usage_data' );

        wp_send_json_success( array(
            'message' => __( '使用状況データをクリアしました', 'kashiwazaki-seo-code-snippet-shortcode' ),
        ) );
    }

    /**
     * 全コンテンツをスキャン
     */
    public function scan_all_content() {
        $usage_data = array();

        // すべてのスニペットを取得
        $snippets = get_posts( array(
            'post_type'      => KSCSS_Post_Type::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        if ( empty( $snippets ) ) {
            return $usage_data;
        }

        // スニペットIDとスラッグのマッピング
        $snippet_map = array();
        foreach ( $snippets as $snippet ) {
            $snippet_map[ $snippet->ID ] = $snippet->post_name;
            $usage_data[ $snippet->ID ]  = array();
        }

        // すべての投稿・ページをスキャン
        $posts = get_posts( array(
            'post_type'      => array( 'post', 'page' ),
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ) );

        foreach ( $posts as $post ) {
            $content = $post->post_content;

            // ID指定のショートコードを検索
            if ( preg_match_all( '/\[kscss_snippet\s+id=["\']?(\d+)["\']?\s*\]/', $content, $matches ) ) {
                foreach ( $matches[1] as $snippet_id ) {
                    $snippet_id = (int) $snippet_id;
                    if ( isset( $usage_data[ $snippet_id ] ) ) {
                        if ( ! in_array( $post->ID, $usage_data[ $snippet_id ], true ) ) {
                            $usage_data[ $snippet_id ][] = $post->ID;
                        }
                    }
                }
            }

            // name指定のショートコードを検索
            if ( preg_match_all( '/\[kscss_snippet\s+name=["\']?([^"\'\]]+)["\']?\s*\]/', $content, $matches ) ) {
                foreach ( $matches[1] as $snippet_name ) {
                    // スラッグからIDを探す
                    $snippet_id = array_search( $snippet_name, $snippet_map, true );
                    if ( false !== $snippet_id && isset( $usage_data[ $snippet_id ] ) ) {
                        if ( ! in_array( $post->ID, $usage_data[ $snippet_id ], true ) ) {
                            $usage_data[ $snippet_id ][] = $post->ID;
                        }
                    }
                }
            }

            // Gutenbergブロックを検索
            if ( has_blocks( $content ) ) {
                $blocks = parse_blocks( $content );
                $this->scan_blocks_for_usage( $blocks, $post->ID, $usage_data );
            }
        }

        return $usage_data;
    }

    /**
     * ブロックを再帰的にスキャン
     */
    private function scan_blocks_for_usage( $blocks, $post_id, &$usage_data ) {
        foreach ( $blocks as $block ) {
            if ( 'kscss/code-snippet' === $block['blockName'] ) {
                if ( isset( $block['attrs']['snippetId'] ) ) {
                    $snippet_id = (int) $block['attrs']['snippetId'];
                    if ( isset( $usage_data[ $snippet_id ] ) ) {
                        if ( ! in_array( $post_id, $usage_data[ $snippet_id ], true ) ) {
                            $usage_data[ $snippet_id ][] = $post_id;
                        }
                    }
                }
            }

            // ネストされたブロックをスキャン
            if ( ! empty( $block['innerBlocks'] ) ) {
                $this->scan_blocks_for_usage( $block['innerBlocks'], $post_id, $usage_data );
            }
        }
    }

    /**
     * スニペットの使用状況取得
     */
    public static function get_usage( $snippet_id ) {
        $usage_data = get_option( 'kscss_usage_data', array() );
        return isset( $usage_data[ $snippet_id ] ) ? $usage_data[ $snippet_id ] : array();
    }

    /**
     * 未使用スニペット取得
     */
    public static function get_unused_snippets() {
        $usage_data = get_option( 'kscss_usage_data', array() );
        $unused     = array();

        $snippets = get_posts( array(
            'post_type'      => KSCSS_Post_Type::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        foreach ( $snippets as $snippet ) {
            if ( ! isset( $usage_data[ $snippet->ID ] ) || empty( $usage_data[ $snippet->ID ] ) ) {
                $unused[] = $snippet;
            }
        }

        return $unused;
    }

    /**
     * 全スニペットの使用状況取得
     */
    public static function get_all_usage() {
        return get_option( 'kscss_usage_data', array() );
    }
}

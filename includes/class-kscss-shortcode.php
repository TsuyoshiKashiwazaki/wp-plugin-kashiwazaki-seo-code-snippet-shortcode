<?php
/**
 * ショートコードクラス
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ショートコード管理クラス
 */
class KSCSS_Shortcode {

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
        add_shortcode( 'kscss_snippet', array( $this, 'render_shortcode' ) );
    }

    /**
     * ショートコード描画
     *
     * @param array $atts ショートコード属性
     * @return string 出力HTML
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id'   => '',
            'name' => '',
        ), $atts, 'kscss_snippet' );

        // スニペット取得
        $snippet = null;
        if ( ! empty( $atts['id'] ) ) {
            $snippet = KSCSS_Post_Type::get_snippet_by_id( $atts['id'] );
        } elseif ( ! empty( $atts['name'] ) ) {
            $snippet = KSCSS_Post_Type::get_snippet_by_name( $atts['name'] );
        }

        if ( ! $snippet || 'publish' !== $snippet->post_status ) {
            return '';
        }

        // メタデータ取得
        $code_type    = get_post_meta( $snippet->ID, '_kscss_code_type', true );
        $execute_php  = get_post_meta( $snippet->ID, '_kscss_execute_php', true );
        $auto_escape  = get_post_meta( $snippet->ID, '_kscss_auto_escape', true );

        // HTMLタイプの場合はpost_content（ブロックエディタの内容）を使用
        if ( 'html' === $code_type ) {
            $code = $snippet->post_content;
        } else {
            $code = get_post_meta( $snippet->ID, '_kscss_code', true );
        }

        if ( empty( $code ) ) {
            return '';
        }

        // グローバル設定チェック
        $options = get_option( 'kscss_settings', array() );
        $php_allowed = ! empty( $options['enable_php_execution'] );

        // 出力処理
        $output = '';

        switch ( $code_type ) {
            case 'php':
                if ( $php_allowed && '1' === $execute_php ) {
                    // PHPコード実行
                    ob_start();
                    try {
                        eval( '?>' . $code );
                    } catch ( Throwable $e ) {
                        if ( current_user_can( 'manage_options' ) ) {
                            $output = '<!-- KSCSS Error: ' . esc_html( $e->getMessage() ) . ' -->';
                        }
                    }
                    $output .= ob_get_clean();
                } else {
                    // エスケープして表示
                    if ( '1' === $auto_escape ) {
                        $output = esc_html( $code );
                    } else {
                        $output = $code;
                    }
                }
                break;

            case 'css':
                $output = '<style>' . $this->sanitize_css( $code ) . '</style>';
                break;

            case 'javascript':
                if ( '1' === $auto_escape ) {
                    $output = '<script>' . esc_js( $code ) . '</script>';
                } else {
                    $output = '<script>' . $code . '</script>';
                }
                break;

            case 'html':
            default:
                if ( '1' === $auto_escape ) {
                    $output = wp_kses_post( $code );
                } else {
                    $output = $code;
                }
                break;
        }

        // 使用状況を記録
        if ( ! is_admin() ) {
            $this->track_usage( $snippet->ID );
        }

        return $output;
    }

    /**
     * CSS サニタイズ
     *
     * @param string $css CSSコード
     * @return string サニタイズ済みCSS
     */
    private function sanitize_css( $css ) {
        // 基本的なサニタイズ（scriptタグなどを除去）
        $css = preg_replace( '/<script[^>]*>.*?<\/script>/si', '', $css );
        $css = preg_replace( '/javascript\s*:/i', '', $css );
        $css = preg_replace( '/expression\s*\(/i', '', $css );
        return $css;
    }

    /**
     * 使用状況記録
     *
     * @param int $snippet_id スニペットID
     */
    private function track_usage( $snippet_id ) {
        global $post;

        if ( ! $post || ! is_singular() ) {
            return;
        }

        $usage_data = get_option( 'kscss_usage_data', array() );

        if ( ! isset( $usage_data[ $snippet_id ] ) ) {
            $usage_data[ $snippet_id ] = array();
        }

        $post_id = $post->ID;
        if ( ! in_array( $post_id, $usage_data[ $snippet_id ], true ) ) {
            $usage_data[ $snippet_id ][] = $post_id;
            update_option( 'kscss_usage_data', $usage_data );
        }
    }
}

<?php
/**
 * 自動挿入クラス
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * スニペット自動挿入管理クラス
 */
class KSCSS_Auto_Insert {

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
        // 優先度11で実行（wpautop=10の直後、他のプラグインより前）
        add_filter( 'the_content', array( $this, 'insert_snippets_in_content' ), 11 );
    }

    /**
     * 本文にスニペットを挿入
     */
    public function insert_snippets_in_content( $content ) {
        // 再帰呼び出し防止
        static $is_running = false;
        if ( $is_running ) {
            return $content;
        }

        if ( ! is_singular() ) {
            return $content;
        }

        global $post;
        $rules = get_option( 'kscss_auto_insert', array() );

        if ( empty( $rules ) ) {
            return $content;
        }

        $is_running = true;

        $before_content = '';
        $after_content = '';

        foreach ( $rules as $rule ) {
            // 投稿タイプのチェック（複数対応）
            $post_types = isset( $rule['post_types'] ) ? (array) $rule['post_types'] : array();
            if ( ! in_array( $post->post_type, $post_types, true ) ) {
                continue;
            }

            $snippet_output = do_shortcode( '[kscss_snippet id="' . $rule['snippet_id'] . '"]' );
            $position = isset( $rule['position'] ) ? $rule['position'] : 'before_content';

            switch ( $position ) {
                case 'before_content':
                    $before_content .= $snippet_output;
                    break;

                case 'after_content':
                    $after_content .= $snippet_output;
                    break;

                case 'after_first_paragraph':
                    $content = $this->insert_after_first_paragraph( $content, $snippet_output );
                    break;

                case 'before_first_h2':
                    $content = $this->insert_before_first_h2( $content, $snippet_output );
                    break;

                case 'after_first_h2':
                    $content = $this->insert_after_first_h2( $content, $snippet_output );
                    break;

                case 'before_last_paragraph':
                    $content = $this->insert_before_last_paragraph( $content, $snippet_output );
                    break;

                case 'after_last_paragraph':
                    $content = $this->insert_after_last_paragraph( $content, $snippet_output );
                    break;

                case 'before_last_h2':
                    $content = $this->insert_before_last_h2( $content, $snippet_output );
                    break;

                case 'after_last_h2':
                    $content = $this->insert_after_last_h2( $content, $snippet_output );
                    break;

                case 'before_last_h3':
                    $content = $this->insert_before_last_h3( $content, $snippet_output );
                    break;

                case 'after_last_h3':
                    $content = $this->insert_after_last_h3( $content, $snippet_output );
                    break;
            }
        }

        $is_running = false;
        return $before_content . $content . $after_content;
    }

    /**
     * 最初の段落の後に挿入
     */
    private function insert_after_first_paragraph( $content, $snippet ) {
        // </p>を探して最初の段落の後に挿入
        $pattern = '/<\/p>/i';
        if ( preg_match( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            $position = $matches[0][1] + strlen( $matches[0][0] );
            return substr( $content, 0, $position ) . $snippet . substr( $content, $position );
        }
        // 段落がなければ先頭に挿入
        return $snippet . $content;
    }

    /**
     * 最初のH2見出しの前に挿入
     */
    private function insert_before_first_h2( $content, $snippet ) {
        $pattern = '/<h2[^>]*>/i';
        if ( preg_match( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            $position = $matches[0][1];
            return substr( $content, 0, $position ) . $snippet . substr( $content, $position );
        }
        // H2がなければ末尾に挿入
        return $content . $snippet;
    }

    /**
     * 最初のH2見出しの後に挿入
     */
    private function insert_after_first_h2( $content, $snippet ) {
        $pattern = '/<\/h2>/i';
        if ( preg_match( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            $position = $matches[0][1] + strlen( $matches[0][0] );
            return substr( $content, 0, $position ) . $snippet . substr( $content, $position );
        }
        // H2がなければ末尾に挿入
        return $content . $snippet;
    }

    /**
     * 最後の段落の前に挿入
     * 他のプラグインが追加したコンテンツを除外するため、元の投稿コンテンツを基準にする
     */
    private function insert_before_last_paragraph( $content, $snippet ) {
        global $post;

        // 元の投稿コンテンツにwpautopを適用して段落を特定
        $original_processed = wpautop( $post->post_content );

        // 元のコンテンツから最後の「テキストがある」段落を抽出
        $pattern = '/<p[^>]*>(.*?)<\/p>/is';
        if ( preg_match_all( $pattern, $original_processed, $orig_matches ) ) {
            // 後ろから順にテキストがある段落を探す
            $last_para_text_clean = '';
            for ( $i = count( $orig_matches[1] ) - 1; $i >= 0; $i-- ) {
                $text = wp_strip_all_tags( $orig_matches[1][ $i ] );
                $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                $text = trim( $text );
                if ( ! empty( $text ) ) {
                    $last_para_text_clean = $text;
                    break;
                }
            }

            if ( ! empty( $last_para_text_clean ) ) {
                // 絵文字を除去してテキストのみで検索（より安定した検索のため）
                $text_without_emoji = preg_replace( '/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', '', $last_para_text_clean );
                $text_without_emoji = trim( $text_without_emoji );
                // テキストの最初の30文字を使用
                $search_text = mb_substr( $text_without_emoji, 0, 30 );
                // strposで位置を探し、その前後の<p>タグを特定する
                $text_pos = mb_strpos( $content, $search_text );
                if ( false !== $text_pos ) {
                    // この位置より前にある最後の<p>タグを探す
                    $before_content = substr( $content, 0, $text_pos );
                    $last_p_pos = strrpos( $before_content, '<p' );
                    if ( false !== $last_p_pos ) {
                        return substr( $content, 0, $last_p_pos ) . $snippet . substr( $content, $last_p_pos );
                    }
                }
            }
        }

        // フォールバック：通常の最後の<p>を探す
        $pattern = '/<p[^>]*>/i';
        if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            $last_match = end( $matches[0] );
            $position = $last_match[1];
            return substr( $content, 0, $position ) . $snippet . substr( $content, $position );
        }
        // 段落がなければ末尾に挿入
        return $content . $snippet;
    }

    /**
     * 最後の段落の後に挿入
     * 他のプラグインが追加したコンテンツを除外するため、元の投稿コンテンツを基準にする
     */
    private function insert_after_last_paragraph( $content, $snippet ) {
        global $post;

        // 元の投稿コンテンツにwpautopを適用して段落を特定
        $original_processed = wpautop( $post->post_content );

        // 元のコンテンツから最後の「テキストがある」段落を抽出
        $pattern = '/<p[^>]*>(.*?)<\/p>/is';
        if ( preg_match_all( $pattern, $original_processed, $orig_matches ) ) {
            // 後ろから順にテキストがある段落を探す
            $last_para_text_clean = '';
            for ( $i = count( $orig_matches[1] ) - 1; $i >= 0; $i-- ) {
                $text = wp_strip_all_tags( $orig_matches[1][ $i ] );
                $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                $text = trim( $text );
                if ( ! empty( $text ) ) {
                    $last_para_text_clean = $text;
                    break;
                }
            }

            if ( ! empty( $last_para_text_clean ) ) {
                // 絵文字を除去してテキストのみで検索（より安定した検索のため）
                $text_without_emoji = preg_replace( '/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', '', $last_para_text_clean );
                $text_without_emoji = trim( $text_without_emoji );
                // テキストの最初の30文字を使用
                $search_text = mb_substr( $text_without_emoji, 0, 30 );
                // strposで位置を探し、その後の</p>タグを特定する
                $text_pos = mb_strpos( $content, $search_text );
                if ( false !== $text_pos ) {
                    // この位置より後にある最初の</p>タグを探す
                    $after_content = substr( $content, $text_pos );
                    $close_p_pos = strpos( $after_content, '</p>' );
                    if ( false !== $close_p_pos ) {
                        $insert_pos = $text_pos + $close_p_pos + 4; // 4 = strlen('</p>')
                        return substr( $content, 0, $insert_pos ) . $snippet . substr( $content, $insert_pos );
                    }
                }
            }
        }

        // フォールバック：通常の最後の</p>を探す
        $pattern = '/<\/p>/i';
        if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            $last_match = end( $matches[0] );
            $position = $last_match[1] + strlen( $last_match[0] );
            return substr( $content, 0, $position ) . $snippet . substr( $content, $position );
        }
        // 段落がなければ末尾に挿入
        return $content . $snippet;
    }

    /**
     * 最後のH2見出しの前に挿入
     */
    private function insert_before_last_h2( $content, $snippet ) {
        $pattern = '/<h2[^>]*>/i';
        if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            $last_match = end( $matches[0] );
            $position = $last_match[1];
            return substr( $content, 0, $position ) . $snippet . substr( $content, $position );
        }
        // H2がなければ末尾に挿入
        return $content . $snippet;
    }

    /**
     * 最後のH2見出しの後に挿入
     */
    private function insert_after_last_h2( $content, $snippet ) {
        $pattern = '/<\/h2>/i';
        if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            $last_match = end( $matches[0] );
            $position = $last_match[1] + strlen( $last_match[0] );
            return substr( $content, 0, $position ) . $snippet . substr( $content, $position );
        }
        // H2がなければ末尾に挿入
        return $content . $snippet;
    }

    /**
     * 最後のH3見出しの前に挿入
     */
    private function insert_before_last_h3( $content, $snippet ) {
        $pattern = '/<h3[^>]*>/i';
        if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            $last_match = end( $matches[0] );
            $position = $last_match[1];
            return substr( $content, 0, $position ) . $snippet . substr( $content, $position );
        }
        // H3がなければ末尾に挿入
        return $content . $snippet;
    }

    /**
     * 最後のH3見出しの後に挿入
     */
    private function insert_after_last_h3( $content, $snippet ) {
        $pattern = '/<\/h3>/i';
        if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
            $last_match = end( $matches[0] );
            $position = $last_match[1] + strlen( $last_match[0] );
            return substr( $content, 0, $position ) . $snippet . substr( $content, $position );
        }
        // H3がなければ末尾に挿入
        return $content . $snippet;
    }
}

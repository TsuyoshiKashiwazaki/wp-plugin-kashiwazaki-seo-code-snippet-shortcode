<?php
/**
 * クラシックエディタークラス
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * クラシックエディター対応クラス
 */
class KSCSS_Classic_Editor {

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
        add_action( 'admin_init', array( $this, 'add_editor_button' ) );
        add_action( 'admin_footer', array( $this, 'render_modal' ) );
    }

    /**
     * エディターボタン追加
     */
    public function add_editor_button() {
        if ( ! current_user_can( 'edit_posts' ) || ! current_user_can( 'edit_pages' ) ) {
            return;
        }

        if ( 'true' === get_user_option( 'rich_editing' ) ) {
            add_filter( 'mce_external_plugins', array( $this, 'add_tinymce_plugin' ) );
            add_filter( 'mce_buttons', array( $this, 'register_tinymce_button' ) );
        }
    }

    /**
     * TinyMCEプラグイン追加
     */
    public function add_tinymce_plugin( $plugins ) {
        // ブロックエディタ使用時はTinyMCEプラグインを読み込まない
        global $post;
        if ( $post && function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ) ) {
            return $plugins;
        }

        $plugins['kscss_button'] = KSCSS_PLUGIN_URL . 'assets/js/tinymce-plugin.js';
        return $plugins;
    }

    /**
     * TinyMCEボタン登録
     */
    public function register_tinymce_button( $buttons ) {
        // ブロックエディタ使用時はボタンを追加しない
        global $post;
        if ( $post && function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ) ) {
            return $buttons;
        }

        array_push( $buttons, 'kscss_button' );
        return $buttons;
    }

    /**
     * モーダル描画
     */
    public function render_modal() {
        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->base, array( 'post', 'page' ), true ) ) {
            return;
        }

        // スニペット編集画面では表示しない
        if ( KSCSS_Post_Type::POST_TYPE === $screen->post_type ) {
            return;
        }

        $snippets = get_posts( array(
            'post_type'      => KSCSS_Post_Type::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        ?>
        <div id="kscss-modal" class="kscss-modal" style="display: none;">
            <div class="kscss-modal-content">
                <div class="kscss-modal-header">
                    <h2><?php esc_html_e( 'コードスニペットを挿入', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></h2>
                    <button type="button" class="kscss-modal-close">&times;</button>
                </div>
                <div class="kscss-modal-body">
                    <div class="kscss-modal-filters">
                        <input type="text" id="kscss-search" placeholder="<?php esc_attr_e( '検索...', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>" class="regular-text">
                    </div>
                    <div class="kscss-snippet-list">
                        <?php if ( empty( $snippets ) ) : ?>
                        <p class="kscss-no-snippets">
                            <?php esc_html_e( 'スニペットがありません。', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . KSCSS_Post_Type::POST_TYPE ) ); ?>">
                                <?php esc_html_e( '新規作成', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                            </a>
                        </p>
                        <?php else : ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( '名前', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                                    <th><?php esc_html_e( 'タイプ', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                                    <th><?php esc_html_e( '説明', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                                    <th><?php esc_html_e( '操作', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $snippets as $snippet ) :
                                    $code_type   = get_post_meta( $snippet->ID, '_kscss_code_type', true );
                                    $description = get_post_meta( $snippet->ID, '_kscss_description', true );
                                ?>
                                <tr class="kscss-snippet-row" data-id="<?php echo esc_attr( $snippet->ID ); ?>">
                                    <td class="kscss-snippet-title"><?php echo esc_html( $snippet->post_title ); ?></td>
                                    <td><span class="kscss-type-badge kscss-type-<?php echo esc_attr( $code_type ); ?>"><?php echo esc_html( strtoupper( $code_type ) ); ?></span></td>
                                    <td class="kscss-snippet-description"><?php echo esc_html( wp_trim_words( $description, 10 ) ); ?></td>
                                    <td>
                                        <button type="button" class="button kscss-insert-snippet" data-id="<?php echo esc_attr( $snippet->ID ); ?>">
                                            <?php esc_html_e( '挿入', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .kscss-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.6);
                z-index: 100000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .kscss-modal-content {
                background: #fff;
                width: 90%;
                max-width: 800px;
                max-height: 80vh;
                border-radius: 4px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }
            .kscss-modal-header {
                padding: 15px 20px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .kscss-modal-header h2 {
                margin: 0;
                font-size: 18px;
            }
            .kscss-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }
            .kscss-modal-close:hover {
                color: #000;
            }
            .kscss-modal-body {
                padding: 20px;
                overflow-y: auto;
            }
            .kscss-modal-filters {
                margin-bottom: 15px;
            }
            .kscss-modal-filters input {
                width: 100%;
            }
            .kscss-snippet-list table {
                margin: 0;
            }
            .kscss-type-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
            }
            .kscss-type-php { background: #8892bf; color: #fff; }
            .kscss-type-html { background: #e44d26; color: #fff; }
            .kscss-type-css { background: #264de4; color: #fff; }
            .kscss-type-javascript { background: #f7df1e; color: #000; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            // モーダルを閉じる
            $('.kscss-modal-close').on('click', function() {
                $('#kscss-modal').hide();
            });

            // モーダル外クリックで閉じる
            $('#kscss-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // 検索フィルター
            $('#kscss-search').on('keyup', function() {
                var searchText = $(this).val().toLowerCase();

                $('.kscss-snippet-row').each(function() {
                    var $row = $(this);
                    var title = $row.find('.kscss-snippet-title').text().toLowerCase();
                    var description = $row.find('.kscss-snippet-description').text().toLowerCase();

                    if (title.indexOf(searchText) > -1 || description.indexOf(searchText) > -1) {
                        $row.show();
                    } else {
                        $row.hide();
                    }
                });
            });

            // スニペット挿入
            $('.kscss-insert-snippet').on('click', function() {
                var snippetId = $(this).data('id');
                var shortcode = '[kscss_snippet id="' + snippetId + '"]';

                // TinyMCEに挿入
                if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                    tinymce.activeEditor.execCommand('mceInsertContent', false, shortcode);
                } else {
                    // テキストエディタに挿入
                    var textarea = document.getElementById('content');
                    if (textarea) {
                        var start = textarea.selectionStart;
                        var end = textarea.selectionEnd;
                        var text = textarea.value;
                        textarea.value = text.substring(0, start) + shortcode + text.substring(end);
                        textarea.selectionStart = textarea.selectionEnd = start + shortcode.length;
                        textarea.focus();
                    }
                }

                $('#kscss-modal').hide();
            });
        });
        </script>
        <?php
    }
}

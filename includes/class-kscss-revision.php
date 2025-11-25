<?php
/**
 * リビジョン管理クラス
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * リビジョン管理クラス
 */
class KSCSS_Revision {

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
        add_filter( '_wp_post_revision_fields', array( $this, 'add_revision_fields' ), 10, 2 );
        add_action( 'save_post_' . KSCSS_Post_Type::POST_TYPE, array( $this, 'save_revision_meta' ), 20, 2 );
        add_action( 'wp_restore_post_revision', array( $this, 'restore_revision' ), 10, 2 );
        add_filter( '_wp_post_revision_field_kscss_code', array( $this, 'display_revision_field' ), 10, 4 );
        add_action( 'add_meta_boxes', array( $this, 'add_revision_meta_box' ) );
    }

    /**
     * リビジョンフィールド追加
     */
    public function add_revision_fields( $fields, $post ) {
        if ( isset( $post['post_type'] ) && KSCSS_Post_Type::POST_TYPE === $post['post_type'] ) {
            $fields['kscss_code'] = __( 'コード', 'kashiwazaki-seo-code-snippet-shortcode' );
        }
        return $fields;
    }

    /**
     * リビジョンメタ保存
     */
    public function save_revision_meta( $post_id, $post ) {
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // 最新リビジョン取得
        $revisions = wp_get_post_revisions( $post_id, array(
            'posts_per_page' => 1,
            'orderby'        => 'ID',
            'order'          => 'DESC',
        ) );

        if ( empty( $revisions ) ) {
            return;
        }

        $revision    = array_shift( $revisions );
        $revision_id = $revision->ID;

        // メタデータをリビジョンにコピー
        $meta_keys = array(
            '_kscss_code',
            '_kscss_description',
            '_kscss_code_type',
            '_kscss_execute_php',
            '_kscss_auto_escape',
        );

        foreach ( $meta_keys as $meta_key ) {
            $meta_value = get_post_meta( $post_id, $meta_key, true );
            if ( '' !== $meta_value ) {
                update_metadata( 'post', $revision_id, $meta_key, $meta_value );
            }
        }
    }

    /**
     * リビジョン復元
     */
    public function restore_revision( $post_id, $revision_id ) {
        $post = get_post( $post_id );
        if ( KSCSS_Post_Type::POST_TYPE !== $post->post_type ) {
            return;
        }

        // メタデータを復元
        $meta_keys = array(
            '_kscss_code',
            '_kscss_description',
            '_kscss_code_type',
            '_kscss_execute_php',
            '_kscss_auto_escape',
        );

        foreach ( $meta_keys as $meta_key ) {
            $meta_value = get_metadata( 'post', $revision_id, $meta_key, true );
            if ( '' !== $meta_value ) {
                update_post_meta( $post_id, $meta_key, $meta_value );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }
    }

    /**
     * リビジョンフィールド表示
     */
    public function display_revision_field( $value, $field, $revision, $type ) {
        if ( 'kscss_code' === $field ) {
            $value = get_metadata( 'post', $revision->ID, '_kscss_code', true );
        }
        return $value;
    }

    /**
     * リビジョンメタボックス追加
     */
    public function add_revision_meta_box() {
        add_meta_box(
            'kscss_revisions',
            __( '変更履歴', 'kashiwazaki-seo-code-snippet-shortcode' ),
            array( $this, 'render_revision_meta_box' ),
            KSCSS_Post_Type::POST_TYPE,
            'normal',
            'low'
        );
    }

    /**
     * リビジョンメタボックス描画
     */
    public function render_revision_meta_box( $post ) {
        $revisions = wp_get_post_revisions( $post->ID, array(
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        if ( empty( $revisions ) ) {
            echo '<p>' . esc_html__( '変更履歴はありません', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</p>';
            return;
        }

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( '日時', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</th>';
        echo '<th>' . esc_html__( '作成者', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</th>';
        echo '<th>' . esc_html__( '操作', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ( $revisions as $revision ) {
            $author      = get_userdata( $revision->post_author );
            $author_name = $author ? $author->display_name : __( '不明', 'kashiwazaki-seo-code-snippet-shortcode' );
            $date        = get_the_modified_date( 'Y-m-d H:i:s', $revision );
            $restore_url = wp_nonce_url(
                admin_url( 'revision.php?action=restore&revision=' . $revision->ID ),
                'restore-post_' . $revision->ID
            );
            $compare_url = admin_url( 'revision.php?revision=' . $revision->ID );

            echo '<tr>';
            echo '<td>' . esc_html( $date ) . '</td>';
            echo '<td>' . esc_html( $author_name ) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url( $compare_url ) . '">' . esc_html__( '表示', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</a>';
            echo ' | ';
            echo '<a href="' . esc_url( $restore_url ) . '">' . esc_html__( '復元', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        if ( count( $revisions ) >= 10 ) {
            $all_revisions_url = admin_url( 'revision.php?revision=' . $post->ID );
            echo '<p><a href="' . esc_url( $all_revisions_url ) . '">' .
                esc_html__( 'すべての変更履歴を表示', 'kashiwazaki-seo-code-snippet-shortcode' ) . '</a></p>';
        }
    }
}

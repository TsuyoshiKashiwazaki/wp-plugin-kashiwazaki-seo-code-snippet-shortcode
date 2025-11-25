<?php
/**
 * 使用状況ビュー
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$usage_data     = KSCSS_Usage_Tracker::get_all_usage();
$unused_snippets = KSCSS_Usage_Tracker::get_unused_snippets();

$snippets = get_posts( array(
    'post_type'      => KSCSS_Post_Type::POST_TYPE,
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
) );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Kashiwazaki SEO Code Snippet Shortcode - 使用状況', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></h1>

    <div class="kscss-usage-actions">
        <button type="button" id="kscss-scan-usage" class="button button-primary">
            <?php esc_html_e( '使用状況をスキャン', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
        </button>
        <button type="button" id="kscss-clear-usage" class="button">
            <?php esc_html_e( 'データをクリア', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
        </button>
        <span class="spinner"></span>
    </div>

    <div class="kscss-usage-notice" style="display: none;"></div>

    <?php if ( ! empty( $unused_snippets ) ) : ?>
    <div class="kscss-unused-section">
        <h2><?php esc_html_e( '未使用のスニペット', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></h2>
        <p class="description">
            <?php esc_html_e( '以下のスニペットは現在どのページでも使用されていません。', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
        </p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'スニペット名', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                    <th><?php esc_html_e( 'タイプ', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                    <th><?php esc_html_e( '作成日', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                    <th><?php esc_html_e( '操作', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $unused_snippets as $snippet ) :
                    $code_type = get_post_meta( $snippet->ID, '_kscss_code_type', true );
                    $edit_url  = admin_url( 'post.php?post=' . $snippet->ID . '&action=edit' );
                ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url( $edit_url ); ?>">
                            <?php echo esc_html( $snippet->post_title ); ?>
                        </a>
                    </td>
                    <td>
                        <span class="kscss-type-badge kscss-type-<?php echo esc_attr( $code_type ); ?>">
                            <?php echo esc_html( strtoupper( $code_type ?: 'HTML' ) ); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html( get_the_date( '', $snippet ) ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">
                            <?php esc_html_e( '編集', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="kscss-usage-section">
        <h2><?php esc_html_e( 'スニペット使用状況一覧', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></h2>

        <?php if ( empty( $snippets ) ) : ?>
        <p><?php esc_html_e( 'スニペットがありません。', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></p>
        <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-snippet"><?php esc_html_e( 'スニペット', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                    <th class="column-type"><?php esc_html_e( 'タイプ', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                    <th class="column-count"><?php esc_html_e( '使用数', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                    <th class="column-pages"><?php esc_html_e( '使用ページ', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $snippets as $snippet ) :
                    $code_type  = get_post_meta( $snippet->ID, '_kscss_code_type', true );
                    $used_pages = isset( $usage_data[ $snippet->ID ] ) ? $usage_data[ $snippet->ID ] : array();
                    $edit_url   = admin_url( 'post.php?post=' . $snippet->ID . '&action=edit' );
                ?>
                <tr>
                    <td class="column-snippet">
                        <a href="<?php echo esc_url( $edit_url ); ?>">
                            <?php echo esc_html( $snippet->post_title ); ?>
                        </a>
                    </td>
                    <td class="column-type">
                        <span class="kscss-type-badge kscss-type-<?php echo esc_attr( $code_type ); ?>">
                            <?php echo esc_html( strtoupper( $code_type ?: 'HTML' ) ); ?>
                        </span>
                    </td>
                    <td class="column-count">
                        <?php if ( empty( $used_pages ) ) : ?>
                            <span class="kscss-unused-badge"><?php esc_html_e( '未使用', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></span>
                        <?php else : ?>
                            <?php echo esc_html( count( $used_pages ) ); ?>
                        <?php endif; ?>
                    </td>
                    <td class="column-pages">
                        <?php if ( ! empty( $used_pages ) ) : ?>
                            <ul class="kscss-used-pages-list">
                                <?php
                                $display_count = 0;
                                foreach ( $used_pages as $page_id ) :
                                    $page = get_post( $page_id );
                                    if ( ! $page ) {
                                        continue;
                                    }
                                    $display_count++;
                                    if ( $display_count > 5 ) {
                                        echo '<li class="kscss-more-pages">... ' .
                                            sprintf(
                                                esc_html__( '他 %d ページ', 'kashiwazaki-seo-code-snippet-shortcode' ),
                                                count( $used_pages ) - 5
                                            ) . '</li>';
                                        break;
                                    }
                                    $page_edit_url = admin_url( 'post.php?post=' . $page_id . '&action=edit' );
                                ?>
                                <li>
                                    <a href="<?php echo esc_url( $page_edit_url ); ?>" target="_blank">
                                        <?php echo esc_html( $page->post_title ); ?>
                                    </a>
                                    <span class="kscss-page-type">(<?php echo esc_html( get_post_type_object( $page->post_type )->labels->singular_name ); ?>)</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // スキャン
    $('#kscss-scan-usage').on('click', function() {
        var $button = $(this);
        var $spinner = $button.siblings('.spinner');
        var $notice = $('.kscss-usage-notice');

        $button.prop('disabled', true);
        $spinner.addClass('is-active');

        $.ajax({
            url: kscssAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'kscss_scan_usage',
                nonce: kscssAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $notice.removeClass('notice-error').addClass('notice notice-success').html('<p>' + response.data.message + '</p>').show();
                    location.reload();
                } else {
                    $notice.removeClass('notice-success').addClass('notice notice-error').html('<p>' + response.data + '</p>').show();
                }
            },
            error: function() {
                $notice.removeClass('notice-success').addClass('notice notice-error').html('<p><?php esc_html_e( 'エラーが発生しました', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></p>').show();
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // クリア
    $('#kscss-clear-usage').on('click', function() {
        if (!confirm('<?php esc_html_e( '使用状況データをクリアしますか？', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>')) {
            return;
        }

        var $button = $(this);
        var $spinner = $button.siblings('.spinner');
        var $notice = $('.kscss-usage-notice');

        $button.prop('disabled', true);
        $spinner.addClass('is-active');

        $.ajax({
            url: kscssAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'kscss_clear_usage',
                nonce: kscssAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $notice.removeClass('notice-error').addClass('notice notice-success').html('<p>' + response.data.message + '</p>').show();
                    location.reload();
                } else {
                    $notice.removeClass('notice-success').addClass('notice notice-error').html('<p>' + response.data + '</p>').show();
                }
            },
            error: function() {
                $notice.removeClass('notice-success').addClass('notice notice-error').html('<p><?php esc_html_e( 'エラーが発生しました', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></p>').show();
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});
</script>

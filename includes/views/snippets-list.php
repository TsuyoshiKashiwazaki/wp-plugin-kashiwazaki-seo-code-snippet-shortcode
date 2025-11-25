<?php
/**
 * スニペット一覧ビュー
 *
 * @package Kashiwazaki_SEO_Code_Snippet_Shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 検索・フィルター処理
$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$paged  = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

$args = array(
    'post_type'      => KSCSS_Post_Type::POST_TYPE,
    'posts_per_page' => 20,
    'paged'          => $paged,
    'orderby'        => 'title',
    'order'          => 'ASC',
);

if ( ! empty( $search ) ) {
    $args['s'] = $search;
}

$snippets_query = new WP_Query( $args );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e( 'Kashiwazaki SEO Code Snippet Shortcode', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
    </h1>
    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . KSCSS_Post_Type::POST_TYPE ) ); ?>" class="page-title-action">
        <?php esc_html_e( '新規追加', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
    </a>
    <hr class="wp-header-end">

    <div class="kscss-list-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="kscss-snippets">

            <div class="kscss-filter-row">
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'スニペット検索...', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>" class="regular-text">

                <button type="submit" class="button"><?php esc_html_e( '検索', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></button>

                <?php if ( ! empty( $search ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kscss-snippets' ) ); ?>" class="button">
                    <?php esc_html_e( 'リセット', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ( $snippets_query->have_posts() ) : ?>
    <table class="wp-list-table widefat fixed striped kscss-snippets-table">
        <thead>
            <tr>
                <th class="column-title"><?php esc_html_e( 'タイトル', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                <th class="column-type"><?php esc_html_e( 'タイプ', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                <th class="column-shortcode"><?php esc_html_e( 'ショートコード', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
                <th class="column-date"><?php esc_html_e( '更新日', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php while ( $snippets_query->have_posts() ) : $snippets_query->the_post();
                $post_id    = get_the_ID();
                $code_type  = get_post_meta( $post_id, '_kscss_code_type', true );
                $shortcode  = '[kscss_snippet id="' . $post_id . '"]';
                $edit_url   = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
                $delete_url = get_delete_post_link( $post_id );
            ?>
            <tr>
                <td class="column-title">
                    <strong>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="row-title">
                            <?php the_title(); ?>
                        </a>
                    </strong>
                    <div class="row-actions">
                        <span class="edit">
                            <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( '編集', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></a> |
                        </span>
                        <span class="trash">
                            <a href="<?php echo esc_url( $delete_url ); ?>" class="submitdelete"><?php esc_html_e( 'ゴミ箱', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></a>
                        </span>
                    </div>
                </td>
                <td class="column-type">
                    <span class="kscss-type-badge kscss-type-<?php echo esc_attr( $code_type ); ?>">
                        <?php echo esc_html( strtoupper( $code_type ?: 'HTML' ) ); ?>
                    </span>
                </td>
                <td class="column-shortcode">
                    <code class="kscss-shortcode-display"><?php echo esc_html( $shortcode ); ?></code>
                    <button type="button" class="button button-small kscss-copy-shortcode" data-shortcode="<?php echo esc_attr( $shortcode ); ?>">
                        <?php esc_html_e( 'コピー', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
                    </button>
                </td>
                <td class="column-date">
                    <?php echo esc_html( get_the_modified_date() ); ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php
    // ページネーション
    $total_pages = $snippets_query->max_num_pages;
    if ( $total_pages > 1 ) :
        $current_page = max( 1, $paged );
        echo '<div class="tablenav bottom"><div class="tablenav-pages">';
        echo paginate_links( array(
            'base'      => add_query_arg( 'paged', '%#%' ),
            'format'    => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total'     => $total_pages,
            'current'   => $current_page,
        ) );
        echo '</div></div>';
    endif;
    ?>

    <?php wp_reset_postdata(); ?>

    <?php else : ?>
    <div class="kscss-no-items">
        <p><?php esc_html_e( 'スニペットがありません。', 'kashiwazaki-seo-code-snippet-shortcode' ); ?></p>
        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . KSCSS_Post_Type::POST_TYPE ) ); ?>" class="button button-primary">
            <?php esc_html_e( '最初のスニペットを作成', 'kashiwazaki-seo-code-snippet-shortcode' ); ?>
        </a>
    </div>
    <?php endif; ?>
</div>

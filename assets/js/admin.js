/**
 * Kashiwazaki SEO Code Snippet Shortcode - 管理画面スクリプト
 */

(function($) {
    'use strict';

    // デフォルト文字列
    var strings = {
        copied: 'コピーしました',
        copyFailed: 'コピーに失敗しました',
        confirmDelete: '本当に削除しますか？'
    };

    // ローカライズされた文字列があれば上書き
    if (typeof kscssAdmin !== 'undefined' && kscssAdmin.strings) {
        strings = $.extend(strings, kscssAdmin.strings);
    }

    // ショートコードコピー機能（イベント委譲で常に動作）
    $(document).on('click', '.kscss-copy-shortcode', function(e) {
        e.preventDefault();

        var $button = $(this);
        var shortcode = $button.data('shortcode');

        if (!shortcode) {
            return;
        }

        // execCommandを優先（より確実に動作）
        if (copyWithExecCommand(shortcode)) {
            showCopySuccess($button);
            return;
        }

        // Clipboard APIにフォールバック
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shortcode).then(function() {
                showCopySuccess($button);
            }).catch(function() {
                alert(strings.copyFailed);
            });
        } else {
            alert(strings.copyFailed);
        }
    });

    // execCommandでコピー
    function copyWithExecCommand(text) {
        var $temp = $('<textarea>');
        $temp.css({
            position: 'fixed',
            left: '-9999px',
            top: '0'
        });
        $('body').append($temp);
        $temp.val(text).select();
        $temp[0].setSelectionRange(0, text.length);

        var success = false;
        try {
            success = document.execCommand('copy');
        } catch (err) {
            success = false;
        }

        $temp.remove();
        return success;
    }

    // コピー成功表示
    function showCopySuccess($button) {
        var originalText = $button.text();
        $button.text(strings.copied);
        $button.addClass('button-primary');

        setTimeout(function() {
            $button.text(originalText);
            $button.removeClass('button-primary');
        }, 2000);
    }

    // 削除確認
    $(document).on('click', '.submitdelete', function(e) {
        if (!confirm(strings.confirmDelete)) {
            e.preventDefault();
        }
    });

})(jQuery);

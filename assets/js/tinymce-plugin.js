/**
 * Kashiwazaki SEO Code Snippet Shortcode - TinyMCEプラグイン
 */

(function() {
    'use strict';

    tinymce.PluginManager.add('kscss_button', function(editor, url) {
        // ボタン追加
        editor.addButton('kscss_button', {
            title: 'Kashiwazaki SEO Code Snippet Shortcode',
            icon: 'code',
            onclick: function() {
                // モーダルを表示
                var modal = document.getElementById('kscss-modal');
                if (modal) {
                    modal.style.display = 'flex';
                }
            }
        });

        // メニュー項目追加
        editor.addMenuItem('kscss_button', {
            text: 'Kashiwazaki SEO Code Snippet Shortcode',
            icon: 'code',
            context: 'insert',
            onclick: function() {
                var modal = document.getElementById('kscss-modal');
                if (modal) {
                    modal.style.display = 'flex';
                }
            }
        });
    });
})();

/**
 * Kashiwazaki SEO Code Snippet Shortcode - コードエディタースクリプト
 */

(function($) {
    'use strict';

    var editor = null;

    $(document).ready(function() {
        initCodeEditor();
        initCodeTypeChange();
    });

    /**
     * CodeMirrorエディター初期化
     */
    function initCodeEditor() {
        var $textarea = $('#kscss_code');
        if ($textarea.length === 0) {
            return;
        }

        var codeType = $('#kscss_code_type').val() || 'html';
        var mimeType = getMimeType(codeType);

        // WordPressのCodeMirror設定を使用
        var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
        editorSettings.codemirror = _.extend(
            {},
            editorSettings.codemirror,
            {
                mode: mimeType,
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 4,
                tabSize: 4,
                indentWithTabs: true,
                autoCloseBrackets: true,
                autoCloseTags: true,
                matchBrackets: true,
                matchTags: true,
                foldGutter: true,
                gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                extraKeys: {
                    'Ctrl-Space': 'autocomplete',
                    'F11': function(cm) {
                        cm.setOption('fullScreen', !cm.getOption('fullScreen'));
                    },
                    'Esc': function(cm) {
                        if (cm.getOption('fullScreen')) {
                            cm.setOption('fullScreen', false);
                        }
                    }
                }
            }
        );

        editor = wp.codeEditor.initialize($textarea, editorSettings);

        // エディターの高さを調整
        if (editor && editor.codemirror) {
            editor.codemirror.setSize(null, 400);
        }
    }

    /**
     * コードタイプ変更ハンドラー
     */
    function initCodeTypeChange() {
        $('#kscss_code_type').on('change', function() {
            var codeType = $(this).val();
            var mimeType = getMimeType(codeType);

            if (editor && editor.codemirror) {
                editor.codemirror.setOption('mode', mimeType);
            }

            // PHPタイプの場合のみPHP実行チェックボックスを表示
            var $phpCheckbox = $('input[name="kscss_execute_php"]').closest('p');
            if (codeType === 'php') {
                $phpCheckbox.show();
            } else {
                $phpCheckbox.hide();
                $('input[name="kscss_execute_php"]').prop('checked', false);
            }
        }).trigger('change');
    }

    /**
     * コードタイプからMIMEタイプを取得
     */
    function getMimeType(codeType) {
        if (kscssEditor && kscssEditor.codeTypes && kscssEditor.codeTypes[codeType]) {
            return kscssEditor.codeTypes[codeType];
        }

        var mimeTypes = {
            'php': 'application/x-httpd-php',
            'html': 'text/html',
            'css': 'text/css',
            'javascript': 'application/javascript'
        };

        return mimeTypes[codeType] || 'text/html';
    }

})(jQuery);

/**
 * Kashiwazaki SEO Code Snippet Shortcode - Gutenbergブロック
 */

(function(blocks, element, components, editor, i18n, data) {
    'use strict';

    var el = element.createElement;
    var Fragment = element.Fragment;
    var RawHTML = element.RawHTML;
    var SelectControl = components.SelectControl;
    var Placeholder = components.Placeholder;
    var Spinner = components.Spinner;
    var ToggleControl = components.ToggleControl;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var useSelect = data.useSelect;

    // ブロックアイコン
    var blockIcon = el('svg', {
        width: 24,
        height: 24,
        viewBox: '0 0 24 24'
    },
        el('path', {
            d: 'M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z'
        })
    );

    // スニペット情報を取得するヘルパー
    function getSnippetInfo(snippetId) {
        if (!snippetId || !kscssBlock.snippets) {
            return null;
        }

        for (var i = 0; i < kscssBlock.snippets.length; i++) {
            if (kscssBlock.snippets[i].value === snippetId) {
                return kscssBlock.snippets[i];
            }
        }
        return null;
    }

    // ブロック登録
    blocks.registerBlockType('kscss/code-snippet', {
        title: kscssBlock.strings.blockTitle,
        description: kscssBlock.strings.blockDescription,
        icon: blockIcon,
        category: 'widgets',
        keywords: ['code', 'snippet', 'kashiwazaki', 'html'],
        attributes: {
            snippetId: {
                type: 'number',
                default: 0
            },
            showPreview: {
                type: 'boolean',
                default: true
            }
        },
        supports: {
            html: false,
            align: ['wide', 'full']
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            var snippetOptions = kscssBlock.snippets || [];
            var selectedSnippet = getSnippetInfo(attributes.snippetId);
            var isHtmlType = selectedSnippet && selectedSnippet.codeType === 'html';

            // プレースホルダー（未選択時）
            if (!attributes.snippetId || attributes.snippetId === 0) {
                return el(Fragment, {},
                    el(InspectorControls, {},
                        el(PanelBody, {
                            title: kscssBlock.strings.selectSnippet,
                            className: 'kscss-block-inspector'
                        },
                            el(SelectControl, {
                                label: kscssBlock.strings.selectSnippet,
                                value: attributes.snippetId,
                                options: snippetOptions.map(function(opt) {
                                    return {
                                        value: opt.value,
                                        label: opt.label
                                    };
                                }),
                                onChange: function(value) {
                                    setAttributes({ snippetId: parseInt(value, 10) });
                                }
                            })
                        )
                    ),
                    el('div', { className: 'wp-block-kscss-code-snippet' },
                        el(Placeholder, {
                            icon: blockIcon,
                            label: kscssBlock.strings.blockTitle,
                            className: 'kscss-block-placeholder'
                        },
                            snippetOptions.length > 1 ?
                                el(SelectControl, {
                                    value: attributes.snippetId,
                                    options: snippetOptions.map(function(opt) {
                                        return {
                                            value: opt.value,
                                            label: opt.label
                                        };
                                    }),
                                    onChange: function(value) {
                                        setAttributes({ snippetId: parseInt(value, 10) });
                                    }
                                }) :
                                el('p', {}, kscssBlock.strings.noSnippets)
                        )
                    )
                );
            }

            // HTMLタイプの場合はHTMLプレビューを表示
            if (isHtmlType && selectedSnippet.code) {
                return el(Fragment, {},
                    el(InspectorControls, {},
                        el(PanelBody, {
                            title: kscssBlock.strings.selectSnippet,
                            className: 'kscss-block-inspector'
                        },
                            el(SelectControl, {
                                label: kscssBlock.strings.selectSnippet,
                                value: attributes.snippetId,
                                options: snippetOptions.map(function(opt) {
                                    return {
                                        value: opt.value,
                                        label: opt.label
                                    };
                                }),
                                onChange: function(value) {
                                    setAttributes({ snippetId: parseInt(value, 10) });
                                }
                            }),
                            el(ToggleControl, {
                                label: kscssBlock.strings.showPreview || 'HTMLプレビューを表示',
                                checked: attributes.showPreview,
                                onChange: function(value) {
                                    setAttributes({ showPreview: value });
                                }
                            })
                        )
                    ),
                    el('div', { className: 'wp-block-kscss-code-snippet kscss-html-block' },
                        el('div', { className: 'kscss-block-header' },
                            el('span', { className: 'kscss-block-title' },
                                el('span', { className: 'dashicons dashicons-editor-code' }),
                                ' ',
                                selectedSnippet.label
                            ),
                            el('span', { className: 'kscss-block-preview-type html' }, 'HTML')
                        ),
                        attributes.showPreview ?
                            el('div', { className: 'kscss-html-preview' },
                                el(RawHTML, {}, selectedSnippet.code)
                            ) :
                            el('div', { className: 'kscss-html-code' },
                                el('pre', {},
                                    el('code', {}, selectedSnippet.code)
                                )
                            )
                    )
                );
            }

            // 他のタイプ（PHP, CSS, JavaScript）のプレビュー表示
            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, {
                        title: kscssBlock.strings.selectSnippet,
                        className: 'kscss-block-inspector'
                    },
                        el(SelectControl, {
                            label: kscssBlock.strings.selectSnippet,
                            value: attributes.snippetId,
                            options: snippetOptions.map(function(opt) {
                                return {
                                    value: opt.value,
                                    label: opt.label
                                };
                            }),
                            onChange: function(value) {
                                setAttributes({ snippetId: parseInt(value, 10) });
                            }
                        })
                    )
                ),
                el('div', { className: 'wp-block-kscss-code-snippet' },
                    el('div', { className: 'kscss-block-preview' },
                        el('div', { className: 'kscss-block-preview-header' },
                            el('span', { className: 'kscss-block-preview-title' },
                                selectedSnippet ? selectedSnippet.label : 'Snippet #' + attributes.snippetId
                            ),
                            selectedSnippet && selectedSnippet.codeType ?
                                el('span', {
                                    className: 'kscss-block-preview-type ' + selectedSnippet.codeType
                                }, selectedSnippet.codeType.toUpperCase()) :
                                null
                        ),
                        el('div', { className: 'kscss-block-preview-content' },
                            '[kscss_snippet id="' + attributes.snippetId + '"]'
                        )
                    )
                )
            );
        },

        save: function() {
            // サーバーサイドレンダリングのため空を返す
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor || window.wp.editor,
    window.wp.i18n,
    window.wp.data
);

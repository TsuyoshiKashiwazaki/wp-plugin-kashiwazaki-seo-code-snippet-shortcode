# Kashiwazaki SEO Code Snippet Shortcode

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)

コードスニペットを管理し、ショートコードで呼び出すことができるWordPressプラグイン。

## 機能

- **コードスニペット管理**: PHP、HTML、CSS、JavaScriptのコードを管理
- **ショートコード出力**: `[kscss_snippet id="123"]` または `[kscss_snippet name="slug"]` で呼び出し
- **シンタックスハイライト**: CodeMirrorエディタでコード編集
- **Gutenbergブロック**: ブロックエディタからスニペットを挿入
- **クラシックエディタ対応**: TinyMCEボタンからスニペットを挿入
- **使用状況追跡**: どの投稿でスニペットが使用されているか確認
- **リビジョン管理**: コードの変更履歴を保存

## インストール

1. プラグインフォルダを `/wp-content/plugins/` にアップロード
2. WordPress管理画面の「プラグイン」から有効化

## 使い方

### スニペットの作成

1. 管理画面 → Kashiwazaki SEO Code Snippet Shortcode → 新規追加
2. タイトルとコードを入力
3. コードタイプを選択（PHP/HTML/CSS/JavaScript）
4. 公開

### スニペットの使用

```
[kscss_snippet id="123"]
[kscss_snippet name="my-snippet"]
```

## 動作環境

- WordPress 5.0以上
- PHP 7.2以上

## ライセンス

GPL v2 or later

## 作者

柏崎剛 (Tsuyoshi Kashiwazaki)
- Website: https://www.tsuyoshikashiwazaki.jp
- Profile: https://www.tsuyoshikashiwazaki.jp/profile/

## 更新履歴

### [1.0.0] - 2025-11-25
- 初回リリース
- コードスニペット管理機能
- ショートコードによるスニペット呼び出し
- Gutenbergブロック統合
- クラシックエディタ統合
- 自動挿入機能（投稿コンテンツの特定位置に自動挿入）
- 複数投稿タイプの選択対応

# TransCipher Distribution

多段暗号化パイプラインとAES-GCMを組み合わせた、強力かつ柔軟な暗号化エンジン「TransCipher」の配布用パッケージです。

## バージョン 2.0.0 (Major Update)
C++版とPHP版の完全互換を達成しました。

## コンテンツ構成

- **bin/**: GUIツール (`TransCipherApp.exe`)、CLIツール (`TransCipherCLI.exe`)、DLL等。
- **php/**: (Ver.2.0 追加) PHP版エンジン本体。
- **include/**: 開発者向けの公開APIヘッダーファイル。
- **lib/**: 開発者向けのインポートライブラリ。

## 使い方 (エンドユーザー)

1. `bin/` フォルダ内の `TransCipherApp.exe` を実行します。
2. 暗号化したいテキストを入力し、秘密鍵を設定して実行してください。

## 使い方 (開発者)

このライブラリを自身のC++プロジェクトに組み込むには、以下のファイルをプロジェクトに含めてください。

1. `include/` 内のヘッダーファイルをインクルード。
2. `lib/` 内のライブラリをリンク。
3. 実行環境に `TransCipher.dll` を配置。

詳しいAPIの使用方法は、`include/cipher_engine.h` を参照してください。

## PHP版エンジンの設定について

Ver.2.0.0 より、PHP版エンジンはセキュリティ向上のため、暗号化・復号処理を外部のセキュアなAPIサーバーへ委譲する仕様に変更されました。
本パッケージに含まれる [php/CipherEngine.php](file:///D:/prog/C++/TransCipher_Dist/php/CipherEngine.php) をそのまま動かすには、以下の手順で API トークンを設定する必要があります。

### 1. API トークンの取得
以下のトークン発行ゲートウェイページへアクセスし、**「アクセストークンを発行する」** ボタンをクリックして専用のアクセストークンを発行してください。

* **トークン取得URL**: [https://streamers-tool.sakura.ne.jp/TransCipher/](https://streamers-tool.sakura.ne.jp/TransCipher/)

![トークン作成画面](doc/CreateToken.png)

### 2. ソースコードの書き換え
発行されたトークンをコピーし、[php/CipherEngine.php](file:///D:/prog/C++/TransCipher_Dist/php/CipherEngine.php) 内の **24行目** にある `$apiToken` の値を書き換えて保存します。

```php
// php/CipherEngine.php 24行目付近
private static $apiToken = "YOUR_API_TOKEN_HERE"; // ← "YOUR_API_TOKEN_HERE" を取得したトークンに書き換えます
```

これで、PHP版の暗号化・復号エンジンが正常に動作するようになります。

## 仕様

### 公開インターフェース
利用者は `CipherEngine` クラスを通じて全ての暗号化・復号処理を行います。
![公開インターフェース](doc/public_interface.png)

### データフォーマット
暗号化されたデータは以下の構造で出力されます。
![データフォーマット](doc/data_format.png)

---

## 免責事項
このソフトウェアは現状有姿で提供されます。作者はこのソフトウェアの使用によって生じた、いかなる損害についても責任を負いません。
ソースコードは非公開ですが、公開APIを通じた自由な利用を歓迎します。

## クレジット表記について（開発者向け）

本ソフトウェアを自身のアプリケーションに組み込んで配布する場合、アプリケーション内の「バージョン情報」や「ライセンス一覧」等に、以下のいずれかの形式で権利表記を行ってください。

**表記例 1（シンプル）:**
> Includes TransCipher, Copyright (c) 2026 BLUE000.

**表記例 2（サードパーティ・ライセンス項目として）:**
> This software uses TransCipher library.
> Copyright (c) 2026 BLUE000.

※ アプリケーション全体の著作権者（あなた）と、ライブラリの著作権者（BLUE000）が区別できるよう、混同を避ける形で記載してください。

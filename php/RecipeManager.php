<?php
namespace TransCipher;

/**
 * RecipeManager - 互換性維持のためのダミー定数定義クラス (配布・クライアント用)
 * 
 * ロジックは一切含まず、モード定義定数のみを提供します。
 * これにより、既存のコードを変更することなく、
 * APIサーバー接続用クライアントエンジンへの移行が可能になります。
 * 
 * ※本ファイルは公開・配布しても暗号化ロジックの漏洩はありません。
 */
class RecipeManager {
    public const MODE_DISABLED   = 0;
    public const MODE_MANDATORY  = 1;
    public const MODE_RANDOMIZED = 2;
}

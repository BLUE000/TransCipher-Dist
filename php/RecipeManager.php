<?php
namespace TransCipher;

/**
 * RecipeManager - 鍵ハッシュから変換レシピを動的に生成する
 * 
 * C++版 (recipe_manager.cpp) と100%の互換性を持つ。
 */
class RecipeManager {
    public const MODE_DISABLED   = 0;
    public const MODE_MANDATORY  = 1;
    public const MODE_RANDOMIZED = 2;

    /**
     * 鍵とモードに基づき、変換パイプラインを生成する
     * 
     * @param string $key 秘密鍵
     * @param int $mode AESの適用モード
     * @return array 変換ステップのリスト
     */
    public static function createRecipe(string $key, int $mode = self::MODE_MANDATORY): array {
        // 1. 鍵のハッシュ化 (SHA-256) - バイナリで取得
        $hash = hash('sha256', $key, true);
        $steps = [];

        // Mandatory モードの場合、最優先（先頭）で AES-GCM を適用する
        if ($mode === self::MODE_MANDATORY) {
            $steps[] = ['type' => 'AES-GCM', 'params' => []];
        }

        // 2. 段階数の決定 (hash[0] を使用: 2～4段階)
        $numSteps = (ord($hash[0]) % 3) + 2;

        // 3. 各ステップの構築
        for ($i = 0; $i < $numSteps; $i++) {
            // 方式の選択 (hash[1..4] を使用)
            $numMethods = ($mode === self::MODE_RANDOMIZED) ? 4 : 3;
            $methodID = ord($hash[$i + 1]) % $numMethods;

            switch ($methodID) {
                case 0: // Percent
                    $steps[] = ['type' => 'Percent', 'params' => []];
                    break;
                case 1: // Base64
                    $steps[] = ['type' => 'Base64', 'params' => []];
                    break;
                case 2: // Reverse
                    // 反転サイズ (hash[5..8] を使用: 4～19バイト)
                    $swapSize = (ord($hash[$i + 5]) % 16) + 4;
                    $steps[] = ['type' => 'Reverse', 'params' => ['size' => $swapSize]];
                    break;
                case 3: // AES-GCM (Randomized モードのみ到達可能)
                    $steps[] = ['type' => 'AES-GCM', 'params' => []];
                    break;
            }
        }

        return $steps;
    }
}

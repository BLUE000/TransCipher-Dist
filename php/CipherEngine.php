<?php
namespace TransCipher;

require_once __DIR__ . '/RecipeManager.php';

/**
 * CipherEngine - TransCipher のメインエンジン (PHP版)
 * 
 * C++版 (cipher_engine.cpp) と100%の互換性を持つ。
 */
class CipherEngine {
    private const MAGIC_NUMBER = "TCF";
    private const VERSION_INFO = "1.0";
    private const HEADER_SIZE  = 32;

    /**
     * データを暗号化し、TCFヘッダーを付与する
     */
    public static function encrypt(string $data, string $key, int $mode = RecipeManager::MODE_MANDATORY): ?string {
        if (empty($key)) return null;

        // 1. レシピの構築とハッシュの取得
        $hash = hash('sha256', $key, true);
        $recipe = RecipeManager::createRecipe($key, $mode);

        // 2. 本体の暗号化/変換
        $payload = self::processPipeline($data, $recipe, $key);

        // 3. ヘッダーの構築
        $vLen = strlen(self::VERSION_INFO);
        $header = self::MAGIC_NUMBER;                    // 0-2: Magic
        $header .= chr($vLen);                           // 3: Version Len
        $header .= self::VERSION_INFO;                   // 4..: Version String
        
        // モード情報の埋め込み (vLen の次)
        $modeOffset = 4 + $vLen;
        $header = str_pad($header, $modeOffset, "\0");   // パディング調整
        $header .= chr($mode);

        // 32バイトまでランダムパディング
        while (strlen($header) < self::HEADER_SIZE) {
            $header .= chr(random_int(0, 255));
        }

        // 4. ヘッダーの XOR 隠蔽 (hash[24..31] を使用)
        $xorKey = substr($hash, 24, 8);
        for ($i = 0; $i < self::HEADER_SIZE; $i++) {
            $header[$i] = $header[$i] ^ $xorKey[$i % 8];
        }

        return $header . $payload;
    }

    /**
     * データを復号する
     */
    public static function decrypt(string $data, string $key): ?string {
        if (strlen($data) < self::HEADER_SIZE) return null;

        // 1. 鍵のハッシュ取得
        $hash = hash('sha256', $key, true);
        $xorKey = substr($hash, 24, 8);

        // 2. ヘッダーの復元 (XOR解除)
        $header = substr($data, 0, self::HEADER_SIZE);
        for ($i = 0; $i < self::HEADER_SIZE; $i++) {
            $header[$i] = $header[$i] ^ $xorKey[$i % 8];
        }

        // 3. 識別子チェック
        if (substr($header, 0, 3) !== self::MAGIC_NUMBER) return null;

        // 4. モード情報の読み取り
        $vLen = ord($header[3]);
        $mode = ord($header[4 + $vLen]);

        // 5. レシピの構築
        $recipe = RecipeManager::createRecipe($key, $mode);

        // 6. 本体の復号 (逆順に実行)
        $payload = substr($data, self::HEADER_SIZE);
        return self::undoPipeline($payload, $recipe, $key);
    }

    /**
     * パイプライン実行 (正方向)
     */
    private static function processPipeline(string $data, array $recipe, string $key): string {
        $result = $data;
        foreach ($recipe as $step) {
            $result = self::executeStep($result, $step, $key, true);
        }
        return $result;
    }

    /**
     * パイプライン実行 (逆方向)
     */
    private static function undoPipeline(string $data, array $recipe, string $key): string {
        $result = $data;
        $reverseRecipe = array_reverse($recipe);
        foreach ($reverseRecipe as $step) {
            $result = self::executeStep($result, $step, $key, false);
        }
        return $result;
    }

    /**
     * 各ステップの実行
     */
    private static function executeStep(string $data, array $step, string $key, bool $forward): string {
        switch ($step['type']) {
            case 'Base64':
                return $forward ? base64_encode($data) : base64_decode($data);
            
            case 'Percent':
                return $forward ? rawurlencode($data) : rawurldecode($data);
            
            case 'Reverse':
                $size = $step['params']['size'];
                $len = strlen($data);
                $reverseLen = min($len, $size);
                if ($reverseLen <= 1) return $data;
                
                $head = substr($data, 0, $reverseLen);
                $tail = substr($data, $reverseLen);
                return strrev($head) . $tail;

            case 'AES-GCM':
                $keyHash = hash('sha256', $key, true);
                if ($forward) {
                    $iv = random_bytes(12);
                    $tag = "";
                    $cipher = openssl_encrypt($data, 'aes-256-gcm', $keyHash, OPENSSL_RAW_DATA, $iv, $tag, "", 16);
                    return $iv . $cipher . $tag;
                } else {
                    if (strlen($data) < 28) return ""; // IV(12) + Tag(16)
                    $iv = substr($data, 0, 12);
                    $tag = substr($data, -16);
                    $cipher = substr($data, 12, -16);
                    return openssl_decrypt($cipher, 'aes-256-gcm', $keyHash, OPENSSL_RAW_DATA, $iv, $tag) ?: "";
                }

            default:
                return $data;
        }
    }
}

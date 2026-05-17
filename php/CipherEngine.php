<?php
namespace TransCipher;

require_once __DIR__ . '/RecipeManager.php';

/**
 * CipherEngine - TransCipher クライアントエンジン (APIプロキシ版 / 配布用)
 * 
 * 暗号化/復号のコア処理は、外部のセキュアな非公開サーバー上の API へ委譲されます。
 * これにより、公開リポジトリや配布パッケージ内の本ソースコードには
 * 暗号アルゴリズム（XORやAES-GCM、動的レシピ構築）の仕組みが一切露出しません。
 */
class CipherEngine {
    /**
     * APIサーバーのエンドポイントURL
     * 本番環境に合わせて変更、または CipherEngine::configure() を使用して設定してください。
     */
    private static $apiUrl = "https://streamers-tool.sakura.ne.jp/TransCipher/api.php";

    /**
     * API接続用の認証トークン
     * トークン発行ゲートウェイ (index.php) で発行されたトークンを設定してください。
     */
    private static $apiToken = "YOUR_API_TOKEN_HERE";

    /**
     * APIサーバーの接続情報とトークンを動的に設定するメソッド
     */
    public static function configure(string $apiUrl, string $apiToken = ""): void {
        self::$apiUrl = $apiUrl;
        if (!empty($apiToken)) {
            self::$apiToken = $apiToken;
        }
    }

    /**
     * APIサーバーへリクエストを送信してデータを暗号化する
     */
    public static function encrypt(string $data, string $key, int $mode = RecipeManager::MODE_MANDATORY): ?string {
        if (empty($key)) return null;

        $response = self::sendRequest([
            'action' => 'encrypt',
            'token'  => self::$apiToken,
            'key'    => $key,
            'data'   => base64_encode($data),
            'mode'   => $mode
        ]);

        if ($response && isset($response['status']) && $response['status'] === 'success') {
            return base64_decode($response['result']);
        }

        return null;
    }

    /**
     * APIサーバーへリクエストを送信してデータを復号する
     */
    public static function decrypt(string $data, string $key): ?string {
        if (empty($key)) return null;

        $response = self::sendRequest([
            'action' => 'decrypt',
            'token'  => self::$apiToken,
            'key'    => $key,
            'data'   => base64_encode($data)
        ]);

        if ($response && isset($response['status']) && $response['status'] === 'success') {
            return base64_decode($response['result']);
        }

        return null;
    }

    /**
     * APIサーバーへ cURL を用いて JSON POST リクエストを送信する
     */
    private static function sendRequest(array $payload): ?array {
        $ch = curl_init(self::$apiUrl);
        if ($ch === false) {
            return null;
        }

        $jsonData = json_encode($payload);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        
        // タイムアウト設定（API呼び出しのハング防止）
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 接続タイムアウト 5秒
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);        // 実行タイムアウト 15秒

        // 必要に応じてSSL検証の無効化（開発環境などの場合のみ自己責任で）
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseRaw === false || $httpCode !== 200) {
            return null;
        }

        $responseData = json_decode($responseRaw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $responseData;
    }
}

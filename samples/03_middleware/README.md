# Laravel Custom Middleware

Laravel のカスタムミドルウェアの実装例です。

## 技術要素

- **認証ミドルウェア**: API キー認証
- **ロギングミドルウェア**: リクエスト/レスポンスのログ記録
- **レート制限**: リクエスト頻度の制限
- **レスポンス変換**: レスポンスの統一的な整形

## セットアップ

```bash
cd samples/03_middleware
composer install
cp .env.example .env
php artisan key:generate
```

## 使い方

### サーバー起動

```bash
php artisan serve
```

### API キー認証のテスト

```bash
# 認証なし（401エラー）
curl http://localhost:8000/api/v1/reservations

# 認証あり
curl -H "X-API-Key: your-api-key" http://localhost:8000/api/v1/reservations
```

## テスト

```bash
php artisan test
```

## 設計ポイント

- ミドルウェアによる横断的関心事の処理
- 認証ロジックの分離
- ログ記録による監査証跡
- レート制限によるDoS攻撃対策


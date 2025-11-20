# Laravel RESTful API

Laravel を使用した RESTful API の実装例です。

## 技術要素

- **Eloquent ORM**: データベース操作
- **マイグレーション**: データベーススキーマ管理
- **API リソース**: レスポンスの整形
- **バリデーション**: リクエストデータの検証
- **テスト**: PHPUnit によるテスト

## セットアップ

```bash
cd samples/01_rest_api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## 使い方

### サーバー起動

```bash
php artisan serve
```

デフォルトで `http://localhost:8000` で起動します。

### API エンドポイント

#### 予約一覧取得

```bash
curl http://localhost:8000/api/v1/reservations
```

#### 予約作成

```bash
curl -X POST http://localhost:8000/api/v1/reservations \
  -H "Content-Type: application/json" \
  -d '{
    "user_name": "Alice",
    "resource_name": "Room-A",
    "starts_at": "2025-01-02T09:00:00Z",
    "ends_at": "2025-01-02T10:00:00Z"
  }'
```

#### 予約キャンセル

```bash
curl -X DELETE http://localhost:8000/api/v1/reservations/1
```

## テスト

```bash
php artisan test
```

## 設計ポイント

- Eloquent モデルによるデータアクセス層の抽象化
- FormRequest によるバリデーションロジックの分離
- API Resource によるレスポンスの整形
- マイグレーションによるバージョン管理されたスキーマ定義
- テストによる品質保証


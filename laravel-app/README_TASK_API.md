# Laravel タスク管理 REST API

Laravel 10を使用した本格的なタスク管理REST APIです。FindyのPHPスコア向上を目的として作成されました。

## 主な機能

### 🔐 認証機能（Laravel Sanctum）
- ユーザー登録
- ログイン/ログアウト
- トークンベースの認証
- ユーザープロフィール取得

### 📝 タスク管理機能
- タスクのCRUD操作（作成・読取・更新・削除）
- タスクの一覧表示（ページネーション対応）
- ステータスによるフィルタリング（pending, in_progress, completed, cancelled）
- 優先度によるフィルタリング（low, medium, high, urgent）
- 期限切れタスクの検出
- タスクの統計情報取得

### 🏗️ アーキテクチャ
- **Eloquent ORM**: データベース操作
- **マイグレーション**: データベーススキーマ管理
- **モデルリレーション**: User ⇔ Task
- **API Resources**: JSONレスポンスの整形
- **Form Request Validation**: 入力バリデーション
- **Policy**: 認可ロジック
- **Factory**: テストデータ生成
- **Feature Tests**: 包括的なテストカバレッジ

## 技術スタック

- **PHP**: 8.2+
- **Laravel**: 10.x
- **データベース**: SQLite（開発環境）/ MySQL（本番環境対応）
- **認証**: Laravel Sanctum
- **テスト**: PHPUnit

## 必要環境

- PHP 8.2以上
- Composer 2.x以上
- SQLite3拡張

## インストール

```bash
# 依存関係のインストール
composer install

# 環境変数の設定
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate

# データベースのマイグレーション
php artisan migrate

# テストの実行
php artisan test
```

## APIエンドポイント

### 認証エンドポイント

| メソッド | エンドポイント | 説明 | 認証 |
|---------|---------------|------|------|
| POST | `/api/register` | ユーザー登録 | 不要 |
| POST | `/api/login` | ログイン | 不要 |
| POST | `/api/logout` | ログアウト | 必要 |
| GET | `/api/me` | ユーザー情報取得 | 必要 |

### タスクエンドポイント

| メソッド | エンドポイント | 説明 | 認証 |
|---------|---------------|------|------|
| GET | `/api/tasks` | タスク一覧取得 | 必要 |
| POST | `/api/tasks` | タスク作成 | 必要 |
| GET | `/api/tasks/{id}` | タスク詳細取得 | 必要 |
| PUT/PATCH | `/api/tasks/{id}` | タスク更新 | 必要 |
| DELETE | `/api/tasks/{id}` | タスク削除 | 必要 |
| GET | `/api/tasks-statistics` | タスク統計取得 | 必要 |

### クエリパラメータ

タスク一覧取得時に使用可能なフィルタリングオプション：

- `status`: ステータスでフィルタリング（pending, in_progress, completed, cancelled）
- `priority`: 優先度でフィルタリング（low, medium, high, urgent）
- `overdue`: 期限切れタスクのみ表示（true/false）
- `sort_by`: ソート項目（created_at, due_date, priority など）
- `sort_order`: ソート順序（asc, desc）
- `per_page`: 1ページあたりの表示件数

## 使用例

### 1. ユーザー登録

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "山田太郎",
    "email": "yamada@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. ログイン

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "yamada@example.com",
    "password": "password123"
  }'
```

レスポンス例：
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "山田太郎",
    "email": "yamada@example.com"
  },
  "token": "1|abc123..."
}
```

### 3. タスク作成

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "title": "プロジェクト提案書の作成",
    "description": "来週の会議で使用する提案書を作成する",
    "priority": "high",
    "due_date": "2024-12-31T23:59:59+09:00"
  }'
```

### 4. タスク一覧取得（フィルタリング付き）

```bash
# ステータスでフィルタリング
curl -X GET "http://localhost:8000/api/tasks?status=pending" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# 優先度でフィルタリング
curl -X GET "http://localhost:8000/api/tasks?priority=high" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# 期限切れタスクのみ
curl -X GET "http://localhost:8000/api/tasks?overdue=true" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 5. タスク更新

```bash
curl -X PUT http://localhost:8000/api/tasks/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "status": "completed"
  }'
```

### 6. タスク統計取得

```bash
curl -X GET http://localhost:8000/api/tasks-statistics \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

レスポンス例：
```json
{
  "total": 15,
  "by_status": {
    "pending": 5,
    "in_progress": 3,
    "completed": 6,
    "cancelled": 1
  },
  "by_priority": {
    "low": 2,
    "medium": 8,
    "high": 4,
    "urgent": 1
  },
  "overdue": 2
}
```

## データベース設計

### usersテーブル
| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint | 主キー |
| name | varchar | ユーザー名 |
| email | varchar | メールアドレス（ユニーク） |
| password | varchar | パスワード（ハッシュ化） |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |

### tasksテーブル
| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint | 主キー |
| user_id | bigint | ユーザーID（外部キー） |
| title | varchar | タスクタイトル |
| description | text | タスク説明（任意） |
| status | enum | ステータス（pending, in_progress, completed, cancelled） |
| priority | enum | 優先度（low, medium, high, urgent） |
| due_date | timestamp | 期限（任意） |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |

### インデックス
- `tasks(user_id, status)`: ユーザーごとのステータス検索を高速化
- `tasks(due_date)`: 期限での検索を高速化

## テスト

プロジェクトには包括的なFeatureテストが含まれています：

```bash
# 全テストの実行
php artisan test

# 特定のテストクラスを実行
php artisan test --filter AuthApiTest
php artisan test --filter TaskApiTest

# カバレッジ付き実行（Xdebugが必要）
php artisan test --coverage
```

### テストカバレッジ
- ✅ ユーザー登録・ログイン・ログアウト
- ✅ バリデーションエラー処理
- ✅ タスクのCRUD操作
- ✅ 認証・認可チェック
- ✅ フィルタリング機能
- ✅ 統計情報取得

## ローカルサーバーの起動

```bash
# Artisanサーバー（開発用）
php artisan serve

# カスタムポート指定
php artisan serve --port=8080

# アクセス
# http://localhost:8000
```

## セキュリティ機能

- **認証**: Laravel Sanctumによるトークンベース認証
- **認可**: Policyによる所有権チェック
- **バリデーション**: FormRequestによる入力検証
- **パスワードハッシュ化**: bcryptによる安全なハッシュ化
- **SQLインジェクション対策**: Eloquent ORMによるプリペアドステートメント
- **CSRF保護**: Laravelの標準機能

## Laravel の主要機能の活用

このプロジェクトでは以下のLaravel機能を実装しています：

1. **Eloquent ORM**: リレーション、スコープ、キャスト
2. **マイグレーション**: データベーススキーマ管理
3. **シーディング & ファクトリー**: テストデータ生成
4. **API Resources**: JSONレスポンスの整形
5. **Form Request Validation**: バリデーションロジックの分離
6. **Policy**: 認可ロジック
7. **Middleware**: 認証チェック
8. **Laravel Sanctum**: API認証
9. **Feature Tests**: エンドツーエンドテスト

## ディレクトリ構造

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php      # 認証API
│   │   │       └── TaskController.php      # タスクAPI
│   │   ├── Requests/
│   │   │   ├── StoreTaskRequest.php        # タスク作成バリデーション
│   │   │   └── UpdateTaskRequest.php       # タスク更新バリデーション
│   │   └── Resources/
│   │       ├── TaskResource.php            # タスクリソース
│   │       └── TaskCollection.php          # タスクコレクション
│   ├── Models/
│   │   ├── User.php                        # ユーザーモデル
│   │   └── Task.php                        # タスクモデル
│   └── Policies/
│       └── TaskPolicy.php                  # タスク認可ポリシー
├── database/
│   ├── factories/
│   │   └── TaskFactory.php                 # タスクファクトリー
│   └── migrations/
│       └── xxxx_create_tasks_table.php     # タスクテーブルマイグレーション
├── routes/
│   └── api.php                             # APIルート定義
└── tests/
    └── Feature/
        ├── AuthApiTest.php                 # 認証APIテスト
        └── TaskApiTest.php                 # タスクAPIテスト
```

## パフォーマンス最適化

- Eloquent Eager Loading（N+1問題の回避）
- データベースインデックスの適切な配置
- ページネーション実装
- APIリソースによる効率的なデータ変換

## 今後の拡張案

- [ ] タグ機能の追加
- [ ] タスクのソート順変更機能
- [ ] タスクの検索機能
- [ ] タスクの共有機能
- [ ] 通知機能
- [ ] ファイル添付機能
- [ ] コメント機能

## ライセンス

MIT License

## 作成者

Laravel Task Management API - Findy PHP Score Improvement Project

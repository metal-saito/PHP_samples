# Laravel 10 タスク管理 REST API

Laravel 10フレームワークを使用した、本格的なタスク管理REST APIアプリケーションです。認証機能、CRUD操作、統計情報の取得など、実務で必要となる機能を網羅的に実装しています。

## 目次

- [主な機能](#主な機能)
- [アーキテクチャ](#アーキテクチャ)
- [技術スタック](#技術スタック)
- [セットアップ](#セットアップ)
- [API エンドポイント](#api-エンドポイント)
- [テスト](#テスト)
- [デザインパターン](#デザインパターン)

## 主な機能

### 認証・認可
- **Laravel Sanctum** によるトークンベース認証
- ユーザー登録・ログイン・ログアウト機能
- **Policy ベース認可** - ユーザーは自分のタスクのみ操作可能

### タスク管理
- タスクのCRUD操作（作成・参照・更新・削除）
- タスクのステータス管理（pending / in_progress / completed / cancelled）
- 優先度設定（low / medium / high / urgent）
- 期限設定と期限切れ判定
- タスクの説明文（本文）の管理

### 高度な機能
- **統計情報の取得** - ステータス別・優先度別の集計
- **フィルタリング** - ステータス、優先度、期限切れでの絞り込み
- **ページネーション** - 大量データに対応した一覧取得
- **バリデーション** - Form Request による入力検証
- **エラーハンドリング** - 適切なHTTPステータスコードとエラーメッセージ

## アーキテクチャ

本アプリケーションは、**レイヤードアーキテクチャ** と **ドメイン駆動設計（DDD）** の原則に基づいて設計されています。

### アーキテクチャ図

```
┌─────────────────────────────────────────────────────────────┐
│                        Presentation Layer                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │   Routes     │→ │ Controllers  │→ │  Resources   │       │
│  │  (API定義)   │  │ (HTTP処理)   │  │ (JSON変換)   │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
│           ↓                ↓                                  │
│  ┌──────────────┐  ┌──────────────┐                         │
│  │Form Requests │  │ Middleware   │                         │
│  │(入力検証)    │  │ (認証/CORS)  │                         │
│  └──────────────┘  └──────────────┘                         │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                      Application Layer                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  Services    │→ │  Policies    │  │   DTOs       │       │
│  │(業務ロジック)│  │  (認可)      │  │(データ転送)  │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                        Domain Layer                           │
│  ┌──────────────┐  ┌──────────────┐                         │
│  │   Models     │  │ Query Scopes │                         │
│  │(Eloquent)    │  │(クエリ再利用)│                         │
│  └──────────────┘  └──────────────┘                         │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                    Infrastructure Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  Database    │  │  Migrations  │  │   Seeders    │       │
│  │  (MySQL)     │  │(スキーマ定義)│  │(初期データ)  │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└─────────────────────────────────────────────────────────────┘
```

### ディレクトリ構造

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/      # APIコントローラー
│   │   │   ├── AuthController.php    # 認証エンドポイント
│   │   │   └── TaskController.php    # タスクCRUD & 統計
│   │   ├── Requests/             # Form Request（入力検証）
│   │   │   ├── StoreTaskRequest.php  # タスク作成時の検証
│   │   │   └── UpdateTaskRequest.php # タスク更新時の検証
│   │   ├── Resources/            # API Resource（JSON変換）
│   │   │   ├── TaskResource.php      # 単一タスクのレスポンス
│   │   │   └── TaskCollection.php    # タスク一覧のレスポンス
│   │   └── Middleware/           # ミドルウェア
│   │       └── Kernel.php            # CORS, 認証設定
│   ├── Models/                   # Eloquent モデル
│   │   ├── User.php                  # ユーザーモデル
│   │   └── Task.php                  # タスクモデル
│   ├── Services/                 # サービス層（業務ロジック）
│   │   └── TaskService.php           # タスク統計・一括操作
│   └── Policies/                 # 認可ポリシー
│       └── TaskPolicy.php            # タスクアクセス制御
├── database/
│   ├── migrations/               # マイグレーション
│   │   ├── 2014_10_12_000000_create_users_table.php
│   │   ├── 2019_12_14_000001_create_personal_access_tokens_table.php
│   │   └── 2024_01_01_000000_create_tasks_table.php
│   └── seeders/                  # シーダー
│       └── DatabaseSeeder.php
├── routes/
│   └── api.php                   # APIルート定義
└── tests/
    ├── Unit/                     # ユニットテスト
    └── Feature/                  # フィーチャーテスト
        ├── AuthApiTest.php           # 認証API
        └── TaskApiTest.php           # タスクAPI
```

## 技術スタック

### フレームワーク・ライブラリ
- **Laravel 10.x** - PHPフレームワーク
- **Laravel Sanctum** - API認証
- **Eloquent ORM** - データベース操作
- **PHPUnit** - テスティングフレームワーク

### データベース
- **MySQL** - 本番環境用RDBMS
- **SQLite** - テスト環境用（インメモリ）

### 開発ツール
- **Composer** - 依存関係管理
- **Artisan** - Laravelコマンドラインツール
- **Git** - バージョン管理

### コーディング規約
- **PSR-12** - PHPコーディング標準
- **Type Declarations** - 厳格な型宣言（`declare(strict_types=1)`）
- **PHPDoc** - 包括的なドキュメントコメント

## セットアップ

### 前提条件

- PHP 8.2以上
- Composer 2.x以上
- MySQL 8.0以上（または SQLite）

### インストール手順

```bash
# 1. 依存関係のインストール
composer install

# 2. 環境設定ファイルの作成
cp .env.example .env

# 3. アプリケーションキーの生成
php artisan key:generate

# 4. データベースの設定（.envファイルを編集）
# SQLiteを使用する場合:
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# MySQLを使用する場合:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_tasks
DB_USERNAME=root
DB_PASSWORD=secret

# 5. データベースのマイグレーション
php artisan migrate

# 6. (オプション) 初期データの投入
php artisan db:seed

# 7. 開発サーバーの起動
php artisan serve
```

アプリケーションは `http://localhost:8000` でアクセス可能になります。

### テストの実行

```bash
# すべてのテストを実行
php artisan test

# 特定のテストファイルのみ実行
php artisan test --filter=TaskApiTest

# カバレッジ付きで実行（Xdebug必要）
php artisan test --coverage
```

## API エンドポイント

### 認証エンドポイント

| メソッド | パス | 説明 | 認証 |
|---------|------|------|------|
| POST | `/api/register` | ユーザー登録 | 不要 |
| POST | `/api/login` | ログイン（トークン取得） | 不要 |
| POST | `/api/logout` | ログアウト（トークン無効化） | 必要 |
| GET | `/api/user` | 認証ユーザー情報取得 | 必要 |

### タスク管理エンドポイント

| メソッド | パス | 説明 | 認証 |
|---------|------|------|------|
| GET | `/api/tasks` | タスク一覧取得 | 必要 |
| POST | `/api/tasks` | タスク作成 | 必要 |
| GET | `/api/tasks/{id}` | タスク詳細取得 | 必要 |
| PUT | `/api/tasks/{id}` | タスク更新 | 必要 |
| DELETE | `/api/tasks/{id}` | タスク削除 | 必要 |
| GET | `/api/tasks/statistics` | 統計情報取得 | 必要 |

### 使用例

#### ユーザー登録

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

レスポンス:
```json
{
  "user": {
    "id": 1,
    "name": "山田太郎",
    "email": "yamada@example.com"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
}
```

#### ログイン

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "yamada@example.com",
    "password": "password123"
  }'
```

#### タスク作成

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "title": "Laravel APIの実装",
    "description": "認証とCRUD機能を実装する",
    "status": "pending",
    "priority": "high",
    "due_date": "2024-12-31 23:59:59"
  }'
```

レスポンス:
```json
{
  "data": {
    "id": 1,
    "title": "Laravel APIの実装",
    "description": "認証とCRUD機能を実装する",
    "status": "pending",
    "priority": "high",
    "due_date": "2024-12-31T23:59:59.000000Z",
    "is_overdue": false,
    "is_completed": false,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

#### タスク一覧取得（フィルタ付き）

```bash
# ステータスでフィルタ
curl -X GET "http://localhost:8000/api/tasks?status=pending" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# 優先度でフィルタ
curl -X GET "http://localhost:8000/api/tasks?priority=high" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# 期限切れのみ取得
curl -X GET "http://localhost:8000/api/tasks?overdue=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# ページネーション
curl -X GET "http://localhost:8000/api/tasks?page=2&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### 統計情報取得

```bash
curl -X GET http://localhost:8000/api/tasks/statistics \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

レスポンス:
```json
{
  "total_tasks": 25,
  "by_status": {
    "pending": 10,
    "in_progress": 8,
    "completed": 5,
    "cancelled": 2
  },
  "by_priority": {
    "low": 5,
    "medium": 10,
    "high": 7,
    "urgent": 3
  },
  "overdue_count": 3,
  "completed_count": 5,
  "completion_rate": 20.0
}
```

## テスト

### テストカバレッジ

- **認証API** - 6テスト
  - ユーザー登録（正常系・異常系）
  - ログイン（正常系・異常系）
  - ログアウト
  - プロフィール取得

- **タスクAPI** - 11テスト
  - タスク作成（認証必須、バリデーション）
  - タスク一覧取得
  - タスク詳細取得（権限チェック）
  - タスク更新
  - タスク削除
  - 統計情報取得
  - フィルタリング

### テスト実行コマンド

```bash
# すべてのテストを実行
php artisan test

# 詳細な出力で実行
php artisan test --verbose

# 特定のテストのみ実行
php artisan test --filter=AuthApiTest
php artisan test --filter=TaskApiTest

# カバレッジレポート生成（Xdebug必要）
php artisan test --coverage --min=80
```

### テスト環境

テストは以下の環境で実行されます：

- **データベース**: SQLite（インメモリ）
- **環境**: `.env.testing` または `phpunit.xml` の設定
- **自動ロールバック**: 各テスト後にデータベースをリセット

## デザインパターン

本アプリケーションでは、以下のデザインパターンを採用しています。

### 1. サービス層パターン（Service Layer Pattern）

**目的**: ビジネスロジックをコントローラーから分離し、再利用性と保守性を向上

**実装例**: `TaskService.php`

```php
class TaskService
{
    /**
     * ユーザーのタスク統計情報を取得
     */
    public function getStatistics(User $user): array
    {
        // 複雑な集計ロジックをサービス層に集約
        $userId = $user->id;
        
        return [
            'total_tasks' => Task::forUser($userId)->count(),
            'by_status' => $this->getCountByStatus($userId),
            'by_priority' => $this->getCountByPriority($userId),
            'overdue_count' => Task::forUser($userId)->overdue()->count(),
            // ...
        ];
    }
}
```

**メリット**:
- コントローラーがシンプルになる
- ビジネスロジックをテストしやすい
- 複数のコントローラーから同じロジックを再利用可能

### 2. リポジトリパターン（Repository Pattern）

**目的**: データアクセスロジックを抽象化し、ドメイン層とデータ層を分離

**実装例**: Eloquentモデルの Query Scope

```php
class Task extends Model
{
    /**
     * 特定ユーザーのタスクに絞り込むスコープ
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 期限切れタスクに絞り込むスコープ
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', self::COMPLETED_STATUSES);
    }
}
```

**メリット**:
- SQLクエリの重複を防ぐ
- クエリロジックの再利用性が高まる
- データベース変更時の影響範囲を局所化

### 3. ポリシーパターン（Policy Pattern）

**目的**: 認可ロジック（誰が何をできるか）をモデルから分離

**実装例**: `TaskPolicy.php`

```php
class TaskPolicy
{
    /**
     * ユーザーがタスクを閲覧できるか判定
     */
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * ユーザーがタスクを更新できるか判定
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
```

**メリット**:
- 認可ロジックが一箇所に集約される
- コントローラーから認可チェックのコードが消える
- 権限変更時の修正箇所が明確

### 4. Form Request パターン

**目的**: 入力検証ロジックをコントローラーから分離

**実装例**: `StoreTaskRequest.php`

```php
class StoreTaskRequest extends FormRequest
{
    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(Task::VALID_STATUSES)],
            'priority' => ['nullable', 'string', Rule::in(Task::VALID_PRIORITIES)],
            'due_date' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タスク名は必須です',
            'due_date.after' => '期限は未来の日時を指定してください',
        ];
    }
}
```

**メリット**:
- コントローラーがシンプルになる
- バリデーションルールを再利用可能
- エラーメッセージを一元管理

### 5. API Resource パターン

**目的**: データベースモデルとJSONレスポンスの変換ロジックを分離

**実装例**: `TaskResource.php`

```php
class TaskResource extends JsonResource
{
    /**
     * リソースを配列に変換
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'is_overdue' => $this->isOverdue(),      // ヘルパーメソッド
            'is_completed' => $this->isCompleted(),  // ヘルパーメソッド
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

**メリット**:
- JSONレスポンスの形式を一元管理
- モデルの内部構造を隠蔽できる
- クライアント向けの計算フィールドを追加しやすい

### 6. 依存性注入（Dependency Injection）

**目的**: クラス間の疎結合を実現し、テスタビリティを向上

**実装例**: コントローラーへのサービス注入

```php
class TaskController extends Controller
{
    /**
     * コンストラクタインジェクション
     */
    public function __construct(
        private readonly TaskService $taskService
    ) {}

    /**
     * 統計情報エンドポイント
     */
    public function statistics(Request $request): JsonResponse
    {
        // サービス層に処理を委譲
        $stats = $this->taskService->getStatistics($request->user());
        return response()->json($stats);
    }
}
```

**メリット**:
- モックを使ったテストが容易
- 実装の差し替えが簡単
- 依存関係が明確になる

### アーキテクチャの利点

これらのパターンを組み合わせることで、以下の利点が得られます：

1. **保守性の向上** - 各レイヤーの責務が明確で、修正箇所を特定しやすい
2. **テスタビリティ** - 各レイヤーを独立してテスト可能
3. **再利用性** - サービス層、スコープ、ポリシーなどを複数箇所から利用可能
4. **拡張性** - 新機能追加時に既存コードへの影響を最小化
5. **可読性** - コードの意図が明確で、新しいメンバーでも理解しやすい

## データベース設計

### テーブル構成

#### users テーブル
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### tasks テーブル
```sql
CREATE TABLE tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    priority VARCHAR(50) NOT NULL DEFAULT 'medium',
    due_date TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_due_date (due_date)
);
```

#### personal_access_tokens テーブル（Sanctum）
```sql
CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_tokenable (tokenable_type, tokenable_id)
);
```

### リレーションシップ

- **User → Task**: 1対多（1ユーザーは複数のタスクを持つ）
- **Task → User**: 多対1（1タスクは1ユーザーに属する）

### インデックス設計

- `tasks.user_id` - ユーザーごとのタスク取得を高速化
- `tasks.status` - ステータスフィルタリングを高速化
- `tasks.priority` - 優先度フィルタリングを高速化
- `tasks.due_date` - 期限切れタスクの検索を高速化

## セキュリティ

### 実装されているセキュリティ対策

1. **認証** - Laravel Sanctum によるトークンベース認証
2. **認可** - Policy による所有者チェック
3. **バリデーション** - Form Request による入力検証
4. **SQLインジェクション対策** - Eloquent ORM のプリペアドステートメント
5. **XSS対策** - Laravel の自動エスケープ
6. **CSRF対策** - API はトークン認証のため CSRF トークン不要
7. **CORS設定** - 許可されたオリジンのみアクセス可能
8. **パスワードハッシュ** - bcrypt による暗号化

### 推奨される追加対策

- **レート制限** - `throttle` ミドルウェアの適用
- **HTTPS強制** - 本番環境では必須
- **環境変数の保護** - `.env` ファイルをバージョン管理から除外
- **依存関係の更新** - 定期的な `composer update` 実行

## パフォーマンス最適化

### 実装されている最適化

1. **Eager Loading** - N+1問題の回避
2. **インデックス** - 頻繁にクエリされるカラムにインデックス
3. **ページネーション** - 大量データの効率的な取得
4. **Query Scope** - クエリの再利用によるパフォーマンス向上

### 今後の最適化案

- **キャッシュ** - Redis/Memcached による統計情報のキャッシュ
- **非同期処理** - 重い処理をジョブキューで実行
- **データベース最適化** - インデックスの見直し、パーティショニング

## トラブルシューティング

### よくある問題と解決方法

#### 1. マイグレーション実行時のエラー

```bash
# エラー: "Access denied for user"
# 解決方法: .env のデータベース認証情報を確認

# エラー: "Database does not exist"
# 解決方法: データベースを作成
mysql -u root -p
CREATE DATABASE laravel_tasks;
```

#### 2. 認証トークンが機能しない

```bash
# 解決方法: アプリケーションキーを再生成
php artisan key:generate

# Sanctum の設定を確認
php artisan config:clear
php artisan cache:clear
```

#### 3. CORS エラー

```php
// config/cors.php を確認
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

## ライセンス

MIT License

---

## 開発者向け情報

### コーディング規約

- PSR-12 準拠のコードスタイル
- 厳格な型宣言（`declare(strict_types=1)`）
- 全クラス・メソッドに PHPDoc を記載
- 定数を使用してマジックナンバー・マジックストリングを排除

### Git ワークフロー

```bash
# 機能開発用ブランチを作成
git checkout -b feature/new-feature

# コミット
git add .
git commit -m "feat: Add new feature"

# プルリクエスト作成
git push origin feature/new-feature
```

### CI/CD

GitHub Actions により、以下が自動実行されます：

- PHPUnit テスト
- コードスタイルチェック
- 静的解析（PHPStan）

### 連絡先

問題や質問がある場合は、GitHub Issues を作成してください。

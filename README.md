# タスク管理 REST API

モダンで高度な機能を持つ、実用的なPHPタスク管理REST APIです。

## 主な機能

- **RESTful API設計**: RESTのベストプラクティスに従った直感的なエンドポイント
- **ドメイン駆動設計**: ビジネスロジックをカプセル化したリッチドメインモデル
- **イミュータブルエンティティ**: 予測可能性を高めるイミュータブルなTaskエンティティ
- **リポジトリパターン**: ドメイン層とデータアクセス層の明確な分離
- **サービス層**: 依存性注入によるビジネスロジックのカプセル化
- **バリデーション**: 詳細なエラーメッセージを伴う包括的な入力検証
- **ステートマシン**: 検証付きタスクステータス遷移
- **レート制限**: 組み込みレート制限ミドルウェア（100リクエスト/時間）
- **CORS対応**: クロスオリジンリソース共有の有効化
- **タグシステム**: タスク整理のための柔軟なタグ付け
- **高度なフィルタリング**: ステータス、タグ、期限切れによるフィルタリング
- **統計情報**: 包括的なタスク統計エンドポイント
- **ユニット・統合テスト**: PHPUnitによる完全なテストカバレッジ
- **静的解析**: 最大限の型安全性のためのPHPStan level 8
- **PSR標準**: PSR-4（オートローディング）、PSR-12（コーディングスタイル）に準拠

## アーキテクチャ

```
src/
├── Controller/      # HTTPリクエスト処理
├── Service/         # ビジネスロジック層
├── Repository/      # データ永続化層
├── Model/           # ドメインエンティティ
├── DTO/             # データ転送オブジェクト
├── Validator/       # 入力バリデーション
├── Middleware/      # HTTPミドルウェア
└── Exception/       # カスタム例外
```

## 必要環境

- PHP 8.1以上
- PDO拡張
- JSON拡張
- Composer（依存関係管理用）

## インストール

```bash
# 依存関係のインストール
composer install

# テストの実行
composer test

# 静的解析の実行
composer analyse
```

## クイックスタート

```bash
# PHPビルトインサーバーの起動
php -S localhost:8000 -t public

# または、ルーティング付きでの起動
cd public && php -S localhost:8000
```

## APIエンドポイント

### タスク管理

- `GET /api/tasks` - タスク一覧取得（ページネーション対応）
  - クエリパラメータ: `?limit=N&offset=N`
- `GET /api/tasks/{id}` - IDによるタスク取得
- `POST /api/tasks` - 新規タスク作成
- `PUT /api/tasks/{id}` - タスク更新
- `DELETE /api/tasks/{id}` - タスク削除

### フィルタリング・統計

- `GET /api/statistics` - タスク統計情報取得
- `GET /api/tasks/overdue/list` - 期限切れタスク取得
- `GET /api/tasks/status/{status}` - ステータスによるタスク取得
  - 有効なステータス: `pending`, `in_progress`, `completed`, `cancelled`
- `GET /api/tasks/tag/{tag}` - タグによるタスク取得

### タグ管理

- `POST /api/tasks/{id}/tags` - タスクへのタグ追加
- `DELETE /api/tasks/{id}/tags/{tag}` - タスクからタグ削除

### システム

- `GET /api/health` - ヘルスチェックエンドポイント
- `GET /` - APIドキュメント

## 使用例

### タスクの作成

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "title": "ユーザー認証の実装",
    "description": "APIにJWTベースの認証を追加",
    "priority": "high",
    "due_date": "2024-12-31 23:59:59",
    "tags": ["backend", "security"]
  }'
```

### タスクステータスの更新

```bash
curl -X PUT http://localhost:8000/api/tasks/1 \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_progress"
  }'
```

### 統計情報の取得

```bash
curl http://localhost:8000/api/statistics
```

### ステータスによるフィルタリング

```bash
curl http://localhost:8000/api/tasks/status/pending
```

## タスクのプロパティ

### ステータス値
- `pending` - 初期状態
- `in_progress` - 作業中
- `completed` - 完了
- `cancelled` - キャンセル済み

### 優先度レベル
- `low` - 低優先度
- `medium` - 中優先度（デフォルト）
- `high` - 高優先度
- `urgent` - 緊急

### ステータス遷移

```
pending → in_progress, cancelled
in_progress → completed, cancelled, pending
completed → (最終状態)
cancelled → pending
```

## テスト

```bash
# 全テストの実行
composer test

# 特定のテストスイートの実行
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Integration

# カバレッジ付き実行（Xdebugが必要）
vendor/bin/phpunit --coverage-html coverage
```

## 静的解析

```bash
# PHPStanの実行
composer analyse

# または直接実行
vendor/bin/phpstan analyse
```

## 使用されているデザインパターン

1. **リポジトリパターン** - データアクセスの抽象化
2. **サービス層パターン** - ビジネスロジックのカプセル化
3. **ファクトリーパターン** - タスクの生成
4. **依存性注入** - 疎結合の実現
5. **イミュータブルオブジェクト** - 予測可能な状態管理
6. **DTOパターン** - レイヤー間のデータ転送
7. **ミドルウェアパターン** - リクエスト/レスポンス処理
8. **ステートマシン** - タスクステータス管理

## 主な技術的特徴

### 1. イミュータブルドメインモデル
タスクはイミュータブルです。すべての変更は新しいインスタンスを作成するため、予測可能な動作とテストの容易性を実現しています。

### 2. 型安全性
- 厳格な型宣言を有効化（`declare(strict_types=1)`）
- PHPStan level 8準拠
- すべてのメソッドに完全な型ヒント
- DTOにreadonly プロパティを使用

### 3. エラーハンドリング
- カスタム例外階層
- 適切なHTTPステータスコード
- 詳細なエラーメッセージ
- トランザクション管理

### 4. データベース設計
- 正規化されたスキーマ
- パフォーマンスのための適切なインデックス
- 外部キー制約
- SQLiteとMySQLのサポート

### 5. テスタビリティ
- 全体的な依存性注入
- 適切なインターフェースベースの設計
- 統合テスト用のインメモリSQLite
- 包括的なテストカバレッジ

## 設定

データベース設定は環境変数でカスタマイズできます：

```bash
DB_DRIVER=sqlite       # または mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=tasks
DB_USERNAME=root
DB_PASSWORD=secret
```

## セキュリティ機能

- 複数層での入力検証
- 悪用防止のレート制限
- SQLインジェクション防止のプリペアドステートメント
- 型安全なパラメータハンドリング
- 本番モードでのエラーメッセージからの機密データ除外

## パフォーマンスの考慮事項

- 頻繁にクエリされるカラムへのデータベースインデックス
- 効率的なページネーションサポート
- 最適化されたオートローダー設定
- コネクションプーリングサポート
- 依存関係の遅延ロード

## デモの実行

プロジェクトには実際の動作を確認できるデモスクリプトが含まれています：

```bash
php demo.php
```

このデモでは以下を確認できます：
- タスクの作成
- ステータスの更新
- タグの追加
- フィルタリング
- 統計情報
- バリデーション
- イミュータビリティ

## ライセンス

MIT License

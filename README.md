<<<<<<< HEAD
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
=======
# PHP & Laravel Samples

このリポジトリは、予約管理システムを題材にした PHP と Laravel の技術サンプル集です。  
1 ディレクトリ 1 トピックの構成

## サンプル一覧

| ディレクトリ | テーマ | 主な技術要素 |
| --- | --- | --- |
| `samples/01_rest_api` | Laravel RESTful API | Eloquent ORM / マイグレーション / API リソース / バリデーション |
| `samples/02_job_queue` | 非同期ジョブ処理 | ジョブキュー / イベントリスナー / 失敗処理 / リトライ |
| `samples/03_middleware` | カスタムミドルウェア | 認証 / ロギング / レート制限 / レスポンス変換 |

## ルート PHP サンプル

`app/` ディレクトリには Laravel とは独立した純 PHP 実装のドメインレイヤーを配置しています。予約作成・重複検知・ビジネスポリシー検証を担う `ReservationService` を中心に、以下の設計を導入しました。

- `TimeSlot` / `Reservation` といった値オブジェクト・集約の作成
- `ReservationPolicy` によるビジネスルール（最大利用時間・15分刻み・未来日のみ等）のカプセル化
- `ReservationRepositoryInterface` でデータソースを抽象化（`InMemoryReservationRepository` を同梱）
- `Clock` インターフェースでテスト時に時間を固定化
- 予約の再スケジュール / キャンセル / 集計 API (`statistics()`) を備え、ユースケースを一通りカバー
- 1 日あたりの予約数上限や重複検知を `ReservationPolicy` に集約し、ルール変更を容易にする

整合性とテスト容易性を意識した構成のため、Laravel サンプルとは別に PHP の設計力をアピールできます。

### ユースケース API

```php
$service = new App\ReservationService();

// 予約作成
$booking = $service->createReservation([...]);

// 日程変更（重複・日次上限を自動検証）
$booking = $service->rescheduleReservation($booking['id'], [...]);

// キャンセル
$service->cancelReservation($booking['id']);

// 統計情報（稼働状況ダッシュボード等に活用可能）
$stats = $service->statistics();
```

### 実行方法

```bash
cd PHP_samples
composer install
composer test       # PHPUnit
composer analyse    # PHPStan
```

### ReservationService の利用例

```php
$service = new App\ReservationService();

$reservation = $service->createReservation([
    'user_name'     => 'Alice',
    'resource_name' => 'Room-A',
    'starts_at'     => '2025-01-02T09:00:00Z',
    'ends_at'       => '2025-01-02T10:00:00Z',
]);

$service->listReservations(status: 'booked');     // ステータスフィルタ付き一覧
$service->statistics();                           // リソース別稼働率サマリ
```

`listReservations()` を呼び出すと内部に保存された予約一覧を取得できます。別データソースと連携したい場合は `ReservationRepositoryInterface` を実装するだけで切り替え可能です。

## 推奨バージョン

- PHP 8.2 以上
- Laravel 10.x 以上
- Composer 2.x 以上

## 使い方

1. 各サンプルディレクトリに移動します。
2. `composer install` で依存関係を取得します。
3. `.env` ファイルを設定します（必要に応じて）。
4. `php artisan migrate` でデータベースをセットアップします。
5. `README.md` の手順に従ってアプリケーションまたはテストを実行します。

## プロジェクトの観点

- ドキュメントで設計判断とテスト範囲を明記し、エンジニアリング判断力を示す
- 各ディレクトリで異なる Laravel の機能（API / ジョブ / ミドルウェア）をカバー
- Eloquent ORM、マイグレーション、テストなど、実務で使われる機能を実装
- バリデーション、エラーハンドリング、セキュリティ対策など、実践的な実装を配置
- ルート PHP コードではユースケース／ドメイン層とテスト容易性を重視した構成を採用

## 品質チェックリスト

- `composer quality` で PHPUnit + PHPStan (level 8) をワンコマンド実行
- GitHub Actions（`.github/workflows/test.yml`）で PR 毎に CI を自動実行
- ドメイン層は Value Object / Enum / カスタム例外で堅牢性を担保
- `tests/` では重複検知・日次上限・再スケジュールなどビジネスルールを網羅


>>>>>>> 55babdc (バージョンアップ)

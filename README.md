# PHP & Laravel Samples

このリポジトリは、PHP と Laravel の技術サンプル集です。タスク管理システムと予約管理システムを題材に、モダンなPHP開発のベストプラクティスを実装しています。

## プロジェクト構成

### Laravel タスク管理 REST API (`laravel-app/`)

Laravel 10を使用した本格的なタスク管理REST APIです。認証、CRUD操作、統計情報など、実務で必要となる機能を網羅的に実装しています。

詳細は [laravel-app/README.md](laravel-app/README.md) を参照してください。

### Pure PHP タスク管理 API (ルートディレクトリ)

Laravel に依存しない純粋なPHP実装によるタスク管理APIです。ドメイン駆動設計、イミュータブルエンティティ、リポジトリパターンなどの設計パターンを採用しています。

#### 主な機能

- **RESTful API設計**: RESTのベストプラクティスに従った直感的なエンドポイント
- **ドメイン駆動設計**: ビジネスロジックをカプセル化したリッチドメインモデル
- **イミュータブルエンティティ**: 予測可能性を高めるイミュータブルなTaskエンティティ
- **リポジトリパターン**: ドメイン層とデータアクセス層の明確な分離
- **サービス層**: 依存性注入によるビジネスロジックのカプセル化
- **バリデーション**: 詳細なエラーメッセージを伴う包括的な入力検証
- **ステートマシン**: 検証付きタスクステータス遷移
- **タグシステム**: タスク整理のための柔軟なタグ付け
- **高度なフィルタリング**: ステータス、タグ、期限切れによるフィルタリング
- **統計情報**: 包括的なタスク統計エンドポイント
- **ユニット・統合テスト**: PHPUnitによる完全なテストカバレッジ
- **静的解析**: 最大限の型安全性のためのPHPStan level 8
- **PSR標準**: PSR-4（オートローディング）、PSR-12（コーディングスタイル）に準拠

#### アーキテクチャ

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

- PHP 8.2 以上
- PDO拡張
- JSON拡張
- Composer 2.x 以上

## インストール

```bash
# ルートプロジェクト（Pure PHP API）
composer install
composer test       # PHPUnit
composer analyse    # PHPStan

# Laravel アプリケーション
cd laravel-app
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test
```

## クイックスタート

### Pure PHP API

```bash
# PHPビルトインサーバーの起動
php -S localhost:8000 -t public

# デモスクリプトの実行
php demo.php
```

### Laravel API

```bash
cd laravel-app
php artisan serve
```

詳細は [laravel-app/README.md](laravel-app/README.md) を参照してください。

## 使用されているデザインパターン

1. **リポジトリパターン** - データアクセスの抽象化
2. **サービス層パターン** - ビジネスロジックのカプセル化
3. **ファクトリーパターン** - エンティティの生成
4. **依存性注入** - 疎結合の実現
5. **イミュータブルオブジェクト** - 予測可能な状態管理
6. **DTOパターン** - レイヤー間のデータ転送
7. **ミドルウェアパターン** - リクエスト/レスポンス処理
8. **ステートマシン** - タスクステータス管理

## 品質チェックリスト

- `composer quality` で PHPUnit + PHPStan (level 8) をワンコマンド実行
- GitHub Actions（`.github/workflows/test.yml`）で PR 毎に CI を自動実行
- ドメイン層は Value Object / Enum / カスタム例外で堅牢性を担保
- `tests/` では重複検知・ステータス遷移・再スケジュールなどビジネスルールを網羅

## ライセンス

MIT License

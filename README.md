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

整合性とテスト容易性を意識した構成のため、Laravel サンプルとは別に PHP の設計力をアピールできます。

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



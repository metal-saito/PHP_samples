# Laravel Job Queue

Laravel のジョブキューを使用した非同期処理の実装例です。

## 技術要素

- **ジョブキュー**: 非同期処理の実装
- **イベントリスナー**: イベント駆動アーキテクチャ
- **失敗処理**: ジョブ失敗時のハンドリング
- **リトライ**: 自動リトライ機能

## セットアップ

```bash
cd samples/02_job_queue
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## 使い方

### キューワーカーの起動

```bash
php artisan queue:work
```

### ジョブの投入

```bash
php artisan tinker
>>> App\Jobs\ProcessReservation::dispatch(App\Models\Reservation::first());
```

## テスト

```bash
php artisan test
```

## 設計ポイント

- ジョブクラスによる非同期処理の実装
- イベントリスナーによる処理の分離
- 失敗時の通知とログ記録
- リトライ機能による堅牢性の向上


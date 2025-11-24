# Architecture Notes

## Core PHP Layer (`app/`)

| Component |役割 |
| --- | --- |
| `ReservationService` | ユースケース層。入力正規化 → ポリシー検証 → 予約生成 → リポジトリ保存までを担う |
| `Reservation` / `TimeSlot` | ドメインモデルと値オブジェクト。整合性をコンストラクタで保証し、配列化してプレゼンテーション層に返却 |
| `ReservationPolicy` | 最大予約時間 / 取得できる未来日数 / 15 分刻みといった業務ルールをカプセル化 |
| `ReservationRepositoryInterface` | データアクセスの抽象。 `InMemoryReservationRepository` を同梱し、アプリやテストで差し替え可能 |
| `ReservationStatus` (Enum) | 予約状態（booked / cancelled / completed）を型安全に扱う |
| `Clock` | `SystemClock` / `FrozenClock` により時間依存ロジックをテストしやすくする |
| `Exception\*` | バリデーション・重複・リソース未検出などをドメイン固有の例外で通知 |

### 処理フロー

1. 外部入力を `ReservationService::createReservation()` に渡す  
2. `TimeSlot` を生成してフォーマットや期間を検証  
3. 1 日あたりの予約上限・重複を `ReservationPolicy` と `assertNoOverlap()` で検証  
4. `Reservation` エンティティを生成し保存、配列形式で返却  
5. `rescheduleReservation()` / `cancelReservation()` では永続化済みエンティティを取得し、副作用のない新インスタンスを保存  
6. `statistics()` は状態別件数・リソース別稼働率・次の予約などをまとめて返却し、ダッシュボード実装に活用できる

## Quality Tooling

- `composer quality` で PHPUnit と PHPStan をまとめて実行
- PHPStan は `phpstan.neon.dist` で厳しめ（level 7）の静的解析を有効化
- テストは `FrozenClock` を用いた deterministic な検証を実施
- GitHub Actions では pull request 時に `composer install` → `composer quality` を自動実行



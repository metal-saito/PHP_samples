# Architecture Notes

## Core PHP Layer (`app/`)

| Component |役割 |
| --- | --- |
| `ReservationService` | ユースケース層。入力正規化 → ポリシー検証 → 予約生成 → リポジトリ保存までを担う |
| `Reservation` / `TimeSlot` | ドメインモデルと値オブジェクト。整合性をコンストラクタで保証し、配列化してプレゼンテーション層に返却 |
| `ReservationPolicy` | 最大予約時間 / 取得できる未来日数 / 15 分刻みといった業務ルールをカプセル化 |
| `ReservationRepositoryInterface` | データアクセスの抽象。 `InMemoryReservationRepository` を同梱し、アプリやテストで差し替え可能 |
| `Clock` | `SystemClock` / `FrozenClock` により時間依存ロジックをテストしやすくする |

### 処理フロー

1. 外部入力を `ReservationService::createReservation()` に渡す  
2. `TimeSlot` を生成してフォーマットや期間を検証  
3. `ReservationPolicy` がビジネスルールに違反していないかを確認  
4. リポジトリで重複予約を検出  
5. `Reservation` エンティティを生成し保存、配列形式で返却

## Quality Tooling

- `composer quality` で PHPUnit と PHPStan をまとめて実行
- PHPStan は `phpstan.neon.dist` で厳しめ（level 7）の静的解析を有効化
- テストは `FrozenClock` を用いた deterministic な検証を実施


<?php

/**
 * Reservation Service
 * 
 * Example service class demonstrating PHP OOP patterns
 */

namespace App;

class ReservationService
{
    /**
     * 予約を作成
     */
    public function createReservation(array $data): array
    {
        // バリデーション
        $this->validateReservationData($data);

        // 重複チェック
        if ($this->hasOverlappingReservation($data)) {
            throw new \InvalidArgumentException('Time slot overlaps with existing reservation');
        }

        // 予約作成処理
        return [
            'id' => $this->generateId(),
            'user_name' => $data['user_name'],
            'resource_name' => $data['resource_name'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'status' => 'booked',
        ];
    }

    /**
     * バリデーション
     */
    private function validateReservationData(array $data): void
    {
        $required = ['user_name', 'resource_name', 'starts_at', 'ends_at'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("{$field} is required");
            }
        }
    }

    /**
     * 重複チェック
     */
    private function hasOverlappingReservation(array $data): bool
    {
        // 実装例（実際の実装ではデータベースをチェック）
        return false;
    }

    /**
     * ID生成
     */
    private function generateId(): string
    {
        return 'RES-' . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}


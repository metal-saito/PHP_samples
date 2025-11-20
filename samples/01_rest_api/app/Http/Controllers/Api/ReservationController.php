<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * 予約一覧を取得
     */
    public function index(): JsonResponse
    {
        $reservations = Reservation::active()->orderBy('starts_at')->get();
        return ReservationResource::collection($reservations)->response();
    }

    /**
     * 予約を作成
     */
    public function store(CreateReservationRequest $request): JsonResponse
    {
        // 重複チェック
        $overlapping = Reservation::active()
            ->where('resource_name', $request->resource_name)
            ->get()
            ->first(function ($existing) use ($request) {
                $newReservation = new Reservation($request->validated());
                return $newReservation->overlaps($existing);
            });

        if ($overlapping) {
            return response()->json([
                'error' => 'Time slot overlaps with existing reservation'
            ], 409);
        }

        $reservation = Reservation::create([
            ...$request->validated(),
            'status' => 'booked',
        ]);

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 予約をキャンセル
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'error' => 'Reservation not found'
            ], 404);
        }

        $reservation->update(['status' => 'cancelled']);

        return response()->json(null, 204);
    }
}


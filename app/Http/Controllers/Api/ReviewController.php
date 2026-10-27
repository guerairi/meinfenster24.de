<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $service) {}

    public function index(): JsonResponse
    {
        try {
            return response()->json($this->service->getActiveReviews());
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error'   => 'Bewertungen konnten nicht geladen werden.',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

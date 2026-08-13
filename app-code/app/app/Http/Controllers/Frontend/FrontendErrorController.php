<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontendErrorStoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class FrontendErrorController extends Controller
{
    public function __invoke(FrontendErrorStoreRequest $request): JsonResponse
    {
        Log::channel('stack')->critical('Critical frontend error.', [
            'type' => $request->string('type')->toString(),
            'message' => $request->string('message')->toString(),
            'stack' => $request->string('stack')->toString(),
            'url' => $request->string('url')->toString(),
            'filename' => $request->string('filename')->toString(),
            'line' => $request->integer('line'),
            'column' => $request->integer('column'),
            'user_agent' => $request->userAgent(),
            'page_type' => $request->string('page_type')->toString(),
            'ip' => $request->ip(),
        ]);

        return response()->json(['success' => true], JsonResponse::HTTP_ACCEPTED);
    }
}

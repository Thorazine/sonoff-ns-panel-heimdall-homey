<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\DoorbellService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoorbellWebhookController extends Controller
{
    public function __invoke(Request $request, DoorbellService $doorbellService): JsonResponse
    {
        $providedToken = trim((string) (
            $request->bearerToken()
            ?? $request->header('X-Doorbell-Token')
            ?? $request->input('token', '')
        ));

        $expectedToken = Setting::ensureDoorbellWebhookToken();

        if ($providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'ok' => false,
                'message' => 'Ongeldig deurbel-webhook token.',
            ], 401);
        }

        $panelInput = $request->input('panel');
        $panelSlug = is_string($panelInput) && trim($panelInput) !== '' ? trim($panelInput) : null;

        $event = $doorbellService->ring($panelSlug, source: 'homey-webhook');

        return response()->json([
            'ok' => true,
            'event' => $event,
        ]);
    }
}

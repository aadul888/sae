<?php

namespace App\Http\Controllers;

use App\Services\RealtimeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RealtimeController extends Controller
{
    /**
     * Server-Sent Events (SSE) stream endpoint.
     */
    public function stream(Request $request): StreamedResponse
    {
        $user = session('user');
        if (!$user) {
            return new StreamedResponse(function () {
                echo "event: unauthorized\ndata: {\"message\":\"Unauthenticated\"}\n\n";
                flush();
            }, 401, ['Content-Type' => 'text/event-stream']);
        }

        $lastId = $request->header('Last-Event-ID', $request->query('last_id'));

        // Tutup sesi PHP agar request HTTP paralel berikutnya tidak terblokir
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $response = new StreamedResponse(function () use ($lastId) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            // Kirim retry directive agar browser tahu interval reconnect jika memakai EventSource
            echo "retry: 4000\n";

            $newEvents = RealtimeService::getEventsSince($lastId);
            if (!empty($newEvents)) {
                foreach ($newEvents as $event) {
                    echo "id: {$event['id']}\n";
                    echo "event: {$event['event']}\n";
                    echo "data: " . json_encode($event) . "\n\n";
                }
            } else {
                echo ": keepalive\n\n";
            }
            flush();
            // Langsung akhiri agar PHP-FPM worker segera kembali ke pool dan tidak menahan thread
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    /**
     * Polling fallback endpoint jika client tidak mendukung atau memblokir SSE.
     */
    public function poll(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        $lastId = $request->query('last_id');
        $events = RealtimeService::getEventsSince($lastId);

        return response()->json([
            'status'  => 'success',
            'events'  => $events,
            'last_id' => !empty($events) ? end($events)['id'] : $lastId,
        ]);
    }
}

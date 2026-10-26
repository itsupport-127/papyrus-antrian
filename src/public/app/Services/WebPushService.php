<?php

namespace App\Services;

use App\Models\Ticket;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class WebPushService
{
    /**
     * Send a push notification to every device the customer registered
     * for this ticket (works even if the ticket page tab / browser is
     * closed, as long as they enabled notifications once).
     */
    public function sendToTicket(Ticket $ticket, string $title, string $body, array $data = []): void
    {
        $subscriptions = $ticket->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return;
        }

        $publicKey = config('services.webpush.vapid_public_key');
        $privateKey = config('services.webpush.vapid_private_key');
        $subject = config('services.webpush.vapid_subject');

        if (!$publicKey || !$privateKey) {
            // VAPID keys not configured yet — silently skip so the rest of
            // the app (polling/Echo alerts) still works.
            return;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $subject ?: url('/'),
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ]);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'data' => array_merge($data, [
                'ticket_id' => $ticket->id,
            ]),
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            // 404/410 = subscription expired or unsubscribed by the browser;
            // clean it up so we stop wasting time pinging a dead endpoint.
            if (!$report->isSuccess() && in_array($report->getResponse()?->getStatusCode(), [404, 410])) {
                $ticket->pushSubscriptions()->where('endpoint', $endpoint)->delete();
            }
        }
    }
}

<?php

namespace App\Webhook;

use App\RemoteEvent\SlackPayloadConverter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\RemoteEvent\Exception\ParseException;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class SlackRequestParser extends AbstractRequestParser
{
    public function __construct(
        private readonly SlackPayloadConverter $converter,
    ) {
    }

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new MethodRequestMatcher('POST'),
            new IsJsonRequestMatcher(),
        ]);
    }

    protected function doParse(Request $request, string $secret): ?RemoteEvent
    {
        $payload = $request->toArray();

        if (
            !isset($payload['token'])
            || !isset($payload['team_id'])
            || !isset($payload['context_team_id'])
            || !isset($payload['context_enterprise_id'])
            || !isset($payload['event'])
            || !isset($payload['event']['type'])
            || !isset($payload['event']['user'])
            || !isset($payload['event']['reaction'])
            || !isset($payload['event']['item']['type'])
            || !isset($payload['event']['item']['channel'])
            || !isset($payload['event']['item']['ts'])
            || !isset($payload['event']['item_user'])
            || !isset($payload['event']['event_ts'])
            || !isset($payload['type'])
            || !isset($payload['event_id'])
            || !isset($payload['event_time'])
            || !isset($payload['event_context'])
        ) {
            throw new RejectWebhookException(406, 'Payload is malformed.');
        }

        try {
            return $this->converter->convert($payload);
        } catch (ParseException $e) {
            throw new RejectWebhookException(406, $e->getMessage(), $e);
        }
    }
}

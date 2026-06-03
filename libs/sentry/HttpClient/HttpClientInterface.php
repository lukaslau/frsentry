<?php

declare(strict_types=1);

namespace FrSentry\Sentry\HttpClient;

use FrSentry\Sentry\Options;

interface HttpClientInterface
{
    public function sendRequest(Request $request, Options $options): Response;
}

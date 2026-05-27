<?php

declare (strict_types=1);
namespace FrSentry\Sentry\Integration;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use FrSentry\Sentry\Client;
use FrSentry\Sentry\Options;
use FrSentry\Sentry\SentrySdk;
use FrSentry\Sentry\State\Scope;
use FrSentry\Sentry\Util\Http;
final class OTLPIntegration implements OptionAwareIntegrationInterface
{
    /**
     * @var bool
     */
    private $setupOtlpTracesExporter;
    /**
     * @var string|null
     */
    private $collectorUrl;
    /**
     * @var Options|null
     */
    private $options;
    public function __construct(bool $setupOtlpTracesExporter = \true, ?string $collectorUrl = null)
    {
        $this->setupOtlpTracesExporter = $setupOtlpTracesExporter;
        $this->collectorUrl = $collectorUrl;
    }
    public function setOptions(Options $options): void
    {
        $this->options = $options;
    }
    public function setupOnce(): void
    {
        $options = $this->options;
        if ($options === null) {
            $this->logDebug('Skipping OTLPIntegration setup because client options were not provided.');
            return;
        }
        if ($options->isTracingEnabled()) {
            $this->logDebug('Skipping OTLPIntegration because Sentry tracing is enabled. Disable "traces_sample_rate", "traces_sampler", and "enable_tracing" before using OTLPIntegration.');
            return;
        }
        Scope::registerExternalPropagationContext(static function (): ?array {
            $currentHub = SentrySdk::getCurrentHub();
            $integration = $currentHub->getIntegration(self::class);
            if (!$integration instanceof self) {
                return null;
            }
            return $integration->getCurrentOpenTelemetryPropagationContext();
        });
        if ($this->setupOtlpTracesExporter) {
            $this->configureOtlpTracesExporter($options);
        }
    }
    public function getCollectorUrl(): ?string
    {
        return $this->collectorUrl;
    }
    /**
     * @return array{trace_id: string, span_id: string}|null
     */
    private function getCurrentOpenTelemetryPropagationContext(): ?array
    {
        if (!class_exists(\FrSentry\OpenTelemetry\API\Trace\Span::class)) {
            return null;
        }
        $spanContext = \FrSentry\OpenTelemetry\API\Trace\Span::getCurrent()->getContext();
        if (!$spanContext->isValid()) {
            return null;
        }
        return ['trace_id' => $spanContext->getTraceId(), 'span_id' => $spanContext->getSpanId()];
    }
    private function configureOtlpTracesExporter(Options $options): void
    {
        $endpoint = $this->collectorUrl;
        $headers = [];
        $dsn = $options->getDsn();
        if ($endpoint === null && $dsn !== null) {
            $endpoint = $dsn->getOtlpTracesEndpointUrl();
            $headers['X-Sentry-Auth'] = Http::getSentryAuthHeader($dsn, Client::SDK_IDENTIFIER, Client::SDK_VERSION);
        }
        if ($endpoint === null) {
            $this->logDebug('Skipping automatic OTLP exporter setup because neither a DSN nor a collector URL is configured.');
            return;
        }
        if (!$this->shouldConfigureOtlpTracesExporter()) {
            return;
        }
        try {
            $transport = (new \FrSentry\OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory())->create($endpoint, \FrSentry\OpenTelemetry\Contrib\Otlp\ContentTypes::PROTOBUF, $headers);
            $spanExporter = new \FrSentry\OpenTelemetry\Contrib\Otlp\SpanExporter($transport);
            $batchSpanProcessor = new \FrSentry\OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor($spanExporter, \FrSentry\OpenTelemetry\API\Common\Time\Clock::getDefault());
            (new \FrSentry\OpenTelemetry\SDK\SdkBuilder())->setTracerProvider(new \FrSentry\OpenTelemetry\SDK\Trace\TracerProvider($batchSpanProcessor))->buildAndRegisterGlobal();
        } catch (\Throwable $exception) {
            $this->logDebug(\sprintf('Skipping automatic OTLP exporter setup because it could not be configured: %s', $exception->getMessage()));
        }
    }
    private function shouldConfigureOtlpTracesExporter(): bool
    {
        if (\PHP_VERSION_ID < 80100) {
            $this->logDebug('Skipping automatic OTLP exporter setup because it requires PHP 8.1 or newer.');
            return \false;
        }
        foreach ([\FrSentry\OpenTelemetry\API\Globals::class, \FrSentry\OpenTelemetry\API\Common\Time\Clock::class, \FrSentry\OpenTelemetry\SDK\SdkBuilder::class, \FrSentry\OpenTelemetry\SDK\Trace\TracerProvider::class, \FrSentry\OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor::class, \FrSentry\OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory::class, \FrSentry\OpenTelemetry\Contrib\Otlp\SpanExporter::class] as $className) {
            if (!class_exists($className)) {
                $this->logDebug('Skipping automatic OTLP exporter setup because the required OpenTelemetry SDK/exporter classes are not available.');
                return \false;
            }
        }
        try {
            if (!$this->isNoopTracerProvider(\FrSentry\OpenTelemetry\API\Globals::tracerProvider())) {
                $this->logDebug('Skipping automatic OTLP exporter setup because the existing OpenTelemetry tracer provider cannot be modified after construction.');
                return \false;
            }
        } catch (\Throwable $exception) {
            $this->logDebug(\sprintf('Skipping automatic OTLP exporter setup because the current OpenTelemetry tracer provider could not be inspected: %s', $exception->getMessage()));
            return \false;
        }
        return \true;
    }
    private function isNoopTracerProvider(?object $tracerProvider): bool
    {
        return $tracerProvider === null || $tracerProvider instanceof \FrSentry\OpenTelemetry\API\Trace\NoopTracerProvider;
    }
    private function logDebug(string $message): void
    {
        $this->getLogger()->debug($message);
    }
    private function getLogger(): LoggerInterface
    {
        if ($this->options !== null) {
            return $this->options->getLoggerOrNullLogger();
        }
        $currentHub = SentrySdk::getCurrentHub();
        $client = $currentHub->getClient();
        if ($client !== null) {
            return $client->getOptions()->getLoggerOrNullLogger();
        }
        return new NullLogger();
    }
}

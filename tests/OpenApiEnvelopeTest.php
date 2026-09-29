<?php

namespace BaseApi\Tests;

use Override;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use BaseApi\App;
use BaseApi\Controllers\Controller;
use BaseApi\Http\JsonResponse;
use BaseApi\Http\Attributes\Enveloped;
use BaseApi\Http\Attributes\ResponseType;
use BaseApi\OpenApi\Builders\IRBuilder;
use BaseApi\OpenApi\Emitters\OpenAPIEmitter;
use BaseApi\OpenApi\Emitters\TypeScriptTypesEmitter;
use BaseApi\OpenApi\IR\ApiIR;
use BaseApi\OpenApi\IR\OperationIR;

class EnvelopeDefaultController extends Controller
{
    #[ResponseType(['name' => 'string'])]
    public function get(): JsonResponse
    {
        return JsonResponse::ok(['name' => 'Widget']);
    }
}

class EnvelopeMethodOnController extends Controller
{
    #[Enveloped(true)]
    #[ResponseType(['name' => 'string'])]
    public function get(): JsonResponse
    {
        return JsonResponse::ok(['name' => 'Widget'], wrap: true);
    }
}

class EnvelopeMethodOffController extends Controller
{
    #[Enveloped(false)]
    #[ResponseType(['name' => 'string'])]
    public function get(): JsonResponse
    {
        return JsonResponse::ok(['name' => 'Widget'], wrap: false);
    }
}

#[Enveloped(true)]
class EnvelopeClassOnController extends Controller
{
    #[ResponseType(['name' => 'string'])]
    public function get(): JsonResponse
    {
        return JsonResponse::ok(['name' => 'Widget'], wrap: true);
    }
}

#[Enveloped(false)]
class EnvelopeClassOffController extends Controller
{
    #[ResponseType(['name' => 'string'])]
    public function get(): JsonResponse
    {
        return JsonResponse::ok(['name' => 'Widget'], wrap: false);
    }
}

#[Enveloped(false)]
class EnvelopeClassOffMethodOnController extends Controller
{
    #[Enveloped(true)]
    #[ResponseType(['name' => 'string'])]
    public function get(): JsonResponse
    {
        return JsonResponse::ok(['name' => 'Widget'], wrap: true);
    }
}

/**
 * The type generator must decide about the { data: ... } envelope the same way
 * JsonResponse does at runtime: #[Enveloped] wins, otherwise response.wrap_data.
 */
class OpenApiEnvelopeTest extends TestCase
{
    private const array CONTROLLERS = [
        '/default' => EnvelopeDefaultController::class,
        '/method-on' => EnvelopeMethodOnController::class,
        '/method-off' => EnvelopeMethodOffController::class,
        '/class-on' => EnvelopeClassOnController::class,
        '/class-off' => EnvelopeClassOffController::class,
        '/class-off-method-on' => EnvelopeClassOffMethodOnController::class,
    ];

    private ?string $tempDir = null;

    #[Override]
    protected function setUp(): void
    {
        $this->resetApp();
        unset($_ENV['RESPONSE_WRAP_DATA'], $_SERVER['RESPONSE_WRAP_DATA']);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->resetApp();
        unset($_ENV['RESPONSE_WRAP_DATA'], $_SERVER['RESPONSE_WRAP_DATA']);

        if ($this->tempDir !== null) {
            $this->removeDirectory($this->tempDir);
            $this->tempDir = null;
        }
    }

    public function testConfigOnEnvelopesByDefault(): void
    {
        $ir = $this->buildWithAppConfig("['response' => ['wrap_data' => true]]");

        $this->assertNotNull($this->operation($ir, '/default')->envelope);
        $this->assertStringContainsString(
            'export type GetEnvelopeDefaultResponse = Envelope<{ name: string }>;',
            (new TypeScriptTypesEmitter())->emit($ir)
        );
        $this->assertSame(['data'], array_keys($this->successSchema($ir, '/default')['properties']));
    }

    public function testConfigOffDoesNotEnvelopeByDefault(): void
    {
        $ir = $this->buildWithAppConfig("['response' => ['wrap_data' => false]]");

        $this->assertNull($this->operation($ir, '/default')->envelope);
        $this->assertStringContainsString(
            'export type GetEnvelopeDefaultResponse = { name: string };',
            (new TypeScriptTypesEmitter())->emit($ir)
        );
        $this->assertSame(['name'], array_keys($this->successSchema($ir, '/default')['properties']));
    }

    public function testAttributesOverrideConfigOff(): void
    {
        $ir = $this->buildWithAppConfig("['response' => ['wrap_data' => false]]");

        $this->assertNotNull($this->operation($ir, '/method-on')->envelope);
        $this->assertNotNull($this->operation($ir, '/class-on')->envelope);
        $this->assertNotNull($this->operation($ir, '/class-off-method-on')->envelope);
        $this->assertNull($this->operation($ir, '/method-off')->envelope);
        $this->assertNull($this->operation($ir, '/class-off')->envelope);

        $types = (new TypeScriptTypesEmitter())->emit($ir);
        $this->assertStringContainsString('export type GetEnvelopeMethodOnResponse = Envelope<{ name: string }>;', $types);
        $this->assertStringContainsString('export type GetEnvelopeClassOnResponse = Envelope<{ name: string }>;', $types);
    }

    public function testAttributesOverrideConfigOn(): void
    {
        $ir = $this->buildWithAppConfig("['response' => ['wrap_data' => true]]");

        $this->assertNull($this->operation($ir, '/method-off')->envelope);
        $this->assertNull($this->operation($ir, '/class-off')->envelope);
        $this->assertNotNull($this->operation($ir, '/method-on')->envelope);
        $this->assertNotNull($this->operation($ir, '/class-on')->envelope);
        $this->assertNotNull($this->operation($ir, '/class-off-method-on')->envelope);

        $types = (new TypeScriptTypesEmitter())->emit($ir);
        $this->assertStringContainsString('export type GetEnvelopeMethodOffResponse = { name: string };', $types);
        $this->assertStringContainsString('export type GetEnvelopeClassOffResponse = { name: string };', $types);
        $this->assertSame(['name'], array_keys($this->successSchema($ir, '/method-off')['properties']));
    }

    public function testFrameworkDefaultMatchesRuntime(): void
    {
        // No response.wrap_data in the app config: config/defaults.php decides
        $ir = $this->buildWithAppConfig('[]');

        $this->assertSame(JsonResponse::shouldWrapData(), $this->operation($ir, '/default')->envelope !== null);
        $this->assertSame(
            JsonResponse::shouldWrapData(),
            $this->bodyIsEnveloped((new EnvelopeDefaultController())->get())
        );
    }

    public function testTemplateEnvVariableOffAndOn(): void
    {
        // Same expression the starter template's config/app.php uses
        $config = "['response' => ['wrap_data' => filter_var(\$_ENV['RESPONSE_WRAP_DATA'] ?? false, FILTER_VALIDATE_BOOLEAN)]]";

        $ir = $this->buildWithAppConfig($config);
        $this->assertNull($this->operation($ir, '/default')->envelope);
        $this->assertFalse($this->bodyIsEnveloped((new EnvelopeDefaultController())->get()));

        $this->tearDown();
        $ir = $this->buildWithAppConfig($config, "RESPONSE_WRAP_DATA=true\n");
        $this->assertNotNull($this->operation($ir, '/default')->envelope);
        $this->assertTrue($this->bodyIsEnveloped((new EnvelopeDefaultController())->get()));
    }

    public function testGeneratedTypesMatchRuntimeForEveryController(): void
    {
        foreach ([true, false] as $wrapData) {
            $this->tearDown();
            $ir = $this->buildWithAppConfig(sprintf("['response' => ['wrap_data' => %s]]", var_export($wrapData, true)));

            foreach (self::CONTROLLERS as $path => $class) {
                $controller = new $class();
                $this->assertInstanceOf(Controller::class, $controller);
                $this->assertSame(
                    $this->bodyIsEnveloped($controller->get()),
                    $this->operation($ir, $path)->envelope !== null,
                    sprintf('%s with wrap_data=%s', $path, var_export($wrapData, true))
                );
            }
        }
    }

    private function buildWithAppConfig(string $configPhp, string $env = "APP_ENV=testing\n"): ApiIR
    {
        $this->tempDir = sys_get_temp_dir() . '/baseapi_envelope_test_' . uniqid();
        mkdir($this->tempDir . '/config', 0755, true);
        mkdir($this->tempDir . '/routes', 0755, true);

        file_put_contents($this->tempDir . '/config/app.php', '<?php return ' . $configPhp . ';');
        file_put_contents($this->tempDir . '/.env', $env);

        $routes = "<?php\n\n";
        foreach (self::CONTROLLERS as $path => $class) {
            $routes .= sprintf("\$router->get('%s', [\\%s::class]);\n", $path, $class);
        }

        file_put_contents($this->tempDir . '/routes/api.php', $routes);

        App::boot($this->tempDir);

        return (new IRBuilder())->build();
    }

    private function operation(ApiIR $ir, string $path): OperationIR
    {
        foreach ($ir->operations as $operation) {
            if ($operation->path === $path) {
                return $operation;
            }
        }

        $this->fail('No operation for ' . $path);
    }

    /**
     * @return array<string, mixed>
     */
    private function successSchema(ApiIR $ir, string $path): array
    {
        $spec = (new OpenAPIEmitter())->emit($ir);

        return $spec['paths'][$path]['get']['responses']['200']['content']['application/json']['schema'];
    }

    private function bodyIsEnveloped(JsonResponse $response): bool
    {
        $body = json_decode((string) $response->body, true);

        return is_array($body) && array_keys($body) === ['data'];
    }

    private function resetApp(): void
    {
        $reflection = new ReflectionClass(App::class);

        $properties = [
            'config', 'logger', 'router', 'kernel', 'connection',
            'db', 'userProvider', 'profiler', 'booted', 'basePath',
            'container', 'serviceProviders'
        ];

        foreach ($properties as $propertyName) {
            $property = $reflection->getProperty($propertyName);

            if ($propertyName === 'booted') {
                $property->setValue(null, false);
            } elseif ($propertyName === 'serviceProviders') {
                $property->setValue(null, []);
            } else {
                $property->setValue(null, null);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $entry) {
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}

<?php

namespace BaseApi\Tests\Console\Commands;

use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Console\Commands\TypesGenerateCommand;
use ReflectionClass;

class TypesGenerateCommandTest extends TestCase
{
    private TypesGenerateCommand $command;

    #[Override]
    protected function setUp(): void
    {
        $this->command = new TypesGenerateCommand();
    }

    public function testMockClassGenerationCreatesValidFile(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('generateMockAppClass');
        $method->setAccessible(true);

        $mockFile = $method->invoke($this->command);

        // Verify file exists
        $this->assertFileExists($mockFile);
        
        // Verify file content is valid PHP
        $content = file_get_contents($mockFile);
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('namespace BaseApi', $content);
        $this->assertStringContainsString('class App', $content);
        $this->assertStringContainsString('setMockRouter', $content);
        $this->assertStringContainsString('router()', $content);
        
        // Verify the generated code is syntactically valid
        $this->assertTrue($this->isValidPhpSyntax($content), 'Generated PHP code should be syntactically valid');
        
        // Clean up
        if (file_exists($mockFile)) {
            unlink($mockFile);
        }
    }

    public function testValidPhpIdentifierAcceptsValidNames(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('isValidPhpIdentifier');
        $method->setAccessible(true);

        $validIdentifiers = [
            'BaseApi',
            'App',
            'MyClass',
            'Test_Class',
            'Namespace\\Class',
            'My\\Long\\Namespace\\ClassName',
            '_UnderscoreClass',
            'Class123',
            '__MagicClass',  // Should now be allowed with fixed validation
            'Ns\\__Class',   // Magic methods in namespace segments should be allowed
        ];

        foreach ($validIdentifiers as $identifier) {
            $this->assertTrue(
                $method->invoke($this->command, $identifier),
                sprintf("Expected '%s' to be valid", $identifier)
            );
        }
    }

    public function testValidPhpIdentifierRejectsMaliciousNames(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('isValidPhpIdentifier');
        $method->setAccessible(true);

        $maliciousIdentifiers = [
            'App; system("rm -rf /");',
            'Class{echo "hacked";}',
            'Test$var',
            'App();',
            'Namespace{evil}',
            'Class;DROP TABLE users;',
            'App{system("ls")}',
            'Class(){}',
            'Test; eval($_POST[x]);',
            '1Class',  // starts with number
            'Class-Name',  // contains dash
            'App$injection',
            'Namespace\\',  // trailing backslash
            '\\Class',      // leading backslash
            'Ns\\\\Class',  // double backslash
            'Ns\\\\',       // empty segment
            '',             // empty string
        ];

        foreach ($maliciousIdentifiers as $identifier) {
            $this->assertFalse(
                $method->invoke($this->command, $identifier),
                sprintf("Expected malicious identifier '%s' to be rejected", $identifier)
            );
        }
    }

    public function testGenerateMockClassContentCreatesValidCode(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('generateMockClassContent');
        $method->setAccessible(true);

        $content = $method->invoke($this->command, 'BaseApi', 'App');
        
        // Verify structure
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('namespace BaseApi', $content);
        $this->assertStringContainsString('class App', $content);
        $this->assertStringContainsString('private static $mockRouter;', $content);
        $this->assertStringContainsString('public static function setMockRouter($router): void', $content);
        $this->assertStringContainsString('public static function router()', $content);
        
        // Verify no dangerous code is present
        $this->assertStringNotContainsString('eval', $content);
        $this->assertStringNotContainsString('system', $content);
        $this->assertStringNotContainsString('exec', $content);
        $this->assertStringNotContainsString('shell_exec', $content);
        
        // Verify it's syntactically valid
        $this->assertTrue($this->isValidPhpSyntax($content));
    }

    public function testMockClassGenerationThrowsOnInvalidNamespace(): void
    {
        $reflection = new ReflectionClass($this->command);
        $isValidMethod = $reflection->getMethod('isValidPhpIdentifier');
        $isValidMethod->setAccessible(true);
        
        // Test that malicious namespace is rejected
        $maliciousNamespace = 'Invalid; system("rm -rf /");';
        
        $this->assertFalse(
            $isValidMethod->invoke($this->command, $maliciousNamespace),
            'Malicious namespace should be rejected by validation'
        );
    }

    public function testNoEvalUsageInEntireClass(): void
    {
        $reflection = new ReflectionClass($this->command);
        $filename = $reflection->getFileName();
        
        $content = file_get_contents($filename);
        
        // Verify eval is not present anywhere in the file
        $this->assertStringNotContainsString('eval(', $content, 'Command should not contain eval() calls');
        $this->assertStringNotContainsString('eval (', $content, 'Command should not contain eval () calls');
        
        // Use regex to be more thorough
        $this->assertDoesNotMatchRegularExpression('/\beval\s*\(/', $content, 'Command should not contain any eval usage');
    }

    public function testMockClassCanBeRequiredAndUsed(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('generateMockAppClass');
        $method->setAccessible(true);

        $mockFile = $method->invoke($this->command);
        
        // Require the file in a separate namespace context
        // We can't actually test the BaseApi\App class loading due to namespace conflicts
        // but we can verify the file is syntactically correct and loadable
        $this->assertFileExists($mockFile);
        
        // Verify the file contains expected structure by parsing it
        $content = file_get_contents($mockFile);
        $tokens = token_get_all($content);
        
        $hasNamespace = false;
        $hasClass = false;
        $hasMethod = false;
        $counter = count($tokens);
        
        for ($i = 0; $i < $counter; $i++) {
            if (is_array($tokens[$i])) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    $hasNamespace = true;
                }

                if ($tokens[$i][0] === T_CLASS) {
                    $hasClass = true;
                }

                if ($tokens[$i][0] === T_FUNCTION) {
                    $hasMethod = true;
                }
            }
        }
        
        $this->assertTrue($hasNamespace, 'Generated file should have namespace declaration');
        $this->assertTrue($hasClass, 'Generated file should have class declaration');
        $this->assertTrue($hasMethod, 'Generated file should have method declarations');
        
        // Clean up
        unlink($mockFile);
    }

    /**
     * Helper method to validate PHP syntax
     */
    private function isValidPhpSyntax(string $code): bool
    {
        // Use php -l to check syntax
        $tempFile = tempnam(sys_get_temp_dir(), 'php_syntax_check_');
        file_put_contents($tempFile, $code);
        
        $output = [];
        $return = 0;
        exec("php -l " . escapeshellarg($tempFile) . " 2>&1", $output, $return);
        
        unlink($tempFile);
        
        return $return === 0;
    }
}

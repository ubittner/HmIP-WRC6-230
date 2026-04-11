<?php

declare(strict_types=1);

namespace tests;

use TestCaseSymconValidation;

include_once __DIR__ . '/stubs/Validator.php';

class ValidationTest extends TestCaseSymconValidation
{
    public function testValidateLibrary(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateModule(): void
    {
        $this->validateModule(__DIR__ . '/../HmIP-WRC6-230');
    }
}
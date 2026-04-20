<?php

namespace CharosEMR\Tests\Unit\Application\Shared\Validation;

use CharosEMR\Application\Shared\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function test_required_rule(): void
    {
        $result = $this->validator->validate(['name' => ''], ['name' => 'required']);
        $this->assertTrue($result->hasErrors());
        $this->assertNotNull($result->getFirstError('name'));
    }

    public function test_required_rule_passes_with_value(): void
    {
        $result = $this->validator->validate(['name' => 'John'], ['name' => 'required']);
        $this->assertFalse($result->hasErrors());
    }

    public function test_email_validation(): void
    {
        $result = $this->validator->validate(['email' => 'invalid'], ['email' => 'email']);
        $this->assertTrue($result->hasErrors());

        $result = $this->validator->validate(['email' => 'valid@example.com'], ['email' => 'email']);
        $this->assertFalse($result->hasErrors());
    }

    public function test_min_length(): void
    {
        $result = $this->validator->validate(['name' => 'ab'], ['name' => 'min:3']);
        $this->assertTrue($result->hasErrors());

        $result = $this->validator->validate(['name' => 'abc'], ['name' => 'min:3']);
        $this->assertFalse($result->hasErrors());
    }

    public function test_max_length(): void
    {
        $result = $this->validator->validate(['name' => 'abcdef'], ['name' => 'max:5']);
        $this->assertTrue($result->hasErrors());

        $result = $this->validator->validate(['name' => 'abcde'], ['name' => 'max:5']);
        $this->assertFalse($result->hasErrors());
    }

    public function test_integer_validation(): void
    {
        $result = $this->validator->validate(['age' => 'not-a-number'], ['age' => 'integer']);
        $this->assertTrue($result->hasErrors());

        $result = $this->validator->validate(['age' => '25'], ['age' => 'integer']);
        $this->assertFalse($result->hasErrors());
    }

    public function test_in_rule(): void
    {
        $result = $this->validator->validate(['status' => 'invalid'], ['status' => 'in:pending,confirmed,cancelled']);
        $this->assertTrue($result->hasErrors());

        $result = $this->validator->validate(['status' => 'pending'], ['status' => 'in:pending,confirmed,cancelled']);
        $this->assertFalse($result->hasErrors());
    }

    public function test_multiple_rules(): void
    {
        $result = $this->validator->validate(
            ['email' => 'ab'],
            ['email' => 'required|email|min:5']
        );
        $this->assertTrue($result->hasErrors());
    }
}

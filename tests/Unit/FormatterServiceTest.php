<?php
namespace Shengamo\TumenyPay\Tests\Unit;

use Shengamo\TumenyPay\Services\FormatterService;
use PHPUnit\Framework\TestCase;

class FormatterServiceTest extends TestCase
{

    public function test_it_can_convert_to_k()
    {
        // Test when $n is less than 1000
        $result = FormatterService::convertToK(500);
        $this->assertEquals(500, $result);

        // Test when $n is greater than 1000
        $result = FormatterService::convertToK(1200);
        $this->assertEquals('1.2k', $result);

        // Add more test cases as needed
    }

    public function test_it_can_convert_ngwee_to_kwacha()
    {
        $result = FormatterService::ngweeToKwacha(500);
        $this->assertEquals('5.00', $result);

        // Add more test cases as needed
    }

    public function test_it_can_convert_kwacha_to_ngwee()
    {
        $result = FormatterService::kwachaToNgwee('5.00');
        $this->assertEquals('500', $result);

        // Add more test cases as needed
    }
}

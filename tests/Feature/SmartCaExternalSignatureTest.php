<?php

namespace Tests\Feature;

use App\Services\SmartCaService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SmartCaExternalSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.smartca.base_url' => 'https://smartca.test',
            'services.smartca.signature_base_url' => 'https://smartca.test',
            'services.smartca.sp_id' => 'test', 'services.smartca.sp_password' => 'test']);
        Http::preventStrayRequests();
    }

    public function test_provider_error_is_preserved(): void
    {
        Http::fake(['*' => Http::response(['responseCode' => 400,
            'message' => 'Transaction expired or not completed before', 'signResps' => []])]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transaction expired or not completed before');
        app(SmartCaService::class)->externalizePdfSignature('transaction', 'file', 'signature');
    }

    public function test_pdf_response_is_decoded(): void
    {
        Http::fake(['*' => Http::response(['signResps' => [['signedData' => base64_encode('%PDF-1.7 test')]]])]);
        $result = app(SmartCaService::class)->externalizePdfSignature('transaction', 'file', 'signature');
        $this->assertSame('%PDF-1.7 test', $result['signed_pdf']);
    }

    public function test_non_pdf_response_is_rejected(): void
    {
        Http::fake(['*' => Http::response(['signResps' => [['signedData' => base64_encode('invalid')]]])]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('khong phai PDF');
        app(SmartCaService::class)->externalizePdfSignature('transaction', 'file', 'signature');
    }
}

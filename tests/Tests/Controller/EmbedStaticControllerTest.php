<?php

namespace App\Tests\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmbedStaticControllerTest extends WebTestCase
{
    public function testStylesheetFile(): void
    {
        $client = static::createClient();

        $client->request('GET', '/embed/booking/booking.css');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/css; charset=UTF-8');

        $response = $client->getResponse();

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertStringEndsWith('.css', $response->getFile()->getFilename());
    }

    public function testScriptFile(): void
    {
        $client = static::createClient();

        $client->request('GET', '/embed/booking/booking.js');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/javascript');

        $response = $client->getResponse();

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertStringEndsWith('.js', $response->getFile()->getFilename());
    }
}

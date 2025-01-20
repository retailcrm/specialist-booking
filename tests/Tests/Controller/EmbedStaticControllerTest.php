<?php

namespace App\Tests\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmbedStaticControllerTest extends WebTestCase
{
    public function testStaticFile(): void
    {
        $client = static::createClient();

        $client->request('GET', '/embed/booking/booking.css');

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString((string) $client->getResponse()->getContent(), '.specialist');
    }
}

<?php

namespace OpenOrchestra\FunctionalTests\ApiBundle\Controller;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractWebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * Class AuthorizationControllersTest
 *
 * @group apiFunctional
 */
class AuthorizationControllersTest extends AbstractWebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Set up the test
     */
    public function setUp()
    {
        $this->client = static::createClient();
    }

    /**
     * Test token creation and usage
     */
    public function testTokenCreationAndUsage()
    {
        $this->markTestSkipped('To reactivate when API roles will be implemented');

        $this->client->request('GET', '/api/node/root');
        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $this->client->getResponse()->headers->get('content-type'));
        $this->assertContains('client.access_denied', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/api/node/root?access_token=access_token');
        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $this->client->getResponse()->headers->get('content-type'));
        $this->assertContains('token.blocked', $this->client->getResponse()->getContent());

        $headers = array(
            'PHP_AUTH_USER' => 'test_key',
            'PHP_AUTH_PW' => 'test_secret',
            'HTTP_username' => 'admin',
            'HTTP_password' => 'admin',
        );
        $this->client->request('GET', '/oauth/access_token?grant_type=password', array(), array(), $headers);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $this->client->getResponse()->headers->get('content-type'));
        $tokenReponse = json_decode($this->client->getResponse()->getContent(), true);
        $accessToken = $tokenReponse['access_token'];
        $refreshToken = $tokenReponse['refresh_token'];

        $this->client->request('GET', '/api/node/root?access_token=' . $accessToken);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $this->client->getResponse()->headers->get('content-type'));

        $this->client->request('GET', '/oauth/access_token?grant_type=refresh_token&refresh_token=' . $refreshToken, array(), array(), array('PHP_AUTH_USER' => 'test_key', 'PHP_AUTH_PW' => 'test_secret'));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $this->client->getResponse()->headers->get('content-type'));
        $newTokenReponse = json_decode($this->client->getResponse()->getContent(), true);
        $newAccessToken = $newTokenReponse['access_token'];
        $this->assertNotSame($accessToken, $newAccessToken);

        $this->client->request('GET', '/api/node/root?access_token=' . $newAccessToken);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $this->client->getResponse()->headers->get('content-type'));
    }
}

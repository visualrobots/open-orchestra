<?php

namespace OpenOrchestra\FunctionalTests\UserAdminBundle\Controller;

use OpenOrchestra\FunctionalTests\Utils\AbstractAuthenticatedTest;

/**
 * Class ApiControllersTest
 *
 * @group apiFunctional
 */
class ApiControllersTest extends AbstractAuthenticatedTest
{
    /**
     * @param string $url
     *
     * @dataProvider provideApiUrl
     */
    public function testApi($url)
    {
        $this->client->request('GET', $url . '?access_token=' . $this->getAccessToken());

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $this->client->getResponse()->headers->get('content-type'));
    }

    /**
     * @return array
     */
    public function provideApiUrl()
    {
        return array(
            array('/api/user'),
        );
    }
}

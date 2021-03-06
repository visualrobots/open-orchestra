<?php

namespace OpenOrchestra\FunctionalTests\ApiBundle\Controller;

use OpenOrchestra\FunctionalTests\Utils\AbstractAuthenticatedTest;
use OpenOrchestra\ModelInterface\Model\NodeInterface;
use OpenOrchestra\ModelInterface\Repository\NodeRepositoryInterface;
use OpenOrchestra\ModelInterface\Repository\StatusRepositoryInterface;

/**
 * Class NodeControllerTest
 *
 * @group apiFunctional
 */
class NodeControllerTest extends AbstractAuthenticatedTest
{
    /**
     * @var StatusRepositoryInterface
     */
    protected $statusRepository;

    /**
     * @var NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * Set up the test
     */
    public function setUp()
    {
        parent::setUp();
        $this->nodeRepository = static::$kernel->getContainer()->get('open_orchestra_model.repository.node');
        $this->statusRepository = static::$kernel->getContainer()->get('open_orchestra_model.repository.status');
    }

    /**
     * Reset removing node after test
     */
    public function tearDown()
    {
        $nodes = $this->nodeRepository->findByNodeAndSite('fixture_page_contact', '2');
        $this->undeleteNodes($nodes);
        $this->republishNodes($nodes);
        static::$kernel->getContainer()->get('object_manager')->flush();
        parent::tearDown();
    }

    /**
     * Test delete action
     */
    public function testDeleteAction()
    {
        $this->markTestSkipped('To reactivate when API roles will be implemented');

        $node = $this->nodeRepository->findOneCurrentlyPublished('fixture_page_contact','fr','2');
        $node->getStatus()->setPublished(false);
        static::$kernel->getContainer()->get('object_manager')->flush();

        $nbNode = count($this->nodeRepository->findLastVersionByType('2'));
        $this->client->request('DELETE', '/api/node/fixture_page_contact/delete');
        $nodesDelete = $this->nodeRepository->findLastVersionByType('2');

        $this->assertCount($nbNode - 1, $nodesDelete);
    }

    /**
     * @param array $nodes
     */
    protected function undeleteNodes($nodes)
    {
        foreach ($nodes as $node) {
            $node->setDeleted(false);
        }
    }

    /**
     * @param array $nodes
     */
    protected function republishNodes($nodes)
    {
        foreach ($nodes as $node) {
            $node->getStatus()->setPublished(true);
        }
    }

    /**
     * Test node new version and references
     */
    public function testNewVersioneNode()
    {
        $this->markTestSkipped('To reactivate when API roles will be implemented');

        $node = $this->nodeRepository
            ->findInLastVersion('fixture_page_community', 'fr', '2');
        $this->client->request('POST', '/api/node/fixture_page_community/new-version?language=fr');

        $nodeLastVersion = $this->nodeRepository
            ->findInLastVersion('fixture_page_community', 'fr', '2');

        $this->assertSame($node->getVersion()+1, $nodeLastVersion->getVersion());
    }

    /**
     * Test creation of new language for a node
     */
    public function testCreateNewLanguageNode()
    {
        $this->markTestSkipped('To reactivate when API roles will be implemented');

        $this->client->request('GET', '/api/node/root/show-or-create', array('language' => 'de'));

        $node = $this->nodeRepository
            ->findInLastVersion('root', 'de', '2');

        $this->assertInstanceOf('OpenOrchestra\ModelInterface\Model\NodeInterface', $node);
        $this->assertSame(1, $node->getVersion());
        $this->assertSame('de', $node->getLanguage());
        static::$kernel->getContainer()->get('object_manager')->remove($node);
        static::$kernel->getContainer()->get('object_manager')->flush();
    }

    /**
     * @param NodeInterface $node
     *
     * @return int
     */
    public function countAreaRef(NodeInterface $node)
    {
        $areaRef = 0;
        foreach ($node->getBlocks() as $block) {
            $areaRef = $areaRef + count($block->getAreas());
        }

        return $areaRef;
    }

    /**
     * @param string $name
     * @param int    $publishedVersion
     *
     * @dataProvider provideStatusNameAndPublishedVersion
     */
    public function testChangeNodeStatus($name, $publishedVersion)
    {
        $this->markTestSkipped('To reactivate when API roles will be implemented');

        $node = $this->nodeRepository->findInLastVersion('root', 'fr', '2');
        $newStatus = $this->statusRepository->findOneByName($name);
        $newStatusId = $newStatus->getId();

        $this->client->request(
            'POST',
            '/api/node/' . $node->getId() . '/update',
            array(),
            array(),
            array(),
            json_encode(array('status_id' => $newStatusId))
        );

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $newNode = $this->nodeRepository->findOneCurrentlyPublished('root', 'fr', '2');
        $this->assertEquals($publishedVersion, $newNode->getVersion());
    }

    /**
     * @return array
     */
    public function provideStatusNameAndPublishedVersion()
    {
        return array(
            array('pending', 1),
            array('published', 2),
            array('draft', 1),
        );
    }

    /**
     * Test update not granted
     */
    public function testUpdateNotGranted()
    {
        $this->markTestSkipped('To reactivate when API roles will be implemented');

        $this->username = 'userNoAccess';
        $this->password = 'userNoAccess';
        $this->logIn();

        $node = $this->nodeRepository->findInLastVersion('root', 'fr', '2');
        $this->client->request(
            'POST',
            '/api/node/' . $node->getId() . '/update',
            array(),
            array(),
            array(),
            json_encode(array('status_id' => 'fakeStatus'))
        );
        $this->assertSame(403, $this->client->getResponse()->getStatusCode());
    }
}

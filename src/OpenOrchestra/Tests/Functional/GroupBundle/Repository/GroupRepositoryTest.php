<?php

namespace OpenOrchestra\FunctionalTests\GroupBundle\Repository;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractKernelTestCase;
use OpenOrchestra\Pagination\Configuration\FinderConfiguration;
use OpenOrchestra\Pagination\Configuration\PaginateFinderConfiguration;
use OpenOrchestra\UserBundle\Repository\GroupRepository;

/**
 * Class GroupRepositoryTest
 *
 * @group integrationTest
 */
class GroupRepositoryTest extends AbstractKernelTestCase
{
    /**
     * @var GroupRepository
     */
    protected $repository;

    /**
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel();
        $this->repository = static::$kernel->getContainer()->get('open_orchestra_user.repository.group');
        $this->siteRepository = static::$kernel->getContainer()->get('open_orchestra_model.repository.site');
    }

    /**
     * @param PaginateFinderConfiguration $configuration
     * @param array                       $siteIds
     * @param int                         $count
     *
     * @dataProvider provideConfigurationAndSites
     */
    public function testFindForPaginate(PaginateFinderConfiguration $configuration, array $siteIds, $count)
    {
        $siteIds = $this->generateMongoIdForSite($siteIds);
        $groups = $this->repository->findForPaginate($configuration, $siteIds);

        $this->assertCount($count, $groups);
    }

    /**
     * test count all user
     *
     * @param PaginateFinderConfiguration $configuration
     * @param array                       $siteIds
     * @param int                         $count
     *
     * @dataProvider provideConfigurationAndSites
     */
    public function testCount(PaginateFinderConfiguration $configuration, array $siteIds, $count)
    {
        $siteIds = $this->generateMongoIdForSite($siteIds);
        $groups = $this->repository->count($siteIds);

        $this->assertEquals($count, $groups);
    }

    /**
     * @return array
     */
    public function provideConfigurationAndSites()
    {
        $configuration = new PaginateFinderConfiguration();
        $configuration->setPaginateConfiguration(null, 0, 100, array('label' => 'labels'));
        return array(
            array($configuration, array(), 0),
            array($configuration, array('2'), 2),
            array($configuration, array('2', '3'), 3),
            array($configuration, array('test'), 0),
        );
    }

    /**
     * test findAllWithSite
     */
    public function testFindAllWithSite()
    {
        $groups = $this->repository->findAllWithSite();
        $this->assertCount(3, $groups);
    }

    /**
     * test findAllWithSiteId
     *
     * @param string $siteId
     * @param int    $expectedGroupCount
     *
     * @dataProvider provideSiteId
     */
    public function testFindAllWithSiteId($siteId, $expectedGroupCount)
    {
        $site = $this->siteRepository->findOneBySiteId($siteId);
        $groups = $this->repository->findAllWithSiteId($site->getId());

        $this->assertCount($expectedGroupCount, $groups);
    }

    /**
     * Provite site mongoId
     */
    public function provideSiteId()
    {
        return array(
             'Empty site' => array('3', 1),
             'Demo site' => array('2', 2)
        );
    }

    /**
     * @param PaginateFinderConfiguration $configuration
     * @param array                       $siteIds
     * @param int                         $count
     *
     * @dataProvider provideColumnsAndSearchAndCount
     */
    public function testCountWithFilter(PaginateFinderConfiguration $configuration, array $siteIds, $count)
    {
        $siteIds = $this->generateMongoIdForSite($siteIds);
        $groups = $this->repository->countWithFilter($configuration, $siteIds);

        $this->assertEquals($count, $groups);
    }

    /**
     * @return array
     */
    public function provideColumnsAndSearchAndCount(){

        $configuration = new PaginateFinderConfiguration();
        $configuration->setPaginateConfiguration(null, 0, 100, array('label' => 'labels'));

        $configuration->setSearch(array('language' => 'en', 'label' => 'site'));

        return array(
            array($configuration, array(), 0),
            array($configuration, array('2'), 1),
            array($configuration, array('2', '3'), 1),
            array($configuration, array('test'), 0),
        );
    }

    /**
     * Generate columns of content with search value
     *
     * @param string $searchName
     * @param string $globalSearch
     *
     * @return array
     */
    protected function generateSearchProvider($searchName = '', $globalSearch = '')
    {
        $search = array();
        if (!empty($searchName)) {
            $search['columns'] = array('name' => $searchName);
        }
        if (!empty($globalSearch)) {
            $search['global'] = $globalSearch;
        }

        return $search;
    }

    /**
     * @param array $siteIds
     */
    protected function generateMongoIdForSite(array $siteIds)
    {
        foreach ($siteIds as $key => $siteId) {
            $site = $this->siteRepository->findOneBySiteId($siteId);
            if (null !== $site) {
                $siteIds[$key] = $site->getId();
            } else {
                unset($siteIds[$key]);
            }
        }

        return $siteIds;
    }

    /**
     * Generate relation between columns names and entities attributes
     *
     * @return array
     */
    protected function getDescriptionColumnEntity()
    {

        return array(
            'name' => array('key' => 'name', 'field' => 'name', 'type' => 'string')
        );
    }

}

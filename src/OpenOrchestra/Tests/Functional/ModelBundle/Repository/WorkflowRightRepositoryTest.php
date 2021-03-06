<?php

namespace OpenOrchestra\FunctionalTests\ModelBundle\Repository;

use OpenOrchestra\BaseBundle\Tests\AbstractTest\AbstractKernelTestCase;
use OpenOrchestra\ModelInterface\Repository\WorkflowRightRepositoryInterface;
use Phake;

/**
 * Class WorkflowRightRepositoryTest
 *
 * @group integrationTest
 */
class WorkflowRightRepositoryTest extends AbstractKernelTestCase
{
    /**
     * @var WorkflowRightRepositoryInterface
     */
    protected $repository;
    protected $workflowFunctionRepository;
    protected $userRepository;

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();
        static::bootKernel();
        $this->repository = static::$kernel->getContainer()->get('open_orchestra_model.repository.workflow_right');
        $this->workflowFunctionRepository = static::$kernel->getContainer()->get('open_orchestra_model.repository.workflow_function');
        $this->userRepository = static::$kernel->getContainer()->get('open_orchestra_user.repository.user');
    }

    /**
     * Test find one by user id with valid user
     */
    public function testFindOneByValidUserId()
    {
        $user = $this->userRepository->findOneByUsername('p-admin');
        $workflowRight = $this->repository->findOneByUserId($user->getId());

        $this->assertInstanceOf('OpenOrchestra\ModelInterface\Model\WorkflowRightInterface', $workflowRight);
    }

    /**
     * Test find one by user id with fake user
     */
    public function testFindOneByFakeUserId()
    {
        $workflowRight = $this->repository->findOneByUserId('fakeId');
        $this->assertNull($workflowRight);
    }

    /**
     * @param string $userId
     *
     * @return \OpenOrchestra\ModelInterface\Model\WorkflowRightInterface
     */
    public function findOneByUserId($userId)
    {
        $qb = $this->createQueryBuilder();
        $qb->field('userId')->equals($userId);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Test has element with valid workflow function
     */
    public function testHashElementWithValidWorkflowFunction()
    {
        $contributorFunction = $this->workflowFunctionRepository->findOneBy(array('names.en' => 'Contributor'));
        $hasElement = $this->repository->hasElementWithWorkflowFunction($contributorFunction);
        $this->assertTrue($hasElement);
    }

    /**
     * Test has element with fake workflow function
     */
    public function testHashElementWithFaKeWorkflowFunction()
    {
        $fakeFunction = Phake::mock('OpenOrchestra\ModelInterface\Model\WorkflowFunctionInterface');

        $hasElement = $this->repository->hasElementWithWorkflowFunction($fakeFunction);
        $this->assertFalse($hasElement);
    }
}

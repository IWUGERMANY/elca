<?php


namespace Elca\Tests\Integration\Assistant;


use Beibob\Blibs\Auth;
use Beibob\Blibs\DbHandle;
use Beibob\Blibs\IdFactory;
use Beibob\Blibs\Interfaces\Logger;
use Beibob\Blibs\User;
use Beibob\Blibs\UserStore;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectLocation;
use Elca\Db\ElcaProjectPhase;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\Model\Processing\ElcaLcaProcessor;
use PHPUnit\Framework\TestCase;

abstract class AbstractAssistantTest extends TestCase
{
    /**
     * @var DbHandle
     */
    protected $dbh;

    protected $lcaProcessor;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;


    protected function setUp()
    {
        $this->dbh = DbHandle::getInstance();
        $this->dbh->begin();

        $this->lcaProcessor = $this->createMock(ElcaLcaProcessor::class);
        $this->lcaProcessor->method('computeElement')->willReturn($this->lcaProcessor);

        $this->logger = $this->createMock(Logger::class);
    }

    protected function tearDown()
    {
        $this->dbh->rollback();
    }

    protected function givenUser(): User
    {
        $auth           = new Auth(Auth::METHOD_MD5);
        $auth->authName = IdFactory::getUniqueId();
        $auth->authKey  = IdFactory::getUniqueId();

        $user = User::create($auth, User::STATUS_CONFIRMED);

        UserStore::getInstance()->setUser($user);

        return $user;
    }

    protected function givenProjectVariant(User $user): ElcaProjectVariant
    {
        $project        = ElcaProject::create(ElcaProcessDb::findMostRecentVersion(true)->getId(), $user->getId(),
            $user->getGroupId(), "TestProject", 50);
        $projectVariant = ElcaProjectVariant::create($project->getId(),
            ElcaProjectPhase::findMinIdByConstrMeasure(Elca::CONSTR_MEASURE_PRIVATE)->getId(), "Variant1");

        ElcaProjectLocation::create($projectVariant->getId());
        ElcaProjectConstruction::create($projectVariant->getId());

        return $projectVariant;
    }

    protected function assertElementCount(ElcaProjectVariant $projectVariant, int $expectedCount)
    {
        $elementCount = ElcaElementSet::dbCount(['project_variant_id' => $projectVariant->getId()], true);
        $this->assertEquals($expectedCount, $elementCount);
    }

}
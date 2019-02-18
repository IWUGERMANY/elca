<?php declare(strict_types=1);

namespace Lcc\Controller\Admin;

use Beibob\Blibs\Url;
use Beibob\Blibs\Validator;
use Beibob\HtmlTools\HtmlFormValidator;
use Elca\Controller\AppCtrl;
use Elca\ElcaNumberFormat;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Messages\ElcaMessages;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaAdminNavigationLeftView;
use Lcc\Db\LccEnergySourceCost;
use Lcc\Db\LccEnergySourceCostSet;
use Lcc\Db\LccVersion;
use Lcc\View\Admin\EnergySourceCostsView;

class EnergySourceCostsCtrl extends AppCtrl
{
    /**
     * Will be called on initialization
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init();
    }

    protected function defaultAction($addNavigationViews = true, Validator $validator = null, bool $addNew = false)
    {
        if (!$this->Request->versionId) {
            return;
        }

        $version = LccVersion::findById($this->Request->versionId);

        $view = $this->addView(new EnergySourceCostsView());
        $view->assign('versionId', $version->getId());
        $view->assign('energySourceCosts', $this->provideEnergySourceCosts($version));
        $view->assign('add', $addNew);

        if ($validator) {
            $view->assign('validator', $validator);
        }

        if ($addNavigationViews) {
            $backUrl = Url::parse('/lcc/admin/versions/');
            $backUrl->addParameter(['calcMethod' => $version->getCalcMethod()]);

            $this->Osit->add(
                new ElcaOsitItem(
                    $version->getName(),
                    (string)$backUrl,
                    t('LCC Version')
                )
            );
            $this->Osit->add(new ElcaOsitItem(\t('Energiekosten'), null, t('LCC')));

            $this->setActiveNavItem($version->getCalcMethod());
        }
    }

    protected function saveAction()
    {
        if (!$this->Request->versionId || !$this->Request->isPost()) {
            return;
        }

        if ($this->Request->has('save')) {
            $version = LccVersion::findById($this->Request->versionId);

            $validator = new ElcaValidator($this->Request);

            $allNames = $this->Request->getArray('name');
            $allCosts = $this->Request->getArray('costs');

            $hasNewRow = false;
            foreach ($allNames as $key => $name) {
                $hasNewRow |= !\is_numeric($key);
                $name  = \trim($name);

                $energySourceCosts = LccEnergySourceCost::findById($key);

                $validator->assertNotEmpty('name[' . $key . ']', null, \t('Der Name darf nicht leer bleiben'));
                $validator->assertNotEmpty('costs[' . $key . ']', null, \t('Der Preis darf nicht leer bleiben'));
                $validator->assertNumber('costs[' . $key . ']', $name, \t('Dieser Wert ist keine Zahl'));
                $byVersionIdAndName = LccEnergySourceCost::findByVersionIdAndName($version->getId(), $name);
                $validator->assertTrue(
                    'name[' . $key . ']',
                     !$byVersionIdAndName->isInitialized() || $byVersionIdAndName->getId() == $key,
                    \t('Der Name ist nicht eindeutig')
                );

                if ($validator->isValid()) {
                    $costs = ElcaNumberFormat::fromString($allCosts[$key], 3);

                    if ($energySourceCosts->isInitialized()) {
                        $energySourceCosts->setName(\trim($name));
                        $energySourceCosts->setCosts($costs);
                        $energySourceCosts->update();
                    } else {
                        LccEnergySourceCost::create($version->getId(), $name, $costs);
                    }
                }
            }

            if (!$validator->isValid()) {
                foreach ($validator->getErrors() as $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }

            $this->defaultAction(false, $validator, !$validator->isValid() && $hasNewRow);

        } elseif ($this->Request->has('add')) {
            $this->defaultAction(false, null, true);
        } elseif ($this->Request->has('cancel')) {
            $this->defaultAction(false);
        }
    }


    protected function deleteAction()
    {
        if (!\is_numeric($this->Request->id) || !$this->isAjax())
            return;

        $energySourceCost = LccEnergySourceCost::findById($this->Request->id);
        if (!$energySourceCost->isInitialized())
            return;

        if ($this->Request->has('confirmed')) {
            $energySourceCost->delete();
            $this->defaultAction(false);
        } else {
            $url = Url::parse($this->Request->getURI());
            $url->addParameter(['confirmed' => null]);

            $this->messages->add(\t('Sind Sie sicher, dass Sie "%name%" löschen wollen?', null, ['%name%' => $energySourceCost->getName()]), ElcaMessages::TYPE_CONFIRM, (string)$url);

            $this->defaultAction(false);
        }
    }

    private function setActiveNavItem($calcMethod): void
    {
        $navView = $this->addView(new ElcaAdminNavigationLeftView());
        $navView->assign('activeCtrlName', VersionsCtrl::class);
        $navView->assign('activeCtrlArgs', ['calcMethod' => $calcMethod]);
    }

    private function provideEnergySourceCosts(LccVersion $version): \stdClass
    {
        $energySourceCosts        = new \stdClass();
        $energySourceCosts->name  = [];
        $energySourceCosts->costs = [];

        $energySourceCostSet = LccEnergySourceCostSet::findByVersionId($version->getId(), ['name' => 'ASC']);

        foreach ($energySourceCostSet as $item) {
            $energySourceCosts->name[$item->getId()] = $item->getName();
            $energySourceCosts->costs[$item->getId()] = $item->getCosts();
        }

        return $energySourceCosts;
    }
}

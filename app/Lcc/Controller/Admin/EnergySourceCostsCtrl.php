<?php declare(strict_types=1);

namespace Lcc\Controller\Admin;

use Beibob\Blibs\Url;
use Elca\Controller\AppCtrl;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaAdminNavigationLeftView;
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

    protected function defaultAction($addNavigationViews = true)
    {
        if (!$this->Request->versionId) {
            return;
        }

        $version = LccVersion::findById($this->Request->versionId);

        $view = $this->addView(new EnergySourceCostsView());
        $view->assign('energySourceCosts', $this->provideEnergySourceCosts());


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
            $this->Osit->add(new ElcaOsitItem(t('Energiekosten'), null, t('LCC')));

            $this->setActiveNavItem($version);
        }
    }

    private function setActiveNavItem(LccVersion $version): void
    {
        $this->addView(new ElcaAdminNavigationLeftView())->assign('activeCtrlName', VersionsCtrl::class);
    }

    private function provideEnergySourceCosts()
    {
        $energySourceCosts        = new \stdClass();
        $energySourceCosts->name  = [];
        $energySourceCosts->costs = [];

        return $energySourceCosts;
    }
}

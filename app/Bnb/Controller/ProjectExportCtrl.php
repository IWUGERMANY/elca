<?php
namespace Bnb\Controller;

use Bnb\View\ProjectExportView;
use Elca\Controller\AppCtrl;
use Elca\Controller\ExportsCtrl;
use Elca\Db\ElcaProject;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaProjectNavigationLeftView;

class ProjectExportCtrl extends AppCtrl
{

    protected function defaultAction()
    {
        $this->addView(new ProjectExportView());

        $this->addView(new ElcaProjectNavigationLeftView());
        $this->Osit->add(new ElcaOsitItem(t('Projekt'), null, t('Export')));
    }

    protected function exportAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        if (!$this->Request->id) {
            return;
        }

        //project
        $project = ElcaProject::findById((int)$this->Request->id);

        if (!$this->checkProjectAccess($project) || !$this->Access->isProjectOwnerOrAdmin($project)) {
            return;
        }

        $this->Response->setHeader(
            'X-Redirect: '. $this->getLinkTo(ExportsCtrl::class, 'project', ['id' => $project->getId()])
        );
    }


}
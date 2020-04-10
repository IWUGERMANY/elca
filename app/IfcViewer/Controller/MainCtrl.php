<?php


namespace IfcViewer\Controller;


use Beibob\Blibs\AjaxController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Interfaces\Viewable;
use Beibob\Blibs\JsonView;
use Elca\Controller\ProjectElementsCtrl;

class MainCtrl extends AjaxController
{
    protected function init(array $args = [])
    {
        parent::init($args);

        if ($this->isAjax()) {
            $this->Response->setHeader('Content-Type: application/json');

            /**
             * Always respond json
             */
            $this->setView(new JsonView());
        }
        else {
            $this->setBaseView(new HtmlView('ifc_viewer_base', "ifcViewer"));
        }
    }

    protected function defaultAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        $view = $this->setView(new HtmlView("ifc_viewer_index", "ifcViewer"));
        $view->assign('srcFile', '/testdata/Duplex_A_20110907_optimized');
    }

    protected function elementsAction()
    {
        $ifcGuid = $this->Request->get('ifcGuid');

        if (!$this->isAjax() || !$ifcGuid) {
            return;
        }

        // TODO translate between ifc model guid and elca elementId
        $elementId = 231357;

        $this->redirect(ProjectElementsCtrl::class, $elementId, ['via' => 'ifcViewer']);
    }

    /**
     * Registers a view
     *
     * @param Viewable $view
     * @return Viewable
     */
    protected function setView(Viewable $view = null)
    {
        if (!$this->hasView() || !$this->isAjax()) {
            return parent::setView($view);
        }

        return $this->addView($view);
    }
    // End addView

    /**
     * Registers an additional view
     *
     * @param Viewable $View $View
     * @param null     $viewname
     * @return Viewable -
     */
    protected function addView(Viewable $View, $viewname = null)
    {
        if ($this->isAjax()) {
            return $this->getView()->assignView($View, $viewname);
        }

        return parent::addView($View);
    }
}
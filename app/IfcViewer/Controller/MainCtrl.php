<?php


namespace IfcViewer\Controller;


use Beibob\Blibs\AjaxController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Interfaces\Viewable;
use Beibob\Blibs\JsonView;
use Beibob\Blibs\Session;
use Beibob\Blibs\Environment;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Elca;
use Elca\Db\ElcaElement;

class MainCtrl extends AjaxController
{
    
	/**
     * 
     */
    protected $srcFileDir;
	
	/**
     * @return bool
     */
    public static function isPublic()
    {
        return false;
    }
	

    protected function init(array $args = [])
    {
        parent::init($args);

		// get direcory info from session
        $this->sessionElca = $this->Session->getNamespace("elca");
		$this->sessionUser = $this->Session->getNamespace("blibs.userStore");
										
		$environment = Environment::getInstance();
		$config = $environment->getConfig();
		
		// Example: "www/ifc-data/1961/10345/ifc-viewer" 
		/*$this->srcFileDir = sprintf("%s%s/%s/%s", 
									'/ifc-data/',
									$this->sessionUser->__get('userId'),
									$this->sessionElca->__get('projectId'),
									($config->ifcViewerFilename ?? 'ifc-viewer')
								);
		*/
		// Example: "www/ifc-data/10345/ifc-viewer" 
		$this->srcFileDir = sprintf("%s%d/%s", 
									'/ifc-data/',
									$this->sessionElca->__get('projectId'),
									($config->ifcViewerFilename ?? 'ifc-viewer')
								);
		
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

        // TODO associate project ifc file
        $view = $this->setView(new HtmlView("ifc_viewer_index", "ifcViewer"));
        //$view->assign('srcFile', '/testdata/Duplex_A_20110907_optimized');
        // $view->assign('srcFile', '/testdata/Beispielwand_IFC4_ReferenceView');
		$view->assign('srcFile', $this->srcFileDir);
    }

    protected function elementsAction()
    {
        $ifcGuid = $this->Request->get('ifcGuid');

        if (!$this->isAjax() || !$ifcGuid) {
            return;
        }

        // TODO translate between ifc model guid and elca elementId
        $elementData = ElcaElement::findByAttributeIdentAndTextValue(Elca::ELEMENT_ATTR_IFCGUID, $ifcGuid);
		$elementId = $elementData->getId();
		//$elementId = 231357;
		if((int)$elementId>0) {
			$this->redirect(ProjectElementsCtrl::class, $elementId, ['via' => 'ifcViewer']);
		}	
		return;
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
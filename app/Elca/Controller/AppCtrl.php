<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2016 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * eLCA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * eLCA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace Elca\Controller;

use Beibob\Blibs\ActionController;
use Beibob\Blibs\AjaxController;
use Beibob\Blibs\Interfaces\Viewable;
use Beibob\Blibs\JsonView;
use Beibob\Blibs\Log;
use Beibob\Blibs\Url;
use Beibob\Blibs\Environment;
use Beibob\Blibs\UserStore;
use Elca\Db\ElcaProject;
use Elca\Elca;
use Elca\Model\Navigation\ElcaOsit;
use Elca\Security\ElcaAccess;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Messages\FlashMessages;
use Elca\Service\ProjectAccess;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaMessagesView;
use Elca\View\ElcaOsitView;
use Elca\View\Modal\ModalProjectAccess;

/**
 * Abstract base class for all Elca application controller
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Möller <fab@beibob.de>
 */
abstract class AppCtrl extends AjaxController
{
    /**
     * Workaround for older browser, not supporting xhr2
     */
    const FORM_TO_IFRAME = '_form2iframe';

    /**
     * Name of method that returns true or false if controller is public or not
     */
    const IS_PUBLIC = 'isPublic';

    /**
     * @var Elca $Elca
     */
    protected $Elca;

    /**
     * @var \Elca\Model\Navigation\ElcaOsit
     */
    protected $Osit;

    /**
     * @var ElcaMessages
     */
    protected $messages;

    /**
     * @var FlashMessages
     */
    protected $flashMessages;

    /**
     * @var ElcaAccess $Access
     */
    protected $Access;

    /**
     * @var Log $Log
     */
    protected $Log;

    /**
     * @return bool
     */
    public static function isPublic()
    {
        return false;
    }
    // End init

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        if ($this->isAjax()) {
            /**
             * prevent html errors in ajax calls
             */
            ini_set('html_errors', 0);

            /**
             * Iframe fallback for ajaxForm plugin
             */
//            if ($this->Request->has(self::FORM_TO_IFRAME)) {
//                $this->Response->setHeader('Content-Type: text/html');
//                $this->setView(new ElcaIE9JsonView());
//            } else {
                $this->Response->setHeader('Content-Type: application/json');

                /**
                 * Always respond json
                 */
                $this->setView(new JsonView());
//            }
        } else {
            $View = $this->setBaseView(new ElcaBaseView());
            $View->assign('version', 'v'.Elca::VERSION_BBSR);
            $View->assign('username', UserStore::getInstance()->getUser()->getAuthName());
            $View->assign('printmedia', $this->Request->has('print') ? 'all' : 'print');
            $View->assign('pdfmode', $this->Request->has('pdf') ? 'pdf' : '');
        }

        /**
         * Init singleton instances
         */
        $this->Elca          = Elca::getInstance();
        $this->Access        = ElcaAccess::getInstance();
        $this->messages      = $this->get(ElcaMessages::class);
        $this->flashMessages = $this->get(FlashMessages::class);
        $this->Osit          = ElcaOsit::getInstance();
        $this->Log           = Log::getInstance();
    }
    // End finalize

    /**
     * Called after actions
     *
     * @return void -
     */
    protected function finalize()
    {
        /**
         * Show messages
         */
        if ($this->isAjax() && $this->flashMessages->has()) {
            $this->messages->appendBag($this->flashMessages);
        }

        if ($this->isAjax() && $this->messages->has()) {

            $view = $this->addView(new ElcaMessagesView());
            $view->assign('messages', $this->messages);
        }

        /**
         * Show osit
         */
        if ($this->isAjax() && $this->Osit->hasItems()) {
            $this->addView(new ElcaOsitView());
        }

        /**
         * Inform the client about the current projectId
         */
        if ($this->isAjax()) {
            $this->getView()->assign('projectId', $this->Elca->getProjectId());
        }
    }
    // End isAjax

    /**
     * Returns true if this controller is called asynchron
     *
     * @returns boolean
     */
    protected function isAjax()
    {
        return parent::isAjax() || $this->Request->has(self::FORM_TO_IFRAME);
    }
    // End setView

    /**
     * Registers a view
     *
     * @param Viewable $View
     * @return Viewable
     */
    protected function setView(Viewable $View = null)
    {
        if (!$this->hasView() || !$this->isAjax()) {
            return parent::setView($View);
        }

        return $this->addView($View);
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
    // End getViewByName

    /**
     * Get a view by name
     *
     * @param $name
     * @return Viewable
     */
    protected function getViewByName($name)
    {
        if ($this->isAjax()) {
            return $this->getView()->getView($name);
        }

        return $this->getView();
    }
    // End getViewByName

    /**
     * Returns true if a view is set by name
     * Works only in ajax requests
     *
     * @param  string $name
     * @return boolean
     */
    protected function hasViewByName($name)
    {
        if (!$this->isAjax()) {
            return false;
        }

        return $this->getView()->hasView($name);
    }
    // End forward

    /**
     * Forwards to another controller and sets its views for the current one
     *
     * @param  string $ctrlName
     * @param  string $action
     * @param  string $module
     * @param  array  $initArgs
     * @return void -
     */
    protected function forward($ctrlName = null, $action = null, $module = null, array $initArgs = [])
    {
        if (!$this->isAjax()) {
            parent::forward($ctrlName, $action, $module, $initArgs);

            return;
        }

        if (is_null($ctrlName)) {
            $ctrlName = get_class($this);
        }

        if (!class_exists($ctrlName)) {
            return;
        }

        /** @var ActionController $ActionController */
        $ActionController = new $ctrlName();
        $ActionController->process($action, $initArgs, false);

        if ($ActionController->hasView()) {
            $View = $ActionController->getView();

            if ($View instanceOf JsonView && $this->getView() instanceOf JsonView) {
                foreach ($View->getViews() as $name => $AdditionalView) {
                    $this->addView($AdditionalView);
                }
            } else {
                $this->setView($View);
            }
        }
    }
    // End reloadHashUrlWithEvent

    /**
     * Reloads the hash url part of the site, adding the _event parameter to the url
     *
     * @param  string $event
     * @return boolean
     */
    protected function notifyHashUrlWithEvent($event)
    {
        if (!$hashUrl = $this->Request->getHttpHeader('X-Hash-Url')) {
            return $this->reloadUrl();
        }

        $Url = Url::parse($hashUrl);
        $Url->addParameter(['_event' => $event]);

        $this->runJs("jBlibs.App.query('".(string)$Url."');");

        return true;
    }
    // End hasEvent

    /**
     * Checks if an event was received
     *
     * @param  string $event
     * @return boolean
     */
    protected function hasEvent($event)
    {
        return $this->Request->has('_event') && $this->Request->get('_event') === $event;
    }
    // End checkProjectAccess

    /**
     * Checks the permissions of the current user for a project
     *
     * @param  ElcaProject $project
     * @param  string      $msg
     * @return boolean
     */
    protected function checkProjectAccess(
        ElcaProject $project = null,
		$removeProject = null,
        $msg = 'Sie haben keine Berechtigung für diese Aktion'
    ) {
    	$project = !is_null($project) ? $project : $this->Elca->getProject();
		$removeProject = !is_null($removeProject) ? $removeProject : false;
	
        $projectAccess = $this->container->get(ProjectAccess::class);

		
        if ($this->Access->canAccessProject(
            $project,
            $projectAccess->retrieveEncryptedPasswordFromSessionForProject($project)
        )
        ) {
            return true;
        }
		
        $this->Elca->unsetProjectId();
       
        if ($project->hasPassword()) {
			
			// deleting of shared project without pwd allowed
			if($removeProject && $this->Access->hasProjectAccessTokenAndCanDelSharedProject($project)) 
			{
				return true;
			}	
			// password required
			return $this->passwordPromptRedirect($project->getId());
        }

		
        // reset view
        if ($this->isAjax()) {
            parent::setView(new JsonView());
        } else {
            parent::setView(null);
        }

        $this->noAccessRedirect($this->getLinkTo(ProjectsCtrl::class), $msg);

        return false;
    }

    /**
     * Redirects to the passwordPrompt action
     */
    protected function passwordPromptRedirect($projectId)
    {
        if (!$this->isAjax()) {
            $this->redirect(
                ProjectsCtrl::class,
                'passwordPrompt',
                ['id' => $projectId, 'url' => urlencode($this->Request->getUri())]
            );

            return false;
        }

        $view = $this->addView(new ModalProjectAccess());
        $view->assign('origin', $this->Request->getUri());
        $view->assign('originCtrl', $this->FrontController->getActionControllerName());
        $view->assign('originAction', $this->FrontController->getAction());
        $view->assign('originArgs', json_encode(Url::parse($this->Request->getUri())->getParameter()));

        return false;
    }
    // End noAccessRedirect

    /**
     * Redirects to the given url after showing no access message
     *
     * @param  string $url
     * @param string  $msg
     * @return boolean
     */
    protected function noAccessRedirect($url = '/', $msg = 'Keine Berechtigung')
    {
        if ($this->isAjax()) {
            $this->messages->add($msg, ElcaMessages::TYPE_ERROR);
            $this->getView()->assign('noAccess', $url);
        } else {
            $NoAccess      = $this->Session->getNamespace('elca.noAccess', true);
            $NoAccess->url = $url;
            $NoAccess->msg = $msg;
            $this->redirect('Elca\Controller\NoAccessCtrl');
        }
    }
    // End updateHashUrl

    /**
     * Updates the hash part of the url within the next response
     *
     * @param  string $url
     * @param bool    $dontOverwrite
     * @param bool    $replace
     * @return boolean
     */
    protected function updateHashUrl($url, $dontOverwrite = false, $replace = false)
    {
        $header = $replace ? 'X-Replace-Hash' : 'X-Update-Hash';

        if ($dontOverwrite) {
            $headers = headers_list();
            foreach ($headers as $hdr) {
                if (\mb_stripos($hdr, $header) !== false) {
                    return;
                }
            }
        }

        $this->Response->setHeader($header.': '.(string)$url);
    }

    protected function loadHashUrl(string $url)
    {
        $this->Response->setHeader('X-Load-Hash: ' . $url);
    }


    protected function reloadUrl()
    {
        $this->Response->setHeader('X-Reload: true');
    }

    protected function reloadHashUrl()
    {
        $this->Response->setHeader('X-Reload-Hash: true');
    }

    /**
     * Adds a javascript expression to run on client side
     *
     * @param  string $expression
     * @return void -
     */
    protected function runJs($expression)
    {
        if ($expression) {
            $this->getView()->assign('run', $expression);
        }
    }

    // End isBaseRequest

    /**
     * Returns true if this request is a base request
     *
     * @param  -
     * @return boolean
     */
    protected function isBaseRequest()
    {
        return !$this->isAjax() || isset($this->Request->_isBaseReq);
    }

    protected function langAction()
    {
        $locale = $this->container->get('Elca\Service\ElcaLocale');
        try {
            $locale->setLocale($this->Request->lang);
            if ($this->Request->origin) {
                $this->Response->setHeaderLocation(base64_decode($this->Request->origin));
            } else {
                $this->Response->setHeaderLocation(
                    $this->FrontController->getUrlTo($this->FrontController->getDefaultActionControllerName())
                );
            }
        }
        catch (\Exception $Exception) {
            $this->messages->add($Exception->getMessage());
        }

    }
    // End isRestrictedPage
}
// End EcoAppCtrl

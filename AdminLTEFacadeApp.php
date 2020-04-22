<?php
namespace exface\AdminLTEFacade;

use exface\Core\Interfaces\InstallerInterface;
use exface\Core\Facades\AbstractHttpFacade\HttpFacadeInstaller;
use exface\Core\CommonLogic\Model\App;
use exface\Core\Factories\FacadeFactory;
use exface\Core\Facades\AbstractPWAFacade\ServiceWorkerInstaller;

class AdminLTEFacadeApp extends App
{

    /**
     * {@inheritdoc}
     * 
     * An additional installer is included to condigure the routing for the HTTP facades.
     * 
     * @see App::getInstaller($injected_installer)
     */
    public function getInstaller(InstallerInterface $injected_installer = null)
    {
        $installer = parent::getInstaller($injected_installer);
        
        // Routing installer
        $tplInstaller = new HttpFacadeInstaller($this->getSelector());
        $tplInstaller->setFacade(FacadeFactory::createFromString('exface.AdminLTEFacade.AdminLTEFacade', $this->getWorkbench()));
        $installer->addInstaller($tplInstaller);
        
        // ServiceWorker installer
        $installer->addInstaller(ServiceWorkerInstaller::fromConfig($this->getSelector(), $this->getConfig()));
        
        return $installer;
    }
}
?>
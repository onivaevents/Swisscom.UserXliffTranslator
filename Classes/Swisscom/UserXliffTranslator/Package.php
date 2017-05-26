<?php
namespace Swisscom\UserXliffTranslator;

/*
 * This file is part of the Swisscom.UserXliffTranslator package.
 */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;

/**
 * The UserXliffTranslator Package
 */
class Package extends BasePackage
{
    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * Function monitors changes on the overridden translation files.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect(\TYPO3\Flow\Core\Booting\Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
            if ($step->getIdentifier() === 'typo3.flow:systemfilemonitor') {
                $objectManager = $bootstrap->getObjectManager();
                $configurationManager = $objectManager->get('TYPO3\Flow\Configuration\ConfigurationManager');
                $userXliffBasePath = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Swisscom.UserXliffTranslator.userXliffBasePath');
                if (is_dir($userXliffBasePath)) {
                    $templateFileMonitor = \TYPO3\Flow\Monitor\FileMonitor::createFileMonitorAtBoot('UserXliffTranslator_TranslationFiles', $bootstrap);
                    $templateFileMonitor->monitorDirectory($userXliffBasePath);
                    $templateFileMonitor->detectChanges();
                    $templateFileMonitor->shutdownObject();
                }
            }
        });
        $flushTranslationCache = function ($identifier, $changedFiles) use ($bootstrap) {
            if ($identifier !== 'UserXliffTranslator_TranslationFiles') {
                return;
            }

            $objectManager = $bootstrap->getObjectManager();
            if ($objectManager->isRegistered(\TYPO3\Flow\Cache\CacheManager::class)) {
                $cacheManager = $objectManager->get(\TYPO3\Flow\Cache\CacheManager::class);

                $cacheManager->getCache('Flow_I18n_XmlModelCache')->flush();
            }
        };
        $dispatcher->connect(\TYPO3\Flow\Monitor\FileMonitor::class, 'filesHaveChanged', $flushTranslationCache);
    }
}

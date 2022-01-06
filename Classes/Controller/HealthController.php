<?php
namespace Libeo\LboHealth\Controller;

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use TYPO3\CMS\Core\Cache\Backend\RedisBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class HealthController extends ActionController
{
    public function statusAction()
    {
        if ($this->settings['checks']['mysql']) {
            $status['mysql'] = self::checkDatabaseConnection();
        }
        if ($this->settings['checks']['redis']) {
            $status['redis'] = self::checkRedis();
        }
        if ($this->settings['checks']['solr']) {
            $status['solr'] = self::checkSolr();
        }

        foreach($status as $service => $state) {
            if ($state === false) {
                throw new \Exception('Service ' . $service . ' not responding !');
            }
        }

        return json_encode($status);
    }

    /**
     * @return bool
     */
    private function checkDatabaseConnection(): bool
    {
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->set('lbo_health', 'checkMySql', 'ok');

        return true;
    }

    /**
     * @return bool
     */
    private function checkRedis(): bool
    {
        /** @var FrontendInterface $cacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class)->getCache('lbo_health');
        $cacheManager->set('status', 'ok');
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['lbo_health']['backend'] === RedisBackend::class
            && $cacheManager->get('status') === 'ok') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function checkSolr(): bool
    {
        $solrConnections = GeneralUtility::makeInstance(ConnectionManager::class)->getAllConnections();
        foreach ($solrConnections as $solrConnection) {
            $coreAdmin = $solrConnection->getAdminService();
            /** @var $solrConnection SolrConnection */
            if (!$coreAdmin->ping()) {
                return false;
            }
        }
        return true;
    }
}

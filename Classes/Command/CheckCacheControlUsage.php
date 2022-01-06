<?php

namespace Libeo\LboHealth\Command;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CheckCacheControlUsage extends Command
{
    private $requestFactory;

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    protected function configure()
    {
        $this
            ->setDescription('Check cache-control usage')
            ->addArgument('sitemaps', InputArgument::REQUIRED, 'URLs of sitemap to check');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urlsToCheck = [];
        if ($sitemapsArgument = $input->getArgument('sitemaps')) {
            $sitemaps = explode(',', $sitemapsArgument);
            foreach ($sitemaps as $sitemap) {
                $this->getAllPages($urlsToCheck, $sitemap);
            }
        }

        $listUrlsWithoutCache = [];
        foreach ($urlsToCheck as $key => $url) {
            /** @var ResponseInterface $response */
            $response = $this->requestFactory->request($url, 'GET', []);
            $this->progressBar($key, count($urlsToCheck), count($listUrlsWithoutCache));

            $headers = $response->getHeaders();
            if (isset($headers['Cache-Control'])) {
                if (strpos($headers['Cache-Control'][0], 'no-cache') !== false || strpos($headers['Cache-Control'][0], 'revalidate') !== false) {
                    $listUrlsWithoutCache[] = $url;
                }
            }
        }

        if ($listUrlsWithoutCache) {
            $output->writeln('URLs without cache :');
            foreach ($listUrlsWithoutCache as $url) {
                $output->writeln($url);
            }
        } else {
            $output->writeln('Every URLs can be in cache.');
        }

        return true;
    }

    private function getAllPages(array &$listUrlsWithoutCache, $sitemap)
    {
        $urlsPart = parse_url($sitemap);
        /** @var ResponseInterface $response */
        $response = $this->requestFactory->request($sitemap . '&no_cache=1', 'GET', []);
        $data = $response->getBody();
        $xml = new \SimpleXMLElement($data);
        foreach ($xml->url as $url_list) {
            $url = (string) $url_list->loc;
            $listUrlsWithoutCache[] = $urlsPart['scheme'] . '://' . $urlsPart['host'] . $url;
        }
    }

    private function progressBar($done, $total, $nbProblem)
    {
        $perc = floor(($done / $total) * 100);
        $left = 100 - $perc;
        $write = sprintf("\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% - $done/$total ($nbProblem problems)", "", "");
        fwrite(STDERR, $write);
    }

}
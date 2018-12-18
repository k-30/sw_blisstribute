<?php

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

require_once __DIR__ . '/../Article/Sync.php';

class Shopware_Components_Blisstribute_Command_ArticleExport extends ShopwareCommand
{
    /**
     * Little helper function for the ...ByVhsNumber methods
     *
     * @param string $vhsNumber
     * @param string $articleNumber
     * @param string $ean
     *
     * @throws \Shopware\Components\Api\Exception\NotFoundException
     * @throws \Shopware\Components\Api\Exception\ParameterMissingException
     *
     * @return int
     */
    public function getIdFromVhsNumber($vhsNumber, $articleNumber = '', $ean = '')
    {
        $modelManager = $this->container->get('models');
        $articleId = $modelManager->getConnection()->fetchColumn(
            'SELECT articleId FROM s_articles_attributes WHERE blisstribute_vhs_number LIKE :vhsNumber',
            [':vhsNumber' => $this->_makeValueMoreSearchable($vhsNumber)]
        );

        if (!empty($articleId)) {
            return $articleId;
        }

        $articleId = $modelManager->getConnection()->fetchColumn(
            'SELECT articleId from s_articles_details WHERE ordernumber LIKE :articleNumber',
            [':articleNumber' => $this->_makeValueMoreSearchable($articleNumber)]
        );

        if (!empty($articleId)) {
            return $articleId;
        }

        $articleId = $modelManager->getConnection()->fetchColumn(
            'SELECT articleId from s_articles_details WHERE ean LIKE :articleEan',
            [':articleEan' => $this->_makeValueMoreSearchable($ean)]
        );

        if (!empty($articleId)) {
            return $articleId;
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('bliss:articleExport')
            ->setDescription('Exports a single article to blisstribute.')
            ->addArgument('identification', InputArgument::REQUIRED, 'the identification value to identify the article')
            ->setHelp(<<<EOF
The <info>%command.name%</info> exports a single order to blisstribute.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $identification = $input->getArgument('identification');
        $output->writeln('<info>blisstribute article export started for article(s) identified by ' . $identification . '</info>');

        $modelManager = $this->container->get('models');
        /** @var \Shopware\CustomModels\Blisstribute\BlisstributeArticleRepository $blisstributeArticleRepository */
        $blisstributeArticleRepository = $modelManager->getRepository('Shopware\CustomModels\Blisstribute\BlisstributeArticle');

        $articleId = $this->getIdFromVhsNumber($identification, $identification, $identification);
        if ($articleId == 0) {
            $output->writeln('<error>buuuhuu.. could not find article by identification ' . $identification . '. script terminated');
        }

        $blisstributeArticle = $blisstributeArticleRepository->fetchByArticleId((int) $articleId);
        if (empty($blisstributeArticle)) {
            $output->writeln('<error>buuuhuu.. could not find blisstribute article by identification ' . $identification . '. script terminated');
        }

        $articleSync = new Shopware_Components_Blisstribute_Article_Sync(
            $this->container->get('plugins')->Backend()->ExitBBlisstribute()->Config()
        );
        $result = $articleSync->processSingleArticleSync($blisstributeArticle);

        $output->writeln('<info>export result: ' . (int) $result . '</info>');
    }

    /**
     * @param string $input
     *
     * @return string
     */
    private function _makeValueMoreSearchable($input)
    {
        return '%' . $input . '%';
    }
}

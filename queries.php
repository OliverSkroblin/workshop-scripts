<?php

namespace Scripts\Examples;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

require_once __DIR__ . '/../examples/base-script.php';

$env = 'prod'; // by default, kernel gets booted in dev

$kernel = require __DIR__ . '/../boot/boot.php';

class Main extends BaseScript
{
    public function run()
    {
        $time = microtime(true); $memory = memory_get_usage();

        $criteria = new Criteria();
        $criteria->setLimit(250);

        $categories = $this->getContainer()
            ->get('category.repository')
            ->search($criteria, Context::createCLIContext())
            ->getEntities();

        foreach ($categories as $category) {
            $count = $this->getContainer()
                ->get(Connection::class)
                ->fetchOne(
                    'SELECT COUNT(*) FROM product_category_tree WHERE category_id = :id',
                    ['id' => Uuid::fromHexToBytes($category->getId())]
                );

            /** @var Entity $product */
            $category->addArrayExtension('meta', ['count' => $count]);
        }

        $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());

        $table = $output->createTable();

        $table->setHeaders(['Name', 'Product count']);

        foreach ($categories as $category) {
            $ids = $category->getExtension('meta')->all();

            $table->addRow([$category->getName(), $ids['count']]);
        }

        $table->render();

        $output->success(
            sprintf('Fetched %s categories and calculated product count for each in %s consuming %s MB', count($categories), round(microtime(true) - $time, 6), round((memory_get_usage()-$memory) / 1024 / 1024, 2))
        );
    }

}


(new Main($kernel))->run();

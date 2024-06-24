<?php

namespace Scripts\Examples;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\CountSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;

require_once __DIR__ . '/../examples/base-script.php';

$env = 'prod'; // by default, kernel gets booted in dev

$kernel = require __DIR__ . '/../boot/boot.php';

class CategoryLoader
{
    public function __construct(private readonly Container $container)
    {
    }

    public function load(): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('media');
        $criteria->addSorting(new CountSorting('products.id', FieldSorting::DESCENDING));

        $ids = $this->container
            ->get('category.repository')
            ->searchIds($criteria, Context::createCLIContext());

        $loader = new MetaLoader($this->container);

        $categories = [];
        foreach ($ids->getIds() as $id) {
            $category = $this->container->get('category.repository')
                ->search(new Criteria([$id]), Context::createCLIContext())
                ->first();

            $category->addArrayExtension('meta', [
                'products' => $loader->products($category->getId()),
            ]);

            $categories[] = $category;
        }

        return $categories;
    }
}

class MetaLoader
{
    public function __construct(private readonly Container $container)
    {
    }

    public function products(string $categoryId): array
    {
        return $this->container->get(Connection::class)
            ->fetchFirstColumn(
                'SELECT LOWER(HEX(product_id)) FROM product_category WHERE category_id = :categoryId',
                ['categoryId' => Uuid::fromHexToBytes($categoryId)]
            );
    }
}

class Main extends BaseScript
{
    public function run()
    {
        $time = microtime(true); $memory = memory_get_usage();

        $categories = (new CategoryLoader($this->container))->load();

        $categories = array_slice($categories, 0, 20);

        $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());

        $table = $output->createTable();

        $table->setHeaders(['Name', 'Product count', 'Has media']);

        /** @var CategoryEntity $category */
        foreach ($categories as $category) {
            $ids = $category->getExtension('meta')->all();

            $table->addRow([$category->getName(), count($ids['products']), $category->getMedia() !== null]);
        }

        $table->render();

        $output->success(
            sprintf('Fetched %s categories and calculated product count for each in %s consuming %s MB', count($categories), round(microtime(true) - $time, 6), round((memory_get_usage()-$memory) / 1024 / 1024, 2))
        );
    }
}


(new Main($kernel))->run();

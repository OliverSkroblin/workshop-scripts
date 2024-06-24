<?php

namespace Scripts\Examples;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\Test\TestDefaults;
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

        $ids = $this->getContainer()->get(Connection::class)
            ->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM product LIMIT 100');

        $products = [];
        foreach ($ids as $id) {
            $products[] = $this->getContainer()->get('product.repository')
                ->search(new Criteria([$id]), Context::createDefaultContext())
                ->first();
        }

        $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());

        $output->success(
            sprintf('Loaded all %s products within %s seconds and %s MB', count($ids), round(microtime(true) - $time, 6), round((memory_get_usage()-$memory) / 1024 / 1024, 2))
        );
    }
}


(new Main($kernel))->run();

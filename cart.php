<?php

namespace Scripts\Examples;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
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
        $ids = $this->getContainer()->get(Connection::class)
            ->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM product LIMIT 50');

        // all services are public in script mode
        $service = $this->getContainer()->get(CartService::class);

        $token = Uuid::randomHex();

        $context = $this->getContainer()
            ->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL);

        $cart = $service->getCart($token, $context);

        $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());

        $time = microtime(true); $memory = memory_get_usage();

        foreach ($ids as $id) {
            $item = $this->getContainer()
                ->get(ProductLineItemFactory::class)
                ->create(['id' => $id], $context);

            $cart = $service->add($cart, [$item], $context);
        }

        $output->success(
            sprintf('Added %s products to cart within %s seconds and %s MB', count($ids), round(microtime(true) - $time, 6), round((memory_get_usage()-$memory) / 1024 / 1024, 2))
        );
    }
}


(new Main($kernel))->run();

<?php

namespace Scripts\Examples;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Util\AfterSort;
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
        $criteria = new Criteria();

        $categories = $this->getContainer()
            ->get('category.repository')
            ->search($criteria, Context::createCLIContext())
            ->getEntities();

        $time = microtime(true); $memory = memory_get_usage();

        $categories = AfterSort::sort($categories->getElements(), 'afterCategoryId');

        $tree = $this->tree(null, new CategoryCollection($categories));

        $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());

        $this->renderTree($tree, $output);

        $output->success(
            sprintf('Built tree with %s categories, within %s seconds and %s MB', count($categories), round(microtime(true) - $time, 6), round((memory_get_usage()-$memory) / 1024 / 1024, 2))
        );
    }

    private function renderTree($tree, $output, $level = 0): void
    {
        foreach ($tree as $category) {
            $output->writeln(str_repeat('  ', $level) . $category->get('name'));
            $this->renderTree($category->get('children'), $output, $level + 1);
        }
    }

    private function tree(?string $parentId, EntityCollection $categories): EntityCollection
    {
        $children = $categories->filter(function (CategoryEntity $category) use ($parentId) {
            return $category->get('parentId') === $parentId && $category->getActive();
        });

        foreach ($children as $child) {
            $child->setChildren(
                $this->tree($child->get('id'), $categories)
            );
        }

        return $children;
    }
}


(new Main($kernel))->run();

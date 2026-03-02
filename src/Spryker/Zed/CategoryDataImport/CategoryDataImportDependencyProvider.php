<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\CategoryDataImport;

use Spryker\Zed\CategoryDataImport\Dependency\Facade\CategoryDataImportToCategoryFacadeBridge;
use Spryker\Zed\CategoryDataImport\Dependency\Facade\CategoryDataImportToUrlFacadeBridge;
use Spryker\Zed\DataImport\DataImportDependencyProvider;
use Spryker\Zed\Kernel\Container;

/**
 * @method \Spryker\Zed\CategoryDataImport\CategoryDataImportConfig getConfig()
 */
class CategoryDataImportDependencyProvider extends DataImportDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_CATEGORY = 'FACADE_CATEGORY';

    /**
     * @var string
     */
    public const FACADE_URL = 'FACADE_URL';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addCategoryFacade($container);
        $container = $this->addUrlFacade($container);

        return $container;
    }

    protected function addCategoryFacade(Container $container): Container
    {
        $container->set(static::FACADE_CATEGORY, function (Container $container) {
            return new CategoryDataImportToCategoryFacadeBridge($container->getLocator()->category()->facade());
        });

        return $container;
    }

    protected function addUrlFacade(Container $container): Container
    {
        $container->set(static::FACADE_URL, function (Container $container) {
            return new CategoryDataImportToUrlFacadeBridge($container->getLocator()->url()->facade());
        });

        return $container;
    }
}

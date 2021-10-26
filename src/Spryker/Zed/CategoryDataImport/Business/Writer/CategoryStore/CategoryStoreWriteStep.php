<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\CategoryDataImport\Business\Writer\CategoryStore;

use Generated\Shared\Transfer\StoreRelationTransfer;
use Generated\Shared\Transfer\UpdateCategoryStoreRelationRequestTransfer;
use Orm\Zed\Category\Persistence\Map\SpyCategoryNodeTableMap;
use Orm\Zed\Category\Persistence\Map\SpyCategoryStoreTableMap;
use Orm\Zed\Category\Persistence\SpyCategoryNodeQuery;
use Orm\Zed\Category\Persistence\SpyCategoryStoreQuery;
use Spryker\Zed\CategoryDataImport\Business\Writer\CategoryStore\DataSet\CategoryStoreDataSetInterface;
use Spryker\Zed\CategoryDataImport\Dependency\Facade\CategoryDataImportToCategoryFacadeInterface;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\PublishAwareStep;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;

class CategoryStoreWriteStep extends PublishAwareStep implements DataImportStepInterface
{
    /**
     * @uses \Spryker\Shared\CategoryStorage\CategoryStorageConstants::CATEGORY_STORE_PUBLISH
     *
     * @var string
     */
    protected const EVENT_CATEGORY_STORE_PUBLISH = 'Category.category_store.publish';

    /**
     * @uses \Spryker\Zed\Category\Dependency\CategoryEvents::ENTITY_CATEGORY_PUBLISH
     *
     * @var string
     */
    protected const ENTITY_CATEGORY_PUBLISH = 'Entity.spy_category.publish';

    /**
     * @var \Spryker\Zed\CategoryDataImport\Dependency\Facade\CategoryDataImportToCategoryFacadeInterface
     */
    protected $categoryFacade;

    /**
     * @param \Spryker\Zed\CategoryDataImport\Dependency\Facade\CategoryDataImportToCategoryFacadeInterface $categoryFacade
     */
    public function __construct(CategoryDataImportToCategoryFacadeInterface $categoryFacade)
    {
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @return void
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $storeIdsToAdd = $dataSet[CategoryStoreDataSetInterface::INCLUDED_STORE_IDS];
        $storeIdsToDelete = $dataSet[CategoryStoreDataSetInterface::EXCLUDED_STORE_IDS];

        if ($storeIdsToAdd === []) {
            $storeIdsToAdd = $this->getParentCategoryStoreRelations($dataSet[CategoryStoreDataSetInterface::ID_CATEGORY]);
        }

        $existingStoreRelationTransfer = $this->getExistingCategoryStoreRelations(
            $dataSet[CategoryStoreDataSetInterface::ID_CATEGORY],
        );

        $newStoreRelationTransfer = $this->createStoreRelationTransferToAssign($storeIdsToAdd, $storeIdsToDelete, $existingStoreRelationTransfer);

        $updateCategoryStoreRelationRequestTransfer = (new UpdateCategoryStoreRelationRequestTransfer())
            ->setIdCategory($dataSet[CategoryStoreDataSetInterface::ID_CATEGORY])
            ->setNewStoreAssignment($newStoreRelationTransfer)
            ->setCurrentStoreAssignment($existingStoreRelationTransfer);

        $this->categoryFacade->updateCategoryStoreRelation($updateCategoryStoreRelationRequestTransfer);

        $this->addPublishEvents(static::EVENT_CATEGORY_STORE_PUBLISH, $dataSet[CategoryStoreDataSetInterface::ID_CATEGORY]);
        $this->addPublishEvents(static::ENTITY_CATEGORY_PUBLISH, $dataSet[CategoryStoreDataSetInterface::ID_CATEGORY]);
    }

    /**
     * @param int $idCategory
     *
     * @return \Generated\Shared\Transfer\StoreRelationTransfer
     */
    protected function getExistingCategoryStoreRelations(int $idCategory): StoreRelationTransfer
    {
        $storeIds = SpyCategoryStoreQuery::create()
            ->filterByFkCategory($idCategory)
            ->select(SpyCategoryStoreTableMap::COL_FK_STORE)
            ->find()
            ->getData();

        return (new StoreRelationTransfer())->setIdStores($storeIds);
    }

    /**
     * @param int $idCategory
     *
     * @return array<int>
     */
    protected function getParentCategoryStoreRelations(int $idCategory): array
    {
        $parentCategoryNodeId = SpyCategoryNodeQuery::create()
            ->filterByFkCategory($idCategory)
            ->filterByIsMain(true)
            ->select(SpyCategoryNodeTableMap::COL_FK_PARENT_CATEGORY_NODE)
            ->find()
            ->getFirst();

        if ($parentCategoryNodeId === null) {
            return [];
        }

        return SpyCategoryStoreQuery::create()
            ->joinWithSpyCategory()
            ->useSpyCategoryQuery()
                ->joinWithNode()
                ->useNodeQuery()
                    ->filterByIdCategoryNode($parentCategoryNodeId)
                ->endUse()
            ->endUse()
            ->select(SpyCategoryStoreTableMap::COL_FK_STORE)
            ->find()
            ->getData();
    }

    /**
     * @param array<int> $storeIdsToAdd
     * @param array<int> $storeIdsToDelete
     * @param \Generated\Shared\Transfer\StoreRelationTransfer $existingStoreRelationTransfer
     *
     * @return \Generated\Shared\Transfer\StoreRelationTransfer
     */
    protected function createStoreRelationTransferToAssign(
        array $storeIdsToAdd,
        array $storeIdsToDelete,
        StoreRelationTransfer $existingStoreRelationTransfer
    ): StoreRelationTransfer {
        $newStoreRelationTransfer = (new StoreRelationTransfer())->setIdStores(array_diff($storeIdsToAdd, $storeIdsToDelete));

        foreach ($existingStoreRelationTransfer->getIdStores() as $idStore) {
            if (in_array($idStore, $storeIdsToDelete) || in_array($idStore, $newStoreRelationTransfer->getIdStores())) {
                continue;
            }
            $newStoreRelationTransfer->addIdStores($idStore);
        }

        return $newStoreRelationTransfer;
    }
}

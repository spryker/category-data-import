<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Zed\CategoryDataImport;

use Codeception\Actor;
use Orm\Zed\Category\Persistence\SpyCategoryStoreQuery;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class CategoryDataImportCommunicationTester extends Actor
{
    use _generated\CategoryDataImportCommunicationTesterActions;

    /**
     * @param int $idCategory
     *
     * @return int
     */
    public function countCategoryStoreRelations(int $idCategory): int
    {
        return SpyCategoryStoreQuery::create()
            ->filterByFkCategory($idCategory)
            ->count();
    }
}

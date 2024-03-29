<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\CategoryDataImport\Business\Model\Reader;

use ArrayObject;
use Orm\Zed\Category\Persistence\SpyCategory;
use Orm\Zed\Category\Persistence\SpyCategoryNode;
use Orm\Zed\Category\Persistence\SpyCategoryQuery;
use Orm\Zed\Url\Persistence\SpyUrlQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\CategoryDataImport\Business\Exception\CategoryByKeyNotFoundException;

class CategoryReader implements CategoryReaderInterface
{
    /**
     * @var string
     */
    public const ID_CATEGORY_NODE = 'id_category_node';

    /**
     * @var string
     */
    public const ID_LOCALE = 'idLocale';

    /**
     * @var string
     */
    public const URL = 'url';

    /**
     * @var string
     */
    public const ID_CATEGORY = 'id_category';

    /**
     * @var \ArrayObject<string, array<string, mixed>>
     */
    protected $categoryKeys;

    /**
     * @var \ArrayObject<string, array<int, array<string, mixed>>>
     */
    protected $categoryUrls;

    public function __construct()
    {
        $this->categoryKeys = new ArrayObject();
        $this->categoryUrls = new ArrayObject();
    }

    /**
     * @param \Orm\Zed\Category\Persistence\SpyCategory $categoryEntity
     * @param \Orm\Zed\Category\Persistence\SpyCategoryNode $categoryNodeEntity
     *
     * @return void
     */
    public function addCategory(SpyCategory $categoryEntity, SpyCategoryNode $categoryNodeEntity)
    {
        $this->categoryKeys[$categoryEntity->getCategoryKey()] = [
            static::ID_CATEGORY => $categoryEntity->getIdCategory(),
            static::ID_CATEGORY_NODE => $categoryNodeEntity->getIdCategoryNode(),
        ];

        $urls = [];
        $categoryNodeEntityCollection = $categoryEntity->getNodes();
        foreach ($categoryNodeEntityCollection as $categoryNode) {
            foreach ($categoryNode->getSpyUrls() as $urlEntity) {
                $urls[] = [
                    static::ID_LOCALE => $urlEntity->getFkLocale(),
                    static::URL => $urlEntity->getUrl(),
                ];
            }
        }

        $this->categoryUrls[$categoryEntity->getCategoryKey()] = $urls;
    }

    /**
     * @param string $categoryKey
     *
     * @throws \Spryker\Zed\CategoryDataImport\Business\Exception\CategoryByKeyNotFoundException
     *
     * @return int
     */
    public function getIdCategoryNodeByCategoryKey($categoryKey)
    {
        if ($this->categoryKeys->count() === 0) {
            $this->loadCategoryKeys();
        }

        if (!$this->categoryKeys->offsetExists($categoryKey)) {
            throw new CategoryByKeyNotFoundException(sprintf(
                'Category by key "%s" not found. Maybe you have a typo in the category key.',
                $categoryKey,
            ));
        }

        /** @var array<string, int> $categories */
        $categories = $this->categoryKeys[$categoryKey];

        return $categories[static::ID_CATEGORY_NODE];
    }

    /**
     * @return void
     */
    protected function loadCategoryKeys()
    {
        $categoryEntityCollection = SpyCategoryQuery::create()
            ->joinWithNode()
            ->find();

        foreach ($categoryEntityCollection as $categoryEntity) {
            $this->categoryKeys[$categoryEntity->getCategoryKey()] = [
                static::ID_CATEGORY => $categoryEntity->getIdCategory(),
                static::ID_CATEGORY_NODE => $categoryEntity->getNodes()->getFirst()->getIdCategoryNode(),
            ];
        }
    }

    /**
     * @param string $categoryKey
     * @param int $idLocale
     *
     * @throws \Spryker\Zed\CategoryDataImport\Business\Exception\CategoryByKeyNotFoundException
     *
     * @return string
     */
    public function getParentUrl($categoryKey, $idLocale)
    {
        if ($this->categoryUrls->count() === 0) {
            $this->loadCategoryUrls();
        }

        if (!$this->categoryUrls->offsetExists($categoryKey)) {
            throw new CategoryByKeyNotFoundException(sprintf(
                'Category url key "%s" not found. Maybe you have a typo in the category key.',
                $categoryKey,
            ));
        }

        /** @var array<array<string, mixed>> $categoryUrls */
        $categoryUrls = $this->categoryUrls[$categoryKey];

        foreach ($categoryUrls as $categoryUrl) {
            if ($categoryUrl[static::ID_LOCALE] === $idLocale) {
                return $categoryUrl[static::URL];
            }
        }

        throw new CategoryByKeyNotFoundException(sprintf(
            'Category url key "%s" and idLocale "%s" not found.',
            $categoryKey,
            $idLocale,
        ));
    }

    /**
     * @return void
     */
    protected function loadCategoryUrls()
    {
        $urlEntityCollection = SpyUrlQuery::create()->filterByFkResourceCategorynode(null, Criteria::ISNOTNULL)->find();

        foreach ($urlEntityCollection as $urlEntity) {
            $categoryNodeEntity = $urlEntity->getSpyCategoryNode();
            if (!$categoryNodeEntity) {
                return;
            }
            $categoryEntity = $categoryNodeEntity->getCategory();

            if (!$this->categoryUrls->offsetExists($categoryEntity->getCategoryKey())) {
                $this->categoryUrls[$categoryEntity->getCategoryKey()] = [];
            }
            $this->categoryUrls[$categoryEntity->getCategoryKey()][] = [
                static::ID_LOCALE => $urlEntity->getFkLocale(),
                static::URL => $urlEntity->getUrl(),
            ];
        }
    }
}

<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\EntryConverterPool;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryExtensionFactory;

/**
 * Catalog product model
 *
 * @method Product setHasError(bool $value)
 * @method \Magento\Catalog\Model\ResourceModel\Product getResource()
 * @method null|bool getHasError()
 * @method Product setAssociatedProductIds(array $productIds)
 * @method array getAssociatedProductIds()
 * @method Product setNewVariationsAttributeSetId(int $value)
 * @method int getNewVariationsAttributeSetId()
 * @method int getPriceType()
 * @method \Magento\Catalog\Model\ResourceModel\Product\Collection getCollection()
 * @method string getUrlKey()
 * @method Product setUrlKey(string $urlKey)
 * @method Product setRequestPath(string $requestPath)
 * @method Product setWebsiteIds(array $ids)
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product extends \Magento\Catalog\Model\AbstractModel implements
    IdentityInterface,
    SaleableInterface,
    ProductInterface
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY = 'catalog_product';

    /**
     * Product cache tag
     */
    const CACHE_TAG = 'catalog_product';

    /**
     * Category product relation cache tag
     */
    const CACHE_PRODUCT_CATEGORY_TAG = 'catalog_category_product';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'catalog_product';

    /**
     * @var string
     */
    protected $_eventObject = 'product';

    /**
     * @var bool
     */
    protected $_canAffectOptions = false;

    /**
     * Product type singleton instance
     *
     * @var \Magento\Catalog\Model\Product\Type\AbstractType
     */
    protected $_typeInstance = null;

    /**
     * Product link instance
     *
     * @var Product\Link
     */
    protected $_linkInstance;

    /**
     * Product object customization (not stored in DB)
     *
     * @var array
     */
    protected $_customOptions = [];

    /**
     * Product Url Instance
     *
     * @var Product\Url
     */
    protected $_urlModel = null;

    /**
     * @var string
     */
    protected static $_url;

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * Product option
     *
     * @var Product\Option
     */
    protected $_optionInstance;

    /**
     * @var array
     */
    protected $_options = [];

    /**
     * @var bool
     */
    protected $optionsInitialized = false;

    /**
     * @var array
     */
    protected $_links = null;

    /**
     * Flag for available duplicate function
     *
     * @var boolean
     */
    protected $_isDuplicable = true;

    /**
     * Flag for get Price function
     *
     * @var boolean
     */
    protected $_calculatePrice = true;

    /**
     * Catalog product
     *
     * @var \Magento\Catalog\Helper\Product
     */
    protected $_catalogProduct = null;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Catalog product type
     *
     * @var Product\Type
     */
    protected $_catalogProductType;

    /**
     * Catalog product media config
     *
     * @var Product\Media\Config
     */
    protected $_catalogProductMediaConfig;

    /**
     * Catalog product status
     *
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $_catalogProductStatus;

    /**
     * Catalog product visibility
     *
     * @var Product\Visibility
     */
    protected $_catalogProductVisibility;

    /**
     * Stock item factory
     *
     * @var \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory
     */
    protected $_stockItemFactory;

    /**
     * Item option factory
     *
     * @var \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory
     */
    protected $_itemOptionFactory;

    /**
     * Filesystem facade
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /** @var \Magento\Framework\Indexer\IndexerRegistry */
    protected $indexerRegistry;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Flat\Processor
     */
    protected $_productFlatIndexerProcessor;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Price\Processor
     */
    protected $_productPriceIndexerProcessor;

    /**
     * @var Indexer\Product\Eav\Processor
     */
    protected $_productEavIndexerProcessor;

    /**
     * @var \Magento\Framework\Pricing\PriceInfo\Base
     */
    protected $_priceInfo;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * Instance of category collection.
     *
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    protected $categoryCollection;

    /**
     * @var Product\Image\CacheFactory
     */
    protected $imageCacheFactory;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    protected $metadataService;

    /*
     * @param \Magento\Catalog\Model\ProductLink\CollectionProvider
     */
    protected $entityCollectionProvider;

    /*
     * @param \Magento\Catalog\Model\Product\LinkTypeProvider
     */
    protected $linkProvider;

    /*
     * @param \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory
     */
    protected $productLinkFactory;

    /*
     * @param \Magento\Catalog\Api\Data\ProductLinkExtensionFactory
     */
    protected $productLinkExtensionFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var int
     */
    protected $_productIdCached;

    /**
     * List of attributes in ProductInterface
     * @var array
     */
    protected $interfaceAttributes = [
        ProductInterface::SKU,
        ProductInterface::NAME,
        ProductInterface::PRICE,
        ProductInterface::WEIGHT,
        ProductInterface::STATUS,
        ProductInterface::VISIBILITY,
        ProductInterface::ATTRIBUTE_SET_ID,
        ProductInterface::TYPE_ID,
        ProductInterface::CREATED_AT,
        ProductInterface::UPDATED_AT,
        ProductInterface::STORE_ID,
        'media_gallery',
        'tier_price',
    ];

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface
     */
    protected $joinProcessor;

    /**
     * Media converter pool
     *
     * @var Product\Attribute\Backend\Media\EntryConverterPool
     */
    protected $mediaGalleryEntryConverterPool;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataService
     * @param Product\Url $url
     * @param Product\Link $productLink
     * @param Product\Configuration\Item\OptionFactory $itemOptionFactory
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory $stockItemFactory
     * @param Product\Option $catalogProductOption
     * @param Product\Visibility $catalogProductVisibility
     * @param Product\Attribute\Source\Status $catalogProductStatus
     * @param Product\Media\Config $catalogProductMediaConfig
     * @param Product\Type $catalogProductType
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Catalog\Helper\Product $catalogProduct
     * @param \Magento\Catalog\Model\ResourceModel\Product $resource
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $resourceCollection
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param Indexer\Product\Flat\Processor $productFlatIndexerProcessor
     * @param Indexer\Product\Price\Processor $productPriceIndexerProcessor
     * @param Indexer\Product\Eav\Processor $productEavIndexerProcessor
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Product\Image\CacheFactory $imageCacheFactory
     * @param ProductLink\CollectionProvider $entityCollectionProvider
     * @param Product\LinkTypeProvider $linkTypeProvider
     * @param \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory
     * @param \Magento\Catalog\Api\Data\ProductLinkExtensionFactory $productLinkExtensionFactory
     * @param EntryConverterPool $mediaGalleryEntryConverterPool
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinProcessor
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $metadataService,
        Product\Url $url,
        Product\Link $productLink,
        \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory $itemOptionFactory,
        \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory $stockItemFactory,
        \Magento\Catalog\Model\Product\Option $catalogProductOption,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $catalogProductStatus,
        \Magento\Catalog\Model\Product\Media\Config $catalogProductMediaConfig,
        Product\Type $catalogProductType,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Catalog\Model\ResourceModel\Product $resource,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $resourceCollection,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor,
        CategoryRepositoryInterface $categoryRepository,
        Product\Image\CacheFactory $imageCacheFactory,
        \Magento\Catalog\Model\ProductLink\CollectionProvider $entityCollectionProvider,
        \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider,
        \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory,
        \Magento\Catalog\Api\Data\ProductLinkExtensionFactory $productLinkExtensionFactory,
        EntryConverterPool $mediaGalleryEntryConverterPool,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinProcessor,
        array $data = []
    ) {
        $this->metadataService = $metadataService;
        $this->_itemOptionFactory = $itemOptionFactory;
        $this->_stockItemFactory = $stockItemFactory;
        $this->_optionInstance = $catalogProductOption;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->_catalogProductStatus = $catalogProductStatus;
        $this->_catalogProductMediaConfig = $catalogProductMediaConfig;
        $this->_catalogProductType = $catalogProductType;
        $this->moduleManager = $moduleManager;
        $this->_catalogProduct = $catalogProduct;
        $this->_collectionFactory = $collectionFactory;
        $this->_urlModel = $url;
        $this->_linkInstance = $productLink;
        $this->_filesystem = $filesystem;
        $this->indexerRegistry = $indexerRegistry;
        $this->_productFlatIndexerProcessor = $productFlatIndexerProcessor;
        $this->_productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->_productEavIndexerProcessor = $productEavIndexerProcessor;
        $this->categoryRepository = $categoryRepository;
        $this->imageCacheFactory = $imageCacheFactory;
        $this->entityCollectionProvider = $entityCollectionProvider;
        $this->linkTypeProvider = $linkTypeProvider;
        $this->productLinkFactory = $productLinkFactory;
        $this->productLinkExtensionFactory = $productLinkExtensionFactory;
        $this->mediaGalleryEntryConverterPool = $mediaGalleryEntryConverterPool;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->joinProcessor = $joinProcessor;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $storeManager,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Initialize resources
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magento\Catalog\Model\ResourceModel\Product');
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomAttributesCodes()
    {
        if ($this->customAttributesCodes === null) {
            $this->customAttributesCodes = $this->getEavAttributesCodes($this->metadataService);
            $this->customAttributesCodes = array_diff($this->customAttributesCodes, $this->interfaceAttributes);
        }
        return $this->customAttributesCodes;
    }

    /**
     * Retrieve Store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        if ($this->hasData(self::STORE_ID)) {
            return $this->getData(self::STORE_ID);
        }
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * Get collection instance
     *
     * @return object
     */
    public function getResourceCollection()
    {
        $collection = parent::getResourceCollection();
        $collection->setStoreId($this->getStoreId());
        return $collection;
    }

    /**
     * Get product url model
     *
     * @return Product\Url
     */
    public function getUrlModel()
    {
        return $this->_urlModel;
    }

    /**
     * Validate Product Data
     *
     * @todo implement full validation process with errors returning which are ignoring now
     *
     * @return array
     */
    public function validate()
    {
        $this->_eventManager->dispatch($this->_eventPrefix . '_validate_before', $this->_getEventData());
        $result = $this->_getResource()->validate($this);
        $this->_eventManager->dispatch($this->_eventPrefix . '_validate_after', $this->_getEventData());
        return $result;
    }

    /**
     * Get product name
     *
     * @return string
     * @codeCoverageIgnoreStart
     */
    public function getName()
    {
        return $this->_getData(self::NAME);
    }
    //@codeCoverageIgnoreEnd

    /**
     * Get product price through type instance
     *
     * @return float
     */
    public function getPrice()
    {
        if ($this->_calculatePrice || !$this->getData(self::PRICE)) {
            return $this->getPriceModel()->getPrice($this);
        } else {
            return $this->getData(self::PRICE);
        }
    }

    /**
     * @codeCoverageIgnoreStart
     * Get visibility status
     * @see \Magento\Catalog\Model\Product\Visibility
     *
     * @return int
     */
    public function getVisibility()
    {
        return $this->_getData(self::VISIBILITY);
    }

    /**
     * Get product attribute set id
     *
     * @return int
     */
    public function getAttributeSetId()
    {
        return $this->_getData(self::ATTRIBUTE_SET_ID);
    }

    /**
     * Get product creation date
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->_getData(self::CREATED_AT);
    }

    /**
     * Get previous product update date
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->_getData(self::UPDATED_AT);
    }

    /**
     * Set Price calculation flag
     *
     * @param bool $calculate
     * @return void
     */
    public function setPriceCalculation($calculate = true)
    {
        $this->_calculatePrice = $calculate;
    }

    /**
     * Get product type identifier
     *
     * @return array|string
     */
    public function getTypeId()
    {
        return $this->_getData(self::TYPE_ID);
    }
    //@codeCoverageIgnoreEnd

    /**
     * Get product status
     *
     * @return int
     */
    public function getStatus()
    {
        $status = $this->_getData(self::STATUS);
        return $status !== null ? $status : \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
    }

    /**
     * Retrieve type instance of the product.
     * Type instance implements product type depended logic and is a singleton shared by all products of the same type.
     *
     * @return \Magento\Catalog\Model\Product\Type\AbstractType
     */
    public function getTypeInstance()
    {
        if ($this->_typeInstance === null) {
            $this->_typeInstance = $this->_catalogProductType->factory($this);
        }
        return $this->_typeInstance;
    }

    /**
     * Set type instance for the product
     *
     * @param \Magento\Catalog\Model\Product\Type\AbstractType|null $instance  Product type instance
     * @return \Magento\Catalog\Model\Product
     */
    public function setTypeInstance($instance)
    {
        $this->_typeInstance = $instance;
        return $this;
    }

    /**
     * Retrieve link instance
     *
     * @return  Product\Link
     */
    public function getLinkInstance()
    {
        return $this->_linkInstance;
    }

    /**
     * Retrieve product id by sku
     *
     * @param   string $sku
     * @return  integer
     */
    public function getIdBySku($sku)
    {
        return $this->_getResource()->getIdBySku($sku);
    }

    /**
     * Retrieve product category id
     *
     * @return int
     */
    public function getCategoryId()
    {
        $category = $this->_registry->registry('current_category');
        if ($category) {
            return $category->getId();
        }
        return false;
    }

    /**
     * Retrieve product category
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategory()
    {
        $category = $this->getData('category');
        if ($category === null && $this->getCategoryId()) {
            $category = $this->categoryRepository->get($this->getCategoryId());
            $this->setCategory($category);
        }
        return $category;
    }

    /**
     * Retrieve assigned category Ids
     *
     * @return array
     */
    public function getCategoryIds()
    {
        if (!$this->hasData('category_ids')) {
            $wasLocked = false;
            if ($this->isLockedAttribute('category_ids')) {
                $wasLocked = true;
                $this->unlockAttribute('category_ids');
            }
            $ids = $this->_getResource()->getCategoryIds($this);
            $this->setData('category_ids', $ids);
            if ($wasLocked) {
                $this->lockAttribute('category_ids');
            }
        }

        return (array) $this->_getData('category_ids');
    }

    /**
     * Retrieve product categories
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function getCategoryCollection()
    {
        if ($this->categoryCollection === null || $this->getId() != $this->_productIdCached) {
            $categoryCollection = $this->_getResource()->getCategoryCollection($this);
            $this->setCategoryCollection($categoryCollection);
            $this->_productIdCached = $this->getId();
        }
        return $this->categoryCollection;
    }

    /**
     * Set product categories.
     *
     * @param \Magento\Framework\Data\Collection $categoryCollection
     * @return $this
     */
    protected function setCategoryCollection(\Magento\Framework\Data\Collection $categoryCollection)
    {
        $this->categoryCollection = $categoryCollection;
        return $this;
    }

    /**
     * Retrieve product websites identifiers
     *
     * @return array
     */
    public function getWebsiteIds()
    {
        if (!$this->hasWebsiteIds()) {
            $ids = $this->_getResource()->getWebsiteIds($this);
            $this->setWebsiteIds($ids);
        }
        return $this->getData('website_ids');
    }

    /**
     * Get all sore ids where product is presented
     *
     * @return array
     */
    public function getStoreIds()
    {
        if (!$this->hasStoreIds()) {
            $storeIds = [];
            if ($websiteIds = $this->getWebsiteIds()) {
                foreach ($websiteIds as $websiteId) {
                    $websiteStores = $this->_storeManager->getWebsite($websiteId)->getStoreIds();
                    $storeIds = array_merge($storeIds, $websiteStores);
                }
            }
            $this->setStoreIds($storeIds);
        }
        return $this->getData('store_ids');
    }

    /**
     * Retrieve product attributes
     * if $groupId is null - retrieve all product attributes
     *
     * @param int  $groupId   Retrieve attributes of the specified group
     * @param bool $skipSuper Not used
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAttributes($groupId = null, $skipSuper = false)
    {
        $productAttributes = $this->getTypeInstance()->getEditableAttributes($this);
        if ($groupId) {
            $attributes = [];
            foreach ($productAttributes as $attribute) {
                if ($attribute->isInGroup($this->getAttributeSetId(), $groupId)) {
                    $attributes[] = $attribute;
                }
            }
        } else {
            $attributes = $productAttributes;
        }

        return $attributes;
    }

    /**
     * Check product options and type options and save them, too
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function beforeSave()
    {
        $this->cleanCache();
        $this->setTypeHasOptions(false);
        $this->setTypeHasRequiredOptions(false);

        $this->getTypeInstance()->beforeSave($this);

        $hasOptions = false;
        $hasRequiredOptions = false;

        /**
         * $this->_canAffectOptions - set by type instance only
         * $this->getCanSaveCustomOptions() - set either in controller when "Custom Options" ajax tab is loaded,
         * or in type instance as well
         */
        $this->canAffectOptions($this->_canAffectOptions && $this->getCanSaveCustomOptions());
        if ($this->getCanSaveCustomOptions()) {
            $options = $this->getProductOptions();
            if (is_array($options)) {
                $this->setIsCustomOptionChanged(true);
                foreach ($this->getProductOptions() as $option) {
                    $this->getOptionInstance()->addOption($option);
                    if (!isset($option['is_delete']) || $option['is_delete'] != '1') {
                        $hasOptions = true;
                    }
                }
                foreach ($this->getOptionInstance()->getOptions() as $option) {
                    if ($option['is_require'] == '1') {
                        $hasRequiredOptions = true;
                        break;
                    }
                }
            }
        }

        /**
         * Set true, if any
         * Set false, ONLY if options have been affected by Options tab and Type instance tab
         */
        if ($hasOptions || (bool)$this->getTypeHasOptions()) {
            $this->setHasOptions(true);
            if ($hasRequiredOptions || (bool)$this->getTypeHasRequiredOptions()) {
                $this->setRequiredOptions(true);
            } elseif ($this->canAffectOptions()) {
                $this->setRequiredOptions(false);
            }
        } elseif ($this->canAffectOptions()) {
            $this->setHasOptions(false);
            $this->setRequiredOptions(false);
        }

        if (!$this->getOrigData('website_ids')) {
            $websiteIds = $this->_getResource()->getWebsiteIds($this);
            $this->setOrigData('website_ids', $websiteIds);
        }
        parent::beforeSave();
    }

    /**
     * Check/set if options can be affected when saving product
     * If value specified, it will be set.
     *
     * @param   bool $value
     * @return  bool
     */
    public function canAffectOptions($value = null)
    {
        if (null !== $value) {
            $this->_canAffectOptions = (bool) $value;
        }
        return $this->_canAffectOptions;
    }

    /**
     * Saving product type related data and init index
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function afterSave()
    {
        $this->getLinkInstance()->saveProductRelations($this);
        $this->getTypeInstance()->save($this);

        if ($this->getStockData()) {
            $this->setForceReindexEavRequired(true);
        }

        $this->_getResource()->addCommitCallback([$this, 'priceReindexCallback']);
        $this->_getResource()->addCommitCallback([$this, 'eavReindexCallback']);

        /**
         * Product Options
         */
        if (!$this->getIsDuplicate()) {
            $this->getOptionInstance()->setProduct($this)->saveOptions();
        }

        $result = parent::afterSave();

        $this->_getResource()->addCommitCallback([$this, 'reindex']);
        $this->reloadPriceInfo();

        // Resize images for catalog product and save results to image cache
        /** @var Product\Image\Cache $imageCache */
        $imageCache = $this->imageCacheFactory->create();
        $imageCache->generate($this);

        return $result;
    }

    /**
     * Set quantity for product
     *
     * @param float $qty
     * @return $this
     */
    public function setQty($qty)
    {
        if ($this->getData('qty') != $qty) {
            $this->setData('qty', $qty);
            $this->reloadPriceInfo();
        }
        return $this;
    }

    /**
     * Get quantity for product
     *
     * @return float
     */
    public function getQty()
    {
        return $this->getData('qty');
    }

    /**
     * Callback for entity reindex
     *
     * @return void
     */
    public function priceReindexCallback()
    {
        if ($this->isObjectNew() || $this->_catalogProduct->isDataForPriceIndexerWasChanged($this)) {
            $this->_productPriceIndexerProcessor->reindexRow($this->getEntityId());
        }
    }

    /**
     * Reindex callback for EAV indexer
     *
     * @return void
     */
    public function eavReindexCallback()
    {
        if ($this->isObjectNew() || $this->hasDataChanges()) {
            $this->_productEavIndexerProcessor->reindexRow($this->getEntityId());
        }
    }

    /**
     * Init indexing process after product save
     *
     * @return void
     */
    public function reindex()
    {
        if ($this->_catalogProduct->isDataForProductCategoryIndexerWasChanged($this) || $this->isDeleted()) {
            $productCategoryIndexer = $this->indexerRegistry->get(Indexer\Product\Category::INDEXER_ID);
            if (!$productCategoryIndexer->isScheduled()) {
                $productCategoryIndexer->reindexRow($this->getId());
            }
        }
        $this->_productFlatIndexerProcessor->reindexRow($this->getEntityId());
    }

    /**
     * Clear cache related with product and protect delete from not admin
     * Register indexing event before delete product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function beforeDelete()
    {
        $this->cleanCache();
        return parent::beforeDelete();
    }

    /**
     * Init indexing process after product delete commit
     *
     * @return void
     */
    public function afterDeleteCommit()
    {
        $this->reindex();
        $this->_productPriceIndexerProcessor->reindexRow($this->getId());
        parent::afterDeleteCommit();
    }

    /**
     * Load product options if they exists
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        if (!$this->hasData(self::STATUS)) {
            $this->setData(self::STATUS, \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        }
        parent::_afterLoad();
        /**
         * Load product options
         */
        if ($this->getHasOptions()) {
            foreach ($this->getProductOptionsCollection() as $option) {
                $option->setProduct($this);
                $this->addOption($option);
            }
        }
        return $this;
    }

    /**
     * Clear cache related with product id
     *
     * @return $this
     */
    public function cleanCache()
    {
        $this->_cacheManager->clean('catalog_product_' . $this->getId());
        return $this;
    }

    /**
     * Get product price model
     *
     * @return \Magento\Catalog\Model\Product\Type\Price
     */
    public function getPriceModel()
    {
        return $this->_catalogProductType->priceFactory($this->getTypeId());
    }

    /**
     * Get product Price Info object
     *
     * @return \Magento\Framework\Pricing\PriceInfo\Base
     */
    public function getPriceInfo()
    {
        if (!$this->_priceInfo) {
            $this->_priceInfo = $this->_catalogProductType->getPriceInfo($this);
        }
        return $this->_priceInfo;
    }

    /**
     * Gets list of product tier prices
     *
     * @return \Magento\Catalog\Api\Data\ProductTierPriceInterface[]|null
     */
    public function getTierPrices()
    {
        return $this->getPriceModel()->getTierPrices($this);
    }

    /**
     * Sets list of product tier prices
     *
     * @param \Magento\Catalog\Api\Data\ProductTierPriceInterface[] $tierPrices
     * @return $this
     */
    public function setTierPrices(array $tierPrices = null)
    {
        $this->getPriceModel()->setTierPrices($this, $tierPrices);
        return $this;
    }

    /**
     * Get product tier price for the customer, based on qty of this product
     *
     * @param   float $qty
     * @return  float|array
     */
    public function getTierPrice($qty = null)
    {
        return $this->getPriceModel()->getTierPrice($qty, $this);
    }

    /**
     * Get formatted by currency product price
     *
     * @return  array || double
     */
    public function getFormatedPrice()
    {
        return $this->getPriceModel()->getFormatedPrice($this);
    }

    /**
     * Sets final price of product
     *
     * This func is equal to magic 'setFinalPrice()', but added as a separate func, because in cart with bundle
     * products it's called very often in Item->getProduct(). So removing chain of magic with more cpu consuming
     * algorithms gives nice optimization boost.
     *
     * @param float $price Price amount
     * @return \Magento\Catalog\Model\Product
     */
    public function setFinalPrice($price)
    {
        $this->_data['final_price'] = $price;
        return $this;
    }

    /**
     * Get product final price
     *
     * @param float $qty
     * @return float
     */
    public function getFinalPrice($qty = null)
    {
        if ($this->_getData('final_price') === null) {
            $this->setFinalPrice($this->getPriceModel()->getFinalPrice($qty, $this));
        }
        return $this->_getData('final_price');
    }

    /**
     * Returns calculated final price
     *
     * @return float
     */
    public function getCalculatedFinalPrice()
    {
        return $this->_getData('calculated_final_price');
    }

    /**
     * Returns minimal price
     *
     * @return float
     */
    public function getMinimalPrice()
    {
        return max($this->_getData('minimal_price'), 0);
    }

    /**
     * Returns special price
     *
     * @return float
     */
    public function getSpecialPrice()
    {
        return $this->_getData('special_price');
    }

    /**
     * Returns starting date of the special price
     *
     * @return mixed
     */
    public function getSpecialFromDate()
    {
        return $this->_getData('special_from_date');
    }

    /**
     * Returns end date of the special price
     *
     * @return mixed
     */
    public function getSpecialToDate()
    {
        return $this->_getData('special_to_date');
    }

    /*******************************************************************************
     ** Linked products API
     */
    /**
     * Retrieve array of related products
     *
     * @return array
     */
    public function getRelatedProducts()
    {
        if (!$this->hasRelatedProducts()) {
            $products = [];
            $collection = $this->getRelatedProductCollection();
            foreach ($collection as $product) {
                $products[] = $product;
            }
            $this->setRelatedProducts($products);
        }
        return $this->getData('related_products');
    }

    /**
     * Retrieve related products identifiers
     *
     * @return array
     */
    public function getRelatedProductIds()
    {
        if (!$this->hasRelatedProductIds()) {
            $ids = [];
            foreach ($this->getRelatedProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setRelatedProductIds($ids);
        }
        return $this->getData('related_product_ids');
    }

    /**
     * Retrieve collection related product
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection
     */
    public function getRelatedProductCollection()
    {
        $collection = $this->getLinkInstance()->useRelatedLinks()->getProductCollection()->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }

    /**
     * Retrieve collection related link
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Collection
     */
    public function getRelatedLinkCollection()
    {
        $collection = $this->getLinkInstance()->useRelatedLinks()->getLinkCollection();
        $collection->setProduct($this);
        $collection->addLinkTypeIdFilter();
        $collection->addProductIdFilter();
        $collection->joinAttributes();
        return $collection;
    }

    /**
     * Retrieve array of up sell products
     *
     * @return array
     */
    public function getUpSellProducts()
    {
        if (!$this->hasUpSellProducts()) {
            $products = [];
            foreach ($this->getUpSellProductCollection() as $product) {
                $products[] = $product;
            }
            $this->setUpSellProducts($products);
        }
        return $this->getData('up_sell_products');
    }

    /**
     * Retrieve up sell products identifiers
     *
     * @return array
     */
    public function getUpSellProductIds()
    {
        if (!$this->hasUpSellProductIds()) {
            $ids = [];
            foreach ($this->getUpSellProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setUpSellProductIds($ids);
        }
        return $this->getData('up_sell_product_ids');
    }

    /**
     * Retrieve collection up sell product
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection
     */
    public function getUpSellProductCollection()
    {
        $collection = $this->getLinkInstance()->useUpSellLinks()->getProductCollection()->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }

    /**
     * Retrieve collection up sell link
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Collection
     */
    public function getUpSellLinkCollection()
    {
        $collection = $this->getLinkInstance()->useUpSellLinks()->getLinkCollection();
        $collection->setProduct($this);
        $collection->addLinkTypeIdFilter();
        $collection->addProductIdFilter();
        $collection->joinAttributes();
        return $collection;
    }

    /**
     * Retrieve array of cross sell products
     *
     * @return array
     */
    public function getCrossSellProducts()
    {
        if (!$this->hasCrossSellProducts()) {
            $products = [];
            foreach ($this->getCrossSellProductCollection() as $product) {
                $products[] = $product;
            }
            $this->setCrossSellProducts($products);
        }
        return $this->getData('cross_sell_products');
    }

    /**
     * Retrieve cross sell products identifiers
     *
     * @return array
     */
    public function getCrossSellProductIds()
    {
        if (!$this->hasCrossSellProductIds()) {
            $ids = [];
            foreach ($this->getCrossSellProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setCrossSellProductIds($ids);
        }
        return $this->getData('cross_sell_product_ids');
    }

    /**
     * Retrieve collection cross sell product
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection
     */
    public function getCrossSellProductCollection()
    {
        $collection = $this->getLinkInstance()->useCrossSellLinks()->getProductCollection()->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }

    /**
     * Retrieve collection cross sell link
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Collection
     */
    public function getCrossSellLinkCollection()
    {
        $collection = $this->getLinkInstance()->useCrossSellLinks()->getLinkCollection();
        $collection->setProduct($this);
        $collection->addLinkTypeIdFilter();
        $collection->addProductIdFilter();
        $collection->joinAttributes();
        return $collection;
    }

    /**
     * Get product links info
     *
     * @return \Magento\Catalog\Api\Data\ProductLinkInterface[]|null
     */
    public function getProductLinks()
    {
        if (empty($this->_links)) {
            $output = [];
            $linkTypes = $this->linkTypeProvider->getLinkTypes();
            foreach (array_keys($linkTypes) as $linkTypeName) {
                $collection = $this->entityCollectionProvider->getCollection($this, $linkTypeName);
                foreach ($collection as $item) {
                    /** @var \Magento\Catalog\Api\Data\ProductLinkInterface $productLink */
                    $productLink = $this->productLinkFactory->create();
                    $productLink->setSku($this->getSku())
                        ->setLinkType($linkTypeName)
                        ->setLinkedProductSku($item['sku'])
                        ->setLinkedProductType($item['type'])
                        ->setPosition($item['position']);
                    if (isset($item['custom_attributes'])) {
                        $productLinkExtension = $productLink->getExtensionAttributes();
                        if ($productLinkExtension === null) {
                            $productLinkExtension = $this->productLinkExtensionFactory->create();
                        }
                        foreach ($item['custom_attributes'] as $option) {
                            $name = $option['attribute_code'];
                            $value = $option['value'];
                            $setterName = 'set' . ucfirst($name);
                            // Check if setter exists
                            if (method_exists($productLinkExtension, $setterName)) {
                                call_user_func([$productLinkExtension, $setterName], $value);
                            }
                        }
                        $productLink->setExtensionAttributes($productLinkExtension);
                    }
                    $output[] = $productLink;
                }
            }
            $this->_links = $output;
        }

        return $this->_links;
    }

    /**
     * Set product links info
     *
     * @param \Magento\Catalog\Api\Data\ProductLinkInterface[] $links
     * @return this
     */
    public function setProductLinks(array $links = null)
    {
        if ($links === null) {
            $this->setData('ignore_links_flag', true);
        } else {
            $this->setData('ignore_links_flag', false);
        }
        $this->_links = $links;
        return $this;
    }

    /*******************************************************************************
     ** Media API
     */
    /**
     * Retrieve attributes for media gallery
     *
     * @return array
     */
    public function getMediaAttributes()
    {
        if (!$this->hasMediaAttributes()) {
            $mediaAttributes = [];
            foreach ($this->getAttributes() as $attribute) {
                if ($attribute->getFrontend()->getInputType() == 'media_image') {
                    $mediaAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
            $this->setMediaAttributes($mediaAttributes);
        }
        return $this->getData('media_attributes');
    }

    /**
     * Retrieve assoc array that contains media attribute values of the product
     *
     * @return array
     */
    public function getMediaAttributeValues()
    {
        $mediaAttributeCodes = array_keys($this->getMediaAttributes());
        $mediaAttributeValues = [];
        foreach ($mediaAttributeCodes as $attributeCode) {
            $mediaAttributeValues[$attributeCode] = $this->getData($attributeCode);
        }
        return $mediaAttributeValues;
    }

    /**
     * Retrieve media gallery images
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function getMediaGalleryImages()
    {
        $directory = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
        if (!$this->hasData('media_gallery_images') && is_array($this->getMediaGallery('images'))) {
            $images = $this->_collectionFactory->create();
            foreach ($this->getMediaGallery('images') as $image) {
                if (isset($image['disabled']) && $image['disabled']) {
                    continue;
                }
                $image['url'] = $this->getMediaConfig()->getMediaUrl($image['file']);
                $image['id'] = !empty($image['value_id']) ? $image['value_id'] : null;
                $image['path'] = $directory->getAbsolutePath($this->getMediaConfig()->getMediaPath($image['file']));
                $images->addItem(new \Magento\Framework\DataObject($image));
            }
            $this->setData('media_gallery_images', $images);
        }

        return $this->getData('media_gallery_images');
    }

    /**
     * Retrieve backend model of product media gallery attribute, return null if the product
     * does not support images
     *
     * @return \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend|null
     */
    public function getGalleryAttributeBackend()
    {
        $attributes = $this->getAttributes();
        if (!isset($attributes['media_gallery'])
            || !($attributes['media_gallery'] instanceof \Magento\Eav\Model\Entity\Attribute\AbstractAttribute)
        ) {
            return null;
        }
        /** @var $galleryAttribute \Magento\Eav\Model\Entity\Attribute\AbstractAttribute */
        $galleryAttribute = $attributes['media_gallery'];
        return $galleryAttribute->getBackend();
    }

    /**
     * Add image to media gallery
     *
     * @param string        $file              file path of image in file system
     * @param string|array  $mediaAttribute    code of attribute with type 'media_image',
     *                                          leave blank if image should be only in gallery
     * @param boolean       $move              if true, it will move source file
     * @param boolean       $exclude           mark image as disabled in product page view
     * @return \Magento\Catalog\Model\Product
     */
    public function addImageToMediaGallery($file, $mediaAttribute = null, $move = false, $exclude = true)
    {
        $attributes = $this->getTypeInstance()->getSetAttributes($this);
        if (!isset($attributes['media_gallery'])) {
            return $this;
        }
        $mediaGalleryAttribute = $attributes['media_gallery'];
        /* @var $mediaGalleryAttribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
        $mediaGalleryAttribute->getBackend()->addImage($this, $file, $mediaAttribute, $move, $exclude);
        return $this;
    }

    /**
     * Retrieve product media config
     *
     * @return Product\Media\Config
     */
    public function getMediaConfig()
    {
        return $this->_catalogProductMediaConfig;
    }

    /**
     * Returns visible status IDs in catalog
     *
     * @return array
     */
    public function getVisibleInCatalogStatuses()
    {
        return $this->_catalogProductStatus->getVisibleStatusIds();
    }

    /**
     * Retrieve visible statuses
     *
     * @return array
     */
    public function getVisibleStatuses()
    {
        return $this->_catalogProductStatus->getVisibleStatusIds();
    }

    /**
     * Check Product visible in catalog
     *
     * @return bool
     */
    public function isVisibleInCatalog()
    {
        return in_array($this->getStatus(), $this->getVisibleInCatalogStatuses());
    }

    /**
     * Retrieve visible in site visibilities
     *
     * @return array
     */
    public function getVisibleInSiteVisibilities()
    {
        return $this->_catalogProductVisibility->getVisibleInSiteIds();
    }

    /**
     * Check Product visible in site
     *
     * @return bool
     */
    public function isVisibleInSiteVisibility()
    {
        return in_array($this->getVisibility(), $this->getVisibleInSiteVisibilities());
    }

    /**
     * Checks product can be duplicated
     *
     * @return boolean
     */
    public function isDuplicable()
    {
        return $this->_isDuplicable;
    }

    /**
     * Set is duplicable flag
     *
     * @param boolean $value
     * @return \Magento\Catalog\Model\Product
     */
    public function setIsDuplicable($value)
    {
        $this->_isDuplicable = (bool)$value;
        return $this;
    }

    /**
     * Check is product available for sale
     *
     * @return bool
     */
    public function isSalable()
    {
        $this->_eventManager->dispatch('catalog_product_is_salable_before', ['product' => $this]);

        $salable = $this->isAvailable();

        $object = new \Magento\Framework\DataObject(['product' => $this, 'is_salable' => $salable]);
        $this->_eventManager->dispatch(
            'catalog_product_is_salable_after',
            ['product' => $this, 'salable' => $object]
        );
        return $object->getIsSalable();
    }

    /**
     * Check whether the product type or stock allows to purchase the product
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->getTypeInstance()->isSalable($this) || $this->_catalogProduct->getSkipSaleableCheck();
    }

    /**
     * Is product salable detecting by product type
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsSalable()
    {
        $productType = $this->getTypeInstance();
        if (method_exists($productType, 'getIsSalable')) {
            return $productType->getIsSalable($this);
        }
        if ($this->hasData('is_saleable')) {
            return $this->getData('is_saleable');
        }

        return $this->isSalable();
    }

    /**
     * Check is a virtual product
     * Data helper wrapper
     *
     * @return bool
     */
    public function isVirtual()
    {
        return $this->getIsVirtual();
    }

    /**
     * Alias for isSalable()
     *
     * @return bool
     */
    public function isSaleable()
    {
        return $this->isSalable();
    }

    /**
     * Whether product available in stock
     *
     * @return bool
     */
    public function isInStock()
    {
        return $this->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
    }

    /**
     * Get attribute text by its code
     *
     * @param string $attributeCode Code of the attribute
     * @return string
     */
    public function getAttributeText($attributeCode)
    {
        return $this->getResource()->getAttribute($attributeCode)->getSource()->getOptionText(
            $this->getData($attributeCode)
        );
    }

    /**
     * Returns array with dates for custom design
     *
     * @return array
     */
    public function getCustomDesignDate()
    {
        $result = [];
        $result['from'] = $this->getData('custom_design_from');
        $result['to'] = $this->getData('custom_design_to');

        return $result;
    }

    /**
     * Retrieve Product URL
     *
     * @param  bool $useSid
     * @return string
     */
    public function getProductUrl($useSid = null)
    {
        return $this->getUrlModel()->getProductUrl($this, $useSid);
    }

    /**
     * Retrieve URL in current store
     *
     * @param array $params the route params
     * @return string
     */
    public function getUrlInStore($params = [])
    {
        return $this->getUrlModel()->getUrlInStore($this, $params);
    }

    /**
     * Formats URL key
     *
     * @param string $str URL
     * @return string
     */
    public function formatUrlKey($str)
    {
        return $this->getUrlModel()->formatUrlKey($str);
    }

    /**
     * Save current attribute with code $code and assign new value
     *
     * @param string $code  Attribute code
     * @param mixed  $value New attribute value
     * @param int    $store Store ID
     * @return void
     */
    public function addAttributeUpdate($code, $value, $store)
    {
        $oldValue = $this->getData($code);
        $oldStore = $this->getStoreId();

        $this->setData($code, $value);
        $this->setStoreId($store);
        $this->getResource()->saveAttribute($this, $code);

        $this->setData($code, $oldValue);
        $this->setStoreId($oldStore);
    }

    /**
     * Renders the object to array
     *
     * @param array $arrAttributes Attribute array
     * @return array
     */
    public function toArray(array $arrAttributes = [])
    {
        $data = parent::toArray($arrAttributes);
        $stock = $this->getStockItem();
        if ($stock) {
            $data['stock_item'] = $stock->toArray();
        }
        unset($data['stock_item']['product']);
        return $data;
    }

    /**
     * Same as setData(), but also initiates the stock item (if it is there)
     *
     * @param array $data Array to form the object from
     * @return \Magento\Catalog\Model\Product
     */
    public function fromArray(array $data)
    {
        if (isset($data['stock_item'])) {
            if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
                $stockItem = $this->_stockItemFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $stockItem,
                    $data['stock_item'],
                    '\Magento\CatalogInventory\Api\Data\StockItemInterface'
                );
                $stockItem->setProduct($this);
                $this->setStockItem($stockItem);
            }
            unset($data['stock_item']);
        }
        $this->setData($data);
        return $this;
    }

    /**
     * Returns request path
     *
     * @return string
     */
    public function getRequestPath()
    {
        return $this->_getData('request_path');
    }

    /**
     * Custom function for other modules
     * @return string
     */
    public function getGiftMessageAvailable()
    {
        return $this->_getData('gift_message_available');
    }

    /**
     * Check is product composite
     *
     * @return bool
     */
    public function isComposite()
    {
        return $this->getTypeInstance()->isComposite($this);
    }

    /**
     * Check if product can be configured
     *
     * @return bool
     */
    public function canConfigure()
    {
        $options = $this->getOptions();
        return !empty($options) || $this->getTypeInstance()->canConfigure($this);
    }

    /**
     * Retrieve sku through type instance
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getTypeInstance()->getSku($this);
    }

    /**
     * Retrieve weight through type instance
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->getTypeInstance()->getWeight($this);
    }

    /**
     * Retrieve option instance
     *
     * @return Product\Option
     */
    public function getOptionInstance()
    {
        return $this->_optionInstance;
    }

    /**
     * Retrieve options collection of product
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Option\Collection
     */
    public function getProductOptionsCollection()
    {
        $collection = $this->getOptionInstance()->getProductOptionCollection($this);

        return $collection;
    }

    /**
     * Add option to array of product options
     *
     * @param Product\Option $option
     * @return \Magento\Catalog\Model\Product
     */
    public function addOption(Product\Option $option)
    {
        $this->_options[$option->getId()] = $option;
        return $this;
    }

    /**
     * Get option from options array of product by given option id
     *
     * @param int $optionId
     * @return Product\Option|null
     */
    public function getOptionById($optionId)
    {
        if (isset($this->_options[$optionId])) {
            return $this->_options[$optionId];
        }

        return null;
    }

    /**
     * Get all options of product
     *
     * @return \Magento\Catalog\Api\Data\ProductCustomOptionInterface[]|null
     */
    public function getOptions()
    {
        if (empty($this->_options) && $this->getHasOptions() && !$this->optionsInitialized) {
            $collection = $this->getProductOptionsCollection();
            $this->joinProcessor->process($collection);
            foreach ($collection as $option) {
                $option->setProduct($this);
                $this->addOption($option);
            }
            $this->optionsInitialized = true;
        }
        return $this->_options;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface[] $options
     * @return $this
     */
    public function setOptions(array $options = null)
    {
        $this->_options = $options;
        if (is_array($options) && empty($options)) {
            $this->setData('is_delete_options', true);
        }
        $this->optionsInitialized = true;
        return $this;
    }

    /**
     * Retrieve is a virtual product
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsVirtual()
    {
        return $this->getTypeInstance()->isVirtual($this);
    }

    /**
     * Add custom option information to product
     *
     * @param   string $code    Option code
     * @param   mixed  $value   Value of the option
     * @param   int|Product    $product Product ID
     * @return  $this
     */
    public function addCustomOption($code, $value, $product = null)
    {
        $product = $product ? $product : $this;
        $option = $this->_itemOptionFactory->create()->addData(
            ['product_id' => $product->getId(), 'product' => $product, 'code' => $code, 'value' => $value]
        );
        $this->_customOptions[$code] = $option;
        return $this;
    }

    /**
     * Sets custom options for the product
     *
     * @param array $options Array of options
     * @return void
     */
    public function setCustomOptions(array $options)
    {
        $this->_customOptions = $options;
    }

    /**
     * Get all custom options of the product
     *
     * @return array
     */
    public function getCustomOptions()
    {
        return $this->_customOptions;
    }

    /**
     * Get product custom option info
     *
     * @param   string $code
     * @return  array
     */
    public function getCustomOption($code)
    {
        if (isset($this->_customOptions[$code])) {
            return $this->_customOptions[$code];
        }
        return null;
    }

    /**
     * Checks if there custom option for this product
     *
     * @return bool
     */
    public function hasCustomOptions()
    {
        if (count($this->_customOptions)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check availability display product in category
     *
     * @param   int $categoryId
     * @return  bool
     */
    public function canBeShowInCategory($categoryId)
    {
        return $this->_getResource()->canBeShowInCategory($this, $categoryId);
    }

    /**
     * Retrieve category ids where product is available
     *
     * @return array
     */
    public function getAvailableInCategories()
    {
        return $this->_getResource()->getAvailableInCategories($this);
    }

    /**
     * Retrieve default attribute set id
     *
     * @return int
     */
    public function getDefaultAttributeSetId()
    {
        return $this->getResource()->getEntityType()->getDefaultAttributeSetId();
    }

    /**
     * Reset all model data
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function reset()
    {
        $this->unlockAttributes();
        $this->_clearData();
        return $this;
    }

    /**
     * Get cache tags associated with object id
     *
     * @return string[]
     */
    public function getCacheIdTags()
    {
        $tags = parent::getCacheIdTags();
        $affectedCategoryIds = $this->getAffectedCategoryIds();
        if (!$affectedCategoryIds) {
            $affectedCategoryIds = $this->getCategoryIds();
        }
        foreach ($affectedCategoryIds as $categoryId) {
            $tags[] = \Magento\Catalog\Model\Category::CACHE_TAG . '_' . $categoryId;
        }
        return $tags;
    }

    /**
     * Check for empty SKU on each product
     *
     * @param  array $productIds
     * @return boolean|null
     */
    public function isProductsHasSku(array $productIds)
    {
        $products = $this->_getResource()->getProductsSku($productIds);
        if (count($products)) {
            foreach ($products as $product) {
                if (!strlen($product['sku'])) {
                    return false;
                }
            }
            return true;
        }
        return null;
    }

    /**
     * Parse buyRequest into options values used by product
     *
     * @param  \Magento\Framework\DataObject $buyRequest
     * @return \Magento\Framework\DataObject
     */
    public function processBuyRequest(\Magento\Framework\DataObject $buyRequest)
    {
        $options = new \Magento\Framework\DataObject();

        /* add product custom options data */
        $customOptions = $buyRequest->getOptions();
        if (is_array($customOptions)) {
            array_filter(
                $customOptions,
                function ($value) {
                    return $value !== '';
                }
            );
            $options->setOptions($customOptions);
        }

        /* add product type selected options data */
        $type = $this->getTypeInstance();
        $typeSpecificOptions = $type->processBuyRequest($this, $buyRequest);
        $options->addData($typeSpecificOptions);

        /* check correctness of product's options */
        $options->setErrors($type->checkProductConfiguration($this, $buyRequest));

        return $options;
    }

    /**
     * Get preconfigured values from product
     *
     * @return \Magento\Framework\DataObject
     */
    public function getPreconfiguredValues()
    {
        $preConfiguredValues = $this->getData('preconfigured_values');
        if (!$preConfiguredValues) {
            $preConfiguredValues = new \Magento\Framework\DataObject();
        }

        return $preConfiguredValues;
    }

    /**
     * Prepare product custom options.
     * To be sure that all product custom options does not has ID and has product instance
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function prepareCustomOptions()
    {
        foreach ($this->getCustomOptions() as $option) {
            if (!is_object($option->getProduct()) || $option->getId()) {
                $this->addCustomOption($option->getCode(), $option->getValue());
            }
        }

        return $this;
    }

    /**
     * Clearing references on product
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function _clearReferences()
    {
        $this->_clearOptionReferences();
        return $this;
    }

    /**
     * Clearing product's data
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function _clearData()
    {
        foreach ($this->_data as $data) {
            if (is_object($data) && method_exists($data, 'reset') && is_callable([$data, 'reset'])) {
                $data->reset();
            }
        }

        $this->setData([]);
        $this->setOrigData();
        $this->_customOptions = [];
        $this->_options = [];
        $this->_canAffectOptions = false;
        $this->_errors = [];

        return $this;
    }

    /**
     * Clearing references to product from product's options
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function _clearOptionReferences()
    {
        /**
         * unload product options
         */
        if (!empty($this->_options)) {
            foreach ($this->_options as $option) {
                $option->setProduct();
                $option->clearInstance();
            }
        }

        return $this;
    }

    /**
     * Retrieve product entities info as array
     *
     * @param string|array $columns One or several columns
     * @return array
     */
    public function getProductEntitiesInfo($columns = null)
    {
        return $this->_getResource()->getProductEntitiesInfo($columns);
    }

    /**
     * Checks whether product has disabled status
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
    }

    /**
     * Sets product image from it's child if possible
     *
     * @return string
     */
    public function getImage()
    {
        $this->getTypeInstance()->setImageFromChildProduct($this);
        return parent::getImage();
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [self::CACHE_TAG . '_' . $this->getId()];
        if ($this->getIsChangedCategories()) {
            foreach ($this->getAffectedCategoryIds() as $categoryId) {
                $identities[] = self::CACHE_PRODUCT_CATEGORY_TAG . '_' . $categoryId;
            }
        }
        if ($this->getOrigData('status') != $this->getData('status')) {
            foreach ($this->getCategoryIds() as $categoryId) {
                $identities[] = self::CACHE_PRODUCT_CATEGORY_TAG . '_' . $categoryId;
            }
        }
        return array_unique($identities);
    }

    /**
     * Reload PriceInfo object
     *
     * @return \Magento\Framework\Pricing\PriceInfo\Base
     */
    public function reloadPriceInfo()
    {
        if ($this->_priceInfo) {
            $this->_priceInfo = null;
            return $this->getPriceInfo();
        }
    }

    /**
     * Return Data Object data in array format.
     *
     * @return array
     * @todo refactor with converter for AbstractExtensibleModel
     */
    public function __toArray()
    {
        $data = $this->_data;
        $hasToArray = function ($model) {
            return is_object($model) && method_exists($model, '__toArray') && is_callable([$model, '__toArray']);
        };
        foreach ($data as $key => $value) {
            if ($hasToArray($value)) {
                $data[$key] = $value->__toArray();
            } elseif (is_array($value)) {
                foreach ($value as $nestedKey => $nestedValue) {
                    if ($hasToArray($nestedValue)) {
                        $value[$nestedKey] = $nestedValue->__toArray();
                    }
                }
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * Convert Category model into flat array.
     *
     * @return array
     */
    public function toFlatArray()
    {
        $dataArray = $this->__toArray();
        //process custom attributes if present
        if (array_key_exists('custom_attributes', $dataArray) && !empty($dataArray['custom_attributes'])) {
            /** @var \Magento\Framework\Api\AttributeInterface[] $customAttributes */
            $customAttributes = $dataArray['custom_attributes'];
            unset($dataArray['custom_attributes']);
            foreach ($customAttributes as $attributeValue) {
                $dataArray[$attributeValue[\Magento\Framework\Api\AttributeInterface::ATTRIBUTE_CODE]]
                    = $attributeValue[\Magento\Framework\Api\AttributeInterface::VALUE];
            }
        }
        return $dataArray;
    }

    //@codeCoverageIgnoreEnd
    /**
     * Set product sku
     *
     * @param string $sku
     * @return $this
     */
    public function setSku($sku)
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * Set product name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Set product store id
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Set product attribute set id
     *
     * @param int $attributeSetId
     * @return $this
     */
    public function setAttributeSetId($attributeSetId)
    {
        return $this->setData(self::ATTRIBUTE_SET_ID, $attributeSetId);
    }

    /**
     * Set product price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * Set product status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Set product visibility
     *
     * @param int $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        return $this->setData(self::VISIBILITY, $visibility);
    }

    /**
     * Set product created date
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Set product updated date
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Set product weight
     *
     * @param float $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        return $this->setData(self::WEIGHT, $weight);
    }

    /**
     * Set product type id
     *
     * @param string $typeId
     * @return $this
     */
    public function setTypeId($typeId)
    {
        if ($typeId !== $this->_getData('type_id')) {
            $this->_typeInstance = null;
        }
        return $this->setData(self::TYPE_ID, $typeId);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Catalog\Api\Data\ProductExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Catalog\Api\Data\ProductExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Catalog\Api\Data\ProductExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
    //@codeCoverageIgnoreEnd

    /**
     * @param array $mediaGallery
     * @return \Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface[]
     */
    protected function convertToMediaGalleryInterface(array $mediaGallery)
    {
        $entries = [];
        foreach ($mediaGallery as $image) {
            $entry = $this
                ->mediaGalleryEntryConverterPool
                ->getConverterByMediaType($image['media_type'])
                ->convertTo($this, $image);
            $entries[] = $entry;
        }
        return $entries;
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface[]|null
     */
    public function getMediaGalleryEntries()
    {
        $mediaGallery = $this->getMediaGallery('images');
        if ($mediaGallery === null) {
            return null;
        }
        //convert the data
        $convertedEntries = $this->convertToMediaGalleryInterface($mediaGallery);
        return $convertedEntries;
    }

    /**
     * @param ProductAttributeMediaGalleryEntryInterface[] $mediaGalleryEntries
     * @return $this
     */
    public function setMediaGalleryEntries(array $mediaGalleryEntries = null)
    {
        if ($mediaGalleryEntries !== null) {
            $images = [];
            foreach ($mediaGalleryEntries as $entry) {
                $images[] = $this
                    ->mediaGalleryEntryConverterPool
                    ->getConverterByMediaType($entry->getMediaType())
                    ->convertFrom($entry);
            }
            $this->setData('media_gallery', ['images' => $images]);

        }
        return $this;
    }
}

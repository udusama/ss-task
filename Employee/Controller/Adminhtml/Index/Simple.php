<?php
declare(strict_types=1);

namespace Vendor\Employee\Controller\Adminhtml\Index;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesCollectionFactory;

/**
 * Class used to get simple data from product collection and Order Collection
 */
class Simple implements ActionInterface
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;
    /**
     * @var ProductFactory
     */
    protected $productFactory;
    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;
    /**
     * @var Csv
     */
    protected $csvProcessor;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;
    protected $itemCollection;
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    /**
     * @var SalesCollectionFactory
     */
    private SalesCollectionFactory $orderCollection;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param ProductFactory $productFactory
     * @param LayoutFactory $resultLayoutFactory
     * @param Csv $csvProcessor
     * @param DirectoryList $directoryList
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SalesCollectionFactory $orderCollection
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductCollectionFactory $itemCollection
     */
    public function __construct(
        Context                    $context,
        FileFactory                $fileFactory,
        ProductFactory             $productFactory,
        LayoutFactory              $resultLayoutFactory,
        Csv                        $csvProcessor,
        DirectoryList              $directoryList,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        SalesCollectionFactory     $orderCollection,
        OrderRepositoryInterface   $orderRepository,
        ProductCollectionFactory   $itemCollection,
    ) {
        $this->fileFactory = $fileFactory;
        $this->productFactory = $productFactory;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->csvProcessor = $csvProcessor;
        $this->directoryList = $directoryList;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderCollection = $orderCollection;
        $this->orderRepository = $orderRepository;
        $this->itemCollection = $itemCollection;
    }
    public function execute()
    {
        $content1[] = [
            'name' => __('Product Name'),
            'price' => __('Product Price'),
            'sku' => __('Sku'),
            'qty_ordered' => __('Quantity Ordered'),
            'product_type' => __('Product Type'),
            'created_at' => __('Created At'),
            'updated_at' => __('Updated At'),
            'weight' => __('Weight Of product'),
            'status' => __('Status Of product'),
            'row_total' => __('Row Total Of product'),
            'discount_amount' => __('Discount price'),
            '' => __('Total Revenue'),

        ];
        $fileName = 'Simple_product_file.csv'; // Add Your CSV File name
        $filePath = $this->directoryList->getPath(DirectoryList::MEDIA) . "/" . $fileName;
        $collection = $this->orderCollection->create()->addAttributeToSelect('*'); // It returns all order collection
        $totalRevenue = 0;
        foreach ($collection as $key => $value) {
            foreach ($value->getAllItems() as $key1 => $product) {
                if ($product->getProductType() == 'simple') {
                    $items = $value->getAllItems();
                    $totalRevenue += $product->getRowTotal() - $product->getDiscountAmount();
                    $content1[] = [
                        $product->getName(),
                        $product->getPrice(),
                        $product->getSku(),
                        $product->getQtyOrdered(),
                        $product->getProductType(),
                        $product->getCreatedAt(),
                        $product->getUpdatedAt(),
                        $product->getWeight(),
                        $product->getStatus(),
                        $product->getRowTotal(),
                        $product->getDiscountAmount(),
                        $totalRevenue,
                    ];
                }
            }
        }
        $content2[] = [
            'entity_id' => __('Entity ID'),
            'type_id' => __('Type ID'),
            'sku' => __('Sku'),
            'created_at' => __('Created At'),
            'updated_at' => __('Updated At'),
            'status' => __('Status of product'),

        ];
        $collection = $this->itemCollection->create();
        $collection->addAttributeToSelect('*');
        $fileName = 'simple_product_file.csv'; // Add Your CSV File name
        $filePath = $this->directoryList->getPath(DirectoryList::MEDIA) . "/" . $fileName;
        while ($product = $collection->fetchItem()) {
            if ($product->getTypeId() == 'simple') {
                $content2[] = [
                    $product->getEntityId(),
                    $product->getTypeId(),
                    $product->getSku(),
                    $product->getCreatedAt(),
                    $product->getUpdatedAt(),
                    $product->getStatus(),

                ];
            }
        }
        $mergedContent = array_merge_recursive($content1, $content2);
        $this->csvProcessor->setEnclosure('"')->setDelimiter(',')->appendData($filePath, $mergedContent);
        return $this->fileFactory->create(
            $fileName,
            [
                'type' => "filename",
                'value' => $fileName,
                'rm' => false, // True => File will be remove from directory after download.
            ],
            DirectoryList::MEDIA,
            'text/csv',
            null
        );
    }
}

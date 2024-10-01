<?php

/**
 * Explanation
 *
 * This PO product model observer is achieving the following functionalities:
 * 1. If the product cost is changed and the system is configured to restrict products to assigned suppliers,
 *    then set the cost price to the supplier's cost price, preventing the user from changing it.
 * 2. When a PO product is created, it creates taxes for the product, updates the sub-total and tax of the PO.
 * 3. When a PO product is updated;
 *    a. It recreates taxes for the product in case the sub-total of the product is changed. (Subtotal is virtual)
 *    b. Updates the sub-total and tax of the PO.
 *    c. Update the purchased quantity of the purchase requisition product if it exists.
 * 4. When a PO product is deleted, it updates the sub-total and tax of the PO, and updates the purchased quantity
 *    of the purchase requisition product if it exists.
 */

namespace Modules\Inventory\Observers;

use Modules\Catalog\Models\Product;
use Modules\Inventory\Actions\PurchaseOrderProducts\CreateTaxes;
use Modules\Inventory\Actions\PurchaseOrders\UpdateSubTotal;
use Modules\Inventory\Actions\PurchaseOrders\UpdateTax;
use Modules\Inventory\Actions\PurchaseRequisitions\UpdatePurchasedQuantity;
use Modules\Inventory\Models\PurchaseOrderProduct;

class PurchaseOrderProductObserver
{
    public function __construct(
        protected CreateTaxes $createTaxesAction,
        protected UpdateSubTotal $updateSubTotalAction,
        protected UpdateTax $updateTaxAction,
        protected UpdatePurchasedQuantity $updatePurchasedQuantityAction,
    ) {
    }

    public function saving(PurchaseOrderProduct $purchaseOrderProduct): void
    {
        // If the product cost is changed and the system is configured to restrict products to assigned suppliers,
        // set the cost price to the supplier's cost price, preventing the user from changing it.
        if (config('catalog.settings.restrict_products_to_assigned_suppliers') && $purchaseOrderProduct->isDirty('cost')) {
            $costPriceBySupplier = Product::getCostPriceBySupplier(
                product: $purchaseOrderProduct->product_id,
                supplier: $purchaseOrderProduct->purchaseOrder->supplier_id,
            );

            if ($costPriceBySupplier) {
                $purchaseOrderProduct->cost = $costPriceBySupplier;
            }
        }
    }

    public function created(PurchaseOrderProduct $purchaseOrderProduct): void
    {
        $this->createTaxesAction->execute($purchaseOrderProduct->refresh());

        $this->updateSubTotalAction->execute($purchaseOrderProduct->purchaseOrder);

        $this->updateTaxAction->execute($purchaseOrderProduct->purchaseOrder);
    }

    public function updated(PurchaseOrderProduct $purchaseOrderProduct): void
    {
        if ($purchaseOrderProduct->wasChanged('cost', 'quantity', 'discount_calculation_method', 'discount_rate')) {
            $this->createTaxesAction->execute($purchaseOrderProduct->refresh());
        }

        $this->updateSubTotalAction->execute($purchaseOrderProduct->purchaseOrder);

        $this->updateTaxAction->execute($purchaseOrderProduct->purchaseOrder);

        if ($purchaseOrderProduct->purchaseRequisitionProduct) {
            $this->updatePurchasedQuantityAction->execute($purchaseOrderProduct->purchaseRequisitionProduct);
        }
    }

    public function deleted(PurchaseOrderProduct $purchaseOrderProduct): void
    {
        $this->updateSubTotalAction->execute($purchaseOrderProduct->purchaseOrder);

        $this->updateTaxAction->execute($purchaseOrderProduct->purchaseOrder);

        if ($purchaseOrderProduct->purchaseRequisitionProduct) {
            $this->updatePurchasedQuantityAction->execute($purchaseOrderProduct->purchaseRequisitionProduct);
        }
    }
}

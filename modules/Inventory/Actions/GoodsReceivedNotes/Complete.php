<?php

/**
 * Explanation for IxDF
 *
 * Goods received notes get marked as "Completed" in multiple places in the system. So I have this action
 * to do necessary checks that are required for a GRN to be marked as "Completed".
 */

namespace Modules\Inventory\Actions\GoodsReceivedNotes;

use Modules\Inventory\Enums\GoodsReceivedNotes\Status;
use Modules\Inventory\Exceptions\GoodsReceivedNotes\GoodsReceivedNoteProductExceedsPendingQuantity;
use Modules\Inventory\Exceptions\GoodsReceivedNotes\PurchaseOrderProductHasNoPendingQuantity;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\GoodsReceivedNoteProduct;

class Complete
{
    public function execute(GoodsReceivedNote $goodsReceivedNote): void
    {
        $goodsReceivedNote->items->each(function (GoodsReceivedNoteProduct $goodsReceivedNoteItem): void {
            if ($goodsReceivedNoteItem->purchaseOrderProduct->pendingQuantity <= 0) {
                throw new PurchaseOrderProductHasNoPendingQuantity("Product \"{$goodsReceivedNoteItem->purchaseOrderProduct->product->name}\" has no pending quantity.");
            } elseif ($goodsReceivedNoteItem->quantity > $goodsReceivedNoteItem->purchaseOrderProduct->pendingQuantity) {
                throw new GoodsReceivedNoteProductExceedsPendingQuantity("GRN quantity of the product \"{$goodsReceivedNoteItem->purchaseOrderProduct->product->name}\" is exceeding the pending quantity.");
            }
        });

        $goodsReceivedNote->status = Status::COMPLETED;

        $goodsReceivedNote->save();
    }
}


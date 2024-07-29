<?php

/**
 * Explanation for IxDF
 *
 * This test covers cases that are related to GRN completions.
 */

namespace Modules\Inventory\Tests\Feature\GoodsReceivedNotes;

use Illuminate\Support\Facades\Event;
use Modules\Inventory\Enums\GoodsReceivedNotes\Status as GoodsReceivedNoteStatus;
use Modules\Inventory\Enums\PurchaseOrders\ReceivedStatus as PurchaseOrderReceivedStatus;
use Modules\Inventory\Events\GoodsReceivedNoteCompleted;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\GoodsReceivedNoteProduct;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\PurchaseOrderProduct;
use Modules\Inventory\Models\StockMovement;
use Tests\TestCase;

class GoodsReceivedNoteCompletionTest extends TestCase
{
    public function test_it_dispatches_the_goods_received_note_completed_event_on_completion(): void
    {
        Event::fake(GoodsReceivedNoteCompleted::class);

        $goodsReceivedNote = GoodsReceivedNote::factory()
            ->set('status', GoodsReceivedNoteStatus::DRAFT)
            ->create();

        $goodsReceivedNote->update([
            'status' => GoodsReceivedNoteStatus::COMPLETED,
        ]);

        Event::assertDispatched(GoodsReceivedNoteCompleted::class);
    }

    public function test_it_updates_the_purchase_order_received_status_to_partially_received_on_completion(): void
    {
        $purchaseOrder = PurchaseOrder::factory()
            ->set('received_status', PurchaseOrderReceivedStatus::NOT_RECEIVED)
            ->has(PurchaseOrderProduct::factory(), 'items')
            ->create();

        $goodsReceivedNote = GoodsReceivedNote::factory()
            ->for($purchaseOrder->organization)
            ->for($purchaseOrder)
            ->set('status', GoodsReceivedNoteStatus::DRAFT)
            ->create();

        // Deduct the received quantity by 1 to make it partially received.
        $goodsReceivedNote->items->first()->update([
            'quantity' => $goodsReceivedNote->items->first()->quantity - 1,
        ]);

        $goodsReceivedNote->update([
            'status' => GoodsReceivedNoteStatus::COMPLETED,
        ]);

        $this->assertDatabaseHas(PurchaseOrder::class, [
            'id' => $purchaseOrder->id,
            'received_status' => PurchaseOrderReceivedStatus::PARTIALLY_RECEIVED,
        ]);
    }

    public function test_it_updates_the_purchase_order_received_status_to_received_on_completion(): void
    {
        $purchaseOrder = PurchaseOrder::factory()
            ->set('received_status', PurchaseOrderReceivedStatus::NOT_RECEIVED)
            ->has(PurchaseOrderProduct::factory(), 'items')
            ->create();

        $goodsReceivedNote = GoodsReceivedNote::factory()
            ->for($purchaseOrder->organization)
            ->for($purchaseOrder)
            ->set('status', GoodsReceivedNoteStatus::DRAFT)
            ->create();

        $goodsReceivedNote->update([
            'status' => GoodsReceivedNoteStatus::COMPLETED,
        ]);

        $this->assertDatabaseHas(PurchaseOrder::class, [
            'id' => $purchaseOrder->id,
            'received_status' => PurchaseOrderReceivedStatus::FULLY_RECEIVED,
        ]);
    }

    public function test_it_creates_a_stock_movement_record_on_completion(): void
    {
        $goodsReceivedNote = GoodsReceivedNote::factory()
            ->set('status', GoodsReceivedNoteStatus::DRAFT)
            ->has(GoodsReceivedNoteProduct::factory(), 'items')
            ->create();

        $goodsReceivedNote->update([
            'status' => GoodsReceivedNoteStatus::COMPLETED,
        ]);

        /** @var GoodsReceivedNoteProduct $goodsReceivedNoteItem */
        $goodsReceivedNoteItem = $goodsReceivedNote->items()->first();

        $this->assertDatabaseHas(StockMovement::class, [
            'product_id' => $goodsReceivedNoteItem->purchaseOrderProduct->product_id,
            'location_id' => $goodsReceivedNote->location_id,
            'document_id' => $goodsReceivedNote->id,
            'document_type' => GoodsReceivedNote::class,
            'quantity' => $goodsReceivedNoteItem->quantity,
            'created_at' => $goodsReceivedNote->date->setTimeFrom($goodsReceivedNote->created_at),
        ]);
    }
}


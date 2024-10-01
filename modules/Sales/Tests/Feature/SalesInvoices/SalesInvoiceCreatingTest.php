<?php

/**
 * Explanation
 *
 * As mentioned in the trait's explanation, this model supports 3 different numbering behaviors.
 */

namespace Modules\Sales\Tests\Feature\SalesInvoices;

use App\Traits\Tests\HasNumberingTests;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Administration\Models\Organization;
use Modules\Inventory\Models\InventorySetting;
use Modules\Sales\Enums\SalesOrders\InvoiceStatus as SalesOrderInvoiceStatus;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderProduct;
use Modules\Sales\Models\SalesSetting;
use Tests\TestCase;

class SalesInvoiceCreatingTest extends TestCase
{
    use HasNumberingTests;

    public function test_it_does_not_update_the_sales_order_invoice_status_on_create(): void
    {
        $salesOrder = SalesOrder::factory()
            ->has(SalesOrderProduct::factory(), 'items')
            ->set('invoice_status', SalesOrderInvoiceStatus::NOT_INVOICED)
            ->create();

        SalesInvoice::factory()
            ->for($salesOrder->organization)
            ->for($salesOrder)
            ->create();

        $this->assertDatabaseHas(SalesOrder::class, [
            'id' => $salesOrder->id,
            'invoice_status' => SalesOrderInvoiceStatus::NOT_INVOICED,
        ]);
    }

    protected function getStartingNumberKey(): string
    {
        return 'sales_invoice_starting_number';
    }

    protected function getPrefixKey(): string
    {
        return 'sales_invoice_prefix';
    }

    protected function getSettingsRelationship(Organization $organization): InventorySetting|SalesSetting
    {
        return $organization->salesSettings;
    }

    protected function getModelName(): string
    {
        return SalesInvoice::class;
    }

    protected function getModelFactory(): Factory
    {
        return SalesInvoice::factory();
    }

    protected function hasCompanyNumbers(): bool
    {
        return true;
    }
}

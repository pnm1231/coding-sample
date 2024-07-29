# IxDF Samples

This repository is a collection of code snippets and tests of a multi-tenant, multi-location inventory SaaS I built
recently for a client.

### App (shared)
* [app/Helpers/Database/Migrations/GeneratedFields.php](app/Helpers/Database/Migrations/GeneratedFields.php)
* [app/Traits/Tests/HasNumberingTests.php](app/Traits/Tests/HasNumberingTests.php)
* [app/Traits/Models/HasDocumentNumber.php](app/Traits/Models/HasDocumentNumber.php)

### Inventory Module
* [modules/Inventory/Actions/GoodsReceivedNotes/Complete.php](modules/Inventory/Actions/GoodsReceivedNotes/Complete.php)
* [modules/Inventory/Observers/PurchaseOrderProductObserver.php](modules/Inventory/Observers/PurchaseOrderProductObserver.php)
* [modules/Inventory/Tests/Feature/GoodsReceivedNotes/GoodsReceivedNoteCompletionTest.php](modules/Inventory/Tests/Feature/GoodsReceivedNotes/GoodsReceivedNoteCompletionTest.php)

### Sales Module
* [modules/Sales/Tests/Feature/SalesInvoices/SalesInvoiceCreatingTest.php](modules/Sales/Tests/Feature/SalesInvoices/SalesInvoiceCreatingTest.php)

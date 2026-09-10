<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Quotations (Penawaran Harga)
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('quotation_number', 50);
            $table->date('quotation_date');
            $table->date('valid_until');
            $table->string('status', 30)->default('draft'); // draft, sent, accepted, rejected, converted
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'quotation_number']);
        });

        Schema::create('quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });

        // 2. Sales Orders (Pesanan Penjualan)
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->string('order_number', 50);
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('status', 30)->default('draft'); // draft, confirmed, processing, fulfilled, closed, cancelled
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('credit_checked')->default(true);
            $table->boolean('credit_hold')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'order_number']);
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('ordered_quantity', 12, 4)->default(1);
            $table->decimal('delivered_quantity', 12, 4)->default(0);
            $table->decimal('invoiced_quantity', 12, 4)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });

        // 3. Delivery Orders (Surat Jalan / Pengiriman Barang)
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('delivery_number', 50);
            $table->date('delivery_date');
            $table->string('driver_name', 100)->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('status', 30)->default('draft'); // draft, dispatched, delivered, returned
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'delivery_number']);
        });

        Schema::create('delivery_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->nullable()->constrained('sales_order_lines')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit', 20)->default('PCS');
            $table->timestamps();
        });

        // 4. Purchase Requests (Permintaan Pembelian Internal)
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('request_number', 50);
            $table->date('request_date');
            $table->date('required_date')->nullable();
            $table->string('status', 30)->default('draft'); // draft, submitted, approved, rejected, converted
            $table->text('purpose')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'request_number']);
        });

        Schema::create('purchase_request_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_description', 255);
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit', 20)->default('PCS');
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->timestamps();
        });

        // 5. Purchase Orders (Pesanan Pembelian ke Supplier)
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
            $table->string('po_number', 50);
            $table->date('order_date');
            $table->date('expected_arrival_date')->nullable();
            $table->string('status', 30)->default('draft'); // draft, sent, partial_received, received, billed, closed, cancelled
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'po_number']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('ordered_quantity', 12, 4)->default(1);
            $table->decimal('received_quantity', 12, 4)->default(0);
            $table->decimal('billed_quantity', 12, 4)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });

        // 6. Goods Receipts (Penerimaan Barang Fisik di Gudang)
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('receipt_number', 50);
            $table->string('supplier_delivery_number', 100)->nullable();
            $table->date('receipt_date');
            $table->string('status', 30)->default('draft'); // draft, confirmed, billed
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'receipt_number']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('accepted_quantity', 12, 4)->default(1);
            $table->decimal('rejected_quantity', 12, 4)->default(0);
            $table->string('unit', 20)->default('PCS');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_request_lines');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('delivery_order_lines');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('quotations');
    }
};

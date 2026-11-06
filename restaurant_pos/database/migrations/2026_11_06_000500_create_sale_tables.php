<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->string('customer_code', 10)->nullable();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_mobile', 20)->nullable();
            $table->string('customer_tax_id', 20)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_email', 100)->nullable();
            $table->string('customer_id_number', 15)->nullable();
            $table->date('birth_date')->nullable();
            $table->timestamps();
            $table->index(['customer_name', 'customer_tax_id']);
            $table->index('customer_phone');
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->unsignedBigInteger('invoice_ref')->nullable()->comment('Igual al id; también es el nro de tarjeta prepago');
            $table->foreignId('branch_id')->constrained('branches');
            $table->integer('order_number')->default(0)->comment('Número de orden del día');
            $table->date('sale_date');
            $table->string('login', 20)->comment('Usuario que registró la venta');
            $table->string('payment_method', 10)->comment('CONTADO, CREDITO, CONSUMO, TARJETA...');
            $table->string('status', 12)->default('ACTIVE')->comment('ACTIVO / INACTIVO (anulada)');
            $table->string('voided_by', 20)->nullable();
            $table->string('service_type', 20)->nullable()->comment('MESA, LLEVAR, DOMICILIO...');
            $table->string('shift', 6)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->integer('seller_id')->default(0)->comment('Id del mesero / vendedor');
            $table->string('sale_time', 5)->nullable()->comment('Hora de venta');
            $table->decimal('exchange_rate', 10, 3)->default(0)->comment('Tipo de cambio');
            $table->decimal('invoice_total', 12, 4)->default(0);
            $table->decimal('debt_total', 12, 4)->default(0);
            $table->string('payable', 1)->nullable();
            $table->string('pending', 1)->nullable();
            $table->string('voided', 1)->nullable()->comment('S = anulada');
            $table->string('closed', 1)->nullable()->comment('S = incluida en cierre de caja');
            $table->integer('invoice_number')->default(0)->comment('Número de factura (folio)');
            $table->integer('real_invoice_number')->default(0)->comment('Número de factura real (folior)');
            $table->string('prepaid_ref', 15)->nullable()->comment('Prepago seleccionado');
            $table->decimal('paid_usd', 12, 3)->default(0);
            $table->decimal('change_usd', 12, 3)->default(0);
            $table->decimal('paid_bob', 12, 3)->default(0);
            $table->decimal('change_bob', 12, 3)->default(0);
            $table->string('currency', 2)->nullable();
            $table->string('staff_code', 10)->nullable();
            $table->string('customer_tax_id', 20)->nullable();
            $table->string('customer_id_number', 15)->nullable();
            $table->string('customer_name', 100)->nullable();
            $table->string('auth_key', 500)->nullable()->comment('Llave de dosificación usada');
            $table->string('authorization_number', 20)->nullable();
            $table->date('limit_date')->nullable();
            $table->string('control_code', 50)->nullable()->comment('Código de control SIN');
            $table->decimal('prepaid_balance', 12, 2)->nullable()->comment('Saldo de tarjeta prepago');
            $table->string('orders_ref', 10)->nullable();
            $table->string('delivered', 1)->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'sale_date']);
            $table->index('invoice_ref');
            $table->index('login');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->integer('order_number')->default(0);
            $table->integer('item')->default(0);
            $table->unsignedBigInteger('product_code')->comment('Id del producto (99999 = atención billar)');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 4)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('exchange_rate', 10, 3)->default(0);
            $table->date('sale_date');
            $table->string('sale_time', 5)->nullable();
            $table->string('product_name', 70);
            $table->string('unit', 4)->nullable();
            $table->string('pending', 1)->nullable();
            $table->string('voided', 1)->nullable()->comment('S = anulado');
            $table->string('options_mode', 15)->nullable();
            $table->string('category_group', 30)->nullable();
            $table->string('closed', 1)->nullable();
            $table->string('shift', 6)->nullable();
            $table->string('family', 20)->nullable()->comment('Forma de pago de la venta');
            $table->decimal('unit_factor', 12, 4)->default(0);
            $table->string('service_type', 20)->nullable();
            $table->integer('invoice_number')->default(0);
            $table->integer('real_invoice_number')->default(0);
            $table->decimal('factor', 10, 5)->default(0);
            $table->string('subtotal', 60)->nullable()->comment('Subtotal de la línea');
            $table->string('group_field', 20)->nullable();
            $table->string('qr_code', 20)->nullable()->comment('Código QR');
            $table->string('group_code', 90)->nullable();
            $table->string('login', 20)->nullable();
            $table->string('orders_ref', 10)->nullable();
            $table->string('note', 254)->nullable();
            $table->string('barcode', 30)->nullable();
            $table->integer('index_number')->default(0);
            $table->string('is_consumption', 1)->nullable();
            $table->timestamps();
            $table->index('sale_date');
            $table->index('product_code');
        });

        Schema::create('prepaid_card_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->integer('order_number')->default(0);
            $table->integer('item')->default(0);
            $table->unsignedBigInteger('product_code');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 4)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('exchange_rate', 10, 3)->default(0);
            $table->date('sale_date');
            $table->string('sale_time', 5)->nullable();
            $table->string('product_name', 70);
            $table->string('unit', 4)->nullable();
            $table->string('pending', 1)->nullable();
            $table->string('voided', 1)->nullable();
            $table->string('options_mode', 15)->nullable();
            $table->string('category_group', 30)->nullable();
            $table->string('closed', 1)->nullable();
            $table->string('shift', 6)->nullable();
            $table->string('family', 20)->nullable();
            $table->decimal('unit_factor', 12, 4)->default(0);
            $table->string('service_type', 20)->nullable();
            $table->integer('invoice_number')->default(0);
            $table->integer('real_invoice_number')->default(0);
            $table->decimal('factor', 10, 5)->default(0);
            $table->string('subtotal', 20)->nullable();
            $table->string('group_field', 20)->nullable();
            $table->string('qr_code', 20)->nullable();
            $table->string('group_code', 90)->nullable();
            $table->string('login', 20)->nullable();
            $table->string('orders_ref', 10)->nullable();
            $table->timestamps();
            $table->index('sale_date');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index()->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches');
            $table->integer('expense_number')->default(0);
            $table->date('expense_date');
            $table->string('delivered_to', 60)->nullable();
            $table->string('concept', 150)->nullable();
            $table->string('expense_time', 8)->nullable();
            $table->string('login', 20)->nullable();
            $table->decimal('exchange_rate', 10, 3)->default(0);
            $table->decimal('amount', 12, 3)->default(0);
            $table->string('shift', 6)->nullable();
            $table->string('status', 10)->default('ACTIVE');
            $table->smallInteger('transaction_type')->default(0)->comment('0 = egreso, 1 = ingreso');
            $table->integer('supplier_invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('supplier_tax_id', 25)->nullable();
            $table->string('supplier_authorization', 25)->nullable();
            $table->string('supplier_control_code', 15)->nullable();
            $table->smallInteger('has_invoice')->default(0);
            $table->decimal('invoice_exchange_rate', 9, 2)->nullable();
            $table->smallInteger('tax_book')->default(0)->comment('1 = entra al libro de compras');
            $table->decimal('tax_credit', 12, 2)->nullable();
            $table->smallInteger('from_cash_drawer')->default(1)->comment('1 = afecta el arqueo de caja');
            $table->timestamps();
            $table->index(['branch_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('prepaid_card_items');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
    }
};

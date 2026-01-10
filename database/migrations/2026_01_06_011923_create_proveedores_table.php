<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->onDelete('cascade');
            $table->string('codigo', 20)->unique();
            $table->string('tipo_proveedor', 20); // Producto, Servicio, Ambos
            $table->string('rubro', 150)->nullable();
            $table->decimal('limite_credito', 15, 2)->default(0);
            $table->decimal('credito_usado', 15, 2)->default(0);
            $table->integer('dias_credito')->default(0);
            $table->decimal('descuento_general', 5, 2)->default(0);
            $table->string('cuenta_bancaria', 50)->nullable();
            $table->string('banco', 100)->nullable();
            $table->string('nombre_contacto', 150)->nullable();
            $table->string('cargo_contacto', 100)->nullable();
            $table->string('telefono_contacto', 20)->nullable();
            $table->string('email_contacto', 150)->nullable();
            $table->text('observaciones')->nullable();
            $table->date('fecha_registro')->nullable();
            $table->date('ultima_compra')->nullable();
            $table->decimal('total_compras', 15, 2)->default(0);
            $table->integer('calificacion')->default(3); // 1-5
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};

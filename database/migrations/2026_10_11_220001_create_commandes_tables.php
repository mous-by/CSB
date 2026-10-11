<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Boutique en ligne (cahier §15.3) : commandes passées depuis le panier, paiement manuel (espèces, Orange Money,
// Moov Money, Wave) confirmé par l'équipe. Les lignes figent la désignation et le prix du moment de la commande.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('status', 20)->default('nouvelle')->index();
            $table->string('name', 150);
            $table->string('organization', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30);
            $table->string('delivery_method', 20);
            $table->string('address', 300)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('payment_method', 20);
            $table->string('payment_reference', 100)->nullable();
            $table->decimal('total', 14, 0);
            $table->text('notes')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('access_token', 64)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('commande_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->restrictOnDelete();
            $table->foreignId('materiel_id')->nullable()->constrained('materiels')->nullOnDelete();
            $table->string('designation', 200);
            $table->decimal('unit_price', 12, 0);
            $table->decimal('normal_price', 12, 0)->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 14, 0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_lignes');
        Schema::dropIfExists('commandes');
    }
};

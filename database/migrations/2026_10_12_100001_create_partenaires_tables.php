<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Partenaires de diffusion (demande de CSB, 2026-10-12) : un partenaire (ex. une personne très suivie sur les réseaux
// sociaux) a son lien personnel (?ref=…) et éventuellement un code promo ; les visites, demandes, comptes clients et
// commandes venus de lui lui sont attribués, et chaque vente lui rapporte une commission à un taux réglable par type
// (matériel, logiciel, sur mesure).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('ref_code', 40)->unique();
            $table->string('promo_code', 30)->nullable()->unique();
            $table->decimal('promo_discount', 5, 2)->nullable();
            $table->date('promo_ends_on')->nullable();
            $table->decimal('rate_materiel', 5, 2)->default(0);
            $table->decimal('rate_logiciel', 5, 2)->default(0);
            $table->decimal('rate_sur_mesure', 5, 2)->default(0);
            $table->unsignedSmallInteger('cookie_days')->default(30);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('payment_info', 200)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('partenaire_visites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partenaire_id')->constrained('partenaires')->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('visits')->default(0);
            $table->unique(['partenaire_id', 'day']);
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partenaire_id')->constrained('partenaires')->restrictOnDelete();
            $table->nullableMorphs('source');
            $table->string('kind', 20);
            $table->string('label', 200);
            $table->decimal('base_amount', 14, 0);
            $table->decimal('rate', 5, 2);
            $table->decimal('amount', 14, 0);
            $table->string('status', 20)->default('a_payer')->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        foreach (['demandes', 'clients'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->foreignId('partenaire_id')->nullable()->constrained('partenaires')->nullOnDelete());
        }
        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignId('partenaire_id')->nullable()->after('client_id')->constrained('partenaires')->nullOnDelete();
            $table->decimal('subtotal', 14, 0)->nullable()->after('payment_reference');
            $table->string('promo_code', 30)->nullable()->after('subtotal');
            $table->decimal('discount_amount', 14, 0)->default(0)->after('promo_code');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partenaire_id');
            $table->dropColumn(['subtotal', 'promo_code', 'discount_amount']);
        });
        foreach (['demandes', 'clients'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('partenaire_id'));
        }
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('partenaire_visites');
        Schema::dropIfExists('partenaires');
    }
};

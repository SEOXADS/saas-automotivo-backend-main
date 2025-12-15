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
        Schema::create('tenant_seo_urls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('locale', 10)->index();
            $table->string('path', 512)->index();
            $table->enum('type', ['vehicle_detail', 'collection', 'blog_post', 'faq', 'static'])->index();
            $table->string('canonical_url', 512);
            $table->boolean('is_indexable')->default(true);
            $table->boolean('include_in_sitemap')->default(true);
            $table->decimal('sitemap_priority', 2, 1)->default(0.5);
            $table->enum('sitemap_changefreq', ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])->default('weekly');
            $table->datetime('lastmod')->nullable();

            // Metadados SEO
            $table->string('title', 160)->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->string('og_image', 512)->nullable();
            $table->json('breadcrumbs')->nullable();
            $table->enum('structured_data_type', ['Vehicle', 'Product', 'Offer', 'CollectionPage', 'ItemList', 'Article', 'FAQPage', 'Organization', 'LocalBusiness'])->nullable();
            $table->json('structured_data_payload')->nullable();

            // Spin e conteúdo
            $table->json('content_data')->nullable();
            $table->json('content_templates')->nullable();
            $table->json('route_params')->nullable();
            $table->json('extra_meta')->nullable();

            // Auditoria
            $table->timestamps();

            // Índices
            $table->unique(['tenant_id', 'locale', 'path']);
            $table->index(['tenant_id', 'type']);
            $table->index(['include_in_sitemap', 'is_indexable']);

            // Foreign key
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_seo_urls');
    }
};

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomSeoEntry extends Model
{
    use HasFactory;

    protected $table = 'custom_seo_entries';

    protected $fillable = [
        'tenant_id',
        'page_url',
        'page_title',
        'subtitle',
        'meta_description',
        'meta_keywords',
        'meta_author',
        'meta_robots',
        'og_title',
        'og_description',
        'og_image_url',
        'og_site_name',
        'og_type',
        'og_locale',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image_url',
        'twitter_site',
        'twitter_creator',
        'canonical_url',
        'structured_data',
    ];

    protected $casts = [
        'structured_data' => 'array',
        'tenant_id' => 'integer',
    ];
}

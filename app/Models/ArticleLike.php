<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleLike extends Model {
    protected $fillable = ['article_id', 'ip_address'];

    public function article() {
        return $this->belongsTo(Article::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{     
     protected $fillable = [
        'title',
        'excerpt',
        'date',
        'views',
        'likes',
        'feature_image',
        'feature_video',
        'tags',
        'content',
        'lang',
        'author_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'content' => 'array', // علشان يخزن الـ JSON كـ array في الداتا
        'date' => 'datetime',
    ];

    // علاقة مع المؤلف
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // لو عايز تحفظ عدد المشاهدات واللايكات بشكل منفصل
    public function viewsRelation()
    {
        return $this->hasMany(ArticleView::class);
    }

    public function likesRelation()
    {
        return $this->hasMany(ArticleLike::class);
    }

    // ممكن تضيف Accessor لو حبيت تحسب العدد مباشرة
    public function getViewsCountAttribute()
    {
        return $this->viewsRelation()->count();
    }

    public function getLikesCountAttribute()
    {
        return $this->likesRelation()->count();
    }


}

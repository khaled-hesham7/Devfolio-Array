<?php

namespace App\Http\Resources;

use App\Models\ArticleLike;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ip = $request->ip();

        // الحل الذكي للـ N+1: 
        // بنشوف لو الداتا مبعوتة جاهزة من الكنترولر (is_liked) نستخدمها
        // لو مش موجودة (زي في الـ show مثلاً) بنعمل الكويري العادي
        $liked = $this->whenHas('is_liked', 
            $this->is_liked, 
            ArticleLike::where('article_id', $this->id)->where('ip_address', $ip)->exists()
        );

        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'excerpt'       => $this->excerpt,
            'lang'          => $this->lang,
            'feature_image' => $this->feature_image ? asset('storage/' . $this->feature_image) : null,
            'feature_video' => $this->feature_video ? asset('storage/' . $this->feature_video) : null,
            'tags'          => $this->tags ?? [],
            'author'        => $this->author ? [
                'id'   => $this->author->id,
                'name' => $this->author->name,
            ] : null,
            'stats'         => [
                'views' => $this->views ?? 0, // بنستخدم الحقل المباشر من الجدول
                'likes' => $this->likes_relation_count ?? $this->likes_count ?? 0, 
                'liked' => $liked,
            ],
            'date'          => [
                'raw'  => $this->date,
                'diff' => $this->date ? $this->date->diffForHumans() : null,
            ],
            'content'       => collect($this->content)->map(function ($item, $index) {
                return [
                    'id'     => $index + 1,
                    'title'  => $item['title'] ?? null,
                    'text'   => $item['text'] ?? null,
                    'code'   => $item['code'] ?? null, // الحقل الجديد
                    // هنا بنحول المسارات لروابط كاملة
                    'images' => collect($item['images'] ?? [])->map(fn($img) => asset('storage/' . $img))->toArray(),
                    'videos' => collect($item['videos'] ?? [])->map(fn($vid) => asset('storage/' . $vid))->toArray(),
                ];
            })->values()->toArray(),
        ];
    }
}
<?php

namespace App\Http\Resources;

use App\Models\ArticleLike;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        $ip = $request->ip();

        // نشوف هل هو عامل لايك قبل كده
        $liked = ArticleLike::where('article_id', $this->id)
            ->where('ip_address', $ip)
            ->exists();
        return [

            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'lang' => $this->lang,
            'feature_image' => $this->feature_image ? asset('storage/' . $this->feature_image) : null,
            'feature_video' => $this->feature_video ? asset('storage/' . $this->feature_video) : null,
            'tags' => $this->tags ?? [],
            'author' => $this->author ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ] : null,
            'stats' => [
                'views' => $this->views_count ?? 0,
                'likes' => $this->likes_count ?? 0,
                'liked' => $liked,
            ],
            'date' => [
                'raw' => $this->date,
                'diff' => $this->date ? $this->date->diffForHumans() : null,
            ],
            'content' => collect($this->content)->map(function ($item, $index) {
                return [
                    'id' => $index + 1,
                    'title' => $item['title'] ?? null,
                    'text' => $item['text'] ?? null,
                    'images' => $item['images'] ?? [],
                    'videos' => $item['videos'] ?? [],
                ];
            })->values()->toArray(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleLike;
use App\Models\ArticleView;
use Illuminate\Http\Request;
use App\Http\Resources\ArticleResource;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    /**
     * إضافة مقال جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string',
            'lang' => 'nullable|string',
            'content' => 'nullable|array', 
            'content.*.text' => 'nullable|string',
            'content.*.title' => 'nullable|string',
            'content.*.code' => 'nullable|string',
            'tags' => 'nullable|array',
            'feature_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp',
            'feature_video' => 'nullable|file|mimes:mp4,mov,avi,wmv',
            'content_images.*.*' => 'nullable|file|mimes:jpg,jpeg,png,gif',
            'content_videos.*.*' => 'nullable|file|mimes:mp4,mov,avi,wmv',
        ]);

        // رفع المرفقات الرئيسية
        $featureImagePath = $request->hasFile('feature_image')
            ? $request->file('feature_image')->store('articles/images', 'public')
            : null;

        $featureVideoPath = $request->hasFile('feature_video')
            ? $request->file('feature_video')->store('articles/videos', 'public')
            : null;

          
        $content = $request->input('content', []);

        foreach ($content as $i => &$item) {
            $item['title'] = $item['title'] ?? null;
            $item['text'] = $item['text'] ?? null;
            $item['code'] = $item['code'] ?? null; // التأكد من استلام الكود البرمجي

            $images = [];
            if ($request->hasFile("content_images.$i")) {
                foreach ($request->file("content_images.$i") as $imageFile) {
                    $images[] = $imageFile->store('articles/images', 'public');
                }
            }
            $item['images'] = $images;

            $videos = [];
            if ($request->hasFile("content_videos.$i")) {
                foreach ($request->file("content_videos.$i") as $videoFile) {
                    $videos[] = $videoFile->store('articles/videos', 'public');
                }
            }
            $item['videos'] = $videos;
        }

        unset($item);

        $article = Article::create([
            'title' => $request->title,
            'excerpt' => $request->excerpt,
            'lang' => $request->lang,
            'feature_image' => $featureImagePath,
            'feature_video' => $featureVideoPath,
            'content' => $content,
            'tags' => $request->tags ?? [],
            'author_id' => auth()->id(), // تم التعديل: الاعتماد على المستخدم المسجل فعلياً للأمان
            'date' => now(),
        ]);

        return response()->json(['message' => 'Article added successfully'], 201);
    }

    /**
     * عرض قائمة المقالات (مع حل مشكلة الـ N+1)
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 5);
        $ip = $request->ip();

        if ($perPage <= 50) {
            $articles = Article::with(['author']) 
                ->withCount(['likesRelation as likes_relation_count']) 
                ->withExists(['likesRelation as is_liked' => function($query) use ($ip) {
                    $query->where('ip_address', $ip); 
                }])
                ->latest() // ترتيب الأحدث أولاً
                ->paginate($perPage);

            return ArticleResource::collection($articles);
        }

        return response()->json(['message' => 'Maximum perPage value is 50'], 400);
    }

    /**
     * تحديث مقال موجود
     */
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'nullable|array',
        ]);

        if ($request->hasFile('feature_image')) {
            // مسح القديم لو حبيت توفر مساحة
            if($article->feature_image) Storage::disk('public')->delete($article->feature_image);
            $article->feature_image = $request->file('feature_image')->store('articles/images', 'public');
        }

        if ($request->hasFile('feature_video')) {
            if($article->feature_video) Storage::disk('public')->delete($article->feature_video);
            $article->feature_video = $request->file('feature_video')->store('articles/videos', 'public');
        }

            $content = $request->input('content', $article->content);

        foreach ($content as $i => &$item) {
            // تحديث الكود البرمجي: لو بعت كود جديد استخدمه، لو مبعتش حافظ على القديم
            $item['text'] = array_key_exists('text', $item) ? $item['text'] : ($article->content[$i]['text'] ?? null);
            $item['title'] = array_key_exists('title', $item) ? $item['title'] : ($article->content[$i]['title'] ?? null);
            $item['code'] = array_key_exists('code', $item) ? $item['code'] : ($article->content[$i]['code'] ?? null);

            if ($request->hasFile("content_images.$i")) {
                $newImages = [];
                foreach ($request->file("content_images.$i") as $imageFile) {
                    $newImages[] = $imageFile->store('articles/images', 'public');
                }
                $item['images'] = array_merge($item['images'] ?? [], $newImages);
            }

            if ($request->hasFile("content_videos.$i")) {
                $newVideos = [];
                foreach ($request->file("content_videos.$i") as $videoFile) {
                    $newVideos[] = $videoFile->store('articles/videos', 'public');
                }
                $item['videos'] = array_merge($item['videos'] ?? [], $newVideos);
            }
        }
                unset($item) ;
        $article->update([
            'title' => $request->title ?? $article->title,
            'excerpt' => $request->excerpt ?? $article->excerpt,
            'lang' => $request->lang ?? $article->lang,
            'content' => $content,
            'tags' => $request->tags ?? $article->tags,
        ]);

        return response()->json(['message' => 'Article updated successfully'], 200);
    }

    /**
     * حذف المقال ومرفقاته
     */
    public function destroy($id)
    {
        $article = Article::find($id);
        if (!$article) return response()->json(['message' => 'Article not found'], 404);

        if ($article->feature_image) Storage::disk('public')->delete($article->feature_image);
        if ($article->feature_video) Storage::disk('public')->delete($article->feature_video);

        // ملاحظة: لو الصور اللي جوه الـ content مش هتحتاجها تاني، ممكن تعمل loop هنا وتحذفها برضه
        
        $article->delete();
        return response()->json(['message' => 'Article deleted successfully'], 200);
    }

    /**
     * عرض مقال واحد وتسجيل مشاهدة
     */
    public function show(Request $request, $id)
    {
        $article = Article::find($id);
        if (!$article) return response()->json(['message' => 'Article not found'], 404);

        $ip = $request->ip();
        $alreadyViewed = ArticleView::where('article_id', $article->id)->where('ip_address', $ip)->exists();

        if (!$alreadyViewed) {
            ArticleView::create(['article_id' => $article->id, 'ip_address' => $ip]);
            $article->increment('views');
        }

        return response()->json([
            'message' => 'Article fetched successfully',
            'data' => new ArticleResource($article) 
        ], 200);
    }

    /**
     * نظام الإعجاب (Toggle Like)
     */
    public function toggleLike(Request $request, $id)
    {
        $article = Article::find($id);
        if (!$article) return response()->json(['message' => 'Article not found'], 404);

        $ip = $request->ip();
        $existingLike = ArticleLike::where('article_id', $id)->where('ip_address', $ip)->first();

        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
        } else {
            ArticleLike::create(['article_id' => $id, 'ip_address' => $ip]);
            $liked = true;
        }

        return response()->json([
            'message' => $liked ? 'Article liked' : 'Like removed',
            'liked' => $liked,
            'likes_count' => ArticleLike::where('article_id', $id)->count(),
        ]);
    }
}
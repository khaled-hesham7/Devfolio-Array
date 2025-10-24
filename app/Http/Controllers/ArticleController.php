<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleLike;
use App\Models\ArticleView;
use Illuminate\Http\Request;
use App\Http\Resources\ArticleResource;

class ArticleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string',
            'lang' => 'required|string',
            'content' => 'required|json', // array of items (title, text)
            'tags' => 'nullable|array',
            'feature_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp',
            'feature_video' => 'nullable|file|mimes:mp4,mov,avi,wmv',
            'content_images.*.*' => 'nullable|file|mimes:jpg,jpeg,png,gif',
            'content_videos.*.*' => 'nullable|file|mimes:mp4,mov,avi,wmv',
        ]);

        // رفع الصورة المميزة
        $featureImagePath = $request->hasFile('feature_image')
            ? $request->file('feature_image')->store('articles/images', 'public')
            : null;

        // رفع الفيديو المميز
        $featureVideoPath = $request->hasFile('feature_video')
            ? $request->file('feature_video')->store('articles/videos', 'public')
            : null;

        // تحويل الـ content من JSON إلى array
        $content = json_decode($request->input('content'), true) ?? [];

        // نضيف الصور والفيديوهات لو موجودة
        foreach ($content as $i => &$item) {
            $item['title'] = $item['title'] ?? null;
            $item['text'] = $item['text'] ?? null;

            // رفع الصور الخاصة بهذا الجزء
            $images = [];
            if ($request->hasFile("content_images.$i")) {
                foreach ($request->file("content_images.$i") as $imageFile) {
                    $path = $imageFile->store('articles/images', 'public');
                    $images[] = asset('storage/' . $path);
                }
            }
            $item['images'] = $images;

            // رفع الفيديوهات الخاصة بهذا الجزء
            $videos = [];
            if ($request->hasFile("content_videos.$i")) {
                foreach ($request->file("content_videos.$i") as $videoFile) {
                    $path = $videoFile->store('articles/videos', 'public');
                    $videos[] = asset('storage/' . $path);
                }
            }
            $item['videos'] = $videos;
        }

        // حفظ المقال
        $article = Article::create([
            'title' => $request->title,
            'excerpt' => $request->excerpt,
            'lang' => $request->lang,
            'feature_image' => $featureImagePath,
            'feature_video' => $featureVideoPath,
            'content' => $content,
            'tags' => $request->tags ?? [],
            'author_id' => auth()->id() ?? 1,
            'date' => now(),
        ]);
        return response()->json([
            'message' => 'Article added successfully'
        ], 201);


        // return (new ArticleResource($article))
        //     ->additional(['message' => 'Article added successfully'])
        //     ->response()
        //     ->setStatusCode(201);
    }

    // ===================================================//
    // ===================================================//
    // ===================================================//

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 5);
        if ($perPage <= 50) {
            $articles = Article::paginate($perPage);
            return ArticleResource::collection($articles);
        } else {
            return response()->json([
                'message' => 'Maximum perPage value is 10'
            ], 400);
        }
    }
    // ==================================//
    // ==================================//    


    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'excerpt' => 'sometimes|required|string',
            'lang' => 'sometimes|required|string',
            'content' => 'nullable|json',
            'tags' => 'nullable|array',
            'feature_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp',
            'feature_video' => 'nullable|file|mimes:mp4,mov,avi,wmv',
            'content_images.*.*' => 'nullable|file|mimes:jpg,jpeg,png,gif',
            'content_videos.*.*' => 'nullable|file|mimes:mp4,mov,avi,wmv',
        ]);

        // لو فيه feature image جديدة ارفعها
        if ($request->hasFile('feature_image')) {
            $featureImagePath = $request->file('feature_image')->store('articles/images', 'public');
            $article->feature_image = $featureImagePath;
        }

        // لو فيه feature video جديدة ارفعها
        if ($request->hasFile('feature_video')) {
            $featureVideoPath = $request->file('feature_video')->store('articles/videos', 'public');
            $article->feature_video = $featureVideoPath;
        }

        // content
        $content = $request->has('content')
            ? json_decode($request->input('content'), true)
            : $article->content;

        // نحدث الصور والفيديوهات لو فيه جديد
        foreach ($content as $i => &$item) {
            $item['title'] = $item['title'] ?? null;
            $item['text'] = $item['text'] ?? null;

            // صور جديدة
            if ($request->hasFile("content_images.$i")) {
                $images = [];
                foreach ($request->file("content_images.$i") as $imageFile) {
                    $path = $imageFile->store('articles/images', 'public');
                    $images[] = asset('storage/' . $path);
                }
                $item['images'] = array_merge($item['images'] ?? [], $images);
            }

            // فيديوهات جديدة
            if ($request->hasFile("content_videos.$i")) {
                $videos = [];
                foreach ($request->file("content_videos.$i") as $videoFile) {
                    $path = $videoFile->store('articles/videos', 'public');
                    $videos[] = asset('storage/' . $path);
                }
                $item['videos'] = array_merge($item['videos'] ?? [], $videos);
            }
        }

        // تحديث باقي الحقول
        $article->update([
            'title' => $request->title ?? $article->title,
            'excerpt' => $request->excerpt ?? $article->excerpt,
            'lang' => $request->lang ?? $article->lang,
            'content' => $content,
            'tags' => $request->tags ?? $article->tags,
        ]);

        return response()->json([
            'message' => 'Article updated successfully'
        ], 200);
    }

    // ===============================
    // ===============================
    // ===============================
    // ===============================
    // ===============================

    public function destroy($id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        // حذف الصور والفيديوهات لو موجودة
        if ($article->feature_image && file_exists(storage_path('app/public/' . $article->feature_image))) {
            unlink(storage_path('app/public/' . $article->feature_image));
        }

        if ($article->feature_video && file_exists(storage_path('app/public/' . $article->feature_video))) {
            unlink(storage_path('app/public/' . $article->feature_video));
        }

        // حذف المقال نفسه
        $article->delete();

        return response()->json(['message' => 'Article deleted successfully'], 200);
    }
  
/////////////////////////////////////////////////////////////////////////////////////

    public function show(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        // ✅ الخطوة الجديدة: تسجيل المشاهدة لو الـ IP أول مرة يشوف المقال
        $ip = $request->ip();

        $alreadyViewed = \App\Models\ArticleView::where('article_id', $article->id)
            ->where('ip_address', $ip)
            ->exists();

        if (!$alreadyViewed) {
            // أول مرة الزائر يشوف المقال
            ArticleView::create([
                'article_id' => $article->id,
                'ip_address' => $ip,
            ]);

            // نحدّث عداد المشاهدات العام
            $article->increment('views');
        }

        return response()->json([
            'message' => 'Article fetched successfully',
            'data' => new \App\Http\Resources\ArticleResource($article),
            // 'your_ip' => $request->ip()

        ], 200);
    }


    
public function toggleLike(Request $request, $id)
{
    $article = Article::find($id);

    if (!$article) {
        return response()->json(['message' => 'Article not found'], 404);
    }

    $ip = $request->ip();

    // هل الشخص دا عامل لايك قبل كده؟
    $existingLike = ArticleLike::where('article_id', $id)
        ->where('ip_address', $ip)
        ->first();

    if ($existingLike) {
        // لو عامل → نحذف اللايك (يعني UnLike)
        $existingLike->delete();
        $liked = false;
    } else {
        // لو مش عامل → نضيف لايك جديد
        ArticleLike::create([
            'article_id' => $id,
            'ip_address' => $ip,
        ]);
        $liked = true;
    }

    // نحسب عدد اللايكات الحالي
    $likeCount = ArticleLike::where('article_id', $id)->count();

    return response()->json([
        'message' => $liked ? 'Article liked' : 'Like removed',
        'liked' => $liked,
        'likes_count' => $likeCount,
    ]);
}
    
}


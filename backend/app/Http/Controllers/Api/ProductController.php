<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * List all products with filtering, search, and pagination
     */
    public function index(Request $request)
    {
        $query = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->select(
                'products.*',
                'categories.name as category_name',
                'categories.slug as category_slug',
                'brands.name as brand_name',
                'brands.slug as brand_slug'
            )
            ->whereNull('products.deleted_at')
            ->where('products.status', 'active');

        // Search by keyword
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('products.name', 'like', "%{$q}%")
                  ->orWhere('products.sku', 'like', "%{$q}%")
                  ->orWhere('products.description', 'like', "%{$q}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $cat = $request->input('category');
            $query->where(function ($w) use ($cat) {
                $w->where('categories.slug', $cat)
                  ->orWhere('categories.slug', 'like', "%{$cat}%");
            });
        }

        // Brand filter
        if ($request->filled('brand')) {
            $query->where('brands.slug', $request->input('brand'));
        }

        // Sort order
        $sortBy = $request->input('sort', 'latest');
        if ($sortBy === 'price_asc') {
            $query->orderBy('products.b2c_price', 'asc');
        } elseif ($sortBy === 'price_desc') {
            $query->orderBy('products.b2c_price', 'desc');
        } else {
            $query->orderBy('products.id', 'desc');
        }

        $products = $query->paginate($request->input('per_page', 12));

        return response()->json([
            'status' => 'success',
            'data' => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ])->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=120');
    }

    /**
     * Show single product detail
     */
    public function show($slug)
    {
        $product = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->select(
                'products.*',
                'categories.name as category_name',
                'categories.slug as category_slug',
                'brands.name as brand_name',
                'brands.slug as brand_slug'
            )
            ->where('products.slug', $slug)
            ->orWhere('products.id', is_numeric($slug) ? $slug : 0)
            ->first();

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found.'], 404);
        }

        // Get additional gallery images
        $images = DB::table('product_images')
            ->where('product_id', $product->id)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'product' => $product,
            'images' => $images,
        ]);
    }

    /**
     * Upload an image file (returns public access URL)
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:10240', // Max 10MB
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            
            $folder = $request->input('folder', 'products');
            if (!in_array($folder, ['products', 'avatars', 'profiles'])) {
                $folder = 'products';
            }

            // Store locally in public/storage
            $path = $file->storeAs($folder, $fileName, 'public');
            
            // Also copy to public/storage for direct web server access on exFAT/Windows drives
            $publicDir = public_path("storage/{$folder}");
            if (!file_exists($publicDir)) {
                @mkdir($publicDir, 0755, true);
            }
            @copy(storage_path("app/public/{$path}"), "{$publicDir}/{$fileName}");
            
            // Generate full URL
            $url = asset('storage/' . $path);

            return response()->json([
                'status' => 'success',
                'url' => $url,
                'path' => $path,
                'message' => 'Image uploaded successfully!',
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'No image file provided.'], 400);
    }

    /**
     * Admin create new product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'brand_id' => 'nullable|integer',
            'sku' => 'nullable|string|max:100',
            'b2c_price' => 'required|numeric|min:0',
            'b2b_price' => 'nullable|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'is_on_sale' => 'nullable|boolean',
            'is_new_arrival' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'badge' => 'nullable|string|max:50',
            'stock_quantity' => 'nullable|integer|min:0',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'image_file' => 'nullable|image|max:10240',
        ]);

        $thumbnailUrl = $validated['thumbnail'] ?? null;

        // If an image file was uploaded directly in the form
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $fileName = time() . '_' . Str::slug($validated['name']) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('products', $fileName, 'public');
            
            $publicDir = public_path('storage/products');
            if (!file_exists($publicDir)) {
                @mkdir($publicDir, 0755, true);
            }
            @copy(storage_path("app/public/{$path}"), "{$publicDir}/{$fileName}");

            $thumbnailUrl = asset('storage/' . $path);
        }

        $sku = !empty($validated['sku']) ? $validated['sku'] : ('MCC-' . strtoupper(Str::random(6)));
        $slug = Str::slug($validated['name']) . '-' . strtolower(Str::random(4));

        $b2bPrice = !empty($validated['b2b_price']) ? $validated['b2b_price'] : ($validated['b2c_price'] * 0.82);

        $productId = DB::table('products')->insertGetId([
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'sku' => $sku,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'thumbnail' => $thumbnailUrl ?: 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=600&q=80',
            'b2c_price' => $validated['b2c_price'],
            'b2b_price' => $b2bPrice,
            'compare_at_price' => !empty($validated['compare_at_price']) ? $validated['compare_at_price'] : null,
            'is_on_sale' => !empty($validated['is_on_sale']) ? 1 : 0,
            'is_new_arrival' => !empty($validated['is_new_arrival']) ? 1 : 0,
            'is_featured' => !empty($validated['is_featured']) ? 1 : 0,
            'badge' => !empty($validated['badge']) ? $validated['badge'] : null,
            'stock_quantity' => $validated['stock_quantity'] ?? 10,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget('api_categories');
        Cache::forget('api_brands');

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully and is now LIVE on store!',
            'product_id' => $productId,
            'slug' => $slug,
        ], 201);
    }

    /**
     * Categories list with real product counts
     */
    public function categories()
    {
        $categories = Cache::remember('api_categories_v2', 300, function () {
            $list = DB::table('categories')
                ->leftJoin('products', function($join) {
                    $join->on('categories.id', '=', 'products.category_id')
                        ->whereNull('products.deleted_at')
                        ->where('products.status', 'active');
                })
                ->select('categories.*', DB::raw('count(products.id) as products_count'))
                ->where('categories.is_active', true)
                ->groupBy('categories.id')
                ->orderBy('categories.sort_order', 'asc')
                ->get();

            return json_decode(json_encode($list), true);
        });

        if (!is_array($categories) || isset($categories['__PHP_Incomplete_Class_Name'])) {
            Cache::forget('api_categories_v2');
            $list = DB::table('categories')
                ->leftJoin('products', function($join) {
                    $join->on('categories.id', '=', 'products.category_id')
                        ->whereNull('products.deleted_at')
                        ->where('products.status', 'active');
                })
                ->select('categories.*', DB::raw('count(products.id) as products_count'))
                ->where('categories.is_active', true)
                ->groupBy('categories.id')
                ->orderBy('categories.sort_order', 'asc')
                ->get();
            $categories = json_decode(json_encode($list), true);
        }

        return response()->json(['status' => 'success', 'data' => $categories])
            ->header('Cache-Control', 'public, max-age=180, stale-while-revalidate=300');
    }

    /**
     * Brands list
     */
    public function brands()
    {
        $brands = Cache::remember('api_brands_v2', 300, function () {
            $list = DB::table('brands')->where('is_active', true)->get();
            return json_decode(json_encode($list), true);
        });

        if (!is_array($brands) || isset($brands['__PHP_Incomplete_Class_Name'])) {
            Cache::forget('api_brands_v2');
            $list = DB::table('brands')->where('is_active', true)->get();
            $brands = json_decode(json_encode($list), true);
        }

        return response()->json(['status' => 'success', 'data' => $brands])
            ->header('Cache-Control', 'public, max-age=180, stale-while-revalidate=300');
    }

    /**
     * Admin Dashboard Statistics & Live Database Records
     */
    public function dashboardStats()
    {
        $data = Cache::remember('admin_dashboard_stats_v2', 60, function () {
            $productsCount = DB::table('products')->whereNull('deleted_at')->count();
            $usersCount = DB::table('users')->count();
            $b2cCount = DB::table('users')->where('role', 'customer')->count();
            $b2bCount = DB::table('users')->whereIn('role', ['b2b_client', 'garage'])->count();
            $garagesCount = DB::table('partner_garages')->count();
            $ordersCount = DB::table('orders')->count();
            $totalRevenue = floatval(DB::table('orders')->sum('total_amount') ?? 0);

            // Real category distribution from products
            $categoriesWithCount = DB::table('categories')
                ->leftJoin('products', function($join) {
                    $join->on('categories.id', '=', 'products.category_id')
                        ->whereNull('products.deleted_at');
                })
                ->select('categories.id', 'categories.name', DB::raw('count(products.id) as count'))
                ->groupBy('categories.id', 'categories.name')
                ->get();

            $totalCatProducts = $categoriesWithCount->sum('count');
            $colors = ['#ff5500', '#fbbf24', '#ef4444', '#38bdf8', '#10b981', '#a855f7'];
            $idx = 0;
            $categoryBreakdown = $categoriesWithCount->map(function($c) use ($totalCatProducts, $colors, &$idx) {
                $pct = $totalCatProducts > 0 ? round(($c->count / $totalCatProducts) * 100) : 0;
                $color = $colors[$idx % count($colors)];
                $idx++;
                return [
                    'name' => $c->name,
                    'count' => intval($c->count),
                    'percentage' => $pct,
                    'color' => $color,
                ];
            });

            // Real geographic deliveries / partner garage locations
            $cities = DB::table('partner_garages')
                ->select('city', DB::raw('count(*) as count'))
                ->groupBy('city')
                ->get();
            $totalGarages = $cities->sum('count');
            $geoBreakdown = $cities->map(function($g) use ($totalGarages) {
                return [
                    'city' => $g->city,
                    'count' => intval($g->count),
                    'percentage' => $totalGarages > 0 ? round(($g->count / $totalGarages) * 100) : 0,
                ];
            });

            $recentUsers = DB::table('users')
                ->select('id', 'name', 'email', 'phone', 'role', 'user_mode', 'created_at')
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();

            $recentProducts = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.sku',
                    'products.b2c_price',
                    'products.b2b_price',
                    'products.stock_quantity',
                    'products.thumbnail',
                    'categories.name as category_name'
                )
                ->whereNull('products.deleted_at')
                ->orderBy('products.id', 'desc')
                ->limit(6)
                ->get();

            $recentGarages = DB::table('partner_garages')
                ->select('id', 'name', 'phone', 'email', 'address', 'city', 'status')
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();

            $res = [
                'stats' => [
                    'total_revenue' => $totalRevenue,
                    'products_count' => $productsCount,
                    'users_count' => $usersCount,
                    'b2c_count' => $b2cCount,
                    'b2b_count' => $b2bCount,
                    'garages_count' => $garagesCount,
                    'orders_count' => $ordersCount,
                ],
                'category_breakdown' => $categoryBreakdown,
                'geo_breakdown' => $geoBreakdown,
                'recent_users' => $recentUsers,
                'recent_products' => $recentProducts,
                'recent_garages' => $recentGarages,
            ];

            return json_decode(json_encode($res), true);
        });

        return response()->json(array_merge(['status' => 'success'], (array)$data))
            ->header('Cache-Control', 'private, max-age=30, stale-while-revalidate=60');
    }
}

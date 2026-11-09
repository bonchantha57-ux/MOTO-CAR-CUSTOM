<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AccountController extends Controller
{
    /**
     * Resolve authenticated user ID safely
     */
    protected function getUserId(Request $request)
    {
        if ($request->user()) {
            return $request->user()->id;
        }

        $bearer = $request->bearerToken();
        if ($bearer) {
            $token = PersonalAccessToken::findToken($bearer);
            if ($token) {
                return $token->tokenable_id;
            }
        }

        $id = $request->header('X-User-Id') ?: ($request->query('user_id') ?: $request->input('user_id'));
        if ($id) {
            return intval($id);
        }
        return null;
    }

    /**
     * User's real orders list from database
     */
    public function orders(Request $request)
    {
        $userId = $this->getUserId($request);

        if (!$userId) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'message' => 'User not identified.'
            ]);
        }

        $orders = DB::table('orders')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.user_id', $userId)
            ->whereNull('orders.deleted_at')
            ->select(
                'orders.*',
                'users.name as customer_name',
                'users.email as customer_email',
                'users.phone as customer_phone'
            )
            ->orderBy('orders.id', 'desc')
            ->get();

        foreach ($orders as $order) {
            $order->items = DB::table('order_items')
                ->where('order_id', $order->id)
                ->get();
            $order->items_count = count($order->items);
        }

        return response()->json([
            'status' => 'success',
            'data' => $orders,
            'total' => count($orders)
        ]);
    }

    /**
     * User's real garage vehicles from database
     */
    public function vehicles(Request $request)
    {
        $userId = $this->getUserId($request);

        if (!$userId) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }

        $vehicles = DB::table('user_garages')
            ->leftJoin('vehicle_makes', 'user_garages.vehicle_make_id', '=', 'vehicle_makes.id')
            ->leftJoin('vehicle_models', 'user_garages.vehicle_model_id', '=', 'vehicle_models.id')
            ->leftJoin('vehicle_years', 'user_garages.vehicle_year_id', '=', 'vehicle_years.id')
            ->leftJoin('vehicle_types', 'vehicle_makes.vehicle_type_id', '=', 'vehicle_types.id')
            ->where('user_garages.user_id', $userId)
            ->select(
                'user_garages.id',
                'user_garages.nickname',
                'user_garages.color',
                'user_garages.plate_number as plate',
                'user_garages.is_primary as active',
                'vehicle_makes.name as make',
                'vehicle_models.name as model',
                'vehicle_years.year_start as year',
                'vehicle_years.trim_level as trim',
                'vehicle_types.slug as type',
                'user_garages.created_at'
            )
            ->orderBy('user_garages.is_primary', 'desc')
            ->orderBy('user_garages.id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $vehicles
        ]);
    }

    /**
     * Add new vehicle to user's garage in database
     */
    public function storeVehicle(Request $request)
    {
        $userId = $this->getUserId($request);

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You must be logged in to save vehicles to your garage.'
            ], 401);
        }

        $validated = $request->validate([
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1970|max:2030',
            'type' => 'nullable|in:motorcycle,car',
            'trim' => 'nullable|string|max:100',
            'plate' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
        ]);

        $typeName = ($validated['type'] ?? 'motorcycle') === 'car' ? 'Car' : 'Motorcycle';
        $typeSlug = ($validated['type'] ?? 'motorcycle') === 'car' ? 'car' : 'motorcycle';

        // 1. Vehicle Type
        $type = DB::table('vehicle_types')->where('slug', $typeSlug)->first();
        if (!$type) {
            $typeId = DB::table('vehicle_types')->insertGetId([
                'name' => $typeName,
                'slug' => $typeSlug,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $typeId = $type->id;
        }

        // 2. Vehicle Make
        $makeSlug = Str::slug($validated['make']);
        $make = DB::table('vehicle_makes')->where('slug', $makeSlug)->first();
        if (!$make) {
            $makeId = DB::table('vehicle_makes')->insertGetId([
                'vehicle_type_id' => $typeId,
                'name' => $validated['make'],
                'slug' => $makeSlug,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $makeId = $make->id;
        }

        // 3. Vehicle Model
        $modelSlug = Str::slug($validated['model'] . '-' . $makeSlug);
        $model = DB::table('vehicle_models')->where('slug', $modelSlug)->first();
        if (!$model) {
            $modelId = DB::table('vehicle_models')->insertGetId([
                'vehicle_make_id' => $makeId,
                'name' => $validated['model'],
                'slug' => $modelSlug,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $modelId = $model->id;
        }

        // 4. Vehicle Year
        $yearRecord = DB::table('vehicle_years')
            ->where('vehicle_model_id', $modelId)
            ->where('year_start', $validated['year'])
            ->first();
        if (!$yearRecord) {
            $yearId = DB::table('vehicle_years')->insertGetId([
                'vehicle_model_id' => $modelId,
                'year_start' => $validated['year'],
                'trim_level' => $validated['trim'] ?? 'Standard Spec',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $yearId = $yearRecord->id;
        }

        // Check if this is the first vehicle (make it primary)
        $existingCount = DB::table('user_garages')->where('user_id', $userId)->count();

        $garageId = DB::table('user_garages')->insertGetId([
            'user_id' => $userId,
            'vehicle_make_id' => $makeId,
            'vehicle_model_id' => $modelId,
            'vehicle_year_id' => $yearId,
            'nickname' => $validated['make'] . ' ' . $validated['model'],
            'color' => $validated['color'] ?? null,
            'plate_number' => $validated['plate'] ?? null,
            'is_primary' => $existingCount === 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle successfully added to your garage!',
            'vehicle' => [
                'id' => $garageId,
                'make' => $validated['make'],
                'model' => $validated['model'],
                'year' => $validated['year'],
                'type' => $typeSlug,
                'trim' => $validated['trim'] ?? 'Standard Spec',
                'plate' => $validated['plate'] ?? 'Custom',
                'active' => $existingCount === 0,
            ]
        ], 201);
    }

    /**
     * Delete vehicle from garage
     */
    public function destroyVehicle(Request $request, $id)
    {
        $userId = $this->getUserId($request);

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        DB::table('user_garages')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle removed from your garage.'
        ]);
    }

    /**
     * Set primary vehicle in garage
     */
    public function setPrimaryVehicle(Request $request, $id)
    {
        $userId = $this->getUserId($request);

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        DB::table('user_garages')->where('user_id', $userId)->update(['is_primary' => false]);
        DB::table('user_garages')->where('id', $id)->where('user_id', $userId)->update(['is_primary' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Primary fitment vehicle updated!'
        ]);
    }

    /**
     * User's Wishlist Items
     */
    public function getWishlist(Request $request)
    {
        $userId = $this->getUserId($request);

        if (!$userId) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'count' => 0
            ]);
        }

        $items = DB::table('wishlists')
            ->join('products', 'wishlists.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('wishlists.user_id', $userId)
            ->whereNull('products.deleted_at')
            ->select(
                'products.*',
                'categories.name as category_name',
                'categories.slug as category_slug',
                'brands.name as brand_name',
                'brands.slug as brand_slug',
                'wishlists.id as wishlist_id',
                'wishlists.created_at as wishlisted_at'
            )
            ->orderBy('wishlists.id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'count' => count($items)
        ]);
    }

    /**
     * Toggle Wishlist for a Product
     */
    public function toggleWishlist(Request $request, $productId)
    {
        $userId = $this->getUserId($request);

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please sign in to save items to your wishlist.'
            ], 401);
        }

        $existing = DB::table('wishlists')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            DB::table('wishlists')->where('id', $existing->id)->delete();
            $wishlisted = false;
            $msg = 'Item removed from wishlist.';
        } else {
            DB::table('wishlists')->insert([
                'user_id' => $userId,
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $wishlisted = true;
            $msg = 'Item added to wishlist!';
        }

        $count = DB::table('wishlists')->where('user_id', $userId)->count();

        return response()->json([
            'status' => 'success',
            'wishlisted' => $wishlisted,
            'count' => $count,
            'message' => $msg
        ]);
    }

    /**
     * Remove item from Wishlist
     */
    public function removeWishlist(Request $request, $productId)
    {
        $userId = $this->getUserId($request);

        if ($userId) {
            DB::table('wishlists')
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();
        }

        $count = $userId ? DB::table('wishlists')->where('user_id', $userId)->count() : 0;

        return response()->json([
            'status' => 'success',
            'count' => $count,
            'message' => 'Removed from wishlist'
        ]);
    }

    /**
     * Dashboard Summary Metrics (Vehicles, Orders, Wishlists)
     */
    public function dashboardSummary(Request $request)
    {
        $userId = $this->getUserId($request);

        $vehiclesCount = 0;
        $ordersCount = 0;
        $wishlistsCount = 0;

        if ($userId) {
            $vehiclesCount = DB::table('user_garages')->where('user_id', $userId)->count();
            $ordersCount = DB::table('orders')->where('user_id', $userId)->whereNull('deleted_at')->count();
            $wishlistsCount = DB::table('wishlists')->where('user_id', $userId)->count();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'vehicles_count' => $vehiclesCount,
                'orders_count' => $ordersCount,
                'wishlists_count' => $wishlistsCount,
            ]
        ]);
    }
}


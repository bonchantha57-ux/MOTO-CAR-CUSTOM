<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // ==========================================
    // USER MANAGEMENT & PASSWORD RESET
    // ==========================================

    /**
     * List all registered users (B2C, B2B, Admins)
     */
    public function users(Request $request)
    {
        $query = DB::table('users')->select(
            'id', 'name', 'email', 'phone', 'role', 'user_mode', 'status', 'avatar', 'created_at', 'updated_at'
        );

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $users = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
            'total' => $users->count(),
        ]);
    }

    /**
     * Create new user from admin
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:customer,b2b_client,admin,super_admin',
            'password' => 'required|string|min:6',
            'avatar' => 'nullable|string|max:500',
        ]);

        $userId = DB::table('users')->insertGetId([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'user_mode' => $validated['role'] === 'b2b_client' ? 'b2b' : 'b2c',
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'avatar' => $validated['avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&h=200&q=80',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User account created successfully in database!',
            'user_id' => $userId,
        ], 201);
    }

    /**
     * Update user details (including profile picture)
     */
    public function updateUser(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$id}",
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:customer,b2b_client,admin,super_admin',
            'status' => 'nullable|in:active,suspended,banned',
            'avatar' => 'nullable|string|max:500',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'user_mode' => $validated['role'] === 'b2b_client' ? 'b2b' : 'b2c',
            'status' => $validated['status'] ?? 'active',
            'updated_at' => now(),
        ];

        if (array_key_exists('avatar', $validated) && $validated['avatar'] !== null) {
            $updateData['avatar'] = $validated['avatar'];
        }

        DB::table('users')->where('id', $id)->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'User details updated successfully in database!',
            'avatar' => $updateData['avatar'] ?? null,
        ]);
    }

    /**
     * Reset / Change Password for any User or Admin
     */
    public function resetPassword(Request $request, $id)
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        DB::table('users')->where('id', $id)->update([
            'password' => Hash::make($validated['new_password']),
            'updated_at' => now(),
        ]);

        // Revoke existing personal access tokens so user must log in with new password
        DB::table('personal_access_tokens')->where('tokenable_id', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Password for {$user->name} has been securely reset in database!",
        ]);
    }

    /**
     * Delete user from database
     */
    public function destroyUser($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        // Prevent deleting the main super admin
        if ($user->role === 'super_admin' && $id == 1) {
            return response()->json(['status' => 'error', 'message' => 'Root Super Admin cannot be deleted.'], 403);
        }

        DB::table('users')->where('id', $id)->delete();
        DB::table('personal_access_tokens')->where('tokenable_id', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => "User {$user->name} was completely deleted from database.",
        ]);
    }

    // ==========================================
    // PRODUCT CRUD & INVENTORY
    // ==========================================

    /**
     * Get single product for editing
     */
    public function getProduct($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found.'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $product]);
    }

    /**
     * Update existing product
     */
    public function updateProduct(Request $request, $id)
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
            'status' => 'nullable|in:active,inactive,draft',
        ]);

        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found.'], 404);
        }

        $b2bPrice = !empty($validated['b2b_price']) ? $validated['b2b_price'] : ($validated['b2c_price'] * 0.82);

        $updateData = [
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'name' => $validated['name'],
            'sku' => !empty($validated['sku']) ? $validated['sku'] : $product->sku,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'thumbnail' => $validated['thumbnail'] ?: $product->thumbnail,
            'b2c_price' => $validated['b2c_price'],
            'b2b_price' => $b2bPrice,
            'compare_at_price' => array_key_exists('compare_at_price', $validated) ? $validated['compare_at_price'] : $product->compare_at_price,
            'is_on_sale' => array_key_exists('is_on_sale', $validated) ? ($validated['is_on_sale'] ? 1 : 0) : $product->is_on_sale,
            'is_new_arrival' => array_key_exists('is_new_arrival', $validated) ? ($validated['is_new_arrival'] ? 1 : 0) : $product->is_new_arrival,
            'is_featured' => array_key_exists('is_featured', $validated) ? ($validated['is_featured'] ? 1 : 0) : $product->is_featured,
            'badge' => array_key_exists('badge', $validated) ? $validated['badge'] : $product->badge,
            'stock_quantity' => $validated['stock_quantity'] ?? $product->stock_quantity,
            'status' => $validated['status'] ?? 'active',
            'updated_at' => now(),
        ];

        DB::table('products')->where('id', $id)->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => "Product '{$validated['name']}' updated successfully in database!",
        ]);
    }

    /**
     * Delete product from database
     */
    public function destroyProduct($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found.'], 404);
        }

        DB::table('products')->where('id', $id)->delete();
        DB::table('product_images')->where('product_id', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Product '{$product->name}' has been permanently deleted from database.",
        ]);
    }

    // ==========================================
    // B2B GARAGES & PARTNERS
    // ==========================================

    public function garages()
    {
        $garages = DB::table('partner_garages')
            ->leftJoin('users', 'partner_garages.owner_user_id', '=', 'users.id')
            ->select(
                'partner_garages.*',
                'users.name as owner_name',
                'users.email as owner_email'
            )
            ->orderBy('partner_garages.id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $garages,
        ]);
    }

    public function updateGarageStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|in:active,pending,suspended']);
        DB::table('partner_garages')->where('id', $id)->update([
            'status' => $validated['status'],
            'updated_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'Garage partner status updated!']);
    }

    // ==========================================
    // ORDERS MANAGEMENT
    // ==========================================

    public function orders()
    {
        $orders = DB::table('orders')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
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

        return response()->json(['status' => 'success', 'data' => $orders]);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $updateData = [
            'status' => $validated['status'],
            'updated_at' => now(),
        ];

        if ($validated['status'] === 'delivered') {
            $updateData['delivered_at'] = now();
        } elseif ($validated['status'] === 'shipped') {
            $updateData['shipped_at'] = now();
        }

        DB::table('orders')->where('id', $id)->update($updateData);

        $order = DB::table('orders')->where('id', $id)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully!',
            'data' => $order
        ]);
    }

    /**
     * Delete order and its items from database
     */
    public function destroyOrder($id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found.'], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('order_items')->where('order_id', $id)->delete();
            DB::table('orders')->where('id', $id)->delete();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Order {$order->order_number} has been permanently deleted."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete order: ' . $e->getMessage()
            ], 500);
        }
    }
}


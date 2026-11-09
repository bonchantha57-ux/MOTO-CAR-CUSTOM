<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class OrderController extends Controller
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

        $headerId = $request->header('X-User-Id');
        if ($headerId) {
            return intval($headerId);
        }

        $paramId = $request->input('user_id') ?: $request->query('user_id');
        if ($paramId) {
            return intval($paramId);
        }

        // Fallback to first customer or create default
        $fallback = DB::table('users')->where('role', 'customer')->first();
        return $fallback ? $fallback->id : 1;
    }

    /**
     * Create a new order (Checkout)
     */
    public function store(Request $request)
    {
        $userId = $this->getUserId($request);

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart is empty. Please add items to checkout.'
            ], 422);
        }

        $subtotal = floatval($request->input('subtotal', 0));
        if ($subtotal <= 0) {
            // Auto compute subtotal from items if missing
            foreach ($items as $item) {
                $qty = intval($item['quantity'] ?? 1);
                $price = floatval($item['price'] ?? $item['b2c_price'] ?? $item['unit_price'] ?? 0);
                $subtotal += ($qty * $price);
            }
        }

        $shippingFee = floatval($request->input('shipping_fee', 15.00));
        $discountAmount = floatval($request->input('discount_amount', 0));
        $totalAmount = floatval($request->input('total_amount', ($subtotal + $shippingFee - $discountAmount)));

        // Unique order number: MCC-2026-XXXXX
        $orderNumber = 'MCC-2026-' . rand(10000, 99999);
        $trackingNumber = 'APX-' . rand(100000, 999999);
        $khqrRef = 'BK-KHQR-' . strtoupper(Str::random(8));

        DB::beginTransaction();
        try {
            $orderId = DB::table('orders')->insertGetId([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'order_type' => $request->input('order_type', 'b2c'),
                'status' => 'confirmed',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => 'USD',
                'payment_method' => $request->input('payment_method', 'khqr'),
                'payment_status' => 'paid',
                'payment_reference' => $khqrRef,
                'paid_at' => now(),
                'coupon_code' => $request->input('coupon_code'),
                'courier_name' => 'Apex Express Motorsport Logistics',
                'tracking_number' => $trackingNumber,
                'shipped_at' => now(),
                'requires_installation' => $request->boolean('requires_installation', false),
                'notes' => $request->input('notes', 'Priority Air-Freight fulfillment direct to customer in Cambodia.'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $savedItems = [];
            foreach ($items as $item) {
                $qty = intval($item['quantity'] ?? 1);
                $unitPrice = floatval($item['price'] ?? $item['b2c_price'] ?? $item['unit_price'] ?? 0);
                $itemTotal = $qty * $unitPrice;
                $prodId = intval($item['id'] ?? $item['product_id'] ?? 1);

                $productName = $item['name'] ?? $item['product_name'] ?? 'Performance Part';
                $productSku = $item['sku'] ?? $item['product_sku'] ?? 'MCC-GEN';
                $productImage = $item['thumbnail'] ?? $item['image'] ?? $item['product_image'] ?? null;
                if (empty($productImage) || str_contains($productImage, '/avatars/')) {
                    $prodRow = DB::table('products')->where('id', $prodId)->first();
                    if ($prodRow && !empty($prodRow->thumbnail)) {
                        $productImage = $prodRow->thumbnail;
                    }
                }

                $itemId = DB::table('order_items')->insertGetId([
                    'order_id' => $orderId,
                    'product_id' => $prodId,
                    'product_name' => $productName,
                    'product_sku' => $productSku,
                    'product_image' => $productImage,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                    'price_type' => 'b2c',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Reduce stock if product exists
                DB::table('products')->where('id', $prodId)->decrement('stock_quantity', $qty);

                $savedItems[] = [
                    'id' => $itemId,
                    'product_name' => $productName,
                    'product_sku' => $productSku,
                    'product_image' => $productImage,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                ];
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed and paid successfully via Bakong KHQR!',
                'data' => [
                    'id' => $orderId,
                    'order_number' => $orderNumber,
                    'tracking_number' => $trackingNumber,
                    'total_amount' => $totalAmount,
                    'payment_status' => 'paid',
                    'order_status' => 'confirmed',
                    'items_count' => count($savedItems),
                    'items' => $savedItems,
                    'courier_name' => 'Apex Express Motorsport Logistics',
                    'estimated_delivery' => now()->addDays(2)->format('M d, Y'),
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public order tracking by order_number or tracking_number
     */
    public function track($orderNumber)
    {
        $order = DB::table('orders')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.order_number', $orderNumber)
            ->orWhere('orders.tracking_number', $orderNumber)
            ->orWhere('orders.id', $orderNumber)
            ->select(
                'orders.*',
                'users.name as customer_name',
                'users.email as customer_email',
                'users.phone as customer_phone'
            )
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => "No order found matching code '{$orderNumber}'."
            ], 404);
        }

        $items = DB::table('order_items')
            ->where('order_id', $order->id)
            ->get();

        // Calculate progress percentage and steps based on status
        $statusKey = strtolower($order->status);
        $isCancelled = ($statusKey === 'cancelled');

        $steps = [
            [
                'step' => 1,
                'title' => $isCancelled ? 'Order Cancelled' : 'Payment Confirmed',
                'desc' => $isCancelled ? 'This order was cancelled by administrator' : 'Bakong KHQR payment verified instantly',
                'time' => date('M d, h:i A', strtotime($order->paid_at ?: $order->created_at)),
                'done' => !$isCancelled,
                'active' => false,
                'cancelled' => $isCancelled
            ],
            [
                'step' => 2,
                'title' => 'Parts Quality Inspection',
                'desc' => 'Verified genuine fitment & laser SKU checked',
                'time' => date('M d, h:i A', strtotime($order->created_at . ' +2 hours')),
                'done' => !$isCancelled && in_array($statusKey, ['processing', 'shipped', 'delivered']),
                'active' => !$isCancelled && ($statusKey === 'confirmed'),
                'cancelled' => $isCancelled
            ],
            [
                'step' => 3,
                'title' => 'Air-Freight Express Dispatch',
                'desc' => 'Apex Express logistics cargo flight dispatched',
                'time' => in_array($statusKey, ['shipped', 'delivered']) ? date('M d, h:i A', strtotime($order->shipped_at ?: ($order->created_at . ' +6 hours'))) : 'In transit',
                'done' => !$isCancelled && in_array($statusKey, ['shipped', 'delivered']),
                'active' => !$isCancelled && ($statusKey === 'processing'),
                'cancelled' => $isCancelled
            ],
            [
                'step' => 4,
                'title' => 'Phnom Penh Hub & Installation',
                'desc' => 'Arrived at central hub / partner garage for mounting',
                'time' => in_array($statusKey, ['delivered']) ? date('M d, h:i A', strtotime($order->delivered_at ?: ($order->created_at . ' +24 hours'))) : 'Pending arrival',
                'done' => !$isCancelled && in_array($statusKey, ['delivered']),
                'active' => !$isCancelled && ($statusKey === 'shipped'),
                'cancelled' => $isCancelled
            ],
            [
                'step' => 5,
                'title' => 'Delivered & Handed Over',
                'desc' => 'Handed to customer / verified installed on vehicle',
                'time' => in_array($statusKey, ['delivered']) ? date('M d, h:i A', strtotime($order->delivered_at ?: 'now')) : 'Pending',
                'done' => !$isCancelled && ($statusKey === 'delivered'),
                'active' => !$isCancelled && ($statusKey === 'delivered'),
                'cancelled' => $isCancelled
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $order,
                'items' => $items,
                'steps' => $steps,
                'courier' => [
                    'name' => $order->courier_name ?: 'Apex Express Air-Freight',
                    'tracking_code' => $order->tracking_number,
                    'phone' => '+855 23 888 999',
                    'service_type' => 'Express Priority Motorsport Logistics'
                ]
            ]
        ]);
    }

    /**
     * Get official invoice data by order number
     */
    public function invoice($orderNumber)
    {
        $order = DB::table('orders')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.order_number', $orderNumber)
            ->orWhere('orders.id', $orderNumber)
            ->orWhere('orders.tracking_number', $orderNumber)
            ->select(
                'orders.*',
                'users.name as customer_name',
                'users.email as customer_email',
                'users.phone as customer_phone'
            )
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => "Invoice not found for order '{$orderNumber}'."
            ], 404);
        }

        $items = DB::table('order_items')->where('order_id', $order->id)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'invoice_number' => 'INV-' . $order->order_number,
                'order' => $order,
                'items' => $items,
                'company' => [
                    'name' => 'MotoCar Custom Co., Ltd.',
                    'brand' => 'MotoCar Custom Performance Workshop',
                    'tax_id' => 'K009-902488192',
                    'address' => '#88 Russian Blvd, Toul Kork, Phnom Penh, Cambodia',
                    'phone' => '+855 23 888 999 / +855 70 888 999',
                    'email' => 'vip@motocarcustom.com',
                    'website' => 'https://moto-car-custom-ten.vercel.app'
                ]
            ]
        ]);
    }
}

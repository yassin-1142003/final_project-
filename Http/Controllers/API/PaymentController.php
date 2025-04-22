<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of the user's payments.
     */
    public function index(Request $request)
    {
        $payments = Payment::with(['listing'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $payments,
            'meta' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * Show a specific payment.
     */
    public function show(Request $request, Payment $payment)
    {
        // Ensure the user owns this payment
        if ($payment->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $payment->load('listing'),
        ]);
    }

    /**
     * Get all payment methods for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentMethods()
    {
        $paymentMethods = PaymentMethod::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paymentMethods
        ]);
    }

    /**
     * Add a new payment method.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPaymentMethod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:card,paypal,vodafone_cash,bank_transfer,orange_money,fawry',
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'details' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // If this is the default method, unset any existing default
        if ($request->is_default) {
            PaymentMethod::where('user_id', Auth::id())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        // Validate details based on payment type
        switch ($request->type) {
            case 'card':
                $detailsValidator = Validator::make($request->details ?? [], [
                    'last4' => 'required|string|size:4',
                    'brand' => 'required|string',
                    'exp_month' => 'required|integer|min:1|max:12',
                    'exp_year' => 'required|integer',
                ]);
                break;
                
            case 'paypal':
                $detailsValidator = Validator::make($request->details ?? [], [
                    'email' => 'required|email',
                ]);
                break;
                
            case 'vodafone_cash':
                $detailsValidator = Validator::make($request->details ?? [], [
                    'phone_number' => 'required|string|regex:/^(01)[0-9]{9}$/',
                ]);
                break;
                
            case 'bank_transfer':
                $detailsValidator = Validator::make($request->details ?? [], [
                    'bank_name' => 'required|string',
                    'account_number' => 'required|string',
                    'account_name' => 'required|string',
                ]);
                break;
                
            case 'orange_money':
                $detailsValidator = Validator::make($request->details ?? [], [
                    'phone_number' => 'required|string|regex:/^(01)[0-9]{9}$/',
                ]);
                break;
                
            case 'fawry':
                $detailsValidator = Validator::make($request->details ?? [], [
                    'reference_number' => 'required|string',
                ]);
                break;
                
            default:
                $detailsValidator = Validator::make([], []);
        }

        if ($detailsValidator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $detailsValidator->errors()
            ], 422);
        }

        $paymentMethod = PaymentMethod::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'name' => $request->name,
            'is_default' => $request->is_default ?? false,
            'details' => $request->details ?? [],
            'external_id' => $request->external_id ?? Str::random(24),
            'expires_at' => $request->expires_at
        ]);

        return response()->json([
            'success' => true,
            'data' => $paymentMethod,
            'message' => 'Payment method added successfully'
        ], 201);
    }

    /**
     * Remove a payment method.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removePaymentMethod($id)
    {
        $paymentMethod = PaymentMethod::find($id);
        
        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found'
            ], 404);
        }

        // Check if payment method belongs to user
        if ($paymentMethod->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        // If it's the default one, set another as default if available
        if ($paymentMethod->is_default) {
            $newDefault = PaymentMethod::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->first();
                
            if ($newDefault) {
                $newDefault->is_default = true;
                $newDefault->save();
            }
        }

        $paymentMethod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully'
        ]);
    }

    /**
     * Create a payment intent.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'booking_id' => 'sometimes|exists:bookings,id',
            'description' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $paymentMethod = PaymentMethod::find($request->payment_method_id);
        
        // Check if payment method belongs to user
        if ($paymentMethod->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized payment method'
            ], 403);
        }

        // Different logic based on payment gateway
        $paymentGateway = $paymentMethod->type;
        $intentData = [];
        
        switch ($paymentGateway) {
            case 'card':
                // Stripe-like payment intent
                $intentData = [
                    'client_secret' => 'pi_' . Str::random(24) . '_secret_' . Str::random(24),
                    'payment_method_options' => ['card' => ['request_three_d_secure' => 'automatic']]
                ];
                break;
                
            case 'paypal':
                // PayPal payment link
                $intentData = [
                    'redirect_url' => 'https://www.paypal.com/checkout?token=' . Str::random(24),
                    'return_url' => url('/api/payments/process') . '?source=paypal'
                ];
                break;
                
            case 'vodafone_cash':
                // Vodafone Cash reference
                $intentData = [
                    'reference_number' => strtoupper(Str::random(8)),
                    'phone_number' => '01555000000', // Example merchant number
                    'instructions' => 'Please transfer the amount to the provided Vodafone Cash number and use the reference number in your transaction.'
                ];
                break;
                
            case 'bank_transfer':
                // Bank transfer details
                $intentData = [
                    'reference_number' => 'TR' . strtoupper(Str::random(8)),
                    'bank_account' => [
                        'name' => 'Real Estate API Company',
                        'account_number' => '1234567890',
                        'bank_name' => 'Sample Bank'
                    ],
                    'instructions' => 'Please transfer the amount to the provided bank account and use the reference number in your transaction details.'
                ];
                break;
                
            case 'orange_money':
                // Orange Money reference
                $intentData = [
                    'reference_number' => strtoupper(Str::random(8)),
                    'phone_number' => '01555111111', // Example merchant number
                    'instructions' => 'Please transfer the amount to the provided Orange Money number and use the reference number in your transaction.'
                ];
                break;
                
            case 'fawry':
                // Fawry reference
                $intentData = [
                    'reference_number' => strtoupper(Str::random(10)),
                    'expiry_date' => now()->addDays(3)->toDateTimeString(),
                    'instructions' => 'Please pay using the Fawry reference number at any Fawry outlet or via the Fawry app.'
                ];
                break;
                
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported payment method'
                ], 400);
        }
        
        // Create the transaction record
        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'payment_method_id' => $request->payment_method_id,
            'booking_id' => $request->booking_id ?? null,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'status' => 'pending',
            'payment_gateway' => $paymentGateway,
            'transaction_id' => $intentData['reference_number'] ?? $intentData['client_secret'] ?? Str::random(24),
            'metadata' => $intentData,
            'description' => $request->description ?? 'Payment for services'
        ]);

        $paymentMethod->last_used_at = now();
        $paymentMethod->save();

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'payment_intent' => $intentData
            ],
            'message' => 'Payment intent created successfully'
        ]);
    }

    /**
     * Process a payment.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'payment_details' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction = Transaction::find($request->transaction_id);
        
        // Check if transaction belongs to user
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized transaction'
            ], 403);
        }

        // Only process if it's pending
        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is already processed'
            ], 400);
        }

        // Simulate payment processing logic based on gateway
        $success = true; // In real implementation, this would be determined by the payment gateway response
        
        if ($success) {
            $transaction->status = 'completed';
            $transaction->metadata = array_merge($transaction->metadata ?? [], [
                'completed_at' => now()->toDateTimeString(),
                'payment_details' => $request->payment_details
            ]);
            $transaction->save();
            
            // If there's a booking associated, update its status
            if ($transaction->booking_id) {
                $booking = Booking::find($transaction->booking_id);
                if ($booking) {
                    $booking->payment_status = 'paid';
                    $booking->save();
                }
            }
        } else {
            $transaction->status = 'failed';
            $transaction->metadata = array_merge($transaction->metadata ?? [], [
                'failed_at' => now()->toDateTimeString(),
                'failure_reason' => 'Payment processing failed'
            ]);
            $transaction->save();
        }

        return response()->json([
            'success' => $success,
            'data' => $transaction,
            'message' => $success ? 'Payment processed successfully' : 'Payment processing failed'
        ]);
    }

    /**
     * Get transaction history.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactions(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id());
        
        // Apply filters if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('payment_gateway')) {
            $query->where('payment_gateway', $request->payment_gateway);
        }
        
        if ($request->has('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }
        
        $transactions = $query->with('paymentMethod')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Get a single transaction.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransaction($id)
    {
        $transaction = Transaction::with('paymentMethod')->find($id);
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        // Check if transaction belongs to user
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Request a refund for a transaction.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestRefund(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction = Transaction::find($id);
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        // Check if transaction belongs to user
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        // Only completed transactions can be refunded
        if ($transaction->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed transactions can be refunded'
            ], 400);
        }

        // Update transaction status
        $transaction->status = 'refunded';
        $transaction->refund_reason = $request->reason;
        $transaction->metadata = array_merge($transaction->metadata ?? [], [
            'refunded_at' => now()->toDateTimeString(),
            'refund_reason' => $request->reason
        ]);
        $transaction->save();
        
        // If there's a booking associated, update its status
        if ($transaction->booking_id) {
            $booking = Booking::find($transaction->booking_id);
            if ($booking) {
                $booking->payment_status = 'refunded';
                $booking->save();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $transaction,
            'message' => 'Refund requested successfully'
        ]);
    }

    /**
     * Get available payment gateways.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableGateways()
    {
        $gateways = [
            [
                'id' => 'card',
                'name' => 'Credit/Debit Card',
                'icon' => 'credit-card',
                'description' => 'Pay securely with your credit or debit card',
                'supported_currencies' => ['USD', 'EUR', 'GBP', 'EGP'],
                'is_available' => true
            ],
            [
                'id' => 'paypal',
                'name' => 'PayPal',
                'icon' => 'paypal',
                'description' => 'Pay with your PayPal account',
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
                'is_available' => true
            ],
            [
                'id' => 'vodafone_cash',
                'name' => 'Vodafone Cash',
                'icon' => 'mobile-alt',
                'description' => 'Pay using your Vodafone Cash wallet',
                'supported_currencies' => ['EGP'],
                'is_available' => true
            ],
            [
                'id' => 'orange_money',
                'name' => 'Orange Money',
                'icon' => 'mobile-alt',
                'description' => 'Pay using your Orange Money wallet',
                'supported_currencies' => ['EGP'],
                'is_available' => true
            ],
            [
                'id' => 'fawry',
                'name' => 'Fawry',
                'icon' => 'store',
                'description' => 'Pay via Fawry outlets or the Fawry app',
                'supported_currencies' => ['EGP'],
                'is_available' => true
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'Bank Transfer',
                'icon' => 'university',
                'description' => 'Pay via bank transfer',
                'supported_currencies' => ['USD', 'EUR', 'GBP', 'EGP'],
                'is_available' => true
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $gateways
        ]);
    }
} 
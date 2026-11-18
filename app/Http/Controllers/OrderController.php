<?php

namespace App\Http\Controllers;

use App\DTOs\OrderWriteData;
use App\Enums\ItemCategoryEnum;
use App\Enums\OrderTypeEnum;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Requests\QuickCustomerRequest;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Services\OrderSequenceService;
use App\Services\OrderService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderSequenceService $sequence,
    ) {}

    public function index(Request $request): View
    {
        $query = Order::query()->with(['customer', 'user']);
        $isDailyOrdered = $this->applyOrderFilters($query, $request);

        if ($isDailyOrdered) {
            $query->orderByDesc('daily_number')->orderByDesc('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $rawPhone = $request->input('q');
        $phone = is_string($rawPhone) ? trim($rawPhone) : '';

        if ($phone === '') {
            $customers = Customer::select('id', 'name', 'phone_number', 'address')->latest('id')->limit(15)->get();

            return response()->json(['customers' => $customers]);
        }

        $customers = Customer::select('id', 'name', 'phone_number', 'address')
            ->where('phone_number', 'like', "{$phone}%")
            ->limit(15)
            ->get();

        return response()->json(['customers' => $customers]);
    }

    public function quickStoreCustomer(QuickCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = Customer::create([
            'name' => trim($validated['name']),
            'phone_number' => trim($validated['phone_number']),
            'address' => isset($validated['address']) && trim($validated['address']) !== '' ? trim($validated['address']) : null,
            'email' => null,
            'password' => null,
        ]);

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone_number' => $customer->phone_number,
                'address' => $customer->address,
            ],
        ], 201);
    }

    public function create(): View
    {
        $items = Item::orderBy('category')->get();
        $categories = ItemCategoryEnum::cases();
        $orderType = OrderTypeEnum::cases();
        $posConfig = $this->buildPosConfig($items, 'Place Order');
        $idempotencyKey = (string) Str::uuid();

        return view('admin.orders.create', compact('items', 'categories', 'orderType', 'posConfig', 'idempotencyKey'));
    }

    public function store(OrderCreateRequest $request): RedirectResponse
    {
        $idempotencyCacheKey = $this->resolveIdempotencyCacheKey($request);
        if ($idempotencyCacheKey !== null && ! Cache::add($idempotencyCacheKey, true, now()->addMinutes(2))) {
            return redirect()->route('orders.create');
        }

        $lock = Cache::lock($this->orderSubmitLockKey($request), 5);
        if (! $lock->get()) {
            return redirect()->route('orders.create');
        }

        try {
            $this->orderService->createOrder(OrderWriteData::fromAdminRequest($request));

            return redirect()->route('orders.create');
        } catch (Exception $exception) {
            if ($idempotencyCacheKey !== null) {
                Cache::forget($idempotencyCacheKey);
            }
            Log::error('order create error', ['exception' => $exception]);

            return back()->with('error', 'Failed to create order.');
        } finally {
            $lock->release();
        }
    }

    public function edit(Order $order): View
    {
        $order->loadMissing(['customer', 'items']);
        $categories = ItemCategoryEnum::cases();
        $orderType = OrderTypeEnum::cases();
        $items = Item::orderBy('category')->get();

        foreach ($order->items as $orderItem) {
            if (! $items->contains('id', $orderItem->id)) {
                $items->push($orderItem);
            }
        }

        $posConfig = $this->buildPosConfig($items, 'Update Order', $order);

        return view('admin.orders.edit', compact('order', 'orderType', 'items', 'categories', 'posConfig'));
    }

    public function update(OrderUpdateRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->updateOrder($order, OrderWriteData::fromAdminRequest($request));

            return redirect()->route('orders.index');
        } catch (Exception $exception) {
            Log::error('order update error', ['exception' => $exception]);

            return back()->with('error', 'Failed to update order.');
        }
    }

    public function delete(Order $order): RedirectResponse
    {
        try {
            $this->orderService->deleteOrder($order);

            return redirect()->route('orders.index');
        } catch (Exception $exception) {
            Log::error('delete order error', ['exception' => $exception]);

            return back()->with('error', 'Failed to delete order.');
        }
    }

    private function applyOrderFilters(Builder $query, Request $request): bool
    {
        $validatedDate = $this->parseDateFilter($request->input('date'));
        if ($validatedDate !== null) {
            $query->where('order_date', $validatedDate);
        }

        $isDailyNumberSearch = false;
        $rawSearch = $request->input('q');
        if (is_string($rawSearch) && ($searchTerm = trim($rawSearch)) !== '') {
            $cleanSearch = ltrim($searchTerm, '#');

            if (preg_match('/^[1-9]\d{0,5}$/', $cleanSearch) === 1) {
                if ($validatedDate === null) {
                    $query->where('order_date', $this->sequence->businessDate());
                }
                $query->where('daily_number', (int) $cleanSearch);
                $isDailyNumberSearch = true;
            } else {
                $query->where(function (Builder $q) use ($cleanSearch, $searchTerm): void {
                    $q->where('order_number', 'like', "{$cleanSearch}%")
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($searchTerm): void {
                            $customerQuery->where('phone_number', 'like', "{$searchTerm}%");
                        });
                });
            }
        }

        $rawType = $request->input('type');
        if (is_string($rawType) && $rawType !== '') {
            $query->where('type', $rawType);
        }

        return $validatedDate !== null || $isDailyNumberSearch;
    }

    private function parseDateFilter(mixed $rawDate): ?string
    {
        if (! is_string($rawDate)) {
            return null;
        }

        $trimmed = trim($rawDate);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed) !== 1) {
            return null;
        }

        $parsed = Carbon::createFromFormat('Y-m-d', $trimmed);
        if ($parsed === false || $parsed->format('Y-m-d') !== $trimmed) {
            return null;
        }

        return $trimmed;
    }

    /**
     * @param  Collection<int, Item>|\Illuminate\Database\Eloquent\Collection<int, Item>  $items
     * @return array<string, mixed>
     */
    private function buildPosConfig(
        $items,
        string $submitVerb,
        ?Order $order = null
    ): array {
        $originalSavedTicket = [];
        if ($order !== null) {
            foreach ($order->items as $orderItem) {
                $originalSavedTicket[(int) $orderItem->id] = (int) $orderItem->pivot->quantity;
            }
        }

        $initialTicket = [];
        $oldItems = old('items');
        if (is_array($oldItems)) {
            foreach ($oldItems as $oldId => $oldVal) {
                if (is_array($oldVal) && ($oldVal['selected'] ?? null) === 'on' && isset($oldVal['quantity']) && is_scalar($oldVal['quantity'])) {
                    $initialTicket[(int) $oldId] = max(1, (int) $oldVal['quantity']);
                }
            }
        } elseif ($order !== null) {
            $initialTicket = $originalSavedTicket;
        }

        $rawOldCustId = old('customer_id');
        $selectedCustomerModel = null;
        if (is_scalar($rawOldCustId) && (string) $rawOldCustId !== '') {
            $selectedCustomerModel = Customer::select('id', 'name', 'phone_number', 'address')
                ->find((int) $rawOldCustId);
        } elseif ($order !== null) {
            $selectedCustomerModel = $order->customer;
        }

        $initialCustomer = null;
        if ($selectedCustomerModel) {
            $initialCustomer = [
                'id' => $selectedCustomerModel->id,
                'name' => $selectedCustomerModel->name,
                'phone_number' => $selectedCustomerModel->phone_number,
                'address' => $selectedCustomerModel->address,
            ];
        }

        $itemsPayload = [];
        foreach ($items as $item) {
            $catVal = is_object($item->category) ? $item->category->value : $item->category;
            $itemsPayload[$item->id] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'category' => strtolower((string) $catVal),
            ];
        }

        return [
            'submitVerb' => $submitVerb,
            'items' => $itemsPayload,
            'initialTicket' => $initialTicket,
            'originalSavedTicket' => $originalSavedTicket ?: (object) [],
            'initialCustomer' => $initialCustomer,
            'searchCustomersUrl' => route('orders.search-customers'),
            'quickCustomerUrl' => route('orders.quick-customer'),
        ];
    }

    private function resolveIdempotencyCacheKey(OrderCreateRequest $request): ?string
    {
        $rawKey = $request->validated('idempotency_key');
        $key = is_string($rawKey) ? trim($rawKey) : '';
        if ($key === '') {
            return null;
        }

        return "order_idempotency:{$request->user()->restaurant_id}:{$request->user()->id}:{$key}";
    }

    private function orderSubmitLockKey(OrderCreateRequest $request): string
    {
        return "order_submit_lock:{$request->user()->restaurant_id}:{$request->user()->id}";
    }
}

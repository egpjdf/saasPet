# Coding Standards - Saaspet (Laravel 13)

> **Obrigatório para todo código.** PHPStan Level 5 + Laravel Pint = lei.

---

## 📏 PHP & Laravel Versions

- **PHP:** 8.3+ (strict types, readonly, enums, attributes)
- **Laravel:** 13.x (PHP Attributes, AI SDK, JSON:API, Queue Routing)

---

## 🧱 Estrutura de Arquivos

### Namespace & Pastas
```
app/
├── Models/                 # Eloquent Models
│   ├── Concerns/           # Traits reutilizáveis
│   └── [Domain]/           # Agrupamento por domínio (opcional)
├── Http/
│   ├── Controllers/
│   │   ├── Api/            # JSON:API Resources
│   │   ├── Platform/       # /admin
│   │   ├── Organization/   # /{org}/
│   │   └── Workspace/      # /{org}/{ws}/
│   ├── Middleware/
│   ├── Requests/           # Form Requests
│   └── Resources/          # JSON:API / API Resources
├── Jobs/
│   └── Concerns/           # Traits para Jobs
├── Services/
│   ├── [Domain]/           # Services por domínio
│   └── Tenant/             # TenantContext, Resolver
├── Policies/
├── Events/
├── Listeners/
├── DTOs/                   # Data Transfer Objects (readonly classes)
├── Enums/                  # PHP 8.1+ Enums
├── Exceptions/
├── Rules/                  # Validation Rules customizadas
└── Providers/
```

### Naming Conventions

| Elemento | Padrão | Exemplo |
|----------|--------|---------|
| **Classes** | PascalCase | `OrderService`, `CreateOrderDTO` |
| **Interfaces** | PascalCase + `Interface` | `PaymentGatewayInterface` |
| **Traits** | PascalCase + `Trait` / `Concern` | `BelongsToOrganization`, `HasTenantContext` |
| **Enums** | PascalCase singular | `OrderStatus`, `PlanInterval` |
| **Methods** | camelCase | `createOrder`, `getOrganizationId` |
| **Properties** | camelCase | `$organizationId`, `$createdAt` |
| **Constants** | UPPER_SNAKE_CASE | `MAX_RETRY_ATTEMPTS` |
| **Variables** | camelCase | `$order`, `$userList` |
| **Database Tables** | snake_case plural | `orders`, `order_items` |
| **Columns** | snake_case | `organization_id`, `created_at` |
| **Routes** | kebab-case | `orders.index`, `organization.settings` |
| **Config Keys** | snake_case | `billing.plans.starter.price_monthly` |
| **Env Vars** | UPPER_SNAKE_CASE | `STRIPE_SECRET_KEY` |
| **Views/Pages** | PascalCase (Vue) / kebab-case (Blade) | `OrderList.vue`, `order-show.blade.php` |

---

## 🔤 Tipagem Estrita (Obrigatório)

### Declare Strict Types
```php
<?php

declare(strict_types=1);

namespace App\Services;

class OrderService
{
    // ...
}
```

### Return Types + Parameter Types
```php
// SEMPRE
public function getOrders(string $organizationId, string $workspaceId, array $filters = []): Collection
{
    // ...
}

public function calculateTotal(Collection $items): float
{
    // ...
}

// NUNCA
public function getOrders($orgId, $wsId, $filters = []) { ... }
```

### Nullable Types Explícitos
```php
// SEMPRE nullable quando pode ser null
public function findOrder(string $id): ?Order
{
    return Order::find($id);
}

// Union types (PHP 8.0+)
public function process(mixed $input): Order|false { ... }

// never para exceções
public function validateOrThrow(array $data): void { ... }
```

### Readonly Classes (DTOs, Value Objects)
```php
// App\DTOs\CreateOrderDTO.php
readonly class CreateOrderDTO
{
    public function __construct(
        public string $organizationId,
        public string $workspaceId,
        public string $customerId,
        public array $items,
        public ?string $notes = null,
    ) {}
}
```

### Enums (PHP 8.1+) para Valores Fixos
```php
// App\Enums\OrderStatus.php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Confirmed => 'Confirmado',
            self::Shipped => 'Enviado',
            self::Delivered => 'Entregue',
            self::Cancelled => 'Cancelado',
        };
    }
    
    public static function active(): array
    {
        return [self::Pending, self::Confirmed, self::Shipped];
    }
}
```

---

## 🏷️ PHP Attributes (Laravel 13 - Obrigatório)

### Controllers
```php
#[Middleware('auth')]
#[Middleware('tenant.access')]
class OrderController extends Controller
{
    #[Authorize('view', Order::class)]
    public function show(Order $order): OrderResource
    {
        return OrderResource::make($order);
    }
    
    #[Authorize('update', Order::class)]
    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        $order->update($request->validated());
        return OrderResource::make($order);
    }
}
```

### Jobs
```php
#[Tries(3)]
#[Backoff([10, 60, 300])]
#[Timeout(60)]
#[FailOnTimeout]
class ProcessOrder implements ShouldQueue
{
    use HasTenantContext;
    
    public function __construct(
        public readonly string $organizationId,
        public readonly string $workspaceId,
        public readonly int $orderId,
    ) {}
    
    public function handle(OrderService $service): void
    {
        $service->process($this->orderId);
    }
}
```

### Models (Casts, Observers)
```php
#[Observers([OrderObserver::class])]
class Order extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, UsesUuids, HasFactory;
    
    #[Casts(OrderStatus::class)]
    public string $status = OrderStatus::Pending->value;
}
```

---

## 📦 Service Layer Pattern

### Interface + Implementation
```php
// App\Contracts\OrderServiceInterface.php
interface OrderServiceInterface
{
    public function create(CreateOrderDTO $dto): Order;
    public function update(string $id, UpdateOrderDTO $dto): Order;
    public function cancel(string $id, string $reason): void;
    public function getById(string $id): ?Order;
    public function list(ListOrdersDTO $dto): LengthAwarePaginator;
}

// App\Services\OrderService.php
class OrderService implements OrderServiceInterface
{
    public function __construct(
        private OrderRepository $repository,
        private EventDispatcher $events,
        private LimitEnforcer $limits,
        private NotificationEngineer $notifications,
    ) {}
    
    public function create(CreateOrderDTO $dto): Order
    {
        $this->limits->enforce($dto->organizationId, 'orders_monthly');
        
        $order = $this->repository->create($dto);
        
        $this->events->dispatch(new OrderCreated($order));
        
        return $order;
    }
}
```

### Repository Pattern (Para queries complexas)
```php
// App\Repositories\OrderRepository.php
class OrderRepository
{
    public function __construct(
        private Order $model,
    ) {}
    
    public function create(CreateOrderDTO $dto): Order
    {
        return $this->model->create([
            'organization_id' => $dto->organizationId,
            'workspace_id' => $dto->workspaceId,
            'user_id' => auth()->id(),
            'customer_id' => $dto->customerId,
            'items' => $dto->items,
            'notes' => $dto->notes,
            'status' => OrderStatus::Pending,
        ]);
    }
    
    public function findById(string $id): ?Order
    {
        return $this->model
            ->with(['customer', 'items.product'])
            ->find($id);
    }
    
    public function list(ListOrdersDTO $dto): LengthAwarePaginator
    {
        return $this->model
            ->with(['customer'])
            ->when($dto->status, fn($q, $s) => $q->where('status', $s))
            ->when($dto->customerId, fn($q, $id) => $q->where('customer_id', $id))
            ->latest()
            ->paginate($dto->perPage ?? 15);
    }
}
```

---

## 🎯 Form Requests (Validation)

```php
// App\Http\Requests\StoreOrderRequest.php
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }
    
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'items.*.product_id.exists' => 'Produto não encontrado neste workspace.',
            'items.*.quantity.max' => 'Quantidade máxima por item é 999.',
        ];
    }
    
    protected function prepareForValidation(): void
    {
        $this->merge([
            'organization_id' => app('tenant')->organizationId(),
            'workspace_id' => app('tenant')->workspaceId(),
        ]);
    }
}
```

---

## 📡 API Resources (JSON:API - Laravel 13)

```php
// App\Http\Resources\OrderResource.php
class OrderResource extends JsonApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status->label(),
            'total' => $this->total,
            'currency' => 'BRL',
            'placed_at' => $this->created_at?->toISOString(),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'links' => [
                'self' => route('api.orders.show', $this->id),
                'customer' => route('api.customers.show', $this->customer_id),
            ],
        ];
    }
    
    public function meta(): array
    {
        return [
            'organization_id' => $this->organization_id,
            'workspace_id' => $this->workspace_id,
        ];
    }
}
```

---

## 🧪 Test Patterns

### Pest Syntax (Obrigatório)
```php
// tests/Feature/OrdersTest.php
uses()->group('tenant');

test('organization admin can list orders', function () {
    $org = Organization::factory()->create();
    $ws = Workspace::factory()->for($org)->create();
    $user = User::factory()->for($ws)->asAdmin()->create();
    $orders = Order::factory()->count(3)->for($ws)->create();
    
    actingAs($user)
        ->getJson(route('api.orders.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('cross-organization access returns 403', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $wsB = Workspace::factory()->for($orgB)->create();
    $userA = User::factory()->for($orgA)->create();
    $orderB = Order::factory()->for($wsB)->create();
    
    actingAs($userA)
        ->getJson(route('api.orders.show', $orderB))
        ->assertForbidden();
});

test('order creation validates tenant limits', function () {
    $org = Organization::factory()->onPlan('free')->create(); // limit 100 orders/month
    $ws = Workspace::factory()->for($org)->create();
    $user = User::factory()->for($ws)->create();
    
    Order::factory()->count(100)->for($ws)->create();
    
    actingAs($user)
        ->postJson(route('api.orders.store'), validOrderData())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['limit']);
});
```

### Factories Tenant-Aware
```php
// database/factories/OrderFactory.php
class OrderFactory extends Factory
{
    protected $model = Order::class;
    
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'workspace_id' => fn(array $attrs) => Workspace::factory()
                ->for(Organization::find($attrs['organization_id']))
                ->create()->id,
            'user_id' => fn(array $attrs) => User::factory()
                ->for(Workspace::find($attrs['workspace_id']))
                ->create()->id,
            'number' => 'ORD-' . fake()->unique()->numerify('######'),
            'status' => OrderStatus::Pending,
            'total' => fake()->randomFloat(2, 10, 10000),
            'items' => [],
        ];
    }
    
    public function forWorkspace(Workspace $ws): static
    {
        return $this->state([
            'workspace_id' => $ws->id,
            'organization_id' => $ws->organization_id,
        ]);
    }
}
```

---

## 🚫 Anti-Patterns (Proibidos)

| Anti-Pattern | Correto |
|--------------|---------|
| `Order::withoutGlobalScopes()->get()` no controller | Policy + Global Scope automático |
| `where('organization_id', $request->org_id)` | Global Scope + Tenant Context |
| `if ($user->isAdmin())` no controller | `#[Authorize('admin', ...)]` ou Policy |
| Logic de negócio no Controller | Service Class |
| `any` / `mixed` sem justificativa | Tipos concretos |
| `dd()` / `ray()` no código committado | Logger + Tests |
| Migration sem `down()` testado | `down()` reversível |
| Secrets em `.env.example` com valores reais | Placeholders: `${VAR:-}` |
| N+1 queries | `with()` / `load()` / `chunkById()` |
| `try/catch` genérico exceções | Exceções tipadas + `report()` |

---

## 📋 Code Review Checklist

```markdown
## Code Review

### Arquitetura
- [ ] Service layer usado (não logic no controller)
- [ ] Repository para queries complexas
- [ ] DTOs para input/output de services
- [ ] Events para side effects

### Multi-Tenancy
- [ ] Model tem traits BelongsToOrg + BelongsToWs
- [ ] Migration tem org_id + ws_id + FKs + índices
- [ ] RLS policy no SQL
- [ ] Policy valida posse (org + ws)
- [ ] Job implementa HasTenantContext
- [ ] Cache keys com prefixo tenant

### Tipagem
- [ ] `declare(strict_types=1)`
- [ ] Return types em todos métodos
- [ ] Parameter types em todos métodos
- [ ] Nullable explícito (`?Type`)
- [ ] Enums para valores fixos
- [ ] Readonly classes para DTOs

### Laravel 13 Features
- [ ] PHP Attributes: Middleware, Authorize, Tries, Backoff
- [ ] JSON:API Resources para APIs
- [ ] Queue::route() para jobs

### Testes
- [ ] Feature test para happy path
- [ ] Tenant isolation test (cross-org 403)
- [ ] Edge cases cobertos
- [ ] Factory tenant-aware

### Segurança
- [ ] Form Request com authorize()
- [ ] Rate limiting configurado
- [ ] Audit log em ações sensíveis
- [ ] Sem secrets no código

### Documentação
- [ ] PHPDoc em métodos públicos complexos
- [ ] ADR se decisão arquitetural nova
- [ ] OpenAPI atualizado (se API)
```

---

**Versão:** 1.0  
**Enforcement:** PHPStan Level 5 + Laravel Pint + Code Review  
**Owner:** CTO
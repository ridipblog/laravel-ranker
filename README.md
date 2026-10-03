# Laravel Ranker ⚡

[![Latest Version](https://img.shields.io/packagist/v/hwsc/laravel-ranker.svg?style=flat-square)](https://packagist.org/packages/hwsc/laravel-ranker)
[![Total Downloads](https://img.shields.io/packagist/dt/hwsc/laravel-ranker.svg?style=flat-square)](https://packagist.org/packages/hwsc/laravel-ranker)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

A high-performance, developer-friendly drag-and-drop ordering and ranking package for Laravel Eloquent models.

---

## ✨ Features

- 🚀 **High Performance**: Batch updates reordering sequences using a single optimized `UPDATE ... CASE WHEN` SQL query.
- 🎯 **Seamless Eloquent Integration**: Automatically assigns sequential order indices on model creation.
- 📂 **Multi-Tenancy & Grouping**: Group sequences per category, user, project, or workspace using `group_by`.
- 🔄 **Directional Helpers**: Built-in methods to `moveOrderUp()`, `moveOrderDown()`, `moveToStart()`, `moveToEnd()`, and `swapOrderWith()`.
- 🌐 **Ready-to-use API Endpoint**: Includes `RankerController`, form request validation, and `Route::ranker()` macro.
- 🛠️ **Universal Frontend Compatibility**: Plug and play with **SortableJS**, **Alpine.js**, **Vue.js**, **React**, or vanilla HTML5 Drag & Drop.

---

## 📦 Installation

Install the package via Composer:

```bash
composer require hwsc/laravel-ranker
```

Publish the package configuration file (optional):

```bash
php artisan vendor:publish --tag="ranker-config"
```

---

## ⚙️ Configuration

The published config file (`config/ranker.php`) allows you to customize defaults:

```php
return [
    // Default column used to store the sort index
    'order_column_name' => 'order_column',

    // Starting index for the sequence (1 or 0)
    'start_order' => 1,

    // Auto-calculate order on creation
    'sort_when_creating' => true,

    // Whitelist of models allowed in universal reorder endpoint
    'allowed_models' => [
        \App\Models\Project::class,
        \App\Models\Task::class,
    ],
];
```

---

## 🚀 Quick Start

### 1. Prepare Database Migration

Add an integer column (e.g. `order_column`) to your table:

```php
Schema::table('tasks', function (Blueprint $table) {
    $table->integer('order_column')->nullable()->index();
});
```

### 2. Prepare Your Eloquent Model

Implement `Ranker\Contracts\Sortable` and use the `Ranker\Traits\HasSortableOrder` trait:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ranker\Contracts\Sortable;
use Ranker\Traits\HasSortableOrder;

class Task extends Model implements Sortable
{
    use HasSortableOrder;

    protected $fillable = ['title', 'project_id', 'order_column'];

    /**
     * Optional custom configuration per model:
     */
    public array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
        'group_by' => ['project_id'], // Group orders per project
    ];
}
```

---

## 📖 Usage & Examples

### Querying Ordered Records

```php
// Fetch tasks sorted in ascending order (default)
$tasks = Task::ordered()->get();

// Fetch tasks sorted in descending order
$tasks = Task::orderedDesc()->get();
```

### Batch Reordering (Drag-and-Drop)

Pass an array of primary keys in the newly sorted sequence. Ranker updates all positions in a **single atomic query**:

```php
use App\Models\Task;

// Order IDs: 10 becomes order 1, 4 becomes order 2, 8 becomes order 3
Task::setNewOrder([10, 4, 8]);

// Or specify custom start index:
Task::setNewOrder([10, 4, 8], startOrder: 0);
```

### Individual Position Manipulation

```php
$task = Task::find(4);

// Swap position with another task
$task->swapOrderWith($otherTask);

// Move one step up or down
$task->moveOrderUp();
$task->moveOrderDown();

// Move to the beginning or end of the list
$task->moveToStart();
$task->moveToEnd();
```

---

## 🌐 API & Drag-and-Drop Frontend Integration

### Registering the Reorder Route

Add the `Route::ranker()` macro to your `routes/api.php` or `routes/web.php`:

```php
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::ranker('tasks/reorder');
});
```

### Frontend Example with SortableJS

```html
<ul id="task-list">
    <li data-id="1">Task A</li>
    <li data-id="2">Task B</li>
    <li data-id="3">Task C</li>
</ul>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    const el = document.getElementById('task-list');
    Sortable.create(el, {
        animation: 150,
        onEnd: function () {
            const itemIds = Array.from(el.children).map(item => item.dataset.id);

            fetch('/api/tasks/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    model: 'App\\Models\\Task',
                    items: itemIds,
                }),
            });
        }
    });
</script>
```

---

## 🧪 Testing

Run test suite via PHPUnit:

```bash
composer test
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

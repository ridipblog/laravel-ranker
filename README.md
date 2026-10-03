# Laravel Ranker ⚡

[![Latest Version](https://img.shields.io/packagist/v/debug404/laravel-ranker.svg?style=flat-square)](https://packagist.org/packages/debug404/laravel-ranker)
[![Total Downloads](https://img.shields.io/packagist/dt/debug404/laravel-ranker.svg?style=flat-square)](https://packagist.org/packages/debug404/laravel-ranker)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

A high-performance, developer-friendly drag-and-drop ordering, ranking, and Kanban management package for Laravel Eloquent models.

---

## ✨ Features

- 🚀 **High Performance Batching**: Updates complete reordering sequences in a single atomic `UPDATE ... CASE WHEN` SQL query.
- 🗂️ **Cross-Group & Kanban Boards**: Move records across columns/categories (`moveToGroup()`) and automatically re-index both origin and target partitions.
- 🎯 **Seamless Eloquent Auto-Ordering**: Automatically calculates and assigns sequence numbers upon model creation.
- ⚡ **Gap Normalization & CLI Repair**: Eliminate fragmentation gaps (e.g. `1, 5, 9` ➔ `1, 2, 3`) via `Model::normalizeOrder()`, `normalize_on_delete`, and `php artisan ranker:normalize`.
- 📢 **Dedicated Eloquent Events**: Dispatches `Ranker\Events\OrderChanged` and `Ranker\Events\ItemMoved` for audit logs, webhooks, and cache invalidation.
- 🔄 **Directional & Positional Helpers**: Built-in `moveOrderUp()`, `moveOrderDown()`, `moveToPosition($pos)`, `moveToStart()`, `moveToEnd()`, and `swapOrderWith()`.
- 🌐 **Ready-to-use API Endpoint**: Includes `RankerController`, form request validation, and `Route::ranker()` macro.

---

## 📦 Installation

Install the package via Composer:

```bash
composer require debug404/laravel-ranker
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

    // Auto-close gaps when records are deleted
    'normalize_on_delete' => false,

    // Whitelist of models allowed in universal reorder endpoint
    'allowed_models' => [
        \App\Models\Project::class,
        \App\Models\Task::class,
    ],
];
```

---

## 🚀 Quick Start

### 1. Database Migration

Add an integer column (e.g. `order_column`) to your table:

```php
Schema::table('tasks', function (Blueprint $table) {
    $table->integer('order_column')->nullable()->index();
});
```

### 2. Prepare Your Model

Implement `Ranker\Contracts\Sortable` and use the `Ranker\Traits\HasSortableOrder` trait:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ranker\Contracts\Sortable;
use Ranker\Traits\HasSortableOrder;

class Task extends Model implements Sortable
{
    use HasSortableOrder;

    protected $fillable = ['title', 'status', 'project_id', 'order_column'];

    /**
     * Optional custom configuration per model:
     */
    public array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
        'normalize_on_delete' => true,
        'group_by' => ['project_id', 'status'], // Multi-tenant / Kanban grouping
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

Pass an array of primary keys in the newly sorted sequence:

```php
use App\Models\Task;

// Reorders IDs: 10 becomes order 1, 4 becomes order 2, 8 becomes order 3
Task::setNewOrder([10, 4, 8]);
```

### 🗂️ Kanban Cross-Column / Cross-Group Moving

Move an item to a different category/status and insert it at a specific position:

```php
$task = Task::find(12);

// Move from 'todo' to 'in_progress' at position 1 (shifts other in_progress items down)
$task->moveToGroup(['status' => 'in_progress'], newPosition: 1);
```

### Position Manipulation & Normalization

```php
$task = Task::find(4);

// Move to exact position (e.g., position 3)
$task->moveToPosition(3);

// Directional helpers
$task->moveOrderUp();
$task->moveOrderDown();
$task->moveToStart();
$task->moveToEnd();
$task->swapOrderWith($otherTask);

// Compact gaps in order sequence (e.g. 1, 4, 9 -> 1, 2, 3)
Task::normalizeOrder(['project_id' => 5]);
```

---

## 🛠️ CLI Management Commands

### Normalize Sequence Gaps
Repair and normalize order gaps across all records or scoped groups:

```bash
# Normalize global orders for a model
php artisan ranker:normalize "App\Models\Task"

# Normalize orders partitioned by grouping column
php artisan ranker:normalize "App\Models\Task" --group=project_id
```

---

## 📢 Events & Webhooks

Laravel Ranker dispatches events for listening to sequence changes:

| Event | Dispatched When | Payload Properties |
| :--- | :--- | :--- |
| `Ranker\Events\OrderChanged` | Batch `setNewOrder()` finishes | `$event->modelClass`, `$event->ids`, `$event->scope` |
| `Ranker\Events\ItemMoved` | Single item moves or swaps | `$event->model`, `$event->previousPosition`, `$event->newPosition` |

---

## 🌐 API Endpoint Integration

Register the `Route::ranker()` macro in `routes/web.php` or `routes/api.php`:

```php
Route::ranker('tasks/reorder')->name('tasks.reorder');
```

### Drag-and-Drop Payload:
```json
{
    "model": "App\\Models\\Task",
    "items": [4, 1, 9]
}
```

### Kanban Cross-Group Move Payload:
```json
{
    "model": "App\\Models\\Task",
    "items": [4],
    "target_group": { "status": "done" },
    "position": 1
}
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

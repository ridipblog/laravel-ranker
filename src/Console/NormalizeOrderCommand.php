<?php

namespace Ranker\Console;

use Illuminate\Console\Command;
use Ranker\Contracts\Sortable;

class NormalizeOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ranker:normalize 
                            {model : The fully qualified class name of the Sortable model}
                            {--group= : Optional column name to group normalization by (e.g. project_id)}';

    /**
     * The console command description.
     */
    protected $description = 'Normalize order sequence gaps (1, 2, 3...) for a Sortable model';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelClass = $this->argument('model');

        if (!class_exists($modelClass)) {
            $this->error("Class [{$modelClass}] not found.");
            return self::FAILURE;
        }

        if (!is_subclass_of($modelClass, Sortable::class)) {
            $this->error("Class [{$modelClass}] does not implement [" . Sortable::class . "].");
            return self::FAILURE;
        }

        $groupBy = $this->option('group');

        if ($groupBy) {
            $this->info("Normalizing orders for [{$modelClass}] grouped by [{$groupBy}]...");

            $distinctGroups = $modelClass::query()
                ->distinct()
                ->pluck($groupBy);

            $this->output->progressStart($distinctGroups->count());

            foreach ($distinctGroups as $groupId) {
                if (method_exists($modelClass, 'normalizeOrder')) {
                    $modelClass::normalizeOrder([$groupBy => $groupId]);
                }
                $this->output->progressAdvance();
            }

            $this->output->progressFinish();
        } else {
            $this->info("Normalizing global orders for [{$modelClass}]...");

            if (method_exists($modelClass, 'normalizeOrder')) {
                $modelClass::normalizeOrder();
            }
        }

        $this->info("✅ Order sequences normalized successfully for [{$modelClass}].");

        return self::SUCCESS;
    }
}

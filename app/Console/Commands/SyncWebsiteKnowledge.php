<?php

namespace App\Console\Commands;

use App\Services\AI\WebsiteKnowledgeRetriever;
use Illuminate\Console\Command;

class SyncWebsiteKnowledge extends Command
{
    protected $signature = 'catatin:sync-website-knowledge {--force : Rebuild all website knowledge embeddings}';

    protected $description = 'Synchronize the public CatatIn website knowledge documentation';

    public function handle(WebsiteKnowledgeRetriever $retriever): int
    {
        $result = $retriever->synchronize((bool) $this->option('force'));
        $this->info("Website knowledge synchronized: {$result['synced']} updated, {$result['unchanged']} unchanged, {$result['removed']} removed.");

        return self::SUCCESS;
    }
}

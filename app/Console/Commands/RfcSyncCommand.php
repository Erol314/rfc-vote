<?php

namespace App\Console\Commands;

use App\Models\Rfc;
use App\Support\ExternalsRssFeed;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RfcSyncCommand extends Command
{
    protected $signature = 'rfc:sync';

    protected $description = 'Sync RFCs from Externals RSS feed';

    public function __construct(private readonly ExternalsRssFeed $feed)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $rss = $this->feed->load();

        foreach ($rss->item as $item) {
            if (! Str::startsWith((string) ($item->title ?? null), ['[VOTE]'])) {
                continue;
            }

            $rfc = Rfc::updateOrCreate(['title' => $item->title]);

            $this->info("✅  {$rfc->title}");

            if (! $rfc->url) {
                preg_match('/\"(https:\/\/wiki\.php\.net\/rfc\/(.*))\"/', $item->description, $matches);
                $url = $matches[1] ?? null;
                $rfc->url = $url;
                $rfc->save();
                $this->comment("\t{$url}");
            }
        }

        return self::SUCCESS;
    }
}

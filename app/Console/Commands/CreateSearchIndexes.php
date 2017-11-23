<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateSearchIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dn:create-search-indexes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the Elastic Search Search Indexes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = \Plastic::getClient();
        
        $this->info("Creating Indicies");
        
        $client->indices()->create([
            'index' => 'people'
        ]);
        
        $client->indices()->create([
            'index' => 'groups'
        ]);
        
        $client->indices()->create([
            'index' => 'images'
        ]);
        
        $this->info("Indicies Created");
    }
}

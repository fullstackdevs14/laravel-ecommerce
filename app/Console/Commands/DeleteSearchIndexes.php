<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeleteSearchIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dn:delete-search-indexes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the Elastic Search Search Indexes';

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
        
        if(!$this->confirm("Are you SURE you want to delete the indicies?", false)) {
            $this->info("Aborting Deleting of Indicies");
        }
        
        $this->info("Deleting Indicies");
        
        $client->indices()->delete([
            'index' => 'people'
        ]);
        
        $client->indices()->delete([
            'index' => 'groups'
        ]);
        
        $client->indices()->delete([
            'index' => 'images'
        ]);
        
        $this->info("Indicies Deleted");
    }
}

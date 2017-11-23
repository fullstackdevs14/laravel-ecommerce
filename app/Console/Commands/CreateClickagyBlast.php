<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateClickagyBlast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clickagy:create {json-input}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Clickagy E-mail template';

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
        $jsonFile = realpath($this->argument('json-input'));

        $json = @json_decode(@file_get_contents($jsonFile), true);
        
        if(!$json) {
            $this->error("Failed to read and process input JSON");
            $this->error("JSON Error: "  . json_last_error_msg());
            exit(1);
        }
        
        $required = ['name', 'content_view', 'from', 'reply_to', 'subject', 'required_vars'];
        
        foreach($required as $requiredField) {
            if(!isset($json[$requiredField])) {
                $this->error("You must provide the '$requiredField' field.");
                exit(1);
            }
        }
        
        if(!is_array($json['required_vars'])) {
            $this->error("The 'required_vars' field must be an array");
            exit(1);
        }
        
        $clickagy = app()->make('App\Mail\ClickagyTransport');
        
        $requestOptions = [
            'form_params' => [
                'name' => $json['name'],
                'content' => view("clickagy::{$json['content_view']}")->__toString(),
                'from' => $json['from'],
                'reply_to' => $json['reply_to'],
                'subject' => $json['subject'],
                'required_vars' => json_encode($json['required_vars'])
            ]
        ];
        
        $response = $clickagy->post('/v2/email/blast', $requestOptions);
    
        $result = @json_decode($response->getBody(), true);
        
        if(empty($result) || !is_array($result) || !isset($result['success']) || !$result['success']) {
            
            $this->error("Failed to create Blast!");
            
            if(isset($result['errors'])) {
                foreach($result['errors'] as $error) {
                    $this->error($error['errorText']);
                }
            }
            
            exit(1);
        }
        
        $this->info("Blast Successfully Created!");
        
        $this->info("Blast ID: {$result['blast_id']}");
        
    }
}

<?php

namespace App\Mail\Clickagy;

use Illuminate\Mail\Mailable as BaseMailable;
use GuzzleHttp\Client;

class Mailable extends BaseMailable
{
    protected $_blastId;
    protected $_blastIds;
    
    public function send(\Illuminate\Contracts\Mail\Mailer $mailer)
    {
        $clickagy = app()->make('App\Mail\ClickagyTransport');
        
        $mergeData = $this->getViewData();
        
        if(!isset($mergeData['CONTENT']) || empty($mergeData['CONTENT'])) {
            if(!empty($this->view)) {
                $mergeData['CONTENT'] = view($this->view, $mergeData)->__toString();
            }
        }


        $requestOptions = [
            'form_params' => [
                'to' => array_pop($this->to)['address'],
                'blast_id' => $this->getBlastId(),
                'vars' => json_encode($mergeData)
            ]
        ];
        
        $response = $clickagy->post('/v2/email/send', $requestOptions);
        
        $result = @json_decode($response->getBody(), true);
        
        if(empty($result) || !is_array($result) || !isset($result['success']) || !$result['success']) {
            throw new \App\Mail\Clickagy\Exception("Failed to send Message via Clickagy");
        }
        
    }
    
    public function setViewData(array $data)
    {
        $this->viewData = $data;
        return $this;
    }
    
    public function getViewData()
    {
        return $this->viewData;
    }
        
    public function setBlastId($id)
    {
        $this->_blastId = $id;
        return $this;
    }
    
    public function getBlastId()
    {
        return $this->_blastId;
    }
    
    public function __construct()
    {
        $theme = \Config::get('app.theme', 'dn');
        
        if(!isset($this->_blastIds[$theme])) {
            throw new \Exception("No blast ID found for current theme");
        }
        
        $this->setBlastId($this->_blastIds[$theme]);
    }
}
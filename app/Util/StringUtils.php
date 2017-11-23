<?php

namespace App\Util;

class StringUtils
{
    static public function filesizeToString($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }
    
        return $bytes;
    }
    
    static public function extractNameComponents($name)
    {
        $parts = [];
        
        $name = trim($name);
        
        while(strlen($name) > 0) {
            $name = trim($name);
            $temp = preg_replace('#.*\s([\w-]*)$#', '$1', $name);
            $parts[] = $temp;
            $name = trim(preg_replace("#$temp#", '', $name ));
        }
        
        if(empty($parts)) {
            return false;
        }
        
        $parts = array_reverse($parts);
        
        return [
            'first' => $parts[0],
            'middle' => isset($parts[2]) ? $parts[1] : '',
            'last' => isset($parts[2]) ? $parts[2] : isset($parts[1]) ? $parts[1] : ''
        ];
    }
    
    static public function titleFromFilename($filename)
    {
        $components = pathinfo($filename);
        $baseName = $components['filename'];
        
        $baseName = preg_replace('/[\~\`\-\[\]\.\_\(\)\!\@\#\$\%\^\&\*\+\=\,\\\]+/i', ' ', $baseName);
        
        return ucwords($baseName);
    }
    
}
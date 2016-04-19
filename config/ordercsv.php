<?php

return [
        /*
         * Return configuration for order csv.   
         */

        /*
         * The allowed max file size (bytes).  
         */

        'max_file_size' => 500000,

        /*
         * The delimiter used in the uploaded order csv.  
         */

        'delimiter' => '|',

        /*
         * The path to store tmp csv file for importing.   
         */
        
        'path' => storage_path('app'),

        /*
         * The required date format of birthday.   
        */

        'birthday_format' => 'M j, Y',

];


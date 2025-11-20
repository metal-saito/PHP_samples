<?php

/**
 * PHP & Laravel Samples
 * 
 * This repository contains PHP and Laravel technical samples.
 * Please see samples/ directory for individual examples.
 */

namespace App;

class Index
{
    public function run(): void
    {
        echo "PHP & Laravel Samples - Reservation Tool\n";
        echo "This repository contains PHP and Laravel technical samples.\n";
        echo "Please see samples/ directory for individual examples.\n";
        
        if (php_sapi_name() === 'cli') {
            echo "\nRepository structure:\n";
            echo "  samples/01_rest_api - Laravel RESTful API\n";
            echo "  samples/02_job_queue - Job Queue with events\n";
            echo "  samples/03_middleware - Custom middleware\n";
            echo "\nUsage: See README.md for instructions\n";
        }
    }
}

if (php_sapi_name() === 'cli') {
    $index = new Index();
    $index->run();
}

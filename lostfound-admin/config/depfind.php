<?php

$locations = array_filter(array_map('trim', explode(',', (string) env('DEPFIND_LOCATIONS', 'Department Building'))));

return [
    'department' => env('DEPFIND_DEPARTMENT_NAME', 'Department'),
    'locations' => array_values(array_unique($locations)),
    'pickup_location' => env('DEPFIND_PICKUP_LOCATION', 'Department Lost and Found Desk'),
];

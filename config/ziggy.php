<?php

// Routes exposed to JavaScript. The public website only needs the online-booking ones
// (the full list is ~120 KB and would also show every admin URL to visitors).
return [
    'groups' => [
        'website' => ['appointment.paystack.init', 'web.appointment.*'],
    ],
];

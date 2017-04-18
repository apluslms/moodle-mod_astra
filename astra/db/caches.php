<?php

/* Define cache areas for the Moodle Cache API (Moodle Universal Cache, MUC). */

$definitions = array(
    // exercise HTML descriptions are cached so that they do not have to be retrieved from the exercise service every time
    'exercisedesc' => array(
        'mode' => cache_store::MODE_APPLICATION, // cache is shared across users
        'simpledata' => true, // scalar data (array of strings and ints)
        'simplekeys' => true,
        //'staticacceleration' => true,
        // staticacceleration not set: an HTTP request fetches an exercise description only once so the cache does not need to stay in memory for the rest of the request
    ),
);

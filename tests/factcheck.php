<?php

namespace atst\factcheck;

use iter;

require_once __DIR__."/../vendor/autoload.php";

// ints
factcheck("is_int", ints());
factcheck(iter\fn\operator("<=", 5000), 10000, ints(0, 5000));

// unique
factcheck(iter\fn\operator("===", range(1, 10)), [iter\toArray(unique(iter\chain(iter\range(1,10), iter\range(1,10))))]);


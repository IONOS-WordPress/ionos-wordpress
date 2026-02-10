<?php

apcu_clear_cache();

printf('apcu cache cleared');

// apcu_cache_info(bool $limited = false)
print_r(apcu_cache_info());

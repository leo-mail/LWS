<?php
if( substr_count(php_uname(),"Windows")>0) return;
set_include_path(__DIR__);
ini_set("php.include_path", __DIR__);
ini_set("php.short_open_tag", "On");
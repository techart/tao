#!/usr/bin/env php-tao
<?php 
Core::load('CLI');
Core_Arrays::shift($argv);
CLI::run_module($argv); 
?>

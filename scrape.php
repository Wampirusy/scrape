#!/usr/bin/env php
<?php

use Scrape\AccuProvider;
use Scrape\Config;

require 'vendor/autoload.php';

$config = new Config();
$provider = new AccuProvider($config->getCity());

print_r($provider->getWeather($config->getYear()));

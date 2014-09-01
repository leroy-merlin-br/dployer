<?php

// Construct the IoC Container
$app = new Illuminate\Container\Container();

// Bind isolator for PHP functions
$app->bind('php', function() {
    return new Icecave\Isolator\Isolator;
}, true);

// Bind the Symfony Console application
$app->bind('console', function() {
    return new Symfony\Component\Console\Application('Dployment', '0.1 BETA');
}, true);

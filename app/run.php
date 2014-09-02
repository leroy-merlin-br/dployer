<?php

$console = $app->make('console');
$console->add($app->make('Dployer\Command\Deploy'));
$console->run();

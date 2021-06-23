<?php
require_once __DIR__ . '/../vendor/autoload.php';

$PHAR_VERSION = \Akamai\Open\EdgeGrid\Client::VERSION;
$PHAR_NAME = "akamai-open-edgegrid-client-${PHAR_VERSION}.phar";
print($PHAR_NAME);

// The php.ini setting phar.readonly must be set to 0
$pharFile = 'app.phar';

// clean up
if (file_exists($PHAR_NAME)) {
    unlink($PHAR_NAME);
}
if (file_exists($PHAR_NAME . '.gz')) {
    unlink($PHAR_NAME . '.gz');
}

// create phar
$p = new Phar("build/$PHAR_NAME");

// creating our library using whole directory  
$p->buildFromIterator(new RegexIterator(
  new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.')
  ),
  '_^.+\.php$_'
), dirname(__DIR__));

$stub = <<<STUB
<?php
if (class_exists('Phar')) {
   Phar::mapPhar('akamai-open-edgegrid-client.phar');
}

Phar::interceptFileFuncs();
require_once 'phar://akamai-open-edgegrid-client.phar/vendor/autoload.php';
// Run the CLI if called directly
if (PHP_SAPI == 'cli' && basename(\$_SERVER['argv'][0]) == basename(__FILE__)) {
    (new \\Akamai\\Open\\EdgeGrid\\Cli())->run();
    exit;
}
__HALT_COMPILER(); ?>
STUB;

// pointing main file which requires all classes  
$p->setStub($stub);

// plus - compressing it into gzip  
$p->compress(Phar::GZ);
   
echo "$pharFile successfully created";
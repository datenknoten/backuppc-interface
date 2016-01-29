<?php

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

require "vendor/autoload.php";

require "config.php";

use React\SocketClient\TcpConnector;
use Silex\Application\TranslationTrait;

class BackupPcInterface {
    private $host = '';
    private $port = 0;
    private $secret = '';
    private $services = null;

    public function __construct($_services,$_host,$_port,$_secret) {
        $this->services = $_services;
        $this->host = $_host;
        $this->port = $_port;
        $this->secret = $_secret;
    }

    private function generateHash($seed,$count,$message) {
        $secret = $this->secret;
        $parts = explode(",",$seed);
        $message_hash = $seed . $count . $secret . $message; 
        
        $hash = str_replace("=","",base64_encode(md5($message_hash,true)));
        return $hash;
    }

    private function getData($message) {
        $loop = React\EventLoop\Factory::create();

        $tcpConnector = new TcpConnector($loop);

        $i = 0;

        $status_text = "";

        $services = $this->services;
        //$this->services->log('Starting connector');
        $tcpConnector
            ->create($this->host, $this->port)
            ->then(function (React\Stream\Stream $stream) use (&$status_text,$message,$services) {
                $command_count = 0;
                $stream->on('data', function($data) use (&$command_count,$stream,&$status_text,$message){
                    if ($command_count == 0) {
                        //$services->log($message);
                        $hash = $this->generateHash($data,$command_count,$message);
                        $_message = sprintf("%s %s\n",$hash,$message);
                        $stream->write($_message);
                        $command_count++;
                    } else {
                        $status_text .= $data;
                        if (substr($status_text,strlen($status_text)-1,1) == "\n") {
                            $stream->end();
                        }
                    }
                });
            });

        $loop->run();

        $status_text = str_replace(['{','}','(',')','%','undef','*::FH'],['[',']','[',']','$','null','null'],$status_text);

        return $status_text;
    }

    public function getHosts() {
        $data = $this->getData("status hosts");

        eval($data);

        unset($Status[' admin ']);
        unset($Status[' admin1 ']);
        unset($Status[' trashClean ']);

        return $Status;
    }

    public function getHost($hostname) {
        $data = $this->getData(sprintf('status hostjobs(%s)',$hostname));

        eval($data);

        $retval = [];

        for($i = 1; $i <= 100; $i++) {
            $to_eval = sprintf('$var = (isset($VAR%d) ? $VAR%d : null);',$i,$i);
            eval($to_eval);
            if (!is_null($var)) {
                $retval[] = $var;
            }
        }
        
        return $retval;
    }

}

class BackupApplication extends Silex\Application {
    use Silex\Application\TranslationTrait;
    use Silex\Application\TwigTrait;
    use Silex\Application\MonologTrait;
}

$app = new BackupApplication(); 

$app['debug'] = true;

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/logs/app.log',
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/templates',
));

$bpc = new BackupPcInterface($app,$config['host'],$config['port'],$config['secret']);

$app->get('/', function() use($app) { 
    return $app['twig']->render('index.html.twig');
});

$app->get('/host/', function() use($app,$bpc, $config) {
    $app->log('Fetching hosts');
    return json_encode($bpc->getHosts());
});

$app->get('/host/{hostname}',function($hostname) use ($app,$bpc){
    $app->log('Fetching information for Host ' . $hostname);
    return json_encode($bpc->getHost($hostname));
    
});

$app->run();
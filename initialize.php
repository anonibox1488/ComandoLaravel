<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Artisan;

class initialize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'initialize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Puesta en Marcha';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->info('Validando la version de PHP');
            $this->validatePhp();
            $this->info('PHP correcto');
            $this->info('Validando Base de Datos');
            $this->validateBD();
            $this->info('Base de Datos correcta');
            $this->info('Inicializando Variables de entorno');
            $this->configurationEnv();
            Artisan::call('key:generate');
            $this->info('Porfavor espere mientras se configura la base de datos');
            $this->info('Comenzando migracion');
            Artisan::call('migrate');
            $this->info('Migracion Finalizada');
            $this->info('Cargando datos basicos por favor espere');
            Artisan::call('db:seed');
            $this->info('Precione ENTER para finalizar');
            Artisan::call('jwt:secret');
            $this->info('Configuracion completada exitosamente');
            
        } catch (Exception $e) {
            $this->error($e);
        }
    }

    public function setEnvironmentValue(array $values){
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {

                $str .= "\n"; // In case the searched variable is in the last line without \n
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }
            }
        }

        $str = substr($str, 0, -1);
        if (!file_put_contents($envFile, $str)) return false;
        return true;
    }

    public function validatePhp(){
        $phpv = phpversion();
        if ($phpv < 7.2) {
            $this->error('La version PHP debe ser mayor o igual a 7.2');
            // return false;
        }
        //Validando php crul
        if  (!in_array ('curl', get_loaded_extensions())) {
            $this->error('Se necesita instalar CRUL en PHP para un funcionamiento correcto');
        }
    }

    public function validateBD(){
        $db = shell_exec('mysql --version');
        $data = explode(",", $db);
        if (strpos($data[0], 'Distrib') === false) {
            $this->error('No tienes base de datos instalada');
            // return false;
        }
        $position = strpos($data[0], 'Distrib');
        $mysql = substr($data[0], ($position +8),strlen($data[0]));
        $version = explode("-", $mysql);
        if (isset($version[1])) {
            $min = 10.2;
            $mensaje = 'La version minima de MariaDB debe ser 10.2';
        }else{
            $min = 5.6;
            $mensaje = 'La version minima de MySql debe ser 5.8';
        }
        $ver = explode(".", $version[0]);
        if (($ver[0] .'.'. $ver[1]) < $min) {
            $this->error($mensaje);
            // return false;
        }
    }

    public function configurationEnv(){
        $name = getenv('APP_NAME');
        if ($name == null) {
            shell_exec('cp .env.example .env');
        }
        $this->info('Por favor ingresar los siguientes valores');
        $DB_HOST = $this->ask('Ingresa el ip del host de la base de datos');
        $DB_PORT = $this->ask('Ingresa el puerto de base de datos');
        $DB_DATABASE = $this->ask('Ingresa el nombre de la base de datos');
        $DB_USERNAME = $this->ask('Ingresa el usuario de la base de datos');
        $DB_PASSWORD = $this->secret('Ingresa el calve de la base de datos');
        $values =  [
            'DB_HOST' => $DB_HOST,
            'DB_PORT' => $DB_PORT,
            'DB_DATABASE' => $DB_DATABASE,
            'DB_USERNAME' => $DB_USERNAME,
            'DB_PASSWORD' => $DB_PASSWORD,
            'APP_DEBUG'=>'false',
            'APP_SITE_MAIL'=>'alterhome',
            'MAIL_ENCRYPTION'=>'tls',
            'MAIL_FROM_ADDRESS'=>'from@example.com',
            'MAIL_FROM_NAME'=>'Example',
            'PAACK_KEY'=>'',
            'ELINFORMAR_CLIENT_ID'=>'',
            'ELINFORMAR_CLIENT_SECRET'=>'',
            'TWILIO_SID'=>'',
            'TWILIO_TOKEN'=>'',
            'TWILIO_FROM'=>'',
            'TRUUST_PK'=>'=',
            'TRUUST_SK'=>'',
            'STRIPE_KEY'=>'',
            'STRIPE_SECRET'=>'',
            'STRIPE_WEBHOOK_SECRET'=>'',
            'STRIPE_WEBHOOK_TOLERANCE'=>'',
            'STRIPE_PLATFORM_ID'=>'',
            'GOOGLE_API_VISION_KEY'=>'',
        ];
        $this->setEnvironmentValue($values);
    }
}


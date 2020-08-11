<?php

namespace IvanoMatteo\ModelUtils\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use IvanoMatteo\ModelUtils\ModelUtils;
use ReflectionClass;

class GenFactories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model-ultils:gen-factories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate a factory for all Models in App\\** namespace';

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
        $models = ModelUtils::findModels();

        foreach ($models as $rfclass) {

            if (!$rfclass->isInstantiable()) {
                continue;
            }

            $file = base_path('database/factories/' . $rfclass->getShortName() . 'Factory.php');

            if (!file_exists($file)) {
                $processed = static::genFactory($rfclass);
                file_put_contents($file, $processed);
                echo $rfclass->getName() . " processed.\n";
            } else {
                echo $rfclass->getName() . " skipped, alredy exists.\n";
            }
        }
    }


    static function guessData($type, $name)
    {
        switch ($type) {
            case 'integer':
                return '$faker->randomNumber';
            case 'float':
                return '$faker->randomFloat';
            case 'string':
                if (\Str::startsWith($name, ['email', 'mail']) || \Str::endsWith($name, ['email', 'mail'])) {
                    return '$faker->unique()->safeEmail';
                }
                if (\Str::startsWith($name, ['address', 'indirizzo']) || \Str::endsWith($name, ['address', 'indirizzo'])) {
                    return '$faker->address';
                }
                if (\Str::startsWith($name, ['phone', 'mobile', 'telephone', 'telefono', 'cellulare']) || \Str::endsWith($name, ['phone', 'mobile', 'telephone', 'telefono', 'cellulare'])) {
                    return '$faker->e164PhoneNumber';
                }
                if (\Str::startsWith($name, ['sex', 'sesso']) || \Str::endsWith($name, ['sex', 'sesso'])) {
                    return '$faker->randomElement([\'M\', \'F\'])';
                }
                if ($name == 'cap' || \Str::startsWith($name, ['cap_']) || \Str::endsWith($name, ['_cap'])) {
                    return '$faker->randomNumber(5, true)';
                }
                if ($name == 'token' || \Str::startsWith($name, ['token_']) || \Str::endsWith($name, ['_token'])) {
                    return 'Str::random(10)';
                }
                if ($name == 'password' || \Str::startsWith($name, ['password_']) || \Str::endsWith($name, ['_password'])) {
                    return 'Hash::make(\'password\')';
                }
                if (in_array($name,['last_name','family_name','cognome']) || \Str::startsWith($name, ['last_name_','cognome_']) || \Str::endsWith($name, ['_last_name','_cognome'])) {
                    return '$faker->lastName';
                }
                if (in_array($name,['name','nome']) || \Str::startsWith($name, ['name_','nome_']) || \Str::endsWith($name, ['_name','_nome'])) {
                    return '$faker->name';
                }
                

                return '$faker->sentence';

            case 'boolean':
                return '$faker->randomElement([true, false])';
            case 'date':
                if (\Str::startsWith($name, ['birth', 'nascita']) || \Str::endsWith($name, ['birth', 'nascita'])) {
                    return '$faker->date(\'Y-m-d\', now()->subYears(18))';
                }
                return '$faker->date(\'Y-m-d\', now())';
            case 'datetime':
            case 'timestamp':
                if ($name === 'email_verified_at') {
                    return 'now()';
                }
                return '$faker->dateTime';
            case 'time':
                return '$faker->time(\'H:i:s\')';
            case 'year':
                return '$faker->year';

            case 'text':
                return '$faker->paragraph(10)';
            case 'blob':
                return '$faker->paragraph(10)';
            case 'json':
                return 'json_encode(["key"=>"val","key1"=>"val1","key2"=>"val2","key3"=>"val3"])';
            default:
                return 'null';
        }
    }


    static function genFactory(ReflectionClass $rfclass)
    {
        $mu = new ModelUtils($rfclass);
        $colmap = $mu->getDBMetadata();

        $out = [
            '<?php',
            ' /** @var \Illuminate\Database\Eloquent\Factory $factory */',
            'use ' . $rfclass->getName() . ';',
            'use Faker\Generator as Faker;',
            "\n\n",
            '$factory->define(' . $rfclass->getShortName() . '::class, function (Faker $faker) {',
            "\treturn ["
        ];

        foreach ($colmap as $c => $info) {
            if (in_array($c, ['id','created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            $out[] = "\t\t'$c' => " . static::guessData($info['type'], $c) . ",";
        }

        $out[] = "\t];";
        $out[] = '});';

        return implode("\n", $out);
    }
}

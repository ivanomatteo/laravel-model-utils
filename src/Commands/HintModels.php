<?php

namespace IvanoMatteo\ModelUtils\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use IvanoMatteo\ModelUtils\ModelUtils;
use ReflectionClass;

class HintModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hint:models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add @property type hint to all Models in App\\** namespace';

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

            $file = $rfclass->getFileName();
            $cstart = $rfclass->getStartLine();

            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            $docComment = static::getFirstDocBlock($content, $cstart);

            $props = static::genDocProps($rfclass);

            $out = [];
            for ($i = 0; $i < count($lines); $i++) {
                $l = $i + 1;
                if ($l >= ($docComment['line'] ?? $cstart) && $l < $cstart) {
                    // old comment lines
                } else if ($l === $cstart) {
                    $old = static::removeDocProps($docComment['text'] ?? '');
                    $out[] = "/**\n" . $old . "\n" . $props . "\n*/";
                    $out[] = $lines[$i];
                } else {
                    $out[] = $lines[$i];
                }
            }

            $processed = implode("\n", $out);

            file_put_contents($file, $processed);

            echo $rfclass->getName()." processed.\n";
        }
    }


    public static $typeMap = [
        'integer' => 'int',
        'float' => 'float',
        'string' => 'string',
        'text' => 'string',
    ];


    static function genDocProps($rfclass)
    {
        $mu = new ModelUtils($rfclass);
        $colmap = $mu->getDBMetadata();

        $out = [];
        foreach ($colmap as $c => $info) {
            $type = static::$typeMap[$info['type']] ?? 'mixed';
            $out[] = " * @property $type \$$c";
        }
        return implode("\n", $out);
    }


    public static function getFirstDocBlock($content, $maxLine)
    {
        $docComments = array_filter(
            token_get_all($content),
            function ($entry) use ($maxLine) {
                return $entry[0] == T_DOC_COMMENT && $entry[2] < $maxLine;
            }
        );
        $fileDocComment = array_shift($docComments);
        if (empty($fileDocComment[1])) {
            return null;
        }
        return [
            'text' => $fileDocComment[1],
            'line' => $fileDocComment[2],
            'lines' => count(explode("\n", $fileDocComment[2])),
        ];
    }

    public static function removeDocProps($props)
    {
        $a = explode("\n", $props);
        $out = [];
        foreach ($a as $l) {
            $l = preg_replace("/^\\s*\\/\\*+/", '', $l);
            $l = preg_replace("/\\s*\\*\\/$/", '', $l);


            if (
                !empty(trim($l))
                && !preg_match("/\\s*\\*?\\s*@property\\s+.*$/", $l)
            ) {
                $out[] = $l;
            }
        }

        return implode("\n", $out);
    }
}

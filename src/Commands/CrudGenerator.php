<?php

namespace DevDr\ApiCrudGenerator\src\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CrudGenerator extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    var $fieldsFillable;
    var $fieldsHidden;
    var $fieldsCast;
    var $fieldsDate;
    var $debug;
    var $options;
    var $columns;
    var $databaseConnection;
    var $property;
    protected $signature = 'crud:api-generator
    {name : Class (singular) for example User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Api CRUD Operations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();

        $this->options = [
            'connection' => '',
            'table' => '',
            'debug' => false,
            'all' => false,
        ];
    }

    protected function getStub($type) {
        return file_get_contents(realpath(__DIR__ . "/../stubs/$type.stub"));
    }

    public function doComment($text, $overrideDebug = false) {
        if ($this->options['debug'] || $overrideDebug) {
            $this->comment($text);
        }
    }

    public function handle() {
        $name = $this->argument('name');
        $table = strtolower($name);
        if (Schema::hasTable($table)) {
            $this->controller($name);
            $this->request($name);
            File::append(base_path('routes/api.php'), 'Route::resource(\'' . str_plural(strtolower($name)) . "', '{$name}Controller');");
            $this->model($name);
        } else {
            $this->doComment('Database does not have ' . $table . ' table', true);
        }
    }

    public function getSchema($tableName) {
        $this->doComment('Retrieving table definition for: ' . $tableName);
        if (strlen($this->options['connection']) <= 0) {
            return Schema::getColumnListing($tableName);
        } else {
            return Schema::connection($this->options['connection'])->getColumnListing($tableName);
        }
    }

    public function describeTable($tableName) {
        $this->doComment('Retrieving column information for : ' . $tableName);
        if (strlen($this->options['connection']) <= 0) {
            return DB::select(DB::raw('describe ' . $tableName));
        } else {
            return DB::connection($this->options['connection'])->select(DB::raw('describe ' . $tableName));
        }
    }

    public function replaceModuleInformation($stub, $modelInformation) {
        // replace table
        $stub = str_replace('{{table}}', $modelInformation['table'], $stub);

        // replace fillable
        $this->fieldsHidden = '';
        $this->fieldsFillable = '';
        $this->fieldsCast = '';
        $property = [];
        foreach ($modelInformation['fillable'] as $field) {
            // fillable and hidden
            if ($field != 'id' && $field != 'created_at' && $field != 'updated_at') {
                $this->fieldsFillable .= (strlen($this->fieldsFillable) > 0 ? ', ' : '') . "'$field'";
                $fieldsFiltered = $this->columns->where('field', $field);
                if ($fieldsFiltered) {
                    // check type
                    switch (strtolower($fieldsFiltered->first()['type'])) {
                        case 'timestamp':
                            $this->fieldsDate .= (strlen($this->fieldsDate) > 0 ? ', ' : '') . "'$field'";
                            break;
                        case 'datetime':
                            $this->fieldsDate .= (strlen($this->fieldsDate) > 0 ? ', ' : '') . "'$field'";
                            break;
                        case 'date':
                            $this->fieldsDate .= (strlen($this->fieldsDate) > 0 ? ', ' : '') . "'$field'";
                            break;
                        case 'tinyint(1)':
                            $this->fieldsCast .= (strlen($this->fieldsCast) > 0 ? ', ' : '') . "'$field' => 'boolean'";
                            break;
                    }
                }
            } else {
                if ($field != 'id' && $field != 'created_at' && $field != 'updated_at') {
                    $this->fieldsHidden .= (strlen($this->fieldsHidden) > 0 ? ', ' : '') . "'$field'";
                }
            }
        }

        foreach ($this->columns as $col) {
            $field = $col['field'];
            array_push($property, ' * @property $' . $field);
        }
        $properties = implode("\n", $property);
        // replace in stub
        $stub = str_replace('{{property}}', $properties, $stub);
        $stub = str_replace('{{fillable}}', $this->fieldsFillable, $stub);
        $stub = str_replace('{{hidden}}', $this->fieldsHidden, $stub);
        $stub = str_replace('{{casts}}', $this->fieldsCast, $stub);
        $stub = str_replace('{{dates}}', $this->fieldsDate, $stub);

        return $stub;
    }

    protected function model($name) {
        $table = strtolower($name);

        $stub = $this->getStub('Model');

        $model = array(
            'table' => $table,
            'fillable' => $this->getSchema($table),
            'guardable' => array(),
            'hidden' => array(),
            'casts' => array(),
        );

        $columns = $this->describeTable($table);

        $this->columns = collect();

        foreach ($columns as $col) {
            $this->columns->push([
                'field' => $col->Field,
                'type' => $col->Type,
            ]);
        }

        $stub = str_replace(
                ['{{modelName}}'],
                [$name],
                $stub
        );

        $stub = $this->replaceModuleInformation($stub, $model);

        $this->doComment('Writing model: ' . app_path("/{$name}.php"), true);

        file_put_contents(app_path("/{$name}.php"), $stub);
    }

    protected function controller($name) {
        $controllerTemplate = str_replace(
                [
                    '{{modelName}}',
                    '{{modelNamePluralLowerCase}}',
                    '{{modelNameSingularLowerCase}}'
                ],
                [
                    $name,
                    strtolower(str_plural($name)),
                    strtolower($name)
                ],
                $this->getStub('Controller')
        );

        if (!file_exists($path = app_path('/Http/Controllers/Api')))
            mkdir($path, 0777, true);
        file_put_contents(app_path("/Http/Controllers/Api/{$name}Controller.php"), $controllerTemplate);
    }

    protected function request($name) {
        $requestTemplate = str_replace(
                ['{{modelName}}'],
                [$name],
                $this->getStub('Request')
        );

        if (!file_exists($path = app_path('/Http/Requests')))
            mkdir($path, 0777, true);

        file_put_contents(app_path("/Http/Requests/{$name}Request.php"), $requestTemplate);
    }

}

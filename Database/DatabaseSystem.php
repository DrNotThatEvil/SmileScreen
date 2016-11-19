<?php 
namespace SmileScreen\Database;

use SmileScreen\Base\BaseModel as BaseModel;
use SmileScreen\Base\ModelStates as ModelStates;
use SmileScreen\Base\Singleton as Singleton;
use SmileScreen\Exceptions\GenericSmileScreenException as GenericSmileScreenException;
use SmileScreen\Exceptions\DatabaseSmileScreenException as DatabaseSmileScreenException;
use SmileScreen\Config\ConfigSystem as ConfigSystem;
use SmileScreen\Database\SelectQuery as SelectQuery;

use \PDO as PDO;

class DatabaseSystem extends Singleton 
{
    private $configSystem;
    private $pdoObject;

    private $tables = [];
    private $tableDefintions = [];


    protected function __construct() 
    {
        $this->configSystem = ConfigSystem::getInstance(); 
        $this->connect();
        $this->getDatabaseTables();
    }

    private function connect()
    {
        $mysqlSettings = $this->configSystem->getSetting('mysql');

        $dsn  = 'mysql:host=' .$mysqlSettings['host'];
        $dsn .= ';dbname='.$mysqlSettings['database'];
        $dsn .= ';charset=utf8';

        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdoObject = new PDO($dsn, $mysqlSettings['user'], $mysqlSettings['password'], $opt); 
    }

    private function getDatabaseTables() 
    {
        try {
            $statement = $this->pdoObject->prepare('SHOW TABLES');
            $statement->execute();

            $tables = $statement->fetchAll(PDO::FETCH_NUM);

            foreach ($tables as $table) {
                // in this for each we pull al the columns from all the tables.
                // so we can use this usefull information later without having to bother the database for it again.
                $sql = 'SELECT column_name, data_type, character_maximum_length, is_nullable FROM information_schema.columns ';
                $sql .= 'WHERE table_name = ? and table_schema = ? ORDER BY ordinal_position';
                  
                $columnsStatment = $this->pdoObject->prepare($sql);
                // Okay let me explain this mysql statement quick.
                // here we just ask a mysql table that exists by default in the database: "Can we have the columns of table x thats part of database x";
                // Just read it again and you will get it :)

                $columnsStatment->execute([$table[0], $this->configSystem->getSetting('mysql.database')]);
                // here we just supply the tablename and database. 
                
                $tableColumnData = $columnsStatment->fetchAll(PDO::FETCH_NUM);
                // We save the usefull data for later use.

                $this->tables[] = $table[0];
                $this->tableDefintions[$table[0]] = array();
                foreach ($tableColumnData as $columnData) {
                    $this->tableDefintions[$table[0]][$columnData[0]] = [ 
                        'type' => $columnData[1],
                        'length' => (!is_null($columnData[2]) ? $columnData[2] : 0),
                        'nullable' => ($columnData[3] === 'YES' ? true : false)
                    ];
                }
            }

        } catch (\PDOException $e) {
            throw new DatabaseSmileScreenException('Could not get database tables: '.$e->getMessage());
        }
    }

    public function modelsFromDatabase($model, SelectQuery $where) 
    {
        if(!is_subclass_of($model, 'SmileScreen\Base\BaseModel')) {
            throw new GenericSmileScreenException('Model needs to extend BaseModel. Not be a instance of BaseModel.');
        }

        $modelTableName = $model->getDatabaseTable();

        $where->setTable($modelTableName);
        $where->select(['*', 'count(*) as cnt']);
           
        if (!in_array($modelTableName, $this->tables)) {
            throw new DatabaseSmileScreenException('Model ' . get_class($model) . ' with providing tablename \'' . $modelTableName . '\' could not be found in the database.');
        }

        $needFilling = $model->getAllDatabaseAttributes();

        $whereStatement = $where->getStatement();
        try {
            $fillStatment = $this->pdoObject->prepare($whereStatement[0]);
            $fillStatment->execute($whereStatement[1]);
            
            $results = $fillStatment->fetchAll(PDO::FETCH_ASSOC);
            
            if ($results[0]['cnt'] == 0) {
                return [];
            }

            $modelsArray = [];
            $className = get_class($model);


            for($i=0; $i<count($results); $i++) {
                $attributes = $results[$i];
                unset($attributes['cnt']);
                unset($attributes['created_on']);
                unset($attributes['updated_on']);
                
                $newModel = new $className($attributes, ModelStates::FROM_DATABASE); 
                $modelsArray[] = $newModel;
            }

            return $modelsArray;

        } catch (\PDOException $e) {
            throw new DatabaseSmileScreenException('Could not fill model: '.$e->getMessage());
        }
    }
}

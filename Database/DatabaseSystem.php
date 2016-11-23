<?php
namespace SmileScreen\Database;

use SmileScreen\Base\BaseModel as BaseModel;
use SmileScreen\Base\ModelStates as ModelStates;
use SmileScreen\Base\Singleton as Singleton;
use SmileScreen\Exceptions\GenericSmileScreenException as GenericSmileScreenException;
use SmileScreen\Exceptions\DatabaseSmileScreenException as DatabaseSmileScreenException;
use SmileScreen\Config\ConfigSystem as ConfigSystem;
use SmileScreen\Database\SelectQuery as SelectQuery;
use SmileScreen\Database\InsertQuery as InsertQuery;
use SmileScreen\Database\UpdateQuery as UpdateQuery;

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
                $sql = 'SELECT column_name, data_type, character_maximum_length, is_nullable,extra, column_default ';
                $sql .= 'FROM information_schema.columns ';
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
                        'nullable' => ($columnData[3] === 'YES' ? true : false),
                        'extra' => $columnData[4],
                        'default' => $columnData[5]
                    ];
                }
            }

        } catch (\PDOException $e) {
            throw new DatabaseSmileScreenException('Could not get database tables: '.$e->getMessage());
        }
    }


    private function getTableRequiredColumns(string $table)
    {
        $arr = [];
        if(!in_array($table, $this->tables)) {
            throw new DatabaseSmileScreenException('Could not get required columns. \'' . $table . '\' table not found.');
        }

        foreach($this->tableDefintions[$table] as $columnName => $column) {
            if(!$column['nullable']
                && strpos($column['extra'], 'auto_increment') === FALSE
                && is_null($column['default'])) {
               $arr[] = $columnName;
            }
        }

        return $arr;
    }

    private function validateInsertQuery(InsertQuery $query, bool $thow = false)
    {
        if(!$query->isValid()) {
            if ($throw) {
                throw new DatabaseSmileScreenException('InsertQuery not valid. Set all parameters');
            }
            return false;
        }

        if(!in_array($query->getTable(), $this->tables)) {
            if ($throw) {
                throw new DatabaseSmileScreenException('InsertQuery not valid. \'' . $query->getTable() . '\' table not found.');
            }
            return false;
        }

        $requiredColumns = $this->getTableRequiredColumns($query->getTable());

        if ($requiredColumns != array_intersect($requiredColumns, $query->getColumns())) {
            if ($throw) {
                throw new DatabaseSmileScreenException('InsertQuery not valid not all required columns are filled.');
            }
            return false;
        }

        if (!is_array($query->getValues()[0])) {
            $return = count($query->getColumns()) == count($query->getValues());
            if(!$return && $throw) {
                throw new DatabaseSmileScreenException('InsertQuery not amount of values and columns is different.');
            }

            return $return;
        }

        foreach ($query->getValues() as $values) {
            if (count($values) != count($query->getColumns())) {
                if ($throw) {
                    throw new DatabaseSmileScreenException('InsertQuery not amount of values and columns is different.');
                }
                return false;  // All value groups whould have the specified columns
            }
        }

        return true;
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
                if ($model->usesTimestamps()) {
                    $newModel->setCreatedOn($results[$i]['created_on']);
                    $newModel->setUpdatedOn($results[$i]['updated_on']);
                }
                $modelsArray[] = $newModel;
            }

            return $modelsArray;

        } catch (\PDOException $e) {
            throw new DatabaseSmileScreenException('Could not fill model: '.$e->getMessage());
        }
    }

    public function runInsertQuery(InsertQuery $query)
    {
        if (!$this->validateInsertQuery($query)) {
            return false; // Dont know if i should throw a exception here..
        }

        $sql = $query->getStatement();
        $sqlParameters = call_user_func_array('array_merge', [$query->getValues()]);

        try {
            $insertStatement = $this->pdoObject->prepare($sql);
            return $insertStatement->execute($sqlParameters);
        } catch (\PDOException $e) {
            throw new DatabaseSmileScreenException('Could not fill model: '.$e->getMessage());
        }

		return false;
    }

    public function saveModelToDatabase($model)
    {
        if(!is_subclass_of($model, 'SmileScreen\Base\BaseModel')) {
            throw new GenericSmileScreenException('Model needs to extend BaseModel. Not be a instance of BaseModel.');
        }

		$modelState = $model->getModelState();
        $modelTableName = $model->getDatabaseTable();
        $date = date('Y-m-d H:i:s');

		if(($modelState & (ModelStates::FROM_DATABASE | ModelStates::NOT_SAVED)) == 3) {
            // Model is from database and needs to be updated.
            $updateQuery = ((new UpdateQuery())->setTable($modelTableName));
            $updateQuery->setIdField($model->getIdField());
            $updateQuery->setId($model->getId());

            $updateColumns = $model->getAllDatabaseAttributes(false);
            $updateValues = $model->getAllDatabaseValues(false);

            $realUpdateColumns = $updateColumns;
            $realUpdateValues = $updateValues;

            if ($model->usesTimestamps()) {
                $realUpdateColumns[] = 'updated_on';
                $realUpdateValues[] = $date;
            }

            $updateQuery->setColumns($realUpdateColumns);
            $updateQuery->setValues($realUpdateValues);

            $sql = $updateQuery->getStatement();

            try {
                $updateStatement = $this->pdoObject->prepare($sql);
                $updateStatement->execute($updateQuery->getValues(true));

				$model->setModelState(ModelStates::FROM_DATABASE);
                // Does the model use timestamps? If so we set it to the date from earlier
                if ($model->usesTimestamps()) {
                    $model->setUpdatedOn($date);
                }

                return true;
            } catch (\PDOException $e) {
                throw new DatabaseSmileScreenException('Could not update model: '.$e->getMessage());
            }
        }

        if(($modelState & ModelStates::NOT_SAVED) == 1) {
            // Model is not from database but needs to be saved.
            $insertQuery = ((new InsertQuery())->setTable($modelTableName));
            $insertColumns = $model->getAllDatabaseAttributes(false);
            $insertValues = $model->getAllDatabaseValues(false);

            $realInsertColumns = $insertColumns;
            $realInsertValues = $insertValues;

            // Does the model use dates? If so we ad the required values to the insertQuery
            if ($model->usesTimestamps())
            {
                $realInsertColumns[] = 'created_on';
                $realInsertColumns[] = 'updated_on';

                $realInsertValues[] = $date;
                $realInsertValues[] = $date;
            }

            $insertQuery->setColumns($realInsertColumns);
            $insertQuery->setValues($realInsertValues);
            $this->runInsertQuery($insertQuery);

            //NOW LETS GET THAT ID
            $selectQuery = ((new SelectQuery())->setTable($modelTableName));
            $selectQuery->select([$model->getIdField(). ' AS id']);
            $whereArray = [];
            foreach($insertColumns as $key => $attribute) {
				if(is_null($insertValues[$key])) {
					continue;
				}
                $whereArray[$attribute] = ['=', $insertValues[$key]];
            }

            $selectQuery->where($whereArray, 'AND');
            $selectStatement = $selectQuery->getStatement();

            try {
                $whereStatement = $this->pdoObject->prepare($selectStatement[0]);
                $whereStatement->execute($selectStatement[1]);
            	$results = $whereStatement->fetchAll(PDO::FETCH_ASSOC);

				$id = $results[0]['id'];

                // Here we update the model. We set it's state to FROM_DATABAS
                // And we set it's id to the id we just got by looking up its values
				$model->setId($id);
				$model->setModelState(ModelStates::FROM_DATABASE);

                // Does the model use timestamps? If so we set it to the date from earlier
                if ($model->usesTimestamps()) {
                    $model->setCreatedOn($date);
                    $model->setUpdatedOn($date);
                }

				return true;
            } catch (\PDOException $e) {
                throw new DatabaseSmileScreenException('Could not get id of inserted model: '.$e->getMessage());
            }
        }

        if(($modelState & ModelStates::NOT_SAVED) == 0) {
            // No update to the database is needed.
            // we just return true cause technically everthing is saved.
            return true;
        }
    }
}

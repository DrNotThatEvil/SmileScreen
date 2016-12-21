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
use SmileScreen\Database\DeleteQuery as DeleteQuery;

use \PDO as PDO;

// this class is heavily commented
// i did this so pepole know what this class does cause 
// they need to use it all the time
// i hope they will appricaite my time by reading the cafully crafted
// commmnets and learn how i do things and my line of reasoning a bit
// this framework will be on github after this project so i guess you can go read them 
// to if your from the future. if thats the case did they invent hoverboards yet? if so let me know.

/**
 * DatabaseSystem
 * This is the SmileScreen database system.
 * It takes care of everything database related.
 * from saving models to filling them.
 * 
 * It might still be a bit rough around the edges
 * but it should do everything we need for now.
 *
 * @uses Singleton
 * @package SmileScreen\Database
 * @author Willmar Knikker aka DrNotThatEvil <wil@wilv.in>
 * @version 0.1.0
 */
class DatabaseSystem extends Singleton
{
    /**
     * This holds the a instance of the ConfigSystem
     * This is needed to get the database settings
     *
     * @var mixed
     */
    private $configSystem;

    /**
     * The pdoObject for the connection with the database.
     *
     * @var PDO
     */
    private $pdoObject;

    /**
     * a array with all the tables in the current databse.
     * this is used later for other oprations and verification of querys
     *
     * @var array
     */
    private $tables = [];

    /**
     * This array hols the table definitions
     * its filled with every table's columns and there information
     * this is later used for other operations like verifying querys
     *
     * @var array
     */
    private $tableDefintions = [];


    /**
     * The constructor for the DatabaseSystem it just gets a instance
     * of the configuration system.
     * Then it runs the connect function to connect to the database
     * and last but not least fills the table and tabledefinitions with getDatabaseTables
     *
     * @return void
     */
    protected function __construct()
    {
        $this->configSystem = ConfigSystem::getInstance();
        $this->connect();
        $this->getDatabaseTables();
    }

    /**
     * The connect method it connects to the database using pdo
     * and stores the resulting PDO object in $pdoObject
     *
     * @return void
     */
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

    /**
     * This method gets the tables and the table definitions
     * from the database and stores them in $tables and $tableDefintions respectively
     *
     * @return void
     */
    private function getDatabaseTables()
    {
        try {
            $statement = $this->pdoObject->prepare('SHOW TABLES');
            // Here we prepare a query 'SHOW TABLES'
            // to get the current tables from the database

            $statement->execute();
            // Execute the stamtent to get the sweet data

            $tables = $statement->fetchAll(PDO::FETCH_NUM);
            // Get all the tables and store them in the $tables variable

            foreach ($tables as $table) {
                // We loop trough the found tables and get all there columns in this loop
                // Thats the basics of this foreach right there but you can read further to gain all the knowlege. 
                
                $sql = 'SELECT column_name, data_type, character_maximum_length, is_nullable,extra, column_default ';
                $sql .= 'FROM information_schema.columns ';
                $sql .= 'WHERE table_name = ? and table_schema = ? ORDER BY ordinal_position';
                // This is just a complex query of the data we want from a special mysql table information_schema.columns
                // We get the name, data_type, maximum length, is_nullable, extra data and column default.

                $columnsStatment = $this->pdoObject->prepare($sql);
                // Now lets prepare that query 
                
                $columnsStatment->execute([$table[0], $this->configSystem->getSetting('mysql.database')]);
                // here we just supply the tablename and database.

                $tableColumnData = $columnsStatment->fetchAll(PDO::FETCH_NUM);
                // We save the usefull data for later use.

                $this->tables[] = $table[0];
                // Here we just store the table of the loop in $this->tables.
                // Really simple stuff                

                $this->tableDefintions[$table[0]] = array();
                // Here we say the $table[0] (witch is our table name)
                // needs to be a array in $this->tableDefintions
                // Its just a list in a list kind of deal 
                
                foreach ($tableColumnData as $columnData) {
                    // Another loop but here we set the data we found in the complex column query above 
                    // to the right places in the $this->tableDefintions array.

                    $this->tableDefintions[$table[0]][$columnData[0]] = [
                        'type' => $columnData[1],
                        'length' => (!is_null($columnData[2]) ? $columnData[2] : 0),
                        'nullable' => ($columnData[3] === 'YES' ? true : false),
                        'extra' => $columnData[4],
                        'default' => $columnData[5]
                    ];
                }
            }

            // Se its not that complex if you get down to it.
            // it's already a good sign your reading my actual comments you will learn quick my child.
            // Now grab your lightsaber.. i mean codeeditor and get learning!

        } catch (\PDOException $e) {
            // This might look silly what we do here is catch the generic PDO error.
            // And wrap it in my custom error and then throw it back.
            // I do this because PDO exceptions are generic its not always clear
            // where the problamatic call comes from so i just say 'Could not get database tables' 
            // and append the old error.

            throw new DatabaseSmileScreenException('Could not get database tables: '.$e->getMessage());
        }
    }


    /**
     * Gets the required columns of a database table
     *
     * @param string $table the database table
     * @return array the array with required columns
     * @throws DatabaseSmileScreenException
     */
    private function getTableRequiredColumns(string $table)
    {
        // welcome to another commented function.
        // This one is simple.
        // it just returns the required columns of a table.
        // it does this by looking up the things stored in $this->tableDefintions

        $arr = [];
        // Creates a empty array we can use later.

        if(!in_array($table, $this->tables)) {
       
            // this if is triggered if the was not found in our $this->tables array 
            // witch means its not in our database and we of course can't do anything with it.
            // i find this programming error enough to throw a neat exception
        
            throw new DatabaseSmileScreenException('Could not get required columns. \'' . $table . '\' table not found.');
        }


        foreach($this->tableDefintions[$table] as $columnName => $column) {
            // lets loop trough the columns of this table.
            if(!$column['nullable']
                && strpos($column['extra'], 'auto_increment') === FALSE
                && is_null($column['default'])) {

                // O no a complex if statement!
                // Whatever shall we do? Fear not comment man is here to save the day.
                // thank god comment man!
                //
                // Jokes aside this is not as complex as it look
                // the first part !$column['nullable'] just says if this column is not nullable 
                // The second part with the strpos function checks the extra auto_incrment value is NOT present
                // last but not least it checks if there is a column default.
                // If all these checks are true this column can not be left empty and should be required
                // so we just add it to the neat array.

               $arr[] = $columnName;
            }
        }

        return $arr;
        // return our array.
    }

    /**
     * Validates a insert query
     *
     * @param InsertQuery $query
     * @param bool $thow if true this function will throw out its errors
     * @return bool true if valid false oterwise
     */
    private function validateInsertQuery(InsertQuery $query, bool $thow = false)
    {
        // Well this function is actually pretty usefull.
        // this is what we programmers do a lot we abstract away parts of code we could use 
        // many times into a neat little function witch makes the other code more readable to.

        // this function just checks if a inser query is valid with a bunch of if statments
        // most of them are easy to understand
        if(!$query->isValid()) {
            // does the query itself say its valid ( is everything set on the insertquery ?)
            // if not then go here and return false.
            
            if ($throw) {
                throw new DatabaseSmileScreenException('InsertQuery not valid. Set all parameters');
            }
            return false;
        }

        // The query itself thinks it's valid if we get here.
        // return statments are fun things. When a function returns it quits right on the spot.
        // since it's done it has returned something there is nothing more to do.
        // we can use this logic in if statements so i know that at this point the 
        // previous if statement could never have been triggered.

        if(!in_array($query->getTable(), $this->tables)) {
            // Here we just check if the table of the insert query is actully in the databse.
            // if not we again return false.
            if ($throw) {
                throw new DatabaseSmileScreenException('InsertQuery not valid. \'' . $query->getTable() . '\' table not found.');
            }
            return false;
        }

        $requiredColumns = $this->getTableRequiredColumns($query->getTable());
        // get the required columns for the querys table.
        
        if ($requiredColumns != array_intersect($requiredColumns, $query->getColumns())) {
            // here we use a funky function array_intersects.
            // What that does it gets the overlapping talbes between 2 arays
            // The requiredColumns should be the same as value array intersects here.
            // if they are not the same we know a required column is missing and we do the same as before
            // return false

            if ($throw) {
                throw new DatabaseSmileScreenException('InsertQuery not valid not all required columns are filled.');
            }
            return false;
        }

        if (!is_array($query->getValues()[0])) {
            // here qe just check if the first entry in the querys values is a array.
            // if so we assume there is only one entry we want to add to the database
            // but for we can do that we need to check if the amount of values in this set 
            // equals the same amount of columns 

            // we just check in a different way. 
            $return = count($query->getColumns()) == count($query->getValues());
            // what that line does is test if the columns is the same and stores the result a variable.
            if(!$return && $throw) {
                // here we return a error if $return is false and the $throw parameter is set.
                throw new DatabaseSmileScreenException('InsertQuery not amount of values and columns is different.');
            }

            return $return;
            // We just return the result. if false this query is still invalid if not it's valid
        }

        // We only get here if we want to add multiple entries in the database.
        // we quickly need to check if all these entries have the required amount of values 

        foreach ($query->getValues() as $values) {
            // lets check.
            if (count($values) != count($query->getColumns())) {
                // Its not the same so we still return false or a error
                if ($throw) {
                    throw new DatabaseSmileScreenException('InsertQuery not amount of values and columns is different.');
                }
                return false;  // All value groups whould have the specified columns
            }
        }

        return true; // all the tests have passed to this point so it can only be valid now.
    }

    /**
     * This fills a model with data from the database filtered by a Select query
     *
     * @param mixed $model
     * @param SelectQuery $where
     * @return array a array of the models it found in the database
     * @throws GenericSmileScreenException
     * @throws DatabaseSmileScreenException
     */
    public function modelsFromDatabase($model, SelectQuery $where)
    {
        // This method is not that complex either.
        // It fils a array of new instances of $model that meet the SelectQuerys requirements.
        // Do not it will also return a empty array if nothing is found

        if(!is_subclass_of($model, 'SmileScreen\Base\BaseModel')) {
            // Here we check if the model extends BaseModel cause it needs to.
            // BaseModel gives the model certain functions and critria that are required for this to method to work
            // so if this requirement is not met we will return a exception
            throw new GenericSmileScreenException('Model needs to extend BaseModel. Not be a instance of BaseModel.');
        }

        $modelTableName = $model->getDatabaseTable();
        // Get the database table that the model uses.

        $where->setTable($modelTableName);
        // We set the SelectQuery's table to this table

        // Here we handle fulltext searches
        if(!$where->isFullText()) {
            // Since the fulltext already has a * select we don't need to add it. 
            $where->select(['*']);
        }
        // We select everything from the table plus a count variable this is makes later logic easier 

        if (!in_array($modelTableName, $this->tables)) {
            // Here we just check quickly if the table even exists of at all.
            // if not we give a exception.
            throw new DatabaseSmileScreenException('Model ' . get_class($model) . ' with providing tablename \'' . $modelTableName . '\' could not be found in the database.');
        }

        $needFilling = $model->getAllDatabaseAttributes();
        // here we get the things that need to be filled by the database.

        $whereStatement = $where->getStatement();
        
        // We get the where statment from the SelectQuery
        // Get statment returns a array.
        // The first part is always a SQL query we use in the ->prepare function
        // the second is a array of values for the where that need to be passed to ->execute
        try {
            $fillStatment = $this->pdoObject->prepare($whereStatement[0]);
            
            if($where->isFullText()) {
                $fillStatment->bindValue(':matchvalue',
                    $where->getFullTextValue(), PDO::PARAM_STR);
            }
            
            // lets prepare the statement
            if(!$where->isFullText()) {
                // the statement is not full text.
                // we pass the needed where data to the execute
                $fillStatment->execute($whereStatement[1]);
            } else {
                echo "TEST";
                $fillStatment->execute();
            }
            // fill the statment with the data

            $results = $fillStatment->fetchAll(PDO::FETCH_ASSOC);
            // get all the data in a associative array ( google associative array if you want to know more! )

            if (count($results) == 0) {
                // the count value was zero we have no results 
                // returning a empty array is the right thing to do at this point
                return [];
            }

            $modelsArray = [];
            $className = get_class($model);
            // here we get the class name of the model that was given to this function
            // thats needed to generate the list of new instances

            for($i=0; $i<count($results); $i++) {
                // Lets us loop through the results
                $attributes = $results[$i];
                if($where->isFullText()) {
                    unset($attributes['score']);
                }
                // we get the attributes
                unset($attributes['cnt']);
                // we remove the count cause thats part of the model
                unset($attributes['created_on']);
                // we remove the created_on value cause that needs to be set by a function
                unset($attributes['updated_on']);
                // same for the updated_on

                $newModel = new $className($attributes, ModelStates::FROM_DATABASE);
                // we generate a new model given the attributes
                // and we also set the ModelState to FROM_DATABASe since the date fresh from the database
                if ($model->usesTimestamps()) {
                    // does the model use timestamps ? yes so we set them with ther apropriate functions
                    $newModel->setCreatedOn($results[$i]['created_on']);
                    $newModel->setUpdatedOn($results[$i]['updated_on']);
                }

                $modelsArray[] = $newModel;
                // add the model to the array and go on with the loop.
            }

            return $modelsArray; 
            // return the array so we can do stuff with it 

        } catch (\PDOException $e) {
            throw new DatabaseSmileScreenException('Could not fill model: '.$e->getMessage());
            // there was a problem filling the model so we throw a error 
        }

        return [];
    }

    /**
     * Runs a instert query to put stuff in the database
     *
     * @param InsertQuery $query the insert query
     * @return void
     */
    public function runInsertQuery(InsertQuery $query)
    {
        // this function just runs a insertQuery nothing else about it clean and simple 
        if (!$this->validateInsertQuery($query)) {
            // The insert query is not valid so we return false to indicate a error
            return false;
        }

        $sql = $query->getStatement();
        // we get the sql statment from the query.
        
        $sqlParameters = call_user_func_array('array_merge', [$query->getValues()]);
        // this might look weird but what we do here here is simple 
        // lets say you want to insert multiple values with the insert query 
        // that results a multidiminsonal sutch a array is to complex so we flatten it to a 
        // simple array with this function. 

        try {
            $insertStatement = $this->pdoObject->prepare($sql);
            // prepare the statment
            return $insertStatement->execute($sqlParameters);
            // execute the statment and return the result ( if it was succesfull )
        } catch (\PDOException $e) {
            throw new DatabaseSmileScreenException('Could not fill model: '.$e->getMessage());
        }

        return false;
        // this code should actually never be reached 
        // but if we do somehow get here it cant be good so we retrun false.
    }

    public function runDeleteQuery(DeleteQuery $query) 
    {
        if(!$query->isValid()) {
            return false; 
        }

        try {
            $sql = $query->getStatement();
            $deleteStatement = $this->pdoObject->prepare($sql);
            $deleteStatement->execute([ $query->getId() ]);
        } catch (PDOException $e) {
            return false; 
        }
    }

    /**
     * This function saves a model to the database 
     *
     * @param mixed $model the model we want to save
     * @return boolean true if everything went correclty
     */
    public function saveModelToDatabase($model)
    {
        if(!is_subclass_of($model, 'SmileScreen\Base\BaseModel')) {
            // again we need a model that extends BaseModel here
            throw new GenericSmileScreenException('Model needs to extend BaseModel. Not be a instance of BaseModel.');
        }

        $modelState = $model->getModelState();
        // here we get the current state of the moddel 
        $modelTableName = $model->getDatabaseTable();
        // here we get the table name the model uses
        $date = date('Y-m-d H:i:s');
        // here we save the current time in a variable used for filling
        // timestamps later.

		if(($modelState & (ModelStates::FROM_DATABASE | ModelStates::NOT_SAVED)) == 3) {
            // Model is from database and needs to be updated.
            
            $updateQuery = ((new UpdateQuery())->setTable($modelTableName));
            // here we get a new update query and we set the table to the correct one.
            $updateQuery->setIdField($model->getIdField());
            // here we set the id field of the update query to the models idField
            $updateQuery->setId($model->getId());
            // we set the id to the id of the model we are updating

            $updateColumns = $model->getAllDatabaseAttributes(false);
            // we get all the database atributes that need to be saved except the id
            $updateValues = $model->getAllDatabaseValues(false);
            // we get all the values for those attributes

            $realUpdateColumns = $updateColumns;
            $realUpdateValues = $updateValues;
            // here we copy these values to the values that go into the database
            // we do this cause a few values need to be added
            // for the actual database but the original values are used later 2

            if ($model->usesTimestamps()) {
                $realUpdateColumns[] = 'updated_on';
                $realUpdateValues[] = $date;
                // does the model use timestamps? yes so we add this value to the real insert query
            }

            $updateQuery->setColumns($realUpdateColumns);
            // we set the updatequerys columsn
            $updateQuery->setValues($realUpdateValues);
            // we set the values to

            $sql = $updateQuery->getStatement();
            // we get the updatequerys sql

            try {
                $updateStatement = $this->pdoObject->prepare($sql);
                // Lets prepare the sql
                $result = $updateStatement->execute($updateQuery->getValues(true));
                // here we execute the array with the values from the updatequery
                // we also store the results since its important to not update the model if sh*t did ot work
                if ($result) {
                    // Everying went fine we can update the model
                    
                    $model->setModelState(ModelStates::FROM_DATABASE);
                    // here we set the state of the model its now from the database and does not 
                    // require to be saved until its changed again.
                    if ($model->usesTimestamps()) {
                        // Does the model use timestamps? Yes so we update the updated_on value
                        $model->setUpdatedOn($date);
                    }

                    return true;
                } else {
                    return false; // Sorry the updated did not go through we return false.
                }
            } catch (\PDOException $e) {
                throw new DatabaseSmileScreenException('Could not update model: '.$e->getMessage());
            }
        }

        if(($modelState & ModelStates::NOT_SAVED) == 1) {
            // Model is not from database but needs to be saved.
            $insertQuery = ((new InsertQuery())->setTable($modelTableName));
            // since the model is not in the database we use a insertquery.
            // create a new insertquery and set the table to the models databse table

            $insertColumns = $model->getAllDatabaseAttributes(false);
            // we get all the stuff we need to insert
            $insertValues = $model->getAllDatabaseValues(false);
            // we get all the values we need to insert

            $realInsertColumns = $insertColumns;
            $realInsertValues = $insertValues;
            // we copy the values since we need to alter them but need to keep the original

            // Does the model use dates? If so we ad the required values to the insertQuery
            if ($model->usesTimestamps())
            {
                // the model uses timestamps so we set the required fields and add them
                $realInsertColumns[] = 'created_on';
                $realInsertColumns[] = 'updated_on';

                $realInsertValues[] = $date;
                $realInsertValues[] = $date;
            }

            $insertQuery->setColumns($realInsertColumns);
            // set the columns of the insert query
            $insertQuery->setValues($realInsertValues);
            // set the values of the insert query
            $this->runInsertQuery($insertQuery);
            // here we run the insertquery we got to insert the data of the new model

            // altho we are done inserting the data for the model
            // we run into problems if we just stop here 
            // if we dont add the new id field to the old model it wont know how to update in the future
            // this will cause problems if not resolved so we go on to select the new model 
            // and get its newly assigned id to update the model 

            $selectQuery = ((new SelectQuery())->setTable($modelTableName));
            // we make a new selectquery and set the database table to the models database table
            $selectQuery->select([$model->getIdField(). ' AS id']);
            // we only need to select the id so we get the id field but we alster the query a litle bit
            // we say 'AS id' this makes it easier to find the variable later
                
            $whereArray = [];
            // we make a new array in this array we store the array we can pass to the selectquerys where function
            foreach($insertColumns as $key => $attribute) {
                // we loop through all the variables we set using insertColumns
                if(is_null($insertValues[$key])) {
                    // if the variable is null we cant use it to compare stuff so we drop it by moving on
					          continue;
				        }
                $whereArray[$attribute] = ['=', $insertValues[$key]];
                // we add part of the where to the $whereArray
            }

            $selectQuery->where($whereArray, 'AND');
            // here we pass the where array to the select query but we also say to AND all the values together
            $selectStatement = $selectQuery->getStatement();
            // here we get the newly created statment 

            try {
                $whereStatement = $this->pdoObject->prepare($selectStatement[0]);
                // we prepare the statment
                $whereStatement->execute($selectStatement[1]);
                // execute the stament
                $results = $whereStatement->fetchAll(PDO::FETCH_ASSOC);
                // Fetch the result of witch there should only be one

                $id = $results[0]['id'];
                // We get the id from the restults

                // Here we update the model. We set it's state to FROM_DATABASE
                // And we set it's id to the id we just got by looking up its values
				$model->setId($id);
				$model->setModelState(ModelStates::FROM_DATABASE);

                // Does the model use timestamps? If so we set it to the date from earlier
                if ($model->usesTimestamps()) {
                    $model->setCreatedOn($date);
                    $model->setUpdatedOn($date);
                }

				return true; // Everything is good so we return true
            } catch (\PDOException $e) {
                // Error so we throw a error
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

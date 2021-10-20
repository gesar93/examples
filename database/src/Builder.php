<?php


namespace Dev\Database;


use Illuminate\Support\Facades\App;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Sequence;
use Tarantool\Mapper\Space;
use Dev\Tarantool\Connection;

class Builder
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected $connection;
    /**
     * The database connection instance.
     *
     * @var Space
     */
    protected $space;

    public static $defaultStringLength = 255;

    /**
     * Builder constructor.
     * @param Connection $connection
     */
    public function __construct()
    {
        $this->connection = app(Connection::class);
    }

    /**
     * Set the default string length for migrations.
     *
     * @param  int  $length
     * @return void
     */
    public static function defaultStringLength($length)
    {
        static::$defaultStringLength = $length;
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable(string $table) : bool
    {
        return $this->connection->getMapper()->getSchema()->hasSpace($table);
    }

    /**
     * @param string $table
     * @return $this
     */
    public function create(string $table){
        try {
            $this->space = $this->connection->getMapper()->getSchema()->createSpace($table);
        } catch (\Exception $e) {}

        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function table(string $table){
        try {
            $this->space = $this->connection->getMapper()->getRepository($table)->getSpace();
            $this->connection->getMapper()->getPlugin(Sequence::class); // Enable AutoIncrement
        } catch (\Exception $e) {}

        return $this;
    }

    /**
     * @param string $table
     * @return array|bool
     */
    public function dropIfExists(string $table){
        if($this->hasTable($table)){
            return $this->connection->getClient()->evaluate('return box.space.'.$table.':drop()');
        }
        return false;
    }

    /**
     * @param string $field
     * @param string $type
     * @param bool $unique
     * @param bool $autoincrement
     * @param bool $index
     * @param array $options
     * @return $this
     * @throws \Exception
     */
    public function addColumn(string $field, string $type, $unique = false, $autoincrement = false, $index = false, $options = []){
        if($this->space && !$this->space->hasProperty($field)){
            $indexOptions = [
                'fields' => $field,
                'unique' => $unique
            ];
            if($autoincrement){
                $indexOptions['sequence'] = $this->space->getName();
                    /**
                     * @var $sequence Sequence
                     * */
                $this->connection->getClient()->evaluate("box.schema.sequence.create('".$indexOptions['sequence']."', {if_not_exists=true})"); // Enable AutoIncrement

            }
            $this->space->addProperty($field, $type, $options);
            if($index && !(isset($options['default']) && $options['default'] === 0))
                $this->space->addIndex($indexOptions);
        }

        return $this;
    }


    /**
     * @return $this
     * @throws \Exception
     */
    public function timestamps(){
        if($this->space){
            $this->addColumn('created_at', 'unsigned');
            $this->addColumn('updated_at', 'unsigned');
        }

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     * @throws \Exception
     */
    public function dropColumn(string $field){
        if($this->space && $this->space->hasProperty($field)){
            if(in_array($field, array_column($this->space->getIndexes(), 'name'))) $this->space->removeIndex($field);
            $this->space->removeProperty($field);
        }

        return $this;
    }

    /**
     * @param array $fields
     * @param bool $unique
     * @return $this
     */
    public function addMultiIndex(array $fields, bool $unique = false){
        if($this->space){
            $indexOptions = [
                'fields' => $fields,
                'unique' => $unique
            ];
            $this->space->addIndex($indexOptions);
        }

        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function dropMultiIndex(array $fields){
        if($this->space){
            $field = implode('_', $fields);
            if(in_array($field, array_column($this->space->getIndexes(), 'name'))) $this->space->removeIndex($field);
        }

        return $this;
    }


}

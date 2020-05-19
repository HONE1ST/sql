<?php


namespace Dispirited\Mysql\Basic;


use Dispirited\Basic\Charset;
use Dispirited\Basic\Collate;
use Dispirited\Basic\Engine;
use Dispirited\Basic\Field;
use Dispirited\Basic\Table;
use Dispirited\Mysql\Facades\Facade;

final class MTable implements Table
{
    /**
     * @var Engine $_engine
     */
    protected  $_engine;

    /**
     * @var string $_name
     */
    protected  $_name;

    /**
     * @var array $_fields
     */
    private  $_fields;

    /**
     * @var string $_comment
     */
    private  $_comment;

    /**
     * 字符集
     * @var Charset $_charset
     */
    protected  $_charset = null;

    /**
     * @var Collate $_collate
     */
    protected  $_collate = null;

    public function __construct(string $name, Engine $engine)
    {
        $this->_name = $name;
        $this->_engine = $engine;
    }

    /**
     * @param Field ...$args
     * @return $this|Table
     */
    public function add(...$args): Table
    {
        foreach ($args as $f) {
            $this->_fields[$f->getName()] = $f;
        }
        return $this;
    }

    public function addBase(): Table
    {
        $this->add(
            Facade::Int("id", MIndex::primaryKey(), 11)->auto()->comment("活动表的id"),
            Facade::Datetime("created_at")->default("current_timestamp")->comment("创建时间"),
            Facade::Datetime("updated_at")->default("current_timestamp")->onUpdate()->comment("创建时间")
        );
        return $this;
    }

    public function addBaseWithDelete(): Table
    {
        $this->add(
            Facade::Int("id", MIndex::primaryKey(), 11)->auto()->comment("活动表的id"),
            Facade::TinyInt("is_delete")->length(1)->comment("0未删除 1已删除"),
            Facade::Datetime("created_at")->default("current_timestamp")->comment("创建时间"),
            Facade::Datetime("updated_at")->default("current_timestamp")->onUpdate()->comment("创建时间")
        );
        return $this;
    }

    public function comment(string $comment): Table
    {
        $this->_comment = $comment;
        return $this;
    }

    public function filter(string ...$args): string
    {
        $keys = array_keys($this->_fields);
        $result = array_reduce($keys, function ($result, $item) use ($args, $keys) {
            if (!in_array($item, $args)) {
                /**
                 * @var $f Field
                 */
                $f = $this->_fields[$item];
                if (array_search($item, $keys) == 0) {
                    $result[] = sprintf($f->alter(), "first", "");
                } else if (array_search($item, $keys) == count($keys) - 1) {
                    $result[] = sprintf($f->alter(), "", "");
                } else {
                    $result[] = sprintf($f->alter(), "after", $keys[array_search($item, $keys) - 1]);
                }
            }
            return $result;
        }, []);
        if (!empty($result)) {
            return sprintf("ALTER TABLE `%s` %s", $this->_name, implode(",\r\n", $result));
        }
        return false;

    }

    public function __toString()
    {
        $sql = array_reduce($this->_fields, function ($result, $item) {
            if ($result) {
                $result .= ",\r\n" . $item;
            } else {
                $result = $item;
            }
            return $result;
        },);

        return implode("\r\n", [
            sprintf("create table `%s` (", $this->_name),
            sprintf("%s", $sql),
            implode(" ", [
                sprintf(") engine %s", (string)$this->_engine),
                $this->_comment ? sprintf("comment '%s'", $this->_comment) : "",
                is_null($this->_charset) ? "" : sprintf("CHARACTER set %s %s",
                    (string)$this->_charset,
                    !is_null($this->_collate) ? sprintf("COLLATE %s", $this->_collate) : ""
                ),
            ]),
        ]);
    }

    /**
     * @param Charset $charset
     * @return Table
     */
    public function charset($charset): Table
    {
        $this->_charset = $charset;
        return $this;
    }

    /**
     * @param Collate $collate
     * @return Table
     */
    public function collate($collate): Table
    {
        $this->_collate = $collate;
        return $this;
    }
}
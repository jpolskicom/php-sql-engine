<?php

namespace engine;

use engine\config;

// Jacek Polski 2021

class Database
{
    private $conn = false;

    private static $instance;

    protected $model_data;

    protected $fillable_data;

    protected $protected_data;

    protected $primary_key;

    protected $table_name;

    public $sql_where_clauses;

    public function __construct()
    {
        $this->conn = mysqli_connect(config::get('sql_host'), config::get('sql_user'), config::get('sql_password'), config::get('sql_db'));
        $this->conn->set_charset("utf8");

        $this->fillable_data = [];
        $this->protected_data = [];

        $this->sql_where_clauses = ["=", ">", "<", ">=", "<=", "<>", "!=", "BETWEEN", "LIKE", "NOT LIKE", "IN", "AND" , "OR"];
    }

    // set primary key
    public function key($data)
    {
        $this->primary_key = $data;
        return $this;
    }

    // set table structure
    public function model($data)
    {
        $this->model_data = $data;
        return $this;
    }

    // fill empty fields
    public function fillable($data)
    {
        $this->fillable_data = $data;
        return $this;
    }

    // set fields with can not be changed
    public function protected($data)
    {
        $this->protected_data = $data;
        return $this;
    }

    // set table name
    public function table($data)
    {
        $this->table_name = $data;
        return $this;
    }

    // make query from string
    public function query($query)
    {
        return mysqli_query($this->conn, $query);
    }

    // fetch results
    public function fetch($data)
    {
        $rows = [];
        while ($row = mysqli_fetch_assoc($data)) {
            $rows[] = $row;
        }
        return  $rows;
    }

    // close SQL connection
    public function close()
    {
        mysqli_close($this->conn);
    }

    public function where_clause($data){

      if(!isset($data)) {
        return;
      }

      $where_values = [];
      foreach ($data as $key => $value) {

          if (in_array($value[0], array_merge(array_keys($this->model_data), [$this->primary_key]))) {

              if (!in_array($value[1], $this->sql_where_clauses)) {
                  return;
              } else {
                  $value[0] = " `".$this->table_name."`.`" . $value[0] . "` ";
                  $value[2] = is_numeric($value[2]) ? $value[2] : "'" . $value[2] . "'";
                  $where_values[] = implode(' ', $value);
              }

          } else {

              $where_values[] = $value[0];

          }

      }

      return "WHERE " . implode(' ', $where_values);

    }

    // return results
    public function get($data)
    {
        if (!isset($data['fields']) && !isset($data['join'])) {
            $query = "SELECT * FROM";
        } else {
            if (!isset($data['join'])) {
                $passed_keys = array_intersect(array_merge(array_keys($this->model_data), [$this->primary_key]), $data['fields']);
                $query = "SELECT " . implode(",", $passed_keys) . " FROM";
            } else {
                if (isset($data['fields'])) {
                    $passed_keys = array_intersect(array_merge(array_keys($this->model_data), [$this->primary_key]), $data['fields']);
                    $fields = [];
                    foreach ($passed_keys as $key) {
                        array_push($fields, "`".$this->table_name."`.`".$key."` AS `".$key."` ");
                    }
                    $query = "SELECT " .  implode(", ", $fields).",";
                } else {
                    $query = "SELECT * ,";
                }

                foreach ($data['join'] as $key => $value) {
                    foreach ($value['fields'] as $field_key => $field) {
                        $data['join'][$key]['fields'][$field_key] = $value['table'].".".$field." AS ".$value['table']."_".$field;
                    }
                    $query .= implode(", ", $data['join'][$key]['fields']);
                }

                $query .= " FROM";
            }
        }

        $query .= " `" . $this->table_name . "` ";

        if (!$data) {
            return 0;
        };

        if (isset($data['join'])) {
            foreach ($data['join'] as $key => $value) {
                $query .= "INNER JOIN `".$value['table']."` ON ".$this->table_name.".".$value['from']." = ".$value['table'].".".$value['by']." ";
            }
        }

        $query .= $this->where_clause($data['where']);


        if (isset($data['search'])) {
            if (!isset($data['where'])) {
                $query .= "WHERE ";
            };
            foreach ($data['search'] as $key => $value) {
                if (in_array($value[0], array_merge(array_keys($this->model_data), [$this->primary_key]))) {
                    $query .= $value[0]." LIKE '%".$value[1]."%' ";
                }
                if (isset($value[2]) && in_array($value[2], $this->sql_where_clauses)) {
                    $query .= $value[2]." ";
                }
            }
        }

        if (isset($data['order_by']) && isset($data['order'])) {
            $query .= " ORDER BY `".$this->table_name."`.`" . $data['order_by'] . "` " . $data['order'];
        }

        if (isset($data['limit'])) {
            $query .= " LIMIT " . $data['limit'];
        }

        if (isset($data['offset'])) {
            $query .= " OFFSET " . $data['offset'];
        }

        $results = $this->query($query);

        return $results ? $this->fetch($results) : [];
    }

    // create new data row
    public function create($data)
    {
        if (!$data) {
            return 0;
        };

        $data = array_merge($this->fillable_data, $data);

        $passed_keys = array_intersect(array_keys($this->model_data), array_keys($data));

        if (!$passed_keys) {
            return 1;
        };

        $passed_values = [];
        foreach ($passed_keys as $key) {
            $passed_values[] = is_numeric($data[$key]) ? $data[$key] : "'" . $data[$key] . "'";
        }


        foreach ($passed_keys as $key => $value) {
            $passed_keys[$key] = "`" . $value . "`";
        }

        $query = "INSERT INTO `" . $this->table_name . "` (" . implode(', ', $passed_keys) . ")  VALUES (" . implode(', ', $passed_values) . ")";
        $this->query($query);



        return ["id" => $this->conn->insert_id];
    }

    // update existing data row
    public function update($data)
    {
        if (!$data) {
            return 0;
        };

        $query = "UPDATE `" . $this->table_name . "` SET ";

        if (isset($data['values'])) {
            $data['values'] = array_merge($this->fillable_data, $data['values']);


            $passed_keys = array_intersect(array_keys($this->model_data), array_keys($data['values']));

            if (array_intersect($this->protected_data, $passed_keys)) {
                return 1;
            }

            $query_values = [];
            foreach ($passed_keys as $key) {
                $value = is_numeric($data['values'][$key]) ? $data['values'][$key] : "'" . $data['values'][$key] . "'";
                $query_values[] = "`" . $key . "` = " . $value;
            }


            $query .= implode(', ', $query_values) . " ";
        } else {
            return 2;
        }

        if (isset($data['where'])) {
          $query .= $this->where_clause($data['where']);
        } else {
            return 3;
        }

        $this->query($query);

        return ["id" => true];
    }

    // delete data row
    public function delete($data)
    {
        $query = "DELETE FROM `" . $this->table_name . "` ";

        if (isset($data['where'])) {
          $query .= $this->where_clause($data['where']);
        } else {
            return 1;
        }

        $this->query($query);

        return true;
    }

    // count rows
    public function count($data)
    {
        $query = "SELECT COUNT(" . $this->primary_key . ") FROM `" . $this->table_name . "` ";

        $query .= $this->where_clause($data['where']);

        $results = $this->query($query);

        return $results ? $this->fetch($results)[0]["COUNT(" . $this->primary_key . ")"] : [];
    }

    // create table from schema
    public function create_db()
    {
        $query = "CREATE TABLE `" . $this->table_name . "` (";

        $query_values = [];
        foreach ($this->model_data as $key => $value) {
            $query_values[] = "`" . $key . "` " . $value;
        }

        $query .= implode(', ', $query_values) . " ";

        if ($this->primary_key) {
            $query .= ",`" . $this->primary_key . "` INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (`" . $this->primary_key . "`))";
        }

        $query .= "ENGINE=InnoDB DEFAULT CHARSET=utf8";

        return $this->query($query);
    }


    public static function getInstance()
    {
        // // Check is $_instance has been set
        // if (!isset(self::$instance)) {
        //     // Creates sets object to instance
        //     self::$instance = new Database();
        // }

        // // Returns the instance
        // return self::$instance;
        return new Database();
    }
}

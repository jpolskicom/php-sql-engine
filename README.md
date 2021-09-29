# php-sql-engine
Simple SQL queries in PHP. 
Example of in class implementation.

## Create DB Model

key() sets primary key\
model() sets table structure ( "field_name" => "SLQ properties")\
fillable() sets default values in case of empty\
protected() will block update of field\

```
        $this->db = Database::getInstance();

        $this->db->table(
            'books'
        )->key(
            'id'
        )->model([
            "name" => "varchar(255) NOT NULL",
            "author_id" => "int(6) NOT NULL",
            "pages" => "int(11) NOT NULL",
            "price" => "int(11) NOT NULL",
            "updated_at" => "TIMESTAMP",
            "created_at" => "DATETIME"
        ])->fillable([
            "pages" => 0,
            "price" => 0,
            "updated_at" => date('Y-m-d H:i:s'),
        ])->protected([
            "created_at"
        ]);
        
```

## Create table from model

Create new table from model above

```
        $this->db->create_db();
```

## Insert data

Insert new row data

```

      $params = [
            "name" => "Potęga Teraźniejszości",
            "author_id" => 1,
            "pages" => 256,
            "created_at" => date('Y-m-d H:i:s')
      ];
      
      $this->db->create($params);

```

## Update data

Updates values in matched rows

```

      $params = [
            "values" => [
              "price" => 56
            ],
            "where" => [
              ["name","=","Potęga Teraźniejszości"]
            ]
      ];
      
      $this->db->update($params);

```

## Count

Counts all rows that matches

```

      $params = [
            "where" => [
              ["price",">",20],
              ["AND"],
              ["pages","!=",0]
            ]
      ];
    
      $this->db->count($data);
  
```

## Delete

Deletes rows that match where clause

```
      $params = [
        "where" => [
          ["name","LIKE","Noname"]
        ]
      ];

      $this->db->delete($params);
      
```

## Get data

Returns results from query. You can select with fields you want to have and define multiple where clauses

```
      $params = [
        "fields" => [
          "name",
          "id"
        ],
        "where" => [
          ["price", "<", 0]
        ]
      ];

      $results = $this->db->get($params);
      
```

## INNER JOIN

Allows to return mapped values from another table by same fields

```

      $params = [
        'join' => [
          [
              'table' => 'authors',
              'by' => 'id',
              'from' => 'author_id',
              'fields' => ['name','surname']
          ]
        ]
      ];

      $results = $this->db->get($params);
      
      
```

## Search in

Using this field You can find all data that includes value

```
      $params = [
        "search" => [
          ["name","Potęga"],
          ["OR"],
          ["name","Teraźniejszości"]
        ]
      ];

      $results = $this->db->get($params);
      

```


<?php

namespace App\Models;

use CodeIgniter\Model;

class MFile extends Model
{
    protected $dbs;
    protected $builder;
    protected $table      = 'msfiles';
    protected $primaryKey = 'fileid';
    protected $returnType = 'array';
    protected $allowedFields = [
        'filename',
        'filerealname',
        'filedirectory'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->dbs     = db_connect();
        $this->builder = $this->dbs->table($this->table . ' f');
    }

    public function store($data)
    {
        return $this->builder->insert($data);
    }

    public function edit($column, $value)
    {
        return $this->builder->where("f.fileid", $column)->update($value);
    }

    public function destroy($column, $value)
    {
        return $this->builder->delete([$column => $value]);
    }
}
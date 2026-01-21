<?php

namespace App\Models;

use CodeIgniter\Model;

class MUom extends Model
{
    protected $dbs;
    protected $table = 'msuom';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uomnm',
        'description'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->dbs = db_connect();
        $this->builder = $this->dbs->table($this->table);
    }

    public function getAll()
    {
        return $this->builder->get()->getResultArray();
    }

    public function store($data)
    {
        return $this->builder->insert($data);
    }

    public function edit($data, $id)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function destroy($column, $value)
    {
        return $this->builder->delete([$column => $value]);
    }

    public function searchUom($search, $limit = 10)
    {
        $builder = $this->like('uomnm', $search);
        return $builder->findAll($limit);
    }
}

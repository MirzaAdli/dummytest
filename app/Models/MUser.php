<?php

namespace App\Models;

use CodeIgniter\Model;

class MUser extends Model   
{
    protected $dbs;
    protected $table = 'msuser';
    protected $primaryKey = 'id';
    protected $allowedFields = [
    'fullname','username','email','telp','filepath',
    'createddate','createdby','updateddate','updatedby'
    ];

    public function __construct()
    {
        parent::__construct(); // panggil constructor Model
        $this->dbs = db_connect();
        $this->builder = $this->dbs->table($this->table);
    }

    public function searchable()
    {   
        return [
            null,
            "username",
            "fullname",
            "email",
            null,
            null,
        ];
    }

    public function datatable()
    {
        return $this->builder;
    }

    public function getByName($name)
    {
        return $this->builder->where("lower(username)", strtolower($name))->get()->getRowArray();
    }

    public function getOne($id)
    {
        return $this->builder->where("id", $id)->get()->getRowArray();
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
}

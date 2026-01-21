<?php

namespace App\Models;

use CodeIgniter\Model;

class MCustomer extends Model
{
    protected $db;
    protected $table = 'mscustomer';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'customername',
        'address',
        'phone',
        'email',
        'filepath',
        'createddate',
        'createdby',
        'updateddate',
        'updatedby'
    ];

    public function searchable()
    {
        return [
            null,
            "customername",
            "address",
            "phone",
            "email",
            "filepath",
            null,
            null,
        ];
    }

    public function datatable()
    {
        return $this->builder();
    }

    public function getByName($name)
    {
        return $this->find($name);
    }

    public function getOne($customerid)
    {
        return $this->find($customerid);
    }

    public function store($data)
    {
        return $this->insert($data);
    }

    public function edit($data, $id)
    {
        return $this->update($id, $data);
    }

    public function destroy($column, $value)
    {
        return $this->where($column, $value)->delete();
    }

    public function searchCustomer($search, $limit = 10)
    {
        return $this->like('customername', $search, 'both', null, true)
            ->findAll($limit);
    }
}

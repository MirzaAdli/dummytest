<?php

namespace App\Models;

use CodeIgniter\Model;

class MProduct extends Model
{
    protected $table = 'msproduct';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'productname',
        'category',
        'price',
        'stock',
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
            "productname",
            "category",
            "price",
            "stock",
            null,
            null,
            null,
        ];
    }

    public function datatable()
    {
        return $this->builder();
    }

    public function getAll()
    {
        return $this->findAll();
    }

    public function getOne($id)
    {
        return $this->find($id);
    }

    public function store($data)
    {
        return $this->insert($data);
    }

    public function edit($id, $data)
    {
        return $this->update($id, $data);
    }

    public function destroy($column, $value)
    {
        return $this->where($column, $value)->delete();
    }

    public function getByName($name)
    {
        return $this->where('LOWER(productname)', strtolower($name))->first();
    }

    public function getOneBy($column, $value)
    {
        return $this->where($column, $value)->first();
    }

    public function searchProduct($search, $limit = 10)
    {
        return $this->like('productname', $search)
            ->findAll($limit);
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class MSalesOrderDetail extends Model
{
    protected $dbs;
    protected $builder;
    protected $table      = 'trsalesorderdt';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'headerid',
        'productid',
        'uomid',
        'qty',
        'price',
        'createddate',
        'createdby',
        'updateddate',
        'updatedby',
        'isactive'
    ];

    public function __construct()
    {
        parent::__construct(); // panggil constructor Model
        $this->dbs     = db_connect();
        $this->builder = $this->dbs->table($this->table);
    }

    public function datatable()
    {
        $headerid = service('request')->getPost('headerid'); // ambil dari ajax.data

        $builder = $this->dbs->table('trsalesorderdt d')
            ->select('d.*, p.productname, u.uomnm')
            ->join('msproduct p', 'p.id = d.productid', 'left')
            ->join('msuom u', 'u.id = d.uomid', 'left');

        if (!empty($headerid)) {
            $builder->where('d.headerid', $headerid);
        }
        return $builder;
    }

    public function getAllByHeader($headerid)
    {
        return $this->builder->where('headerid', $headerid)->get()->getResultArray();
    }

    public function getDetailsByHeader($headerid)
    {
        return $this->where('headerid', $headerid)->findAll();
    }

    public function getOne($id)
    {
        return $this->builder->where('id', $id)->get()->getRowArray();
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

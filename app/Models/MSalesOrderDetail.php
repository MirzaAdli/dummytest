<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

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

    // protected $sales_id;

    // public function setSalesId($sales_id)
    // {
    //     echo $this->sales_id;
    //     die;
    //     $this->sales_id = $sales_id;
    //     return $this;
    // }

    public function __construct()
    {
        parent::__construct(); // panggil constructor Model
        $this->dbs     = db_connect();
        $this->builder = $this->dbs->table($this->table . ' d');
    }

    public function searchable()
    {
        return [
            null,
            'p.productname',
            'u.uomnm',
            'd.qty',
            'd.price',
            null
        ];
    }


    public function datatable($sales_id = null, $order = [])
    {
        $x = $this->builder->select('d.*, p.productname, u.uomnm')
            ->join('msproduct p', 'p.id = d.productid', 'left')
            ->join('msuom u', 'u.id = d.uomid', 'left');

        if (!empty($sales_id)) {
            $x->where('d.headerid', $sales_id);
        }

        if (!empty($order['columnName'])) {
            $x->orderBy($order['columnName'], $order['columnOrder']);
        } else {
            $x->orderBy('d.id', 'asc');
        }

        return $x;
    }

    public function getDetail($column = null, $value = null)
    {
        $builder = $this->datatable();

        if (!empty($column) && !empty($value)) {
            $builder->where($column, $value);
        }

        return $builder;
    }

    public function store($data)
    {
        return $this->builder->insert($data);
    }

    public function edit($id, $data)
    {
        return $this->builder
            ->where('d.id', $id)
            ->update($data);
    }

    public function destroy($column, $value)
    {
        return $this->builder
            ->delete([$column => $value]);
    }
}

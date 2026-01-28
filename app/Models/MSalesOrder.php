<?php

namespace App\Models;

use CodeIgniter\Model;

class MSalesOrder extends Model
{
    protected $dbs;
    protected $builder;
    protected $table      = 'trsalesorderhd';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'transcode',
        'transdate',
        'customerid',
        'grandtotal',
        'description',
        'createdby',
        'createddate',
        'updatedby',
        'updateddate',
        'isactive'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->dbs     = db_connect();
        $this->builder = $this->dbs->table($this->table . ' h');
    }

    public function searchable()
    {
        return [
            null,
            'transcode',
            'transdate',
            'customername',
            'grandtotal',
            'description',
        ];
    }

    public function datatable($customer_id = null, $order = [])
    {
        $x = $this->builder
            ->select('h.*, c.customername')
            ->join('mscustomer c', 'c.id = h.customerid', 'left');

        if (!empty($customer_id)) {
            $x->where('h.customerid', $customer_id);
        }

        if (!empty($order['columnName'])) {
            $x->orderBy($order['columnName'], $order['columnOrder']);
        } else {
            $x->orderBy('h.id', 'asc');
        }

        return $x;
    }

        // public function getAll()
        // {
        //     return $this->datatable()
        //         ->get()
        //         ->getResultArray();
        // }

        // public function getOne($id)
        // {
        //     return $this->datatable()
        //         ->where('h.id', $id)
        //         ->get()
        //         ->getRowArray() ?? [];
        // }

    public function getHeader($column = null, $value = null)
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

    public function edit($column, $data)
    {
        return $this->builder->where("h.id", $column)->update($data);
    }

    public function destroy($column, $value)
    {
        return $this->builder->delete([$column => $value]);
    }
}

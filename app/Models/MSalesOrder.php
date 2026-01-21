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
        $this->builder = $this->dbs->table($this->table);
    }

    public function searchable()
    {
        return [
            null,
            "transcode",
            "transdate",
            "customername",
            "grandtotal",
            "description",
        ];
    }

    public function datatable()
    {
        return $this->dbs->table('trsalesorderhd')
            ->select("trsalesorderhd.*, mscustomer.customername")
            ->join("mscustomer", "mscustomer.id = trsalesorderhd.customerid", "left");
    }

    public function getAll()
    {
        return $this->builder->get()->getResultArray();
    }

    public function getOne($id)
    {
        return $this->builder->where("id", $id)->get()->getRowArray();
    }

    public function store($data)
    {
        return $this->builder->insert($data);
    }

    public function edit($id, $data)
    {
        return $this->builder->update($data, ['id' => $id]);
    }

    public function destroy($column, $value)
    {
        return $this->builder->delete([$column => $value]);
    }
}

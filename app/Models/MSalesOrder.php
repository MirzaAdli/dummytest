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
            'h.transdate',
            'customername',
            'grandtotal',
            'description',
        ];
    }

    public function datatable($params = [])
    {
        $x = $this->builder
            ->select('h.*, c.customername')
            ->join('mscustomer c', 'c.id = h.customerid', 'left');

        // Filter tanggal
        if (!empty($params['dateFrom']) && !empty($params['dateTo'])) {
            $x->where('h.transdate >=', $params['dateFrom'])
                ->where('h.transdate <=', $params['dateTo']);
        } elseif (!empty($params['dateFrom'])) {
            $x->where("h.transdate >=", $params['dateFrom']);
        } elseif (!empty($params['dateTo'])) {
            $x->where("h.transdate <=", $params['dateTo']);
        }

        // Filter customer
        if (!empty($params['customerid'])) {
            $x->where('h.customerid', $params['customerid']);
        }

        // Order
        if (!empty($params['columnName'])) {
            $x->orderBy($params['columnName'], $params['columnOrder']);
        } else {
            $x->orderBy('h.id', 'asc');
        }

        return $x;
    }

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

    public function findCustomerId(string $customername): ?int
    {
        $row = $this->dbs->table('mscustomer')
            ->select('id')
            ->where('customername', $customername)
            ->get()
            ->getRow();

        return $row ? (int) $row->id : null;
    }

    public function existsByTranscode($transcode)
    {
        return $this->where('transcode', $transcode)->countAllResults() > 0;
    }
}

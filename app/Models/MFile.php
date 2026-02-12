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
        'filedirectory',
        'created_date',
        'created_by',
        'update_date',
        'update_by',
        'isactive'
    ];

    public function searchable()
    {
        return [
            null,
            "filerealname",
            "filedirectory",
            "created_date",
            null,
        ];
    }

    public function __construct()
    {
        parent::__construct();
        $this->dbs     = db_connect();
        $this->builder = $this->dbs->table($this->table . ' f');
    }

    public function datatable()
    {
        return $this->builder
            ->select('f.*, u.fullname AS created_name, u2.fullname AS updated_name')
            ->join('msuser u', 'u.id = f.created_by', 'left')
            ->join('msuser u2', 'u2.id = f.update_by', 'left')
            ->orderBy('f.fileid', 'asc');
    }

    public function getOne($fileid)
    {
        return $this->builder->where("f.fileid", $fileid)->get()->getRowArray();
    }

    public function store($data)
    {
        return $this->builder->insert($data);
    }

    public function edit($data, $id)
    {
        return $this->builder->where('fileid', $id)->update($data);
    }

    public function destroy($id)
    {
        return $this->builder->where('fileid', $id)->delete();
    }
}

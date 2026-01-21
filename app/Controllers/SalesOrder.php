<?php

namespace App\Controllers;

use App\Models\MSalesOrder;
use App\Models\MSalesOrderDetail;
use App\Models\MUom;
use App\Models\MCustomer;
use App\Models\MProduct;
use Exception;
use App\Helpers\Datatables\Datatables;
use App\Controllers\BaseController;


class SalesOrder extends BaseController
{
    protected $salesModel;
    protected $salesDetailModel;
    protected $customerModel;
    protected $uomModel;
    protected $productModel;
    protected $bc;
    protected $db;

    public function __construct()
    {
        $this->salesModel = new MSalesOrder();
        $this->salesDetailModel = new MSalesOrderDetail();
        $this->customerModel = new MCustomer();
        $this->uomModel = new MUom();
        $this->productModel = new MProduct();
        $this->db = \Config\Database::connect();
        $this->bc = [
            [
                'Setting',
                'Sales Order',
            ]
        ];
    }

    public function index()
    {
        return view('master/salesorder/v_salesorder', [
            'title' => 'Sales Order',
            'akses' => null,
            'breadcrumb' => $this->bc,
            'section' => 'Setting Sales Order',
        ]);
    }

    public function customerList()
    {
        $search = $this->request->getPost('search');
        $builder = $this->customerModel;
        if (!empty($search)) {
            $builder = $builder->like('customername', $search);
        }
        $items = $builder->findAll(10);

        $results = array_map(fn($c) => [
            'id'   => $c['id'],
            'text' => $c['customername']
        ], $items);

        return $this->response->setJSON(['items' => $results]);
    }

    public function productList()
    {
        $search = $this->request->getPost('search');
        $builder = $this->productModel;
        if (!empty($search)) {
            $builder = $builder->like('productname', $search);
        }
        $items = $builder->findAll(10);

        $results = array_map(fn($p) => [
            'id'    => $p['id'],
            'text'  => $p['productname'],
            'price' => $p['price']
        ], $items);

        return $this->response->setJSON(['items' => $results]);
    }

    public function uomList()
    {
        $search = $this->request->getPost('search');
        $builder = $this->uomModel;
        if (!empty($search)) {
            $builder = $builder->like('uomnm', $search);
        }
        $items = $builder->findAll(10);

        $results = array_map(fn($u) => [
            'id'   => $u['id'],
            'text' => $u['uomnm']
        ], $items);

        return $this->response->setJSON(['items' => $results]);
    }


    public function datatable()
    {
        $table = Datatables::method([MSalesOrder::class, 'datatable'], 'searchable')
            ->make();


        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning' onclick=\"modalForm('Update SalesOrder - " . $db->transcode . "', 'modal-lg', '" . getURL('salesorder/form/' . encrypting($db->id)) . "', {identifier: this})\"><i class='bx bx-edit-alt'></i></button>";
            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger' onclick=\"modalDelete('Delete SalesOrder - " . $db->transcode . "', {'link':'" . getURL('salesorder/delete') . "', 'id':'" . encrypting($db->id) . "', 'pagetype':'table'})\"><i class='bx bx-trash'></i></button>";

            return [
                $no,
                $db->transcode,
                $db->transdate,
                $db->customername,
                number_format($db->grandtotal, 0, ',', '.'),
                $db->description,
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });
        $table->toJson();
    }

    public function detaildatatable($headerid = null)
    {
        if (empty($headerid)) {
            // return kosong kalau header belum ada
            return $this->response->setJSON([
                'draw' => intval($this->request->getPost('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        $table = Datatables::method([MSalesOrderDetail::class, 'datatable'], 'searchable')
            ->make($headerid);

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning'
                onclick=\"editDetail('{$db->id}','{$db->productid}','{$db->uomid}','{$db->qty}','{$db->price}')\">
                <i class='bx bx-edit'></i></button>";

            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger'
                onclick=\"deleteDataDt(this, '" . encrypting($db->id) . "')\">
                <i class='bx bx-trash'></i></button>";

            return [
                $no,
                $db->productname,
                $db->uomnm,
                number_format($db->qty, 0, ',', '.'),
                number_format($db->price, 0, ',', '.'),
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });

        return $table->toJson();
    }

    public function forms($id = '')
    {
        $form_type = (empty($id) ? 'add' : 'edit');

        // default row header
        $row = [
            'id'         => '',
            'transcode'  => '',
            'transdate'  => date('Y-m-d'),
            'customerid' => '',
            'description' => ''
        ];
        $details = [];

        if (!empty($id)) {
            $id = decrypting($id);
            $row = $this->salesModel->find($id) ?? $row;

            // ambil detail kalau edit
            $details = $this->salesDetailModel->getByHeader($id);
        }

        // data master
        $customers = $this->customerModel->findAll();
        $products  = $this->productModel->findAll();
        $uoms      = $this->uomModel->findAll();

        // kirim ke view
        $dt['view'] = view('master/salesorder/v_form', [
            'form_type' => $form_type,
            'row'       => $row,
            'headerid'  => $row['id'],
            'customers' => $customers,
            'products'  => $products,
            'uoms'      => $uoms,
            'details'   => $details
        ]);
        $dt['csrfToken'] = csrf_hash();
        echo json_encode($dt);
    }


    public function addData()
    {
        $res = array();
        $transcode   = $this->request->getPost('transcode');
        $transdate   = $this->request->getPost('transdate');
        $customerid  = $this->request->getPost('customerid');
        $description = $this->request->getPost('description');

        $this->db->transBegin();
        try {

            // Validasi wajib isi
            if (empty($transcode))   throw new \Exception("Transcode dibutuhkan!");
            if (empty($transdate))   throw new \Exception("Transdate dibutuhkan!");
            if (empty($customerid))  throw new \Exception("Customername dibutuhkan!");

            // Cek duplikat transcode
            $row = $this->salesModel->where('transcode', $transcode)->first();
            if (!empty($row)) throw new \Exception("Transcode sudah terdaftar!");

            // Insert ke header
            $this->salesModel->store([
                'transcode'   => $transcode,
                'transdate'   => $transdate,
                'customerid'  => $customerid,
                'grandtotal'  => 0,
                'description' => $description ?? '-',
                'createddate' => date('Y-m-d H:i:s'),
                'createdby'   => session()->get('id'),
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby'   => session()->get('id'),
                'isactive'    => true
            ]);
            $headerid = $this->db->insertID();
            $res = [
                'sukses' => 1,
                'pesan'  => 'Sukses menambahkan Sales Order baru',
                'dbError' => $this->db->error()
            ];
            $this->db->transCommit();
        } catch (\Exception $e) {
            $res = [
                'sukses' => 0,
                'pesan'  => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => $this->db->error()
            ];
            $this->db->transRollback();
        }

        echo json_encode($res);
    }

    public function addDetail()
    {
        $headerid  = $this->request->getPost('headerid');
        $productId = $this->request->getPost('productid');
        $uomId     = $this->request->getPost('uomid');
        $qty       = (float) $this->request->getPost('qty') ?: 0.0;
        $price    = (float) $this->request->getPost('price') ?: 0.0;
        $this->db->transBegin();
        try {
            if (empty($headerid) || empty($productId) || empty($uomId) || $qty <= 0 || $price <= 0) {
                throw new \Exception("Data detail tidak lengkap!");
            }

            $total = $qty * $price;

            // Insert ke detail
            $this->salesDetailModel->store([
                'headerid' => $headerid,
                'productid' => $productId,
                'uomid' => $uomId,
                'qty' => $qty,
                'price' => $price,
                'createddate' => date('Y-m-d H:i:s'),
                'createdby' => session()->get('id'),
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => session()->get('id'),
                'isactive' => true
            ]);

            $grandtotal = $this->updateGrandTotal($headerid);

            $res = [
                'sukses' => '1',
                'pesan' => 'Sukses menambahkan Detail',
                'grandtotal' => $grandtotal,
                'dbError' => db_connect()
            ];
            $this->db->transCommit();
        } catch (\Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function updateData()
    {
        $id = $this->request->getPost('id');
        $transcode = $this->request->getPost('transcode');
        $transdate = $this->request->getPost('transdate');
        $customerid = $this->request->getPost('customerid');
        $description = $this->request->getPost('description');
        $res = array();

        $this->db->transBegin();
        try {
            if (empty($transcode)) throw new Exception("Transcode dibutuhkan!");
            if (empty($transdate)) throw new Exception("Transdate dibutuhkan!");
            if (empty($customerid)) throw new Exception("Customername dibutuhkan!");
            $row = $this->salesModel->where('transcode', $transcode)->first();
            if (!empty($row) && $row['id'] != $id) throw new Exception("Transcode sudah terdaftar!");
            $data = [
                'transcode' => $transcode,
                'transdate' => $transdate,
                'customerid' => $customerid,
                'description' => $description,
            ];
            $this->salesModel->edit($id, $data);
            $res = [
                'sukses' => '1',
                'pesan' => 'Sukses update Sales Order',
                'dbError' => $this->db->error()
            ];
            $this->db->transCommit();
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => $this->db->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function updateDetail()
    {
        $id       = $this->request->getPost('detailid');
        $headerid = $this->request->getPost('headerid');

        $data = [
            'headerid'    => $this->request->getPost('headerid'),
            'productid'   => $this->request->getPost('productid'),
            'uomid'       => $this->request->getPost('uomid'),
            'qty'         => $this->request->getPost('qty'),
            'price'       => $this->request->getPost('price'),
            'updateddate' => date('Y-m-d H:i:s'),
            'updatedby'   => session()->get('id'),
        ];

        $this->db->transBegin();
        try {
            // update detail berdasarkan id
            $this->salesDetailModel->update($id, $data);

            // hitung ulang grandtotal di header
            $grandtotal = $this->updateGrandTotal($headerid);

            $res = [
                'sukses'     => 1,
                'pesan'      => 'Detail berhasil diupdate',
                'grandtotal' => $grandtotal,
                'dbError'    => $this->db->error()
            ];
            $this->db->transCommit();
        } catch (\Exception $e) {
            $res = [
                'sukses'      => 0,
                'pesan'       => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError'     => $this->db->error()
            ];
            $this->db->transRollback();
        }
        echo json_encode($res);
    }

    public function deleteData()
    {
        $id = decrypting($this->request->getPost('id'));
        $res = array();

        $this->db->transBegin();
        try {
            $this->salesModel->destroy('id', $id);
            $res = [
                'sukses' => '1',
                'pesan' => 'Data berhasil dihapus',
                'dbError' => $this->db->error()
            ];
            $this->db->transCommit();
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => $this->db->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }


    public function deleteDetail()
    {
        $id = decrypting($this->request->getPost('id'));
        $detail = $this->salesDetailModel->find($id);
        $headerid = $detail['headerid'];
        $res = array();

        $this->db->transBegin();
        try {
            $this->salesDetailModel->destroy('id', $id);
            $grandtotal = $this->updateGrandTotal($headerid);

            $res = [
                'sukses'    => '1',
                'pesan'     => 'Detail berhasil dihapus',
                'grandtotal' => $grandtotal,
                'dbError'   => $this->db->error()
            ];
            $this->db->transCommit();
        } catch (Exception $e) {
            $res = [
                'sukses'      => '0',
                'pesan'       => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError'     => $this->db->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function updateGrandTotal($headerid)
    {
        $details = $this->salesDetailModel->where('headerid', $headerid)->findAll();
        $grandtotal = 0;
        foreach ($details as $dt) {
            $grandtotal += $dt['qty'] * $dt['price'];
        }

        $this->salesModel->update($headerid, [
            'grandtotal'  => $grandtotal,
            'updateddate' => date('Y-m-d H:i:s'),
            'updatedby'   => session()->get('id'),
        ]);

        return $grandtotal;
    }
}

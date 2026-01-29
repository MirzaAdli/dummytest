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
use Fpdf\Fpdf;

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
        $this->salesModel       = new MSalesOrder();
        $this->salesDetailModel = new MSalesOrderDetail();
        $this->customerModel    = new MCustomer();
        $this->uomModel         = new MUom();
        $this->productModel     = new MProduct();
        $this->db               = \Config\Database::connect();
        $this->bc               = [
            ['Setting', 'Sales Order']
        ];
    }

    public function index()
    {
        return view('master/salesorder/v_salesorder', [
            'title'      => 'Sales Order',
            'akses'      => null,
            'breadcrumb' => $this->bc,
            'section'    => 'Setting Sales Order',
        ]);
    }

    public function customerList()
    {
        $search = $this->request->getPost('search');

        $items = !empty($search)
            ? $this->customerModel->searchCustomer($search)
            : $this->customerModel->findAll(10);

        $results = array_map(static function ($c) {
            return [
                'id'   => $c['id'],
                'text' => $c['customername'],
            ];
        }, $items);

        return $this->response->setJSON(['items' => $results]);
    }

    public function productList()
    {
        $search = $this->request->getPost('search');

        $items = !empty($search)
            ? $this->productModel->searchProduct($search)
            : $this->productModel->findAll(10);

        $results = array_map(static function ($p) {
            return [
                'id'    => $p['id'],
                'text'  => $p['productname'],
                'price' => $p['price'],
            ];
        }, $items);

        return $this->response->setJSON(['items' => $results]);
    }

    public function uomList()
    {
        $search = $this->request->getPost('search');

        $items = !empty($search)
            ? $this->uomModel->searchUom($search)
            : $this->uomModel->findAll(10);

        $results = array_map(static function ($u) {
            return [
                'id'   => $u['id'],
                'text' => $u['uomnm'],
            ];
        }, $items);

        return $this->response->setJSON(['items' => $results]);
    }


    public function datatable()
    {
        $columnIndex = $this->request->getPost('order')[0]['column'];
        $columnOrder = $this->request->getPost('order')[0]['dir'];
        $arrColumn = [null, "h.transcode", "h.transdate", "c.customername", "h.grandtotal", "h.description"];
        $columnName = $arrColumn[$columnIndex];

        $table = Datatables::method([$this->salesModel::class, 'datatable'], 'searchable')
            ->setParams(null, ['columnName' => $columnName, 'columnOrder' => $columnOrder])
            ->make();

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning'
            onclick=\"window.location.href='" . getURL('salesorder/form/' . encrypting($db->id)) . "'\">
            <i class='bx bx-edit-alt'></i></button>";

            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger'
            onclick=\"modalDelete('Delete SalesOrder - " . $db->transcode . "', {'link':'" . getURL('salesorder/delete') . "', 'id':'" . encrypting($db->id) . "', 'pagetype':'table'})\">
            <i class='bx bx-trash'></i></button>";

            $btn_pdf = "<button type='button' class='btn btn-sm btn-info'
            onclick=\"window.open('" . getURL('salesorder/pdf/' . encrypting($db->id)) . "', '_blank')\">
            <i class='bx bx-printer'></i></button>";

            return [
                $no,
                $db->transcode,
                $db->transdate,
                $db->customername,
                'Rp' . number_format($db->grandtotal, 2, ',', '.'),
                $db->description,
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_pdf&nbsp;$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });

        return $table->toJson();
    }

    public function detaildatatable($param = "")
    {
        $headerid = decrypting($param);

        if (empty($headerid)) {
            return $this->response->setJSON([
                'draw'            => intval($this->request->getPost('draw')),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => []
            ]);
        }

        $orderData   = $this->request->getPost('order')[0] ?? [];
        $columnIndex = $orderData['column'] ?? 1;
        $columnOrder = $orderData['dir'] ?? 'asc';
        $arrColumn  = [null, "p.productname", "u.uomnm", "d.qty", "d.price"];
        $columnName = $arrColumn[$columnIndex] ?? "d.id";

        // panggil Datatables helper dengan instance model
        $table = Datatables::method([$this->salesDetailModel, 'datatable'], 'searchable')
            ->setParams($headerid, ['columnName' => $columnName, 'columnOrder' => $columnOrder])
            ->make();

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning' 
            onclick=\"modalForm('Update Detail - " . $db->productname . "', 'modal-lg', '" . getURL('salesorder/detailform/' . $db->id) . "', {identifier: this})\">
            <i class='bx bx-edit-alt'></i></button>";

            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger'
            onclick=\"deleteDataDt(this,'" . encrypting($db->id) . "')\">
            <i class='bx bx-trash'></i></button>";

            return [
                $no,
                $db->productname,
                $db->uomnm,
                number_format($db->qty, 0, ',', '.'),
                'Rp' . number_format($db->price, 2, ',', '.'),
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });

        return $table->toJson();
    }

    public function detailForm($id)
    {
        $detail = $this->salesDetailModel->getDetail('d.id', $id)
            ->get()
            ->getRowArray();

        if (empty($detail)) {
            return $this->response->setJSON([
                'error'     => "Detail dengan ID $id tidak ditemukan",
                'csrfToken' => csrf_hash()
            ]);
        }

        // data master untuk dropdown
        $products = $this->productModel->findAll();
        $uoms     = $this->uomModel->findAll();

        // Format qty & price sama seperti di datatable
        $detail['qty_formatted']   = $detail['qty'];
        $detail['price_formatted'] = $detail['price'];

        $dt = [
            'view'      => view('master/salesorder/v_detail_form', [
                'detail'   => $detail,
                'products' => $products,
                'uoms'     => $uoms,
            ]),
            'csrfToken' => csrf_hash()
        ];

        return $this->response->setJSON($dt);
    }

    public function forms($id = '')
    {
        $form_type = (empty($id) ? 'add' : 'edit');

        // default row header
        $row = [
            'id'          => '',
            'transcode'   => '',
            'transdate'   => date('Y-m-d'),
            'customerid'  => '',
            'description' => ''
        ];
        $details = [];

        if (!empty($id)) {
            $id  = decrypting($id);
            $row = $this->salesModel->getHeader('h.id', $id)
                ->get()
                ->getRowArray();

            if (!empty($row)) {
                // ambil detail kalau edit
                $details = $this->salesDetailModel->getDetail('d.headerid', $row['id'])
                    ->get()
                    ->getResultArray();
            }
        }

        // data master
        $customers = $this->customerModel->findAll();
        $products  = $this->productModel->findAll();
        $uoms      = $this->uomModel->findAll();

        // kirim ke view
        return view('master/salesorder/v_form', [
            'title'      => 'Sales Order Forms',
            'breadcrumb' => $this->bc,
            'section'    => 'Setting Sales Order',
            'form_type'  => $form_type,
            'row'        => $row,
            'headerid'   => $row['id'],
            'customers'  => $customers,
            'products'   => $products,
            'uoms'       => $uoms,
            'details'    => $details
        ]);
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

            $duplicate = $this->salesModel
                ->where('transcode', $transcode)
                ->first();

            if ($duplicate) {
                throw new \Exception("Transcode sudah terdaftar!");
            }

            $data = [
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
            ];

            // Insert ke header
            $this->salesModel->store($data);
            $headerid = $this->db->insertID();
            if ($this->db->transStatus() === FALSE) {
                $this->db->transRollback();
                $res = [
                    'sukses' => 0,
                    'pesan'  => 'Terjadi kesalahan',
                    'dbError' => $this->db->error()
                ];
            } else {
                $this->db->transCommit();
                $res = [
                    'sukses' => 1,
                    'pesan'  => 'Sukses menambahkan Sales Order baru',
                    'dbError' => $this->db->error()
                ];
            }
        } catch (\Exception $e) {
            $res = [
                'sukses' => 0,
                'pesan'  => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => $this->db->error()
            ];
        }

        return $this->response->setJSON($res);
    }

    public function addDetail()
    {
        $headerid  = $this->request->getPost('headerid');
        $productId = $this->request->getPost('productid');
        $uomId     = $this->request->getPost('uomid');
        $qty  = $this->request->getPost('qty');
        $price = $this->request->getPost('price');

        $this->db->transBegin();
        try {
            if (empty($headerid) || empty($productId) || empty($uomId) || $qty <= 0 || $price <= 0) {
                throw new \Exception("Data detail tidak lengkap!");
            }

            // Insert ke detail
            $data = [
                'headerid'    => $headerid,
                'productid'   => $productId,
                'uomid'       => $uomId,
                'qty'         => $qty,
                'price'       => $price,
                'createddate' => date('Y-m-d H:i:s'),
                'createdby'   => session()->get('id'),
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby'   => session()->get('id'),
                'isactive'    => true
            ];
            $this->salesDetailModel->store($data);

            /// hitung ulang grandtotal dari semua detail
            $details = $this->salesDetailModel->getDetail('d.headerid', $headerid)
                ->get()
                ->getResultArray();
            $grandtotal = 0;
            foreach ($details as $dt) {
                $grandtotal += $dt['qty'] * $dt['price'];
            }


            // update grandtotal ke header
            $this->salesModel->edit($headerid, ['grandtotal' => $grandtotal]);

            if ($this->db->transStatus() === FALSE) {
                $this->db->transRollback();
                $res = [
                    'sukses'  => 0,
                    'pesan'   => 'Terjadi kesalahan saat insert detail',
                    'dbError' => $this->db->error()
                ];
            } else {
                $this->db->transCommit();
                $res = [
                    'sukses'     => 1,
                    'pesan'      => 'Sukses menambahkan detail',
                    'grandtotal' => $grandtotal,
                    'dbError'    => $this->db->error()
                ];
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses'      => 0,
                'pesan'       => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError'     => $this->db->error()
            ];
        }

        return $this->response->setJSON($res);
    }

    public function updateData()
    {
        $id = $this->request->getPost('headerid');
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

            $duplicate = $this->salesModel
                ->where('transcode', $transcode)
                ->where('id !=', $id)
                ->first();

            if ($duplicate) {
                throw new \Exception("Transcode sudah terdaftar!");
            }

            $data = [
                'transcode' => $transcode,
                'transdate' => $transdate,
                'customerid' => $customerid,
                'description' => $description,
            ];
            $update = $this->salesModel->edit($id, $data);

            if ($this->db->transStatus() === FALSE || !$update) {
                $this->db->transRollback();
                $res = [
                    'sukses' => 0,
                    'pesan'  => 'Terjadi kesalahan saat update Sales Order',
                    'dbError' => $this->db->error()
                ];
            } else {
                $this->db->transCommit();
                $res = [
                    'sukses' => 1,
                    'pesan'  => 'Sukses update Sales Order',
                    'dbError' => $this->db->error()
                ];
            }
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => $this->db->error()
            ];
        }

        return $this->response->setJSON($res);
    }

    public function deleteData()
    {
        $id = decrypting($this->request->getPost('id'));
        $res = array();

        $this->db->transBegin();
        try {
            $this->salesModel->destroy('id', $id);
            if ($this->db->transStatus() === FALSE) {
                $this->db->transRollback();
                $res = [
                    'sukses'  => 0,
                    'pesan'   => 'Terjadi kesalahan saat menghapus data',
                    'dbError' => $this->db->error()
                ];
            } else {
                $this->db->transCommit();
                $res = [
                    'sukses' => 1,
                    'pesan'  => 'Data berhasil dihapus',
                    'dbError' => $this->db->error()
                ];
            }
        } catch (Exception $e) {
            $res = [
                'sukses' => 0,
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => $this->db->error()
            ];
        }

        return $this->response->setJSON($res);
    }


    public function deleteDetail()
    {
        $id       = decrypting($this->request->getPost('id'));
        $detail   = $this->salesDetailModel->getDetail('d.id', $id)
            ->get()
            ->getRowArray();
        $headerid = $detail['headerid'];

        $this->db->transBegin();
        try {
            // hapus detail
            $this->salesDetailModel->destroy('id', $id);

            // hitung ulang grandtotal dari semua detail
            $details = $this->salesDetailModel->getDetail('d.headerid', $headerid)
                ->get()
                ->getResultArray();

            $grandtotal = 0;
            foreach ($details as $dt) {
                // konversi ke float untuk hitung, tapi tetap simpan/tampil mentah
                $qty   = floatval(str_replace(',', '.', $dt['qty']));
                $price = floatval(str_replace(',', '.', $dt['price']));
                $grandtotal += $qty * $price;
            }


            // update grandtotal ke header
            $this->salesModel->edit($headerid, ['grandtotal' => $grandtotal]);

            if ($this->db->transStatus() === FALSE) {
                $this->db->transRollback();
                $res = [
                    'sukses'  => 0,
                    'pesan'   => 'Terjadi kesalahan saat menghapus detail',
                    'dbError' => $this->db->error()
                ];
            } else {
                $this->db->transCommit();
                $res = [
                    'sukses'     => 1,
                    'pesan'      => 'Detail berhasil dihapus',
                    'grandtotal' => $grandtotal,
                    'dbError'    => $this->db->error()
                ];
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses'      => 0,
                'pesan'       => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError'     => $this->db->error()
            ];
        }

        return $this->response->setJSON($res);
    }

    public function updateDetail()
    {
        $id       = $this->request->getPost('detailid');
        $headerid = $this->request->getPost('headerid');

        $qty  = $this->request->getPost('qty');
        $price = $this->request->getPost('price');

        $data = [
            'headerid'    => $headerid,
            'productid'   => $this->request->getPost('productid'),
            'uomid'       => $this->request->getPost('uomid'),
            'qty'         => $qty,
            'price'       => $price,
            'updateddate' => date('Y-m-d H:i:s'),
            'updatedby'   => session()->get('id'),
        ];

        $this->db->transBegin();
        try {
            // update detail berdasarkan id
            $this->salesDetailModel->edit($id, $data);

            // hitung ulang grandtotal dari semua detail
            $details = $this->salesDetailModel->getDetail('d.headerid', $headerid)
                ->get()
                ->getResultArray();
            $grandtotal = 0;
            foreach ($details as $dt) {
                $grandtotal += $dt['qty'] * $dt['price'];
            }

            // update grandtotal ke header
            $this->salesModel->edit($headerid, ['grandtotal' => $grandtotal]);

            if ($this->db->transStatus() === FALSE) {
                $this->db->transRollback();
                $res = [
                    'sukses'  => 0,
                    'pesan'   => 'Terjadi kesalahan saat update detail',
                    'dbError' => $this->db->error()
                ];
            } else {
                $this->db->transCommit();
                $res = [
                    'sukses'     => 1,
                    'pesan'      => 'Detail berhasil diupdate',
                    'grandtotal' => $grandtotal,
                    'dbError'    => $this->db->error()
                ];
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses'      => 0,
                'pesan'       => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError'     => $this->db->error()
            ];
        }

        return $this->response->setJSON($res);
    }

    public function printPDF($headerid)
    {
        $id      = decrypting($headerid);
        $header  = $this->salesModel->getHeader('h.id', $id)
            ->get()
            ->getRowArray();
        $details = $this->salesDetailModel->getDetail('d.headerid', $id)
            ->get()
            ->getResultArray();
        $logo    = FCPATH . 'public/images/hyperdata.png';
        $ttd    = FCPATH . 'public/images/ttd.png';

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        //HEADER
        $pdf->SetFont('Arial', 'B', 11);

        // LOGO (tinggi 24)
        $pdf->Cell(35, 20, '', 1, 0, 'C');
        $pdf->Image($logo, $pdf->GetX() - 34, $pdf->GetY() + 1, 34, 17);

        // JUDUL (tinggi 24)
        $pdf->Cell(70, 20, 'SALES ORDER', 1, 0, 'C');

        // ttd
        $pdf->SetFont('Arial', '', 8);

        // Baris 1
        $pdf->Cell(25, 5, 'Dokumen', 1, 0);
        $pdf->Cell(30, 5, '04.1-FRM-MKT', 1, 0);
        $pdf->MultiCell(30, 2.5, "Disetujui oleh:\nManager Mutu", 1, 'C');

        // Baris 2
        $pdf->Cell(105, 5, '', 0, 0);
        $pdf->Cell(25, 5, 'Revisi', 1, 0);
        $pdf->Cell(30, 5, '001', 1, 0);
        $pdf->Cell(30, 5, '', 'LR', 1, 'C');
        $pdf->Image($ttd, $pdf->GetX() + 162, $pdf->GetY()-4, 27, 10);

        // Baris 3
        $pdf->Cell(105, 5, '', 0, 0);
        $pdf->Cell(25, 5, 'Tanggal Terbit', 1, 0);
        $pdf->Cell(30, 5, date('d F Y', strtotime($header['transdate'])), 1, 0);
        $pdf->Cell(30, 5, '', 'LR', 1, 'C');

        // Baris 4
        $pdf->Cell(105, 5, '', 0, 0);
        $pdf->Cell(25, 5, 'Halaman', 1, 0);
        $pdf->Cell(30, 5, '1', 1, 0);
        $pdf->Cell(30, 5, 'Winna Oktavia P.', 1, 1, 'C');

        //pemisah
        $pdf->Ln(4);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(2);

        //Info Transaksi
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(30, 6, 'No. Sales Order', 0, 0);
        $pdf->Cell(70, 6, ': ' . $header['transcode'], 0, 0);
        $pdf->Cell(30, 6, 'Tanggal Order', 0, 0);
        $pdf->Cell(50, 6, ': ' . date('d F Y', strtotime($header['transdate'])), 0, 1);

        $pdf->Cell(30, 6, 'Customer', 0, 0);
        $pdf->Cell(50, 6, ': ' . $header['customername'], 0, 1);

        $pdf->Ln(2);

        //Nama Kolom Tabel
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(10, 6, 'DATA DETAIL', 0, 1, 'L');
        $pdf->Cell(10, 6, 'No', 1, 0, 'C');
        $pdf->Cell(65, 6, 'Product Name', 1, 0, 'C');
        $pdf->Cell(20, 6, 'UOM', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Qty', 1, 0, 'C');
        $pdf->Cell(37.5, 6, 'Price', 1, 0, 'C');
        $pdf->Cell(37.5, 6, 'Total', 1, 1, 'C');

        //Isi Tabel
        $pdf->SetFont('Arial', '', 10);
        $no = 1;
        $subtotalAll = 0;

        foreach ($details as $d) {
            $total = $d['qty'] * $d['price'];
            $subtotalAll += $total;

            $qty   = number_format($d['qty'], 0, '.', '.');
            $price = number_format($d['price'], 2, ',', '.');
            $total = number_format($total, 2, ',', '.');

            $pdf->Cell(10, 6, $no++, 1, 0, 'C');
            $pdf->Cell(65, 6, $d['productname'], 1, 0, 'C');
            $pdf->Cell(20, 6, $d['uomnm'], 1, 0, 'C');
            $pdf->Cell(20, 6, $qty, 1, 0, 'C');
            $pdf->Cell(37.5, 6, 'Rp ' . $price, 1, 0, 'R');
            $pdf->Cell(37.5, 6, 'Rp ' . $total, 1, 1, 'R');
        }

        //Subtotal & grandtotal
        $pdf->Ln(4);
        $pdf->SetX(120);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 8, 'Sub Total', 0, 0, 'R');
        $pdf->Cell(30, 8, 'Rp ' . number_format($subtotalAll, 2, ',', '.'), 0, 1, 'R');
        $pdf->Cell(160.5, 8, 'Discount', 0, 0, 'R');
        $pdf->Cell( 12, 8, 'Rp ', 0, 0, 'R');
        $pdf->Cell(17.5, 8, '0', 0, 1, 'R');

        $pdf->SetX(120);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(145, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(2);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(130);
        $pdf->Cell(40, 8, 'Grand Total', 0, 0, 'R');
        $pdf->Cell(30, 8, 'Rp ' . number_format($subtotalAll, 2, ',', '.'), 0, 1, 'R');

        /* ================= OUTPUT ================= */
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="SalesOrder_' . $header['transcode'] . '.pdf"');
        $pdf->Output('I');
        exit;
    }
}

<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Datatables\Datatables;
use App\Models\MSupplier;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class Supplier extends BaseController
{
    protected $db;
    protected $bc;
    protected $MSupplier;

    public function __construct()
    {
        $this->bc = [
            [
                'Setting',
                'Supplier'
            ]
        ];

        $this->MSupplier = new MSupplier();
    }

    public function index()
    {
        return view('master/supplier/v_supplier', [
            'title' => 'Supplier',
            'akses' => null,
            'breadcrumb' => $this->bc,
            'section' => 'Setting Supplier',
        ]);
    }

    public function forms($id = '')
    {
        $form_type = (empty($id) ? 'add' : 'edit');
        $row = array();
        if ($id != '') {
            $id = decrypting($id);
            $row = $this->MSupplier->find($id);
        }
        $dt['view'] = view('master/supplier/v_form', [
            'form_type' => $form_type,
            'row' => $row,
            'userid' => $id,
            'title' => 'Supplier Form'
        ]);
        $dt['csrfToken'] = csrf_hash();
        echo json_encode($dt);
    }

    public function add()
    {
        $suppliername = $this->request->getPost('suppliername');
        $address = $this->request->getPost('address');
        $phone = $this->request->getPost('phone');
        $email = $this->request->getPost('email');
        $filepath = $this->request->getFile('filepath');
        $res = array();

        $this->db->transBegin();
        try {
            // Validasi input
            if (empty($suppliername)) throw new Exception('Masukkan nama supplier');
            if (empty($address)) throw new Exception('Masukkan alamat');
            if (empty($phone)) throw new Exception('Masukkan nomor HP');
            if (empty($email)) throw new Exception('Masukkan email');

            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            $extension = $filepath->getExtension();
            if (!in_array($extension, $allowedExtensions)) {
                throw new Exception("Format filepath tidak valid, hanya jpg, jpeg, dan png yang diperbolehkan!");
            }

            $filename = $filepath->getRandomName();
            $filepath->move('uploads/supplier/', $filename);
            $fileurl = 'uploads/supplier/' . $filename;

            // Insert data
            $this->MSupplier->store([
                'suppliername' => $suppliername,
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
                'filepath' => $fileurl,
                'createdby' => getSession('userid'),
                'createddate' => date('Y-m-d H:i:s'),
                'updatedby' => getSession('userid'),
                'updateddate' => date('Y-m-d H:i:s')
            ]);

            $res = [
                'status' => '1',
                'message' => 'Supplier added successfully',
                "dbError" => db_connect()
            ];
            $this->db->transCommit();
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses' => '0',
                'message' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function datatable()
    {
        $table = Datatables::method([MSupplier::class, 'datatable'], 'searchable')
            ->make();

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning' onclick=\"modalForm('Update User - " . $db->suppliername . "', 'modal-lg', '" . getURL('supplier/form/' . encrypting($db->id)) . "', {identifier: this})\"><i class='bx bx-edit-alt'></i></button>";
            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger' onclick=\"modalDelete('Delete User - " . $db->suppliername . "', {'link':'" . getURL('supplier/delete') . "', 'id':'" . encrypting($db->id) . "', 'pagetype':'table'})\"><i class='bx bx-trash'></i></button>";
            return [
                $no,
                $db->suppliername,
                $db->address,
                $db->phone,
                $db->email,
                $db->filepath,
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });
        $table->toJson();
    }

    public function update()
    {
        $supplierid = $this->request->getPost('id');
        $suppliername = $this->request->getPost('suppliername');
        $address = $this->request->getPost('address');
        $phone = $this->request->getPost('phone');
        $email = $this->request->getPost('email');
        $filepath = $this->request->getFile('filepath');
        $res = array();

        $this->db->transBegin();
        try {
            // Validasi input
            if (empty($suppliername)) throw new Exception('Masukkan nama supplier');
            if (empty($address)) throw new Exception('Masukkan alamat');
            if (empty($phone)) throw new Exception('Masukkan nomor HP');
            if (empty($email)) throw new Exception('Masukkan email');

            $data = [
                'suppliername' => $suppliername,
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
                'updatedby' => getSession('userid'),
                'updateddate' => date('Y-m-d H:i:s')
            ];

            if ($filepath && $filepath->isValid()) {
                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                $extension = $filepath->getExtension();
                if (!in_array($extension, $allowedExtensions)) {
                    throw new Exception("Format foto tidak valid, hanya jpg, jpeg, dan png yang diperbolehkan!");
                }
                $oldFilePath = $this->MSupplier->getOne($supplierid)['filepath'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
                $newName = $filepath->getRandomName();
                $filepath->move('uploads/supplier/', $newName);
                $data['filepath'] = 'uploads/supplier/' . $newName;
            }

            $this->MSupplier->edit($data, $supplierid);
            $res = [
                'status' => '1',
                'message' => 'Supplier updated successfully',
                'csrf_token' => csrf_hash(),
                'dbError' => db_connect()
            ];
            $this->db->transCommit();
        } catch (Exception $e) {
            $res = [
                'status' => '0',
                'message' => $e->getMessage(),
                'csrf_token' => csrf_hash(),
                'dbError' => db_connect()->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function delete()
    {
        $userid = decrypting($this->request->getPost('id'));
        $res = array();
        $this->db->transBegin();
        try {
            $row = $this->MSupplier->getOne($userid);
            if (empty($row)) throw new Exception("User not found!");
            $this->MSupplier->destroy('id', $userid);
            $res = [
                'status' => '1',
                'message' => 'Data deleted successfully!',
                'dbError' => db_connect()->error()
            ];
        } catch (Exception $e) {
            $res = [
                'status' => '0',
                'message' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }
}

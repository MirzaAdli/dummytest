<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MFile;
use App\Helpers\Datatables\Datatables;
use Exception;

class File extends BaseController
{
    protected $fileModel;
    protected $bc;
    protected $db;

    public function __construct()
    {
        $this->fileModel = new MFile();
        $this->bc = [
            ['Setting', 'File']
        ];
        $this->db = db_connect();
    }

    public function index()
    {
        return view('master/file/v_file', [
            'title'      => 'File',
            'akses'      => null,
            'breadcrumb' => $this->bc,
            'section'    => 'Setting File',
        ]);
    }

    public function datatable()
    {
        $table = Datatables::method([MFile::class, 'datatable'], 'searchable')
            ->make();

        $table->updateRow(function ($db, $no) {
            $ext = pathinfo($db->filerealname, PATHINFO_EXTENSION);

            $btn_view = in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])
                ? "<a href='" . base_url($db->filedirectory) . "' target='_blank' class='btn btn-sm btn-info'><i class='bx bx-show'></i></a>"
                : "";

            $btn_download = "<a href='" . base_url($db->filedirectory) . "' download class='btn btn-sm btn-success'><i class='bx bx-download'></i></a>";

            $btn_update = "<a href='" . getURL('file/form/' . encrypting($db->fileid)) . "' class='btn btn-sm btn-warning'>
            <i class='bx bx-edit-alt'></i></a>";

            $btn_delete = "<button type='button' class='btn btn-sm btn-danger' 
            onclick=\"modalDelete('Delete File - " . $db->filerealname . "', {'link':'" . getURL('file/delete') . "', 'id':'" . encrypting($db->fileid) . "', 'pagetype':'table'})\">
            <i class='bx bx-trash'></i></button>";

            return [
                $no,
                $db->filerealname,
                $db->filedirectory,
                date('d-F-Y H:i:s', strtotime($db->created_date)),
                $db->created_name,
                "<div style='display:flex;gap:5px;justify-content:center;'>$btn_view $btn_download $btn_update $btn_delete</div>"
            ];
        });

        $table->toJson();
    }

    public function form($fileid = '')
    {
        $form_type = (empty($fileid) ? 'add' : 'edit');
        $row = [
            'fileid'       => '',
            'filename'     => '',
            'filerealname' => '',
            'filedirectory' => ''
        ];

        if (!empty($fileid)) {
            $fileid = decrypting($fileid);
            $row    = $this->fileModel->getOne($fileid);
        }

        // kirim ke view
        return view('master/file/v_form', [
            'title'      => 'File Form',
            'breadcrumb' => $this->bc,
            'section'    => 'Setting File',
            'form_type'  => $form_type,
            'row'        => $row,
            'id'         => $row['fileid']
        ]);
    }

    public function addFile()
    {
        $files = $this->request->getFiles();
        $res = [];

        $this->db->transBegin();
        try {
            foreach ($files['file'] as $file) {
                if (!$file->isValid()) continue;

                $newName = $file->getRandomName();
                $file->move(FCPATH . 'uploads/', $newName);
                $filePath = 'uploads/' . $newName;

                $this->fileModel->store([
                    'filename'     => $newName,
                    'filerealname' => $file->getClientName(),
                    'filedirectory' => $filePath,
                    'created_date' => date('Y-m-d H:i:s'),
                    'created_by'   => getSession('userid'),
                ]);
            }

            $this->db->transCommit();
            $res = ['sukses' => '1', 'pesan' => 'File berhasil diupload'];
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = ['sukses' => '0', 'pesan' => $e->getMessage()];
        }
        echo json_encode($res);
    }

    public function updateFile()
    {
        $fileid       = $this->request->getPost('fileid');
        $filerealname = $this->request->getPost('filerealname');
        $files        = $this->request->getFiles();
        $res = [];

        $this->db->transBegin();
        try {
            if (empty($fileid)) throw new Exception("ID File kosong!");

            $row = $this->fileModel->getOne($fileid);
            if (!$row) throw new Exception("File tidak ditemukan!");

            // default update data
            $dataUpdate = [
                'filerealname' => $filerealname,
                'update_date' => date('Y-m-d H:i:s'),
                'update_by'   => getSession('userid')
            ];

            if (isset($files['file'])) {
                foreach ($files['file'] as $file) {
                    if ($file->isValid() && !$file->hasMoved()) {
                        // hapus file lama
                        if (file_exists(FCPATH . $row['filedirectory'])) {
                            unlink(FCPATH . $row['filedirectory']);
                        }

                        // simpan file baru
                        $newName = $file->getRandomName();
                        $file->move(FCPATH . 'uploads/', $newName);
                        $filePath = 'uploads/' . $newName;

                        $dataUpdate['filename']      = $newName;
                        $dataUpdate['filedirectory'] = $filePath;
                        $dataUpdate['filerealname']  = $file->getClientName();
                    }
                }
            }

            $this->fileModel->edit($dataUpdate, $fileid);

            $this->db->transCommit();
            $res = ['sukses' => '1', 'pesan' => 'File berhasil diupdate'];
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = ['sukses' => '0', 'pesan' => $e->getMessage()];
        }
        echo json_encode($res);
    }


    public function deleteFile()
    {
        $fileid = decrypting($this->request->getPost('id'));
        $res = [];

        $this->db->transBegin();
        try {
            $row = $this->fileModel->getOne($fileid);
            if (empty($row)) throw new Exception("File tidak terdaftar!");

            if (file_exists(FCPATH . $row['filedirectory'])) {
                unlink(FCPATH . $row['filedirectory']);
            }

            $this->fileModel->destroy($fileid);

            $this->db->transCommit();
            $res = ['sukses' => '1', 'pesan' => 'File berhasil dihapus'];
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = ['sukses' => '0', 'pesan' => $e->getMessage()];
        }
        echo json_encode($res);
    }

    public function uploadFile()
    {
        $file = $this->request->getFile('file');

        // parameter tambahan dari Dropzone
        $chunkIndex  = $this->request->getPost('dzchunkindex');
        $totalChunks = $this->request->getPost('dztotalchunkcount');
        $uuid        = $this->request->getPost('dzuuid'); // unique ID dari Dropzone

        $tempDir = WRITEPATH . 'uploads/chunks/' . $uuid;
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // simpan chunk sementara
        $file->move($tempDir, $chunkIndex);

        // cek apakah semua chunk sudah masuk
        if (count(scandir($tempDir)) - 2 == $totalChunks) {
            $finalName = $uuid . '_' . $file->getClientName();
            $finalPath = FCPATH . 'uploads/' . $finalName;
            $out = fopen($finalPath, 'wb');

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunk = fopen($tempDir . '/' . $i, 'rb');
                stream_copy_to_stream($chunk, $out);
                fclose($chunk);
            }
            fclose($out);

            // hapus folder chunk
            array_map('unlink', glob("$tempDir/*"));
            rmdir($tempDir);

            // simpan metadata ke DB
            $this->fileModel->store([
                'filename'      => $finalName,
                'filerealname'  => $file->getClientName(),
                'filedirectory' => 'uploads/' . $finalName,
                'created_date'  => date('Y-m-d H:i:s'),
                'created_by'    => getSession('userid'),
                'isactive'      => true
            ]);
        }

        return $this->response->setJSON(['success' => true]);
    }
}

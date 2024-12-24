<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Datatables\Datatables;
use App\Models\MDocument;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;


class Document extends BaseController
{
    protected $MDocument;
    protected $bc;
    protected $db;
    public function __construct()
    {
        $this->MDocument = new MDocument();
        $this->bc = [
            [
                'Setting',
                'Document'
            ]
        ];
    }

    public function index()
    {
        return view('master/document/v_document', [
            'title' => 'Document',
            'akses' => null,
            'breadcrumb' => $this->bc,
            'section' => 'Setting Document',
        ]);
    }




    public function datatable()
    {
        $table = Datatables::method([MDocument::class, 'datatable'], 'searchable')
            ->make();

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning' onclick=\"modalForm('Update document - " . $db->documentname . "', 'modal-lg', '" . getURL('document/form/' . encrypting($db->id)) . "', {identifier: this})\"><i class='bx bx-edit-alt'></i></button>";
            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger' onclick=\"modalDelete('Delete document - " . $db->documentname . "', {'link':'" . getURL('document/delete') . "', 'id':'" . encrypting($db->id) . "', 'pagetype':'table'})\"><i class='bx bx-trash'></i></button>";
            return [
                $no,
                $db->documentname,
                $db->description,
                $db->filepath,
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });
        $table->toJson();
    }

    public function forms($id = '')
    {
        $form_type = (empty($id) ? 'add' : 'edit');
        $row = [];
        if ($id != '') {
            $id = decrypting($id);
            $row = $this->MDocument->getOne($id);
        }
        $dt['view'] = view('master/document/v_form', [
            'form_type' => $form_type,
            'row' => $row,
            'userid' => $id
        ]);
        $dt['csrfToken'] = csrf_hash();
        echo json_encode($dt);
    }

    public function addData()
    {
        $documentname = $this->request->getPost('name');
        $description = $this->request->getPost('description');
        $filepath = $this->request->getFile('dokumen');
        $res = array();
        $db = db_connect();

        $db->transBegin();
        try {
            if (empty($description)) throw new Exception("Masukkan deskripsi");
            if (!$filepath || !$filepath->isValid()) throw new Exception("Filepath tidak valid!");
            if (empty($documentname)) throw new Exception("Masukkan nama dokumen");

            $allowedExtensions = ['doc', 'docx', 'pdf', 'xlsx'];
            $extension = $filepath->getExtension();
            if (!in_array($extension, $allowedExtensions)) {
                throw new Exception("Format file tidak valid, hanya doc, docx, pdf, dan xlsx yang diperbolehkan!");
            }

            // Generate nama file unik untuk filepath
            $newName = $filepath->getRandomName();
            $filepath->move('uploads/document', $newName);
            $filePath = 'uploads/document' . $newName;

            $this->MDocument->store([
                'documentname' => $documentname,
                'description' => $description,
                'filepath' => $filePath,
                'createddate' => date('Y-m-d H:i:s'),
                'createdby' => 1,
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => 1,
            ]);

            $db->transCommit();

            $res = [
                'sukses' => '1',
                'pesan' => 'Sukses menambahkan dokumen baru',

            ];
        } catch (Exception $e) {
            $db->transRollback();
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
            ];
        }

        echo json_encode($res);
    }



    public function updateData()
    {
        $userid = $this->request->getPost('id');
        $documentname = $this->request->getPost('name');
        $description = $this->request->getPost('description');
        $filepath = $this->request->getFile('dokumen');
        $res = array();
        $db = db_connect();
    
        $db->transBegin();
        try {
            // Validasi input
            if (empty($description)) throw new Exception("Masukkan deskripsi");
            if (empty($documentname)) throw new Exception("Masukkan nama dokumen");
    
            // Ambil data dokumen lama berdasarkan user ID
            $oldDocument = $this->MDocument->getOne($userid);
            if (!$oldDocument) throw new Exception("Dokumen tidak ditemukan untuk ID tersebut");
    
            // Siapkan data untuk diperbarui
            $data = [
                'documentname' => $documentname,
                'description' => $description,
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => 1, // ID user yang melakukan pembaruan
            ];
    
            // Jika ada file baru yang diunggah
            if ($filepath && $filepath->isValid()) {
                $allowedExtensions = ['doc', 'docx', 'pdf', 'xlsx'];
                $extension = $filepath->getExtension();
                if (!in_array($extension, $allowedExtensions)) {
                    throw new Exception("Format file tidak valid, hanya doc, docx, pdf, dan xlsx yang diperbolehkan!");
                }
    
                // Hapus file lama jika ada
                if (!empty($oldDocument['filepath']) && file_exists($oldDocument['filepath'])) {
                    unlink($oldDocument['filepath']);
                }
    
                // Generate nama file unik untuk file baru
                $newName = $filepath->getRandomName();
                $filepath->move('uploads/document', $newName);
                $data['filepath'] = 'uploads/document/' . $newName; // Update file path di database
            }
    
            // Update data di database
            $result = $this->MDocument->edit($data, $userid);
            if (!$result) throw new Exception("Gagal memperbarui data dalam database!");
    
            $db->transCommit();
    
            $res = [
                'sukses' => '1',
                'pesan' => 'Sukses memperbarui dokumen',
            ];
        } catch (Exception $e) {
            $db->transRollback();
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
            ];
        }
    
        echo json_encode($res);
    }
    



    public function deleteData()
    {
        $userid = decrypting($this->request->getPost('id'));
        $res = array();
        $this->db->transBegin();
        try {
            $row = $this->MDocument->getOne($userid);
            if (empty($row)) throw new Exception("Dokumen tidak terdaftar!");
            $this->MDocument->destroy('id', $userid);
            $res = [
                'sukses' => '1',
                'pesan' => 'Data berhasil dihapus!',
                'dbError' => db_connect()->error()
            ];
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function logOut()
    {
        $userid = session()->get('userid');
        $row = $this->MDocument->getOne($userid);
        if (!empty($row)) {
            destroySession();
        }
        return redirect('login');
    }



    public function export()
    {
        // Fetch data from the model
        $documents = $this->MDocument->findAll();
    
        // Create a new Spreadsheet
        $spreadSheet = new Spreadsheet();
        $sheet = $spreadSheet->getActiveSheet();
    
        // Set the title and header format
        $sheet->setCellValue('A1', "Data Dokumen");
        $sheet->mergeCells('A1:C1'); // Merge sesuai kolom
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    
        // Header columns (Row 3)
        $sheet->setCellValue('A3', "No");
        $sheet->setCellValue('B3', "Documentname");
        $sheet->setCellValue('C3', "Description");
        $sheet->setCellValue('D3', "FilePath");
    
        // Set the style for the header (Bold and centered)
        $sheet->getStyle('A3:D3')->getFont()->setBold(true);
        $sheet->getStyle('A3:D3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3:D3')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
        // Write data to the sheet
        $row = 4; // Start from row 4 for data
        foreach ($documents as $key => $document) {
            // Write data into the respective columns
            $sheet->setCellValue('A' . $row, $key + 1);
            $sheet->setCellValue('B' . $row, $document['documentname']);
            $sheet->setCellValue('C' . $row, $document['description']);
            $sheet->setCellValue('D' . $row, $document['filepath']); // Menampilkan file path
    
            $row++;
        }
    
        // Set column widths to auto size
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
    
        // Set borders for the data
        $sheet->getStyle('A3:D' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
        // Save file as Excel
        $writer = new Xlsx($spreadSheet);
        $filename = 'data_dokumen_' . date('Ymd_His') . '.xlsx';
    
        // Output the file to the browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
    
        // Save the file and output
        $writer->save('php://output');
    }

    
}

<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Datatables\Datatables;
use App\Models\MUser;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Fpdf\Fpdf;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$headerStyle['alignment'] = [
    'horizontal' => Alignment::HORIZONTAL_CENTER,
    'vertical' => Alignment::VERTICAL_CENTER,
];

$dataStyle['alignment'] = [
    'horizontal' => Alignment::HORIZONTAL_LEFT,
    'vertical' => Alignment::VERTICAL_CENTER,
];


class User extends BaseController
{
    protected $userModel;
    protected $bc;
    protected $db;
    public function __construct()
    {
        $this->userModel = new MUser();
        $this->bc = [
            [
                'Setting',
                'User'
            ]
        ];
    }

    public function index()
    {
        return view('master/user/v_user', [
            'title' => 'User',
            'akses' => null,
            'breadcrumb' => $this->bc,
            'section' => 'Setting User',
        ]);
    }

    public function viewLogin()
    {
        return view('login/v_login', [
            'title' => 'Login'
        ]);
    }

    public function loginAuth()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $res = array();
        $this->db->transBegin();
        try {
            if (empty($username) || empty($password)) throw new Exception("Username atau Password harus diisi!");
            $row = $this->userModel->getByName($username);
            if (empty($row)) throw new Exception("User tidak terdaftar di sistem!");
            if (password_verify($password, $row['password'])) {
                setSession('userid', $row['id']);
                setSession('name', $row['fullname']);
                $res = [
                    'sukses' => '1',
                    'pesan' => 'Berhasil Login',
                    'link' => base_url('user'),
                    'dbError' => db_connect()->error()
                ];
            } else {
                throw new Exception("Password user salah, coba lagi!");
            }
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),    
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function datatable()
    {
        $table = Datatables::method([MUser::class, 'datatable'], 'searchable')
            ->make();

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning' onclick=\"modalForm('Update User - " . $db->fullname . "', 'modal-lg', '" . getURL('user/form/' . encrypting($db->id)) . "', {identifier: this})\"><i class='bx bx-edit-alt'></i></button>";
            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger' onclick=\"modalDelete('Delete User - " . $db->fullname . "', {'link':'" . getURL('user/delete') . "', 'id':'" . encrypting($db->id) . "', 'pagetype':'table'})\"><i class='bx bx-trash'></i></button>";
            return [
                $no,
                $db->fullname,
                $db->username,
                $db->email,
                $db->telp,
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
            $row = $this->userModel->getOne($id);
        }
        $dt['view'] = view('master/user/v_form', [
            'form_type' => $form_type,
            'row' => $row,
            'userid' => $id
        ]);
        $dt['csrfToken'] = csrf_hash();
        echo json_encode($dt);
    }

    public function addData()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $fullname = $this->request->getPost('name');
        $email = $this->request->getPost('email');
        $phone = $this->request->getPost('phone');
        $res = array();

        $this->db->transBegin();
        try {
            if (empty($username)) throw new Exception("Username dibutuhkan!");
            if (empty($password)) throw new Exception("Password dibutuhkan!");
            if (empty($fullname)) throw new Exception("Fullname masih kosong!");
            $row = $this->userModel->getByName($fullname);
            if (!empty($row)) throw new Exception("User dengan username ini sudah terdaftar!");
            $this->userModel->store([
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'fullname' => $fullname,
                'email' => $email,
                'telp' => $phone,
                'createddate' => date('Y-m-d H:i:s'),
                'createdby' => 1,
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => 1,
            ]);
            $res = [
                'sukses' => '1',
                'pesan' => 'Sukses menambahkan user baru',
                'dbError' => db_connect()
            ];
            $this->db->transCommit();
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

    public function updateData()
    {
        $userid = $this->request->getPost('id');
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $fullname = $this->request->getPost('name');
        $email = $this->request->getPost('email');
        $phone = $this->request->getPost('phone');
        $res = array();

        $this->db->transBegin();
        try {
            if (empty($username)) throw new Exception("Username dibutuhkan!");
            if (empty($fullname)) throw new Exception("Fullname masih kosong!");
            $row = $this->userModel->getByName($fullname);
            if (!empty($row)) throw new Exception("User dengan username ini sudah terdaftar!");
            $data = [
                'username' => $username,
                'fullname' => $fullname,
                'email' => $email,
                'telp' => $phone,
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => $userid,
            ];
            if (!empty($password)) {
                $data += [
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ];
            }
            $this->userModel->edit($data, $userid);
            $res = [
                'sukses' => '1',
                'pesan' => 'Sukses update user baru',
                'dbError' => db_connect()
            ];
            $this->db->transCommit();
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
    

    public function deleteData()
    {
        $userid = decrypting($this->request->getPost('id'));
        $res = array();
        $this->db->transBegin();
        try {
            $row = $this->userModel->getOne($userid);
            if (empty($row)) throw new Exception("User tidak terdaftar!");
            $this->userModel->destroy('id', $userid);
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
        session()->destroy();
        return redirect()->to(base_url('login'));
    }

    public function printPDF()
    {
        $pdf = new Fpdf();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);

        $pdf->Cell(10, 10, 'No', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Name', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Username', 1, 0, 'C');
        $pdf->Cell(60, 10, 'Email', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Telephone', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 12);
        $datas = $this->userModel->datatable()->get()->getResultArray();

        $no = 1;
        foreach ($datas as $row) {
            $pdf->Cell(10, 10, $no++, 1, 0, 'C');
            $pdf->Cell(40, 10, $row['fullname'], 1, 0, 'L');
            $pdf->Cell(40, 10, $row['username'], 1, 0, 'L');
            $pdf->Cell(60, 10, $row['email'], 1, 0, 'L');
            $pdf->Cell(40, 10, $row['telp'], 1, 1, 'L');
        }

        $pdf->Output('D', 'user_data.pdf');
        exit;
    }

    public function exportexcel()
    {
        //memanggil data dari db
        $data = $this->userModel->getAll();
        //memanggil library/package untuk import excell
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        //==== $sheet->setTitle('Product_Data');

        //digunakan untuk mengatur style di excellnya
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => '4CAF50'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        //digunakan untuk menulis header kolom pertama
        $headers = ['No', 'Name', 'Username', 'Email', 'Telephone', 'File Path'];
        $columns = range('A', 'F');

        foreach ($columns as $key => $column) {
            $sheet->setCellValue($column . '1', $headers[$key]);
        }
        // untuk memasang style dimana ingin ditempatkan
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        $i = 2;
        // untuk menulis data yang diambil dari db ke excell
        foreach ($data as $index => $row) {
            $sheet->setCellValue('A' . $i, $index + 1);
            $sheet->setCellValue('B' . $i, $row['fullname']);
            $sheet->setCellValue('C' . $i, $row['username']);
            $sheet->setCellValue('D' . $i, $row['email']);
            $sheet->setCellValue('E' . $i, $row['telp']);
            $sheet->setCellValue('F' . $i, $row['filepath']);
            $i++;
        }
        // untuk memasang style dimana ingin ditempatkan
        $sheet->getStyle('A2:F' . ($i - 1))->applyFromArray($dataStyle);
        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        //untuk mengirim file excell dari php ke browser tanpa  menyimpan file di server/local
        // membuat writer excell
        $writer = new Xlsx($spreadsheet);
        $filename = 'User' . date('dmy') . '.xlsx';
        // untuk memberitahu ke browser itu adalah file excell
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        //memaksa download dengan nama yang ditentukan
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        //untuk mencegah cache
        header('Cache-Control: max-age=0');
        //output file ke browser
        $writer->save('php://output');
        exit;
    }

    public function formImport()
    {
        $dt['view'] = view('master/user/v_import', []);
        $dt['csrfToken'] = csrf_hash();
        echo json_encode($dt);
    }

    public function importExcel()
    {
        // data dari front end (JSON)
        $datas = json_decode($this->request->getPost('datas'));
        $res = array();
        $this->db->transBegin();

        try {
            $undfhuser = 0;
            $undfhuserarr = [];

            foreach ($datas as $dt) {
                // validasi minimal kolom (fullname, username, email, telp)
                if (
                    empty($dt[0]) || // fullname
                    empty($dt[1]) || // username
                    empty($dt[2]) || // email
                    empty($dt[3])    // telp
                ) {
                    $undfhuser++;
                    $undfhuserarr[] = $dt[0] ?? '-';
                    continue;
                }

                // simpan user
                $this->userModel->insert([
                    'fullname'    => trim($dt[0]),
                    'username'    => trim($dt[1]),
                    'email'       => trim($dt[2]),
                    'telp'        => trim($dt[3]),
                    'filepath'    => $dt[4] ?? null, // opsional
                    'createddate' => date('Y-m-d H:i:s'),
                    'createdby'   => getSession('id'),
                    'updateddate' => date('Y-m-d H:i:s'),
                    'updatedby'   => getSession('id'),
                ]);
            }

            $res = [
                'sukses' => '1',
                'undfhuser' => $undfhuser,
                'undfhuserarr' => $undfhuserarr
            ];
            $this->db->transCommit();
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'err' => $e->getMessage(),
                'traceString' => $e->getTraceAsString()
            ];
            $this->db->transRollback();
        }

        $this->db->transComplete();
        $res['csrfToken'] = csrf_hash();
        echo json_encode($res);
    }
        
}

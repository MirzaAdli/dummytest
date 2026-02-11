<?php

namespace App\Controllers;

use App\Models\MFile;

class File extends BaseController
{
    protected $fileModel;
    protected $bc;

    public function __construct()
    {
        $this->fileModel = new MFile();
        $this->bc = [
            ['Setting', 'File']
        ];
    }

    // Halaman utama daftar file
    public function index()
    {
        return view('master/file/v_file', [
            'title'      => 'Upload File',
            'breadcrumb' => $this->bc,
            'section'    => 'Setting File',
        ]);
    }

    // Halaman form upload file
    public function form()
    {
        return view('master/file/v_form', [
            'title'      => 'Form File',
            'breadcrumb' => $this->bc,
            'section'    => 'Setting File',
            'form_type'  => 'add'
        ]);
    }

    // Proses upload file
    public function upload()
    {
        $file = $this->request->getFile('file');
        $description = $this->request->getPost('description');

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/files', $newName);

            $this->fileModel->insert([
                'filename'     => $newName,
                'filerealname' => $file->getClientName(),
                'filedirectory' => 'uploads/files/' . $newName,
                'description'  => $description
            ]);

            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false, 'error' => 'Upload gagal']);
    }
}

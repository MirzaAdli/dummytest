controller :
 public function addDetail()
    {
        $headerEncypted = $this->request->getPost('headerId');
        $headerId  = decrypting($headerEncypted);
        $productId = $this->request->getPost('productId');
        $uomId     = $this->request->getPost('uomId');
        $qty       = (float) $this->request->getPost('qty');

        $this->db->transBegin();

        try {
            if ($qty <= 0) {
                throw new \Exception('Qty harus lebih dari 0');
            }

            $product = $this->db->table('msproduct')
            ->select('price')
            ->where('id', $productId)
            ->get()
            ->getRowArray();

            if (!$product) {
                throw new \Exception('Product tidak ditemukan');
            }

            $price = (float) $product['price'];

            $data = [
                'headerid'    => $headerId,
                'productid'   => $productId,
                'uomid'       => $uomId,
                'qty'         => $qty,
                'price'       => $price,
                'isactive'    => true,
                'createdby'   => session('userid'),
                'createddate' => date('Y-m-d H:i:s')
            ];

            $res = $this->ModelPoHd->addDetail($data);

            $this->ModelPoHd->hitungGrandTotal($headerId);

            $this->db->transCommit();

            return $this->response->setJSON([
                'sukses' => 1,
                'pesan'  => 'Detail added'
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            return $this->response->setJSON([
                'sukses' => 0,
                'pesan'  => $e->getMessage()
            ]);
        }
    }

model :
 public function getDetails($headerId)
    {
        return $this->db->table('trpurchaseorderdt as dt')
            ->select('dt.*, p.productname, u.uomnm')
            ->join('msproduct p', 'p.id = dt.productid', 'left')
            ->join('msuom u', 'u.id = dt.uomid', 'left')
            ->where('dt.headerid', $headerId)
            ->where('dt.isactive', true)
            ->get()
            ->getResultArray();
    }

    public function addDetail($data)
    {
        return $this->db->table('trpurchaseorderdt')->insert($data);
    }

    public function updateDetail($data, $id)
    {
        return $this->db->table('trpurchaseorderdt')->update($data, ['id' => $id]);
    }

    public function deleteDetail($id) 
    {
        return $this->db->table('trpurchaseorderdt')->update(['isactive' => false], ['id' => $id]);
    }

v_form :
if ($.fn.DataTable.isDataTable('#detailsTable')) {
            $('#detailsTable').DataTable().destroy();
        }

        if (!$.fn.DataTable.isDataTable('#detailsTable')) {
            $('#detailsTable').DataTable({
                "searching": true,
                "paging": true,
                "lengthMenu": [5, 10, 25],
                "info": true,
                "language": {
                    "search": "Search details:"
                }
            });
        }

        $('#modaldetail').off('hidden.bs.modal').on('hidden.bs.modal', function() {
            if ($.fn.DataTable.isDataTable('#detailsTable')) {
                $('#detailsTable').DataTable().destroy();
            }
        });
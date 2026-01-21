<style>
  .main-content {
    max-height: 85vh;
    /* tinggi maksimal 85% layar */
    overflow-y: auto;
    /* scroll vertikal otomatis */
    overflow-x: hidden;
    /* sembunyikan scroll horizontal */
    padding-right: 8px;
    /* biar scrollbar tidak nutup konten */
  }
</style>

<div class="main-content content">
  <!-- Form Header -->
  <h5 class="fw-bold mb-3"><?= ($form_type == 'edit') ? 'Edit Sales Order' : 'Tambah Sales Order' ?></h5>
  <form id="form-salesorder" class="form" enctype="multipart/form-data">
    <?php if ($form_type == 'edit') : ?>
      <input type="hidden" id="id" name="id" value="<?= $headerid ?? ($row['id'] ?? '') ?>">
    <?php endif; ?>

    <div class="form-group mb-3">
      <label for="transcode" class="form-label fw-bold">Transcode</label>
      <input type="text" class="form-control form-control-sm" id="transcode" name="transcode"
        value="<?= ($form_type == 'edit') ? ($row['transcode'] ?? '') : '' ?>" required>
    </div>

    <div class="form-group mb-3">
      <label for="transdate" class="form-label fw-bold">Transaction Date</label>
      <input type="date" class="form-control form-control-sm" id="transdate" name="transdate"
        value="<?= ($form_type == 'edit') ? ($row['transdate'] ?? date('Y-m-d')) : date('Y-m-d') ?>" required>
    </div>

    <div class="form-group mb-3">
      <label for="customerid" class="form-label fw-bold">Customer</label>
      <select id="customerid" name="customerid" class="form-select form-select-sm" required>
        <option value="" selected disabled>-- Select Customer --</option>
        <?php if (!empty($customers)): ?>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"
              <?= ($form_type == 'edit' && $row['customerid'] == $c['id']) ? 'selected' : '' ?>>
              <?= $c['customername'] ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="form-group mb-3">
      <label for="description" class="form-label fw-bold">Description</label>
      <textarea class="form-control form-control-sm" id="description" name="description" rows="3"><?= ($form_type == 'edit') ? ($row['description'] ?? '') : '' ?></textarea>
    </div>

    <div class="modal-footer">
      
      <button type="submit" id="btn-submit" class="btn btn-primary btn-sm d-flex align-items-center">
        <i class="bx bx-check me-1"></i> <?= ($form_type == 'edit' ? 'Update' : 'Save') ?>
      </button>
    </div>
  </form>

  <hr>

  <!-- Form Detail + Table -->
  <?php if ($form_type == 'edit') : ?>
    <h5 class="mt-4">Sales Order Detail</h5>
    <form id="form-detail" class="form" enctype="multipart/form-data">
      <input type="hidden" name="headerid" value="<?= $row['id'] ?>">
      <input type="hidden" id="detailid" name="detailid" value="">

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Product</label>
        <select id="productid" name="productid" class="form-select form-select-sm" required>
          <option value="">-- Select Product --</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
              <?= $p['productname'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">UOM</label>
        <select id="uomid" name="uomid" class="form-select form-select-sm" required>
          <option value="" selected disabled>-- Select UOM --</option>
          <?php foreach ($uoms as $u): ?>
            <option value="<?= $u['id'] ?>"><?= $u['uomnm'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Qty</label>
        <input type="number" step="0.001" id="qty" name="qty" class="form-control form-control-sm" required>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Price</label>
        <input type="text" id="price" name="price" class="form-control form-control-sm" required>
      </div>

      <div class="modal-footer">
        <button type="submit" id="btn-detail" class="btn btn-primary btn-sm d-flex align-items-center">
          <i class="bx bx-check me-1"></i> Add
        </button>
        <button type="button" class="btn btn-warning dflex align-center" id="btn-reset">
          <i class="bx bx-refresh me-1"></i> Reset
        </button>
      </div>
    </form>
    <hr>

    <!-- Tabel Detail -->
    <div class="card mt-4 shadow-sm w-100 gap">
      <div class="card-body">
        <div class="table-responsive margin-t-14p">
          <table class="table table-bordered table-responsive-lg table-master fs-7 w-100" id="detailTable">
            <thead class="table-light">
              <tr>
                <td class="tableheader">No</td>
                <td class="tableheader">Product</td>
                <td class="tableheader">UOM</td>
                <td class="tableheader">Qty</td>
                <td class="tableheader">Price</td>
                <td class="tableheader">Actions</td>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
  $(document).ready(function() {

    // Submit header
    $('#form-salesorder').on('submit', function(e) {
      e.preventDefault();
      let csrf = decrypter($("#csrf_token").val());
      $("#csrf_token_form").val(csrf);

      let form_type = "<?= $form_type ?>";
      let link = (form_type === 'edit') ?
        "<?= getURL('salesorder/update') ?>" :
        "<?= getURL('salesorder/add') ?>";

      $.ajax({
        type: 'POST',
        url: link,
        data: new FormData(this),
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(res) {
          $("#csrf_token").val(encrypter(res.csrfToken));
          $("#csrf_token_form").val("");
          showNotif(res.sukses ? 'success' : 'error', res.pesan);
          if (res.sukses == 1) {
            close_modal('modaldetail');
            tbl.ajax.reload();

          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          showError(thrownError + ", please contact administrator.");
        }
      });
    });

    // Submit detail
    $('#form-detail').on('submit', function(e) {
      e.preventDefault();

      let url = $('#detailid').val() ?
        "<?= base_url('salesorder/updateDetail') ?>" :
        "<?= base_url('salesorder/addDetail') ?>";

      $.ajax({
        type: 'POST',
        url: url,
        data: new FormData(this),
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(res) {
          showNotif(res.sukses ? 'success' : 'error', res.pesan);
          if (res.sukses) {
            // reset form ke mode Add
            $('#form-detail')[0].reset();
            $('#productid, #uomid').val(null).trigger('change');
            $('#detailid').val('');
            $('#btn-detail')
              .html('<i class="bx bx-check me-1"></i> Add')
              .removeClass('btn-warning')
              .addClass('btn-primary');

            // update grandtotal di form
            $('#grandtotal').text(res.grandtotal);
            
            // reload detail table
            $('#detailTable').DataTable().ajax.reload(null, false);

            // reload header table kalau perlu
            if (typeof tbl !== 'undefined') {
              tbl.ajax.reload(null, false);
            }

            // update CSRF
            $("#csrf_token").val(encrypter(res.csrfToken));
          }
        }
      });
    });

    // Select2 server-side
    $('#customerid').select2({
      minimumResultsForSearch: 0,
      dropdownParent: $('#form-salesorder'),
      ajax: {
        url: '<?= base_url("salesorder/customer/list") ?>',
        type: 'POST',
        dataType: 'json',
        delay: 250,
        data: params => ({
          search: params.term
        }),
        processResults: data => ({
          results: data.items
        })
      }
    });

    $('#productid').select2({
      placeholder: '-- Select Product --',
      minimumResultsForSearch: 0,
      dropdownParent: $('#form-detail'),
      ajax: {
        url: '<?= base_url("salesorder/product/list") ?>',
        type: 'POST',
        dataType: 'json',
        delay: 250,
        data: params => ({
          search: params.term
        }),
        processResults: data => ({
          results: data.items
        })
      }
    });

    $('#uomid').select2({
      placeholder: '-- Select UOM --',
      minimumResultsForSearch: 0,
      dropdownParent: $('#form-detail'),
      ajax: {
        url: '<?= base_url("salesorder/uom/list") ?>',
        type: 'POST',
        dataType: 'json',
        delay: 250,
        data: params => ({
          search: params.term
        }),
        processResults: data => ({
          results: data.items
        })
      }
    });

    loadTable();
  });

  function loadTable() {
    $('#detailTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '<?= base_url("salesorder/detaildatatable/" . ($row['id'] ?? 0)) ?>',
        type: 'POST',
        data: function(d) {
          d.headerid = $('input[name="id"]').val();
        }
      },
      columns: [{
          data: 0
        }, // No
        {
          data: 1
        }, // Product
        {
          data: 2
        }, // UOM
        {
          data: 3
        }, // Qty
        {
          data: 4
        }, // Price
        {
          data: 5
        } // Actions
      ]
    });

    $('#btn-reset').on('click', function() {
      resetDetailForm();
    });
  }

  function deleteDataDt(these, params) {
    let id = params;
    $(these).attr('disabled', 'disabled');

    $.ajax({
      url: "<?= base_url('salesorder/deleteDetail') ?>",
      type: "POST",
      data: {
        id: id
      },
      dataType: "json",
      success: function(res) {
        $(these).removeAttr('disabled');
        showNotif(res.sukses ? 'success' : 'error', res.pesan);

        if (res.sukses == 1) {
          $('#form-detail')[0].reset();
          $('#productid, #uomid').val(null).trigger('change');
          $('#price').val('');
          $('#detailid').val('');

          reloadTable(); // refresh detail
          tbl.ajax.reload(); // refresh header
          $("#csrf_token").val(encrypter(res.csrfToken)); // update token
        }
      }
    });
  }

  function reloadTable() {
    $('#detailTable').DataTable().destroy();
    loadTable();
  }

  function editDetail(id, productId, uomId, qty, price, productName = '') {
    $('#detailid').val(id);
    $('#productid').val(productId).trigger('change');
    $('#uomid').val(uomId).trigger('change');
    $('#qty').val(parseFloat(qty));
    $('#price').val(parseFloat(price));

    $('#btn-detail')
      .html('<i class="bx bx-check me-1"></i> Update')
      .removeClass('btn-primary')
      .addClass('btn-warning');
  }

  function resetDetailForm() {
    $('#form-detail')[0].reset();
    $('#productid, #uomid').val(null).trigger('change');
    $('#price').val('');
    $('#detailid').val('');
    $('#btn-detail')
      .html('<i class="bx bx-check me-1"></i> Add')
      .removeClass('btn-warning')
      .addClass('btn-primary'); // ubah tombol kembali ke Add
  }
</script>
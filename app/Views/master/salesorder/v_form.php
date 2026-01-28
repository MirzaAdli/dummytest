<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>
<style>
  .main-content {
    margin-top: 100px;
  }
</style>
<div class="main-content content">
  <!-- Form Header -->
  <h5 class="fw-bold mb-3"><?= ($form_type == 'edit') ? 'Edit Sales Order' : 'Tambah Sales Order' ?></h5>
  <form id="form-salesorder" class="form" enctype="multipart/form-data">
    <?php if ($form_type === 'edit'): ?>
      <input type="hidden" id="headerid" name="headerid"
        value="<?= !empty($headerid) ? $headerid : (!empty($row['id']) ? $row['id'] : '') ?>">
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

    <div class="modal-footer" style="gap: 10px">
      <button type="submit" id="btn-submit"
        class="btn btn-primary btn-sm d-flex align-items-center">
        <i class="bx bx-check margin-r-2"></i>
        <span class="fw-normal fs-7"><?= ($form_type == 'edit' ? 'Update' : 'Save') ?></span>
      </button>
      <button type="button"
        class="btn btn-secondary btn-sm d-flex align-items-center"
        onclick="window.location.href='<?= base_url('salesorder') ?>'">
        <i class="bx bx-arrow-back margin-r-2"></i>
        <span class="fw-normal fs-7">Back</span>
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
        <input type="number" id="price" name="price" class="form-control form-control-sm" required>
      </div>

      <div class="modal-footer" style="gap: 10px;">
        <button type="submit" id="btn-detail" class="btn btn-primary btn-sm d-flex align-items-center">
          <i class="bx bx-check margin-r-2"></i>
          <span class="fw-normal fs-7">Add</span>
        </button>
        <button type="button" class="btn btn-warning dflex align-center" id="btn-reset" onclick="return resetForm('form-detail')" >
          <i class="bx bx-revision margin-r-2"></i>
          <span class="fw-normal fs-7">Reset</span>
        </button>
      </div>

    </form>
    <hr>

    <!-- Tabel Detail -->
    <div class="card mt-4 shadow-sm w-100 gap">
      <div class="card-body">
        <div class="table-responsive margin-t-14p">
          <table class="table table-bordered table-responsive-lg fs-7 w-100" id="detailTable">
            <thead>
              <tr>
                <th class="tableheader">No</th>
                <th class="tableheader">Product</th>
                <th class="tableheader">UOM</th>
                <th class="tableheader">Qty</th>
                <th class="tableheader">Price</th>
                <th class="tableheader">Actions</th>
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
<?= $this->include('template/v_footer') ?>
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
            window.location.href = "<?= base_url('salesorder') ?>";
            close_modal('modaldetail');
            if (typeof tbl !== 'undefined') {
              tbl.ajax.reload();
            }
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

      $('#btn-detail').prop('disabled', true);

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
            resetDetailForm();
            $('#grandtotal').text(res.grandtotal);

            // reload detail table
            if (detailTbl) {
              detailTbl.ajax.reload(null, false);
            }

            // reload header table kalau ada
            if (typeof tbl !== 'undefined') {
              tbl.ajax.reload(null, false);
            }

            // update CSRF
            $("#csrf_token").val(encrypter(res.csrfToken));
          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          showError(thrownError + ", please contact administrator.");
        },
        complete: function() {
          $('#btn-detail').prop('disabled', false);
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

  let detailTbl;

  function loadTable() {
    detailTbl = $('#detailTable').DataTable({
      serverSide: true,
      processing: true,

      ajax: {
        url: '<?= base_url(uri_string() . "/tables") ?>',
        type: 'POST',
      },
    });
  }

  function reloadTable() {
    $('#detailTable').DataTable().ajax.reload();
  }

  function deleteDataDt(these, id) {
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
          resetDetailForm();
          reloadTable();

          $("#csrf_token").val(encrypter(res.csrfToken));
        }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        showError(thrownError + ", please contact administrator.");
      }
    });
  }

  function resetDetailForm() {
    $('#form-detail')[0].reset();
    $('#productid, #uomid').val(null).trigger('change');
    $('#price').val('');
    $('#detailid').val('');
    $('#btn-detail')
      .html('<i class="bx bx-check me-1"></i> Add')
      .removeClass('btn-warning')
      .addClass('btn-primary');
  }
</script>
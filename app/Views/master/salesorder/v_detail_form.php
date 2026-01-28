<form id="form-edit-detail" style="padding-inline: 0px;" enctype="multipart/form-data">
    <input type="hidden" name="detailid" value="<?= $detail['id'] ?>">
    <input type="hidden" name="headerid" value="<?= $detail['headerid'] ?>">
    <input type="hidden" id="csrf_token_form" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

    <div class="form-group">
        <label class="required">Product :</label>
        <select name="productid" class="form-select form-select-sm" required>
            <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($detail['productid'] == $p['id']) ? 'selected' : '' ?>>
                    <?= $p['productname'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="required">UOM :</label>
        <select name="uomid" class="form-select form-select-sm" required>
            <?php foreach ($uoms as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($detail['uomid'] == $u['id']) ? 'selected' : '' ?>>
                    <?= $u['uomnm'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="required">Qty :</label>
        <input type="number" step="0.01" id="qty" name="qty"
            class="form-control form-control-sm"
            value="<?= rtrim(rtrim($detail['qty'], '0'), '.') ?>" required>
        <label class="required">Price :</label>
        <input type="number" step="0.01" id="price" name="price"
            class="form-control form-control-sm"
            value="<?= rtrim(rtrim($detail['price'], '0'), '.') ?>" required>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-warning dflex align-center" onclick="return resetForm('form-edit-detail')">
            <i class="bx bx-revision margin-r-2"></i>
            <span class="fw-normal fs-7">Reset</span>
        </button>
        <button type="button" id="btn-submit-detail" class="btn btn-primary dflex align-center">
            <i class="bx bx-check margin-r-2"></i>
            <span class="fw-normal fs-7">Update</span>
        </button>
    </div>
</form>

<script>
    $(document).ready(function() {
        // klik tombol → trigger submit
        $('#btn-submit-detail').click(function() {
            $('#form-edit-detail').trigger('submit');
        });

        // submit form detail (edit)
        $("#form-edit-detail").on('submit', function(e) {
            e.preventDefault();

            let csrf = decrypter($("#csrf_token").val());
            $("#csrf_token_form").val(csrf);

            let link = "<?= getURL('salesorder/updateDetail') ?>"; // edit detail
            let data = new FormData(this);

            $.ajax({
                type: 'post',
                url: link,
                data: data,
                dataType: "json",
                processData: false,
                contentType: false,
                success: function(response) {
                    $("#csrf_token").val(encrypter(response.csrfToken));
                    $("#csrf_token_form").val("");

                    let notif = response.sukses == 1 ? 'success' : 'error';
                    let pesan = response.pesan || (response.sukses == 1 ? 'Detail berhasil diupdate' : 'Gagal update detail');
                    showNotif(notif, pesan);

                    if (response.sukses == 1) {
                        close_modal('modaldetail');
                        if (typeof detailTbl !== 'undefined') {
                            detailTbl.ajax.reload(null, false);
                        }
                        if (typeof tbl !== 'undefined') {
                            tbl.ajax.reload(null, false);
                        }
                        $('#grandtotal').text(response.grandtotal);
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    showError(thrownError + ", please contact administrator for further assistance.");
                }
            });

            return false;
        });

        // --- Select2 untuk Product & UOM di form edit ---
        $('#form-edit-detail select[name="productid"]').select2({
            placeholder: '-- Select Product --',
            minimumResultsForSearch: 0,
            dropdownParent: $('#form-edit-detail'),
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

        $('#form-edit-detail select[name="uomid"]').select2({
            placeholder: '-- Select UOM --',
            minimumResultsForSearch: 0,
            dropdownParent: $('#form-edit-detail'),
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

    });
</script>
    <?= $this->include('template/v_header') ?>
    <?= $this->include('template/v_appbar') ?>

    <div class="main-content content margin-t-4">
        <div class="card-header dflex align-center justify-end">
            <button class="btn btn-primary d-flex align-center" onclick="return modalForm('Tambah Sales Order', 'modal-lg', '<?= getURL('salesorder/form') ?>')">
                <i class="bx bx-plus-circle margin-r-2"></i>
                <span class="fw-normal fs-7">Add New</span>
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive margin-t-14p">
                <table class="table table-bordered table-responsive-lg table-master fs-7 w-100" id="dataTable">
                    <thead>
                        <tr>
                            <td class="tableheader">No</td>
                            <td class="tableheader">Transcode</td>
                            <td class="tableheader">Transdate</td>
                            <td class="tableheader">Customer Name</td>
                            <td class="tableheader">Grand Total</td>
                            <td class="tableheader">Description</td>
                            <td class="tableheader">Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?= $this->include('template/v_footer') ?>
    <script>
        function submitData() {
            let link = $('#linksubmit').val(),
                transcode = $('#transcode').val(),
                transdate = $('#transdate').val(),
                customername = $('#customername').val(),
                description = $('#description').val(),
                id = $('#id').val();

            $.ajax({
                url: link,
                type: 'post',
                dataType: 'json',
                data: {
                    transcode: transcode,
                    transdate: transdate,
                    customername: customername,
                    description: description,
                    id: id
                },
                success: function(res) {
                    if (res.sukses === '1') {
                        alert(res.pesan);
                        $('#transcode').val("");
                        $('#transdate').val("");
                        $('#customername').val("");
                        $('#description').val("");
                        tbl.ajax.reload();
                    } else {
                        alert(res.pesan);
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    alert("Request gagal: " + thrownError);
                }
            });
        }
    </script>